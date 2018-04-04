<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Abstract EVF_Form_Fields Class
 *
 * @version        1.0.0
 * @package        EverestFroms/Abstracts
 * @category       Abstract Class
 * @author         WPEverest
 */
abstract class EVF_Form_Fields {
	/**
	 * Full name of the field type, eg "Paragraph Text".
	 *
	 * $since 1.0.0
	 * @var string
	 */
	public $name;

	/**
	 * Type of the field, eg "textarea".
	 *
	 * $since 1.0.0
	 * @var string
	 */
	public $type;

	/**
	 * Font Awesome Icon used for the editor button.
	 *
	 * $since 1.0.0
	 * @var mixed
	 */
	public $icon = false;

	/**
	 * Priority order the field button should show inside the "Add Fields" tab.
	 *
	 * $since 1.0.0
	 * @var integer
	 */
	public $order = 20;

	/**
	 * Field group the field belongs to.
	 *
	 * $since 1.0.0
	 * @var string
	 */
	public $group = 'general';

	/**
	 * Placeholder to hold default value(s) for some field types.
	 *
	 * $since 1.0.0
	 * @var mixed
	 */
	public $defaults;

	/**
	 * Current form ID in the admin builder.
	 *
	 * $since 1.0.0
	 * @var mixed, int or false
	 */
	public $form_id;

	/**
	 * Current form data in admin builder.
	 *
	 * $since 1.0.0
	 * @var mixed, int or false
	 */
	public $form_data;

	/**
	 * Primary class constructor.
	 *
	 * $since 1.0.0
	 *
	 * @param bool $init
	 */
	public function __construct( $init = true ) {
		if ( ! $init ) {
			return;
		}

		// The form ID is to be accessed in the builder.
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

		// Bootstrap.
		$this->init();

		// Add fields tab.
		add_filter( 'everest_forms_builder_fields_buttons', array( $this, 'field_button' ), 15 );

		// Field options tab.
		add_action( "everest_forms_builder_fields_options_{$this->type}", array( $this, 'field_options' ), 10 );

		// Preview fields.
		add_action( "everest_forms_builder_fields_previews_{$this->type}", array( $this, 'field_preview' ), 10 );

		// AJAX Add new field.
		add_action( "wp_ajax_everest_forms_new_field_{$this->type}", array( $this, 'field_new' ) );

		// Display field input elements on front-end.
		add_action( "evf_display_field_{$this->type}", array( $this, 'field_display' ), 10, 3 );

		// Validation on submit.
		add_action( "everest_forms_process_validate_{$this->type}", array( $this, 'validate' ), 10, 4 );

		// Format.
		add_action( "everest_forms_process_format_{$this->type}", array( $this, 'format' ), 10, 3 );
	}

	/**
	 * All systems go. Used by subclasses.
	 *
	 * $since 1.0.0
	 */
	public function init() {
	}

	/**
	 * Create the button for the 'Add Fields' tab, inside the form editor.
	 *
	 * $since 1.0.0
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function field_button( $fields ) {

		// Add field information to fields array.
		$fields[ $this->group ]['fields'][] = array(
			'order' => $this->order,
			'name'  => $this->name,
			'type'  => $this->type,
			'icon'  => $this->icon,
		);

		// Wipe hands clean.
		return $fields;
	}

	/**
	 * Creates the field options panel. Used by subclasses.
	 *
	 * $since 1.0.0
	 *
	 * @param array $field
	 */
	public function field_options( $field ) {
	}

	/**
	 * Creates the field preview. Used by subclasses.
	 *
	 * $since 1.0.0
	 *
	 * @param array $field
	 */
	public function field_preview( $field ) {
	}

	/**
	 * Helper function to create field option elements.
	 *
	 * Field option elements are pieces that help create a field option.
	 * They are used to quickly build field options.
	 *
	 * $since 1.0.0
	 *
	 * @param string  $option
	 * @param array   $field
	 * @param array   $args
	 * @param boolean $echo
	 *
	 * @return mixed echo or return string
	 */
	public function field_element( $option, $field, $args = array(), $echo = true ) {

		$id     = $field['id'];
		$class  = ! empty( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '';
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

		switch ( $option ) {

			// Row.
			case 'row':
				$output = sprintf( '<div class="everest-forms-field-option-row everest-forms-field-option-row-%s %s" id="everest-forms-field-option-row-%s-%s" data-field-id="%s">%s</div>', $slug, $class, $id, $slug, $id, $args['content'] );
				break;

			// Label.
			case 'label':
				$output = sprintf( '<label for="everest-forms-field-option-%s-%s">%s', $id, $slug, esc_html( $args['value'] ) );
				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help everest-forms-help-tooltip" data-tip="%s"></i>', esc_attr( $args['tooltip'] ) );
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
				$output = sprintf( '%s<input type="%s" class="%s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" value="%s" placeholder="%s" %s>', $before, $type, $class, $id, $slug, $id, $slug, esc_attr( $args['value'] ), $placeholder, $data );
				break;

			// Textarea.
			case 'textarea':
				$rows   = ! empty( $args['rows'] ) ? (int) $args['rows'] : '3';
				$output = sprintf( '<textarea class="%s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" rows="%s" %s>%s</textarea>', $class, $id, $slug, $id, $slug, $rows, $data, $args['value'] );
				break;

			// Checkbox.
			case 'checkbox':
				$checked = checked( '1', $args['value'], false );
				$output  = sprintf( '<input type="checkbox" class="%s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" value="1" %s %s>', $class, $id, $slug, $id, $slug, $checked, $data );
				$output  .= sprintf( '<label for="everest-forms-field-option-%s-%s" class="inline">%s', $id, $slug, $args['desc'] );
				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help everest-forms-help-tooltip" data-tip="%s"></i>', esc_attr( $args['tooltip'] ) );
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
				$output  .= sprintf( '<input type="checkbox" class="%s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" value="1" %s %s></span>', $class, $id, $slug, $id, $slug, $checked, $data );
				break;

			// Select.
			case 'select':
				$options = $args['options'];
				$value   = isset( $args['value'] ) ? $args['value'] : '';
				$output  = sprintf( '<select class="%s" id="everest-forms-field-option-%s-%s" name="form_fields[%s][%s]" %s>', $class, $id, $slug, $id, $slug, $data );
				foreach ( $options as $key => $option ) {
					$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $key, $value, false ), $option );
				}
				$output .= '</select>';
				break;
		} // End switch().

		if ( $echo ) {
			echo $output; // WPCS: XSS ok.
		} else {
			return $output;
		}
	}

	/**
	 * Helper function to create common field options that are used frequently.
	 *
	 * $since 1.0.0
	 *
	 * @param string  $option
	 * @param array   $field
	 * @param array   $args
	 * @param boolean $echo
	 *
	 * @return mixed echo or return string
	 */
	public function field_option( $option, $field, $args = array(), $echo = true ) {

		$output = '';

		switch ( $option ) {

			// --------------------------------------------------------------//
			// Basic Fields.
			// --------------------------------------------------------------//

			// Basic Options markup. ------------------------------------------//

			case 'basic-options':
				$markup = ! empty( $args['markup'] ) ? $args['markup'] : 'open';
				$class  = ! empty( $args['class'] ) ? esc_html( $args['class'] ) : '';
				if ( 'open' === $markup ) {
					$output = sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-basic open" id="everest-forms-field-option-basic-%s">', $field['id'] );
					$output .= sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<span>(ID #%s)</span> <i class="handlediv"></i></a>', $this->name, $field['id'] );
					$output .= sprintf( '<div class="everest-forms-field-option-group-inner %s">', $class );
				} else {
					$output = '</div></div>';
				}
				break;

			// Field Label. ---------------------------------------------------//

			case 'label':
				$value   = ! empty( $field['label'] ) ? esc_attr( $field['label'] ) : '';
				$tooltip = __( 'Enter text for the form field label.', 'everest-forms' );
				$output  = $this->field_element( 'label', $field, array(
					'slug'    => 'label',
					'value'   => __( 'Label', 'everest-forms' ),
					'tooltip' => $tooltip
				), false );
				$output  .= $this->field_element( 'text', $field, array(
					'slug'  => 'label',
					'value' => $value
				), false );
				$output  = $this->field_element( 'row', $field, array(
					'slug'    => 'label',
					'content' => $output
				), false );
				break;

			// EVF meta fields
			case 'meta':
				$value   =  ! empty( $field['meta-key'] ) ? esc_attr( $field['meta-key'] ) : evf_get_meta_key_field_option( $field );
				$tooltip = __( 'Enter meta key to be stored in database.', 'everest-forms' );
				$output  = $this->field_element( 'label', $field, array(
					'slug'    => 'meta-key',
					'value'   => __( 'Meta Key', 'everest-forms' ),
					'tooltip' => $tooltip
				), false );
				$output  .= $this->field_element( 'text', $field, array(
					'slug'  => 'meta-key',
					'value' => $value
				), false );
				$output  = $this->field_element( 'row', $field, array(
					'slug'    => 'meta-key',
					'content' => $output
				), false );
				break;

			// Field Description. ---------------------------------------------//

			case 'description':
				$value   = ! empty( $field['description'] ) ? esc_attr( $field['description'] ) : '';
				$tooltip = __( 'Enter text for the form field description.', 'everest-forms' );
				$output  = $this->field_element( 'label', $field, array(
					'slug'    => 'description',
					'value'   => __( 'Description', 'everest-forms' ),
					'tooltip' => $tooltip
				), false );
				$output  .= $this->field_element( 'textarea', $field, array(
					'slug'  => 'description',
					'value' => $value
				), false );
				$output  = $this->field_element( 'row', $field, array(
					'slug'    => 'description',
					'content' => $output
				), false );
				break;


			case 'required':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = isset( $field['required'] ) ? $field['required'] : $default;
				$tooltip = __( 'Check this option to mark the field required.', 'everest-forms' );
				$output  = $this->field_element( 'checkbox', $field, array(
					'slug'    => 'required',
					'value'   => $value,
					'desc'    => __( 'Required', 'everest-forms' ),
					'tooltip' => $tooltip
				), false );
				$output  = $this->field_element( 'row', $field, array(
					'slug'    => 'required',
					'content' => $output
				), false );
				break;

			case 'choices':
				$tooltip = __( 'Add choices for the form field.', 'everest-forms' );
				$toggle  = '';
				$dynamic = ! empty( $field['dynamic_choices'] ) ? esc_html( $field['dynamic_choices'] ) : '';
				$values  = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
				$class   = ! empty( $field['show_values'] ) && $field['show_values'] == '1' ? 'show-values' : '';
				$class   .= ! empty( $dynamic ) ? ' evf-hidden' : '';

				// Field option label and type.
				$option_label = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'choices',
						'value'         => __( 'Choices', 'everest-forms' ),
						'tooltip'       => $tooltip,
						'after_tooltip' => $toggle,
					),
					false
				);
				$option_type  = 'checkbox' === $this->type ? 'checkbox' : 'radio';

				// Field option choices inputs
				$option_choices = sprintf( '<ul data-next-id="%s" class="evf-choices-list %s" data-field-id="%s" data-field-type="%s">', max( array_keys( $values ) ) + 1, $class, $field['id'], $this->type );
				foreach ( $values as $key => $value ) {
					$default        = ! empty( $value['default'] ) ? $value['default'] : '';
					$option_choices .= sprintf( '<li data-key="%d">', $key );
					$option_choices .= sprintf( '<input type="%s" name="form_fields[%s][choices][%s][default]" class="default" value="1" %s>', $option_type, $field['id'], $key, checked( '1', $default, false ) );
					$option_choices .= sprintf( '<input type="text" name="form_fields[%s][choices][%s][label]" value="%s" class="label">', $field['id'], $key, esc_attr( $value['label'] ) );
					$option_choices .= sprintf( '<input type="text" name="form_fields[%s][choices][%s][value]" value="%s" class="value">', $field['id'], $key, esc_attr( $value['value'] ) );
					$option_choices .= '<a class="remove" href="#"><i class="dashicons dashicons-dismiss"></i></a>';
					$option_choices .= '<a class="add" href="#"><i class="dashicons dashicons-plus-alt"></i></a>';

					$option_choices .= '</li>';
				}
				$option_choices .= '</ul>';
				// Field option row (markup) including label and input.
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'choices',
						'content' => $option_label . $option_choices,
					)
				);
				break;

			// ---------------------------------------------------------------//
			// Advanced Fields.
			// ---------------------------------------------------------------//

			// Default value. -------------------------------------------------//

			case 'default_value':
				$value   = ! empty( $field['default_value'] ) ? esc_attr( $field['default_value'] ) : '';
				$tooltip = __( 'Enter text for the default form field value.', 'everest-forms' );
				$toggle  = '';
				$output  = $this->field_element( 'label', $field, array(
					'slug'          => 'default_value',
					'value'         => __( 'Default Value', 'everest-forms' ),
					'tooltip'       => $tooltip,
					'after_tooltip' => $toggle
				), false );
				$output  .= $this->field_element( 'text', $field, array(
					'slug'  => 'default_value',
					'value' => $value
				), false );
				$output  = $this->field_element( 'row', $field, array(
					'slug'    => 'default_value',
					'content' => $output
				), false );
				break;

			// Advanced Options markup. ---------------------------------------//

			case 'advanced-options':
				$markup = ! empty( $args['markup'] ) ? $args['markup'] : 'open';
				if ( 'open' === $markup ) {
					$override = apply_filters( 'everest_forms_advanced_options_override', false );
					$override = ! empty( $override ) ? 'style="display:' . $override . ';"' : '';
					$output   = sprintf( '<div class="everest-forms-field-option-group everest-forms-field-option-group-advanced everest-forms-hide closed" id="everest-forms-field-option-advanced-%s" %s>', $field['id'], $override );
					$output   .= sprintf( '<a href="#" class="everest-forms-field-option-group-toggle">%s<i class="handlediv"></i></a>', __( 'Advanced Options', 'everest-forms' ) );
					$output   .= '<div class="everest-forms-field-option-group-inner">';
				} else {
					$output = '</div></div>';
				}
				break;

			// Placeholder. ---------------------------------------------------//

			case 'placeholder':
				$value   = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
				$tooltip = __( 'Enter text for the form field placeholder.', 'everest-forms' );
				$output  = $this->field_element( 'label', $field, array(
					'slug'    => 'placeholder',
					'value'   => __( 'Placeholder Text', 'everest-forms' ),
					'tooltip' => $tooltip
				), false );
				$output  .= $this->field_element( 'text', $field, array(
					'slug'  => 'placeholder',
					'value' => $value
				), false );
				$output  = $this->field_element( 'row', $field, array(
					'slug'    => 'placeholder',
					'content' => $output
				), false );
				break;

			// CSS classes. ---------------------------------------------------//

			case 'css':
				$toggle  = '';
				$tooltip = __( 'Enter CSS class for this field container. Class names should be separated with spaces.', 'everest-forms' );
				$value   = ! empty( $field['css'] ) ? esc_attr( $field['css'] ) : '';
				// Build output
				$output = $this->field_element( 'label', $field, array(
					'slug'          => 'css',
					'value'         => __( 'CSS Classes', 'everest-forms' ),
					'tooltip'       => $tooltip,
					'after_tooltip' => $toggle
				), false );
				$output .= $this->field_element( 'text', $field, array( 'slug' => 'css', 'value' => $value ), false );
				$output = $this->field_element( 'row', $field, array( 'slug' => 'css', 'content' => $output ), false );
				break;

			// Hide Label. ----------------------------------------------------//

			case 'label_hide':
				$value   = isset( $field['label_hide'] ) ? $field['label_hide'] : '0';
				$tooltip = __( 'Check this option to hide the form field label.', 'everest-forms' );
				// Build output
				$output = $this->field_element( 'checkbox', $field, array(
					'slug'    => 'label_hide',
					'value'   => $value,
					'desc'    => __( 'Hide Label', 'everest-forms' ),
					'tooltip' => $tooltip
				), false );
				$output = $this->field_element( 'row', $field, array(
					'slug'    => 'label_hide',
					'content' => $output
				), false );
				break;

			// Hide Sub-Labels. -----------------------------------------------//

			case 'sublabel_hide':
				$value   = isset( $field['sublabel_hide'] ) ? $field['sublabel_hide'] : '0';
				$tooltip = __( 'Check this option to hide the form field sub-label.', 'everest-forms' );
				// Build output
				$output = $this->field_element( 'checkbox', $field, array(
					'slug'    => 'sublabel_hide',
					'value'   => $value,
					'desc'    => __( 'Hide Sub-Labels', 'everest-forms' ),
					'tooltip' => $tooltip
				), false );
				$output = $this->field_element( 'row', $field, array(
					'slug'    => 'sublabel_hide',
					'content' => $output
				), false );
				break;

		} // End switch().

		if ( $echo ) {

			if ( in_array( $option, array( 'basic-options', 'advanced-options' ), true ) ) {

				if ( 'open' === $markup ) {
					do_action( "everest_forms_field_options_before_{$option}", $field, $this );
				}

				echo $output; // WPCS: XSS ok.

				if ( 'close' === $markup ) {
					do_action( "everest_forms_field_options_after_{$option}", $field, $this );
				}
			} else {
				echo $output; // WPCS: XSS ok.
			}
		} else {
			return $output;
		}
	}

	/**
	 * Helper function to create common field options that are used frequently
	 * in the field preview.
	 *
	 * $since 1.0.0
	 *
	 * @param string  $option
	 * @param array   $field
	 * @param array   $args
	 * @param boolean $echo
	 *
	 * @return mixed echo or return string
	 */
	public function field_preview_option( $option, $field, $args = array(), $echo = true ) {

		$required_string = isset( $field['required'] ) && $field['required'] ? '<span class="required">*</span>' : '';
		$hide_style      = isset( $field['label_hide'] ) && $field['label_hide'] ? 'display:none' : '';
		switch ( $option ) {

			case 'label':
				$label  = isset( $field['label'] ) && ! empty( $field['label'] ) ? esc_html( $field['label'] ) : '';
				$output = sprintf( '<label style="%s" class="label-title"><span class="text">%s</span>%s</label>', $hide_style, $label, $required_string );
				break;

			case 'description':
				$description = isset( $field['description'] ) && ! empty( $field['description'] ) ? $field['description'] : '';
				$output      = sprintf( '<div class="description">%s</div>', $description );
				break;
		}

		if ( $echo ) {
			echo $output; // WPCS: XSS ok.
		} else {
			return $output;
		}
	}

	/**
	 * Create a new field in the admin AJAX editor.
	 *
	 * $since 1.0.0
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

		$field_args = ! empty( $_POST['defaults'] ) ? (array) $_POST['defaults'] : array();

		$field_type = esc_attr( $_POST['field_type'] );
		$field_id   = EVF()->form->field_unique_key( $_POST['form_id'] );
		$field      = array(
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
		$prev    = ob_get_clean();
		$preview = sprintf( '<div class="everest-forms-field everest-forms-field-%s %s %s" id="everest-forms-field-%s" data-field-id="%s" data-field-type="%s">', $field_type, $field_required, $field_class, $field['id'], $field['id'], $field_type );
		$preview .= sprintf( '<div class="evf-field-action">' );
		$preview .= sprintf( '<a href="#" class="everest-forms-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', __( 'Duplicate Field', 'everest-forms' ) );
		$preview .= sprintf( '<a href="#" class="everest-forms-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', __( 'Delete Field', 'everest-forms' ) );
		$preview .= sprintf( '<a href="#" class="everest-forms-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', __( 'Settings', 'everest-forms' ) );
		$preview .= sprintf( '</div>' );
		$preview .= $prev;
		$preview .= '</div>';

		// Build Options.
		$options = sprintf( '<div class="everest-forms-field-option everest-forms-field-option-%s" id="everest-forms-field-option-%s" data-field-id="%s">', esc_attr( $field['type'] ), $field['id'], $field['id'] );
		$options .= sprintf( '<input type="hidden" name="form_fields[%s][id]" value="%s" class="everest-forms-field-option-hidden-id">', $field['id'], $field['id'] );
		$options .= sprintf( '<input type="hidden" name="form_fields[%s][type]" value="%s" class="everest-forms-field-option-hidden-type">', $field['id'], esc_attr( $field['type'] ) );
		ob_start();
		$this->field_options( $field );
		$options .= ob_get_clean();
		$options .= '</div>';

		$form_field_array = explode( '-', $field_id );
		$field_id_int     = absint( $form_field_array[ count( $form_field_array ) - 1 ] );

		// Prepare to return compiled results.
		wp_send_json_success(
			array(
				'form_id'       => $_POST['form_id'],
				'field'         => $field,
				'preview'       => $preview,
				'options'       => $options,
				'form_field_id' => ( $field_id_int + 1 )
			)
		);
	}

	/**
	 * Display the field input elements on the frontend.
	 *
	 * $since 1.0.0
	 *
	 * @param array $field
	 * @param array $field_atts
	 * @param array $form_data
	 */
	public function field_display( $field, $field_atts, $form_data ) {
	}

	/**
	 * Display field input errors if present.
	 *
	 * $since 1.0.0
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public function field_display_error( $key, $field ) {

		// Need an error.
		if ( empty( $field['properties']['error']['value'][ $key ] ) ) {
			return;
		}

		printf(
			'<label class="everest-forms-error" for="%s">%s</label>',
			esc_attr( $field['properties']['inputs'][ $key ]['id'] ),
			esc_html( $field['properties']['error']['value'][ $key ] )
		);
	}

	/**
	 * Display field input sublabel if present.
	 *
	 * $since 1.0.0
	 *
	 * @param string $key
	 * @param string $position
	 * @param array  $field
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
			$hidden,
			$field['properties']['inputs'][ $key ]['sublabel']['value']
		);
	}

	/**
	 * Validates field on form submit.
	 *
	 * $since 1.0.0
	 *
	 * @param int   $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function validate( $field_id, $field_type, $field_submit, $form_data ) {
		$required_field = isset( $form_data['form_fields'][ $field_id ]['required'] ) ? $form_data['form_fields'][ $field_id ]['required'] : false;

		// Basic required check - If field is marked as required, check for entry data.
		if ( false !== $required_field && ( empty( $field_submit ) && '0' !== $field_submit ) ) {
			EVF()->process->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_required_label', get_option( 'evf_required_validation', __( 'This field is required.', 'everest-forms' ) ) );
			update_option( 'evf_validation_error', 'yes');
		}
		// Type validations.
		switch ( $field_type ) {
			case 'url':
				if( ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) && filter_var( $field_submit, FILTER_VALIDATE_URL ) === FALSE ){
					$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter a valid url', 'everest-forms' ) );
				}
				break;
			case 'email':
			 	if ( ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) && ! is_email( $field_submit ) ) {
					$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter a valid email address', 'everest-forms' ) );
				}
				break;
			case 'number':
				if ( ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) && ! is_numeric( $field_submit ) ){
					$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter a valid number', 'everest-forms' ) );
				}
				break;
		}

		if( isset( $validation_text ) ){
			EVF()->process->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_type_validation', $validation_text );
			update_option( 'evf_validation_error', 'yes');
		}
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * $since 1.0.0
	 *
	 * @param int   $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		if ( is_array( $field_submit ) ) {
			$field_submit = array_filter( $field_submit );
			$field_submit = implode( "\r\n", $field_submit );
		}

		$name = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['form_fields'][ $field_id ]['label'] ) : '';

		// Sanitize but keep line breaks.
		$value = everest_forms_sanitize_textarea_field( $field_submit );

		EVF()->process->fields[ $field_id ] = array(
			'name'  => $name,
			'value' => $value,
			'id'    => absint( $field_id ),
			'type'  => $this->type,
		);
	}
}
