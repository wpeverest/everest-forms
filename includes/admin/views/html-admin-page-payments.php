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
?>
<?php include __DIR__ . '/html-admin-header-skeleton.php'; ?>

<div class="evf-payments-page-wrap">
	<div class="evf-payments-upsell-card">

		<div class="evf-payments-icon-wrap" aria-hidden="true">
			<svg width="44" height="36" viewBox="0 0 44 36" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect width="33" height="22" rx="4" fill="#f59e0b"/>
				<rect y="5" width="33" height="7" fill="#d97706"/>
				<rect x="4" y="15" width="12" height="4" rx="2" fill="rgba(255,255,255,0.5)"/>
				<path d="M37 4 Q40.5 8 37 12" stroke="#7c3aed" stroke-width="2" fill="none" stroke-linecap="round"/>
				<path d="M40 1.5 Q45 8 40 14.5" stroke="#7c3aed" stroke-width="2" fill="none" stroke-linecap="round"/>
			</svg>
		</div>

		<h3><?php esc_html_e( 'Unlock Payments with Pro', 'everest-forms' ); ?></h3>

		<p class="evf-payments-desc">
			<?php esc_html_e( 'Connect your preferred payment gateway via its official APIs to start collecting secure payments on your forms.', 'everest-forms' ); ?>
		</p>

		<div class="evf-payments-gateways">
			<span class="evf-payments-gateway-badge evf-payments-gateway-badge--stripe">
				<i class="evf-payments-gateway-icon" style="background:#635BFF;" aria-hidden="true">S</i>
				<?php esc_html_e( 'Stripe', 'everest-forms' ); ?>
			</span>
			<span class="evf-payments-gateway-badge evf-payments-gateway-badge--paypal">
				<i class="evf-payments-gateway-icon" style="background:#003087;" aria-hidden="true">P</i>
				<?php esc_html_e( 'PayPal', 'everest-forms' ); ?>
			</span>
			<span class="evf-payments-gateway-badge evf-payments-gateway-badge--more">
				<?php esc_html_e( '+6 more on Pro', 'everest-forms' ); ?>
			</span>
		</div>

		<a href="<?php echo esc_url( $upgrade_url ); ?>" class="evf-payments-upsell-btn" target="_blank" rel="noopener noreferrer">
			<svg width="15" height="13" viewBox="0 0 15 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
				<path d="M1 11.5H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				<path d="M1 9.5L3 3.5L7.5 7L11 1.5L14 9.5H1Z" fill="currentColor"/>
			</svg>
			<?php esc_html_e( 'Upgrade to Pro', 'everest-forms' ); ?>
		</a>

	</div>
</div>
