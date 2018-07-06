<?php
/**
 * Textarea field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Textarea class.
 */
class EVF_Field_Textarea extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Paragraph Text', 'everest-forms' );
		$this->type     = 'textarea';
		$this->icon     = 'evf-icon evf-icon-paragraph';
		$this->order    = 40;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options' => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'size',
					'placeholder',
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
	 * @since      1.0.0
	 *
	 * @param array $field
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<textarea placeholder="' . $placeholder . '" class="widefat" disabled></textarea>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since      1.0.0
	 *
	 * @param array $field
	 * @param array $deprecated
	 * @param array $form_data
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$primary = $field['properties']['inputs']['primary'];
		$value   = '';

		if ( ! empty( $primary['attr']['value'] ) ) {
			$value = $primary['attr']['value'];
			unset( $primary['attr']['value'] );

			$value = evf_sanitize_textarea_field( $value );
		}

		// Primary field.
		printf(
			'<textarea %s %s>%s</textarea>',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			$primary['required'],
			$value
		);
	}
}

