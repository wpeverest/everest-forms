<?php
/**
 * Admin View: Notice - Email Failed Notice.
 *
 * @package Everest Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="notice notice-warning is-dismissible" id="everest-forms-email-failed-notice">
	<p>
		<strong><?php esc_html_e( 'Everest Forms Email Send Error', 'everest-forms' ); ?></strong><br/>
		<?php esc_html_e( 'The last email sent from the Everest Forms Plugin was not delivered to the user.', 'everest-forms' ); ?>
	</p>
	<p style="border-left: 2px solid #72aee6; background: #F0FFFF; padding: 10px;">\
		/* translators: %s: Error Message*/
		<?php echo esc_html( sprintf( __( '%s', 'everest-forms' ), $error_message ) ); ?>
	</p>
	<br/>
	<p>
		<a href="https://docs.wpeverest.com/everest-forms/docs/emails-are-not-being-delivered/" target="_blank">
			<?php esc_html_e( 'Learn More', 'everest-forms' ); ?>
		</a>
	</p>
</div>

<script>
	jQuery(function($) {
		$(document).ready(function() {
			var noticeContainer = $('#everest-forms-email-failed-notice');
			noticeContainer.find('.notice-dismiss').on('click', function(e) {
				e.preventDefault();

				$.post(ajaxurl, {
					action: 'everest_forms_email_failed_notice_dismiss',
					_wpnonce: '<?php echo esc_js( wp_create_nonce( 'email_failed_nonce' ) ); ?>'
				});
			});
		});
	});
</script>
