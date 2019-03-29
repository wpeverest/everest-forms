<?php
/**
 *  Signature field.
 *
 * @package EverestForms\Fields
 * @since   1.5.0
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
		$this->name   = esc_html__( 'e-Signature', 'everest-forms' );
		$this->type   = 'signature';
		$this->icon   = 'evf-icon evf-icon-e-signature';
		$this->order  = 90;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
