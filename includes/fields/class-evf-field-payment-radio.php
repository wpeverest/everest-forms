<?php
/**
 * Payment Radio field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'evf_sanitize_amount' ) ) {
	/**
	 * Sanitize a price amount. Builder-scope fallback for the free plugin; the
	 * real currency-aware helper ships in Everest Forms Pro. Only defined when the
	 * Pro helper is absent so the locked payment fields can render their read-only
	 * builder options without fataling.
	 *
	 * @param string $amount Amount value.
	 * @return string
	 */
	function evf_sanitize_amount( $amount ) {
		return is_numeric( $amount ) ? $amount : preg_replace( '/[^0-9.\-]/', '', (string) $amount );
	}
}

if ( ! function_exists( 'evf_format_amount' ) ) {
	/**
	 * Format a price amount. Builder-scope fallback for the free plugin; the real
	 * currency-aware helper ships in Everest Forms Pro. Only defined when the Pro
	 * helper is absent.
	 *
	 * @param mixed $amount   Amount value.
	 * @param bool  $currency Whether to prepend a currency symbol (ignored in the fallback).
	 * @return string
	 */
	function evf_format_amount( $amount, $currency = false ) {
		return number_format( (float) $amount, 2, '.', '' );
	}
}

/**
 * EVF_Field_Payment_Radio Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * exporters) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Payment_Radio extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Multiple Choice', 'everest-forms' );
		$this->type     = 'payment-multiple';
		$this->icon     = 'evf-icon evf-icon-multiple-choices';
		$this->order    = 20;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'Qr_5ZynFs_I',
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
					'choices',
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
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
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
