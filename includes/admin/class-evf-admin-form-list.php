<?php
/**
 * Form List Page
 *
 * @author      WPEverest
 * @category    Admin
 * @package     EverestForms/Admin/Form List Page
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Admin_Form_List Class.
 */
class EVF_Admin_Form_List {

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
		global $forms_table_list;

		$forms_table_list->prepare_items();
		include_once( dirname( __FILE__ ) . '/views/html-admin-page-forms.php' );

		add_action( 'everest_form_list_admin_footer', array( __CLASS__, 'everest_form_list_admin_footer' ), 10, 1 );
	}

	public static function everest_form_list_admin_footer( $screen_id ) {

		if ( $screen_id === 'toplevel_page_everest-forms' ) {

			include_once( dirname( __FILE__ ) . '/views/html-admin-form-modal.php' );


			wp_enqueue_style( 'evf-form-modal-style', EVF()->plugin_url() . '/assets/css/evf-form-modal.css', array(), EVF_VERSION );

			wp_enqueue_script(
				'evf-admin-form-modal',
				EVF()->plugin_url() . '/assets/js/admin/evf-form-modal.js',
				array(
					'underscore',
					'backbone',
					'wp-util',

				),
				EVF_VERSION
			);

			$strings = array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'evf_new_form_nonce' => wp_create_nonce( 'evf_new_form' ),

			);
			$strings = apply_filters( 'everest_forms_builder_modal_strings', $strings );


			wp_localize_script(
				'evf-admin-form-modal',
				'evf_form_modal_data',
				$strings
			);

		}

	}

}
