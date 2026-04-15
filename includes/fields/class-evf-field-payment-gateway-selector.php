<?php
/**
 * Payment Gateway / Payment Method field (Pro)
 *
 * Stub in Free: visible in the builder as a locked field; full behavior requires Pro.
 *
 * @package EverestForms\Fields
 * @since   1.9.15
 */

defined( 'ABSPATH' ) || exit;

// Pro loads this class from the Pro plugin before fields init; avoid redeclaration.
if ( class_exists( 'EVF_Field_Payment_Gateway_Selector', false ) ) {
	return;
}

/**
 * EVF_Field_Payment_Gateway_Selector Class.
 */
class EVF_Field_Payment_Gateway_Selector extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Payment Gateway', 'everest-forms' );
		$this->type   = 'payment-gateway-selector';
		$this->icon   = 'evf-icon evf-icon-payment';
		$this->order  = 45;
		$this->group  = 'payment';
		$this->is_pro = true;
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => '',
		);

		parent::__construct();
	}
}
