<?php
/**
 * Admin View: Notice - Legacy Payment charge field.
 *
 * @package EverestForms\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated everest-forms-message">
	<a class="everest-forms-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'evf-hide-notice', 'legacy_payment_charge' ), 'everest_forms_hide_notices_nonce', '_evf_notice_nonce' ) ); ?>">
		<?php esc_html_e( 'Dismiss', 'everest-forms' ); ?>
	</a>

	<p class="main">
		<strong><?php esc_html_e( 'New:', 'everest-forms' ); ?> <?php esc_html_e( 'Conditional Logic for Payment Gateways', 'everest-forms' ); ?></strong> &#8211; <?php esc_html_e( 'More than one payment gateways can be assigned as the payment methods on the form using conditional logic.', 'everest-forms' ); ?>
	</p>
	<p>
		<?php _e( '<strong>Payment Options</strong> field is deprecated and will not continue to work since <strong>Everest Forms 1.4.9</strong>. We recommend disabling these field and setting up new credit card field for Stripe within forms as soon as possible.', 'everest-forms' ); ?>
	</p>

	<p class="submit">
		<a class="button-secondary" href="https://docs.wpeverest.com/docs/everest-forms/everest-forms-add-ons/stripe/#new">
			<?php esc_html_e( 'Learn more about the changes here!', 'everest-forms' ); ?>
		</a>
	</p>
</div>
