<?php
/**
 * Text field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Text class.
 */
class EVF_Field_Text extends EVF_Form_Fields {

	/**
	 * Primary class constructor.
	 *
	 * @since      1.0.0
	 */
	public function init() {

		// Define field type information.
		$this->name  = __( 'Single Line Text', 'everest-forms' );
		$this->type  = 'text';
		$this->icon  = 'evf-icon evf-icon-text';
		$this->order = 3;

		// Define additional field properties.
		add_filter( 'evf_field_properties_text', array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Define additional field properties.
	 *
	 * @param array $properties
	 * @param array $field
	 * @param array $form_data
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {
		// Input primary: Detect custom input mask.
		if ( ! empty( $field['input_mask'] ) ) {

			// Add class that will trigger custom mask.
			$properties['inputs']['primary']['class'][] = 'evf-masked-input';

			if ( false !== strpos( $field['input_mask'], 'alias:' ) ) {
				$mask = str_replace( 'alias:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-alias'] = $mask;
			} elseif ( false !== strpos( $field['input_mask'], 'regex:' ) ) {
				$mask = str_replace( 'regex:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-regex'] = $mask;
			} elseif ( false !== strpos( $field['input_mask'], 'date:' ) ) {
				$mask = str_replace( 'date:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-alias']       = 'datetime';
				$properties['inputs']['primary']['data']['inputmask-inputformat'] = $mask;
			} else {
				$properties['inputs']['primary']['data']['inputmask-mask'] = $field['input_mask'];
			}
		}

		return $properties;
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @param array $field
	 */
	public function field_options( $field ) {

		// -------------------------------------------------------------------//
		// Basic field options.
		// -------------------------------------------------------------------//

  		$args = array(
			'markup' => 'open',
		);
		$this->field_option( 'basic-options', $field, $args );

		// Label.
		$this->field_option( 'label', $field );

		// Meta.
		$this->field_option( 'meta', $field );

		// Description.
		$this->field_option( 'description', $field );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Options close markup.
		$args = array(
			'markup' => 'close',
		);
		$this->field_option( 'basic-options', $field, $args );

		// --------------------------------------------------------------------//
		// Advanced field options.
		// --------------------------------------------------------------------//

		// Options open markup.
		$args = array(
			'markup' => 'open',
		);
		$this->field_option( 'advanced-options', $field, $args );

		// Placeholder.
		$this->field_option( 'placeholder', $field );

		// Hide label.
		$this->field_option( 'label_hide', $field );

		// Default value.
		$this->field_option( 'default_value', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'          => 'input_mask',
				'value'         => esc_html__( 'Input Mask', 'everest-forms' ),
				'tooltip'       => esc_html__( 'Enter your custom input mask.', 'everest-forms' ),
				'after_tooltip' => '<a href="https://wpeverest.com/how-to-use-custom-input-masks/" class="after-label-description" target="_blank" rel="noopener noreferrer">' . esc_html__( 'See Examples & Docs', 'everest-forms' ) . '</a>',
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'input_mask',
				'value' => ! empty( $field['input_mask'] ) ? esc_attr( $field['input_mask'] ) : '',
			),
			false
		);
		$this->field_element( 'row', $field, array(
			'slug'    => 'input_mask',
			'content' => $lbl . $fld,
		) );

		// Options close markup.
		$args = array(
			'markup' => 'close',
		);
		$this->field_option( 'advanced-options', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @param array $field
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="text" placeholder="' . $placeholder . '" class="primary-input" disabled>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @param array $field
	 * @param array $deprecated
	 * @param array $form_data
	 */
	public function field_display( $field, $deprecated, $form_data ) {

 		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Primary field.
		printf( '<input type="text" %s %s>',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			$primary['required']
		);
	}
}

new EVF_Field_Text();
