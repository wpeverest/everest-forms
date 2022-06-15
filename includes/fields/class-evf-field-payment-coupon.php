<?php
/**
 * Coupon field.
 *
 * @package EverestForms\Fields
 * @since   1.8.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Coupon Class.
 */
class EVF_Field_Payment_Coupon extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Coupon', 'everest-forms' );
		$this->type   = 'payment-coupon';
		$this->icon   = 'evf-icon evf-icon-coupon';
		$this->order  = 17;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
