<?php
/**
 * Phone field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Phone Class.
 */
class EVF_Field_Phone extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Phone', 'everest-forms' );
		$this->type   = 'phone';
		$this->icon   = 'evf-icon evf-icon-phone';
		$this->order  = 60;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
