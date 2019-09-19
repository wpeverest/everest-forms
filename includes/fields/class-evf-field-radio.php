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
		$this->icon     = 'evf-icon evf-icon-multiple-choices';
		$this->order    = 60;
		$this->group    = 'general';
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( 'First Choice', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( 'Second Choice', 'everest-forms' ),
				'value'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( 'Third Choice', 'everest-forms' ),
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
					'description',
					'required',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
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
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Define additional field properties.
	 *
	 * @param array $properties Field properties.
	 * @param array $field Field data.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {

		$properties['inputs']['primary']['class'][] = 'input-text';

		return $properties;
	}

	/**
	 * Show values field option.
	 *
	 * @param array $field
	 */
	public function show_values( $field ) {
		// Show Values toggle option. This option will only show if already used or if manually enabled by a filter.
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
			$this->field_element(
				'row',
				$field,
				array(
					'slug'    => 'show_values',
					'content' => $show_values,
				)
			);
		}
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

			printf( '<li><input type="radio" %s disabled>%s</li>', $selected, $value['label'] );
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
		$primary     = $field['properties']['inputs']['primary'];
		$field_class = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id    = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_data  = '';
		$form_id     = $form_data['id'];
		$choices     = isset( $field['choices'] ) ? $field['choices'] : array();
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
			$id       = $primary['id'] . '_' . $key;

			printf( '<li class="choice-%d depth-%d">', $key, $depth );

			// Radio elements
			printf(
				'<input type="radio" value="%s" %s %s>',
				esc_attr( $val ),
				evf_html_attributes( $id, $primary['class'], $primary['data'], $primary['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$primary['required'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);

			printf( '<label class="everest-forms-field-label-inline" for="everest-forms-%d-field_%s_%d">%s</label>', $form_id, $field['id'], $key, wp_kses_post( $choice['label'] ) );

			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @param int    $field_id
	 * @param array  $field_submit
	 * @param array  $form_data
	 * @param string $meta_key
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field_submit = (array) $field_submit;
		$field        = $form_data['form_fields'][ $field_id ];
		$dynamic      = ! empty( $field['dynamic_choices'] ) ? $field['dynamic_choices'] : false;
		$name         = sanitize_text_field( $field['label'] );
		$value_raw    = evf_sanitize_array_combine( $field_submit );

		$data = array(
			'name'      => $name,
			'value'     => '',
			'value_raw' => $value_raw,
			'id'        => $field_id,
			'type'      => $this->type,
			'meta_key'  => $meta_key,
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

		// Push field details to be saved.
		EVF()->task->form_fields[ $field_id ] = $data;
	}
}

