<?php
/**
 * EverestForms Import Export Class
 *
 * @package EverestForms\Admin
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Forms class.
 */
class EVF_Admin_Import_Export {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'export_json' ) );
	}

	/**
	 * Exports form data along with settings in JSON format.
	 */
	public function export_json() {
		// Check for non empty $_POST.
		if ( ! isset( $_POST['everest-forms-export-form'] ) || ! isset( $_POST['everest-forms-export-nonce'] ) ) {
			return;
		}

		// Nonce check.
		if ( ! wp_verify_nonce( wp_unslash( $_POST['everest-forms-export-nonce'] ), 'everest_forms_export_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms' ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;

		// Return if form id is not set and current user doesnot have export capability.
		if ( empty( $form_id ) || ! current_user_can( 'export' ) ) {
			return;
		}

		$form_post   = get_post( $form_id );
		$export_data = array(
			'form_post' => array(
				'post_content' => $form_post->post_content,
				'post_title'   => $form_post->post_title,
				'post_name'    => $form_post->post_name,
				'post_type'    => $form_post->post_type,
				'post_status'  => $form_post->post_status,
			),
		);
		$form_name   = strtolower( str_replace( ' ', '-', get_the_title( $form_id ) ) );
		$file_name   = html_entity_decode( $form_name, ENT_QUOTES, 'UTF-8' ) . '-' . current_time( 'Y-m-d_H:i:s' ) . '.json';

		if ( ob_get_contents() ) {
			ob_clean();
		}

		$export_json = wp_json_encode( $export_data );
		// Force download.
		header( 'Content-Type: application/force-download' );
		// Disposition / Encoding on response body.
		header( "Content-Disposition: attachment;filename={$file_name}; charset=utf-8" );
		header( 'Content-type: application/json' );
		echo $export_json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit();
	}

	/**
	 * Import Form from backend.
	 */
	public static function import_form() {
		// Check for $_FILES set or not.
		if ( isset( $_FILES['jsonfile']['name'], $_FILES['jsonfile']['tmp_name'] ) ) {
			$filename  = esc_html( sanitize_text_field( wp_unslash( $_FILES['jsonfile']['name'] ) ) );
			$extension = pathinfo( $filename, PATHINFO_EXTENSION );

			// Check for file format.
			if ( 'json' === $extension ) {
				$form_data = json_decode( file_get_contents( $_FILES['jsonfile']['tmp_name'] ) ); // @codingStandardsIgnoreLine

				// Check for non-empty JSON file.
				if ( ! empty( $form_data ) ) {
					// Check for non-empty post data array.
					if ( ! empty( $form_data->form_post ) ) {
						$args  = array( 'post_type' => 'everest_form' );
						$forms = get_posts( $args );

						foreach ( $forms as $key => $form_obj ) {
							if ( $form_data->form_post->post_title === $form_obj->post_title ) {
								$form_data->form_post->post_title = $form_data->form_post->post_title . ' (Imported)';
								break;
							}
						}

						// Get the form data.
						$new_form_data = evf_decode( $form_data->form_post->post_content );
						$new_form      = array(
							'post_content' => evf_encode( $new_form_data ),
							'post_status'  => $form_data->form_post->post_status,
							'post_title'   => $form_data->form_post->post_title,
							'post_type'    => $form_data->form_post->post_type,
						);
						$post_id       = wp_insert_post( $new_form );

						// Set new form ID.
						$new_form_data['id'] = absint( $post_id );
						$form                = array(
							'ID'           => $post_id,
							'post_content' => evf_encode( $new_form_data ),
						);

						wp_update_post( $form );

						// Check for any error while inserting.
						if ( is_wp_error( $post_id ) ) {
							return $post_id;
						}

						if ( $post_id ) {
							wp_send_json_success(
								array(
									'message' => esc_html__( 'Imported Successfully.', 'everest-forms' ),
								)
							);
						}
					} else {
						wp_send_json_error(
							array(
								'message' => esc_html__( 'Invalid file content. Please export file from Everest Forms plugin.', 'everest-forms' ),
							)
						);
					}
				} else {
					wp_send_json_error(
						array(
							'message' => esc_html__( 'Invalid file content. Please export file from Everest Forms plugin.', 'everest-forms' ),
						)
					);
				}
			} else {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Invalid file format. Only JSON File Allowed.', 'everest-forms' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please select json file to import form data.', 'everest-forms' ),
				)
			);
		}
	}
}

new EVF_Admin_Import_Export();
