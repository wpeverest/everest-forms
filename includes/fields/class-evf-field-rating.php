<?php
/**
 * Rating field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Rating Class.
 */
class EVF_Field_Rating extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Rating', 'everest-forms' );
		$this->type   = 'rating';
		$this->icon   = 'evf-icon';
		$this->order  = 200;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
