<?php
/**
 * Zip field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Zip Class.
 */
class EVF_Field_Zip extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Zip', 'everest-forms' );
		$this->type   = 'zip';
		$this->icon   = 'evf-icon evf-icon-zip-code';
		$this->order  = 40;
		$this->group  = 'address';
		$this->is_pro = true;

		parent::__construct();
	}
}
