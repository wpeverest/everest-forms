<?php
/**
 * Abstract EVF_Form_Fields Class
 *
 * @version 1.0.0
 * @package EverestFroms/Abstracts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form fields class.
 */
abstract class EVF_Form_Fields {

	/**
	 * Field name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Field icon.
	 *
	 * @var mixed
	 */
	public $icon = '';

	/**
	 * Field class.
	 *
	 * @var string
	 */
	public $class = '';

	/**
	 * Form ID.
	 *
	 * @var int|mixed
	 */
	public $form_id;

	/**
	 * Field group.
	 *
	 * @var string
	 */
	public $group = 'general';

	/**
	 * Is available in Pro?
	 *
	 * @var boolean
	 */
	public $is_pro = false;

	/**
	 * Placeholder to hold default value(s) for some field types.
	 *
	 * @var mixed
	 */
	public $defaults;

	/**
	 * Array of form data.
	 *
	 * @var array
	 */
	public $form_data;

	/**
	 * Array of field settings.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->class   = $this->is_pro ? 'upgrade-modal' : '';
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification

		// Init hooks.
		$this->init_hooks();

		// Hooks.
		add_action( 'everest_forms_builder_fields_options_' . $this->type, array( $this, 'field_options' ) );
		add_action( 'everest_forms_builder_fields_preview_' . $this->type, array( $this, 'field_preview' ) );
		add_action( 'wp_ajax_everest_forms_new_field_' . $this->type, array( $this, 'field_new' ) );
		add_action( 'everest_forms_display_field_' . $this->type, array( $this, 'field_display' ), 10, 3 );
		add_action( 'everest_forms_process_validate_' . $this->type, array( $this, 'validate' ), 10, 3 );
		add_action( 'everest_forms_process_format_' . $this->type, array( $this, 'format' ), 10, 4 );
		add_filter( 'everest_forms_field_properties', array( $this, 'field_prefill_value_property' ), 10, 3 );
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {}

	/**
	 * Prefill field value with either fallback or dynamic data.
	 * Needs to be public (although internal) to be used in WordPress hooks.
	 *
	 * @since 1.6.5
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 * @param array $form_data  Prepared form data/settings.
	 *
	 * @return array Modified field properties.
	 */
	public function field_prefill_value_property( $properties, $field, $form_data ) {
		// Process only for current field.
		if ( $this->type !== $field['type'] ) {
			return $properties;
		}

		// Set the form data, so we can reuse it later, even on front-end.
		$this->form_data = $form_data;

		return $properties;
	}

	/**
	 * Get the form fields after they are initialized.
	 *
	 * @return array of options
	 */
	public function get_field_settings() {
		return apply_filters( 'everest_forms_get_field_settings_' . $this->type, $this->settings );
	}

	/**
	 * Output form fields options.
	 *
	 * Loops though the field options array and outputs each field.
	 *
	 * @param array $field Field data.
	 */
	public function field_options( $field ) {
		$settings = $this->get_field_settings();

		foreach ( $settings as $option_key => $option ) {
			$this->field_option(
				$option_key,
				$field,
				array(
					'markup' => 'open',
				)
			);

			if ( ! empty( $option['field_options'] ) ) {
				foreach ( $option['field_options'] as $option_name ) {
					$this->field_option( $option_name, $field );
				}
			}

			$this->field_option(
				$option_key,
				$field,
				array(
					'markup' => 'close',
				)
			);
		}
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {}

	/**
	 * Helper function to create field option elements.
	 *
	 * Field option elements are pieces that help create a field option.
	 * They are used to quickly build field options.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $option Field option to render.
	 * @param array   $field  Field data and settings.
	 * @param array   $args   Field preview arguments.
	 * @param boolean $echo   Print or return the value. Print by default.
	 *
	 * @return mixed echo or return string
	 */
	public function field_element( $option, $field, $args = array(), $echo = true ) {
		$id     = (string) $field['id'];
		$class  = ! empty( $args['class'] ) && is_string( $args['class'] ) ? esc_attr( $args['class'] ) : '';
		$slug   = ! empty( $args['slug'] ) ? sanitize_title( $args['slug'] ) : '';
		$data   = '';
		$output = '';

		if ( ! empty( $args['data'] ) ) {
			foreach ( $args['data'] as $key => $val ) {
				if ( is_array( $val ) ) {
					$val = wp_json_encode( $val );
				}
				$data .= ' data-' . $key . '=\'' . $val . '\'';
			}
		}

		// BW compat for number attrs.
		if ( ! empty( $args['min'] ) ) {
			$args['attrs']['min'] = esc_attr( $args['min'] );
			unset( $args['min'] );
		}
		if ( ! empty( $args['max'] ) ) {
			$args['attrs']['max'] = esc_attr( $args['max'] );
			unset( $args['min'] );
		}
		if ( ! empty( $args['required'] ) && $args['required'] ) {
			$args['attrs']['required'] = 'required';
			unset( $args['required'] );
		}

		if ( ! empty( $args['attrs'] ) ) {
			foreach ( $args['attrs'] as $arg_key => $val ) {
				if ( is_array( $val ) ) {
					$val = wp_json_encode( $val );
				}
				$data .= $arg_key . '=\'' . $val . '\'';
			}
		}

		switch ( $option ) {

			// Row.
			case 'row':
				$output = sprintf( '<div class="everest-forms-field-option-row everest-forms-field-option-row-%s %s" id="everest-forms-field-option-row-%s-%s" data-field-id="%s">%s</div>', $slug, $class, $id, $slug, $id, $args['content'] );
				break;

			// Icon.
			case 'icon':
				$element_tooltip = isset( $args['tooltip'] ) ? $args['tooltip'] : 'Edit Label';
				$icon            = isset( $args['icon'] ) ? $args['icon'] : 'dashicons-edit';
				$output         .= sprintf( ' <i class="dashicons %s everest-forms-icon %s" title="%s" %s></i>', esc_attr( $icon ), $class, esc_attr( $element_tooltip ), $data );
				break;

			// Label.
			case 'label':
				$output = sprintf( '<label for="everest-forms-field-option-%s-%s" class="%s" %s>%s', $id, $slug, $class, $data, esc_html( $args['value'] ) );
				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help everest-forms-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
				}
				if ( isset( $args['after_tooltip'] ) && ! empty( $args['after_tooltip'] ) ) {
					$output .= $args['after_tooltip'];
				}
				$output .= '</label>';
				break;

			// Text input.
			case 'text':
				$type        = ! empty( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';
				$placeholder = ! empty( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '';
				$before      = ! empty( $args['before'] ) ? '<span class="before-input">' . esc_html( $args['before'] ) . '</span>' : '';
				if ( ! empty( $before ) ) {
					$class .= ' has-before';
				}

				$output = sprintf( '%s<input type="%s" class="widefat %s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" value="%s" placeholder="%s" %s>', $before, $type, $class, $id, $slug, $id, $slug, esc_attr( $args['value'] ), $placeholder, $data );
				break;

			// Textarea.
			case 'textarea':
				$rows   = ! empty( $args['rows'] ) ? (int) $args['rows'] : '3';
				$output = sprintf( '<textarea class="widefat %s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" rows="%s" %s>%s</textarea>', $class, $id, $slug, $id, $slug, $rows, $data, $args['value'] );
				break;

			// Checkbox.
			case 'checkbox':
				$checked = checked( '1', $args['value'], false );
				$output  = sprintf( '<input type="checkbox" class="widefat %s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" value="1" %s %s>', $class, $id, $slug, $id, $slug, $checked, $data );
				$output .= sprintf( '<label for="everest-forms-field-option-%s-%s" class="inline">%s', $id, $slug, $args['desc'] );
				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help everest-forms-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
				}
				$output .= '</label>';
				break;

			// Toggle.
			case 'toggle':
				$checked = checked( '1', $args['value'], false );
				$icon    = $args['value'] ? 'fa-toggle-on' : 'fa-toggle-off';
				$cls     = $args['value'] ? 'everest-forms-on' : 'everest-forms-off';
				$status  = $args['value'] ? __( 'On', 'everest-forms' ) : __( 'Off', 'everest-forms' );
				$output  = sprintf( '<span class="everest-forms-toggle-icon %s"><i class="fa %s" aria-hidden="true"></i> <span class="everest-forms-toggle-icon-label">%s</span>', $cls, $icon, $status );
				$output .= sprintf( '<input type="checkbox" class="widefat %s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" value="1" %s %s></span>', $class, $id, $slug, $id, $slug, $checked, $data );
				break;

			// Select.
			case 'select':
				$options = $args['options'];
				$value   = isset( $args['value'] ) ? $args['value'] : '';
				$output  = sprintf( '<select class="widefat %s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" %s>', $class, $id, $slug, $id, $slug, $data );
				foreach ( $options as $key => $option ) {
					$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $key, $value, false ), $option );
				}
				$output .= '</select>';
				break;

			// Radio.
			case 'radio':
				$options = $args['options'];
				$default = isset( $args['default'] ) ? $args['default'] : '';
				$output  = '<label>' . $args['desc'];

				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help everest-forms-help-tooltip" title="%s"></i></label>', esc_attr( $args['tooltip'] ) );
				} else {
					$output .= '</label>';
				}
				$output .= '<ul>';

				foreach ( $options as $key => $option ) {
					$output .= '<li>';
					$output .= sprintf( '<label><input type="radio" class="widefat %s" id="everest-forms-field-option-%s-%s-%s" value="%s" name="form_fields[%s][%s]" %s %s>%s</label>', $class, $id, $slug, $key, $key, $id, $slug, $data, checked( $key, $default, false ), $option );
					$output .= '</li>';
				}
				$output .= '</ul>';
				break;
		}

		if ( $echo ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $output;
		}
	}

	/**
	 * Helper function to create common field options that are used frequently.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $option Option.
	 * @param array   $field  Field data.
	 * @param array   $args   Arguments.
	 * @param boolean $echo   True to echo.
	 *
	 * @return mixed echo or return string
	 */
	public function field_option( $option, $field, $args = array(), $echo = true ) {
		$output = '';

		switch ( $option ) {
			/**
			 * Basic Fields.
			 */

			/*
			 * Basic Options markup.
			 */
			case 'basic-options':
				$markup = ! empty( $args['markup'] ) ? $args['markup'] : 'open';
				$class  = ! empty( $args['class'] ) ? esc_html( $args['class'] ) : '';
				if ( 'open' === $markup ) {
					$output  = sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-basic open" id="everest-forms-field-option-basic-%s">', $field['id'] );
					$output .= sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<span> (ID #%s)</span> <i class="handlediv"></i></a>', $this->name, $field['id'] );
					$output .= sprintf( '<div class="everest-forms-field-option-group-inner %s">', $class );
				} else {
					$output = '</div></div>';
				}
				break;

			/*
			 * Field Label.
			 */
			case 'label':
				$value   = ! empty( $field['label'] ) ? esc_attr( $field['label'] ) : '';
				$tooltip = esc_html__( 'Enter text for the form field label. This is recommended and can be hidden in the Advanced Settings.', 'everest-forms' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'label',
						'value'   => esc_html__( 'Label', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'label',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'label',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Field Meta.
			 */
			case 'meta':
				$value   = ! empty( $field['meta-key'] ) ? esc_attr( $field['meta-key'] ) : evf_get_meta_key_field_option( $field );
				$tooltip = esc_html__( 'Enter meta key to be stored in database.', 'everest-forms' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'meta-key',
						'value'   => esc_html__( 'Meta Key', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'meta-key',
						'class' => 'evf-input-meta-key',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'meta-key',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Field Description.
			 */
			case 'description':
				$value   = ! empty( $field['description'] ) ? esc_attr( $field['description'] ) : '';
				$tooltip = esc_html__( 'Enter text for the form field description.', 'everest-forms' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'description',
						'value'   => esc_html__( 'Description', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'textarea',
					$field,
					array(
						'slug'  => 'description',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'description',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Field Required toggle.
			 */
			case 'required':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = isset( $field['required'] ) ? $field['required'] : $default;
				$tooltip = esc_html__( 'Check this option to mark the field required. A form will not submit unless all required fields are provided.', 'everest-forms' );
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'required',
						'value'   => $value,
						'desc'    => esc_html__( 'Required', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'required',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Required Field Message.
			 */
			case 'required_field_message':
				$has_sub_fields = false;
				$sub_fields     = array();

				$required_validation = get_option( 'everest_forms_required_validation' );
				if ( in_array( $field['type'], array( 'number', 'email', 'url', 'phone' ), true ) ) {
					$required_validation = get_option( 'everest_forms_' . $field['type'] . '_validation' );
				}

				if ( 'likert' === $field['type'] ) {
					$has_sub_fields = true;
					$likert_rows    = isset( $field['likert_rows'] ) ? $field['likert_rows'] : array();
					foreach ( $likert_rows as $row_number => $row_label ) {
						$row_slug                = 'required-field-message-' . $row_number;
						$sub_fields[ $row_slug ] = array(
							'label' => array(
								'value'   => $row_label,
								'tooltip' => esc_html__( 'Enter a message to show for this row if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field[ $row_slug ] ) ? esc_attr( $field[ $row_slug ] ) : esc_attr( $required_validation ),
							),
						);
					}
				} elseif ( 'address' === $field['type'] ) {
					$has_sub_fields = true;
					$sub_fields     = array(
						'required-field-message-address1' => array(
							'label' => array(
								'value'   => esc_html__( 'Address Line 1', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for Address Line 1 if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-address1'] ) ? esc_attr( $field['required-field-message-address1'] ) : esc_attr( $required_validation ),
							),
						),
						'required-field-message-city'     => array(
							'label' => array(
								'value'   => esc_html__( 'City', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for City if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-city'] ) ? esc_attr( $field['required-field-message-city'] ) : esc_attr( $required_validation ),
							),
						),
						'required-field-message-state'    => array(
							'label' => array(
								'value'   => esc_html__( 'State / Province / Region', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for State/Province/Region if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-state'] ) ? esc_attr( $field['required-field-message-state'] ) : esc_attr( $required_validation ),
							),
						),
						'required-field-message-postal'   => array(
							'label' => array(
								'value'   => esc_html__( 'Zip / Postal Code', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for Zip/Postal Code if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-postal'] ) ? esc_attr( $field['required-field-message-postal'] ) : esc_attr( $required_validation ),
							),
						),
						'required-field-message-country'  => array(
							'label' => array(
								'value'   => esc_html__( 'Country', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for Country if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-country'] ) ? esc_attr( $field['required-field-message-country'] ) : esc_attr( $required_validation ),
							),
						),
					);
				}

				if ( true === $has_sub_fields ) {
					$sub_field_output_array = array();
					foreach ( $sub_fields as $sub_field_slug => $sub_field_data ) {
						$value   = isset( $field['required-field-message'] ) ? esc_attr( $field['required-field-message'] ) : esc_attr( $required_validation );
						$tooltip = esc_html__( 'Enter a message to show for this field if it\'s required.', 'everest-forms' );
						$output  = $this->field_element(
							'label',
							$field,
							array(
								'slug'    => $sub_field_slug,
								'value'   => $sub_field_data['label']['value'],
								'tooltip' => $sub_field_data['label']['tooltip'],
							),
							false
						);
						$output .= $this->field_element(
							'text',
							$field,
							array(
								'slug'  => $sub_field_slug,
								'value' => $sub_field_data['text']['value'],
							),
							false
						);
						$output  = $this->field_element(
							'row',
							$field,
							array(
								'slug'    => $sub_field_slug,
								'content' => $output,
							),
							false
						);

						$sub_field_output_array[] = $output;
					}
					$output = implode( '', $sub_field_output_array );
					$output = $this->field_element(
						'row',
						$field,
						array(
							'slug'    => 'required-field-message',
							'class'   => isset( $field['required'] ) ? '' : 'hidden',
							'content' => $output,
						),
						false
					);
				} else {
					$value   = isset( $field['required-field-message'] ) ? esc_attr( $field['required-field-message'] ) : esc_attr( $required_validation );
					$tooltip = esc_html__( 'Enter a message to show for this field if it\'s required.', 'everest-forms' );
					$output  = $this->field_element(
						'label',
						$field,
						array(
							'slug'    => 'required-field-message',
							'value'   => esc_html__( 'Required Field Message', 'everest-forms' ),
							'tooltip' => $tooltip,
						),
						false
					);
					$output .= $this->field_element(
						'text',
						$field,
						array(
							'slug'  => 'required-field-message',
							'value' => $value,
						),
						false
					);
					$output  = $this->field_element(
						'row',
						$field,
						array(
							'slug'    => 'required-field-message',
							'class'   => isset( $field['required'] ) ? '' : 'hidden',
							'content' => $output,
						),
						false
					);
				}
				break;

			/*
			 * Code Block.
			 */
			case 'code':
				$value   = ! empty( $field['code'] ) ? esc_attr( $field['code'] ) : '';
				$tooltip = esc_html__( 'Enter code for the form field.', 'everest-forms' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'code',
						'value'   => esc_html__( 'Code', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'textarea',
					$field,
					array(
						'slug'  => 'code',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'code',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Choices.
			 */
			case 'choices':
				$class      = array();
				$label      = ! empty( $args['label'] ) ? esc_html( $args['label'] ) : esc_html__( 'Choices', 'everest-forms' );
				$choices    = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
				$input_type = in_array( $field['type'], array( 'radio', 'payment-multiple' ), true ) ? 'radio' : 'checkbox';

				if ( ! empty( $field['show_values'] ) ) {
					$class[] = 'show-values';
				}
				if ( ! empty( $field['choices_images'] ) ) {
					$class[] = 'show-images';
				}

				// Field label.
				$field_label = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'choices',
						'value'         => $label,
						'tooltip'       => esc_html__( 'Add choices for the form field.', 'everest-forms' ),
						'after_tooltip' => '', // @todo Bulk import and export for choices.
					)
				);

				// Field contents.
				$field_content = sprintf(
					'<ul data-next-id="%s" class="evf-choices-list %s" data-field-id="%s" data-field-type="%s">',
					max( array_keys( $choices ) ) + 1,
					evf_sanitize_classes( $class, true ),
					$field['id'],
					$this->type
				);
				foreach ( $choices as $key => $choice ) {
					$default = ! empty( $choice['default'] ) ? $choice['default'] : '';
					$name    = sprintf( 'form_fields[%s][choices][%s]', $field['id'], $key );
					$image   = ! empty( $choice['image'] ) ? $choice['image'] : '';

					// BW compatibility for value in payment fields.
					if ( ! empty( $field['amount'][ $key ]['value'] ) ) {
						$choice['value'] = evf_format_amount( evf_sanitize_amount( $field['amount'][ $key ]['value'] ) );
					}

					$field_content .= sprintf( '<li data-key="%1$d">', absint( $key ) );
					$field_content .= '<span class="sort"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" role="img" aria-hidden="true" focusable="false"><path d="M13,8c0.6,0,1-0.4,1-1s-0.4-1-1-1s-1,0.4-1,1S12.4,8,13,8z M5,6C4.4,6,4,6.4,4,7s0.4,1,1,1s1-0.4,1-1S5.6,6,5,6z M5,10 c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S5.6,10,5,10z M13,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S13.6,10,13,10z M9,6 C8.4,6,8,6.4,8,7s0.4,1,1,1s1-0.4,1-1S9.6,6,9,6z M9,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S9.6,10,9,10z"></path></svg></span>';
					$field_content .= sprintf( '<input type="%1$s" name="%2$s[default]" class="default" value="1" %3$s>', $input_type, $name, checked( '1', $default, false ) );
					$field_content .= '<div class="evf-choice-list-input">';
					$field_content .= sprintf( '<input type="text" name="%1$s[label]" value="%2$s" class="label" data-key="%3$s">', $name, esc_attr( $choice['label'] ), absint( $key ) );
					if ( in_array( $field['type'], array( 'payment-multiple', 'payment-checkbox' ), true ) ) {
						$field_content .= sprintf( '<input type="text" name="%1$s[value]" value="%2$s" class="value evf-money-input" placeholder="%3$s">', $name, esc_attr( $choice['value'] ), evf_format_amount( 0 ) );
					} else {
						$field_content .= sprintf( '<input type="text" name="%1$s[value]" value="%2$s" class="value">', $name, esc_attr( $choice['value'] ) );
					}
					$field_content .= '</div>';
					$field_content .= '<a class="add" href="#"><i class="dashicons dashicons-plus-alt"></i></a>';
					$field_content .= '<a class="remove" href="#"><i class="dashicons dashicons-dismiss"></i></a>';
					$field_content .= '<div class="everest-forms-attachment-media-view">';
					$field_content .= sprintf( '<input type="hidden" class="source" name="%s[image]" value="%s">', $name, esc_url_raw( $image ) );
					$field_content .= sprintf( '<button type="button" class="upload-button button-add-media"%s>%s</button>', ! empty( $image ) ? ' style="display:none;"' : '', esc_html__( 'Upload Image', 'everest-forms' ) );
					$field_content .= '<div class="thumbnail thumbnail-image">';
					if ( ! empty( $image ) ) {
						$field_content .= sprintf( '<img class="attachment-thumb" src="%1$s">', esc_url_raw( $image ) );
					}
					$field_content .= '</div>';
					$field_content .= sprintf( '<div class="actions"%s>', empty( $image ) ? ' style="display:none;"' : '' );
					$field_content .= sprintf( '<button type="button" class="button remove-button">%1$s</button>', esc_html__( 'Remove', 'everest-forms' ) );
					$field_content .= sprintf( '<button type="button" class="button upload-button">%1$s</button>', esc_html__( 'Change image', 'everest-forms' ) );
					$field_content .= '</div>';
					$field_content .= '</div>';
					$field_content .= '</li>';
				}
				$field_content .= '</ul>';

				// Final field output.
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'choices',
						'content' => $field_label . $field_content,
					),
					false
				);
				break;

			/*
			 * Choices Images.
			 */
			case 'choices_images':
				$field_content = sprintf(
					'<div class="notice notice-warning%s"><p>%s</p></div>',
					empty( $field['choices_images'] ) ? ' hidden' : '',
					esc_html__( 'For best results, images should be square and at least 200 × 160 pixels or smaller.', 'everest-forms' )
				);

				$field_content .= $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'choices_images',
						'value'   => isset( $field['choices_images'] ) ? '1' : '0',
						'desc'    => esc_html__( 'Use image choices', 'everest-forms' ),
						'tooltip' => esc_html__( 'Check this option to enable using images with the choices.', 'everest-forms' ),
					),
					false
				);

				// Final field output.
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'choices_images',
						'content' => $field_content,
					),
					false
				);
				break;

			/**
			 * Advanced Fields.
			 */

			/*
			 * Default value.
			 */
			case 'default_value':
				$value   = ! empty( $field['default_value'] ) ? esc_attr( $field['default_value'] ) : '';
				$tooltip = esc_html__( 'Enter text for the default form field value.', 'everest-forms' );
				$toggle  = '';
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'default_value',
						'value'         => esc_html__( 'Default Value', 'everest-forms' ),
						'tooltip'       => $tooltip,
						'after_tooltip' => $toggle,
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'default_value',
						'value' => $value,
					),
					false
				);

				// Smart tag for default value.
				$exclude_fields = array( 'rating', 'number' );

				if ( ! in_array( $field['type'], $exclude_fields, true ) ) {
					$output .= '<a href="#" class="evf-toggle-smart-tag-display" data-type="other"><span class="dashicons dashicons-editor-code"></span></a>';
					$output .= '<div class="evf-smart-tag-lists" style="display: none">';
					$output .= '<div class="smart-tag-title other-tag-title">Others</div><ul class="evf-others"></ul></div>';
				}

				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'default_value',
						'content' => $output,
						'class'   => in_array( $field['type'], $exclude_fields, true ) ? '' : 'evf_smart_tag',
					),
					false
				);
				break;

			/*
			 * Advanced Options markup.
			 */
			case 'advanced-options':
				$markup = ! empty( $args['markup'] ) ? $args['markup'] : 'open';
				if ( 'open' === $markup ) {
					$override = apply_filters( 'everest_forms_advanced_options_override', false );
					$override = ! empty( $override ) ? 'style="display:' . $override . ';"' : '';
					$output   = sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-advanced everest-forms-hide closed" id="everest-forms-field-option-advanced-%s" %s>', $field['id'], $override );
					$output  .= sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<i class="handlediv"></i></a>', __( 'Advanced Options', 'everest-forms' ) );
					$output  .= '<div class="everest-forms-field-option-group-inner">';
				} else {
					$output = '</div></div>';
				}
				break;

			/*
			 * Placeholder.
			 */
			case 'placeholder':
				$value   = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
				$tooltip = esc_html__( 'Enter text for the form field placeholder.', 'everest-forms' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'placeholder',
						'value'   => esc_html__( 'Placeholder Text', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'placeholder',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'placeholder',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * CSS classes.
			 */
			case 'css':
				$toggle  = '';
				$tooltip = esc_html__( 'Enter CSS class names for this field container. Multiple class names should be separated with spaces.', 'everest-forms' );
				$value   = ! empty( $field['css'] ) ? esc_attr( $field['css'] ) : '';

				// Build output.
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'css',
						'value'         => esc_html__( 'CSS Classes', 'everest-forms' ),
						'tooltip'       => $tooltip,
						'after_tooltip' => $toggle,
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'css',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'css',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Hide Label.
			 */
			case 'label_hide':
				$value   = isset( $field['label_hide'] ) ? $field['label_hide'] : '0';
				$tooltip = esc_html__( 'Check this option to hide the form field label.', 'everest-forms' );

				// Build output.
				$output = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'label_hide',
						'value'   => $value,
						'desc'    => esc_html__( 'Hide Label', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'label_hide',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Hide Sub-Labels.
			 */
			case 'sublabel_hide':
				$value   = isset( $field['sublabel_hide'] ) ? $field['sublabel_hide'] : '0';
				$tooltip = esc_html__( 'Check this option to hide the form field sub-label.', 'everest-forms' );

				// Build output.
				$output = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'sublabel_hide',
						'value'   => $value,
						'desc'    => esc_html__( 'Hide Sub-Labels', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'sublabel_hide',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Input columns.
			 */
			case 'input_columns':
				$value   = ! empty( $field['input_columns'] ) ? esc_attr( $field['input_columns'] ) : '';
				$tooltip = esc_html__( 'Select the column layout for displaying field choices.', 'everest-forms' );
				$options = array(
					''       => esc_html__( 'One Column', 'everest-forms' ),
					'2'      => esc_html__( 'Two Columns', 'everest-forms' ),
					'3'      => esc_html__( 'Three Columns', 'everest-forms' ),
					'inline' => esc_html__( 'Inline', 'everest-forms' ),
				);

				// Build output.
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'input_columns',
						'value'   => esc_html__( 'Layout', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'select',
					$field,
					array(
						'slug'    => 'input_columns',
						'value'   => $value,
						'options' => $options,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'input_columns',
						'content' => $output,
					),
					false
				);
				break;

			/*
			 * Default.
			 */
			default:
				if ( is_callable( array( $this, $option ) ) ) {
					$this->{$option}( $field );
				}
				do_action( 'everest_forms_field_options_' . $option, $this, $field, $args );
				break;

		}

		if ( $echo ) {
			if ( in_array( $option, array( 'basic-options', 'advanced-options' ), true ) ) {
				if ( 'open' === $markup ) {
					do_action( "everest_forms_field_options_before_{$option}", $field, $this );
				}

				if ( 'close' === $markup ) {
					do_action( "everest_forms_field_options_bottom_{$option}", $field, $this );
				}

				echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				if ( 'open' === $markup ) {
					do_action( "everest_forms_field_options_top_{$option}", $field, $this );
				}

				if ( 'close' === $markup ) {
					do_action( "everest_forms_field_options_after_{$option}", $field, $this );
				}
			} else {
				echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} else {
			return $output;
		}
	}

	/**
	 * Helper function to create common field options that are used frequently
	 * in the field preview.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $option Field option to render.
	 * @param array   $field  Field data and settings.
	 * @param array   $args   Field preview arguments.
	 * @param boolean $echo   Print or return the value. Print by default.
	 *
	 * @return mixed Print or return a string.
	 */
	public function field_preview_option( $option, $field, $args = array(), $echo = true ) {
		$output = '';
		$class  = ! empty( $args['class'] ) ? evf_sanitize_classes( $args['class'] ) : '';

		switch ( $option ) {
			case 'label':
				$label  = isset( $field['label'] ) && ! empty( $field['label'] ) ? $field['label'] : '';
				$output = sprintf( '<label class="label-title %s"><span class="text">%s</span><span class="required">*</span></label>', $class, $label );
				break;

			case 'description':
				$description = isset( $field['description'] ) && ! empty( $field['description'] ) ? $field['description'] : '';
				$description = false !== strpos( $class, 'nl2br' ) ? nl2br( $description ) : $description;
				$output      = sprintf( '<div class="description %s">%s</div>', $class, $description );
				break;

			case 'choices':
				$values         = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
				$choices_fields = array( 'select', 'radio', 'checkbox', 'payment-multiple', 'payment-checkbox' );

				// Notify if choices source is currently empty.
				if ( empty( $values ) ) {
					$values = array(
						'label' => esc_html__( '(empty)', 'everest-forms' ),
					);
				}

				// Build output.
				if ( ! in_array( $field['type'], $choices_fields, true ) ) {
					break;
				}

				switch ( $field['type'] ) {
					case 'checkbox':
					case 'payment-checkbox':
						$type = 'checkbox';
						break;

					case 'select':
						$type = 'select';
						break;

					default:
						$type = 'radio';
						break;
				}

				$list_class     = array( 'widefat', 'primary-input' );
				$choices_images = ! empty( $field['choices_images'] );

				if ( $choices_images ) {
					$list_class[] = 'everest-forms-image-choices';
				}

				if ( 'select' === $type ) {
					$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
					$output      = sprintf( '<select class="%s" disabled>', evf_sanitize_classes( $list_class, true ) );

					// Optional placeholder.
					if ( ! empty( $placeholder ) ) {
						$output .= sprintf( '<option value="" class="placeholder">%s</option>', esc_html( $placeholder ) );
					}

					// Build the select options (even though user can only see 1st option).
					foreach ( $values as $value ) {
						$default  = isset( $value['default'] ) ? (bool) $value['default'] : false;
						$selected = ! empty( $placeholder ) ? '' : selected( true, $default, false );
						$output  .= sprintf( '<option %s>%s</option>', $selected, esc_html( $value['label'] ) );
					}

					$output .= '</select>';
				} else {
					$output = sprintf( '<ul class="%s">', evf_sanitize_classes( $list_class, true ) );

					// Individual checkbox/radio options.
					foreach ( $values as $value ) {
						$default     = isset( $value['default'] ) ? $value['default'] : '';
						$selected    = checked( '1', $default, false );
						$placeholder = evf()->plugin_url() . '/assets/images/everest-forms-placeholder.png';
						$image_src   = ! empty( $value['image'] ) ? esc_url( $value['image'] ) : $placeholder;
						$item_class  = array();

						if ( ! empty( $value['default'] ) ) {
							$item_class[] = 'everest-forms-selected';
						}

						if ( $choices_images ) {
							$item_class[] = 'everest-forms-image-choices-item';
						}

						$output .= sprintf( '<li class="%s">', evf_sanitize_classes( $item_class, true ) );

						if ( $choices_images ) {
							$output .= '<label>';
							$output .= sprintf( '<span class="everest-forms-image-choices-image"><img src="%s" alt="%s"%s></span>', $image_src, esc_attr( $value['label'] ), ! empty( $value['label'] ) ? ' title="' . esc_attr( $value['label'] ) . '"' : '' );
							$output .= sprintf( '<input type="%s" %s disabled>', $type, $selected );
							$output .= '<span class="everest-forms-image-choices-label">' . wp_kses_post( $value['label'] ) . '</span>';
							$output .= '</label>';
						} else {
							$output .= sprintf( '<input type="%s" %s disabled>%s', $type, $selected, $value['label'] );
						}

						$output .= '</li>';
					}

					$output .= '</ul>';
				}
				break;
		}

		if ( $echo ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $output;
		}
	}

	/**
	 * Create a new field in the admin AJAX editor.
	 *
	 * @since 1.0.0
	 */
	public function field_new() {
		// Run a security check.
		check_ajax_referer( 'everest_forms_field_drop', 'security' );

		// Check for permissions.
		if ( ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			die( esc_html__( 'You do no have permission.', 'everest-forms' ) );
		}

		// Check for form ID.
		if ( ! isset( $_POST['form_id'] ) || empty( $_POST['form_id'] ) ) {
			die( esc_html__( 'No form ID found', 'everest-forms' ) );
		}

		// Check for field type to add.
		if ( ! isset( $_POST['field_type'] ) || empty( $_POST['field_type'] ) ) {
			die( esc_html__( 'No field type found', 'everest-forms' ) );
		}

		// Grab field data.
		$field_args     = ! empty( $_POST['defaults'] ) ? (array) wp_unslash( $_POST['defaults'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$field_type     = esc_attr( wp_unslash( $_POST['field_type'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$field_id       = evf()->form->field_unique_key( wp_unslash( $_POST['form_id'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$field          = array(
			'id'          => $field_id,
			'type'        => $field_type,
			'label'       => $this->name,
			'description' => '',
		);
		$field          = wp_parse_args( $field_args, $field );
		$field          = apply_filters( 'everest_forms_field_new_default', $field );
		$field_required = apply_filters( 'everest_forms_field_new_required', '', $field );
		$field_class    = apply_filters( 'everest_forms_field_new_class', '', $field );

		// Field types that default to required.
		if ( ! empty( $field_required ) ) {
			$field_required    = 'required';
			$field['required'] = '1';
		}

		// Build Preview.
		ob_start();
		$this->field_preview( $field );
		$preview      = sprintf( '<div class="everest-forms-field everest-forms-field-%s %s %s" id="everest-forms-field-%s" data-field-id="%s" data-field-type="%s">', $field_type, $field_required, $field_class, $field['id'], $field['id'], $field_type );
			$preview .= sprintf( '<div class="evf-field-action">' );
			$preview .= sprintf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', __( 'Duplicate Field', 'everest-forms' ) );
			$preview .= sprintf( '<a href="#" class="everest-forms-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', __( 'Delete Field', 'everest-forms' ) );
			$preview .= sprintf( '<a href="#" class="everest-forms-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', __( 'Settings', 'everest-forms' ) );
			$preview .= sprintf( '</div>' );
			$preview .= ob_get_clean();
		$preview     .= '</div>';

		// Build Options.
		$options      = sprintf( '<div class="everest-forms-field-option everest-forms-field-option-%s" id="everest-forms-field-option-%s" data-field-id="%s">', esc_attr( $field['type'] ), $field['id'], $field['id'] );
			$options .= sprintf( '<input type="hidden" name="form_fields[%s][id]" value="%s" class="everest-forms-field-option-hidden-id">', $field['id'], $field['id'] );
			$options .= sprintf( '<input type="hidden" name="form_fields[%s][type]" value="%s" class="everest-forms-field-option-hidden-type">', $field['id'], esc_attr( $field['type'] ) );
			ob_start();
			$this->field_options( $field );
			$options .= ob_get_clean();
		$options     .= '</div>';

		$form_field_array = explode( '-', $field_id );
		$field_id_int     = absint( $form_field_array[ count( $form_field_array ) - 1 ] );

		// Prepare to return compiled results.
		wp_send_json_success(
			array(
				'form_id'       => (int) $_POST['form_id'],
				'field'         => $field,
				'preview'       => $preview,
				'options'       => $options,
				'form_field_id' => ( $field_id_int + 1 ),
			)
		);
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
	public function field_display( $field, $field_atts, $form_data ) {}

	/**
	 * Display field input errors if present.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Input key.
	 * @param array  $field Field data and settings.
	 */
	public function field_display_error( $key, $field ) {
		// Need an error.
		if ( empty( $field['properties']['error']['value'][ $key ] ) ) {
			return;
		}

		printf(
			'<label class="everest-forms-error evf-error" for="%s">%s</label>',
			esc_attr( $field['properties']['inputs'][ $key ]['id'] ),
			esc_html( $field['properties']['error']['value'][ $key ] )
		);
	}

	/**
	 * Display field input sublabel if present.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key      Input key.
	 * @param string $position Sublabel position.
	 * @param array  $field    Field data and settings.
	 */
	public function field_display_sublabel( $key, $position, $field ) {
		// Need a sublabel value.
		if ( empty( $field['properties']['inputs'][ $key ]['sublabel']['value'] ) ) {
			return;
		}

		$pos    = ! empty( $field['properties']['inputs'][ $key ]['sublabel']['position'] ) ? $field['properties']['inputs'][ $key ]['sublabel']['position'] : 'after';
		$hidden = ! empty( $field['properties']['inputs'][ $key ]['sublabel']['hidden'] ) ? 'everest-forms-sublabel-hide' : '';

		if ( $pos !== $position ) {
			return;
		}

		printf(
			'<label for="%s" class="everest-forms-field-sublabel %s %s">%s</label>',
			esc_attr( $field['properties']['inputs'][ $key ]['id'] ),
			sanitize_html_class( $pos ),
			$hidden, // phpcs:ignore WordPress.Security.EscapeOutput
			evf_string_translation( (int) $this->form_data['id'], $field['id'], $field['properties']['inputs'][ $key ]['sublabel']['value'], '-sublabel-' . $key ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}

	/**
	 * Validates field on form submit.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Data.
	 * @param array  $form_data All Form Data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$field_type         = isset( $form_data['form_fields'][ $field_id ]['type'] ) ? $form_data['form_fields'][ $field_id ]['type'] : '';
		$required_field     = isset( $form_data['form_fields'][ $field_id ]['required'] ) ? $form_data['form_fields'][ $field_id ]['required'] : false;
		$conditional_status = isset( $form_data['form_fields'][ $field_id ]['conditional_logic_status'] ) ? $form_data['form_fields'][ $field_id ]['conditional_logic_status'] : 0;

		// Basic required check - If field is marked as required, check for entry data.
		if ( false !== $required_field && '1' !== $conditional_status && ( empty( $field_submit ) && '0' !== $field_submit ) ) {
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = evf_get_required_label();
			update_option( 'evf_validation_error', 'yes' );
		}

		// Type validations.
		switch ( $field_type ) {
			case 'url':
				if ( ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) && filter_var( $field_submit, FILTER_VALIDATE_URL ) === false ) { // phpcs:ignore WordPress.Security.NonceVerification
					$validation_text = get_option( 'evf_' . $field_type . '_validation', esc_html__( 'Please enter a valid url', 'everest-forms' ) );
				}
				break;
			case 'email':
				if ( is_array( $field_submit ) ) {
					$value = ! empty( $field_submit['primary'] ) ? $field_submit['primary'] : '';
				} else {
					$value = ! empty( $field_submit ) ? $field_submit : '';
				}
				if ( ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) && ! is_email( $value ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$validation_text = get_option( 'evf_' . $field_type . '_validation', esc_html__( 'Please enter a valid email address', 'everest-forms' ) );
				}
				break;
			case 'number':
				if ( ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) && ! is_numeric( $field_submit ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$validation_text = get_option( 'evf_' . $field_type . '_validation', esc_html__( 'Please enter a valid number', 'everest-forms' ) );
				}
				break;
		}

		if ( isset( $validation_text ) ) {
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_type_validation', $validation_text );
			update_option( 'evf_validation_error', 'yes' );
		}
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
		if ( is_array( $field_submit ) ) {
			$field_submit = array_filter( $field_submit );
			$field_submit = implode( "\r\n", $field_submit );
		}

		$name = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? make_clickable( $form_data['form_fields'][ $field_id ]['label'] ) : '';

		// Sanitize but keep line breaks.
		$value = evf_sanitize_textarea_field( $field_submit );

		evf()->task->form_fields[ $field_id ] = array(
			'name'     => $name,
			'value'    => $value,
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}

	/**
	 * Field with limit.
	 *
	 * @param  array $field Field to check.
	 * @return boolean
	 */
	protected function field_is_limit( $field ) {
		if ( in_array( $field['type'], array( 'text', 'textarea' ), true ) ) {
			return isset( $field['limit_enabled'] ) && ! empty( $field['limit_count'] );
		}
	}
}
