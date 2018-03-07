<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Select
 *
 * @package    EverestForms
 * @author     WPEverest
 * @since      1.0.0
 */
class EVF_Field_Select extends EVF_Form_Fields {

	/**
	 * Primary class constructor.
	 *
	 * @since      1.0.0
	 */
	public function init() {

		// Define field type information
		$this->name     = __( 'Select', 'everest-forms' );
		$this->type     = 'select';
		$this->icon     = 'evf-icon evf-icon-dropdown';
		$this->order    = 17;
		$this->group = 'advanced';
		$this->defaults = array(
			1 => array(
				'label'   => __( 'Option 1', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => __( 'Option 2', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
		);
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since      1.0.0
	 *
	 * @param array $field
	 */
	public function field_options( $field ) {

		// --------------------------------------------------------------------//
		// Basic field options.
		// --------------------------------------------------------------------//

		// Options open markup.
		$this->field_option( 'basic-options', $field, array(
			'markup' => 'open',
		) );

		// Label.
		$this->field_option( 'label', $field );
		
		// Meta.
		$this->field_option( 'meta', $field );
		
		// Choices.
		$this->field_option( 'choices', $field );

		// Description.
		$this->field_option( 'description', $field );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Options close markup.
		$this->field_option( 'basic-options', $field, array(
			'markup' => 'close',
		) );

		// --------------------------------------------------------------------//
		// Advanced field options.
		// --------------------------------------------------------------------//

		// Options open markup.
		$this->field_option( 'advanced-options', $field, array(
			'markup' => 'open',
		) );


		// Size.
		$this->field_option( 'size', $field );

		// Placeholder.
		$this->field_option( 'placeholder', $field );

		// Hide label.
		$this->field_option( 'label_hide', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Options close markup.
		$this->field_option( 'advanced-options', $field, array(
			'markup' => 'close',
		) );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since      1.0.0
	 *
	 * @param array $field
	 */
	public function field_preview( $field ) {

		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$values      = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;

		// Label.
		$this->field_preview_option( 'label', $field );

		// Field select element.
		echo '<select class="primary-input" disabled>';

		// Optional placeholder.
		if ( ! empty( $placeholder ) ) {
			printf( '<option value="" class="placeholder">%s</option>', $placeholder );
		}
		// Notify if currently empty.
		if ( empty( $values ) ) {
			$values = array(
				'label' => __( '(empty)', 'everest-forms' ),
			);
		}

		// Build the select options (even though user can only see 1st option).
		foreach ( $values as $key => $value ) {

			$default  = isset( $value['default'] ) ? $value['default'] : '';
			$selected = ! empty( $placeholder ) ? '' : selected( '1', $default, false );

			printf( '<option %s>%s</option>', $selected, $value['label'] );
		}

		echo '</select>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since      1.0.0
	 *
	 * @param array $field
	 * @param array $field_atts
	 * @param array $form_data
	 */
	public function field_display( $field, $field_atts, $form_data ) {

		// Setup and sanitize the necessary data.
		$field             = apply_filters( 'everest_forms_select_field_display', $field, $field_atts, $form_data );
		$field_placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$field_required    = ! empty( $field['required'] ) ? ' required' : '';
		$field_class       = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id          = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_data        = '';
		$choices           = $field['choices'];
		$has_default       = false;

		if ( ! empty( $field_atts['input_data'] ) ) {
			foreach ( $field_atts['input_data'] as $key => $val ) {
				$field_data .= ' data-' . $key . '="' . $val . '"';
			}
		}

		// Check to see if any of the options have selected by default.
		foreach ( $choices as $choice ) {
			if ( isset( $choice['default'] ) ) {
				$has_default = true;
				break;
			}
		}

		// Primary select field.
		printf( '<select name="everest_forms[form_fields][%s]" id="%s" class="%s" %s %s>',
			$field['id'],
			$field_id,
			$field_class,
			$field_required,
			$field_data
		);

		// Optional placeholder.
		if ( ! empty( $field_placeholder ) ) {
			printf( '<option value="" class="placeholder" disabled %s>%s</option>', selected( false, $has_default, false ), $field_placeholder );
		}
		// Build the select options.
		foreach ( $choices as $key => $choice ) {

			$selected = isset( $choice['default'] ) && empty( $field_placeholder ) ? '1' : '0';
			$val      = isset( $field['show_values'] ) ? esc_attr( $choice['value'] ) : esc_attr( $choice['label'] );

			printf( '<option value="%s" %s>%s</option>', $val, selected( '1', $selected, false ), $choice['label'] );
		}

		echo '</select>';
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since      1.0.0
	 *
	 * @param int    $field_id
	 * @param string $field_submit
	 * @param array  $form_data
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$field     = $form_data['form_fields'][ $field_id ];
		$name      = sanitize_text_field( $field['label'] );
		$value_raw = sanitize_text_field( $field_submit );
		$value     = '';

		$data = array(
			'name'      => $name,
			'value'     => '',
			'value_raw' => $value_raw,
			'id'        => absint( $field_id ),
			'type'      => $this->type,
		);


		// Normal processing, dynamic population is off.

		// If show_values is true, that means values posted are the raw values
		// and not the labels. So we need to get the label values.
		if ( ! empty( $field['show_values'] ) && '1' == $field['show_values'] ) {

			foreach ( $field['choices'] as $choice ) {
				if ( $choice['value'] === $field_submit ) {
					$value = $choice['label'];
					break;
				}
			}

			$data['value'] = sanitize_text_field( $value );

		} else {
			$data['value'] = $value_raw;
		}
	}
}

new EVF_Field_Select;
