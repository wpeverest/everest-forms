<?php
/**
 * Credit Card field
 *
 * @package EverestForms\Fields
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Credit_Card Class.
 */
class EVF_Field_Credit_Card extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Credit Card', 'everest-forms' );
		$this->type   = 'credit-card';
		$this->icon   = 'evf-icon evf-icon-payment';
		$this->order  = 60;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
