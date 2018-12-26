<?php
/**
 * Likert field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Likert Class.
 */
class EVF_Field_Likert extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Likert', 'everest-forms' );
		$this->type   = 'likert';
		$this->icon   = 'evf-icon evf-icon-map-marker';
		$this->order  = 210;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
