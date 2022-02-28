<?php
/**
 * WysiWyg field
 *
 * @package EverestForms\Fields
 * @since   1.8.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Wysiwyg Class.
 */
class EVF_Field_Wysiwyg extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'WYSIWYG', 'everest-forms' );
		$this->type   = 'wysiwyg';
		$this->icon   = 'evf-icon evf-icon-wysiwyg';
		$this->order  = 170;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
