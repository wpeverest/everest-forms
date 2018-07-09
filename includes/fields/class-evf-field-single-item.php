<?php
/**
 * Single Item field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Single_Item Class.
 */
class EVF_Field_Single_Item extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Single Item', 'everest-forms' );
		$this->type   = 'payment-single';
		$this->icon   = 'evf-icon evf-icon-single-item';
		$this->order  = 10;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
