<?php
/**
 *  Signature field.
 *
 * @package EverestForms\Fields
 * @since   1.4.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Signature Class.
 */
class EVF_Field_Signature extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Signature', 'everest-forms' );
		$this->type   = 'signature';
		$this->icon   = 'evf-icon evf-icon-signature';
		$this->order  = 100;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
