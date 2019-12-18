<?php
/**
 * EverestForms Import Export Class
 *
 * @package EverestForms\Admin
 * @since   1.5.11
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Forms class.
 */
class EVF_Admin_Import_Export {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'export_json' ) );
	}

	/**
	 * Exports form data along with settings in JSON format.
	 *
	 * @return void
	 */
	public function export_json() {
		global $wpdb;
		// Check for non empty $_POST.
		if ( ! isset( $_POST['everest-forms-export-form'] ) ) {
			return;
		}

		$form_id = isset( $_POST['form_id'] ) ? $_POST['form_id'] : 0;
		// Return if form id is not set and current user doesnot have export capability.
		if ( ! isset( $form_id ) || ! current_user_can( 'export' ) ) {
			return;
		}
		$form_post       = get_post( $form_id );

		$export_data = array(
			'form_post'      => array(
				'post_content' => $form_post->post_content,
				'post_title'   => $form_post->post_title,
				'post_name'    => $form_post->post_name,
				'post_type'    => $form_post->post_type,
				'post_status'  => $form_post->post_status,
			),
		);
		$form_name = strtolower( str_replace( ' ', '-', get_the_title( $form_id ) ) );
		$file_name = $form_name . '-' . current_time( 'Y-m-d_H:i:s' ) . '.json';
		if ( ob_get_contents() ) {
			ob_clean();
		}
		$export_json = wp_json_encode( $export_data );
		// Force download.
		header( 'Content-Type: application/force-download' );
		// Disposition / Encoding on response body.
		header( "Content-Disposition: attachment;filename={$file_name}" );
		header( 'Content-type: application/json' );
		echo $export_json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit();
	}


}
new EVF_Admin_Import_Export();
