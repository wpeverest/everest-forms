<?php
/**
 * Section Title field.
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Title Class.
 */
class EVF_Field_Title extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = __( 'Section Title', 'everest-forms' );
		$this->type   = 'title';
		$this->icon   = 'evf-icon evf-icon-section-divider';
		$this->order  = 90;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
