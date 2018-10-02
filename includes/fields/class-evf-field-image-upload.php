<?php
/**
 * Image upload field
 *
 * @package EverestForms\Fields
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Image_Upload Class.
 */
class EVF_Field_Image_Upload extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = __( 'Image Upload', 'everest-forms' );
		$this->type   = 'image-upload';
		$this->icon   = 'evf-icon evf-icon-img-upload';
		$this->order  = 30;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
