<?php
/**
 * Progress field
 *
 * @package EverestForms\Fields
 * @since   1.9.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Progress Class.
 */
class EVF_Field_Progress extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Progress', 'everest-forms' );
		$this->type   = 'progress';
		$this->icon   = 'evf-icon evf-icon-progress';
		$this->order  = 200;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
