<?php
/**
 * Payment Gateway field
 *
 * @package EverestForms\Fields
 * @since   1.9.15
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Field_Payment_Gateway_Selector', false ) ) {
	return;
}

/**
 * EVF_Field_Payment_Gateway_Selector Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the Pro
 * upsell. The full functional implementation (front-end gateway picker, payment
 * processing) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Payment_Gateway_Selector extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Payment Gateway', 'everest-forms' );
		$this->type     = 'payment-gateway-selector';
		$this->icon     = 'evf-icon evf-icon-payment';
		$this->order    = 45;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->plan     = 'personal agency themegrill-agency';
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => '',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Field preview inside the builder.
	 *
	 * Renders a representative gateway picker (the same card grid the real
	 * Pro field shows) so the locked Pro showcase reads as the actual field —
	 * just non-interactive.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$this->field_preview_option( 'label', $field );

		$gateways = array(
			'stripe' => array(
				'label' => esc_html__( 'Stripe', 'everest-forms' ),
				'image' => 'assets/images/payment/stripe.svg',
			),
			'paypal' => array(
				'label' => esc_html__( 'PayPal', 'everest-forms' ),
				'image' => 'assets/images/payment/paypal.svg',
			),
		);
		// Inline styles keep the preview self-contained: the Pro grid stylesheet
		// (evf-pgw-grid) is not loaded in the free builder, so without these the
		// logos would render full-size and stacked. Values mirror the Pro
		// .evf-pgw-card / .evf-pgw-logo-img rules so the showcase matches the
		// real field.
		$grid_style = 'display:flex;flex-wrap:wrap;gap:12px;margin-bottom:8px;pointer-events:none;';
		$tile_style = 'display:flex;align-items:center;justify-content:center;width:28%;min-width:130px;padding:12px;border:1.5px solid #e5e7eb;border-radius:4px;background:#fff;box-sizing:border-box;';
		$img_style  = 'display:block;width:80px;max-width:100%;height:auto;object-fit:contain;';
		?>
		<div class="everest-forms-payment-gateway-selector-preview">
			<div class="evf-pgw-grid evf-pgw-grid--preview" style="<?php echo esc_attr( $grid_style ); ?>">
				<?php foreach ( $gateways as $slug => $gateway ) : ?>
					<div class="evf-pgw-card evf-pgw-logo-tile" style="<?php echo esc_attr( $tile_style ); ?>">
						<img src="<?php echo esc_url( plugins_url( $gateway['image'], EVF_PLUGIN_FILE ) ); ?>" alt="<?php echo esc_attr( $gateway['label'] ); ?>" class="evf-pgw-logo-img" style="<?php echo esc_attr( $img_style ); ?>" />
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
