<?php
/**
 * Rating field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Thumb_Rating Class.
 */
class EVF_Field_Thumb_Rating extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Thumb Rating', 'everest-forms' );
		$this->type   = 'thumb-rating';
		$this->icon   = 'evf-icon evf-icon-thumb-rating';
		$this->order  = 15;
		$this->group  = 'survey';
		$this->is_pro = true;

		parent::__construct();
	}
}
