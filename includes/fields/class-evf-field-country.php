<?php
/**
 * Country field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Country Class.
 */
class EVF_Field_Country extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function init() {
		// Define field type information.
		$this->name  = esc_html__( 'Country', 'everest-forms' );
		$this->type  = 'country';
		$this->icon  = 'evf-icon evf-icon-address';
		$this->order = 17;
		$this->group = 'address';
		$this->is_pro = true;
	}
}
