<?php
/**
 * Payment checkbox field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Checkbox Class.
 */
class EVF_Field_Payment_Checkbox extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Checkboxes', 'everest-forms' );
		$this->type   = 'payment-checkbox';
		$this->icon   = 'evf-icon evf-icon-checkbox';
		$this->order  = 30;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
