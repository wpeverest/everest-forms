<?php
/**
 * Admin View: Notice - Allow Usage
 *
 * @package EverestForms\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated everest-forms-message evf-allow-usage-notice">
	<div class="everest-forms-logo">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.15,4l1.23,2H15.49L14.26,4ZM20,20H2.21L12,4.09,18.1,14H10.77L12,12h2.52L12,7.91,5.79,18H20.56l1.23,2ZM17.94,10,16.71,8H20.6l1.23,2Z"/></svg>
	</div>
	<div class="everest-forms-message--content">
		<h3 class="everest-forms-message__title">
		<?php esc_html_e( 'Contribute to the enhancement', 'everest-forms' ); ?>
		</h3>
		<p class="everest-forms-message__description">
			<?php
			if ( false !== evf_get_license_plan() ) {
				printf(
					wp_kses(
						__( 'Help us improve the plugin\'s features by sharing <a href="https://docs.wpeverest.com/everest-forms/docs/misc-settings/#2-toc-title" target="_blank">non-sensitive plugin data</a> with us.', 'everest-forms' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array()
							)
						)
					)
				);
			} else {
				printf(
					wp_kses(
						__( ' Help us improve the plugin\'s features and receive an instant discount coupon with occasional email updates by sharing <a href="https://docs.wpeverest.com/everest-forms/docs/misc-settings/#2-toc-title" target="_blank">non-sensitive plugin data</a> with us.', 'everest-forms' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array()
							)
						)
					)
				);
			}
			?>
		</p>
		<p class="everest-forms-message__action submit">
			<a href="#" class="button button-primary evf-dismiss-allow-usage-notice evf-allow-data-sharing" target="_blank" rel="noopener noreferrer"><span  class="dashicons dashicons-smiley"></span><?php esc_html_e( 'Allow', 'everest-forms' ); ?></a>
			<a href="#" class="button button-secondary evf-dismiss-allow-usage-notice evf-deny-data-sharing" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'No, Thanks', 'everest-forms' ); ?></a>
		</p>
	</div>
</div>
<script type="text/javascript">
	jQuery( document ).ready( function ( $ ) {
		$( document ).on( 'click', '.evf-dismiss-allow-usage-notice', function ( event ) {
			event.preventDefault();
			var allow_usage_tracking = false;

			if( $(this).hasClass('evf-allow-data-sharing') ) {
				allow_usage_tracking = true;
			}

			$.post( ajaxurl, {
				action: 'everest_forms_allow_usage_dismiss',
				allow_usage_tracking: allow_usage_tracking,
				_wpnonce: '<?php echo esc_js( wp_create_nonce( 'allow_usage_nonce' ) ); ?>'
			} );
			$( '.evf-allow-usage-notice' ).remove();
		} );
	} );
</script>
