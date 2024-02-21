<?php
/**
 * Everest Form email preview template.
 *
 * @since 2.0.5
 *
 * @package Everest form email preview template.
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
	<html <?php language_attributes(); ?> style="background-color: #E9EAEC;">
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title>
				<?php get_bloginfo( 'name' ); ?>
			</title>
		</head>
		<body <?php body_class(); ?> >
			<?php
			$connection_id = isset( $_GET['evf_email_preview'] ) ? $_GET['evf_email_preview'] : '';
			/**
			 * Get email message from the specific email connection
			 *
			 * @return array email preview message.
			 */
			function form_data() {
				$form_data = array();

				if ( ! empty( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$connection_id = isset( $_GET['evf_email_preview'] ) ? $_GET['evf_email_preview'] : '';
					$form_data     = evf()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification

					if ( isset( $form_data['settings']['email'][ "$connection_id" ] ) && isset( $form_data['settings']['email'][ "$connection_id" ]['evf_email_message'] ) ) {
						$email_preview_message = $form_data['settings']['email'][ "$connection_id" ]['evf_email_message'];
					}
				}

				return $form_data;
			}


			// Email data of the specific connection.
			$email_form_data = form_data();

			if ( isset( $email_form_data['settings']['email'] ) && isset( $email_form_data['settings']['email'][ $connection_id ] ) ) {
				$email_content           = $email_form_data['settings']['email'][ $connection_id ]['evf_email_message'];
				$email_template_included = isset( $email_form_data['settings']['email'][ $connection_id ]['choose_template'] ) ? $email_form_data['settings']['email'][ $connection_id ]['choose_template'] : 0;
			}

			// Initializing the EVF_Emails class to import the email template.
			$evf_emails_obj            = new EVF_Emails();
			$evf_emails_obj->form_data = $email_form_data;

			if ( empty( $email_content ) ) {
				$email_content = esc_html__( '{all_fields}', 'everest-forms' );
			}

			$email_content = str_replace( '{all_fields}', evf_process_all_fields_smart_tag( $email_content ), $email_content );

			// Email Template Enabled or not checked.
			$email_template_included = ! empty( $email_form_data['settings']['email'][ $connection_id ]['choose_template'] ) ? $email_content : 0;

			if ( $email_template_included ) {
				$email_content = apply_filters( 'everest_forms_email_template_message', $email_content, $evf_emails_obj, $connection_id );
				echo $email_content;
			} else {
				$email_content = $evf_emails_obj->build_email( $email_content );
				echo $email_content;
			}

			?>
		</body>
	</html>
