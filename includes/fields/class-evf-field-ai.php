<?php
/**
 * AI  field
 *
 * @package EverestForms\Fields
 * @since   1.9.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_AI  Class.
 */
class EVF_Field_AI extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'AI', 'everest-forms' );
		$this->type   = 'ai';
		$this->icon   = 'evf-icon evf-icon-ai';
		$this->order  = 240;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
