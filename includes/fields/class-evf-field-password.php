<?php
/**
 *  Password field.
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Password Class.
 */
class EVF_Field_Password extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Password', 'everest-forms' );
		$this->type   = 'password';
		$this->icon   = 'evf-icon evf-icon-password';
		$this->order  = 70;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
