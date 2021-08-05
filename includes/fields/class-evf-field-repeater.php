<?php
/**
 * Repeater field
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Repeater Class.
 */
class EVF_Field_Repeater extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Repeater Fields', 'everest-forms' );
		$this->type   = 'repeater-fields';
		$this->icon   = 'evf-icon evf-icon-repeater';
		$this->order  = 190;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
