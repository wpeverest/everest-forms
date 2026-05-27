<?php
/**
 * High-level form builder used by the create-form/update-form abilities.
 *
 * Translates a human/LLM-friendly descriptor:
 *
 *   [
 *     'title'    => 'Contact 2026',
 *     'settings' => [ 'form_desc' => '...' ],
 *     'layout'   => [
 *        [ 'row' => [
 *            [ 'type' => 'first-name', 'label' => 'First', 'grid' => 1 ],
 *            [ 'type' => 'last-name',  'label' => 'Last',  'grid' => 2 ],
 *        ] ],
 *        [ 'row' => [ [ 'type' => 'email', 'label' => 'Email' ] ] ],
 *     ],
 *   ]
 *
 * ...into the EVF form-data shape persisted to `post_content` (form_fields,
 * structure.row_N.grid_M, form_field_id, settings, id).
 *
 * Also accepts the flat `fields => [...]` shape; that auto-stacks into rows.
 *
 * Validation happens against {@see EVF_Field_Schemas}: unknown types emit a
 * structured error pointing the caller at the addon that ships them; missing
 * required attributes (e.g. choices on a select) are flagged before any DB
 * write. Build is in-memory; the caller does the single update() call, which
 * keeps multi-field operations atomic.
 *
 * @package EverestForms\Abilities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builder.
 */
class EVF_Form_Builder {

	/**
	 * Build / mutate an EVF form data array from a high-level descriptor.
	 *
	 * @param array $existing  Existing form-data array (decoded post_content) or empty for a fresh build.
	 * @param array $descriptor High-level descriptor (see file docblock).
	 * @return array|WP_Error Updated form-data array, or WP_Error on validation failure.
	 */
	public static function build( array $existing, array $descriptor ) {
		$data = $existing;

		// --- Multi-Part addon gate ------------------------------------------
		// If the descriptor asks for multi-part behavior, the Multi-Part Forms
		// addon must be installed *and* active. Surface the same structured
		// error the field-type validator uses, so the LLM can route the user
		// into the activate-addon flow before any DB write happens.
		$wants_conversational = ! empty( $descriptor['conversational']['enabled'] );

		$wants_multipart = ! empty( $descriptor['multi_part']['enabled'] )
			|| ( isset( $descriptor['multi_part']['parts'] ) && is_array( $descriptor['multi_part']['parts'] ) && count( $descriptor['multi_part']['parts'] ) > 0 )
			|| self::layout_has_parts( $descriptor );

		// Conversational + Multi-Part are mutually exclusive (same UI surface).
		if ( $wants_multipart && $wants_conversational ) {
			return new WP_Error(
				'evf_incompatible_features',
				'Multi-Part and Conversational layouts cannot be enabled on the same form. Pick one.',
				array( 'status' => 400 )
			);
		}

		if ( $wants_multipart ) {
			$mp_check = self::check_multipart_available();
			if ( $mp_check ) {
				return new WP_Error(
					'evf_addon_required',
					$mp_check['message'],
					array( 'status' => 400, 'errors' => array( $mp_check ) )
				);
			}
		}

		if ( $wants_conversational ) {
			$cf_check = self::check_conversational_available();
			if ( $cf_check ) {
				return new WP_Error(
					'evf_addon_required',
					$cf_check['message'],
					array( 'status' => 400, 'errors' => array( $cf_check ) )
				);
			}
		}

		// --- Top-level settings ---------------------------------------------
		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			$data['settings'] = array();
		}

		if ( isset( $descriptor['title'] ) ) {
			$data['settings']['form_title'] = sanitize_text_field( (string) $descriptor['title'] );
		}
		if ( isset( $descriptor['description'] ) ) {
			$data['settings']['form_desc'] = wp_kses_post( (string) $descriptor['description'] );
		}
		if ( isset( $descriptor['settings'] ) && is_array( $descriptor['settings'] ) ) {
			// Before deep-merging caller-supplied settings, verify any addon-
			// gated toggle (enable_save_and_continue, pdf_enabled, etc.) has
			// the matching addon active. Otherwise the option lands in
			// post_content silently and the feature is broken without the
			// caller knowing — exactly the footgun Save and Continue hit.
			$settings_check = self::check_settings_addons_available( $descriptor['settings'] );
			if ( $settings_check ) {
				return new WP_Error(
					'evf_addon_required',
					$settings_check['message'],
					array( 'status' => 400, 'errors' => array( $settings_check ) )
				);
			}
			$data['settings'] = self::deep_merge( $data['settings'], $descriptor['settings'] );
		}

		// --- Existing fields / structure / counter --------------------------
		$existing_fields = isset( $data['form_fields'] ) && is_array( $data['form_fields'] ) ? $data['form_fields'] : array();
		$structure       = isset( $data['structure'] ) && is_array( $data['structure'] ) ? $data['structure'] : array();
		$counter         = isset( $data['form_field_id'] ) ? (int) $data['form_field_id'] : count( $existing_fields );

		// Normalize layout descriptor: support both `layout` and flat `fields`.
		$layout = self::normalize_layout( $descriptor );

		// Validate first (no DB writes yet).
		$registered = EVF_Field_Schemas::registered_types();
		$errors     = array();
		$row_idx    = 0;
		foreach ( $layout as $row ) {
			$row_idx++;
			$col_idx = 0;
			foreach ( $row['row'] as $field ) {
				$col_idx++;
				$err = self::validate_field( $field, $registered, $row_idx, $col_idx );
				if ( $err ) {
					$errors[] = $err;
				}
			}
		}
		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'evf_field_validation_failed',
				sprintf( '%d field validation error(s).', count( $errors ) ),
				array( 'status' => 400, 'errors' => $errors )
			);
		}

		// --- Walk layout and produce field defs + structure rows ------------
		// Track which rows belong to which part as we emit them.
		$row_keys_by_part = array(); // part_idx (1-based) => [ row_key, row_key, ... ]

		$row_idx = 0;
		foreach ( $layout as $row ) {
			$row_idx++;
			$row_key = 'row_' . self::next_row_key( $structure );

			// Capture the row's part assignment if any.
			$part_idx = isset( $row['part'] ) ? max( 1, (int) $row['part'] ) : 0;
			if ( $part_idx > 0 ) {
				if ( ! isset( $row_keys_by_part[ $part_idx ] ) ) {
					$row_keys_by_part[ $part_idx ] = array();
				}
				$row_keys_by_part[ $part_idx ][] = $row_key;
			}

			// Default each row to a single full-width grid. Only allocate
			// additional grids (grid_2, grid_3, ...) when the row actually
			// holds multiple fields or fields specify explicit grid numbers.
			// This avoids producing a two-column row with one empty column
			// for the single-field case.
			$explicit_grids = self::row_has_explicit_grids( $row['row'] );
			$auto_grid      = 1;
			$auto_count     = count( $row['row'] );

			if ( ! isset( $structure[ $row_key ] ) ) {
				$structure[ $row_key ] = array( 'grid_1' => array() );
			}

			foreach ( $row['row'] as $field ) {
				$counter++;
				$field_id   = ( function_exists( 'evf_get_random_string' ) ? evf_get_random_string() : wp_generate_password( 10, false ) ) . '-' . $counter;
				$field_data = self::compose_field( $field, $field_id, $counter );

				$existing_fields[ $field_id ] = $field_data;

				$grid_no = $explicit_grids
					? max( 1, (int) ( isset( $field['grid'] ) ? $field['grid'] : 1 ) )
					: ( $auto_count > 1 ? $auto_grid++ : 1 );

				$grid_key = 'grid_' . $grid_no;
				if ( ! isset( $structure[ $row_key ][ $grid_key ] ) ) {
					$structure[ $row_key ][ $grid_key ] = array();
				}
				$structure[ $row_key ][ $grid_key ][] = $field_id;
			}
		}

		// --- Direct form_fields patches (update path) -----------------------
		if ( isset( $descriptor['form_fields_patch'] ) && is_array( $descriptor['form_fields_patch'] ) ) {
			foreach ( $descriptor['form_fields_patch'] as $fid => $patch ) {
				if ( isset( $existing_fields[ $fid ] ) && is_array( $patch ) ) {
					$existing_fields[ $fid ] = array_merge( $existing_fields[ $fid ], $patch );
				}
			}
		}

		$data['form_fields']   = $existing_fields;
		$data['structure']     = $structure;
		$data['form_field_id'] = (string) $counter;

		// --- Multi-Part block -----------------------------------------------
		if ( $wants_multipart ) {
			$data = self::apply_multipart( $data, $descriptor, $row_keys_by_part );
		}

		// --- Conversational block -------------------------------------------
		if ( $wants_conversational ) {
			$data = self::apply_conversational( $data, $descriptor );
		}

		return $data;
	}

	/**
	 * Whether the layout descriptor uses per-row `part` assignments.
	 *
	 * @param array $descriptor Descriptor.
	 * @return bool
	 */
	protected static function layout_has_parts( $descriptor ) {
		if ( ! isset( $descriptor['layout'] ) || ! is_array( $descriptor['layout'] ) ) {
			return false;
		}
		foreach ( $descriptor['layout'] as $row ) {
			if ( isset( $row['part'] ) && (int) $row['part'] > 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether the Multi-Part Forms addon is usable.
	 *
	 * @return array|null Error array (matching field-validator shape) if not usable, null otherwise.
	 */
	protected static function check_multipart_available() {
		$plugin = 'everest-forms-multi-part/everest-forms-multi-part.php';

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins   = function_exists( 'get_plugins' ) ? (array) get_plugins() : array();
		$installed = isset( $plugins[ $plugin ] );
		$active    = function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) : false;

		if ( $active ) {
			return null;
		}

		$action = $installed ? 'activate' : 'install_or_upgrade';
		return array(
			'code'      => 'addon_required',
			'feature'   => 'multi_part',
			'addon'     => array( 'addon' => 'Multi-Part Forms', 'plugin' => $plugin ),
			'installed' => $installed,
			'active'    => false,
			'action'    => $action,
			'message'   => $installed
				? sprintf( 'Multi-step (page break) layout requires the "Multi-Part Forms" addon, which is installed but not active. Ask the user to confirm, then call the "activate-addon" ability with plugin="%s". If they decline, omit `multi_part`/row parts and proceed with a single-page form.', $plugin )
				: 'Multi-step (page break) layout requires the "Multi-Part Forms" addon, which is not installed on this site. The user must install/purchase it from their Everest Forms account, or upgrade their plan if it isn\'t included. If they decline, omit `multi_part`/row parts and proceed with a single-page form.',
		);
	}

	/**
	 * Scan caller-supplied settings for addon-gated toggle keys whose
	 * underlying addon isn't active. Returns the first offending entry
	 * in the same error envelope as the field validator so the chat UI
	 * can render the standard activation prompt.
	 *
	 * @param array $settings Caller-supplied settings block (the descriptor's
	 *                        `settings`, not the post_content `settings`).
	 * @return array|null
	 */
	protected static function check_settings_addons_available( array $settings ) {
		$gated = EVF_Field_Schemas::setting_addon_hints();
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = function_exists( 'get_plugins' ) ? (array) get_plugins() : array();

		foreach ( $gated as $key => $hint ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				continue;
			}
			$value = $settings[ $key ];
			// Skip falsy values — the caller is *disabling* the toggle, which
			// is safe regardless of addon state.
			if ( empty( $value ) || '0' === (string) $value || 'false' === strtolower( (string) $value ) ) {
				continue;
			}

			$plugin    = $hint['plugin'];
			$installed = isset( $plugins[ $plugin ] );
			$active    = function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) : false;
			if ( $active ) {
				continue;
			}

			$action = $installed ? 'activate' : 'install_or_upgrade';
			return array(
				'code'      => 'addon_required',
				'feature'   => $key,
				'addon'     => $hint,
				'installed' => $installed,
				'active'    => false,
				'action'    => $action,
				'message'   => $installed
					? sprintf( 'Setting "%s" needs the "%s" addon, which is installed but not active. Ask the user to confirm, then call the "activate-addon" ability with plugin="%s". If they decline, omit `%s` from settings and proceed.', $key, $hint['addon'], $plugin, $key )
					: sprintf( 'Setting "%s" needs the "%s" addon, which is not installed on this site. The user must install/purchase it from their Everest Forms account, or upgrade their plan if it isn\'t included. If they decline, omit `%s` from settings and proceed.', $key, $hint['addon'], $key ),
			);
		}
		return null;
	}

	/**
	 * Check whether the Conversational Forms addon is usable.
	 *
	 * @return array|null Error array (matching field-validator shape) if not usable, null otherwise.
	 */
	protected static function check_conversational_available() {
		$plugin = 'everest-forms-conversational-forms/everest-forms-conversational-forms.php';

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins   = function_exists( 'get_plugins' ) ? (array) get_plugins() : array();
		$installed = isset( $plugins[ $plugin ] );
		$active    = function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) : false;

		if ( $active ) {
			return null;
		}

		$action = $installed ? 'activate' : 'install_or_upgrade';
		return array(
			'code'      => 'addon_required',
			'feature'   => 'conversational',
			'addon'     => array( 'addon' => 'Conversational Forms', 'plugin' => $plugin ),
			'installed' => $installed,
			'active'    => false,
			'action'    => $action,
			'message'   => $installed
				? sprintf( 'Conversational (one-question-at-a-time) layout requires the "Conversational Forms" addon, which is installed but not active. Ask the user to confirm, then call the "activate-addon" ability with plugin="%s". If they decline, omit `conversational` and proceed with a standard form.', $plugin )
				: 'Conversational (one-question-at-a-time) layout requires the "Conversational Forms" addon, which is not installed on this site. The user must install/purchase it from their Everest Forms account, or upgrade their plan if it isn\'t included. If they decline, omit `conversational` and proceed with a standard form.',
		);
	}

	/**
	 * Write the conversational settings block.
	 *
	 * Data shape EVF expects:
	 *   $form_data['settings']['enable_conversational_forms']                              = '1'
	 *   $form_data['settings']['conversational_forms']['conversational_forms_url']        = '<slug>'   (optional)
	 *   $form_data['settings']['conversational_forms']['conversational_forms_title']      = '<title>'  (optional)
	 *   $form_data['settings']['conversational_forms']['conversational_forms_description']= '<desc>'   (optional)
	 *
	 * @param array $data       Form data so far.
	 * @param array $descriptor Descriptor.
	 * @return array Updated form data.
	 */
	protected static function apply_conversational( $data, $descriptor ) {
		$data['settings']['enable_conversational_forms'] = '1';

		$existing_cf = isset( $data['settings']['conversational_forms'] ) && is_array( $data['settings']['conversational_forms'] )
			? $data['settings']['conversational_forms']
			: array();

		$cf = $existing_cf;
		$src = isset( $descriptor['conversational'] ) && is_array( $descriptor['conversational'] ) ? $descriptor['conversational'] : array();

		if ( isset( $src['slug'] ) ) {
			$cf['conversational_forms_url'] = sanitize_title( (string) $src['slug'] );
		}
		if ( isset( $src['title'] ) ) {
			$cf['conversational_forms_title'] = sanitize_text_field( (string) $src['title'] );
		}
		if ( isset( $src['description'] ) ) {
			$cf['conversational_forms_description'] = wp_kses_post( (string) $src['description'] );
		}
		// Allow opaque pass-through of any other conversational_forms_* keys.
		foreach ( $src as $k => $v ) {
			if ( in_array( $k, array( 'enabled', 'slug', 'title', 'description' ), true ) ) {
				continue;
			}
			$key = sanitize_key( (string) $k );
			if ( is_scalar( $v ) ) {
				$cf[ $key ] = sanitize_text_field( (string) $v );
			} elseif ( is_array( $v ) ) {
				$cf[ $key ] = $v;
			}
		}

		$data['settings']['conversational_forms'] = $cf;
		return $data;
	}

	/**
	 * Write the multi_part block and enable_multi_part toggle into form data.
	 *
	 * @param array $data             Form data so far.
	 * @param array $descriptor       Descriptor.
	 * @param array $row_keys_by_part Map of part_idx => [row_key, ...].
	 * @return array Updated form data.
	 */
	protected static function apply_multipart( $data, $descriptor, $row_keys_by_part ) {
		$data['settings']['enable_multi_part'] = '1';

		$declared = isset( $descriptor['multi_part']['parts'] ) && is_array( $descriptor['multi_part']['parts'] )
			? $descriptor['multi_part']['parts']
			: array();

		// Derive the part list: prefer explicit declarations, otherwise fall back
		// to a "Step N" series sized to cover whichever part index any row uses.
		$max_used_part = 0;
		foreach ( $row_keys_by_part as $p => $_rows ) {
			$max_used_part = max( $max_used_part, (int) $p );
		}
		$part_count = max( count( $declared ), $max_used_part, 1 );

		$existing_parts = isset( $data['multi_part'] ) && is_array( $data['multi_part'] ) ? array_values( $data['multi_part'] ) : array();
		$built          = array();
		for ( $i = 1; $i <= $part_count; $i++ ) {
			$idx_zero = $i - 1;
			$prior    = isset( $existing_parts[ $idx_zero ] ) && is_array( $existing_parts[ $idx_zero ] ) ? $existing_parts[ $idx_zero ] : array();
			$declared_entry = isset( $declared[ $idx_zero ] ) && is_array( $declared[ $idx_zero ] ) ? $declared[ $idx_zero ] : array();

			$name = '';
			if ( isset( $declared_entry['name'] ) ) {
				$name = sanitize_text_field( (string) $declared_entry['name'] );
			} elseif ( isset( $prior['name'] ) ) {
				$name = (string) $prior['name'];
			} else {
				$name = sprintf( 'Step %d', $i );
			}

			// Build the rows map for this part (1-based index → row_key).
			$rows_for_part = isset( $row_keys_by_part[ $i ] ) ? $row_keys_by_part[ $i ] : array();
			$rows_map      = array();
			$slot          = 1;
			foreach ( $rows_for_part as $row_key ) {
				$rows_map[ $slot ] = $row_key;
				$slot++;
			}

			$built[ $idx_zero ] = array_merge(
				$prior,
				array(
					'id'   => $i,
					'name' => $name,
				),
				$rows_map ? array( 'rows' => $rows_map ) : array()
			);
		}

		$data['multi_part'] = $built;
		return $data;
	}

	/* ----------------------------------------------------------------------
	 * Internals
	 * ---------------------------------------------------------------------- */

	/**
	 * Normalize the layout from the descriptor.
	 *
	 * Returns a list of rows, each `{ row: [field, field, ...] }`. Accepts:
	 *   - 'layout' => [{row: [...]}]
	 *   - 'fields' => [field, field, ...]  (auto-stacked one per row)
	 *
	 * @param array $descriptor Descriptor.
	 * @return array
	 */
	protected static function normalize_layout( array $descriptor ) {
		if ( isset( $descriptor['layout'] ) && is_array( $descriptor['layout'] ) ) {
			$out = array();
			foreach ( $descriptor['layout'] as $row ) {
				if ( isset( $row['row'] ) && is_array( $row['row'] ) ) {
					$entry = array( 'row' => array_values( $row['row'] ) );
					// Preserve per-row metadata the walker needs (multi-part step
					// assignment in particular). Without this, `part: 2/3` was
					// silently dropped here and every field ended up in step 1.
					if ( isset( $row['part'] ) ) {
						$entry['part'] = max( 1, (int) $row['part'] );
					}
					$out[] = $entry;
				}
			}
			return $out;
		}
		if ( isset( $descriptor['fields'] ) && is_array( $descriptor['fields'] ) ) {
			$out = array();
			foreach ( $descriptor['fields'] as $field ) {
				if ( is_array( $field ) ) {
					$entry = array( 'row' => array( $field ) );
					// Allow flat `fields` entries to carry a `part` too, so a
					// caller can do single-field-per-row multi-step layouts
					// without using the richer `layout` shape.
					if ( isset( $field['part'] ) ) {
						$entry['part'] = max( 1, (int) $field['part'] );
					}
					$out[] = $entry;
				}
			}
			return $out;
		}
		return array();
	}

	/**
	 * Validate a field definition against the schema registry.
	 *
	 * @param array $field      Field def.
	 * @param array $registered Map of currently-registered type ids.
	 * @param int   $row_idx    Row index (for error reporting).
	 * @param int   $col_idx    Column index (for error reporting).
	 * @return array|null Error or null.
	 */
	protected static function validate_field( $field, $registered, $row_idx, $col_idx ) {
		if ( ! is_array( $field ) || empty( $field['type'] ) ) {
			return array(
				'row'     => $row_idx,
				'col'     => $col_idx,
				'code'    => 'missing_type',
				'message' => 'Field is missing required "type".',
			);
		}
		$type = (string) $field['type'];

		$hint        = EVF_Field_Schemas::addon_for( $type );
		$addon_inactive = false;
		$installed     = false;
		$active        = false;
		if ( $hint && ! empty( $hint['plugin'] ) ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins        = function_exists( 'get_plugins' ) ? (array) get_plugins() : array();
			$installed      = isset( $plugins[ $hint['plugin'] ] );
			$active         = function_exists( 'is_plugin_active' ) ? is_plugin_active( $hint['plugin'] ) : false;
			$addon_inactive = ! $active;
		}

		// A field type is unusable if either:
		//   (a) it isn't registered at all (no class in core), OR
		//   (b) it has an addon hint AND that addon plugin is inactive.
		// Case (b) catches types like `likert`, `yes-no`, `rating` whose field
		// class lives in core but whose real implementation ships in an addon.
		if ( ! isset( $registered[ $type ] ) || $addon_inactive ) {
			$action = $hint
				? ( $installed ? 'activate' : 'install_or_upgrade' )
				: 'unknown';

			$message = $hint
				? ( 'activate' === $action
					? sprintf( 'Field type "%s" needs the "%s" addon, which is installed but not active. Ask the user to confirm, then call the "activate-addon" ability with plugin="%s". If the user declines, omit this field and proceed with the rest.', $type, $hint['addon'], $hint['plugin'] )
					: sprintf( 'Field type "%s" needs the "%s" addon, which is not installed on this site. The user must install/purchase it from their Everest Forms account (or upgrade their plan if it isn\'t in their tier). If the user declines, omit this field and proceed with the rest.', $type, $hint['addon'] )
				)
				: sprintf( 'Field type "%s" is not registered on this site.', $type );

			return array(
				'row'       => $row_idx,
				'col'       => $col_idx,
				'code'      => 'unregistered_type',
				'type'      => $type,
				'addon'     => $hint,
				'installed' => $installed,
				'active'    => $active,
				'action'    => $action, // 'activate' | 'install_or_upgrade' | 'unknown'
				'message'   => $message,
			);
		}

		$schema = EVF_Field_Schemas::for_type( $type );
		if ( $schema && $schema['requires_choices'] && empty( $field['choices'] ) ) {
			return array(
				'row'     => $row_idx,
				'col'     => $col_idx,
				'code'    => 'missing_choices',
				'type'    => $type,
				'message' => sprintf( 'Field type "%s" requires a non-empty "choices" array.', $type ),
			);
		}
		return null;
	}

	/**
	 * Compose the final EVF field array (id, type, label, meta-key, plus
	 * normalized choices / sublabels / common props).
	 *
	 * @param array  $field    Input field def.
	 * @param string $field_id Generated field id.
	 * @param int    $counter  Position counter (for unique meta-key).
	 * @return array
	 */
	protected static function compose_field( $field, $field_id, $counter ) {
		$type  = sanitize_key( (string) $field['type'] );
		$label = isset( $field['label'] ) && '' !== $field['label']
			? sanitize_text_field( (string) $field['label'] )
			: ucfirst( str_replace( array( '-', '_' ), ' ', $type ) );
		$meta_key = sanitize_key( $label . '_' . $counter );

		$out = array(
			'id'       => $field_id,
			'type'     => $type,
			'label'    => $label,
			'meta-key' => $meta_key,
		);

		if ( ! empty( $field['required'] ) ) {
			$out['required'] = '1';
		}
		if ( isset( $field['placeholder'] ) ) {
			$out['placeholder'] = sanitize_text_field( (string) $field['placeholder'] );
		}
		if ( isset( $field['description'] ) ) {
			$out['description'] = wp_kses_post( (string) $field['description'] );
		}
		if ( isset( $field['default_value'] ) ) {
			$out['default_value'] = is_scalar( $field['default_value'] ) ? (string) $field['default_value'] : $field['default_value'];
		}
		if ( isset( $field['css'] ) ) {
			$out['css'] = sanitize_text_field( (string) $field['css'] );
		}

		// Choices for select/radio/checkbox/country.
		if ( isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
			$out['choices'] = self::normalize_choices( $field['choices'] );
		}

		// First/last name sublabels.
		if ( isset( $field['sublabels'] ) && is_array( $field['sublabels'] ) ) {
			foreach ( $field['sublabels'] as $sk => $sv ) {
				$out[ 'sublabel_' . sanitize_key( (string) $sk ) ] = sanitize_text_field( (string) $sv );
			}
		}

		// Pass-through for anything else (numeric mins, formats, etc.) — sanitized scalars.
		$skip = array( 'type', 'label', 'required', 'placeholder', 'description', 'default_value', 'css', 'choices', 'sublabels', 'grid' );
		foreach ( $field as $k => $v ) {
			if ( in_array( $k, $skip, true ) ) {
				continue;
			}
			$key = sanitize_key( (string) $k );
			if ( is_scalar( $v ) ) {
				$out[ $key ] = sanitize_text_field( (string) $v );
			} elseif ( is_array( $v ) ) {
				$out[ $key ] = $v; // arrays passed through; EVF expects them for some advanced types
			}
		}

		return $out;
	}

	/**
	 * Normalize a choices array.
	 *
	 * Accepts either an indexed list of strings (`["Red","Green","Blue"]`) or
	 * a list of `{label, value?, default?}` objects, and produces the
	 * `{1: {label, value, default}, 2: {...}}` shape EVF expects.
	 *
	 * @param array $choices Input.
	 * @return array
	 */
	protected static function normalize_choices( array $choices ) {
		$out = array();
		$i   = 0;
		foreach ( $choices as $choice ) {
			$i++;
			if ( is_string( $choice ) ) {
				$out[ $i ] = array( 'label' => sanitize_text_field( $choice ), 'value' => '', 'default' => '' );
			} elseif ( is_array( $choice ) ) {
				$out[ $i ] = array(
					'label'   => isset( $choice['label'] ) ? sanitize_text_field( (string) $choice['label'] ) : '',
					'value'   => isset( $choice['value'] ) ? sanitize_text_field( (string) $choice['value'] ) : '',
					'default' => ! empty( $choice['default'] ) ? '1' : '',
				);
			}
		}
		return $out;
	}

	/**
	 * Pick the next free row key index, scanning existing structure to keep
	 * row ordering stable across multiple builder calls.
	 *
	 * @param array $structure Structure map.
	 * @return int
	 */
	protected static function next_row_key( $structure ) {
		$max = 0;
		foreach ( array_keys( $structure ) as $k ) {
			if ( preg_match( '/^row_(\d+)$/', (string) $k, $m ) ) {
				$max = max( $max, (int) $m[1] );
			}
		}
		return $max + 1;
	}

	/**
	 * Whether every field in a row carries an explicit `grid` value.
	 *
	 * @param array $row_fields Row fields.
	 * @return bool
	 */
	protected static function row_has_explicit_grids( $row_fields ) {
		foreach ( $row_fields as $f ) {
			if ( ! isset( $f['grid'] ) ) {
				return false;
			}
		}
		return ! empty( $row_fields );
	}

	/**
	 * Recursive merge that overwrites scalar values but merges associative arrays.
	 *
	 * @param array $base    Base.
	 * @param array $overlay Overlay.
	 * @return array
	 */
	protected static function deep_merge( array $base, array $overlay ) {
		foreach ( $overlay as $k => $v ) {
			if ( is_array( $v ) && isset( $base[ $k ] ) && is_array( $base[ $k ] ) ) {
				$base[ $k ] = self::deep_merge( $base[ $k ], $v );
			} else {
				$base[ $k ] = $v;
			}
		}
		return $base;
	}
}
