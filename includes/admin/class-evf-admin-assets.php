<?php
/**
 * Load assets
 *
 * @package EverestForms/Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Admin_Assets', false ) ) {
	return new EVF_Admin_Assets();
}

/**
 * EVF_Admin_Assets Class.
 */
class EVF_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'everest-forms-admin', evf()->plugin_url() . '/assets/css/admin.css', array(), EVF_VERSION );
		wp_register_style( 'everest-forms-admin-menu', evf()->plugin_url() . '/assets/css/menu.css', array(), EVF_VERSION );
		wp_register_style( 'jquery-ui-style', evf()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), EVF_VERSION );
		wp_register_style( 'jquery-confirm', evf()->plugin_url() . '/assets/css/jquery-confirm/jquery-confirm.min.css', array(), '3.3.0' );
		wp_register_style( 'perfect-scrollbar', evf()->plugin_url() . '/assets/css/perfect-scrollbar/perfect-scrollbar.css', array(), '1.4.0' );
		wp_register_style( 'flatpickr', evf()->plugin_url() . '/assets/css/flatpickr.css', array(), EVF_VERSION );

		// Add RTL support for admin styles.
		wp_style_add_data( 'everest-forms-admin', 'rtl', 'replace' );
		wp_style_add_data( 'everest-forms-admin-menu', 'rtl', 'replace' );

		// Show hint in codemirror.
		wp_enqueue_style( 'wp-codemirror' );
		wp_enqueue_style( 'codemirror-hint-css', evf()->plugin_url() . '/assets/css/code-mirror/show-hint.min.css', array( 'wp-codemirror' ), EVF_VERSION );

		// Sitewide menu CSS.
		wp_enqueue_style( 'everest-forms-admin-menu' );

		// Admin styles for EVF pages only.
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_style( 'everest-forms-admin' );
			wp_enqueue_style( 'jquery-confirm' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'flatpickr' );

			if ( 'everest-forms_page_evf-tools' !== $screen_id ) {
				wp_enqueue_style( 'perfect-scrollbar' );
			}
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		global $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts.
		wp_register_script( 'everest-forms-admin', evf()->plugin_url() . '/assets/js/admin/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'tooltipster', 'wp-color-picker', 'perfect-scrollbar', 'evf-clipboard' ), EVF_VERSION, true );
		wp_register_script( 'everest-forms-extensions', evf()->plugin_url() . '/assets/js/admin/extensions' . $suffix . '.js', array( 'jquery', 'updates', 'wp-i18n' ), EVF_VERSION, true );
		wp_register_script( 'everest-forms-email-admin', evf()->plugin_url() . '/assets/js/admin/evf-admin-email' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'tooltipster', 'wp-color-picker', 'perfect-scrollbar' ), EVF_VERSION, true );
		wp_register_script( 'everest-forms-editor', evf()->plugin_url() . '/assets/js/admin/editor' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'jquery-blockui', evf()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'jquery-confirm', evf()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm' . $suffix . '.js', array( 'jquery' ), '3.3.0', true );
		wp_register_script( 'jquery-tiptip', evf()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true ); // @deprecated
		wp_register_script( 'tooltipster', evf()->plugin_url() . '/assets/js/tooltipster/tooltipster.bundle' . $suffix . '.js', array( 'jquery' ), '4.6.2', true );
		wp_register_script( 'perfect-scrollbar', evf()->plugin_url() . '/assets/js/perfect-scrollbar/perfect-scrollbar' . $suffix . '.js', array( 'jquery' ), '1.5.0', true );
		wp_register_script( 'evf-clipboard', evf()->plugin_url() . '/assets/js/admin/evf-clipboard' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'selectWoo', evf()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.8', true );
		wp_register_script( 'evf-enhanced-select', evf()->plugin_url() . '/assets/js/admin/evf-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), EVF_VERSION, true );
		wp_register_script( 'evf-template-controller', evf()->plugin_url() . '/assets/js/admin/form-template-controller' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'flatpickr', evf()->plugin_url() . '/assets/js/flatpickr/flatpickr' . $suffix . '.js', array( 'jquery' ), '4.6.3', true );
		wp_register_script( 'evf-file-uploader', evf()->plugin_url() . '/assets/js/admin/evf-file-uploader' . $suffix . '.js', array(), EVF_VERSION, true );
		// Register admin scripts for survey fields.
		wp_register_script( 'everest-forms-survey-polls-quiz-builder', evf()->plugin_url() . "/assets/js/admin/everest-forms-survey-polls-quiz-builder{$suffix}.js", array( 'jquery', 'wp-util', 'underscore', 'jquery-ui-sortable' ), EVF_VERSION, true );
		wp_register_script( 'random-color', evf()->plugin_url() . "/assets/js/admin/randomColor{$suffix}.js", array(), EVF_VERSION, true );
		wp_register_script( 'chart', evf()->plugin_url() . "/assets/js/admin/chart{$suffix}.js", array(), EVF_VERSION, true );
		wp_register_script( 'print_this', evf()->plugin_url() . "/assets/js/admin/printThis{$suffix}.min.js", array(), EVF_VERSION, true );
		wp_register_script( 'progress_bar', evf()->plugin_url() . "/assets/js/admin/progressbar{$suffix}.js", array(), EVF_VERSION, true );
		wp_register_script( 'evf-import-entries-form-csv', evf()->plugin_url() . '/assets/js/admin/tool-import-entries' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_localize_script(
			'evf-file-uploader',
			'evf_file_uploader',
			array(
				'upload_file' => __( 'Upload Image', 'everest-forms' ),
			)
		);
		wp_localize_script(
			'evf-template-controller',
			'evf_templates',
			array(
				'evf_template_all' => EVF_Admin_Form_Templates::get_template_data(),
				'i18n_get_started' => esc_html__( 'Get Started', 'everest-forms' ),
				'i18n_get_preview' => esc_html__( 'Preview', 'everest-forms' ),
				'i18n_pro_feature' => esc_html__( 'Pro', 'everest-forms' ),
				'template_refresh' => esc_html__( 'Updating Templates', 'everest-forms' ),
				'evf_plugin_url'   => esc_url( evf()->plugin_url() ),
			)
		);
		wp_localize_script(
			'evf-enhanced-select',
			'evf_enhanced_select_params',
			array(
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'everest-forms' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'everest-forms' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'everest-forms' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'everest-forms' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'everest-forms' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'everest-forms' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'everest-forms' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'everest-forms' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'everest-forms' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'everest-forms' ),
				'i18n_select_all'           => _x( 'Select All', 'enhanced select', 'everest-forms' ),
				'i18n_unselect_all'         => _x( 'Unselect All', 'enhanced select', 'everest-forms' ),
			)
		);
		wp_register_script( 'evf-form-builder', evf()->plugin_url() . '/assets/js/admin/form-builder' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'tooltipster', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-datepicker', 'jquery-confirm', 'evf-clipboard', 'flatpickr' ), EVF_VERSION, true );
		wp_localize_script(
			'evf-form-builder',
			'evf_data',
			apply_filters(
				'everest_forms_builder_strings',
				array(
					'post_id'                             => isset( $post->ID ) ? $post->ID : '',
					'ajax_url'                            => admin_url( 'admin-ajax.php' ),
					'tab'                                 => isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.
					'evf_field_drop_nonce'                => wp_create_nonce( 'everest_forms_field_drop' ),
					'evf_add_row_nonce'                   => wp_create_nonce( 'everest_forms_add_row' ),
					'evf_save_form'                       => wp_create_nonce( 'everest_forms_save_form' ),
					'evf_embed_form'                      => wp_create_nonce( 'everest_forms_embed_form' ),
					'evf_goto_edit_page'                  => wp_create_nonce( 'everest_forms_goto_edit_page' ),
					'evf_get_next_id'                     => wp_create_nonce( 'everest_forms_get_next_id' ),
					'evf_enabled_form'                    => wp_create_nonce( 'everest_forms_enabled_form' ),
					'form_id'                             => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
					'field'                               => esc_html__( 'field', 'everest-forms' ),
					'i18n_ok'                             => esc_html__( 'OK', 'everest-forms' ),
					'i18n_installing'                     => esc_html__( 'Installing', 'everest-forms' ),
					'i18n_activating'                     => esc_html__( 'Activating', 'everest-forms' ),
					'i18n_install_activate'               => esc_html__( 'Install & Activate', 'everest-forms' ),
					'i18n_install_only'                   => esc_html__( 'Activate Plugins', 'everest-forms' ),
					'i18n_copy'                           => esc_html__( '(copy)', 'everest-forms' ),
					'i18n_close'                          => esc_html__( 'Close', 'everest-forms' ),
					'i18n_cancel'                         => esc_html__( 'Cancel', 'everest-forms' ),
					'i18n_row_locked'                     => esc_html__( 'Row Locked', 'everest-forms' ),
					'i18n_single_row_locked_msg'          => esc_html__( 'Single row cannot be deleted.', 'everest-forms' ),
					'i18n_field_locked'                   => esc_html__( 'Field Locked', 'everest-forms' ),
					'i18n_field_locked_msg'               => esc_html__( 'This field cannot be deleted or duplicated.', 'everest-forms' ),
					'i18n_row_locked_msg'                 => esc_html__( 'This row cannot be deleted or duplicated.', 'everest-forms' ),
					'i18n_field_error_choice'             => esc_html__( 'This item must contain at least one choice.', 'everest-forms' ),
					'i18n_delete_row_confirm'             => esc_html__( 'Are you sure you want to delete this row?', 'everest-forms' ),
					'i18n_delete_field_confirm'           => esc_html__( 'Are you sure you want to delete this field?', 'everest-forms' ),
					'i18n_duplicate_field_confirm'        => esc_html__( 'Are you sure you want to duplicate this field?', 'everest-forms' ),
					'i18n_duplicate_row_confirm'          => esc_html__( 'Are you sure you want to duplicate this row?', 'everest-forms' ),
					'i18n_email_disable_message'          => esc_html__( 'Turn on Email settings to manage your email notification.', 'everest-forms' ),
					'i18n_upload_image_title'             => esc_html__( 'Choose an image', 'everest-forms' ),
					'i18n_upload_image_button'            => esc_html__( 'Use Image', 'everest-forms' ),
					'i18n_upload_image_remove'            => esc_html__( 'Remove Image', 'everest-forms' ),
					'i18n_field_title_empty'              => esc_html__( 'Empty Form Name', 'everest-forms' ),
					'i18n_shortcut_key_title'             => esc_html__( 'keyboard Shortcut Keys', 'everest-forms' ),
					'i18n_shortcut_keys'                  => array(
						'Ctrl+S' => esc_html__( 'Save Builder', 'everest-forms' ),
						'Ctrl+W' => esc_html__( 'Close Builder', 'everest-forms' ),
						'Ctrl+P' => esc_html__( 'Preview Form', 'everest-forms' ),
						'Ctrl+E' => esc_html__( 'Go to Entries', 'everest-forms' ),
						'Ctrl+H' => esc_html__( 'Open Help', 'everest-forms' ),
					),
					'i18n_field_title_payload'            => esc_html__( 'Form name can\'t be empty.', 'everest-forms' ),
					'email_fields'                        => evf_get_all_email_fields_by_form_id( isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0 ), // phpcs:ignore WordPress.Security.NonceVerification
					'all_fields'                          => evf_get_all_form_fields_by_form_id( isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0 ), // phpcs:ignore WordPress.Security.NonceVerification
					'smart_tags_other'                    => evf()->smart_tags->other_smart_tags(),
					'regex_expression_lists'              => evf()->smart_tags->regex_expression_lists(),
					'entries_url'                         => ! empty( $_GET['form_id'] ) ? esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . absint( $_GET['form_id'] ) ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
					'preview_url'                         => ! empty( $_GET['form_id'] ) ? esc_url( // phpcs:ignore WordPress.Security.NonceVerification
						add_query_arg(
							array(
								'form_id'     => absint( $_GET['form_id'] ), // phpcs:ignore WordPress.Security.NonceVerification
								'evf_preview' => 'true',
								),
							home_url()
						)
					) : '',
					'form_one_time_draggable_fields'      => evf_get_one_time_draggable_fields(),
					'i18n_privacy_policy_consent_message' => esc_html__( 'I allow this website to collect and store the submitted data.', 'everest-forms' ),
					'is_pro'                              => ( ! defined( 'EFP_PLUGIN_FILE' ) ) ? false : true,
				)
			)
		);

		// Builder upgrade.
		wp_register_script( 'evf-upgrade', evf()->plugin_url() . '/assets/js/admin/upgrade.js', array( 'jquery', 'jquery-confirm' ), EVF_VERSION, false );
		wp_localize_script(
			'evf-upgrade',
			'evf_upgrade',
			array(
				'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				'upgrade_title'                  => esc_html__( 'is a PRO Feature', 'everest-forms' ),
				'upgrade_message'                => esc_html__( 'We\'re sorry, the %name% is not available on your plan.<br>Please upgrade to the PRO plan to unlock all these awesome features.', 'everest-forms' ),
				'upgrade_button'                 => esc_html__( 'Upgrade to PRO', 'everest-forms' ),
				'upgrade_url'                    => apply_filters( 'everest_forms_upgrade_url', 'https://everestforms.net/pricing/?utm_source=builder-fields&utm_medium=premium-field-popup&utm_campaign=' . evf()->utm_campaign ),
				'upgrade_integration_url'        => apply_filters( 'everest_forms_upgrade_integration_url', 'https://everestforms.net/pricing/?utm_source=builder-settings&utm_medium=premium-form-settings-popup&utm_campaign=' . evf()->utm_campaign ),
				'enable_stripe_title'            => esc_html__( 'Please enable Stripe', 'everest-forms' ),
				'recaptcha_title'                => esc_html__( 'reCaptcha', 'everest-forms' ),
				'recaptcha_api_key_message'      => esc_html__( 'Please enter a reCaptcha key on Everest Forms>Settings>Captcha>reCaptcha.', 'everest-forms' ),
				'hcaptcha_title'                 => esc_html__( 'hCaptcha', 'everest-forms' ),
				'hcaptcha_api_key_message'       => esc_html__( 'Please enter a hCaptcha key on Everest Forms>Settings>Captcha>hCaptcha.', 'everest-forms' ),
				'turnstile_title'                => esc_html__( ' Cloudflare Turnstile', 'everest-forms' ),
				'turnstile_api_key_message'      => esc_html__( 'Please enter a  Cloudflare Turnstile key on Everest Forms>Settings>Captcha>Cloudflare Turnstile.', 'everest-forms' ),
				'enable_stripe_message'          => esc_html__( 'Enable Stripe Payment gateway in payments section to use this field.', 'everest-forms' ),
				'enable_authorize_net_title'     => esc_html__( 'Please enable Authorize.Net', 'everest-forms' ),
				'enable_authorize_net_message'   => esc_html__( 'Enable Authorize.Net Payment gateway in payments section to use this field.', 'everest-forms' ),
				'enable_square_title'            => esc_html( 'Please enable Square', 'everest-forms' ),
				'enable_square_message'          => esc_html__( 'Enable Square Payment gateway in payments section to use this field.', 'everest-forms' ),
				'evf_install_and_active_nonce'   => wp_create_nonce( 'install_and_active_nonce' ),
				'upgrade_plan_title'             => esc_html__( 'is a Premium Addon', 'everest-forms' ),
				'upgrade_plan_message'           => esc_html__( 'This addon requires premium plan. Please upgrade to the Premium plan to unlock all these awesome field.', 'everest-forms' ),
				'upgrade_plan_button'            => esc_html__( 'Upgrade Plan', 'everest-forms' ),
				'admin_url'                      => admin_url(),
				'vedio_links'                    => array(
					'dropdown' => 'kDYAKElqNtM',
				),
				'evf_one_time_draggable_title'   => esc_html__( 'File upload', 'everest-forms' ),
				'evf_one_time_draggable_message' => esc_html__( 'field can only be used once. To use it multiple times, please upgrade to the pro version.', 'everest-forms' ),

			)
		);

		// EverestForms admin pages.
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_script( 'everest-forms-admin' );
			wp_enqueue_script( 'everest-forms-email-admin' );
			wp_enqueue_script( 'evf-enhanced-select' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'evf-upgrade' );

			wp_localize_script(
				'everest-forms-email-admin',
				'evf_email_params',
				array(
					'i18n_email_connection'  => esc_html__( 'Enter a Email nickname', 'everest-forms' ),
					'i18n_email_placeholder' => esc_html__( 'Eg: Support Email', 'everest-forms' ),
					'i18n_email_error_name'  => esc_html__( 'You must provide a Email nickname', 'everest-forms' ),
					'i18n_email_ok'          => esc_html__( 'OK', 'everest-forms' ),
					'ajax_email_nonce'       => wp_create_nonce( 'process-ajax-nonce' ),
					'ajax_url'               => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_email_cancel'      => esc_html__( 'Cancel', 'everest-forms' ),
					'i18n_default_address'   => get_option( 'admin_email' ),
					'from_name'              => get_bloginfo( 'name', 'display' ),
					'email_subject'          => esc_html__( 'New Form Entry', 'everest-forms' ),
				)
			);

			wp_localize_script(
				'everest-forms-admin',
				'everest_forms_admin',
				array(
					'ajax_import_nonce'             => wp_create_nonce( 'process-import-ajax-nonce' ),
					'evf_search_addons'             => wp_create_nonce( 'everest_forms_search_addons' ),
					'ajax_url'                      => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_field_meta_key_error'     => esc_html__( 'Please enter in meta key with alphanumeric characters, dashes and underscores.', 'everest-forms' ),
					'i18n_field_min_value_greater'  => esc_html__( 'Minimum value is greater than Maximum value.', 'everest-forms' ),
					'i18n_field_max_value_smaller'  => esc_html__( 'Maximum value is smaller than Minimum value.', 'everest-forms' ),
					'i18n_field_def_value_greater'  => esc_html__( 'Default value is greater than Maximum value.', 'everest-forms' ),
					'i18n_field_def_value_smaller'  => esc_html__( 'Default value is smaller than Minimum value.', 'everest-forms' ),
					'i18n_form_export_action_error' => esc_html__( 'Please select a form which you want to export.', 'everest-forms' ),
				)
			);

			wp_localize_script(
				'evf-import-entries-form-csv',
				'evf_import_entries_obj ',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'evf-import-entries' ),
				)
			);

			wp_localize_script(
				'everest-forms-admin',
				'everest_forms_admin_locate',
				array(
					'ajax_locate_nonce' => wp_create_nonce( 'process-locate-ajax-nonce' ),
					'ajax_url'          => admin_url( 'admin-ajax.php', 'relative' ),
					'form_found_error'  => esc_html__( 'Form not found in content', 'everest-forms' ),
					'form_found'        => esc_html__( 'Form found in page:', 'everest-forms' ),
				)
			);

			wp_localize_script(
				'everest-forms-admin',
				'everest_forms_admin_generate_restapi_key',
				array(
					'ajax_restapi_key_nonce' => wp_create_nonce( 'process-restapi-api-ajax-nonce' ),
					'ajax_url'               => admin_url( 'admin-ajax.php', 'relative' ),
				)
			);

			wp_localize_script(
				'everest-forms-admin',
				'everest_forms_admin_form_migrator',
				array(
					'evf_fm_dismiss_notice_nonce' => wp_create_nonce( 'evf_fm_dismiss_notice_nonce' ),
					'ajax_url'                    => admin_url( 'admin-ajax.php', 'relative' ),
				)
			);
		}

		// EverestForms builder pages.
		if ( in_array( $screen_id, array( 'everest-forms_page_evf-builder' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'evf-upgrade' );
			wp_enqueue_script( 'evf-form-builder' );

			wp_enqueue_script( 'wp-codemirror' );
			// Enqueue additional scripts for hints if not included by default
			wp_enqueue_script( 'codemirror-hint', evf()->plugin_url() . '/assets/js/code-mirror/show-hint' . $suffix . '.js', array( 'wp-codemirror' ), EVF_VERSION, true );

			// De-register scripts.
			wp_dequeue_script( 'colorpick' );

			// EverestForms builder setup page.
			if ( isset( $_GET['create-form'] ) || isset( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_register_script( 'evf-setup', evf()->plugin_url() . '/assets/js/admin/evf-setup' . $suffix . '.js', array( 'jquery', 'everest-forms-extensions', 'evf-template-controller' ), EVF_VERSION, true );
				wp_enqueue_script( 'evf-setup' );
				wp_localize_script(
					'evf-setup',
					'evf_setup_params',
					array(
						'ajax_url'                     => admin_url( 'admin-ajax.php' ),
						'create_form_nonce'            => wp_create_nonce( 'everest_forms_create_form' ),
						'evf_active_nonce'             => wp_create_nonce( 'evf_active_nonce' ),
						'template_licence_check_nonce' => wp_create_nonce( 'everest_forms_template_licence_check' ),
						'i18n_form_name'               => esc_html__( 'Give it a name.', 'everest-forms' ),
						'i18n_form_error_name'         => esc_html__( 'You must provide a Form name', 'everest-forms' ),
						'upgrade_url'                  => apply_filters( 'everest_forms_upgrade_url', 'https://everestforms.net/pricing/?utm_source=form-template&utm_medium=button&utm_campaign=' . evf()->utm_campaign ),
						'upgrade_button'               => esc_html__( 'Upgrade Plan', 'everest-forms' ),
						'upgrade_message'              => esc_html__( 'This template requires premium addons. Please upgrade to the Premium plan to unlock all these awesome Templates.', 'everest-forms' ),
						'upgrade_title'                => esc_html__( 'is a Premium Template', 'everest-forms' ),
						'i18n_form_ok'                 => esc_html__( 'Continue', 'everest-forms' ),
						'i18n_form_placeholder'        => esc_html__( 'Untitled Form', 'everest-forms' ),
						'i18n_form_title'              => esc_html__( 'Uplift your form experience to the next level.', 'everest-forms' ),
						'i18n_installing'              => esc_html__( 'installing', 'everest-forms' ),
						'save_changes_text'            => esc_html__( 'Save and Reload', 'everest-forms' ),
						'reload_text'                  => esc_html__( 'Just Reload', 'everest-forms' ),
						'active_confirmation_title'    => esc_html__( 'Activation Successful.', 'everest-forms' ),
						'install_confirmation_title'   => esc_html__( 'Installation Successful.', 'everest-forms' ),
						'install_confirmation_message' => esc_html__( 'Addons have been installed and Activated. You have to reload the page', 'everest-forms' ),
						'active_confirmation_message'  => esc_html__( 'Addons have been Activated. You have to reload the page', 'everest-forms' ),
						'download_failed'              => esc_html__( 'Download Failed', 'everest-forms' ),
						'installing_title'             => esc_html__( 'Installing...', 'everest-forms' ),
						'activate_title'               => esc_html__( 'Activating...', 'everest-forms' ),
						'installing_message'           => esc_html__( 'Please wait while the addon is being installed.', 'everest-forms' ),
						'activate_message'             => esc_html__( 'Please wait while the addon is being activated.', 'everest-forms' ),
					)
				);
			}
		}

		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_style( 'everest-forms-survey-polls-quiz-admin' );
			wp_enqueue_script( 'everest-forms-survey-polls-quiz-builder' );
			wp_enqueue_script( 'random-color' );
			// wp_enqueue_script( 'chart' ); //for future use.
			wp_enqueue_script( 'progress_bar' );
			wp_enqueue_script( 'evf-import-entries-form-csv' );
			wp_enqueue_script( 'print_this' );
			wp_localize_script(
				'everest-forms-survey-polls-quiz-builder',
				'everest_forms_survey_polls_quiz_builder',
				array(
					'ajax_url'                           => admin_url( 'admin-ajax.php', 'relative' ),
					'admin_url'                          => admin_url(),
					'ajax_nonce'                         => wp_create_nonce( 'process-ajax-nonce' ),
					'i18n_field_less_than_highest_point_error' => esc_html__( 'Please enter in a value less than the highest rating point.', 'everest-forms' ),
					'i18n_field_greater_than_lowest_point_error' => esc_html__( 'Please enter in a value greater than the lowest rating point.', 'everest-forms' ),
					'i18n_field_lowest_rating_lower_than_min_value_error' => esc_html__( 'Please enter in a value greater or equal to 0.', 'everest-forms' ),
					'i18n_field_highest_rating_greater_than_max_value_error' => esc_html__( 'Please enter in a value less than 100.', 'everest-forms' ),
					'i18n_field_from_score_greater_than_to_score' => esc_html__( 'Please enter in a value greater than From Score.', 'everest-forms' ),
					'i18n_field_to_score_is_empty_error' => esc_html__( 'Please enter in a value to add more feedback.', 'everest-forms' ),
				)
			);
		}

		// Tools page.
		if ( 'everest-forms_page_evf-tools' === $screen_id ) {
			wp_register_script( 'evf-admin-tools', evf()->plugin_url() . '/assets/js/admin/tools' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
			wp_enqueue_script( 'evf-admin-tools' );
			wp_localize_script(
				'evf-admin-tools',
				'everest_forms_admin_tools',
				array(
					'ajax_url'                    => admin_url( 'admin-ajax.php' ),
					'delete_log_confirmation'     => esc_js( esc_html__( 'Are you sure you want to delete this log?', 'everest-forms' ) ),
					'delete_all_log_confirmation' => esc_js( esc_html__( 'Are you sure you want to delete all logs?', 'everest-forms' ) ),
				)
			);
			wp_localize_script(
				'evf-admin-tools',
				'everest_forms_form_migrator',
				array(
					'ajax_url'                           => admin_url( 'admin-ajax.php' ),
					'evf_form_migrator_forms_list_nonce' => wp_create_nonce( 'evf_form_migrator_forms_list_nonce' ),
					'evf_form_migrator_nonce'            => wp_create_nonce( 'evf_form_migrator_nonce' ),
					'evf_form_entry_migrator_nonce'      => wp_create_nonce( 'evf_form_entry_migrator_nonce' ),
				)
			);
		}

		// Add-ons/extensions page.
		if ( 'everest-forms_page_evf-addons' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-extensions' );
		}
	}
}

return new EVF_Admin_Assets();
