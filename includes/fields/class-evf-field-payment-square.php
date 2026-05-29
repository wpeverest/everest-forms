<?php
/**
 * EVF_Field_Payment_Square field
 *
 * @package EverestForms\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Square Class.
 */
class EVF_Field_Payment_Square extends \EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->name   = esc_html__( 'Square', 'everest-forms' );
		$this->type   = 'square-payment';
		$this->icon   = 'evf-icon evf-icon-payment';
		$this->order  = 231;
		$this->group  = 'payment';
		$this->is_pro = true;
		$this->plan   = 'personal agency themegrill-agency';
		$this->addon  = 'everest-forms-square';
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => 'a5EcKjwWD1A',
		);

		parent::__construct();
	}
}
