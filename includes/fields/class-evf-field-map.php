<?php
/**
 * Map field.
 *
 * @package EverestForms\Fields
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Map Class.
 */
class EVF_Field_Map extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Map', 'everest-forms' );
		$this->type   = 'map';
		$this->icon   = 'evf-icon evf-icon-geo-map';
		$this->order  = 130;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
