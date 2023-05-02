<?php
/**
 * Deactivation popup admin
 *
 * Link to WPEverest contact form page.
 *
 * @package EverestForms\Admin
 * @version 1.9.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $status, $page, $s;

$deactivate_url = wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . EVF_PLUGIN_BASENAME . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . EVF_PLUGIN_BASENAME );
?>
<div id="evf-deactivate-feedback-popup-wrapper">
	<div class="evf-deactivate-feedback-popup-inner">
		<div class="evf-deactivate-feedback-popup-header">
			<div class="everest-forms-deactivate-feedback-popup-header__logo-wrap">
				<div class="everest-forms-deactivate-feedback-popup-header__logo-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.88,3l1.37,2.25H15.89L14.52,3ZM21,21H1L12,3.15l6.84,11.11H10.6L12,12H14.8L12,7.43,5,18.74H21.58L23,21ZM18.64,9.77,17.27,7.53h4.36L23,9.77Z"/></svg>
				</div>
				<span class="evf-deactivate-feedback-popup-header-title"><?php echo esc_html__( 'Quick Feedback', 'everest-forms' ); ?></span>
			</div>
			<a class="close-deactivate-feedback-popup"><span class="dashicons dashicons-no-alt"></span></a>
		</div>
		<form class="evf-deactivate-feedback-form" method="POST">
			<?php
			wp_nonce_field( '_evf_deactivate_feedback_nonce' );
			?>
			<input type="hidden" name="action" value="evf_deactivate_feedback"/>

			<div
				class="evf-deactivate-feedback-popup-form-caption"><?php echo sprintf( esc_html__( 'Could you please share why you are deactivating %1$sEverest Forms%2$s plugin?', 'everest-forms' ), '<span>', '</span>' ); ?></div>
			<div class="evf-deactivate-feedback-popup-form-body">
				<?php foreach ( $deactivate_reasons as $reason_slug => $reason ) : ?>
					<div class="evf-deactivate-feedback-popup-input-wrapper">
						<input id="evf-deactivate-feedback-<?php echo esc_attr( $reason_slug ); ?>"
							class="evf-deactivate-feedback-input" type="radio" name="reason_slug"
							value="<?php echo esc_attr( $reason_slug ); ?>"/>
						<label for="evf-deactivate-feedback-<?php echo esc_attr( $reason_slug ); ?>"
							class="evf-deactivate-feedback-label"><?php echo esc_html( $reason['title'] ); ?></label>
						<?php if ( ! empty( $reason['input_placeholder'] ) ) : ?>
							<input class="evf-feedback-text" type="text"
								name="reason_<?php echo esc_attr( $reason_slug ); ?>"
								placeholder="<?php echo esc_attr( $reason['input_placeholder'] ); ?>"/>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="evf-deactivate-feedback-popup-form-footer">
				<a href="<?php echo esc_url( $deactivate_url ); ?>" class="skip"><?php esc_html_e( 'Skip &amp; Deactivate', 'everest-forms' ); ?></a>
				<button class="submit" type="submit"><?php esc_html_e( 'Submit &amp; Deactivate', 'everest-forms' ); ?></button>
			</div>
			<span class="consent">* <?php esc_html_e( 'By submitting this form, you will also be sending us your email address & website URL.', 'everest-forms' ); ?></span>
		</form>
	</div>
</div>
