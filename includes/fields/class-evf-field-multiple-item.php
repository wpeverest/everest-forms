<?php
/**
 * Multiple Item field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Multiple_Item Class.
 */
class EVF_Field_Multiple_Item extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Multiple Items', 'everest-forms' );
		$this->type   = 'payment-multiple';
		$this->icon   = 'evf-icon evf-icon-multiple-choices';
		$this->order  = 20;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}
