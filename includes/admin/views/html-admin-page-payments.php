<?php
/**
 * Admin View: Payments upsell page (free version).
 *
 * @package EverestForms\Admin\Views
 */

defined( 'ABSPATH' ) || exit;

$upgrade_url = apply_filters(
	'everest_forms_upgrade_url',
	'https://everestforms.net/pricing/?utm_medium=evf-admin-payments&utm_source=evf-free&utm_campaign=payment-page-upsell&utm_content=Upgrade+to+Pro'
);

$icon_url    = plugins_url( 'assets/images/payment-gateway-icon.png', EVF_PLUGIN_FILE );
$stripe_url  = plugins_url( 'assets/images/stripe.png', EVF_PLUGIN_FILE );
$paypal_url  = plugins_url( 'assets/images/paypal.png', EVF_PLUGIN_FILE );
$premium_url = plugins_url( 'assets/images/evf-premium-icon.png', EVF_PLUGIN_FILE );
?>
<?php include __DIR__ . '/html-admin-header-skeleton.php'; ?>

<div class="evf-payments-page-wrap">
	<div class="evf-payments-upsell-card">

		<div class="evf-payments-icon-wrap">
			<img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="80" height="80" aria-hidden="true">
		</div>

		<h3><?php esc_html_e( 'Unlock Payments with Pro', 'everest-forms' ); ?></h3>

		<p class="evf-payments-desc">
			<?php esc_html_e( 'Connect your preferred payment gateway via its official APIs to start collecting secure payments on your forms.', 'everest-forms' ); ?>
		</p>

		<div class="evf-payments-gateways">
			<span class="evf-payments-gateway-badge evf-payments-gateway-badge--stripe">
				<img class="evf-payments-gateway-icon" src="<?php echo esc_url( $stripe_url ); ?>" alt="Stripe" width="18" height="18">
				<?php esc_html_e( 'Stripe', 'everest-forms' ); ?>
			</span>
			<span class="evf-payments-gateway-badge evf-payments-gateway-badge--paypal">
				<img class="evf-payments-gateway-icon" src="<?php echo esc_url( $paypal_url ); ?>" alt="PayPal" width="18" height="18">
				<?php esc_html_e( 'PayPal', 'everest-forms' ); ?>
			</span>
			<span class="evf-payments-gateway-badge evf-payments-gateway-badge--more">
				<?php esc_html_e( '+6 more on Pro', 'everest-forms' ); ?>
			</span>
		</div>

		<a href="<?php echo esc_url( $upgrade_url ); ?>" class="evf-payments-upsell-btn" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Upgrade to Pro', 'everest-forms' ); ?>
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14" width="14" height="14" aria-hidden="true" focusable="false"><path fill="#efefef" d="m7 1.167 3.5 9.333h-7z"></path><path fill="#fff" fill-rule="evenodd" d="M12 12.834H2v-1.667h10zm0-2.334H2l-.833-7L7 8.312 12.833 3.5z" clip-rule="evenodd"></path></svg>
		</a>

	</div>
</div>
