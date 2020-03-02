<?php
/**
 * Admin View: Notice - Review
 *
 * @package EverestForms\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated everest-forms-message evf-review-notice">
	<div class="everest-forms-logo">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.15,4l1.23,2H15.49L14.26,4ZM20,20H2.21L12,4.09,18.1,14H10.77L12,12h2.52L12,7.91,5.79,18H20.56l1.23,2ZM17.94,10,16.71,8H20.6l1.23,2Z"/></svg>
	</div>
	<div class="everest-forms-message--content">
		<h3 class="everest-forms-message__title"><?php esc_html_e( 'Please help us spread the word', 'everest-forms' ); ?></h3>
		<p class="everest-forms-message__description">
			<?php
			/* translators: %1$s: Plugin Name, %2$s: Rating link */
			printf( esc_html__( 'Enjoying the experience with %1$s? Please take a moment to spread your love by rating us on %2$s', 'everest-forms' ), '<strong>Everest Forms</strong>', '<a href="https://wordpress.org/support/plugin/everest-forms/reviews?rate=5#new-post" target="_blank"><strong>WordPress.org</strong>!</a>' );
			?>
		</p>
		<p class="everest-forms-message__action submit">
			<a href="https://wordpress.org/support/plugin/everest-forms/reviews?rate=5#new-post" class="button button-primary evf-dismiss-review-notice evf-review-received" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sure, I\'d love to!', 'everest-forms' ); ?></a>
			<a href="#" class="button button-secondary evf-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Remind me later', 'everest-forms' ); ?></a>
			<a href="#" class="evf-button-link evf-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'I already did', 'everest-forms' ); ?></a>
		</p>
	</div>
</div>
<script type="text/javascript">
	jQuery( document ).ready( function ( $ ) {
		$( document ).on( 'click', '.evf-dismiss-review-notice, .evf-review-notice button', function ( event ) {
			if ( ! $( this ).hasClass( 'evf-review-received' ) ) {
				event.preventDefault();
			}
			$.post( ajaxurl, {
				action: 'everest_forms_review_dismiss'
			} );
			$( '.evf-review-notice' ).remove();
		} );
	} );
</script>
