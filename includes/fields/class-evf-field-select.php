<?php
/**
 * Select field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Select class.
 */
class EVF_Field_Select extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Dropdown', 'everest-forms' );
		$this->type     = 'select';
		$this->icon     = 'evf-icon evf-icon-dropdown';
		$this->order    = 50;
		$this->group    = 'general';
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( 'Option 1', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( 'Option 2', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( 'Option 3', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'choices',
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
	 * @param array $field
	 */
	public function field_preview( $field ) {
		$values      = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Field select element.
		echo '<select class="widefat" disabled>';

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
	 * @param array $field
	 * @param array $field_atts
	 * @param array $form_data
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Setup and sanitize the necessary data.
		$primary           = $field['properties']['inputs']['primary'];
		$field             = apply_filters( 'everest_forms_select_field_display', $field, $field_atts, $form_data );
		$field_placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
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
		printf(
			"<select type='select' %s %s>",
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$primary['required'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
	 * @param int    $field_id
	 * @param array  $field_submit
	 * @param array  $form_data
	 * @param string $meta_key
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field     = $form_data['form_fields'][ $field_id ];
		$name      = sanitize_text_field( $field['label'] );
		$value_raw = sanitize_text_field( $field_submit );
		$value     = '';

		$data = array(
			'name'      => $name,
			'value'     => '',
			'value_raw' => $value_raw,
			'id'        => $field_id,
			'type'      => $this->type,
			'meta_key'  => $meta_key,
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

		// Push field details to be saved.
		EVF()->task->form_fields[ $field_id ] = $data;
	}
}

