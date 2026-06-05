<?php
/**
 * EverestForms Builder Fields
 *
 * @package EverestForms\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Builder_Fields', false ) ) {
	return new EVF_Builder_Fields();
}

/**
 * EVF_Builder_Fields class.
 */
class EVF_Builder_Fields extends EVF_Builder_Page {

	/**
	 * Contains information for multi-part forms.
	 *
	 * Forms that do not contain parts return false, otherwise returns an array
	 * that contains the number of total pages and page counter used when
	 * displaying part rows.
	 *
	 * @since 1.3.2
	 *
	 * @var array
	 */
	public static $parts = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id      = 'fields';
		$this->label   = __( 'Fields', 'everest-forms' );
		$this->sidebar = true;

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		if ( is_object( $this->form ) ) {
			add_action( 'everest_forms_builder_fields', array( $this, 'output_fields' ) );
			add_action( 'everest_forms_builder_fields_options', array( $this, 'output_fields_options' ) );
			add_action( 'everest_forms_builder_fields_preview', array( $this, 'output_fields_preview' ) );
		}
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		?>
		<div class="everest-forms-fields-tab">
			<a href="#" id="add-fields" class="fields active"><?php esc_html_e( 'Add Fields', 'everest-forms' ); ?></a>
			<a href="#" id="field-options" class="options"><?php esc_html_e( 'Field Options', 'everest-forms' ); ?></a>
			<?php do_action( 'everest_forms_builder_fields_tab', $this->form ); ?>
		</div>
		<div class="everest-forms-tab-content">
			<div class="everest-forms-add-fields">
				<div class="everest-forms-input-group everest-forms-search-input evf-mb-3">
					<input id="everest-forms-search-fields" class="everest-forms-input-control everest-forms-search-fields" type="text" placeholder="<?php esc_attr_e( 'Search fields&hellip;', 'everest-forms' ); ?>" />
					<div class="everest-forms-input-group__append">
						<div class="everest-forms-input-group__text">
							<svg xmlns="http://www.w3.org/2000/svg" height="20px" width="20px" viewBox="0 0 24 24" fill="#a1a4b9"><path d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z"/></svg>
						</div>
					</div>
				</div>
				<div class="hidden everest-forms-fields-not-found">
					<img src="<?php echo esc_attr( plugin_dir_url( EVF_PLUGIN_FILE ) . 'assets/images/fields-not-found.png' ); ?>" />
					<h3 class="everest-forms-fields-not-found__title"><?php esc_html_e( 'Oops!', 'everest-forms' ); ?></h3>
					<span><?php esc_html_e( 'There is not such field that you are searching for.', 'everest-forms' ); ?></span>
				</div>
				<?php do_action( 'everest_forms_builder_fields', $this->form ); ?>
			</div>
			<div class="everest-forms-field-options">
				<?php do_action( 'everest_forms_builder_fields_options', $this->form ); ?>
			</div>
			<?php do_action( 'everest_forms_builder_fields_tab_content', $this->form ); ?>
		</div>
		<?php
	}

	/**
	 * Outputs the builder content.
	 */
	public function output_content() {
		?>
		<div class="everest-forms-preview-wrap">
			<div class="everest-forms-preview">
				<div class="everest-forms-title-desc">
					<input id= "evf-edit-form-name" type="text" class="everest-forms-form-name everest-forms-name-input" value ="<?php echo isset( $this->form->post_title ) ? esc_html( $this->form->post_title ) : esc_html__( 'Form not found.', 'everest-forms' ); ?>" disabled autocomplete="off" required>
					<span id="edit-form-name" class = "evf-icon dashicons dashicons-edit"></span>
				</div>
				<div class="everest-forms-field-wrap">
					<?php do_action( 'everest_forms_builder_fields_preview', $this->form ); ?>
				</div>
				<?php evf_debug_data( $this->form_data ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output fields group buttons.
	 */
	public function output_fields() {
		$containers = apply_filters(
			'everest_forms_builder_layout_containers',
			array(
				array(
					'type'    => 'layout_one_col',
					'label'   => __( 'One Column', 'everest-forms' ),
					'columns' => 1,
					'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 24">
			<rect x="2" y="2" width="32" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
		</svg>',
				),
				array(
					'type'    => 'layout_two_col',
					'label'   => __( 'Two Column', 'everest-forms' ),
					'columns' => 2,
					'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 24">
			<rect x="2" y="2" width="14.5" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
			<rect x="19.5" y="2" width="14.5" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
		</svg>',
				),
				array(
					'type'    => 'layout_three_col',
					'label'   => __( 'Three Column', 'everest-forms' ),
					'columns' => 3,
					'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 24">
			<rect x="2" y="2" width="8.5" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
			<rect x="13.75" y="2" width="8.5" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
			<rect x="25" y="2" width="9" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/>
		</svg>',
				),
				array(
					'type'    => 'layout_four_col',
					'label'   => __( 'Four Column', 'everest-forms' ),
					'columns' => 4,
					'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 24">
			<rect x="2" y="2" width="5.75" height="20" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
			<rect x="10.25" y="2" width="5.75" height="20" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
			<rect x="18.5" y="2" width="5.75" height="20" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
			<rect x="27" y="2" width="7" height="20" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
		</svg>',
				),
			)
		);

		$form_fields = evf()->form_fields->form_fields();

		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $group => $form_field ) {
				?>
				<div class="everest-forms-add-fields-group open">
					<a href="#" class="everest-forms-add-fields-heading" data-group="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( evf_get_fields_group( $group ) ); ?><i class="handlediv"></i></a>
					<div class="evf-registered-buttons">
						<?php
						foreach ( $form_field as $field ) :
							$field_plan  = isset( $field->plan ) ? $field->plan : '';
							$addon_slug  = isset( $field->addon ) ? $field->addon : '';
							$field_links = isset( $field->links ) ? json_encode( $field->links ) : '';
							?>
							<button type="button" id="everest-forms-add-fields-<?php echo esc_attr( $field->type ); ?>" class="evf-registered-item <?php echo sanitize_html_class( $field->class ); ?>" data-field-type="<?php echo esc_attr( $field->type ); ?>" data-field-plan="<?php echo esc_attr( $field_plan ); ?>" data-addon-slug="<?php echo esc_attr( $addon_slug ); ?>" data-links="<?php echo esc_attr( $field_links ); ?>">
								<?php if ( isset( $field->icon ) ) : ?>
									<i class="<?php echo esc_attr( $field->icon ); ?>"></i>
								<?php endif; ?>
								<?php echo esc_html( $field->name ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php

				if ( 'advanced' === $group ) {
					?>
					<div class="everest-forms-add-fields-group open evf-layout-group">
						<a href="#" class="everest-forms-add-fields-heading" data-group="layout"><?php esc_html_e( 'Layout', 'everest-forms' ); ?><i class="handlediv"></i></a>
						<div class="evf-registered-buttons">
							<?php foreach ( $containers as $container ) : ?>
							<button type="button"
								class="evf-layout-container-btn"
								data-columns="<?php echo absint( $container['columns'] ); ?>"
								data-field-type="<?php echo esc_attr( $container['type'] ); ?>">
								<?php echo $container['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo esc_html( $container['label'] ); ?>
							</button>
							<?php endforeach; ?>
						</div>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * Output fields setting options.
	 */
	public function output_fields_options() {
		$fields         = isset( $this->form_data['form_fields'] ) ? $this->form_data['form_fields'] : array();
		$recaptcha_type = get_option( 'everest_forms_recaptcha_type', 'v2' );
		if ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
			if ( 'v2' === $recaptcha_type || 'v3' === $recaptcha_type ) {
				$recaptcha_type = 'recaptcha';
			}
			$fields['IWX5HFxv2j-18'] = array(
				'id'       => 'IWX5HFxv2j-18',
				'type'     => $recaptcha_type,
				'label'    => '',
				'meta-key' => $recaptcha_type . '_7543',
			);
		}

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$is_locked = evf_is_field_locked( $field['type'] );

				$field_option_class = apply_filters(
					'everest_forms_builder_field_option_class',
					array(
						'everest-forms-field-option',
						'everest-forms-field-option-' . esc_attr( $field['type'] ),
					),
					$field
				);

				// Locked Pro fields keep their settings rendered (inputs are NOT
				// disabled so values persist on save) but are visually locked.
				if ( $is_locked ) {
					$field_option_class[] = 'everest-forms-field-option-locked';
				}

				?>
				<div class="<?php echo esc_attr( implode( ' ', $field_option_class ) ); ?>" id="everest-forms-field-option-<?php echo esc_attr( $field['id'] ); ?>" data-field-id="<?php echo esc_attr( $field['id'] ); ?>" >
					<input type="hidden" name="form_fields[<?php echo esc_attr( $field['id'] ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" class="everest-forms-field-option-hidden-id" />
					<input type="hidden" name="form_fields[<?php echo esc_attr( $field['id'] ); ?>][type]" value="<?php echo esc_attr( $field['type'] ); ?>" class="everest-forms-field-option-hidden-type" />
					<?php
					if ( $is_locked ) {
						$this->locked_field_option_overlay( $field['type'] );
					}
					do_action( 'everest_forms_builder_fields_options_' . $field['type'], $field );
					?>
				</div>
				<?php
			}
		} else {
			printf( '<p class="no-fields">%s</p>', esc_html__( 'You don\'t have any fields yet.', 'everest-forms' ) );
		}
	}

	/**
	 * Outputs fields preview content.
	 */
	public function output_fields_preview() {
		$form_data = $this->form_data;
		$form_id   = absint( $form_data['id'] );
		$fields    = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$structure = isset( $form_data['structure'] ) ? $form_data['structure'] : array( 'row_1' => array() );
		$row_ids   = array_map(
			function ( $row_id ) {
				return str_replace( 'row_', '', $row_id );
			},
			array_keys( $structure )
		);

		/**
		 * BW compatiable for multi-parts form.
		 *
		 * @todo Remove in Major EVF version 1.6.0
		 */
		if ( defined( 'EVF_MULTI_PART_PLUGIN_FILE' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugin_data = get_plugin_data( EVF_MULTI_PART_PLUGIN_FILE, false, false );

			if ( version_compare( $plugin_data['Version'], '1.3.0', '<' ) ) {
				$settings_defaults = array(
					'indicator'       => 'progress',
					'indicator_color' => '#7e3bd0',
					'nav_align'       => 'center',
				);

				if ( isset( $form_data['settings']['enable_multi_part'] ) && evf_string_to_bool( $form_data['settings']['enable_multi_part'] ) ) {
					$settings = isset( $form_data['settings']['multi_part'] ) ? $form_data['settings']['multi_part'] : array();

					if ( ! empty( $form_data['multi_part'] ) ) {
						self::$parts = array(
							'total'    => count( $form_data['multi_part'] ),
							'current'  => 1,
							'parts'    => array_values( $form_data['multi_part'] ),
							'settings' => wp_parse_args( $settings, $settings_defaults ),
						);
					}
				} else {
					self::$parts = array(
						'total'    => '',
						'current'  => '',
						'parts'    => array(),
						'settings' => $settings_defaults,
					);
				}
			}
		}

		// Allow Multi-Part to be customized.
		self::$parts[ $form_id ] = apply_filters( 'everest_forms_parts_data', self::$parts, $form_data, $form_id );

		// Output the fields preview.
		echo '<div class="evf-admin-field-container">';
		echo '<div class="evf-admin-field-wrapper">';

		/**
		 * Hook: everest_forms_display_builder_fields_before.
		 *
		 * @hooked EverestForms_MultiPart::display_builder_fields_before() Multi-Part markup open.
		 */
		do_action( 'everest_forms_display_builder_fields_before', $form_data, $form_id );
		if ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
			$num_rows = count( $structure );

			// Create a new row with the next available row number.
			$new_row_key = 'row_' . ( $num_rows + 1 );
			$new_row     = array(
				$new_row_key => array(
					'grid_1' => array(
						'IWX5HFxv2j-18',
					),
				),
			);
			$structure   = array_merge( $structure, $new_row );
		}

		foreach ( $structure as $row_id => $row_data ) {
			$row         = str_replace( 'row_', '', $row_id );
			$row_grid    = isset( $form_data['structure'][ 'row_' . $row ] ) ? $form_data['structure'][ 'row_' . $row ] : array();
			$form_grid   = apply_filters( 'everest_forms_default_form_grid', 4 );
			$total_grid  = $form_grid;
			$active_grid = ( count( $row_grid ) > 0 ) ? count( $row_grid ) : ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ? 1 : 2 );
			$active_grid = $active_grid > $total_grid ? $total_grid : $active_grid;

			/**
			 * Hook: everest_forms_display_row_before.
			 */
			do_action( 'everest_forms_display_builder_row_before', $row_id, $form_data, $form_id );

			$repeater_field = apply_filters( 'everest_forms_display_repeater_fields', false, $row_grid, $fields );

			echo '<div class="evf-admin-row" data-row-id="' . absint( $row ) . '"' . ( ! empty( $repeater_field ) ? esc_attr( $repeater_field ) : '' ) . '>';
			echo '<div class="evf-toggle-row">';
			if ( empty( $repeater_field ) ) {
				echo '<div class="evf-duplicate-row"><span class="dashicons dashicons-media-default" title="Duplicate Row"></span></div>';
				echo '<div class="evf-delete-row"><span class="dashicons dashicons-trash" title="Delete Row"></span></div>';
				echo '<div class="evf-show-grid"><span class="dashicons dashicons-edit" title="Edit"></span></div>';
				if ( defined( 'EFP_VERSION' ) ) {
					echo '<div class="evf-row-setting"><span class="dashicons dashicons-admin-settings" title="Row Setting"></span></div>';
				}
			}
			echo '<div class="evf-toggle-row-content">';
			echo '<span>' . esc_html__( 'Row Settings', 'everest-forms' ) . '</span>';
			echo '<small>' . esc_html__( 'Select the type of row', 'everest-forms' ) . '</small>';
			echo '<div class="clear"></div>';

			for ( $grid_active = 1; $grid_active <= $total_grid; $grid_active++ ) {
				$class = 'evf-grid-selector';

				if ( $grid_active === $active_grid ) {
					$class .= ' active';
				}

				echo '<div class="' . esc_attr( $class ) . '" data-evf-grid="' . absint( $grid_active ) . '">';

				$gaps   = 15;
				$width  = ( 100 - $gaps ) / $grid_active;
				$margin = ( $gaps / $grid_active ) / 2;

				for ( $row_icon = 1; $row_icon <= $grid_active; $row_icon++ ) {
					echo '<span style="width:' . (float) $width . '%; margin-left:' . (float) $margin . '%; margin-right:' . (float) $margin . '%"></span>';
				}

				echo '</div>';
			}

			echo '</div>';
			echo '</div>';
			echo '<div class="clear evf-clear"></div>';
			echo '<div class="evf-grid-lists">';
			$grid_class = 'evf-admin-grid evf-grid-' . ( $active_grid );
			for ( $grid_start = 1; $grid_start <= $active_grid; $grid_start++ ) {
				echo '<div class="' . esc_attr( $grid_class ) . ' " data-grid-id="' . absint( $grid_start ) . '">';
				$grid_fields    = isset( $row_grid[ 'grid_' . $grid_start ] ) && is_array( $row_grid[ 'grid_' . $grid_start ] ) ? $row_grid[ 'grid_' . $grid_start ] : ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ? array(
					'IWX5HFxv2j-18',
				) : array() );
				$recaptcha_type = get_option( 'everest_forms_recaptcha_type', 'v2' );
				if ( isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
					if ( 'v2' === $recaptcha_type || 'v3' === $recaptcha_type ) {
						$recaptcha_type = 'recaptcha';
					}
					$fields['IWX5HFxv2j-18'] = array(
						'id'       => 'IWX5HFxv2j-18',
						'type'     => $recaptcha_type,
						'label'    => '',
						'meta-key' => $recaptcha_type . '_7543',
					);
				}
				foreach ( $grid_fields as $field_id ) {
					if ( isset( $fields[ $field_id ] ) ) {
						// Locked Pro fields are rendered too (see field_preview()) so the
						// builder can show them as an upsell instead of dropping them.
						$this->field_preview( $fields[ $field_id ] );
					}
				}
				echo '</div>';
			}
			echo '</div >';
			echo '<div class="clear evf-clear"></div>';
			echo '</div >';

			/**
			 * Hook: everest_forms_display_builder_row_after.
			 *
			 * @hooked EverestForms_MultiPart::display_builder_row_after() Multi-Part markup (close previous part, open next).
			 */
			do_action( 'everest_forms_display_builder_row_after', $row_id, $form_data, $form_id );
		}

		/**
		 * Hook: everest_forms_display_builder_fields_after.
		 *
		 * @hooked EverestForms_MultiPart::display_builder_fields_after() Multi-Part markup open.
		 */
		do_action( 'everest_forms_display_builder_fields_after', $form_data, $form_id );

		echo '</div>';
		echo '<div class="clear evf-clear"></div>';
		if ( defined( 'EVF_REPEATER_FIELDS_VERSION' ) ) {
			echo '<div class="evf-repeater-row-wrapper">'; // Repeater Row Wrapper starts.
		}

		echo '<div class="evf-add-row" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-plus-alt">' . esc_html__( 'Add Row', 'everest-forms' ) . '</span></div>';

		if ( defined( 'EVF_REPEATER_FIELDS_VERSION' ) ) {
			echo '<div class="evf-add-row repeater-row" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-plus-alt">' . esc_html__( 'Add Repeater Row', 'everest-forms' ) . '</span></div>';
			echo '</div>'; // Repeater Row Wrapper ends.
		}
		echo '</div >';
	}

	/**
	 * Single Field preview.
	 *
	 * @param array $field        Field data and settings.
	 * @param bool  $show_actions Whether to render the edit action chrome
	 *                            (duplicate/delete/settings). Disabled for the
	 *                            read-only AI preview so it shows only field content.
	 */
	public function field_preview( $field, $show_actions = true ) {
		$css  = ! empty( $field['size'] ) ? 'size-' . esc_attr( $field['size'] ) : '';
		$css .= ! empty( $field['label_hide'] ) && '1' === $field['label_hide'] ? ' label_hide' : '';
		$css .= ! empty( $field['sublabel_hide'] ) && '1' === $field['sublabel_hide'] ? ' sublabel_hide' : '';
		$css .= ! empty( $field['required'] ) && '1' === $field['required'] ? ' required' : '';
		$css .= ! empty( $field['input_columns'] ) && '2' === $field['input_columns'] ? ' everest-forms-list-2-columns' : '';
		$css .= ! empty( $field['input_columns'] ) && '3' === $field['input_columns'] ? ' everest-forms-list-3-columns' : '';
		$css .= ! empty( $field['input_columns'] ) && 'inline' === $field['input_columns'] ? ' everest-forms-list-inline' : '';
		$is_locked = evf_is_field_locked( $field['type'] );
		if ( $is_locked ) {
			$css .= ' everest-forms-field-locked';
		}
		$css  = apply_filters( 'everest_forms_field_preview_class', $css, $field );
		printf( '<div class="everest-forms-field everest-forms-field-%1$s %2$s" id="everest-forms-field-%3$s" data-field-id="%3$s" data-field-type="%4$s">', esc_attr( $field['type'] ), esc_attr( $css ), esc_attr( $field['id'] ), esc_attr( $field['type'] ) );
		if ( $show_actions ) {
			printf( '<div class="evf-field-action">' );
			if ( 'repeater-fields' !== $field['type'] ) {
				printf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Field', 'everest-forms' ) );
				printf( '<a href="#" class="everest-forms-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Field', 'everest-forms' ) );
				printf( '<a href="#" class="everest-forms-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', esc_html__( 'Settings', 'everest-forms' ) );
			} else {
				printf( '<a href="#" class="evf-duplicate-row" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Repeater', 'everest-forms' ) );
				printf( '<a href="#" class="evf-delete-row" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Repeater', 'everest-forms' ) );
			}
			printf( '</div>' );
		}

		// Locked (Pro/addon) fields placed in the form. The orange "Pro" icon is
		// added inline next to the field label via CSS (.everest-forms-field-locked
		// .label-title::after) — matching the fields sidebar — so no absolute badge
		// element is emitted here.
		if ( $is_locked ) {
			// Render the field's real preview (the free plugin now bundles the
			// builder-scope rendering for Pro fields). If a field still ships no
			// preview, fall back to a representative card instead of a blank block.
			ob_start();
			do_action( 'everest_forms_builder_fields_preview_' . $field['type'], $field );
			$preview = trim( ob_get_clean() );

			if ( '' === $preview ) {
				$this->locked_field_preview_body( $field );
			} else {
				echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Field preview is escaped by the field class.
			}
		} else {
			do_action( 'everest_forms_builder_fields_preview_' . $field['type'], $field );
		}

		echo '</div>';
	}

	/**
	 * Synthesize a representative preview body for a locked (Pro/addon) field.
	 *
	 * The free plugin ships Pro fields as stubs without a field_preview(), so on
	 * the builder canvas / AI preview we render a consistent upsell card showing
	 * the label and the field type name rather than an empty block.
	 *
	 * @param array $field Field data and settings.
	 */
	public function locked_field_preview_body( $field ) {
		$meta      = evf()->form_fields->get_pro_fields_meta();
		$type_name = isset( $meta[ $field['type'] ]['name'] ) ? $meta[ $field['type'] ]['name'] : $field['type'];
		$label     = ! empty( $field['label'] ) ? $field['label'] : $type_name;
		$required  = ! empty( $field['required'] ) && '1' === $field['required'];

		// When the user is already licensed but the field's required add-on is
		// inactive, prompt to activate the add-on instead of upselling Pro.
		$trigger = $this->get_locked_field_trigger( $field['type'] );
		if ( ! empty( $trigger['licensed'] ) && ! empty( $trigger['addon'] ) ) {
			/* translators: %s: add-on name (e.g. Stripe). */
			$message = sprintf( esc_html__( 'Activate the %s add-on to use this field', 'everest-forms' ), esc_html( $this->humanize_addon( $trigger['addon'] ) ) );
		} else {
			/* translators: %s: field type name (e.g. Signature). */
			$message = sprintf( esc_html__( '%s is a premium field', 'everest-forms' ), esc_html( $type_name ) );
		}
		?>
		<label class="evf-locked-field-label">
			<?php echo esc_html( $label ); ?>
			<?php if ( $required ) : ?>
				<span class="evf-locked-field-required">*</span>
			<?php endif; ?>
		</label>
		<div class="evf-locked-field-placeholder">
			<span class="dashicons dashicons-lock"></span>
			<span><?php echo esc_html( $message ); ?></span>
		</div>
		<?php
	}

	/**
	 * Render a read-only, edit-chrome-free preview of a form's fields.
	 *
	 * Reuses the exact per-field markup of field_preview() (so the preview is
	 * pixel-identical to the builder canvas) but omits the row toolbars, grid
	 * selectors, add-row buttons and per-field action icons that only make sense
	 * inside the live builder. Used by the "Create with AI" preview endpoint.
	 *
	 * @param array $form_data Form data ( form_fields, structure ).
	 * @return string Preview HTML.
	 */
	public function render_ai_preview( $form_data ) {
		$fields    = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$structure = isset( $form_data['structure'] ) && ! empty( $form_data['structure'] ) ? $form_data['structure'] : array();

		// Fall back to one-field-per-row if no structure is provided.
		if ( empty( $structure ) ) {
			$row = 1;
			foreach ( array_keys( $fields ) as $fid ) {
				$structure[ 'row_' . $row ] = array( 'grid_1' => array( $fid ) );
				$row++;
			}
		}

		ob_start();
		// Reproduce the builder's exact ancestor chain so the canvas field CSS
		// (scoped to `#everest-forms-builder .evf-tab-content
		// .everest-forms-panel-content-wrap .everest-forms-panel-content …`) applies
		// identically in the AI preview. The preview stylesheet neutralises these
		// containers' own layout (absolute positioning / sidebar width) so they
		// don't disturb the preview pane. See .evf-ai-preview-canvas in
		// assets/css/evf-locked-fields.css.
		// Inline styles guarantee the builder container's own layout (absolute
		// positioning / 100vh height) is neutralised regardless of stylesheet load
		// order or caching, so the preview expands to its full content height.
		echo '<div id="everest-forms-builder" style="position:static !important;inset:auto !important;min-height:0 !important;height:auto !important;width:100% !important;"><div class="evf-tab-content"><div class="everest-forms-panel-content-wrap"><div class="everest-forms-panel-content">';
		echo '<div class="evf-admin-field-container"><div class="evf-admin-field-wrapper">';

		$row_index = 0;
		foreach ( $structure as $row_data ) {
			$grids       = is_array( $row_data ) ? $row_data : array();
			$active_grid = max( 1, count( $grids ) );

			// --evf-row-index drives a staggered "field appears" animation in the
			// preview (see .evf-ai-preview-canvas in evf-locked-fields.css), so the
			// form reveals field-by-field on generate / regenerate.
			printf( '<div class="evf-admin-row" style="--evf-row-index:%d;">', absint( $row_index ) );
			echo '<div class="evf-grid-lists">';
			$row_index++;

			$grid_index = 1;
			foreach ( $grids as $grid ) {
				printf( '<div class="evf-admin-grid evf-grid-%1$d" data-grid-id="%2$d">', absint( $active_grid ), absint( $grid_index ) );
				foreach ( (array) $grid as $field_id ) {
					if ( isset( $fields[ $field_id ] ) ) {
						$this->field_preview( $fields[ $field_id ], false );
					}
				}
				echo '</div>';
				$grid_index++;
			}

			echo '</div>'; // .evf-grid-lists
			echo '<div class="clear evf-clear"></div>';
			echo '</div>'; // .evf-admin-row
		}

		echo '</div></div>'; // .evf-admin-field-wrapper .evf-admin-field-container
		echo '</div></div></div></div>'; // .everest-forms-panel-content .everest-forms-panel-content-wrap .evf-tab-content #everest-forms-builder

		return ob_get_clean();
	}

	/**
	 * Build the upgrade-trigger data attributes shared by the locked-field
	 * badge (canvas) and the locked-options overlay (settings panel).
	 *
	 * Reuses the existing upgrade.js handlers: when no license is present the
	 * generic "upgrade-modal" flow runs; when licensed but the required addon
	 * is inactive the "evf-upgrade-addon" install/activate flow runs.
	 *
	 * @param string $type Field type slug.
	 * @return array {
	 *     @type string $trigger Trigger CSS class (upgrade-modal|evf-upgrade-addon).
	 *     @type string $name    Human-readable field name.
	 *     @type string $attr    Pre-escaped HTML data attributes.
	 * }
	 */
	protected function get_locked_field_trigger( $type ) {
		$meta     = evf()->form_fields->get_pro_fields_meta();
		$info     = isset( $meta[ $type ] ) ? $meta[ $type ] : array();
		$addon    = isset( $info['addon'] ) ? $info['addon'] : '';
		$plan     = isset( $info['plan'] ) ? $info['plan'] : '';
		$links    = isset( $info['links'] ) ? $info['links'] : array();
		$name     = isset( $info['name'] ) ? $info['name'] : $type;
		$licensed = false !== evf_get_license_plan();
		$trigger  = ( $licensed && ! empty( $addon ) ) ? 'evf-upgrade-addon' : 'upgrade-modal';

		$attr = sprintf(
			' data-field-type="%1$s" data-field-name="%2$s" data-field-plan="%3$s" data-addon-slug="%4$s" data-field-class="%5$s" data-links="%6$s"',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $plan ),
			esc_attr( $addon ),
			esc_attr( $trigger ),
			esc_attr( wp_json_encode( $links ) )
		);

		return array(
			'trigger'  => $trigger,
			'name'     => $name,
			'attr'     => $attr,
			'addon'    => $addon,
			'licensed' => $licensed,
		);
	}

	/**
	 * Humanize an addon slug into a display name (e.g.
	 * "everest-forms-survey-polls-quiz" => "Survey Polls Quiz").
	 *
	 * @param string $slug Addon slug.
	 * @return string
	 */
	protected function humanize_addon( $slug ) {
		$slug = preg_replace( '/^everest-forms-/', '', (string) $slug );
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * Output the PRO ribbon for a locked field on the builder canvas.
	 *
	 * @param string $type Field type slug.
	 */
	public function locked_field_badge( $type ) {
		$data = $this->get_locked_field_trigger( $type );

		printf(
			'<span class="everest-forms-field-pro-badge everest-forms-locked-field-cta %1$s"%2$s title="%3$s">%4$s</span>',
			esc_attr( $data['trigger'] ),
			$data['attr'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped in get_locked_field_trigger().
			esc_attr__( 'This is a premium field. Upgrade to use it on your form.', 'everest-forms' ),
			esc_html__( 'PRO', 'everest-forms' )
		);
	}

	/**
	 * Output the locked notice banner above a Pro field's settings panel.
	 *
	 * The field's real option rows are still rendered below this banner so all
	 * settings are READABLE; CSS (.everest-forms-field-option-locked) disables
	 * pointer events on them so they are not editable, while their inputs remain
	 * enabled (not the HTML `disabled` attribute) so the field data round-trips
	 * on save and the field works the moment the user upgrades.
	 *
	 * @param string $type Field type slug.
	 */
	public function locked_field_option_overlay( $type ) {
		$data         = $this->get_locked_field_trigger( $type );
		$needs_addon  = ( 'evf-upgrade-addon' === $data['trigger'] ); // Licensed, but the required addon is inactive.
		$field_strong = '<strong>' . esc_html( $data['name'] ) . '</strong>';

		if ( $needs_addon && ! empty( $data['addon'] ) ) {
			$addon_strong = '<strong>' . esc_html( $this->humanize_addon( $data['addon'] ) ) . '</strong>';
			/* translators: 1: field name, 2: addon name. */
			$message   = sprintf( esc_html__( '%1$s needs the %2$s add-on. Activate it to use this field.', 'everest-forms' ), $field_strong, $addon_strong );
			$cta_label = esc_html__( 'Activate add-on', 'everest-forms' );
			$tag_label = esc_html__( 'ADD-ON', 'everest-forms' );
			$tag_icon  = 'dashicons-admin-plugins';
		} else {
			/* translators: %s: field name. */
			$message   = sprintf( esc_html__( '%s is a premium field — settings are read-only until you upgrade.', 'everest-forms' ), $field_strong );
			$cta_label = esc_html__( 'Unlock', 'everest-forms' );
			$tag_label = esc_html__( 'PRO', 'everest-forms' );
			$tag_icon  = 'dashicons-lock';
		}
		?>
		<div class="everest-forms-field-option-locked-banner everest-forms-locked-field-cta <?php echo esc_attr( $data['trigger'] ); ?>"<?php echo $data['attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped in get_locked_field_trigger(). ?>>
			<span class="evf-locked-banner-tag"><span class="dashicons <?php echo esc_attr( $tag_icon ); ?>"></span><?php echo esc_html( $tag_label ); ?></span>
			<span class="evf-locked-banner-text">
				<?php echo wp_kses( $message, array( 'strong' => array() ) ); ?>
			</span>
			<button type="button" class="everest-forms-btn everest-forms-btn-primary evf-locked-banner-cta"><?php echo esc_html( $cta_label ); ?></button>
		</div>
		<?php
	}
}

return new EVF_Builder_Fields();
