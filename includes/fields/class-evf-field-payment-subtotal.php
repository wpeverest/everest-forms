<?php
/**
 * Payment Subtotal field
 *
 * @package EverestForms\Fields
 * @since   1.9.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Subtotal Class.
 */
class EVF_Field_Payment_Subtotal extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Subtotal', 'everest-forms' );
		$this->type   = 'payment-subtotal';
		$this->icon   = 'evf-icon evf-icon-subtotal';
		$this->order  = 220;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
