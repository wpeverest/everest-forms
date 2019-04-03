<?php
/**
 * Address field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Address Class.
 */
class EVF_Field_Address extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Address', 'everest-forms' );
		$this->type   = 'address';
		$this->icon   = 'evf-icon evf-icon-map-marker';
		$this->order  = 110;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
