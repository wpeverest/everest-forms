<?php
/**
 * Payment subscription plan field
 *
 * @since   3.0.9
 *
 * @package EverestForms\Fields
 */

defined( 'ABSPATH' ) || exit;

// Currency-aware amount helper so the choices preview matches the real Pro field.
require_once __DIR__ . '/payment-amount-helpers.php';

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

	/**
	 * Field preview inside the builder (matches the Pro Subscription Plan field).
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Choices.
		$this->field_preview_option( 'choices', $field );

		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
