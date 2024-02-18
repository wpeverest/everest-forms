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
		$this->name   = esc_html__( 'Quantity', 'everest-forms' );
		$this->type   = 'payment-quantity';
		$this->icon   = 'evf-icon evf-icon-single-item';
		$this->order  = 40;
		$this->group  = 'payment';
		$this->is_pro = true;
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => 'JLRES75WeqM',
		);

		parent::__construct();
	}
}
