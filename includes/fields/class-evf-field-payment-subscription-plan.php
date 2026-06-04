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
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * processing) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
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
		$this->name     = esc_html__( 'Subscription Plan', 'everest-forms' );
		$this->type     = 'payment-subscription-plan';
		$this->icon     = 'evf-icon evf-icon-subscription-plan';
		$this->order    = 12;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => '',
		);
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( 'First Choice', 'everest-forms' ),
				'value'   => '10.00',
				'image'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( 'Second Choice', 'everest-forms' ),
				'value'   => '20.00',
				'image'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( 'Third Choice', 'everest-forms' ),
				'value'   => '30.00',
				'image'   => '',
				'default' => '',
			),
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'plan_choices',
					'choices_images',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'input_columns',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/*
	 * NOTE: field_preview() is intentionally omitted. The Pro implementation
	 * renders the choices preview, which routes through the abstract's payment
	 * choice rendering and calls evf_format_amount() — a Pro-only helper not
	 * present in the free plugin. Calling it here fatals, so the builder falls
	 * back to the synthesized preview. The settings above still render the full
	 * read-only option panel for the locked upsell.
	 */
}
