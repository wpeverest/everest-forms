<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * checkbox
 *
 * @package    EverestForms
 * @author     WPEverest
 * @since      1.0.0
 */
class EVF_Field_Checkbox extends EVF_Form_Fields {

	/**
	 * Primary class constructor.
	 *
	 * @since      1.0.0
	 */
	public function init() {

		// Define field type information
		$this->name     = __( 'Checkboxes', 'everest-forms' );
		$this->type     = 'checkbox';
		$this->icon     = 'evf-icon  evf-icon-checkbox';
		$this->order    = 13;
		$this->group = 'advanced';
		$this->defaults = array(
			1 => array(
				'label'   => __( 'First Choice', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => __( 'Second Choice', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => __( 'Third Choice', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
		);
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since      1.0.0
	 *
	 * @param array $field
	 */
	public function field_options( $field ) {

		// --------------------------------------------------------------------//
		// Basic field options
		// --------------------------------------------------------------------//

		// Options open markup
		$this->field_option( 'basic-options', $field, array(
			'markup' => 'open',
		) );

		// Label
		$this->field_option( 'label', $field );

		// Meta.
		$this->field_option( 'meta', $field );
		
		// Choices
		$this->field_option( 'choices', $field );

		// Description
		$this->field_option( 'description', $field );

		// Required toggle
		$this->field_option( 'required', $field );

		// Options close markup
		$this->field_option( 'basic-options', $field, array(
			'markup' => 'close',
		) );

		// --------------------------------------------------------------------//
		// Advanced field options
		// --------------------------------------------------------------------//

		// Options open markup
		$this->field_option( 'advanced-options', $field, array(
			'markup' => 'open',
		) );

		// Show Values toggle option. This option will only show if already used
		// or if manually enabled by a filter.
		if ( ! empty( $field['show_values'] ) || apply_filters( 'everest_forms_fields_show_options_setting', false ) ) {
			$show_values = $this->field_element(
				'checkbox',
				$field,
				array(
					'slug'    => 'show_values',
					'value'   => isset( $field['show_values'] ) ? $field['show_values'] : '0',
					'desc'    => __( 'Show Values', 'everest-forms' ),
					'tooltip' => __( 'Check this to manually set form field values.', 'everest-forms' ),
				),
				false
			);
			$this->field_element( 'row', $field, array(
				'slug'    => 'show_values',
				'content' => $show_values,
			) );
		}

		// Input columns
		$this->field_option( 'input_columns', $field );

		// Hide label
		$this->field_option( 'label_hide', $field );

		// Custom CSS classes
		$this->field_option( 'css', $field );

		// Options close markup
		$this->field_option( 'advanced-options', $field, array(
			'markup' => 'close',
		) );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since      1.0.0
	 *
	 * @param array $field
	 */
	public function field_preview( $field ) {

		$values  = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
		$dynamic = ! empty( $field['dynamic_choices'] ) ? $field['dynamic_choices'] : false;

		// Label
		$this->field_preview_option( 'label', $field );

		// Field checkbox elements
		echo '<ul class="primary-input">';

		// Notify if currently empty
		if ( empty( $values ) ) {
			$values = array(
				'label' => __( '(empty)', 'everest-forms' ),
			);
		}

		// Individual checkbox options
		foreach ( $values as $key => $value ) {

			$default  = isset( $value['default'] ) ? $value['default'] : '';
			$selected = checked( '1', $default, false );

			printf( '<li><input type="checkbox" %s disabled>%s</li>', $selected, $value['label'] );
		}

		echo '</ul>';

		// Dynamic population is enabled and contains more than 20 items
		if ( isset( $total ) && $total > 20 ) {
			echo '<div class="everest-forms-alert-dynamic everest-forms-alert everest-forms-alert-warning">';
			printf( __( 'Showing the first 20 choices.<br> All %d choices will be displayed when viewing the form.', 'everest-forms' ), absint( $total ) );
			echo '</div>';
		}

		// Description
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since      1.0.0
	 *
	 * @param array $field
	 * @param array $field_atts
	 * @param array $form_data
	 */
	public function field_display( $field, $field_atts, $form_data ) {

		// Setup and sanitize the necessary data
		$field          = apply_filters( 'everest_forms_checkbox_field_display', $field, $field_atts, $form_data );
		$field_required = ! empty( $field['required'] ) ? ' required' : '';
		$field_class    = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id       = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_data     = '';
		$form_id        = $form_data['id'];
		$choices        = isset( $field['choices'] ) ? $field['choices'] : array();
		if ( ! empty( $field_atts['input_data'] ) ) {
			foreach ( $field_atts['input_data'] as $key => $val ) {
				$field_data .= ' data-' . $key . '="' . $val . '"';
			}
		}
		// List.
		printf( '<ul id="%s" class="%s" %s>', $field_id, $field_class, $field_data );

		foreach ( $choices as $key => $choice ) {

			$selected = isset( $choice['default'] ) ? '1' : '0';
			$val      = isset( $field['show_values'] ) ? esc_attr( $choice['value'] ) : esc_attr( $choice['label'] );
			$depth    = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;

			printf( '<li class="choice-%d depth-%d">', $key, $depth );

			// Checkbox elements
			printf( '<input type="checkbox" id="everest-forms-%d-field_%s_%d" name="everest_forms[form_fields][%s][]" value="%s" %s %s>',
				$form_id,
				$field['id'],
				$key,
				$field['id'],
				$val,
				checked( '1', $selected, false ),
				$field_required
			);

			printf( '<label class="everest-forms-field-label-inline" for="everest-forms-%d-field_%d_%d">%s</label>', $form_id, $field['id'], $key, wp_kses_post( $choice['label'] ) );

			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since      1.0.0
	 *
	 * @param int   $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$field_submit = (array) $field_submit;
		$field        = $form_data['form_fields'][ $field_id ];
		$dynamic      = ! empty( $field['dynamic_choices'] ) ? $field['dynamic_choices'] : false;
		$name         = sanitize_text_field( $field['label'] );
		$value_raw    = evf_sanitize_array_combine( $field_submit );

		$data = array(
			'name'      => $name,
			'value'     => '',
			'value_raw' => $value_raw,
			'id'        => absint( $field_id ),
			'type'      => $this->type,
		);

		if ( 'post_type' === $dynamic && ! empty( $field['dynamic_post_type'] ) ) {

			// Dynamic population is enabled using post type
			$value_raw                 = implode( ',', array_map( 'absint', $field_submit ) );
			$data['value_raw']         = $value_raw;
			$data['dynamic']           = 'post_type';
			$data['dynamic_items']     = $value_raw;
			$data['dynamic_post_type'] = $field['dynamic_post_type'];
			$posts                     = array();

			foreach ( $field_submit as $id ) {
				$post = get_post( $id );

				if ( ! is_wp_error( $post ) && ! empty( $post ) && $data['dynamic_post_type'] === $post->post_type ) {
					$posts[] = esc_html( $post->post_title );
				}
			}

			$data['value'] = ! empty( $posts ) ? evf_sanitize_array_combine( $posts ) : '';

		} elseif ( 'taxonomy' === $dynamic && ! empty( $field['dynamic_taxonomy'] ) ) {

			// Dynamic population is enabled using taxonomy
			$value_raw                = implode( ',', array_map( 'absint', $field_submit ) );
			$data['value_raw']        = $value_raw;
			$data['dynamic']          = 'taxonomy';
			$data['dynamic_items']    = $value_raw;
			$data['dynamic_taxonomy'] = $field['dynamic_taxonomy'];
			$terms                    = array();

			foreach ( $field_submit as $id ) {
				$term = get_term( $id, $field['dynamic_taxonomy'] );

				if ( ! is_wp_error( $term ) && ! empty( $term ) ) {
					$terms[] = esc_html( $term->name );
				}
			}

			$data['value'] = ! empty( $terms ) ? evf_sanitize_array_combine( $terms ) : '';

		} else {

			// Normal processing, dynamic population is off

			// If show_values is true, that means values posted are the raw values
			// and not the labels. So we need to get the label values.
			if ( ! empty( $field['show_values'] ) && '1' == $field['show_values'] ) {

				$value = array();

				foreach ( $field_submit as $field_submit_single ) {
					foreach ( $field['choices'] as $choice ) {
						if ( $choice['value'] == $field_submit_single ) {
							$value[] = $choice['label'];
							break;
						}
					}
				}

				$data['value'] = ! empty( $value ) ? evf_sanitize_array_combine( $value ) : '';

			} else {
				$data['value'] = $value_raw;
			}
		}
	}
}

new EVF_Field_Checkbox;
