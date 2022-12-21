<?php
/**
 * Reset field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Reset Class.
 */
class EVF_Field_Reset extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Reset', 'everest-forms' );
		$this->type   = 'reset';
		$this->icon   = 'evf-icon evf-icon-reset';
		$this->order  = 15;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
