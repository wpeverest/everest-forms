<?php
/**
 * Yes/No field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Yes_No Class.
 */
class EVF_Field_Yes_No extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Yes/No', 'everest-forms' );
		$this->type     = 'yes-no';
		$this->icon     = 'evf-icon evf-icon-yes-no';
		$this->order    = 15;
		$this->group    = 'survey';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'yes_value',
					'no_value',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'style',
					'yes_icon_color',
					'no_icon_color',
					'yes_label',
					'no_label',
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
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
		add_filter( 'everest_forms_entries_field_editable', array( $this, 'field_editable' ), 10, 2 );
	}

	/**
	 * Returns a SVG for use in the yes no field.
	 *
	 * @param string $icon_type Type of the icon.
	 * @param string $icon_color Color of the icon.
	 */
	private function get_icon_svg( $icon_type = 'yes', $icon_color = '' ) {
		$svg_icons = array(
			'yes' => '<svg width="32" height="32" viewBox="0 0 24 24" style="fill:' . $icon_color . '"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-1.91l-.01-.01L23 10z"/></path></svg>',
			'no'  => '<svg width="32" height="32" viewBox="0 0 24 24" style="fill:' . $icon_color . '"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v1.91l.01.01L1 14c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"></path></svg>',
		);

		if ( isset( $svg_icons[ $icon_type ] ) ) {
			return $svg_icons[ $icon_type ];
		}

		return false;
	}

	/**
	 * Yes field option.
	 *
	 * @param array $field Field settings.
	 */
	public function yes_value( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'yes_value',
				'value'   => esc_html__( 'Yes Value', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter the value for Yes.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'yes_value',
				'value' => ! empty( $field['yes_value'] ) ? esc_attr( $field['yes_value'] ) : 'yes',
				'class' => 'evf-yes',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'yes_value',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * No field option.
	 *
	 * @param array $field Field settings.
	 */
	public function no_value( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'no_value',
				'value'   => esc_html__( 'No Value', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter the value for No.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'no_value',
				'value' => ! empty( $field['no_value'] ) ? esc_attr( $field['no_value'] ) : 'no',
				'class' => 'evf-yes',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'no_value',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Icon color for yes field option.
	 *
	 * @param array $field Field settings.
	 */
	public function style( $field ) {

		$lbl         = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'style',
				'value'   => esc_html__( 'Style', 'everest-forms' ),
				'tooltip' => esc_html__( 'Choose the field design variation.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'select',
			$field,
			array(
				'type'    => 'select',
				'slug'    => 'style',
				'class'   => 'evf-yes-no-class',
				'options' => array(
					'with_icon'      => esc_html__( 'Icon', 'everest-forms' ),
					'with_text'      => esc_html__( 'Text', 'everest-forms' ),
					'with_icon_text' => esc_html__( 'Text and Icon', 'everest-forms' ),
				),
				'value'   => ! empty( $field['style'] ) ? esc_attr( $field['style'] ) : 'with_icon',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'style',
				'content' => $lbl . $input_field,
			)
		);
	}

	/**
	 * Icon color for yes field option.
	 *
	 * @param array $field Field settings.
	 */
	public function yes_icon_color( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'yes_icon_color',
				'value'   => esc_html__( 'Yes Icon Color', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select the primary color for the Yes icon.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'yes_icon_color',
				'value' => ! empty( $field['yes_icon_color'] ) ? esc_attr( $field['yes_icon_color'] ) : 'green',
				'class' => 'evf-colorpicker',
				'data'  => array(
					'default-color' => 'green',
				),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'yes_icon_color',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Icon color for no field option.
	 *
	 * @param array $field Field settings.
	 */
	public function no_icon_color( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'no_icon_color',
				'value'   => esc_html__( 'No Icon Color', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select the primary color for the No icon.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'no_icon_color',
				'value' => ! empty( $field['no_icon_color'] ) ? esc_attr( $field['no_icon_color'] ) : 'red',
				'class' => 'evf-colorpicker',
				'data'  => array(
					'default-color' => 'red',
				),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'no_icon_color',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Label for yes field option.
	 *
	 * @param array $field Field settings.
	 */
	public function yes_label( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'yes_label',
				'value'   => esc_html__( 'Yes Label', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter the label text for Yes.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'yes_label',
				'value' => ! empty( $field['yes_label'] ) ? esc_attr( $field['yes_label'] ) : 'Yes',
				'class' => 'evf-yes-label',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'yes_label',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Label for no field option.
	 *
	 * @param array $field Field settings.
	 */
	public function no_label( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'no_label',
				'value'   => esc_html__( 'No Label', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter the label text for No.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'no_label',
				'value' => ! empty( $field['no_label'] ) ? esc_attr( $field['no_label'] ) : 'No',
				'class' => 'evf-no-label',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'no_label',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.2.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		$properties['inputs']['primary']['yes_no']['yes_value']      = ! empty( $field['yes_value'] ) ? esc_attr( $field['yes_value'] ) : 'yes';
		$properties['inputs']['primary']['yes_no']['no_value']       = ! empty( $field['no_value'] ) ? esc_attr( $field['no_value'] ) : 'no';
		$properties['inputs']['primary']['yes_no']['yes_icon_color'] = ! empty( $field['yes_icon_color'] ) ? esc_attr( $field['yes_icon_color'] ) : 'green';
		$properties['inputs']['primary']['yes_no']['no_icon_color']  = ! empty( $field['no_icon_color'] ) ? esc_attr( $field['no_icon_color'] ) : 'red';
		$properties['inputs']['primary']['yes_no']['yes_label']      = ! empty( $field['yes_label'] ) ? esc_attr( $field['yes_label'] ) : 'Yes';
		$properties['inputs']['primary']['yes_no']['no_label']       = ! empty( $field['no_label'] ) ? esc_attr( $field['no_label'] ) : 'No';
		$properties['inputs']['primary']['yes_no']['field_style']    = ! empty( $field['style'] ) ? esc_attr( $field['style'] ) : 'with_icon';

		if ( 'with_icon' === $properties['inputs']['primary']['yes_no']['field_style'] ) {
			$properties['container']['class'][] = 'evf-yes-no-field-with-icon';
		} elseif ( 'with_text' === $properties['inputs']['primary']['yes_no']['field_style'] ) {
			$properties['container']['class'][] = 'evf-yes-no-field-with-text';

		} elseif ( 'with_icon_text' === $properties['inputs']['primary']['yes_no']['field_style'] ) {
			$properties['container']['class'][] = 'evf-yes-no-field-with-icon-text';
		}

		return $properties;
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @param array $field Field Data.
	 */
	public function field_exporter( $field ) {
		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => ! empty( $field['value'] ) ? $field['value'] : false,
		);
	}

	/**
	 * Allow this field to be editable.
	 *
	 * @param bool   $is_editable True if editable. False if not.
	 * @param string $field_type  Field type to check for editable.
	 */
	public function field_editable( $is_editable, $field_type ) {
		return ! empty( $field_type ) && $field_type === $this->type ? true : $is_editable;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.2.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$yes_icon_color = ! empty( $field['yes_icon_color'] ) ? esc_attr( $field['yes_icon_color'] ) : 'green';
		$no_icon_color  = ! empty( $field['no_icon_color'] ) ? esc_attr( $field['no_icon_color'] ) : 'red';
		$yes_label      = ! empty( $field['yes_label'] ) ? esc_attr( $field['yes_label'] ) : 'Yes';
		$no_label       = ! empty( $field['no_label'] ) ? esc_attr( $field['no_label'] ) : 'No';
		$yes_icon       = $this->get_icon_svg( 'yes', $yes_icon_color );
		$no_icon        = $this->get_icon_svg( 'no', $no_icon_color );
		$field_style    = ! empty( $field['style'] ) ? esc_attr( $field['style'] ) : 'with_icon';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		$additional_class = 'with_icon_text' === $field_style ? ' icon-text' : ( 'with_text' === $field_style ? ' text-only' : '' );

		printf( '<div class="yes-no-preview' . esc_attr( $additional_class ) . '">' );
		if ( 'with_icon' === $field_style ) {
			printf(
				'<span class="%s yes-no-icon">%s</span>',
				esc_attr( 'yes' ),
				$yes_icon // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);

			printf(
				'<span class="%s yes-no-icon">%s</span>',
				esc_attr( 'no' ),
				$no_icon // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		} elseif ( 'with_text' === $field_style ) {
			printf( '<input type="text" value="%s" disabled /><input type="text" value="%s" disabled />', esc_attr( $yes_label ), esc_attr( $no_label ) );
		} elseif ( 'with_icon_text' === $field_style ) {
			printf(
				'<span class="%s yes-no-icon">%s <label>%s</label></span>',
				esc_attr( 'yes' ),
				$yes_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $yes_label )
			);
			printf(
				'<span class="%s yes-no-icon">%s <label>%s</label></span>',
				esc_attr( 'no' ),
				$no_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $no_label )
			);
		}

		printf( '</div>' );

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.2.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Define data.
		$primary        = $field['properties']['inputs']['primary'];
		$yes_no         = $primary['yes_no'];
		$yes_value      = esc_attr( $yes_no['yes_value'] );
		$no_value       = esc_attr( $yes_no['no_value'] );
		$yes_icon_color = $yes_no['yes_icon_color'];
		$no_icon_color  = $yes_no['no_icon_color'];
		$yes_icon       = $this->get_icon_svg( 'yes', $yes_icon_color );
		$no_icon        = $this->get_icon_svg( 'no', $no_icon_color );
		$yes_label      = $yes_no['yes_label'];
		$no_label       = $yes_no['no_label'];
		$field_style    = $yes_no['field_style'];

		echo '<div id="evf-' . absint( $form_data['id'] ) . '-field_' . esc_attr( $field['id'] ) . '" class="everest-forms-field-yes-no-container">';

			printf(
				'<label class="everest-forms-field-yes-no yes" for="everest-forms-%d-field_%s_1">',
				absint( $form_data['id'] ),
				esc_attr( $field['id'] )
			);

			// Primary field.
			$primary['id'] = sprintf(
				'everest-forms-%d-field_%s_1',
				absint( $form_data['id'] ),
				$field['id']
			);

			$primary['attr']['value']      = esc_attr( $yes_value );

		if ( 'with_icon' === $field_style ) {
			printf(
				'<input type="radio" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			echo $yes_icon; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'with_text' === $field_style ) {
			printf(
				'<input type="radio" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);
			printf( '<label for="%s">%s</label>', esc_attr( $primary['id'] ), esc_html( $yes_label ) );
		} elseif ( 'with_icon_text' === $field_style ) {
			printf(
				'<input type="radio" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			echo $yes_icon; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( '<label for="%s">%s</label>', esc_attr( $primary['id'] ), esc_html( $yes_label ) );
		}

			echo '</label>';

			printf(
				'<label class="everest-forms-field-yes-no no" for="everest-forms-%d-field_%s_0">',
				absint( $form_data['id'] ),
				esc_attr( $field['id'] )
			);

			// Primary field.
			$primary['id'] = sprintf(
				'everest-forms-%d-field_%s_0',
				absint( $form_data['id'] ),
				$field['id']
			);

			$primary['attr']['value']      = esc_js( $no_value );


		if ( 'with_icon' === $field_style ) {
			printf(
				'<input type="radio" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			echo $no_icon; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'with_text' === $field_style ) {
			printf(
				'<input type="radio" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);
			printf( '<label for="%s">%s</label>', esc_attr( $primary['id'] ), esc_html( $no_label ) );
		} elseif ( 'with_icon_text' === $field_style ) {
			printf(
				'<input type="radio" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			echo $no_icon; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( '<label for="%s">%s</label>', esc_attr( $primary['id'] ), esc_html( $no_label ) );
		}

			echo '</label>';
		echo '</div>';
	}




	/**
	 * Validates field on form submit.
	 *
	 * @param int   $field_id Field ID.
	 * @param array $field_submit Submitted form field data.
	 * @param array $form_data Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id          = $form_data['id'];
		$entry            = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );

		if ( false === $visible ) {
			return;
		}

		if ( ! empty( $form_data['form_fields'][ $field_id ]['required'] ) && empty( $field_submit ) && '0' !== $field_submit ) {
			EVF()->task->errors[ $form_id ][ $field_id ] = $required_message;
			update_option( 'evf_validation_error', 'yes' );
		}
	}

	/**
	 * Formats field.
	 *
	 * @param int   $field_id Field ID.
	 * @param array $field_submit Submitted form field data.
	 * @param array $form_data Form data.
	 * @param mixed $meta_key Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {

		$value = '' !== $field_submit ? $field_submit : '';
		$name  = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';

		EVF()->task->form_fields[ $field_id ] = array(
			'name'     => sanitize_text_field( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}
}
