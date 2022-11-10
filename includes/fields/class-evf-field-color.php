<?php
/**
 * Color field
 *
 * @package EverestForms\Fields
 * @since   1.9.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Color Class.
 */
class EVF_Field_Color extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Color', 'everest-forms' );
		$this->type   = 'color';
		$this->icon   = 'evf-icon evf-icon-color';
		$this->order  = 210;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
