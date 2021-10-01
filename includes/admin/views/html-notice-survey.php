<?php
/**
 * Admin View: Notice - Survey
 *
 * @package EverestForms\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated everest-forms-message evf-survey-notice">
	<div class="everest-forms-logo">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.15,4l1.23,2H15.49L14.26,4ZM20,20H2.21L12,4.09,18.1,14H10.77L12,12h2.52L12,7.91,5.79,18H20.56l1.23,2ZM17.94,10,16.71,8H20.6l1.23,2Z"/></svg>
	</div>
	<div class="everest-forms-message--content">
		<h3 class="everest-forms-message__title"><?php esc_html_e( 'Everest Form Plugin Survey', 'everest-forms' ); ?></h3>
		<p class="everest-forms-message__description">
		<p>
		<?php
		_e(
			'<strong>Hey there!</strong> <br>
								We would be grateful if you could spare a moment and help us fill this survey. This survey will take approximately 4 minutes to complete.',
			'everest-forms'
		);
		?>
		</p>
		<p class="extra-pad">
		<?php
		_e(
			'<strong>What benefit would you have?</strong> <br>
								We will take your feedback from the survey and use that information to make the plugin better. As a result, you will have a better plugin as you wanted. <span class="dashicons dashicons-smiley smile-icon"></span><br>',
			'everest-forms'
		);
		?>
		</p>
		</p>
		<p class="everest-forms-message__action submit">
			<a href="https://survey.wpeverest.com/everest-forms/" class="button button-primary evf-dismiss-review-notice evf-survey-received" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'Sure, I\'d love to!', 'everest-forms' ); ?></a>
			<a href="#" class="button button-secondary evf-dismiss-survey-notice" target="_blank" rel="noopener noreferrer"><span  class="dashicons dashicons-smiley"></span><?php esc_html_e( 'I already did', 'everest-forms' ); ?></a>
			<a href="#" class="button button-secondary evf-dismiss-survey-notice" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Never show again  ', 'everest-forms' ); ?></a>
			<a href="https://wpeverest.com/support-forum/" class="button button-secondary evf-have-query" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-testimonial"></span><?php esc_html_e( 'I have a query', 'everest-forms' ); ?></a>
		</p>
	</div>
</div>
<script type="text/javascript">
	jQuery( document ).ready( function ( $ ) {
		$( document ).on( 'click', '.evf-dismiss-survey-notice, .evf-survey-notice button', function ( event ) {
			if ( ! $( this ).hasClass( 'evf-survey-received' ) ) {
				event.preventDefault();
			}
			$.post( ajaxurl, {
				action: 'everest_forms_survey_dismiss'
			} );
			$( '.evf-survey-notice' ).remove();
		} );
	} );
</script>
