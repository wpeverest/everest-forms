<?php
/**
 * Payment Subtotal field
 *
 * @package EverestForms\Fields
 * @since   1.9.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Subtotal Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * processing) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Payment_Subtotal extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Subtotal', 'everest-forms' );
		$this->type     = 'payment-subtotal';
		$this->icon     = 'evf-icon evf-icon-subtotal';
		$this->order    = 220;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'PpRGYqBTeoI',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'map_field',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Mapping the payment field with quantity.
	 *
	 * @param array $field Field data object.
	 */
	public function map_field( $field ) {
		$form_id   = isset( $_GET['form_id'] ) ? wp_unslash( absint( $_GET['form_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';
		$options   = array();

		if ( isset( $form_data['form_fields'] ) && is_array( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $id => $form_field ) {
				if ( isset( $form_field['enable_payment_slider'] ) && '1' === $form_field['enable_payment_slider'] ) {
					if ( in_array( $form_field['type'], array( 'payment-single', 'payment-multiple', 'payment-checkbox', 'range-slider' ), true ) ) {
						$options[ $form_field['id'] ] = $form_field['label'];
					}
				} else {
					if ( in_array( $form_field['type'], array( 'payment-single', 'payment-multiple', 'payment-checkbox' ), true ) ) {
						$options[ $form_field['id'] ] = $form_field['label'];
					}
				}
			}
		}

		$options = evf_parse_args( $options, array( '' => __( '---Select a Field---', 'everest-forms' ) ) );

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => esc_html__( 'Calculate with this field', 'everest-forms' ),
				'tooltip' => esc_html__( 'Calculate subtoal value for this selected payment field.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => ! empty( $field['map_field'] ) ? esc_attr( $field['map_field'] ) : '',
				'options' => $options,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'map_field',
				'content' => $lbl . $fld,
			)
		);
	}

	/*
	 * NOTE: field_preview() is intentionally omitted. The Pro implementation
	 * renders the live subtotal via evf_format_amount(), which is a Pro-only
	 * helper not present in the free plugin. Calling it here fatals, so the
	 * builder falls back to the synthesized preview. The settings and the
	 * map_field option above still render the full read-only option panel.
	 */
}
