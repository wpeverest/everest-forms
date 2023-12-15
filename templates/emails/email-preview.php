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
			<style>
				html,
				body {
					overflow: auto;
					-webkit-overflow-scrolling: auto;
					margin: 0;
					min-height: 100vh;
				}
			</style>
		</head>
		<body <?php body_class(); ?> >
			<?php
			/**
			 * Get email message from the specific email connection
			 *
			 * @return array email preview message.
			 */
			function form_data() {
				$form_data = array();

				$connection_id = isset( $_GET['evf_email_preview'] ) ? $_GET['evf_email_preview'] : '';

				if ( ! empty( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$form_data             = evf()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification
					$email_preview_message = $form_data['settings']['email'][ "$connection_id" ]['evf_email_message'];
				}

				return $email_preview_message;
			}

			$email_content = form_data();
			if ( has_filter( 'everest_forms_process_smart_tags' ) ) {
				echo evf_process_email_content( apply_filters('everest_forms_process_smart_tags',$email_content, array(), '','') ); // phpcs:ignore.
			}
			?>
		</body>
	</html>
