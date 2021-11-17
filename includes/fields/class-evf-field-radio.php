<?php
/**
 * Radio field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Radio class.
 */
class EVF_Field_Radio extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Multiple Choice', 'everest-forms' );
		$this->type     = 'radio';
		$this->icon     = 'evf-icon evf-icon-multiple-choices-radio';
		$this->order    = 60;
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
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'randomize',
					'show_values',
					'input_columns',
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
			$field_type  = isset( $field_value['type'] ) ? sanitize_text_field( $field_value['type'] ) : 'radio';

			if ( $field_type === $this->type ) {
				if (
					'entry-table' !== $context
					&& ! empty( $field_value['label'] )
					&& ! empty( $field_value['image'] )
					&& apply_filters( 'everest_forms_checkbox_field_html_value_images', true, $context )
				) {
					return sprintf(
						'<span style="max-width:200px;display:block;margin:0 0 5px 0;"><img src="%s" style="max-width:100%%;display:block;margin:0;"></span>%s',
						esc_url( $field_value['image'] ),
						esc_html( $field_value['label'] )
					);
				} elseif ( isset( $field_value['label'] ) ) {
					return esc_html( $field_value['label'] );
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

		// Set input properties.
		foreach ( $choices as $key => $choice ) {
			$depth                        = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;
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
					'name'  => "everest_forms[form_fields][{$field_id}]",
					'value' => isset( $field['show_values'] ) ? $choice['value'] : $choice['label'],
				),
				'class'     => array( 'input-text' ),
				'data'      => array(),
				'id'        => "evf-{$form_id}-field_{$field_id}_{$key}",
				'image'     => isset( $choice['image'] ) ? $choice['image'] : '',
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);
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
		$container = $field['properties']['input_container'];
		$choices   = $field['properties']['inputs'];

		// List.
		printf( '<ul %s>', evf_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] ) );

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

				printf( '<input type="radio" %s %s %s>', evf_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				echo '<label class="everest-forms-image-choices-label">' . wp_kses_post( $choice['label']['text'] ) . '</label>';
				echo '</label>';
			} else {
				// Normal display.
				printf( '<input type="radio" %s %s %s>', evf_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
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
		$value = isset( $entry_field['value_raw'] ) ? $entry_field['value_raw'] : '';

		$this->remove_field_choices_defaults( $field, $field['properties'] );

		if ( '' !== $value ) {
			$field['properties'] = $this->get_single_field_property_value( $value, 'primary', $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
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
		$field      = $form_data['form_fields'][ $field_id ];
		$name       = make_clickable( $field['label'] );
		$value_raw  = sanitize_text_field( $field_submit );
		$choice_key = '';

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
			foreach ( $field['choices'] as $key => $choice ) {
				if ( $choice['value'] === $field_submit ) {
					$data['value']['label'] = sanitize_text_field( $choice['label'] );
					$choice_key             = $key;
					break;
				}
			}
		} else {
			$data['value']['label'] = $value_raw;

			// Determine choice key, this is needed for image choices.
			foreach ( $field['choices'] as $key => $choice ) {
				if ( $choice['label'] === $field_submit ) {
					$choice_key = $key;
					break;
				}
			}
		}

		// Images choices are enabled, lookup and store image URL.
		if ( ! empty( $choice_key ) && ! empty( $field['choices_images'] ) ) {
			$data['value']['image'] = ! empty( $field['choices'][ $choice_key ]['image'] ) ? esc_url_raw( $field['choices'][ $choice_key ]['image'] ) : '';
		}

		// Push field details to be saved.
		evf()->task->form_fields[ $field_id ] = $data;
	}
}
