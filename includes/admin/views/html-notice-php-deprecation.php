<?php
/**
 * Admin View: Notice - PHP Deprecation
 *
 * @package Everest Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="notice notice-warning is-dismissible" id="everest-forms-php-deprecation-notice">
	<p>
		<strong><?php esc_html_e( 'Warning!', 'everest-forms' ); ?></strong>
		<?php _e( "Your website is running on an outdated version of PHP ( v$php_version ) that might not be supported by <strong>Everest Forms</strong> plugin in future updates.", 'everest-forms' ); //phpcs:ignore ?>
		</br>
		<?php
		echo esc_html__( //phpcs:ignore
			sprintf( //phpcs:ignore
				'Please update to at least PHP v%s to ensure compatibility and security.',
				$base_version
			),
			'everest-forms'
		);
		?>
		<a href="https://docs.wpeverest.com/everest-forms/docs/php-version-lesser-than-7-2-is-not-supported/" target="_blank"><?php esc_html_e( 'Learn More', 'everest-forms' ); ?> </a>
	</p>
</div>

<script>

	jQuery( function( $ ) {
		$(document).ready( function() {
			var notice_container = $('#everest-forms-php-deprecation-notice');
			notice_container.find( '.notice-dismiss' ).on( 'click', function(e) {
				e.preventDefault();

				$.post( ajaxurl, {
				action: 'everest_forms_php_notice_dismiss',
				_wpnonce: '<?php echo esc_js( wp_create_nonce( 'php_notice_nonce' ) ); ?>'
				} );
			});

		});
	});
</script>
