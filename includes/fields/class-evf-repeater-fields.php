<?php
/**
 * Repeater field
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Repeater_Fields Class.
 */
class EVF_Repeater_Fields extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Repeater Fields', 'everest-forms' );
		$this->type   = 'repeater-fields';
		$this->icon   = 'evf-icon evf-icon-repeater-fields';
		$this->order  = 16500;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
