<?php
/**
 * Ability handler callbacks.
 *
 * @package EverestForms\Abilities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Static handlers for each registered ability.
 *
 * Every callback receives the validated input array and returns a plain
 * array/scalar on success or a WP_Error on failure.
 */
class EVF_Abilities_Handlers {

	/* ------------------------------------------------------------------
	 * Permission helpers
	 * ------------------------------------------------------------------ */

	public static function can_view_forms() {
		return current_user_can( 'everest_forms_view_forms' ) || current_user_can( 'manage_everest_forms' );
	}

	public static function can_create_forms() {
		return current_user_can( 'everest_forms_create_forms' );
	}

	public static function can_view_entries() {
		return current_user_can( 'everest_forms_view_entries' ) || current_user_can( 'manage_everest_forms' );
	}

	public static function can_delete_entries() {
		return current_user_can( 'everest_forms_delete_entries' ) || current_user_can( 'manage_everest_forms' );
	}

	public static function can_edit_entries() {
		return current_user_can( 'everest_forms_edit_entries' ) || current_user_can( 'manage_everest_forms' );
	}

	public static function can_edit_forms() {
		return current_user_can( 'everest_forms_edit_forms' ) || current_user_can( 'manage_everest_forms' );
	}

	public static function can_delete_forms() {
		return current_user_can( 'everest_forms_delete_forms' ) || current_user_can( 'manage_everest_forms' );
	}

	public static function can_activate_plugins() {
		return current_user_can( 'activate_plugins' );
	}

	/* ------------------------------------------------------------------
	 * Form abilities
	 * ------------------------------------------------------------------ */

	/**
	 * @param array $input Args.
	 * @return array
	 */
	public static function list_forms( $input ) {
		$limit  = isset( $input['limit'] ) ? (int) $input['limit'] : 50;
		$status = isset( $input['status'] ) ? (string) $input['status'] : 'publish';

		$query_args = array(
			'post_type'      => 'everest_form',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( 'any' !== $status ) {
			$query_args['post_status'] = $status;
		} else {
			$query_args['post_status'] = array( 'publish', 'draft', 'trash' );
		}

		$forms = get_posts( $query_args );
		$out   = array();
		foreach ( $forms as $form ) {
			$out[] = array(
				'id'      => (int) $form->ID,
				'title'   => $form->post_title,
				'status'  => $form->post_status,
				'created' => $form->post_date_gmt,
			);
		}
		return $out;
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function get_form( $input ) {
		$form_id = (int) $input['form_id'];
		$form    = evf()->form->get( $form_id );
		if ( ! $form ) {
			return new WP_Error( 'evf_form_not_found', 'Form not found.', array( 'status' => 404 ) );
		}
		$data = is_string( $form->post_content ) ? json_decode( $form->post_content, true ) : array();
		return array(
			'id'       => (int) $form->ID,
			'title'    => $form->post_title,
			'status'   => $form->post_status,
			'settings' => isset( $data['settings'] ) ? $data['settings'] : array(),
			'fields'   => isset( $data['form_fields'] ) ? $data['form_fields'] : array(),
		);
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function create_form( $input ) {
		$title    = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';
		$template = isset( $input['template'] ) ? sanitize_key( $input['template'] ) : 'blank';
		$dry_run  = ! empty( $input['dry_run'] );

		if ( '' === $title ) {
			return new WP_Error( 'evf_invalid_title', 'A non-empty title is required.', array( 'status' => 400 ) );
		}

		$descriptor = array_intersect_key( $input, array_flip( array( 'title', 'description', 'settings', 'fields', 'layout', 'multi_part', 'conversational' ) ) );

		// Validate the descriptor against the schema registry BEFORE touching
		// the database, so a bad field type doesn't leave an empty form on
		// disk. dry_run can use the same throwaway build to return a preview.
		$preview = EVF_Form_Builder::build( array(), $descriptor );
		if ( is_wp_error( $preview ) ) {
			return $preview;
		}

		if ( $dry_run ) {
			return array(
				'dry_run' => true,
				'preview' => array(
					'title'       => isset( $preview['settings']['form_title'] ) ? $preview['settings']['form_title'] : $title,
					'fields'      => self::summarize_fields( $preview ),
					'field_count' => isset( $preview['form_fields'] ) ? count( $preview['form_fields'] ) : 0,
					'structure'   => isset( $preview['structure'] ) ? $preview['structure'] : array(),
				),
			);
		}

		$form_id = evf()->form->create( $title, $template );
		if ( ! $form_id ) {
			return new WP_Error( 'evf_create_failed', 'Form creation failed (insufficient capabilities or invalid template).', array( 'status' => 500 ) );
		}

		// Merge what the template produced (if any) with what we built. This
		// is the canonical build — produces the final shape, validates again
		// in case the template added something incompatible (shouldn't, but
		// defensive). If this fails, clean up the empty form we just made.
		$created = evf()->form->get( $form_id );
		$base    = is_string( $created->post_content ) ? json_decode( $created->post_content, true ) : array();
		if ( ! is_array( $base ) ) {
			$base = array();
		}
		$merged = EVF_Form_Builder::build( $base, $descriptor );
		if ( is_wp_error( $merged ) ) {
			wp_delete_post( $form_id, true );
			return $merged;
		}
		$merged['id'] = (int) $form_id;
		evf()->form->update( $form_id, $merged );

		return array(
			'id'       => (int) $form_id,
			'title'    => isset( $merged['settings']['form_title'] ) ? $merged['settings']['form_title'] : $title,
			'fields'   => self::summarize_fields( $merged ),
			'edit_url' => admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . (int) $form_id ),
		);
	}

	/**
	 * Summarize fields for a response envelope (no internal IDs in the verbose form).
	 *
	 * @param array $data Built form-data array.
	 * @return array
	 */
	protected static function summarize_fields( $data ) {
		$out = array();
		if ( empty( $data['form_fields'] ) ) {
			return $out;
		}
		foreach ( $data['form_fields'] as $fid => $f ) {
			$out[] = array(
				'id'    => (string) $fid,
				'type'  => isset( $f['type'] ) ? $f['type'] : '',
				'label' => isset( $f['label'] ) ? $f['label'] : '',
			);
		}
		return $out;
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function update_form( $input ) {
		$form_id = (int) $input['form_id'];
		$dry_run = ! empty( $input['dry_run'] );
		$form    = evf()->form->get( $form_id );
		if ( ! $form ) {
			return new WP_Error( 'evf_form_not_found', 'Form not found.', array( 'status' => 404 ) );
		}

		$existing = is_string( $form->post_content ) ? json_decode( $form->post_content, true ) : array();
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$descriptor = array_intersect_key( $input, array_flip( array( 'title', 'description', 'settings', 'fields', 'layout', 'form_fields_patch', 'multi_part', 'conversational' ) ) );

		$next = EVF_Form_Builder::build( $existing, $descriptor );
		if ( is_wp_error( $next ) ) {
			return $next;
		}
		$next['id'] = $form_id;

		if ( $dry_run ) {
			return array(
				'dry_run' => true,
				'form_id' => $form_id,
				'preview' => array(
					'title'       => isset( $next['settings']['form_title'] ) ? $next['settings']['form_title'] : '',
					'fields'      => self::summarize_fields( $next ),
					'field_count' => isset( $next['form_fields'] ) ? count( $next['form_fields'] ) : 0,
					'structure'   => isset( $next['structure'] ) ? $next['structure'] : array(),
				),
			);
		}

		$result = evf()->form->update( $form_id, $next );
		if ( ! $result ) {
			return new WP_Error( 'evf_form_update_failed', 'Form update failed (likely a capability check).', array( 'status' => 403 ) );
		}

		return array(
			'form_id'  => (int) $result,
			'title'    => isset( $next['settings']['form_title'] ) ? $next['settings']['form_title'] : '',
			'fields'   => self::summarize_fields( $next ),
			'edit_url' => admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . (int) $result ),
		);
	}

	/**
	 * @param array $input Args.
	 * @return array
	 */
	/**
	 * Activate an installed Everest Forms addon.
	 *
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function activate_addon( $input ) {
		$plugin = isset( $input['plugin'] ) ? (string) $input['plugin'] : '';
		if ( '' === $plugin || false === strpos( $plugin, '/' ) ) {
			return new WP_Error( 'evf_invalid_plugin', 'Plugin file is required, e.g. "everest-forms-survey-polls-quiz/everest-forms-survey-polls-quiz.php".', array( 'status' => 400 ) );
		}

		// Refuse to activate anything outside the EVF family — this ability is
		// scoped to Everest Forms addons, not a general plugin activator.
		if ( 0 !== strpos( $plugin, 'everest-forms' ) ) {
			return new WP_Error( 'evf_not_an_evf_addon', 'This ability only activates Everest Forms addons.', array( 'status' => 400 ) );
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = (array) get_plugins();
		if ( ! isset( $installed[ $plugin ] ) ) {
			return new WP_Error(
				'evf_addon_not_installed',
				sprintf( 'Addon "%s" is not installed on this site. The user needs to install/purchase it from their Everest Forms account, or upgrade their plan if it is not included.', $plugin ),
				array(
					'status'      => 404,
					'plugin'      => $plugin,
					'action'      => 'install_or_upgrade',
					'account_url' => 'https://everestforms.net/my-account/downloads/',
				)
			);
		}

		if ( is_plugin_active( $plugin ) ) {
			return array(
				'plugin'  => $plugin,
				'active'  => true,
				'message' => 'Addon was already active.',
			);
		}

		$result = activate_plugin( $plugin, '', false, false );
		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'evf_addon_activation_failed',
				$result->get_error_message(),
				array( 'status' => 500, 'plugin' => $plugin )
			);
		}

		// EVF has a second gate beyond WP plugin activation: the slug must be
		// present in the `everest_forms_enabled_features` option for some addon
		// field types (payment-coupon, payment-square, etc.) to be registered.
		// Derive the slug from the plugin file (folder name) and add it.
		$slug             = dirname( $plugin );
		$enabled_features = (array) get_option( 'everest_forms_enabled_features', array() );
		$feature_added    = false;
		if ( $slug && ! in_array( $slug, $enabled_features, true ) ) {
			$enabled_features[] = $slug;
			update_option( 'everest_forms_enabled_features', $enabled_features );
			$feature_added = true;
		}

		return array(
			'plugin'        => $plugin,
			'active'        => true,
			'feature_slug'  => $slug,
			'feature_added' => $feature_added,
			'name'          => isset( $installed[ $plugin ]['Name'] ) ? $installed[ $plugin ]['Name'] : $plugin,
			'version'       => isset( $installed[ $plugin ]['Version'] ) ? $installed[ $plugin ]['Version'] : null,
			'message'       => 'Addon activated and registered in Everest Forms enabled-features. Retry the original create-form/update-form call now.',
		);
	}

	public static function list_addons( $input ) {
		$known = array(
			'everest-forms-pro/everest-forms-pro.php'                                     => 'Everest Forms PRO',
			'everest-forms-survey-polls-quiz/everest-forms-survey-polls-quiz.php'         => 'Survey, Polls and Quiz',
			'everest-forms-multi-part/everest-forms-multi-part.php'                       => 'Multi-Part Forms',
			'everest-forms-repeater-fields/everest-forms-repeater-fields.php'             => 'Repeater Fields',
			'everest-forms-conversational-forms/everest-forms-conversational-forms.php'   => 'Conversational Forms',
			'everest-forms-coupons/everest-forms-coupons.php'                             => 'Coupons',
			'everest-forms-captcha/everest-forms-captcha.php'                             => 'Captcha',
			'everest-forms-calculations/everest-forms-calculations.php'                   => 'Calculations',
			'everest-forms-save-and-continue/everest-forms-save-and-continue.php'         => 'Save and Continue',
			'everest-forms-pdf-submission/everest-forms-pdf-submission.php'               => 'PDF Submission',
			'everest-forms-google-sheets/everest-forms-google-sheets.php'                 => 'Google Sheets',
			'everest-forms-mailchimp/everest-forms-mailchimp.php'                         => 'Mailchimp',
			'everest-forms-zapier/everest-forms-zapier.php'                               => 'Zapier',
			'everest-forms-stripe/everest-forms-stripe.php'                               => 'Stripe',
			'everest-forms-paypal-standard/everest-forms-paypal-standard.php'             => 'PayPal Standard',
			'everest-forms-form-analytics/everest-forms-form-analytics.php'               => 'Form Analytics',
			'everest-forms-user-registration/everest-forms-user-registration.php'         => 'User Registration',
			'everest-forms-post-submissions/everest-forms-post-submissions.php'           => 'Post Submissions',
			'everest-forms-email-templates/everest-forms-email-templates.php'             => 'Email Templates',
			'everest-forms-frontend-listing/everest-forms-frontend-listing.php'           => 'Frontend Listing',
			'everest-forms-style-customizer/everest-forms-style-customizer.php'           => 'Style Customizer',
			'everest-forms-sms-notifications/everest-forms-sms-notifications.php'         => 'SMS Notifications',
		);

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed        = function_exists( 'get_plugins' ) ? (array) get_plugins() : array();
		$enabled_features = (array) get_option( 'everest_forms_enabled_features', array() );
		$out              = array();
		foreach ( $known as $file => $name ) {
			$is_installed = isset( $installed[ $file ] );
			$slug         = dirname( $file );
			$active       = function_exists( 'is_plugin_active' ) ? is_plugin_active( $file ) : false;
			$feature_on   = in_array( $slug, $enabled_features, true );
			$out[]        = array(
				'plugin'             => $file,
				'name'               => $name,
				'installed'          => $is_installed,
				'active'             => $active,
				// EVF maintains a second toggle beyond WP plugin activation;
				// some addon-gated field types (payment-coupon, etc.) only
				// register when their slug is in `everest_forms_enabled_features`.
				'evf_feature_on'     => $feature_on,
				'fully_operational'  => $is_installed && $active && $feature_on,
				'version'            => $is_installed && isset( $installed[ $file ]['Version'] ) ? $installed[ $file ]['Version'] : null,
			);
		}
		return $out;
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function describe_field_type( $input ) {
		$type   = isset( $input['type'] ) ? sanitize_key( (string) $input['type'] ) : '';
		if ( '' === $type ) {
			return new WP_Error( 'evf_invalid_type', 'Missing "type".', array( 'status' => 400 ) );
		}
		$registered = EVF_Field_Schemas::registered_types();
		$schema     = EVF_Field_Schemas::for_type( $type );

		return array(
			'type'             => $type,
			'available'        => isset( $registered[ $type ] ),
			'addon'            => EVF_Field_Schemas::addon_for( $type ),
			'accepted_keys'    => $schema ? $schema['accepted_keys'] : array(),
			'requires_choices' => $schema ? $schema['requires_choices'] : false,
			'known_to_builder' => null !== $schema,
		);
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function update_form_status( $input ) {
		$form_id = (int) $input['form_id'];
		$status  = isset( $input['status'] ) ? sanitize_key( (string) $input['status'] ) : '';
		if ( ! in_array( $status, array( 'publish', 'draft', 'trash' ), true ) ) {
			return new WP_Error( 'evf_invalid_status', 'Status must be one of: publish, draft, trash.', array( 'status' => 400 ) );
		}
		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return new WP_Error( 'evf_form_not_found', 'Form not found.', array( 'status' => 404 ) );
		}
		if ( $post->post_status === $status ) {
			return array( 'form_id' => $form_id, 'status' => $status, 'changed' => false );
		}
		$result = wp_update_post( array( 'ID' => $form_id, 'post_status' => $status ), true );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'evf_form_status_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}
		return array( 'form_id' => $form_id, 'status' => $status, 'changed' => true );
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function bulk_delete_entries( $input ) {
		global $wpdb;
		$ids       = isset( $input['entry_ids'] ) && is_array( $input['entry_ids'] ) ? array_values( array_unique( array_filter( array_map( 'intval', $input['entry_ids'] ) ) ) ) : array();
		$permanent = array_key_exists( 'permanent', $input ) ? (bool) $input['permanent'] : true;

		if ( empty( $ids ) ) {
			return new WP_Error( 'evf_no_ids', 'Provide one or more entry_ids.', array( 'status' => 400 ) );
		}

		$deleted = 0;
		$skipped = array();

		if ( ! $permanent ) {
			// Single UPDATE for the soft-delete case — much cheaper than N round-trips.
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$query        = "UPDATE {$wpdb->prefix}evf_entries SET status = 'trash' WHERE entry_id IN ({$placeholders})";
			$result       = $wpdb->query( $wpdb->prepare( $query, $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $result ) {
				return new WP_Error( 'evf_bulk_trash_failed', 'Bulk trash failed.', array( 'status' => 500 ) );
			}
			return array(
				'trashed'    => (int) $result,
				'requested'  => count( $ids ),
				'permanent'  => false,
			);
		}

		foreach ( $ids as $entry_id ) {
			$d = $wpdb->delete( $wpdb->prefix . 'evf_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );
			if ( false === $d || 0 === (int) $d ) {
				$skipped[] = $entry_id;
				continue;
			}
			$wpdb->delete( $wpdb->prefix . 'evf_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
			do_action( 'everest_forms_delete_entry', $entry_id );
			$deleted++;
		}

		return array(
			'deleted'   => $deleted,
			'requested' => count( $ids ),
			'skipped'   => $skipped,
			'permanent' => true,
		);
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function delete_form( $input ) {
		$form_id = (int) $input['form_id'];
		$post    = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return new WP_Error( 'evf_form_not_found', 'Form not found.', array( 'status' => 404 ) );
		}
		// We've already gated on `can_delete_forms` in the permission_callback,
		// so bypass EVF_Form_Handler::delete()'s 'everest_forms_delete' cap check
		// (singular cap that isn't granted by default) and delete directly.
		$deleted = wp_delete_post( $form_id, true );
		if ( ! $deleted ) {
			return new WP_Error( 'evf_form_delete_failed', 'wp_delete_post failed.', array( 'status' => 500 ) );
		}
		do_action( 'everest_forms_delete_form', array( $form_id ) );
		return array( 'deleted' => true, 'form_id' => $form_id );
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function duplicate_form( $input ) {
		$form_id = (int) $input['form_id'];
		$result  = evf()->form->duplicate( array( $form_id ) );
		if ( ! $result ) {
			return new WP_Error( 'evf_form_duplicate_failed', 'Form could not be duplicated.', array( 'status' => 500 ) );
		}
		$new_id = is_array( $result ) ? (int) reset( $result ) : (int) $result;
		return array(
			'source_form_id' => $form_id,
			'new_form_id'    => $new_id,
		);
	}

	/**
	 * @param array $input Args.
	 * @return array
	 */
	public static function list_field_types( $input ) {
		$out      = array();
		$registry = evf()->form_fields;
		$groups   = ( is_object( $registry ) && isset( $registry->form_fields ) ) ? (array) $registry->form_fields : array();

		foreach ( $groups as $group => $fields ) {
			foreach ( (array) $fields as $field ) {
				if ( ! is_object( $field ) ) {
					continue;
				}
				$out[] = array(
					'type'  => isset( $field->type ) ? $field->type : '',
					'name'  => isset( $field->name ) ? $field->name : '',
					'group' => (string) $group,
					'icon'  => isset( $field->icon ) ? $field->icon : '',
				);
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Entry abilities
	 * ------------------------------------------------------------------ */

	/**
	 * @param array $input Args.
	 * @return array
	 */
	public static function list_entries( $input ) {
		$args = array(
			'form_id' => (int) $input['form_id'],
			'limit'   => isset( $input['limit'] ) ? (int) $input['limit'] : 25,
			'offset'  => isset( $input['offset'] ) ? (int) $input['offset'] : 0,
		);
		if ( ! empty( $input['search'] ) ) {
			$args['search'] = (string) $input['search'];
		}
		if ( ! empty( $input['status'] ) ) {
			$args['status'] = (string) $input['status'];
		}
		$ids = evf_search_entries( $args );
		$out = array();
		foreach ( (array) $ids as $entry_id ) {
			$entry = function_exists( 'evf_get_entry' ) ? evf_get_entry( (int) $entry_id ) : null;
			if ( ! $entry ) {
				continue;
			}
			$out[] = array(
				'entry_id' => (int) $entry->entry_id,
				'form_id'  => (int) $entry->form_id,
				'status'   => isset( $entry->status ) ? $entry->status : '',
				'date'     => isset( $entry->date_created ) ? $entry->date_created : '',
			);
		}
		return $out;
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function get_entry( $input ) {
		$entry_id = (int) $input['entry_id'];
		$entry    = function_exists( 'evf_get_entry' ) ? evf_get_entry( $entry_id, true ) : null;
		if ( ! $entry ) {
			return new WP_Error( 'evf_entry_not_found', 'Entry not found.', array( 'status' => 404 ) );
		}
		if ( ! self::can_view_entries() ) {
			return new WP_Error( 'evf_forbidden', 'Permission denied.', array( 'status' => 403 ) );
		}
		$meta = isset( $entry->meta ) ? $entry->meta : array();
		return array(
			'entry_id' => (int) $entry->entry_id,
			'form_id'  => (int) $entry->form_id,
			'status'   => isset( $entry->status ) ? $entry->status : '',
			'date'     => isset( $entry->date_created ) ? $entry->date_created : '',
			'fields'   => $meta,
		);
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function delete_entry( $input ) {
		global $wpdb;
		$entry_id  = (int) $input['entry_id'];
		$permanent = array_key_exists( 'permanent', $input ) ? (bool) $input['permanent'] : true;

		if ( ! $permanent ) {
			$updated = $wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array( 'status' => 'trash' ),
				array( 'entry_id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);
			if ( false === $updated ) {
				return new WP_Error( 'evf_entry_trash_failed', 'Entry could not be moved to trash.', array( 'status' => 500 ) );
			}
			return array( 'trashed' => true, 'entry_id' => $entry_id );
		}

		$deleted = $wpdb->delete( $wpdb->prefix . 'evf_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );
		if ( false === $deleted || 0 === (int) $deleted ) {
			return new WP_Error( 'evf_entry_delete_failed', 'Entry could not be deleted (not found or DB error).', array( 'status' => 404 ) );
		}
		$wpdb->delete( $wpdb->prefix . 'evf_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
		do_action( 'everest_forms_delete_entry', $entry_id );
		return array( 'deleted' => true, 'entry_id' => $entry_id );
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function update_entry_status( $input ) {
		global $wpdb;
		$entry_id = (int) $input['entry_id'];
		$status   = sanitize_key( $input['status'] );
		$updated  = $wpdb->update(
			$wpdb->prefix . 'evf_entries',
			array( 'status' => $status ),
			array( 'entry_id' => $entry_id ),
			array( '%s' ),
			array( '%d' )
		);
		if ( false === $updated ) {
			return new WP_Error( 'evf_entry_status_failed', 'Could not update entry status.', array( 'status' => 500 ) );
		}
		do_action( 'everest_forms_update_entry_status', $entry_id, $status );
		return array( 'entry_id' => $entry_id, 'status' => $status, 'updated' => (int) $updated );
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function set_entry_starred( $input ) {
		return self::set_entry_flag( $input, 'starred' );
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function set_entry_viewed( $input ) {
		return self::set_entry_flag( $input, 'viewed' );
	}

	/**
	 * Shared implementation for boolean entry flags (starred/viewed).
	 *
	 * @param array  $input Input args.
	 * @param string $col   Column name.
	 * @return array|WP_Error
	 */
	protected static function set_entry_flag( $input, $col ) {
		global $wpdb;
		$entry_id = (int) $input['entry_id'];
		$value    = ! empty( $input[ $col ] ) ? 1 : 0;
		$updated  = $wpdb->update(
			$wpdb->prefix . 'evf_entries',
			array( $col => $value ),
			array( 'entry_id' => $entry_id ),
			array( '%d' ),
			array( '%d' )
		);
		if ( false === $updated ) {
			return new WP_Error( 'evf_entry_flag_failed', "Could not update entry {$col}.", array( 'status' => 500 ) );
		}
		return array( 'entry_id' => $entry_id, $col => (bool) $value, 'updated' => (int) $updated );
	}

	/**
	 * @param array $input Args.
	 * @return array
	 */
	public static function count_entries( $input ) {
		global $wpdb;
		$form_id = isset( $input['form_id'] ) ? (int) $input['form_id'] : 0;
		$table   = $wpdb->prefix . 'evf_entries';
		$where   = $form_id > 0 ? $wpdb->prepare( 'WHERE form_id = %d', $form_id ) : '';

		$total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
		$unread   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} " . ( '' === $where ? 'WHERE' : 'AND' ) . " viewed = 0 AND status <> 'trash'" );
		$starred  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} " . ( '' === $where ? 'WHERE' : 'AND' ) . " starred = 1 AND status <> 'trash'" );

		$by_status = array();
		foreach ( array( 'publish', 'approved', 'denied', 'pending', 'spam', 'trash' ) as $st ) {
			$by_status[ $st ] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} " . ( $form_id > 0 ? 'WHERE form_id = %d AND' : 'WHERE' ) . " status = %s", $form_id > 0 ? array( $form_id, $st ) : array( $st ) )
			);
		}

		return array(
			'form_id'   => $form_id,
			'total'     => $total,
			'unread'    => $unread,
			'starred'   => $starred,
			'by_status' => $by_status,
		);
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function create_entry( $input ) {
		global $wpdb;
		$form_id = (int) $input['form_id'];
		$fields  = isset( $input['fields'] ) && is_array( $input['fields'] ) ? $input['fields'] : array();
		$status  = isset( $input['status'] ) ? sanitize_key( $input['status'] ) : 'publish';

		if ( empty( $fields ) ) {
			return new WP_Error( 'evf_no_fields', 'No fields provided.', array( 'status' => 400 ) );
		}
		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return new WP_Error( 'evf_form_not_found', 'Form not found.', array( 'status' => 404 ) );
		}
		// Per-form capability: the broad `everest_forms_edit_entries` was already
		// gated in the ability's permission_callback. Re-check the per-form cap
		// here so a user can be restricted to specific forms (EVF convention).
		if ( ! current_user_can( 'everest_forms_view_form_entries', $form_id ) && ! current_user_can( 'manage_everest_forms' ) ) {
			return new WP_Error( 'evf_form_forbidden', 'Not allowed to create entries for this form.', array( 'status' => 403 ) );
		}

		$entry_row = array(
			'user_id'         => get_current_user_id(),
			'user_device'     => '',
			'user_ip_address' => '',
			'form_id'         => $form_id,
			'referer'         => '',
			'fields'          => wp_json_encode( $fields ),
			'status'          => $status,
			'viewed'          => 0,
			'starred'         => 0,
			'date_created'    => gmdate( 'Y-m-d H:i:s' ),
		);

		$inserted = $wpdb->insert( $wpdb->prefix . 'evf_entries', $entry_row );
		if ( ! $inserted ) {
			return new WP_Error( 'evf_entry_create_failed', 'Could not insert entry.', array( 'status' => 500 ) );
		}
		$entry_id = (int) $wpdb->insert_id;

		$meta_table = $wpdb->prefix . 'evf_entrymeta';
		foreach ( $fields as $field_id => $value ) {
			$wpdb->insert(
				$meta_table,
				array(
					'entry_id'   => $entry_id,
					'meta_key'   => sanitize_key( (string) $field_id ),
					'meta_value' => is_scalar( $value ) ? (string) $value : maybe_serialize( $value ),
				),
				array( '%d', '%s', '%s' )
			);
		}

		do_action( 'everest_forms_complete_entry_save', $entry_id, $fields, $form_id, array() );

		return array(
			'entry_id' => $entry_id,
			'form_id'  => $form_id,
			'status'   => $status,
		);
	}

	/**
	 * @param array $input Args.
	 * @return array|WP_Error
	 */
	public static function update_entry_fields( $input ) {
		global $wpdb;
		$entry_id = (int) $input['entry_id'];
		$fields   = isset( $input['fields'] ) && is_array( $input['fields'] ) ? $input['fields'] : array();
		if ( empty( $fields ) ) {
			return new WP_Error( 'evf_no_fields', 'No fields provided.', array( 'status' => 400 ) );
		}

		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}evf_entries WHERE entry_id = %d", $entry_id ) );
		if ( ! $exists ) {
			return new WP_Error( 'evf_entry_not_found', 'Entry not found.', array( 'status' => 404 ) );
		}

		$meta_table = $wpdb->prefix . 'evf_entrymeta';
		$changed    = array();
		foreach ( $fields as $field_id => $value ) {
			$meta_key   = sanitize_key( (string) $field_id );
			$meta_value = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );

			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$meta_table} WHERE entry_id = %d AND meta_key = %s", $entry_id, $meta_key ) );
			if ( $existing ) {
				$wpdb->update( $meta_table, array( 'meta_value' => $meta_value ), array( 'meta_id' => (int) $existing ), array( '%s' ), array( '%d' ) );
			} else {
				$wpdb->insert( $meta_table, array( 'entry_id' => $entry_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value ), array( '%d', '%s', '%s' ) );
			}
			$changed[ $meta_key ] = $meta_value;
		}

		do_action( 'everest_forms_update_entry_fields', $entry_id, $changed );
		return array( 'entry_id' => $entry_id, 'updated_fields' => $changed );
	}

	/* ------------------------------------------------------------------
	 * Analytics
	 * ------------------------------------------------------------------ */

	/**
	 * @param array $input Args.
	 * @return array
	 */
	public static function analytics_summary( $input ) {
		global $wpdb;
		$days    = isset( $input['days'] ) ? max( 1, (int) $input['days'] ) : 30;
		$form_id = isset( $input['form_id'] ) ? (int) $input['form_id'] : 0;
		$since   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$table = $wpdb->prefix . 'evf_entries';

		$total_forms = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'everest_form' AND post_status = 'publish'" );

		if ( $form_id > 0 ) {
			$total_entries   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE form_id = %d", $form_id ) );
			$recent_entries  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE form_id = %d AND date_created >= %s", $form_id, $since ) );
			$top_forms       = array();
		} else {
			$total_entries  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			$recent_entries = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE date_created >= %s", $since ) );

			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT form_id, COUNT(*) AS cnt FROM {$table} WHERE date_created >= %s GROUP BY form_id ORDER BY cnt DESC LIMIT 5",
				$since
			) );
			$top_forms = array();
			foreach ( (array) $rows as $row ) {
				$post = get_post( (int) $row->form_id );
				$top_forms[] = array(
					'form_id'    => (int) $row->form_id,
					'title'      => $post ? $post->post_title : '',
					'entries'    => (int) $row->cnt,
				);
			}
		}

		return array(
			'window_days'    => $days,
			'total_forms'    => $total_forms,
			'total_entries'  => $total_entries,
			'recent_entries' => $recent_entries,
			'top_forms'      => $top_forms,
			'scope_form_id'  => $form_id,
		);
	}
}
