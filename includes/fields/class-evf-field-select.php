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
					'enhanced_select',
					'description',
					'required',
					'required_field_message_setting',
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
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_action( 'everest_forms_shortcode_scripts', array( $this, 'load_assets' ) );
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.7.0
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
		$field    = apply_filters( 'everest_forms_dropdown_options', $field );
		$choices  = $field['choices'];

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Set input container (<select>) properties.
		$properties['input_container'] = array(
			'class' => array( 'input-text' ),
			'data'  => array(),
			'id'    => "evf-{$form_id}-field_{$field_id}",
			'attr'  => array(
				'name' => "everest_forms[form_fields][{$field_id}]",
			),
		);

		// Set input properties.
		foreach ( $choices as $key => $choice ) {
			$depth = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;

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
					'value' => isset( $field['show_values'] ) ? $choice['value'] : $choice['label'],
				),
				'class'     => array(),
				'data'      => array(),
				'id'        => "evf-{$form_id}-field_{$field_id}_{$key}",
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);
		}

		// Required class for validation.
		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'evf-field-required';
		}

		return $properties;
	}

	/**
	 * Register/queue frontend scripts.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function load_assets( $atts ) {
		$form_data = evf()->form->get( $atts['id'], array( 'content_only' => true ) );

		if ( ! empty( $form_data['form_fields'] ) ) {
			$is_enhanced_select = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type'            => 'select',
					'enhanced_select' => 1,
				)
			);

			if ( ! empty( $is_enhanced_select ) ) {
				wp_enqueue_style( 'evf_select2' );
				wp_enqueue_script( 'selectWoo' );
			}
		}
	}

	/**
	 * Enable enhanced select field option.
	 *
	 * @param array $field Field Data.
	 */
	public function enhanced_select( $field ) {
		$plan    = evf_get_license_plan();
		$value   = isset( $field['enhanced_select'] ) && false !== $plan ? $field['enhanced_select'] : '0';
		$tooltip = esc_html__( 'Check this option to enable enhanced select. It enables you to search items in the dropdown field.', 'everest-forms' );

		// Enable enhanced select toggle field.
		$enhanced_select = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'enhanced_select',
				'value'   => $value,
				'class'   => ( false === $plan ) ? 'disabled' : '',
				'desc'    => esc_html__( 'Enable Enhanced Select', 'everest-forms' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'enhanced_select',
				'content' => $enhanced_select,
				'class'   => ( false === $plan ) ? 'upgrade-modal' : '',
				'data'    => array(
					'feature' => esc_html__( 'Enhanced select', 'everest-forms' ),
				),
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$args = array();

		if (
			! empty( $field['enhanced_select'] )
			&& ! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices']
		) {
			$args['class'] = 'evf-enhanced-select';
		}

		// Label.
		$this->field_preview_option( 'label', $field );

		// Choices.
		$this->field_preview_option( 'choices', $field, $args );

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
		$container         = $field['properties']['input_container'];
		$choices           = $field['properties']['inputs'];
		$field             = apply_filters( 'everest_forms_select_field_display', $field, $field_atts, $form_data );
		$field_placeholder = ! empty( $field['placeholder'] ) ? evf_string_translation( $form_data['id'], $field['id'], $field['placeholder'], '-placeholder' ) : '';
		$plan              = evf_get_license_plan();
		$has_default       = false;
		$is_multiple       = false;
		$select_all        = isset( $field['select_all'] ) ? $field['select_all'] : '0';

		if ( ! empty( $field['required'] ) ) {
			$container['attr']['required'] = 'required';
		}

		// Enable enhanced select.
		if ( false !== $plan && ! empty( $field['enhanced_select'] ) && '1' === $field['enhanced_select'] ) {
			$container['class'][] = 'evf-enhanced-select';

			if ( empty( $field_placeholder ) ) {
				$first_choices     = reset( $choices );
				$field_placeholder = $first_choices['label']['text'];
			}

			// Set placeholder for select2.
			$container['data']['placeholder'] = esc_attr( $field_placeholder );
		}

		// Enable multiple choices selection.
		if ( false !== $plan && ! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices'] ) {
			$is_multiple                   = true;
			$container['attr']['multiple'] = 'multiple';

			// Change a name attribute.
			if ( ! empty( $container['attr']['name'] ) ) {
				$container['attr']['name'] .= '[]';
			}
		}

		// Check to see if any of the options have selected by default.
		foreach ( $choices as $choice ) {
			if ( ! empty( $choice['default'] ) ) {
				$has_default = true;
				break;
			}
		}

		// Conditional logic.
		if ( isset( $choices['primary'] ) ) {
			$container['attr']['conditional_id'] = $choices['primary']['attr']['conditional_id'];

			if ( isset( $choices['primary']['attr']['conditional_rules'] ) ) {
				$container['attr']['conditional_rules'] = $choices['primary']['attr']['conditional_rules'];
			}
		}

		// Select All checkbox.
		if ( '1' === $select_all ) {
			if ( isset( $container['attr']['multiple'] ) && 'multiple' === $container['attr']['multiple'] ) {
				$container['attr']['select_all_unselect_all'] = 'true';
			}
		}

		// Primary select field.
		printf(
			'<select %s >',
			evf_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
		);

		// Optional placeholder.
		if ( ! empty( $field_placeholder ) ) {
			printf( '<option value="" class="placeholder" disabled %s>%s</option>', selected( false, $has_default || $is_multiple, false ), esc_html( $field_placeholder ) );
		}

		// Build the select options.
		foreach ( $choices as $choice ) {
			if ( empty( $choice['container'] ) ) {
				continue;
			}

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $choice['attr']['value'] ),
				selected( true, ! empty( $choice['default'] ), false ),
				esc_html( $choice['label']['text'] )
			);
		}

		echo '</select>';
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

		if ( is_array( $value_choices ) ) {
			foreach ( $value_choices as $input => $single_value ) {
				$field['properties'] = $this->get_single_field_property_value( $single_value, sanitize_key( $input ), $field['properties'], $field );
			}
		}

		$this->field_display( $field, null, $form_data );
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
		$field = $form_data['form_fields'][ $field_id ];
		$name  = make_clickable( $field['label'] );
		$value = array();

		// Convert field value into to array.
		if ( ! is_array( $field_submit ) ) {
			$field_submit = array( $field_submit );
		}

		$value_raw = evf_sanitize_array_combine( $field_submit );

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
			foreach ( $field_submit as $item ) {
				foreach ( $field['choices'] as $choice ) {
					if ( $item === $choice['value'] ) {
						$value[] = $choice['label'];
						break;
					}
				}
			}

			$data['value'] = ! empty( $value ) ? evf_sanitize_array_combine( $value ) : '';
		} else {
			$data['value'] = $value_raw;
		}

		// Push field details to be saved.
		evf()->task->form_fields[ $field_id ] = $data;
	}
}
