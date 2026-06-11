<?php
/**
 * Payment Gateway (Pro)
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
