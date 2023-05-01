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
		$this->class   = $this->is_pro ? 'upgrade-modal' : $this->class;
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification

		// Init hooks.
		$this->init_hooks();

		// Hooks.
		add_action( 'everest_forms_builder_fields_options_' . $this->type, array( $this, 'field_options' ) );
		add_action( 'everest_forms_builder_fields_preview_' . $this->type, array( $this, 'field_preview' ) );
		add_action( 'wp_ajax_everest_forms_new_field_' . $this->type, array( $this, 'field_new' ) );
		add_action( 'everest_forms_display_field_' . $this->type, array( $this, 'field_display' ), 10, 3 );
		add_action( 'everest_forms_display_edit_form_field_' . $this->type, array( $this, 'edit_form_field_display' ), 10, 3 );
		add_action( 'everest_forms_process_validate_' . $this->type, array( $this, 'validate' ), 10, 3 );
		add_action( 'everest_forms_process_format_' . $this->type, array( $this, 'format' ), 10, 4 );
		add_filter( 'everest_forms_field_properties', array( $this, 'field_prefill_value_property' ), 10, 3 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
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
		$settings = apply_filters( 'everest_forms_builder_fields_option', $this->get_field_settings() );

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
	 *
	 * @param array $field Field data and settings.
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
				$output = sprintf( '<div class="everest-forms-field-option-row everest-forms-field-option-row-%s %s" id="everest-forms-field-option-row-%s-%s" data-field-id="%s" %s>%s</div>', $slug, $class, $id, $slug, $id, $data, $args['content'] );
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
				$options     = $args['options'];
				$value       = isset( $args['value'] ) ? $args['value'] : '';
				$is_multiple = isset( $args['multiple'] ) && true === $args['multiple'];

				if ( true === $is_multiple ) {
					$output = sprintf( '<select class="widefat %s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s][]" %s multiple>', $class, $id, $slug, $id, $slug, $data );
				} else {
					$output = sprintf( '<select class="widefat %s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" %s >', $class, $id, $slug, $id, $slug, $data );
				}

				foreach ( $options as $key => $option_value ) {

					if ( true === $is_multiple && is_array( $value ) ) {
						$selected_value = in_array( $key, $value, true ) ? 'selected="selected"' : '';
					} else {
						$selected_value = ( $value == $key ) ? 'selected="selected"' : '';
					}
					$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $selected_value, $option_value );
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
			echo wp_kses( $output, evf_get_allowed_html_tags( 'builder' ) );
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
		$markup = ! empty( $args['markup'] ) ? $args['markup'] : 'open';
		$class  = ! empty( $args['class'] ) ? esc_html( $args['class'] ) : '';

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "everest_forms_field_options_before_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "everest_forms_field_options_bottom_{$option}", $field, $this );
		}

		switch ( $option ) {
			/**
			 * Basic Fields.
			 */

			/*
			 * Basic Options markup.
			 */
			case 'basic-options':
				if ( 'open' === $markup ) {
					if ( $echo ) {
						echo sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-basic open" id="everest-forms-field-option-basic-%s">', esc_attr( $field['id'] ) );
						echo sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<span> (ID #%s)</span> <i class="handlediv"></i></a>', esc_html( $this->name ), esc_html( $field['id'] ) );
						echo sprintf( '<div class="everest-forms-field-option-group-inner %s">', esc_attr( $class ) );
					} else {
						$output  = sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-basic open" id="everest-forms-field-option-basic-%s">', $field['id'] );
						$output .= sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<span> (ID #%s)</span> <i class="handlediv"></i></a>', $this->name, $field['id'] );
						$output .= sprintf( '<div class="everest-forms-field-option-group-inner %s">', $class );
					}
				} else {
					if ( $echo ) {
						echo '</div></div>';
					} else {
						$output = '</div></div>';
					}
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
					$echo
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
					$echo
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
					$echo
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
					$echo
				);
				break;

			/*
			 * Field Required toggle.
			 */
			case 'required_field_message_setting':
				$default = ! empty( $args['default'] ) ? $args['default'] : 'global';
				if ( isset( $field['required_field_message_setting'] ) ) {
					if ( 'global' === $field['required_field_message_setting'] ) {
						$value = $field['required_field_message_setting'];
					} elseif ( 'individual' === $field['required_field_message_setting'] ) {
						$value = $field['required_field_message_setting'];
					}
				} elseif ( ! empty( $field['required-field-message'] ) ) {
					$value = 'individual';
				} else {
					$value = $default;
				}
				$output = $this->field_element(
					'radio',
					$field,
					array(
						'slug'    => 'required_field_message_setting',
						'default' => $value,
						'desc'    => '',
						'options' => array(
							'global'     => esc_html__( 'Show Required Message From Global Setting', 'everest-forms' ),
							'individual' => esc_html__( 'Custom Required Message', 'everest-forms' ),
						),
					),
					false
				);
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'required_field_message_setting',
						'class'   => isset( $field['required'] ) ? '' : 'hidden',
						'content' => $output,
					),
					$echo
				);
				break;
			/**
			 * Required Field Message.
			 */
			case 'required_field_message':
				$has_sub_fields      = false;
				$sub_fields          = array();
				$required_validation = get_option( 'everest_forms_required_validation' );
				if ( in_array( $field['type'], array( 'number', 'email', 'url', 'phone' ), true ) ) {
					$required_validation = get_option( 'everest_forms_' . $field['type'] . '_validation' );
				}
				$hidden = true;
				if ( isset( $field['required_field_message_setting'], $field['required'] ) ) {
					if ( 'global' === $field['required_field_message_setting'] ) {
						$hidden = true;
					} elseif ( 'individual' === $field['required_field_message_setting'] ) {
						$hidden = false;
					}
				} elseif ( ! empty( $field['required-field-message'] ) && isset( $field['required'] ) ) {
					$hidden = false;
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
								'value' => isset( $field[ $row_slug ] ) ? esc_attr( $field[ $row_slug ] ) : '',
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
								'value' => isset( $field['required-field-message-address1'] ) ? esc_attr( $field['required-field-message-address1'] ) : '',
							),
						),
						'required-field-message-city'     => array(
							'label' => array(
								'value'   => esc_html__( 'City', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for City if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-city'] ) ? esc_attr( $field['required-field-message-city'] ) : '',
							),
						),
						'required-field-message-state'    => array(
							'label' => array(
								'value'   => esc_html__( 'State / Province / Region', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for State/Province/Region if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-state'] ) ? esc_attr( $field['required-field-message-state'] ) : '',
							),
						),
						'required-field-message-postal'   => array(
							'label' => array(
								'value'   => esc_html__( 'Zip / Postal Code', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for Zip/Postal Code if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-postal'] ) ? esc_attr( $field['required-field-message-postal'] ) : '',
							),
						),
						'required-field-message-country'  => array(
							'label' => array(
								'value'   => esc_html__( 'Country', 'everest-forms' ),
								'tooltip' => esc_html__( 'Enter a message to show for Country if it\'s required.', 'everest-forms' ),
							),
							'text'  => array(
								'value' => isset( $field['required-field-message-country'] ) ? esc_attr( $field['required-field-message-country'] ) : '',
							),
						),
					);
				}

				if ( true === $has_sub_fields ) {
					$sub_field_output_array = array();
					foreach ( $sub_fields as $sub_field_slug => $sub_field_data ) {
						$value   = isset( $field['required-field-message'] ) ? esc_attr( $field['required-field-message'] ) : '';
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
							'class'   => false === $hidden ? '' : 'hidden',
							'content' => $output,
						),
						$echo
					);
				} else {
					$value   = isset( $field['required-field-message'] ) ? esc_attr( $field['required-field-message'] ) : '';
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
							'class' => 'everest-forms-field-option-row',
						),
						false
					);
					$output  = $this->field_element(
						'row',
						$field,
						array(
							'slug'    => 'required-field-message',
							'class'   => false === $hidden ? '' : 'hidden',
							'content' => $output,
						),
						$echo
					);
				}
				break;

			/**
			 * Field Visibilty.
			 */
			case 'field_visiblity':
				$default        = ! empty( $args['default'] ) ? $args['default'] : '0';
				$readonly_value = isset( $field['readonly_field_visibility'] ) ? $field['readonly_field_visibility'] : $default;
				$hidden_value   = isset( $field['hidden_field_visibility'] ) ? $field['hidden_field_visibility'] : $default;
				$tooltip        = esc_html__( 'Check this option to mark the field readonly and hidden.', 'everest-forms' );
				$label          = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'field_visibility',
						'value'   => esc_html__( 'Field Visibility', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$readonly       = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'  => 'readonly_field_visibility',
						'value' => $readonly_value,
						'class' => 'field_visibility_readonly',
						'desc'  => esc_html__( 'Readonly ', 'everest-forms' ),
					),
					false
				);
				$hidden         = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'  => 'hidden_field_visibility',
						'value' => $hidden_value,
						'class' => 'field_visibility_hidden',
						'desc'  => esc_html__( 'Hidden', 'everest-forms' ),
					),
					false
				);
				$output         = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'field_visiblity',
						'content' => $label . '' . $readonly . ' ' . $hidden,
					),
					$echo
				);
				break;

			/**
			 * No Duplicates.
			 */
			case 'no_duplicates':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = ! empty( $field['no_duplicates'] ) ? esc_attr( $field['no_duplicates'] ) : '';
				$tooltip = esc_html__( 'Select this option to limit user input to unique values only. This will require that a value entered in a field does not currently exist in the entry database for that field..', 'everest-forms' );
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'no_duplicates',
						'value'   => $value,
						'desc'    => esc_html__( 'Validate as unique', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'no_duplicates',
						'content' => $output,
					),
					$echo
				);
				break;
			case 'validate_message':
				$toggle  = '';
				$tooltip = esc_html__( 'if the form submission failed it will show this message.', 'everest-forms' );
				$value   = ! empty( $field['validate_message'] ) ? esc_attr( $field['validate_message'] ) : 'This field value needs to be unique.';

				// Build output.
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'validate_message',
						'value'         => esc_html__( 'Validation Message for Duplicate', 'everest-forms' ),
						'tooltip'       => $tooltip,
						'after_tooltip' => $toggle,
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'validate_message',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'validate_message',
						'content' => $output,
						'class'   => isset( $field['no_duplicates'] ) ? '' : 'hidden',
					),
					$echo
				);
				break;
			/**
			 * No Duplicates.
			 */
			case 'show_tooltip':
				$default           = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value             = ! empty( $field['show_tooltip'] ) ? esc_attr( $field['show_tooltip'] ) : '';
				$output            = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'show_tooltip',
						'value'   => $value,
						'desc'    => esc_html__( 'Enable Tooltip', 'everest-forms' ),
						'tooltip' => esc_html__( 'Check this option to show the form field tooltip.', 'everest-forms' ),
					),
					false
				);
				$output            = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'show_tooltip',
						'content' => $output,
					),
					$echo
				);
				$description_value = ! empty( $field['tooltip_description'] ) ? esc_attr( $field['tooltip_description'] ) : '';
				$output            = $this->field_element(
					'textarea',
					$field,
					array(
						'slug'  => 'tooltip_description',
						'value' => $description_value,
						'desc'  => esc_html__( 'Enable Tooltip', 'everest-forms' ),
						'attrs' => array(
							'placeholder' => esc_html__( 'Enter the text for tooltip description.', 'everest-forms' ),
						),
					),
					false
				);

				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'tooltip_description',
						'content' => $output,
					),
					$echo
				);
				break;
			/**
			 * No Duplicates.
			 */
			case 'autocomplete_address':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = ! empty( $field['autocomplete_address'] ) ? esc_attr( $field['autocomplete_address'] ) : '';
				$tooltip = esc_html__( 'Check this option to autofill address field.', 'everest-forms' );
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'autocomplete_address',
						'value'   => $value,
						'desc'    => esc_html__( 'Enable Autocomplete Address Field', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'autocomplete_address',
						'content' => $output,
					),
					$echo
				);
				break;
			case 'address_style':
				$default = ! empty( $args['default'] ) ? $args['default'] : 'none';
				$tooltip = esc_html__( 'Select the style', 'everest-forms' );
				$value   = ! empty( $field['address_style'] ) ? esc_attr( $field['address_style'] ) : '';
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'address_style',
						'value'   => esc_html__( 'Style', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'select',
					$field,
					array(
						'slug'    => 'address_style',
						'value'   => $value,
						'tooltip' => $tooltip,
						'options' => array(
							'address'          => esc_html__( 'Address', 'everest-forms' ),
							'map'              => esc_html__( 'Map', 'everest-forms' ),
							'address_with_map' => esc_html( 'Address With Map' ),
						),
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'address_style',
						'content' => $output,
					),
					$echo
				);
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
					$echo
				);
				break;

			/*
			 * Choices.
			 */
			case 'choices':
				$class      = array();
				$label      = ! empty( $args['label'] ) ? esc_html( $args['label'] ) : esc_html__( 'Choices', 'everest-forms' );
				$choices    = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
				$input_type = in_array( $field['type'], array( 'radio', 'select', 'payment-multiple' ), true ) ? 'radio' : 'checkbox';

				if ( ! empty( $field['show_values'] ) ) {
					$class[] = 'show-values';
				}

				if ( ! empty( $field['choices_images'] ) ) {
					$class[] = 'show-images';
				}

				if ( ! empty( $field['multiple_choices'] ) ) {
					$input_type = 'checkbox';
				}

				// Add bulk options toggle handle.
				$bulk_add_enabled           = apply_filters( 'evf_bulk_add_enabled', true );
				$licensed                   = ( false === evf_get_license_plan() ) ? false : true;
				$upgradable_feature_class   = ( true === $licensed ) ? '' : 'evf-upgradable-feature';
				$bulk_options_toggle_handle = sprintf( '<a href="#" class="evf-toggle-bulk-options after-label-description %s">%s</a>', esc_attr( $upgradable_feature_class ), esc_html__( 'Bulk Add', 'everest-forms' ) );

				// Field label.
				$field_label   = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'choices',
						'value'         => $label,
						'tooltip'       => esc_html__( 'Add choices for the form field.', 'everest-forms' ),
						'after_tooltip' => $bulk_options_toggle_handle, // @todo Bulk import and export for choices.
					)
				);
				$field_content = '';

				if ( true === $bulk_add_enabled && true === $licensed ) {
					$field_content .= $this->field_option(
						'add_bulk_options',
						$field,
						array(
							'class' => 'everest-forms-hidden',
						)
					);
				}

				if ( 'select' === $field['type'] ) {
					$selection_btn   = array();
					$selection_types = array(
						'single'   => array(
							'type'  => 'radio',
							'label' => esc_html__( 'Single Selection', 'everest-forms' ),
						),
						'multiple' => array(
							'type'  => 'checkbox',
							'label' => esc_html__( 'Multiple Selection', 'everest-forms' ),
						),
					);

					$active_type = ! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices'] ? 'multiple' : 'single';
					foreach ( $selection_types as $key => $selection_type ) {
						$selection_btn[ $key ] = '<span data-selection="' . esc_attr( $key ) . '" data-type="' . esc_attr( $selection_type['type'] ) . '" class="flex everest-forms-btn ' . ( $active_type === $key ? 'is-active' : '' ) . ' ' . ( false === $licensed && 'multiple' === $key ? 'upgrade-modal' : '' ) . '" data-feature="' . esc_html__( 'Multiple selection', 'everest-forms' ) . '">' . esc_html( $selection_type['label'] ) . '</span>';
					}

					$field_content .= sprintf(
						'<div class="flex everest-forms-btn-group everest-forms-btn-group--inline"><input type="hidden" id="everest-forms-field-option-%1$s-multiple_choices" name="form_fields[%1$s][multiple_choices]" value="%2$s" />%3$s</div>',
						esc_attr( $field['id'] ),
						! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices'] ? 1 : 0,
						implode( '', $selection_btn )
					);
				}

				// Field contents.
				$field_content .= sprintf(
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
					$echo
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
					$echo
				);
				break;

			/**
			 * Add bulk options.
			 */
			case 'add_bulk_options':
				$class = ! empty( $args['class'] ) ? esc_attr( $args['class'] ) : '';
				$label = ! empty( $args['label'] ) ? esc_html( $args['label'] ) : esc_html__( 'Add Bulk Options', 'everest-forms' );

				// Field label.
				$field_label = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'add_bulk_options',
						'value'         => $label,
						'tooltip'       => esc_html__( 'Add multiple options at once.', 'everest-forms' ),
						'after_tooltip' => sprintf( '<a class="evf-toggle-presets-list" href="#">%s</a>', esc_html__( 'Presets', 'everest-forms' ) ),
					),
					false
				);

				// Preset contents.
				$presets      = array(
					array(
						'label'   => esc_html__( 'Months', 'everest-forms' ),
						'class'   => 'evf-options-preset-months',
						'options' => array(
							esc_html__( 'January', 'everest-forms' ),
							esc_html__( 'February', 'everest-forms' ),
							esc_html__( 'March', 'everest-forms' ),
							esc_html__( 'April', 'everest-forms' ),
							esc_html__( 'May', 'everest-forms' ),
							esc_html__( 'June', 'everest-forms' ),
							esc_html__( 'July', 'everest-forms' ),
							esc_html__( 'August', 'everest-forms' ),
							esc_html__( 'September', 'everest-forms' ),
							esc_html__( 'October', 'everest-forms' ),
							esc_html__( 'November', 'everest-forms' ),
							esc_html__( 'December', 'everest-forms' ),
						),
					),
					array(
						'label'   => esc_html__( 'Week Days', 'everest-forms' ),
						'class'   => 'evf-options-preset-week-days',
						'options' => array(
							esc_html__( 'Sunday', 'everest-forms' ),
							esc_html__( 'Monday', 'everest-forms' ),
							esc_html__( 'Tuesday', 'everest-forms' ),
							esc_html__( 'Wednesday', 'everest-forms' ),
							esc_html__( 'Thursday', 'everest-forms' ),
							esc_html__( 'Friday', 'everest-forms' ),
							esc_html__( 'Saturday', 'everest-forms' ),
						),
					),
					array(
						'label'   => esc_html__( 'Countries', 'everest-forms' ),
						'class'   => 'evf-options-preset-countries',
						'options' => array_values( evf_get_countries() ),
					),
					array(
						'label'   => esc_html__( 'Countries Postal Code', 'everest-forms' ),
						'class'   => 'evf-options-preset-countries-postal-code',
						'options' => array_keys( evf_get_countries() ),
					),
					array(
						'label'   => esc_html__( 'U.S. States', 'everest-forms' ),
						'class'   => 'evf-options-preset-states',
						'options' => array_values( evf_get_states()['US'] ),
					),
					array(
						'label'   => esc_html__( 'U.S. States Postal Code', 'everest-forms' ),
						'class'   => 'evf-options-preset-states-postal-code',
						'options' => array_keys( evf_get_states()['US'] ),
					),
					array(
						'label'   => esc_html__( 'Age Groups', 'everest-forms' ),
						'class'   => 'evf-options-preset-age-groups',
						'options' => array(
							esc_html__( 'Under 18', 'everest-forms' ),
							esc_html__( '18-24', 'everest-forms' ),
							esc_html__( '25-34', 'everest-forms' ),
							esc_html__( '35-44', 'everest-forms' ),
							esc_html__( '45-54', 'everest-forms' ),
							esc_html__( '55-64', 'everest-forms' ),
							esc_html__( '65 or Above', 'everest-forms' ),
							esc_html__( 'Prefer Not to Answer', 'everest-forms' ),
						),
					),
					array(
						'label'   => esc_html__( 'Satisfaction', 'everest-forms' ),
						'class'   => 'evf-options-preset-satisfaction',
						'options' => array(
							esc_html__( 'Very Satisfied', 'everest-forms' ),
							esc_html__( 'Satisfied', 'everest-forms' ),
							esc_html__( 'Neutral', 'everest-forms' ),
							esc_html__( 'Unsatisfied', 'everest-forms' ),
							esc_html__( 'Very Unsatisfied', 'everest-forms' ),
							esc_html__( 'N/A', 'everest-forms' ),
						),
					),
					array(
						'label'   => esc_html__( 'Importance', 'everest-forms' ),
						'class'   => 'evf-options-preset-importance',
						'options' => array(
							esc_html__( 'Very Important', 'everest-forms' ),
							esc_html__( 'Important', 'everest-forms' ),
							esc_html__( 'Neutral', 'everest-forms' ),
							esc_html__( 'Somewhat Important', 'everest-forms' ),
							esc_html__( 'Not at all Important', 'everest-forms' ),
							esc_html__( 'N/A', 'everest-forms' ),
						),
					),
					array(
						'label'   => esc_html__( 'Agreement', 'everest-forms' ),
						'class'   => 'evf-options-preset-agreement',
						'options' => array(
							esc_html__( 'Strongly Agree', 'everest-forms' ),
							esc_html__( 'Agree', 'everest-forms' ),
							esc_html__( 'Neutral', 'everest-forms' ),
							esc_html__( 'Disagree', 'everest-forms' ),
							esc_html__( 'Strongly Disagree', 'everest-forms' ),
							esc_html__( 'N/A', 'everest-forms' ),
						),
					),
				);
				$presets_html = '<div class="evf-options-presets" hidden>';
				foreach ( $presets as $preset ) {
					$presets_html .= sprintf( '<div class="evf-options-preset %s">', esc_attr( $preset['class'] ) );
					$presets_html .= sprintf( '<a class="evf-options-preset-label" href="#">%s</a>', $preset['label'] );
					$presets_html .= sprintf( '<textarea hidden class="evf-options-preset-value">%s</textarea>', implode( "\n", $preset['options'] ) );
					$presets_html .= '</div>';
				}
				$presets_html .= '</div>';

				// Field contents.
				$field_content  = $this->field_element(
					'textarea',
					$field,
					array(
						'slug'  => 'add_bulk_options',
						'value' => '',
					),
					false
				);
				$field_content .= sprintf( '<a class="button button-small evf-add-bulk-options" href="#">%s</a>', esc_html__( 'Add New Choices', 'everest-forms' ) );

				// Final field output.
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'add_bulk_options',
						'content' => $field_label . $presets_html . $field_content,
						'class'   => $class,
					),
					$echo
				);
				break;

			/**
			 * Advanced Fields.
			 */

			/*
			 * Default value.
			 */
			case 'default_value':
				$value   = ! empty( $field['default_value'] ) || ( isset( $field['default_value'] ) && '0' === (string) $field['default_value'] ) ? esc_attr( $field['default_value'] ) : '';
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
				$exclude_fields = array( 'rating', 'number', 'range-slider', 'payment-quantity', 'reset' );

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
					$echo
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
					if ( $echo ) {
						echo sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-advanced everest-forms-hide closed" id="everest-forms-field-option-advanced-%s" %s>', esc_attr( $field['id'] ), ( ! empty( $override ) ? 'style="display:' . esc_attr( apply_filters( 'everest_forms_advanced_options_override', false ) ) . ';"' : '' ) );
						echo sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<i class="handlediv"></i></a>', esc_html__( 'Advanced Options', 'everest-forms' ) );
						echo '<div class="everest-forms-field-option-group-inner">';
					} else {
						$output  = sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-advanced everest-forms-hide closed" id="everest-forms-field-option-advanced-%s" %s>', $field['id'], $override );
						$output .= sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<i class="handlediv"></i></a>', __( 'Advanced Options', 'everest-forms' ) );
						$output .= '<div class="everest-forms-field-option-group-inner">';
					}
				} else {
					if ( $echo ) {
						echo '</div></div>';
					} else {
						$output = '</div></div>';
					}
				}

				break;

			/*
			 * Placeholder.
			 */
			case 'placeholder':
				$value   = ! empty( $field['placeholder'] ) || ( isset( $field['placeholder'] ) && '0' === (string) $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
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
					$echo
				);
				break;
			case 'enable_prepopulate':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = ! empty( $field['enable_prepopulate'] ) ? esc_attr( $field['enable_prepopulate'] ) : '';
				$tooltip = esc_html__( 'Enable this option to allow field to be populated dynamically', 'everest-forms' );
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'enable_prepopulate',
						'value'   => $value,
						'desc'    => esc_html__( 'Enable Autopopulate ', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'enable_prepopulate',
						'content' => $output,
					),
					$echo
				);
				break;
			case 'parameter_name':
					$toggle  = '';
					$tooltip = esc_html__( 'Enter name of the parameter to populate the field.', 'everest-forms' );
					$value   = ! empty( $field['parameter_name'] ) ? esc_attr( $field['parameter_name'] ) : '';

					// Build output.
					$output  = $this->field_element(
						'label',
						$field,
						array(
							'slug'          => 'parameter_name',
							'value'         => esc_html__( 'Parameter Name', 'everest-forms' ),
							'tooltip'       => $tooltip,
							'after_tooltip' => $toggle,
						),
						false
					);
					$output .= $this->field_element(
						'text',
						$field,
						array(
							'slug'  => 'parameter_name',
							'value' => $value,
						),
						false
					);
					$output  = $this->field_element(
						'row',
						$field,
						array(
							'slug'    => 'parameter_name',
							'content' => $output,
							'class'   => isset( $field['enable_prepopulate'] ) ? '' : 'hidden',
						),
						$echo
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
					$echo
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
					$echo
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
					$echo
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
					$echo
				);
				break;

			/**
			* Whitelisted Domain.
			*/
			case 'whitelist_domain':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				// $value for just backward compatibility.
				$value           = ( isset( $field['whitelist_domain'] ) && ! empty( $field['whitelist_domain'] ) ) ? esc_attr( $field['whitelist_domain'] ) : '';
				$allowed_domains = ( isset( $field['allowed_domains'] ) && ! empty( $field['allowed_domains'] ) ) ? esc_attr( $field['allowed_domains'] ) : ( ( isset( $field['select_whitelist'] ) && 'allow' === $field['select_whitelist'] ) ? $value : '' );
				$denied_domains  = ( isset( $field['denied_domains'] ) && ! empty( $field['denied_domains'] ) ) ? esc_attr( $field['denied_domains'] ) : ( ( isset( $field['select_whitelist'] ) && 'deny' === $field['select_whitelist'] ) ? $value : '' );
				$style           = ( isset( $field['select_whitelist'] ) && ! empty( $field['select_whitelist'] ) ) ? esc_attr( $field['select_whitelist'] ) : 'allow';
				$tooltip         = esc_html__( 'Please enter valid allowed or denied domains, separated by commas. For example: google.com, yahoo.com', 'everest-forms' );
				$output          = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'whitelist_domain',
						'value'   => esc_html__( 'Whitelisted Domains', 'everest-forms' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output         .= $this->field_element(
					'select',
					$field,
					array(
						'slug'    => 'select_whitelist',
						'value'   => $style,
						'options' => array(
							'allow' => esc_html__( 'Allowed Domains', 'everest-forms' ),
							'deny'  => esc_html__( 'Denied Domains', 'everest-forms' ),
						),
					),
					false
				);
				$output          = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'whitelist_domain',
						'content' => $output,
					),
					$echo
				);

				$output .= $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'allowed_domains',
						'content' => $this->field_element(
							'text',
							$field,
							array(
								'slug'        => 'allowed_domains',
								'value'       => esc_attr( $allowed_domains ),
								'placeholder' => esc_attr__( 'Allowed Domain(s)', 'everest-forms' ),
							),
							false
						),
					),
					$echo
				);
				$output .= $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'denied_domains',
						'content' => $this->field_element(
							'text',
							$field,
							array(
								'slug'        => 'denied_domains',
								'value'       => esc_attr( $denied_domains ),
								'placeholder' => esc_attr__( 'Denied Domain(s)', 'everest-forms' ),
							),
							false
						),
					),
					$echo
				);
				break;

			/**
			 * Select All.
			 */
			case 'select_all':
				$value   = isset( $field['select_all'] ) ? '1' : '0';
				$tooltip = esc_html__( 'Check this option to hide the form field label.', 'everest-forms' );

				$output = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'select_all',
						'value'   => $value,
						'class'   => 'evf-select-all-chk',
						'desc'    => esc_html__( 'Select All', 'everest-forms' ),
						'tooltip' => esc_html__( 'Check this option to select all the options.', 'everest-forms' ),
					),
					false
				);
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'select_all',
						'content' => $output,
					),
					$echo
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

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "everest_forms_field_options_top_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "everest_forms_field_options_after_{$option}", $field, $this );
		}

		if ( ! $echo ) {
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
		$output    = '';
		$class     = ! empty( $args['class'] ) ? evf_sanitize_classes( $args['class'] ) : '';
		$form_id   = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_data = evf()->form->get( absint( $form_id ), array( 'content_only' => true ) );
		$markup    = '';

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "everest_forms_field_options_before_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "everest_forms_field_options_bottom_{$option}", $field, $this );
		}

		switch ( $option ) {
			case 'label':
				$label = isset( $field['label'] ) && ! empty( $field['label'] ) ? $field['label'] : '';
				if ( $echo ) {
					echo sprintf( '<label class="label-title %s"><span class="text">%s</span><span class="required">%s</span></label>', esc_attr( $class ), esc_html( $label ), esc_html( apply_filters( 'everest_form_get_required_type', '*', $field, $form_data ) ) );
				} else {
					$output = sprintf( '<label class="label-title %s"><span class="text">%s</span><span class="required">%s</span></label>', $class, $label, apply_filters( 'everest_form_get_required_type', '*', $field, $form_data ) );
				}
				break;

			case 'description':
				$description = isset( $field['description'] ) && ! empty( $field['description'] ) ? $field['description'] : '';
				$description = false !== strpos( $class, 'nl2br' ) ? nl2br( $description ) : $description;
				if ( $echo ) {
					echo sprintf( '<div class="description %s">%s</div>', esc_attr( $class ), esc_html( $description ) );
				} else {
					$output = sprintf( '<div class="description %s">%s</div>', $class, $description );
				}
				break;

			case 'repeater_fields':
				$repeater_fields = isset( $field['repeater_fields'] ) && ! empty( $field['repeater_fields'] ) ? $field['repeater_fields'] : '';
				if ( $echo ) {
					echo sprintf( '<div>%s</div>', esc_html( $repeater_fields ) );
				} else {
					$output = sprintf( '<div>%s</div>', $repeater_fields );
				}
				break;

			case 'repeater_button_add_remove_label':
				$add_new_label = isset( $field['repeater_button_add_new_label'] ) && ! empty( $field['repeater_button_add_new_label'] ) ? $field['repeater_button_add_new_label'] : 'Add';
				$remove_label  = isset( $field['repeater_button_remove_label'] ) && ! empty( $field['repeater_button_remove_label'] ) ? $field['repeater_button_remove_label'] : 'Remove';
				if ( $echo ) {
					echo sprintf( '<div style="margin-right: %s" class="evf-add-row repeater_button_add_remove_label %s"><span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-plus">%s</span>&nbsp;<span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-minus">%s</span></div>', '65%', esc_attr( $class ), esc_html( $add_new_label ), esc_html( $remove_label ) );
				} else {
					$output = sprintf( '<div style="margin-right: %s" class="evf-add-row repeater_button_add_remove_label %s"><span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-plus">%s</span>&nbsp;<span class="everest-forms-btn everest-forms-btn-primary dashicons dashicons-minus">%s</span></div>', '65%', $class, $add_new_label, $remove_label );
				}
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

				if ( ! empty( $class ) ) {
					$list_class[] = $class;
				}

				if ( 'select' === $type ) {
					$multiple    = ! empty( $field['multiple_choices'] ) ? ' multiple' : '';
					$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

					if ( $echo ) {
						echo sprintf( '<select class="%s" %s data-placeholder="%s" disabled>', esc_attr( evf_sanitize_classes( $list_class, true ) ), esc_attr( $multiple ), esc_attr( $placeholder ) );

						// Optional placeholder.
						if ( ! empty( $placeholder ) ) {
							echo sprintf( '<option value="" class="placeholder">%s</option>', esc_html( $placeholder ) );
						}

						// Build the select options (even though user can only see 1st option).
						foreach ( $values as $value ) {
							$default  = isset( $value['default'] ) ? (bool) $value['default'] : false;
							$selected = ! empty( $placeholder ) && empty( $multiple ) ? '' : selected( true, $default, false );
							echo sprintf( '<option %s>%s</option>', esc_attr( $selected ), esc_html( $value['label'] ) );
						}

						echo '</select>';
					} else {
						$output = sprintf( '<select class="%s" %s data-placeholder="%s" disabled>', evf_sanitize_classes( $list_class, true ), esc_attr( $multiple ), esc_attr( $placeholder ) );

						// Optional placeholder.
						if ( ! empty( $placeholder ) ) {
							$output .= sprintf( '<option value="" class="placeholder">%s</option>', esc_html( $placeholder ) );
						}

						// Build the select options (even though user can only see 1st option).
						foreach ( $values as $value ) {
							$default  = isset( $value['default'] ) ? (bool) $value['default'] : false;
							$selected = ! empty( $placeholder ) && empty( $multiple ) ? '' : selected( true, $default, false );
							$output  .= sprintf( '<option %s>%s</option>', $selected, esc_html( $value['label'] ) );
						}

						$output .= '</select>';
					}
				} else {

					if ( $echo ) {
						echo sprintf( '<ul class="%s">', esc_attr( evf_sanitize_classes( $list_class, true ) ) );

						// Individual checkbox/radio options.
						foreach ( $values as $value ) {
							$default     = isset( $value['default'] ) ? $value['default'] : '';
							$selected    = checked( '1', $default, false );
							$placeholder = evf()->plugin_url( 'assets/images/everest-forms-placeholder.png' );
							$image_src   = ! empty( $value['image'] ) ? esc_url( $value['image'] ) : $placeholder;
							$item_class  = array();

							if ( ! empty( $value['default'] ) ) {
								$item_class[] = 'everest-forms-selected';
							}

							if ( $choices_images ) {
								$item_class[] = 'everest-forms-image-choices-item';
							}

							echo sprintf( '<li class="%s">', esc_attr( evf_sanitize_classes( $item_class, true ) ) );

							if ( $choices_images ) {
								echo '<label>';
								echo sprintf( '<span class="everest-forms-image-choices-image"><img src="%s" alt="%s"%s></span>', esc_url( $image_src ), esc_attr( $value['label'] ), ( ! empty( $value['label'] ) ? ' title="' . esc_attr( $value['label'] ) . '"' : '' ) );
								echo sprintf( '<input type="%s" %s disabled>', esc_attr( $type ), esc_attr( $selected ) );
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									echo '<span class="everest-forms-image-choices-label">' . esc_html( $value['label'] . '-' . evf_format_amount( evf_sanitize_amount( $value['value'] ), true ) ) . '</span>';
								} else {
									echo '<span class="everest-forms-image-choices-label">' . esc_html( $value['label'] ) . '</span>';
								}
								echo '</label>';
							} else {
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									echo sprintf( '<input type="%s" %s disabled>%s - %s', esc_attr( $type ), esc_attr( $selected ), esc_html( $value['label'] ), esc_attr( evf_format_amount( evf_sanitize_amount( $value['value'] ) ), true ) );
								} else {
									echo sprintf( '<input type="%s" %s disabled>%s', esc_attr( $type ), esc_attr( $selected ), esc_html( $value['label'] ) );
								}
							}

							echo '</li>';
						}

						echo '</ul>';

					} else {
						$output = sprintf( '<ul class="%s">', evf_sanitize_classes( $list_class, true ) );

						// Individual checkbox/radio options.
						foreach ( $values as $value ) {
							$default     = isset( $value['default'] ) ? $value['default'] : '';
							$selected    = checked( '1', $default, false );
							$placeholder = wp_remote_get( evf()->plugin_url( 'assets/images/everest-forms-placeholder.png' ), array( 'sslverify' => false ) );
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
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									$output .= '<span class="everest-forms-image-choices-label">' . wp_kses_post( $value['label'] ) . '-' . evf_format_amount( evf_sanitize_amount( $value['value'] ), true ) . '</span>';
								} else {
									$output .= '<span class="everest-forms-image-choices-label">' . wp_kses_post( $value['label'] ) . '</span>';
								}
								$output .= '</label>';
							} else {
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									$output .= sprintf( '<input type="%s" %s disabled>%s - %s', $type, $selected, $value['label'], evf_format_amount( evf_sanitize_amount( $value['value'] ), true ) );
								} else {
									$output .= sprintf( '<input type="%s" %s disabled>%s', $type, $selected, $value['label'] );
								}
							}

							$output .= '</li>';
						}

						$output .= '</ul>';
					}
				}
				break;
		}

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "everest_forms_field_options_top_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "everest_forms_field_options_after_{$option}", $field, $this );
		}

		if ( ! $echo ) {
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

		// Check for form ID.
		if ( ! isset( $_POST['form_id'] ) || empty( $_POST['form_id'] ) ) {
			die( esc_html__( 'No form ID found', 'everest-forms' ) );
		}

		// Check for permissions.
		if ( ! current_user_can( 'everest_forms_edit_form', (int) $_POST['form_id'] ) ) {
			die( esc_html__( 'You don\'t have permission.', 'everest-forms' ) );
		}

		// Check for field type to add.
		if ( ! isset( $_POST['field_type'] ) || empty( $_POST['field_type'] ) ) {
			die( esc_html__( 'No field type found', 'everest-forms' ) );
		}

		// Grab field data.
		$field_args     = ! empty( $_POST['defaults'] ) && is_array( $_POST['defaults'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['defaults'] ) ) : array();
		$field_type     = esc_attr( sanitize_text_field( wp_unslash( $_POST['field_type'] ) ) );
		$field_id       = evf()->form->field_unique_key( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) );
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
		$preview  = sprintf( '<div class="everest-forms-field everest-forms-field-%s %s %s" id="everest-forms-field-%s" data-field-id="%s" data-field-type="%s">', $field_type, $field_required, $field_class, $field['id'], $field['id'], $field_type );
		$preview .= sprintf( '<div class="evf-field-action">' );
		if ( 'repeater-fields' !== $field_type ) {
			$preview .= sprintf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', __( 'Duplicate Field', 'everest-forms' ) );
			$preview .= sprintf( '<a href="#" class="everest-forms-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', __( 'Delete Field', 'everest-forms' ) );
			$preview .= sprintf( '<a href="#" class="everest-forms-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', __( 'Settings', 'everest-forms' ) );
		} else {
			$preview .= sprintf( '<a href="#" class="evf-duplicate-row" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Field', 'everest-forms' ) );
			$preview .= sprintf( '<a href="#" class="evf-delete-row" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Field', 'everest-forms' ) );
		}
		$preview .= sprintf( '</div>' );
		$preview .= ob_get_clean();
		$preview .= '</div>';

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
	 * Edit form field display on the entry back-end.
	 *
	 * @since 1.7.0
	 *
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {

		if ( 'repeater_fields' === $field['type'] ) {
			return;
		}

		$value = isset( $entry_field['value'] ) ? $entry_field['value'] : '';

		if ( '' !== $value ) {
			$field['properties'] = $this->get_single_field_property_value( $value, 'primary', $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * Get the value to prefill, based on field data and current properties.
	 *
	 * @since 1.7.0
	 *
	 * @param string $raw_value  Raw Value, always a string.
	 * @param string $input      Subfield inside the field.
	 * @param array  $properties Field properties.
	 * @param array  $field      Field specific data.
	 *
	 * @return array Modified field properties.
	 */
	public function get_single_field_property_value( $raw_value, $input, $properties, $field ) {
		if ( ! is_string( $raw_value ) ) {
			return $properties;
		}

		$get_value = wp_unslash( sanitize_text_field( $raw_value ) );

		if ( ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
			$properties = $this->get_single_field_property_value_choices( $get_value, $properties, $field );
		} else {
			if (
				! empty( $input ) &&
				isset( $properties['inputs'][ $input ] )
			) {
				$properties['inputs'][ $input ]['attr']['value'] = $get_value;

				// Update data attributes depending on the field type.
				if ( isset( $field['type'] ) && 'range-slider' === $field['type'] ) {
					$properties['inputs'][ $input ]['data']['from'] = $get_value;
				}
			}
		}

		return $properties;
	}

	/**
	 * Get the value to prefill for choices section, based on field data and current properties.
	 *
	 * @since 1.7.0
	 *
	 * @param string $get_value  Requested value.
	 * @param array  $properties Field properties.
	 * @param array  $field      Field specific data.
	 *
	 * @return array Modified field properties.
	 */
	protected function get_single_field_property_value_choices( $get_value, $properties, $field ) {
		$default_key = null;

		// For fields with normal choices, we need dafault key.
		foreach ( $field['choices'] as $choice_key => $choice_arr ) {
			$choice_value_key = isset( $field['show_values'] ) ? 'value' : 'label';
			if (
				isset( $choice_arr[ $choice_value_key ] ) &&
				strtoupper( sanitize_text_field( $choice_arr[ $choice_value_key ] ) ) === strtoupper( $get_value )
			) {
				$default_key = $choice_key;
				break;
			}
		}

		// Redefine selected choice.
		if ( null !== $default_key ) {
			foreach ( $field['choices'] as $choice_key => $choice_arr ) {
				if ( $choice_key === $default_key ) {
					$properties['inputs'][ $choice_key ]['default']              = true;
					$properties['inputs'][ $choice_key ]['container']['class'][] = 'everest-forms-selected';
					break;
				}
			}
		}

		return $properties;
	}

	/**
	 * Remove all admin-defined defaults from choices-related fields only.
	 *
	 * @since 1.7.0
	 *
	 * @param array $field      Field data and settings.
	 * @param array $properties Field Properties to be modified.
	 */
	protected function remove_field_choices_defaults( $field, &$properties ) {
		if ( ! empty( $field['choices'] ) ) {
			array_walk_recursive(
				$properties['inputs'],
				function ( &$value, $key ) {
					if ( 'default' === $key ) {
						$value = false;
					}
					if ( 'everest-forms-selected' === $value ) {
						$value = '';
					}
				}
			);
		}
	}

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
			esc_html( $hidden ),
			esc_html( evf_string_translation( (int) $this->form_data['id'], $field['id'], $field['properties']['inputs'][ $key ]['sublabel']['value'], '-sublabel-' . $key ) )
		);
	}

	/**
	 * Validates field on form submit.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $field_id Field Id.
	 * @param string|array $field_submit Submitted Data.
	 * @param array        $form_data All Form Data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$field_type          = isset( $form_data['form_fields'][ $field_id ]['type'] ) ? $form_data['form_fields'][ $field_id ]['type'] : '';
		$required_field      = isset( $form_data['form_fields'][ $field_id ]['required'] ) ? $form_data['form_fields'][ $field_id ]['required'] : false;
		$field               = isset( $form_data['form_fields'][ $field_id ] ) ? $form_data['form_fields'][ $field_id ] : '';
		$required_validation = get_option( 'everest_forms_required_validation' );
		if ( in_array( $field_type, array( 'number', 'email', 'url', 'phone' ), true ) ) {
			$required_validation = get_option( 'everest_forms_' . $field['type'] . '_validation' );
		}
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : $required_validation;
		$entry            = $form_data['entry'];
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );

		if ( false === $visible ) {
			return;
		}
		// Basic required check - If field is marked as required, check for entry data.
		if ( false !== $required_field && ( empty( $field_submit ) && '0' !== $field_submit ) ) {
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = $required_message;
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
			case 'textarea':
			case 'text':
				if ( ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification

					// Limit Length.
					if ( isset( $field['limit_enabled'], $field['limit_mode'], $field['limit_count'] ) && '1' === $field['limit_enabled'] && in_array( $field['limit_mode'], array( 'characters', 'words' ), true ) && ! empty( $field['limit_count'] ) ) {
						if ( 'words' === $field['limit_mode'] && $field['limit_count'] < str_word_count( $field_submit ) ) {
							/* translators: %s Number of max words. */
							$validation_text = sprintf( esc_html__( 'This field contains at most %s words', 'everest-forms' ), $field['limit_count'] );
						} elseif ( 'characters' === $field['limit_mode'] && $field['limit_count'] < mb_strlen( $field_submit ) ) { //phpcs:ignore PHPCompatibility.ParameterValues.NewIconvMbstringCharsetDefault.NotSet
							/* translators: %s Number of max characters. */
							$validation_text = sprintf( esc_html__( 'This field contains at most %s characters', 'everest-forms' ), $field['limit_count'] );
						}
					}

					// Min Length.
					if ( isset( $field['min_length_enabled'], $field['min_length_mode'], $field['min_length_count'] ) && '1' === $field['min_length_enabled'] && in_array( $field['min_length_mode'], array( 'characters', 'words' ), true ) && ! empty( $field['min_length_count'] ) ) {
						if ( 'words' === $field['min_length_mode'] && $field['min_length_count'] > str_word_count( $field_submit ) ) {
							/* translators: %s Number of minimum words. */
							$validation_text = sprintf( esc_html__( 'This field contains at least %s words', 'everest-forms' ), $field['min_length_count'] );
						} elseif ( 'characters' === $field['min_length_mode'] && $field['min_length_count'] > mb_strlen( $field_submit ) ) { //phpcs:ignore PHPCompatibility.ParameterValues.NewIconvMbstringCharsetDefault.NotSet
							/* translators: %s Number of minimum characters. */
							$validation_text = sprintf( esc_html__( 'This field contains at least %s characters', 'everest-forms' ), $field['min_length_count'] );
						}
					}
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
			return ( isset( $field['limit_enabled'] ) && ! empty( $field['limit_count'] ) ) || ( isset( $field['min_length_enabled'] ) && ! empty( $field['min_length_count'] ) );
		}
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @param array $field Field Data.
	 */
	public function field_exporter( $field ) {
		$export = array();

		switch ( $this->type ) {
			case 'radio':
			case 'signature':
			case 'payment-multiple':
				$value  = '';
				$image  = ! empty( $field['value']['image'] ) ? sprintf( '<img src="%s" style="width:75px;height:75px;max-height:75px;max-width:75px;"  /><br>', $field['value']['image'] ) : '';
				$value  = ! empty( $field['value']['label'] ) ? $image . $field['value']['label'] : '';
				$export = array(
					'label' => ! empty( $field['value']['name'] ) ? $field['value']['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
					'value' => ! empty( $value ) ? $value : false,
				);
				break;
			case 'checkbox':
			case 'payment-checkbox':
				$value = array();

				if ( count( $field['value'] ) ) {
					foreach ( $field['value']['label'] as $key => $choice ) {
						$image = ! empty( $field['value']['images'][ $key ] ) ? sprintf( '<img src="%s" style="width:75px;height:75px;max-height:75px;max-width:75px;"  /><br>', $field['value']['images'][ $key ] ) : '';

						if ( ! empty( $choice ) ) {
							$value[ $key ] = $image . $choice;
						}
					}
				}
				$export = array(
					'label' => ! empty( $field['value']['name'] ) ? $field['value']['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
					'value' => is_array( $value ) ? implode( '<br>', array_values( $value ) ) : false,
				);
				break;
			default:
				$export = array(
					'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
					'value' => ! empty( $field['value'] ) ? ( is_array( $field['value'] ) ? $this->implode_recursive( $field['value'] ) : $field['value'] ) : false,
				);
		}

		return $export;
	}

	/**
	 * Recursively process an array with an implosion.
	 *
	 * @since 1.6.6
	 *
	 * @param  array  $array     Array that needs to be recursively imploded.
	 * @param  string $delimiter Delimiter for the implosion - defaults to <br>.
	 *
	 * @return string $output Imploded array.
	 */
	protected function implode_recursive( $array, $delimiter = '<br>' ) {
		$output = '';

		foreach ( $array as $tuple ) {
			if ( is_array( $tuple ) ) {
				$output .= $this->implode_recursive( $tuple, ' ' );
			} elseif ( ! empty( $tuple ) ) {
				$output .= $delimiter . $tuple;
			}
		}

		return $output;
	}
}
