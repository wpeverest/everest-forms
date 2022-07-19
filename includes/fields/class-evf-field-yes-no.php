<?php
/**
 * Yes/No field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Yes_No Class.
 */
class EVF_Field_Yes_No extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Yes/No', 'everest-forms' );
		$this->type   = 'yes-no';
		$this->icon   = 'evf-icon evf-icon-yes-no';
		$this->order  = 15;
		$this->group  = 'survey';
		$this->is_pro = true;

		parent::__construct();
	}
}
