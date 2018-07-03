<?php
/**
 * City field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_City Class.
 */
class EVF_Field_City extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function init() {
		// Define field type information.
		$this->name  = esc_html__( 'City', 'everest-forms' );
		$this->type  = 'city';
		$this->icon  = 'evf-icon evf-icon-address';
		$this->order = 16;
		$this->group = 'address';
		$this->is_pro = true;
	}
}
