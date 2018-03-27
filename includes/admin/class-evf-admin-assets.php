<?php
/**
 * Load Admin Assets
 *
 * @package EverestForms/Admin
 * @version 1.00
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
		add_action( 'everest_forms_builder_scripts', array( $this, 'everest_forms_builder_scripts' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		global $wp_scripts;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'everest-forms-admin', EVF()->plugin_url() . '/assets/css/admin.css', array(), EVF_VERSION );
		wp_register_style( 'jquery-ui-style', EVF()->plugin_url() . '/assets/css/jquery-ui.min.css', array(), EVF_VERSION );
		wp_register_style( 'evf_add_form_css', EVF()->plugin_url() . '/assets/css/evf-add-form.css', array(), EVF_VERSION );
		wp_register_style( 'evf-admin-entries-style', EVF()->plugin_url() . '/assets/css/admin-entries.css', array(), EVF_VERSION );
		wp_register_style( 'evf-admin-setting-style', EVF()->plugin_url() . '/assets/css/admin-settings.css', array(), EVF_VERSION );

		// Add RTL support for admin styles.
		wp_style_add_data( 'everest-forms-admin', 'rtl', 'replace' );

		// Sitewide menu styles.
		wp_enqueue_style( 'evf_add_form_css' );

		// Admin styles for EVF pages only.
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_style( 'everest-forms-admin' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-color-picker' );
		}

		// Entries styles.
		if ( in_array( $screen_id, array( 'toplevel_page_everest-forms', 'everest-forms_page_evf-entries' ), true ) ) {
			wp_enqueue_style( 'evf-admin-entries-style' );
		}

		// Settings styles.
		if ( $screen_id === 'everest-forms_page_evf-settings' ) {
			wp_enqueue_style( 'evf-admin-setting-style' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts
		wp_register_script( 'everest_forms_builder', EVF()->plugin_url() . '/assets/js/admin/everest-builder' . $suffix . '.js', array( 'jquery' ), EVF_VERSION );
		wp_register_script( 'everest_forms_settings', EVF()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array( 'jquery' ), EVF_VERSION );
		wp_register_script( 'everest-forms-admin', EVF()->plugin_url() . '/assets/js/admin/everest-forms-admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), EVF_VERSION );
		wp_register_script( 'everest-forms-extensions', EVF()->plugin_url() . '/assets/js/admin/extensions' . $suffix . '.js', array( 'jquery', 'updates' ), EVF_VERSION, true );
		wp_register_script( 'jquery-confirm-script', EVF()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm.min.js', array( 'jquery' ), '3.3.0' );
		wp_register_script( 'jquery-blockui', EVF()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'jquery-tiptip', EVF()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'evf_add_form_js', EVF()->plugin_url() . '/assets/js/admin/evf-add-form' . $suffix . '.js', 'jquery' );
		wp_localize_script( 'evf_add_form_js', 'everest_add_form_params', array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'create_form_nonce' => wp_create_nonce( 'everest_forms_create_form' ),
		) );

		wp_enqueue_script( 'evf_add_form_js' );

		if ( 'everest-forms_page_evf-settings' === $screen_id ) {
			wp_enqueue_script( 'everest_forms_settings' );

		}

		// EverestForms admin pages.
		if ( in_array( $screen_id, evf_get_screen_ids() ) ) {
			wp_enqueue_script( 'everest-forms-admin' );
		}

		// Add-ons/extensions Page.
		if ( 'everest-forms_page_evf-addons' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-extensions' );
		}
	}

	/**
	 * Enqueue scripts for the builder.
	 */
	public function everest_forms_builder_scripts() {
		// Remove conflicting scripts.
		do_action( 'everest_forms_builder_enqueues_before' );

		wp_enqueue_style( 'everest_forms_admin_menu_styles', EVF()->plugin_url() . '/assets/css/everest-builder.css', array(), EVF_VERSION );
		wp_enqueue_style( 'jquery-confirm-style', EVF()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm.min.css', array(), '3.3.0' );

		wp_enqueue_script( 'evf-admin-helper', EVF()->plugin_url() . '/assets/js/admin/admin-helper.js', array( 'jquery', 'jquery-blockui', 'jquery-tiptip', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-tiptip' ), EVF_VERSION );
		wp_enqueue_script( 'evf-panel-builder', EVF()->plugin_url() . '/assets/js/admin/everest-panel-builder.js', array( 'evf-admin-helper', 'jquery-confirm-script' ), EVF_VERSION );

		wp_enqueue_media();

		$params = apply_filters( 'everest_forms_builder_strings', array(
			'ajax_url'                               => admin_url( 'admin-ajax.php' ),
			'evf_field_drop_nonce'                   => wp_create_nonce( 'everest_forms_field_drop' ),
			'evf_save_form'                          => wp_create_nonce( 'everest_forms_save_form' ),
			'evf_get_next_id'                        => wp_create_nonce( 'everest_forms_get_next_id' ),
			'form_id'                                => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0,
			'are_you_sure_want_to_delete_this'       => __( 'Are you sure want to delete this', 'everest-forms' ),
			'field'                                  => __( 'field', 'everest-forms' ),
			'confirm'                                => __( 'Confirm', 'everest-forms' ),
			'cancel'                                 => __( 'Cancel', 'everest-forms' ),
			'delete_confirm_title'                   => __( 'Delete Confirmation', 'everest-forms' ),
			'duplicate_confirm_title'                => __( 'Duplicate Confirmation', 'everest-forms' ),
			'are_you_sure_want_to_duplicate_this'    => __( 'Are you sure want to duplicate this', 'everest-forms' ),
			'are_you_sure_want_to_delete_row'        => __( 'Are you sure want to delete this row?', 'everest-forms' ),
			'copy_of'                                => __( 'Copy of ', 'everest-forms' ),
			'ok'                                     => __( 'Ok', 'everest-forms' ),
			'could_not_delete_single_row_title'      => __( 'Could not delete', 'everest-forms' ),
			'could_not_delete_single_row_content'    => __( 'Could not delete single row.', 'everest-forms' ),
			'could_not_delete_single_choice'         => __( 'Could not delete single choice.', 'everest-forms' ),
			'could_not_delete_single_choice_content' => __( 'Could not delete single choice.', 'everest-forms' ),
			'tab'                                    => isset( $_GET['tab'] ) ? $_GET['tab'] : ''
		) );

		wp_localize_script( 'evf-panel-builder', 'evf_data', $params );
	}
}

return new EVF_Admin_Assets();
