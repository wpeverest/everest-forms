<?php
/**
 * Admin View: Payments upsell page (free version).
 *
 * @package EverestForms\Admin\Views
 */

defined( 'ABSPATH' ) || exit;

$upgrade_url = apply_filters(
	'everest_forms_upgrade_url',
	'https://everestforms.net/pricing/?utm_medium=evf-admin-payments&utm_source=evf-free&utm_campaign=payment-page-upsell&utm_content=Get+Started'
);

$docs_url = 'https://docs.everestforms.net/docs/payment-field/?utm_medium=evf-admin-payments&utm_source=evf-free&utm_campaign=payment-page-upsell&utm_content=Comprehensive+Guide';
?>
<?php include __DIR__ . '/html-admin-header-skeleton.php'; ?>

<div class="evf-payments-page-wrap">
	<div class="evf-payments-upsell-card">

		<div class="evf-payment-upsell-header">
			<span class="evf-payment-wave">&#x1F44B;</span>
			<h2><?php esc_html_e( 'Hi there!', 'everest-forms' ); ?></h2>
		</div>

		<h3><?php esc_html_e( 'Ready to start collecting payments from your customers?', 'everest-forms' ); ?></h3>

		<p class="evf-payments-desc">
			<?php
			printf(
				/* translators: 1: Opening strong tag, 2: Closing strong tag, 3: Opening anchor tag, 4: Closing anchor tag */
				esc_html__( 'First you need to set up a payment gateway. Everest Forms integrates with %1$sStripe%2$s and %1$sPayPal%2$s via their official APIs to enable secure payment collection on your forms. To use more payment gateways, please consider %3$supgrading to Pro%4$s.', 'everest-forms' ),
				'<strong>',
				'</strong>',
				'<a href="' . esc_url( $upgrade_url ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			?>
		</p>

		<div class="evf-payments-upsell-illustration">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 520 340" fill="none" role="img" aria-hidden="true" focusable="false">
				<!-- Credit card (left, rotated) -->
				<g transform="rotate(-10, 95, 165)">
					<rect x="22" y="125" width="115" height="72" rx="11" fill="#f59e0b"/>
					<rect x="22" y="125" width="115" height="26" rx="11" fill="#d97706"/>
					<rect x="22" y="138" width="115" height="13" fill="#d97706"/>
					<circle cx="40" cy="145" r="7" fill="#fde68a" opacity="0.9"/>
					<circle cx="52" cy="145" r="7" fill="#fbbf24" opacity="0.65"/>
					<rect x="36" y="168" width="44" height="7" rx="3.5" fill="rgba(255,255,255,0.5)"/>
					<rect x="36" y="181" width="66" height="5" rx="2.5" fill="rgba(255,255,255,0.35)"/>
				</g>

				<!-- Monitor body -->
				<rect x="128" y="28" width="268" height="192" rx="14" fill="#5317aa"/>
				<rect x="141" y="41" width="242" height="167" rx="8" fill="#f5f0ff"/>

				<!-- Screen chrome dots -->
				<circle cx="158" cy="57" r="5" fill="#ef4444" opacity="0.7"/>
				<circle cx="172" cy="57" r="5" fill="#f59e0b" opacity="0.7"/>
				<circle cx="186" cy="57" r="5" fill="#10b981" opacity="0.7"/>

				<!-- Form: card number label -->
				<rect x="156" y="78" width="70" height="9" rx="4" fill="#c4b5fd"/>
				<!-- Form: card number input -->
				<rect x="156" y="92" width="214" height="26" rx="5" fill="#fff" stroke="#ddd6fe" stroke-width="1.5"/>
				<rect x="164" y="102" width="120" height="6" rx="3" fill="#ede9fe"/>

				<!-- Form: expiry label -->
				<rect x="156" y="128" width="42" height="9" rx="4" fill="#c4b5fd"/>
				<!-- Form: expiry input -->
				<rect x="156" y="142" width="96" height="26" rx="5" fill="#fff" stroke="#ddd6fe" stroke-width="1.5"/>
				<rect x="164" y="152" width="50" height="6" rx="3" fill="#ede9fe"/>

				<!-- Form: CVC label -->
				<rect x="264" y="128" width="36" height="9" rx="4" fill="#c4b5fd"/>
				<!-- Form: CVC input -->
				<rect x="264" y="142" width="106" height="26" rx="5" fill="#fff" stroke="#ddd6fe" stroke-width="1.5"/>
				<rect x="272" y="152" width="40" height="6" rx="3" fill="#ede9fe"/>

				<!-- Pay button -->
				<rect x="156" y="180" width="214" height="22" rx="6" fill="#7545bb"/>
				<rect x="202" y="187" width="122" height="8" rx="3" fill="rgba(255,255,255,0.85)"/>

				<!-- Monitor stand -->
				<rect x="240" y="220" width="44" height="20" rx="3" fill="#c4b5fd"/>
				<rect x="218" y="238" width="88" height="9" rx="4.5" fill="#ddd6fe"/>

				<!-- Shopping bag 1 (back, right) -->
				<g transform="translate(388, 82)">
					<path d="M5 34 L55 34 L62 100 L-2 100 Z" fill="#ec4899" opacity="0.85"/>
					<path d="M13 34 Q13 12 30 12 Q47 12 47 34" stroke="#be185d" stroke-width="4" fill="none" stroke-linecap="round"/>
					<rect x="10" y="55" width="38" height="3.5" rx="1.75" fill="rgba(255,255,255,0.45)"/>
					<rect x="10" y="67" width="30" height="3.5" rx="1.75" fill="rgba(255,255,255,0.45)"/>
					<rect x="10" y="79" width="34" height="3.5" rx="1.75" fill="rgba(255,255,255,0.45)"/>
				</g>

				<!-- Shopping bag 2 (front, right) -->
				<g transform="translate(406, 120)">
					<path d="M4 28 L44 28 L50 80 L-2 80 Z" fill="#f472b6"/>
					<path d="M10 28 Q10 10 24 10 Q38 10 38 28" stroke="#db2777" stroke-width="3.5" fill="none" stroke-linecap="round"/>
					<rect x="7" y="46" width="30" height="3" rx="1.5" fill="rgba(255,255,255,0.5)"/>
					<rect x="7" y="57" width="22" height="3" rx="1.5" fill="rgba(255,255,255,0.5)"/>
				</g>

				<!-- Percentage badge -->
				<circle cx="444" cy="58" r="32" fill="#10b981"/>
				<circle cx="444" cy="58" r="28" fill="#059669"/>
				<text x="444" y="66" text-anchor="middle" fill="white" font-size="24" font-weight="700" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif">%</text>
			</svg>
		</div>

		<a href="<?php echo esc_url( $upgrade_url ); ?>" class="evf-payments-upsell-btn" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Get Started', 'everest-forms' ); ?>
		</a>

		<p class="evf-payments-upsell-help">
			<?php
			printf(
				/* translators: 1: Opening anchor tag, 2: Closing anchor tag */
				esc_html__( 'Need some help? Check out our %1$scomprehensive guide.%2$s', 'everest-forms' ),
				'<a href="' . esc_url( $docs_url ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			?>
		</p>

	</div>
</div>
