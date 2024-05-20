<?php
/**
 * Authorize.Net field
 *
 * @package EverestForms\Fields
 * @since   1.9.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Authorize_Net Class.
 */
class EVF_Field_Payment_Authorize_Net extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Authorize.Net', 'everest-forms' );
		$this->type   = 'authorize-net';
		$this->icon   = 'evf-icon evf-icon-payment';
		$this->order  = 230;
		$this->group  = 'payment';
		$this->is_pro = true;
		$this->plan   = 'personal agency themegrill-agency';
		$this->addon  = 'everest-forms-authorize-net';
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => 'a5EcKjwWD1A',
		);

		parent::__construct();
	}
}
