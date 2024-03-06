<?php
/**
 * Privacy Policy field
 *
 * @package EverestForms\Fields
 * @since   1.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Privacy_Policy Class.
 */
class EVF_Field_Privacy_Policy extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Privacy Policy', 'everest-forms' );
		$this->type   = 'privacy-policy';
		$this->icon   = 'evf-icon evf-icon-privacy-policy';
		$this->order  = 150;
		$this->group  = 'advanced';
		$this->is_pro = true;
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => 'eiPAWx5IDKU',
		);

		parent::__construct();
	}
}
