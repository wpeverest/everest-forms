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
		$this->icon     = 'evf-icon  evf-icon-number';
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
					'placeholder',
					'label_hide',
					'css',
					'maximum_number',
					'minimum_number',

				),
			),
		);

		parent::__construct();
	}

	/**
	 * Minimum number field option
	 *
	 * @since 1.4.9
	 * @param array $field Field Data.
	 */
	public function minimum_number( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'minimum_number',
				'value'   => esc_html__( 'Minimum Number', 'everest-forms' ),
				'tooltip' => sprintf( esc_html__( 'Minimum number user is allowed to enter.', 'everest-forms' ) ),
			),
			false
		);
		$fld  = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'minimum_number',
				'class' => 'evf-input-number',
				'value' => isset( $field['maximum_number'] ) && null !== trim( $field['minimum_number'] ) ? $field['minimum_number'] : '',
			),
			false
		);
		$args = array(
			'slug'    => 'minimum_number',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Maximum number field option
	 *
	 * @since 1.4.9
	 * @param array $field Field Data.
	 */
	public function maximum_number( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'maximum_number',
				'value'   => esc_html__( 'Maximum Number', 'everest-forms' ),
				'tooltip' => sprintf( esc_html__( 'Minimum number user is allowed to enter.', 'everest-forms' ) ),
			),
			false
		);
		$fld  = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'maximum_number',
				'class' => 'evf-input-number',
				'value' => isset( $field['maximum_number'] ) && null !== trim( $field['maximum_number'] ) ? $field['maximum_number'] : '',
			),
			false
		);
		$args = array(
			'slug'    => 'maximum_number',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}


	/**
	 * Field preview inside the builder.
	 *
	 * @since      1.0.0
	 * @param array $field Field Data.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="number" placeholder="' . esc_html( $placeholder ) . '" class="widefat" disabled>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since      1.0.0
	 * @param array $field Field Data.
	 * @param array $deprecated Deprecated Parameter.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		$min_num = isset( $field['minimum_number'] ) && null !== trim( $field['minimum_number'] ) ? "min='{$field['minimum_number']}'" : '';
		$max_num = isset( $field['maximum_number'] ) && null !== trim( $field['maximum_number'] ) ? "max='{$field['maximum_number']}'" : '';

		// Define data.
		$primary = $field['properties']['inputs']['primary'];
		// Primary field.
		printf(
			'<input type="number" %s %s %s %s>',
			esc_html( evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ) ),
			esc_html( $primary['required'] ),
			esc_html( $min_num ),
			esc_html( $max_num )
		);

	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @param int    $field_id Field Id.
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
		 * @param int   $field_id Field Id.
		 * @param array $field_submit Submitted Data.
		 * @param array $form_data All Form Data.
		 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$field_type = isset( $form_data['form_fields'][ $field_id ]['type'] ) ? $form_data['form_fields'][ $field_id ]['type'] : '';
		$min        = null !== trim( $form_data['form_fields'][ $field_id ]['minimum_number'] ) ? $form_data['form_fields'][ $field_id ]['minimum_number'] : '';
		$max        = null !== trim( $form_data['form_fields'][ $field_id ]['maximum_number'] ) ? $form_data['form_fields'][ $field_id ]['maximum_number'] : '';

		if ( $field_submit < $min ) {
			$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter a value greater than or equal to ' . $min, 'everest-forms' ) );
		} elseif ( $field_submit > $max ) {
			$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter a value less than or equal to ' . $max, 'everest-forms' ) );
		}
		if ( isset( $validation_text ) ) {
			EVF()->task->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_type_validation', $validation_text );
			update_option( 'evf_validation_error', 'yes' );
		}
	}
}

