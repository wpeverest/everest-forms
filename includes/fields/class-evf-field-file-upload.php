<?php
/**
 * File upload field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_File_Upload Class.
 */
class EVF_Field_File_Upload extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'File Upload', 'everest-forms' );
		$this->type   = 'file-upload';
		$this->icon   = 'evf-icon evf-icon-file-upload';
		$this->order  = 40;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
