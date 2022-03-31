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
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['everest-forms-export-nonce'] ) ), 'everest_forms_export_nonce' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms' ) );
		}
		$form_ids = isset( $_POST['form_ids'] ) ? wp_unslash( $_POST['form_ids'] ) : array(); //phpcs:ignore

		// Return if form id is not set and current user doesnot have export capability.
		if ( empty( $form_ids ) || ! current_user_can( 'export' ) ) {
			return;
		}

		$zip      = new ZipArchive();
		$zip_name = 'evf-json-form-files-' . time() . '.zip';

		if ( $zip->open( $zip_name, ZIPARCHIVE::CREATE ) === true ) {
			foreach ( $form_ids as $key => $form_id ) {
				$form_id     = absint( wp_unslash( $form_id ) );
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
				$file_name   = html_entity_decode( $form_name, ENT_QUOTES, 'UTF-8' ) . '-' . current_time( 'Y-m-d_H:i:s' ) . wp_hash( $form_id ) . '.json';
				// Export form styles if found.
				$form_styles = get_option( 'everest_forms_styles', array() );
				if ( ! empty( $form_styles[ $form_id ] ) ) {
					$export_data['form_styles'] = wp_json_encode( $form_styles[ $form_id ] );
				}

				if ( ob_get_contents() ) {
					ob_clean();
				}

				if ( 1 === count( $form_ids ) ) {
					// Force download.
					header( 'Content-Type: application/force-download' );
					// Disposition / Encoding on response body.
					header( "Content-Disposition: attachment;filename={$file_name}; charset=utf-8" );
					header( 'Content-type: application/json' );
					echo wp_json_encode( $export_data );
					exit();
				} else {
					$zip->addFromString( $file_name, wp_json_encode( $export_data ) );
				}
			}
		}

		$zip->close();

		if ( file_exists( $zip_name ) ) {
			if ( 1 < count( $form_ids ) ) {
				// push to download the zip.
				header( 'Content-type: application/zip' );
				header( 'Content-Disposition: attachment; filename="' . $zip_name . '"' );
				readfile( $zip_name ); // phpcs:ignore
			}
			// remove zip file is exists in temp path.
			unlink( $zip_name );
		}
	}

	/**
	 * Import Forms from backend.
	 */
	public static function import_forms() {
		$files = isset( $_FILES['jsonfiles'] ) ? $_FILES['jsonfiles'] : array(); //phpcs:ignore

		// Velidate if files exist.
		if ( 0 < count( isset( $files['tmp_name'] ) ? $files['tmp_name'] : array() ) ) {
			$is_importable  = array();
			$post_ids       = array();
			$import_success = false;

			// Loop through file items.
			foreach ( $files['name'] as $id => $file_name ) {
				if ( 0 === $files['error'][ $id ] ) {
					$filename  = sanitize_file_name( wp_unslash( $file_name ) );
					$extension = pathinfo( $filename, PATHINFO_EXTENSION );

					// Check for file format.
					if ( 'json' === $extension ) {
						$form_data = json_decode( file_get_contents( $files['tmp_name'][ $id ] ) ); // @codingStandardsIgnoreLine

						// Check for non-empty JSON file.
						if ( ! empty( $form_data ) ) {
							// Check for non-empty post data array.
							if ( ! empty( $form_data->form_post ) ) {
								$is_importable[] = true;
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
									'message' => esc_html__( 'Invalid file format. Only JSON File Allowed.', 'everest-forms' ),
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
							'message' => esc_html__( 'Invalid file format. Only JSON File Allowed.', 'everest-forms' ),
						)
					);
				}
			}
			// Insert new forms.
			if ( count( $files['tmp_name'] ) === count( $is_importable ) ) {
				foreach ( $files['name'] as $id => $file_name ) {
					$form_data = json_decode( file_get_contents( $files['tmp_name'][ $id ] ) ); // @codingStandardsIgnoreLine
					$args      = array( 'post_type' => 'everest_form' );
					$forms     = get_posts( $args );

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

					// Import form styles if present.
					$style_needed = false;
					if ( ! empty( $form_data->form_styles ) ) {
						$style_needed            = true;
						$form_styles             = get_option( 'everest_forms_styles', array() );
						$form_styles[ $post_id ] = evf_decode( $form_data->form_styles );

						// Update forms styles.
						update_option( 'everest_forms_styles', $form_styles );
					}

					do_action( 'everest_forms_import_form', $post_id, $form, array(), $style_needed );

					// Check for any error while inserting.
					if ( is_wp_error( $post_id ) ) {
						return $post_id;
					}

					if ( $post_id ) {
						$import_success = true;
						$post_ids []    = absint( $post_id );
					}
				}
			}

			if ( $import_success ) {
				if ( 1 === count( $is_importable ) ) {
					wp_send_json_success(
						array(
							'message' => esc_html__( 'Imported Successfully. ', 'everest-forms' ) . '<a href="' . esc_url( admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $new_form_data['id'] ) ) . '">' . esc_html__( 'View Form', 'everest-forms' ) . '</a>',
						)
					);
				} else {
					wp_send_json_success(
						array(
							'message' => esc_html__( 'Imported Successfully. ', 'everest-forms' ),
							'data'    => $post_ids,
						)
					);
				}
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
