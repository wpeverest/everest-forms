<?php
/**
 * Select Dropdown field.
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
					'required_field_message',
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
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Choices.
		$this->field_preview_option( 'choices', $field );

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Setup and sanitize the necessary data.
		$primary           = $field['properties']['inputs']['primary'];
		$field             = apply_filters( 'everest_forms_select_field_display', $field, $field_atts, $form_data );
		$field_placeholder = ! empty( $field['placeholder'] ) ? evf_string_translation( $form_data['id'], $field['id'], $field['placeholder'], '-placeholder' ) : '';
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
			printf( '<option value="" class="placeholder" disabled %s>%s</option>', selected( false, $has_default, false ), esc_html( $field_placeholder ) );
		}

		// Build the select options.
		foreach ( $choices as $key => $choice ) {
			// Register string for translation.
			$choice['label'] = evf_string_translation( $form_data['id'], $field['id'], $choice['label'], '-choices-' . $key );

			$selected = isset( $choice['default'] ) && empty( $field_placeholder ) ? '1' : '0';
			$val      = isset( $field['show_values'] ) ? esc_attr( $choice['value'] ) : esc_attr( $choice['label'] );

			printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( '1', $selected, false ), esc_html( $choice['label'] ) );
		}

		echo '</select>';
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $field_id     Field ID.
	 * @param mixed  $field_submit Submitted field value.
	 * @param array  $form_data    Form data and settings.
	 * @param string $meta_key     Field meta key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field     = $form_data['form_fields'][ $field_id ];
		$name      = make_clickable( $field['label'] );
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
		if ( ! empty( $field['show_values'] ) && '1' === $field['show_values'] ) {
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
		evf()->task->form_fields[ $field_id ] = $data;
	}
}
