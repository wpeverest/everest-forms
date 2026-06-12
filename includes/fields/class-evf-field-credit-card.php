<?php
/**
 * Credit Card field
 *
 * @package EverestForms\Fields
 * @since   1.4.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Credit_Card Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, Stripe
 * gateways, processing) lives in Everest Forms Pro, whose class loads in place
 * of this one when the plugin is active (this file is only autoloaded when that
 * class is not already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Credit_Card extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Credit Card', 'everest-forms' );
		$this->type     = 'credit-card';
		$this->icon     = 'evf-icon evf-icon-payment';
		$this->order    = 50;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->plan     = 'personal agency themegrill-agency';
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'ermR7iHtWEc',
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

		// The required Stripe add-on supplies this field. Mark it so the locked
		// settings panel shows an "Activate Stripe" CTA when already licensed.
		$this->addon = 'everest-forms-stripe';

		parent::__construct();
	}

	/**
	 * Field preview inside the builder.
	 *
	 * Renders the same card layout the real (Stripe-active) field shows in the
	 * builder, so the locked Pro showcase is pixel-identical to the actual field
	 * — just non-interactive. The "Activate Stripe" CTA lives in the locked
	 * settings panel, not on the canvas.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$this->field_preview_option( 'label', $field );
		?>
		<div class="everest-forms-credit-card-cardnumber">
			<div class="everest-forms-card-icon">
				<svg focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 21"><g fill="none" fill-rule="evenodd"><g id="unknown" class="Icon-fill"><g id="card" transform="translate(0 2)"><path id="shape" d="M26.58 19H2.42A2.4 2.4 0 0 1 0 16.62V2.38A2.4 2.4 0 0 1 2.42 0h24.16A2.4 2.4 0 0 1 29 2.38v14.25A2.4 2.4 0 0 1 26.58 19zM10 5.83c0-.46-.35-.83-.78-.83H3.78c-.43 0-.78.37-.78.83v3.34c0 .46.35.83.78.83h5.44c.43 0 .78-.37.78-.83V5.83z" opacity=".2"></path><path id="shape" d="M25 15h-3c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1zm-6 0h-3c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1zm-6 0h-3c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1zm-6 0H4c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1z" opacity=".3"></path></g></g></g></svg>
			</div>
			<input class="card-number" type="text" placeholder="Card Number" disabled>
			<input class="card-expiration" type="text" placeholder="MM / YY" disabled>
			<input class="card-cvc" type="text" placeholder="CVC" disabled>
		</div>
		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
