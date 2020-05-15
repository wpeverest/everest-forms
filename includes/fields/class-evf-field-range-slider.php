<?php
/**
 * Range Slider field
 *
 * @package EverestForms\Fields
 * @since   1.6.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Range_Slider Class.
 */
class EVF_Field_Range_Slider extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Range Slider', 'everest-forms' );
		$this->type   = 'range-slider';
		$this->icon   = 'evf-icon evf-icon-range-slider';
		$this->order  = 140;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
