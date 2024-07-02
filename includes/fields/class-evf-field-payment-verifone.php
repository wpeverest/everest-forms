<?php
/**
 * Verifone field
 *
 * @package EverestForms\Fields
 * @since   1.9.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Verifone_Net Class.
 */
class EVF_Field_Payment_Verifone extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( '(Verifone', 'everest-forms' );
		$this->type   = 'verifone';
		$this->icon   = 'evf-icon evf-icon-payment';
		$this->order  = 232;
		$this->group  = 'payment';
		$this->is_pro = true;
		$this->plan   = 'personal agency themegrill-agency';
		$this->addon  = 'everest-forms-verifone';
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => '',
		);

		parent::__construct();
	}
}
