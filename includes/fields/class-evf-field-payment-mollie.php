<?php
/**
 * Mollie field
 *
 * @package EverestForms\Fields
 * @since   1.9.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Mollie Class.
 */
class EVF_Field_Payment_Mollie extends \EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Mollie', 'everest-forms' );
		$this->type   = 'mollie';
		$this->icon   = 'evf-icon evf-icon-payment';
		$this->order  = 232;
		$this->group  = 'payment';
		$this->is_pro = false;
		$this->plan   = 'personal agency themegrill-agency';
		$this->addon  = 'everest-forms-mollie';
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => '',
		);

		parent::__construct();
	}
}
