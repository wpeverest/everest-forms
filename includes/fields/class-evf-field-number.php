<?php
/**
 * Number field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Number class.
 */
class EVF_Field_Number extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Number', 'everest-forms' );
		$this->type     = 'number';
		$this->icon     = 'evf-icon evf-icon-number';
		$this->order    = 80;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'step',
					'min_value',
					'max_value',
					'default_value',
					'placeholder',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Step field option.
	 *
	 * @since 1.4.9
	 * @param array $field Field Data.
	 */
	public function step( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'step',
				'value'   => esc_html__( 'Step', 'everest-forms' ),
				'tooltip' => esc_html__( 'Allows users to enter specific legal number intervals.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'slug'  => 'step',
				'class' => 'evf-input-number-step',
				'value' => isset( $field['step'] ) ? $field['step'] : 1,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'step',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Minimum value field option.
	 *
	 * @since 1.4.9
	 * @param array $field Field Data.
	 */
	public function min_value( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'min_value',
				'value'   => esc_html__( 'Min Value', 'everest-forms' ),
				'tooltip' => esc_html__( 'Minimum value user is allowed to enter.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'slug'  => 'min_value',
				'class' => 'evf-input-number',
				'value' => isset( $field['min_value'] ) ? $field['min_value'] : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'min_value',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Maximum value field option.
	 *
	 * @since 1.4.9
	 * @param array $field Field Data.
	 */
	public function max_value( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'max_value',
				'value'   => esc_html__( 'Max Value', 'everest-forms' ),
				'tooltip' => esc_html__( 'Maximum value user is allowed to enter.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'slug'  => 'max_value',
				'class' => 'evf-input-number',
				'value' => isset( $field['max_value'] ) ? $field['max_value'] : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'max_value',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		// Input primary: step interval.
		if ( ! empty( $field['step'] ) ) {
			$properties['inputs']['primary']['attr']['step'] = (float) $field['step'];
		}

		// Input primary: minimum value.
		if ( ! empty( $field['min_value'] ) ) {
			$properties['inputs']['primary']['attr']['min'] = (float) $field['min_value'];
		}

		// Input primary: maximum value.
		if ( ! empty( $field['max_value'] ) ) {
			$properties['inputs']['primary']['attr']['max'] = (float) $field['max_value'];
		}

		return $properties;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="number" placeholder="' . esc_attr($placeholder) . '" class="widefat" disabled>'; // @codingStandardsIgnoreLine.

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
		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Primary field.
		printf(
			'<input type="number" %s %s />',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Field.
	 * @param array  $form_data All Form Data.
	 * @param string $meta_key Field Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		// Define data.
		$name  = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';
		$value = preg_replace( '/[^0-9.]/', '', $field_submit );

		// Set final field details.
		evf()->task->form_fields[ $field_id ] = array(
			'name'     => make_clickable( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}

	/**
	 * Validates field for minimum and maximum data input.
	 *
	 * @since 1.4.9
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Data.
	 * @param array  $form_data All Form Data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id            = absint( $form_data['id'] );
		$min_value          = isset( $form_data['form_fields'][ $field_id ]['min_value'] ) ? floatval( $form_data['form_fields'][ $field_id ]['min_value'] ) : 0;
		$max_value          = isset( $form_data['form_fields'][ $field_id ]['max_value'] ) ? floatval( $form_data['form_fields'][ $field_id ]['max_value'] ) : 0;
		$conditional_status = isset( $form_data['form_fields'][ $field_id ]['conditional_logic_status'] ) ? $form_data['form_fields'][ $field_id ]['conditional_logic_status'] : 0;

		// Basic required check - If field is marked as required, check for entry data.
		if ( ! empty( $form_data['form_fields'][ $field_id ]['required'] ) && '1' !== $conditional_status && empty( $field_submit ) && '0' !== $field_submit ) {
			evf()->task->errors[ $form_id ][ $field_id ] = evf_get_required_label();
			update_option( 'evf_validation_error', 'yes' );
		}

		// Check if value is numeric.
		if ( ! empty( $field_submit ) && ! is_numeric( $field_submit ) ) {
			evf()->task->errors[ $form_id ][ $field_id ] = apply_filters( 'everest_forms_valid_number_label', esc_html__( 'Please enter a valid number.', 'everest-forms' ) );
			update_option( 'evf_validation_error', 'yes' );
		}

		// Check if minimum and maximum value is valid.
		if ( ! empty( $form_data['form_fields'][ $field_id ]['min_value'] ) && ! empty( $field_submit ) && floatval( $field_submit ) < $min_value ) {
			/* translators: %s - minimum value. */
			evf()->task->errors[ $form_id ][ $field_id ] = sprintf( esc_html__( 'Please enter a value greater than or equal to %s', 'everest-forms' ), absint( $min_value ) );
			update_option( 'evf_validation_error', 'yes' );
		} elseif ( ! empty( $form_data['form_fields'][ $field_id ]['max_value'] ) && ! empty( $field_submit ) && floatval( $field_submit ) > $max_value ) {
			/* translators: %s - maximum value. */
			evf()->task->errors[ $form_id ][ $field_id ] = sprintf( esc_html__( 'Please enter a value less than or equal to %s', 'everest-forms' ), absint( $max_value ) );
			update_option( 'evf_validation_error', 'yes' );
		}
	}
}
