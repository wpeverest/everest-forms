<?php
/**
 * Payment Quantity field
 *
 * @package EverestForms\Fields
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Quantity Class.
 */
class EVF_Field_Payment_Quantity extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Total', 'everest-forms' );
		$this->type   = 'payment-total';
		$this->icon   = 'evf-icon evf-icon-total';
		$this->order  = 40;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
