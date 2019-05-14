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
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'step',
					'max_value',
					'min_value',
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
				'value' => isset( $field['step'] ) ? $field['step'] : '1',
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
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 * @param array $field Field Data.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="number" placeholder="' . $placeholder . '" class="widefat" disabled>'; // @codingStandardsIgnoreLine.

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 * @param array $field Field Data.
	 * @param array $deprecated Deprecated Parameter.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $deprecated, $form_data ) {
		$step      = isset( $field['step'] ) ? $field['step'] : '1';
		$min_value = isset( $field['min_value'] ) ? $field['min_value'] : '';
		$max_value = isset( $field['max_value'] ) ? $field['max_value'] : '';

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Primary field.
		printf(
			'<input type="number" %s %s min="%s" max="%s" step="%s" />',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_html( $primary['required'] ),
			esc_html( $min_value ),
			esc_html( $max_value ),
			esc_html( $step )
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
		EVF()->task->form_fields[ $field_id ] = array(
			'name'     => sanitize_text_field( $name ),
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
		$field_type = isset( $form_data['form_fields'][ $field_id ]['type'] ) ? $form_data['form_fields'][ $field_id ]['type'] : '';
		$min        = null !== trim( $form_data['form_fields'][ $field_id ]['min_value'] ) ? floatval( $form_data['form_fields'][ $field_id ]['min_value'] ) : '';
		$max        = null !== trim( $form_data['form_fields'][ $field_id ]['max_value'] ) ? floatval( $form_data['form_fields'][ $field_id ]['max_value'] ) : '';

		if ( floatval( $field_submit ) < $min ) {
			$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter a value greater than or equal to ' . $min, 'everest-forms' ) );
		} elseif ( floatval( $field_submit ) > $max ) {
			$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter a value less than or equal to ' . $max, 'everest-forms' ) );
		}

		if ( isset( $validation_text ) ) {
			EVF()->task->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_type_validation', $validation_text );
			update_option( 'evf_validation_error', 'yes' );
		}
	}
}

