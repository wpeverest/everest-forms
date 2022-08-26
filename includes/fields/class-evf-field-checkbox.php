<?php
/**
 * Checkbox field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Checkbox class.
 */
class EVF_Field_Checkbox extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Checkboxes', 'everest-forms' );
		$this->type     = 'checkbox';
		$this->icon     = 'evf-icon evf-icon-checkbox';
		$this->order    = 70;
		$this->group    = 'general';
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( 'First Choice', 'everest-forms' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( 'Second Choice', 'everest-forms' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( 'Third Choice', 'everest-forms' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'choices',
					'choices_images',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'randomize',
					'show_values',
					'input_columns',
					'choice_limit',
					'label_hide',
					'css',
					'select_all',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_html_field_value', array( $this, 'html_field_value' ), 10, 4 );
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Return images, if any, for HTML supported values.
	 *
	 * @since 1.6.0
	 *
	 * @param string $value     Field value.
	 * @param array  $field     Field settings.
	 * @param array  $form_data Form data and settings.
	 * @param string $context   Value display context.
	 *
	 * @return string
	 */
	public function html_field_value( $value, $field, $form_data = array(), $context = '' ) {
		if ( is_serialized( $field ) || in_array( $context, array( 'email-html', 'export-pdf' ), true ) ) {
			$field_value = maybe_unserialize( $field );
			$field_type  = isset( $field_value['type'] ) ? sanitize_text_field( $field_value['type'] ) : 'checkbox';

			if ( $field_type === $this->type ) {
				if (
					'entry-table' !== $context
					&& ! empty( $field_value['label'] )
					&& ! empty( $field_value['images'] )
					&& apply_filters( 'everest_forms_checkbox_field_html_value_images', true, $context )
				) {
					$items = array();

					if ( ! empty( $field_value['label'] ) ) {
						foreach ( $field_value['label'] as $key => $value ) {
							if ( ! empty( $field_value['images'][ $key ] ) ) {
								$items[] = sprintf(
									'<span style="max-width:200px;display:block;margin:0 0 5px 0;"><img src="%s" style="max-width:100%%;display:block;margin:0;"></span>%s',
									esc_url( $field_value['images'][ $key ] ),
									esc_html( $value )
								);
							} else {
								$items[] = esc_html( $value );
							}
						}
					}

					return implode( 'export-csv' !== $context ? '<br><br>' : '|', $items );
				}
			}
		}

		return $value;
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
		// Define data.
		$form_id  = absint( $form_data['id'] );
		$field_id = $field['id'];
		$choices  = $field['choices'];

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Set input container (ul) properties.
		$properties['input_container'] = array(
			'class' => array( ! empty( $field['random'] ) ? 'everest-forms-randomize' : '' ),
			'data'  => array(),
			'attr'  => array(),
			'id'    => "evf-{$form_id}-field_{$field_id}",
		);

		// Set choice limit.
		$field['choice_limit'] = empty( $field['choice_limit'] ) ? 0 : (int) $field['choice_limit'];
		if ( $field['choice_limit'] > 0 ) {
			$properties['input_container']['data']['choice-limit'] = $field['choice_limit'];
		}

		// Set input properties.
		foreach ( $choices as $key => $choice ) {
			$depth = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;

			// Choice labels should not be left blank, but if they are we provide a basic value.
			$value = isset( $field['show_values'] ) ? $choice['value'] : $choice['label'];
			if ( '' === $value ) {
				if ( 1 === count( $choices ) ) {
					$value = esc_html__( 'Checked', 'everest-forms' );
				} else {
					/* translators: %s - Choice Number. */
					$value = sprintf( esc_html__( 'Choice %s', 'everest-forms' ), $key );
				}
			}

			$properties['inputs'][ $key ] = array(
				'container' => array(
					'attr'  => array(),
					'class' => array( "choice-{$key}", "depth-{$depth}" ),
					'data'  => array(),
					'id'    => '',
				),
				'label'     => array(
					'attr'  => array(
						'for' => "evf-{$form_id}-field_{$field_id}_{$key}",
					),
					'class' => array( 'everest-forms-field-label-inline' ),
					'data'  => array(),
					'id'    => '',
					'text'  => evf_string_translation( $form_id, $field_id, $choice['label'], '-choice-' . $key ),
				),
				'attr'      => array(
					'name'  => "everest_forms[form_fields][{$field_id}][]",
					'value' => $value,
				),
				'class'     => array( 'input-text' ),
				'data'      => array(),
				'id'        => "evf-{$form_id}-field_{$field_id}_{$key}",
				'image'     => isset( $choice['image'] ) ? $choice['image'] : '',
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);

			// Rule for choice limit validator.
			if ( $field['choice_limit'] > 0 ) {
				$properties['inputs'][ $key ]['data']['rule-check-limit'] = 'true';
			}
		}

		// Required class for validation.
		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'evf-field-required';
		}

		// Custom properties if enabled image choices.
		if ( ! empty( $field['choices_images'] ) ) {
			$properties['input_container']['class'][] = 'everest-forms-image-choices';

			foreach ( $properties['inputs'] as $key => $inputs ) {
				$properties['inputs'][ $key ]['container']['class'][] = 'everest-forms-image-choices-item';
			}
		}

		// Add selected class for choices with defaults.
		foreach ( $properties['inputs'] as $key => $inputs ) {
			if ( ! empty( $inputs['default'] ) ) {
				$properties['inputs'][ $key ]['container']['class'][] = 'everest-forms-selected';
			}
		}

		return $properties;
	}

	/**
	 * Randomize order of choices.
	 *
	 * @since 1.6.0
	 * @param array $field Field Data.
	 */
	public function randomize( $field ) {
		$args = array(
			'slug'    => 'random',
			'content' => $this->field_element(
				'checkbox',
				$field,
				array(
					'slug'    => 'random',
					'value'   => isset( $field['random'] ) ? '1' : '0',
					'desc'    => esc_html__( 'Randomize Choices', 'everest-forms' ),
					'tooltip' => esc_html__( 'Check this option to randomize the order of the choices.', 'everest-forms' ),
				),
				false
			),
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Choice limit field option.
	 *
	 * @since 1.6.0
	 * @param array $field Field data.
	 */
	public function choice_limit( $field ) {
		$choice_limit_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'choice_limit',
				'value'   => esc_html__( 'Choice Limit', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this option to limit the number of checkboxes a user can select.', 'everest-forms' ),
			),
			false
		);
		$choice_limit_input = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'choice_limit',
				'value' => ( isset( $field['choice_limit'] ) && $field['choice_limit'] > 0 ) ? (int) $field['choice_limit'] : '',
				'type'  => 'number',
			),
			false
		);

		$args = array(
			'slug'    => 'choice_limit',
			'content' => $choice_limit_label . $choice_limit_input,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Show values field option.
	 *
	 * @param array $field Field Data.
	 */
	public function show_values( $field ) {
		// Show Values toggle option. This option will only show if already used or if manually enabled by a filter.
		if ( ! empty( $field['show_values'] ) || apply_filters( 'everest_forms_fields_show_options_setting', false ) ) {
			$args = array(
				'slug'    => 'show_values',
				'content' => $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'show_values',
						'value'   => isset( $field['show_values'] ) ? $field['show_values'] : '0',
						'desc'    => __( 'Show Values', 'everest-forms' ),
						'tooltip' => __( 'Check this to manually set form field values.', 'everest-forms' ),
					),
					false
				),
			);
			$this->field_element( 'row', $field, $args );
		}
	}

	/**
	 * Select All checkbox.
	 *
	 * @since 1.8.4
	 * @param array $field Field data.
	 */
	public function select_all( $field ) {
		$fld = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'select_all',
				'value'   => isset( $field['select_all'] ) ? '1' : '0',
				'desc'    => esc_html__( 'Select All', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this option to select all the options.', 'everest-forms' ),
			),
			false
		);

		$args = array(
			'slug'    => 'select_all',
			'content' => $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
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
		// Define data.
		$container  = $field['properties']['input_container'];
		$choices    = $field['properties']['inputs'];
		$select_all = isset( $field['select_all'] ) ? $field['select_all'] : '0';

		// List.
		printf( '<ul %s>', evf_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] ) );

		// Select All Checkbox.
		if ( '1' === $select_all ) {
			printf( '<li class="evf-select-all-checkbox-li"><input type="checkbox" id="evfCheckAll" class="evf-select-all-checkbox"><label for="evfCheckAll">' . esc_html__( 'Select All', 'everest-forms' ) . '</label></li>' );
		}

		foreach ( $choices as $choice ) {
			if ( empty( $choice['container'] ) ) {
				continue;
			}

			// Conditional logic.
			if ( isset( $choices['primary'] ) ) {
				$choice['attr']['conditional_id'] = $choices['primary']['attr']['conditional_id'];
				if ( isset( $choices['primary']['attr']['conditional_rules'] ) ) {
					$choice['attr']['conditional_rules'] = $choices['primary']['attr']['conditional_rules'];
				}
			}

			printf( '<li %s>', evf_html_attributes( $choice['container']['id'], $choice['container']['class'], $choice['container']['data'], $choice['container']['attr'] ) );

			if ( ! empty( $field['choices_images'] ) ) {
				// Make image choices keyboard-accessible.
				$choice['label']['attr']['tabindex'] = 0;

				// Image choices.
				printf( '<label %s>', evf_html_attributes( $choice['label']['id'], $choice['label']['class'], $choice['label']['data'], $choice['label']['attr'] ) );

				if ( ! empty( $choice['image'] ) ) {
					printf(
						'<span class="everest-forms-image-choices-image"><img src="%s" alt="%s"%s></span>',
						esc_url( $choice['image'] ),
						esc_attr( $choice['label']['text'] ),
						! empty( $choice['label']['text'] ) ? ' title="' . esc_attr( $choice['label']['text'] ) . '"' : ''
					);
				}

				echo '<br>';

				$choice['attr']['tabindex'] = '-1';
				printf( '<input type="checkbox" %s %s %s>', evf_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				echo '<label class="everest-forms-image-choices-label">' . wp_kses_post( $choice['label']['text'] ) . '</label>';
				echo '</label>';
			} else {
				// Normal display.
				printf( '<input type="checkbox" %s %s %s>', evf_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				printf( '<label %s>%s</label>', evf_html_attributes( $choice['label']['id'], $choice['label']['class'], $choice['label']['data'], $choice['label']['attr'] ), wp_kses_post( $choice['label']['text'] ) );
			}

			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Edit form field display on the entry back-end.
	 *
	 * @since 1.7.0
	 *
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {
		$value_choices = ! empty( $entry_field['value_raw'] ) ? $entry_field['value_raw'] : array();

		$this->remove_field_choices_defaults( $field, $field['properties'] );

		foreach ( $value_choices as $input => $single_value ) {
			$field['properties'] = $this->get_single_field_property_value( $single_value, sanitize_key( $input ), $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * Validates field on form submit.
	 *
	 * @since 1.6.0
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted data.
	 * @param array $form_data    Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$field_submit     = (array) $field_submit;
		$form_id          = $form_data['id'];
		$fields           = $form_data['form_fields'];
		$choice_limit     = empty( $fields[ $field_id ]['choice_limit'] ) ? 0 : (int) $fields[ $field_id ]['choice_limit'];
		$entry            = $form_data['entry'];
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );

		if ( false === $visible ) {
			return;
		}

		// Generating the error.
		if ( $choice_limit > 0 && $choice_limit < count( $field_submit ) ) {
			$error = get_option( 'everest_forms_check_limit_validation', esc_html__( 'You have exceeded number of allowed selections: {#}.', 'everest-forms' ) );
			$error = str_replace( '{#}', $choice_limit, $error );
		}

		// Basic required check.
		if ( ! empty( $fields[ $field_id ]['required'] ) && ( empty( $field_submit ) || ( 1 === count( $field_submit ) && empty( $field_submit[0] ) ) ) ) {
			$error = $required_message;
		}

		if ( ! empty( $error ) ) {
			evf()->task->errors[ $form_id ][ $field_id ] = $error;
		}
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Field.
	 * @param array  $form_data All Form Data.
	 * @param string $meta_key Field Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field_submit = (array) $field_submit;
		$field        = $form_data['form_fields'][ $field_id ];
		$name         = make_clickable( $field['label'] );
		$value_raw    = evf_sanitize_array_combine( $field_submit );
		$choice_keys  = array();

		$data = array(
			'id'        => $field_id,
			'type'      => $this->type,
			'value'     => array(
				'name' => $name,
				'type' => $this->type,
			),
			'meta_key'  => $meta_key,
			'value_raw' => $value_raw,
		);

		/*
		 * If show_values is true, that means values posted are the raw values
		 * and not the labels. So we need to get the label values.
		 */
		if ( ! empty( $field['show_values'] ) && '1' === $field['show_values'] ) {
			foreach ( $field_submit as $item ) {
				foreach ( $field['choices'] as $key => $choice ) {
					if ( $item === $choice['value'] || ( empty( $choice['value']['label'] ) && (int) str_replace( 'Choice ', '', $item ) === $key ) ) {
						$value[]       = $choice['label'];
						$choice_keys[] = $key;
						break;
					}
				}
			}

			$data['value']['label'] = ! empty( $value ) ? evf_sanitize_array_combine( $value ) : '';
		} else {
			$data['value']['label'] = $value_raw;

			// Determine choices keys, this is needed for image choices.
			foreach ( $field_submit as $item ) {
				foreach ( $field['choices'] as $key => $choice ) {
					if ( $item === $choice['label'] ) {
						$choice_keys[] = $key;
						break;
					}
				}
			}
		}

		// Images choices are enabled, lookup and store image URLs.
		if ( ! empty( $choice_keys ) && ! empty( $field['choices_images'] ) ) {
			$data['value']['images'] = array();

			foreach ( $choice_keys as $key ) {
				$data['value']['images'][] = ! empty( $field['choices'][ $key ]['image'] ) ? esc_url_raw( $field['choices'][ $key ]['image'] ) : '';
			}
		}

		// Push field details to be saved.
		evf()->task->form_fields[ $field_id ] = $data;
	}
}
