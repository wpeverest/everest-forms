<?php
/**
 * Payment subscription plan field
 *
 * @since   3.0.9
 *
 * @package EverestForms\Fields
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Subscription_Plan Class.
 *
 * @since 3.0.9
 */
class EVF_Field_Payment_Subscription_Plan extends EVF_Form_Fields {

	/**
	 * Constructor.
	 *
	 * @since 3.0.9
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Subscription Plan', 'everest-forms' );
		$this->type   = 'payment-subscription-plan';
		$this->icon   = 'evf-icon evf-icon-subscription-plan';
		$this->order  = 12;
		$this->group  = 'payment';
		$this->is_pro = true;
		$this->links  = array(
			'image_id' => '',
			'vedio_id' => '',
		);

		parent::__construct();
	}
}
