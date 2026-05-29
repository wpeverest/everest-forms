<?php
/**
 * Lookup field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.6.7.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Lookup Class.
 */
class EVF_Field_Lookup extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Lookup', 'everest-forms' );
		$this->type   = 'lookup';
		$this->icon   = 'evf-icon evf-icon-lookup';
		$this->order  = 250;
		$this->group  = 'advanced';
		$this->is_pro = true;
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => '8hFSI5-Gf_U',
		);

		parent::__construct();
	}
}
