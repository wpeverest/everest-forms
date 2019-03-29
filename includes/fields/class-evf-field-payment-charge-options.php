<?php
/**
 * Payment Credit card field
 *
 * @package EverestForms\Fields
 * @since   1.5.0
 */
defined( 'ABSPATH' ) || exit();

/**
 * EVF_Field_Payment_Charge_Options Class.
 */
class EVF_Field_Payment_Charge_Options extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Payment Options', 'everest-forms' );
		$this->type   = 'payment-charge-options';
		$this->icon   = 'evf-icon evf-icon-payment';
		$this->order  = 50;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
