<?php
/**
 * Textarea field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Textarea class.
 */
class EVF_Field_Textarea extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Paragraph Text', 'everest-forms' );
		$this->type     = 'textarea';
		$this->icon     = 'evf-icon evf-icon-paragraph';
		$this->order    = 40;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'size',
					'placeholder',
					'label_hide',
					'limit_length',
					'default_value',
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
	}

	/**
	 * Limit length field option.
	 *
	 * @param array $field Field settings.
	 */
	public function limit_length( $field ) {
		// Limit length.
		$args = array(
			'slug'    => 'limit_enabled',
			'content' => $this->field_element(
				'checkbox',
				$field,
				array(
					'slug'    => 'limit_enabled',
					'value'   => isset( $field['limit_enabled'] ),
					'desc'    => esc_html__( 'Limit Length', 'everest-forms' ),
					'tooltip' => esc_html__( 'Check this option to limit text length by characters or words count.', 'everest-forms' ),
				),
				false
			),
		);
		$this->field_element( 'row', $field, $args );

		// Limit controls.
		$count = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'class' => 'small-text',
				'slug'  => 'limit_count',
				'attrs' => array(
					'min'     => 1,
					'step'    => 1,
					'pattern' => '[0-9]',
				),
				'value' => ! empty( $field['limit_count'] ) ? absint( $field['limit_count'] ) : 1,
			),
			false
		);

		$mode = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'limit_mode',
				'class'   => 'limit-select',
				'value'   => ! empty( $field['limit_mode'] ) ? esc_attr( $field['limit_mode'] ) : 'characters',
				'options' => array(
					'characters' => esc_html__( 'Characters', 'everest-forms' ),
					'words'      => esc_html__( 'Words Count', 'everest-forms' ),
				),
			),
			false
		);
		$args = array(
			'slug'    => 'limit_controls',
			'class'   => ! isset( $field['limit_enabled'] ) ? 'everest-forms-hidden' : '',
			'content' => $count . $mode,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Enqueue shortcode scripts.
	 *
	 * @param array $atts Shortcode Attributes.
	 */
	public function load_assets( $atts ) {
		$form_id   = isset( $atts['id'] ) ? wp_unslash( $atts['id'] ) : ''; // WPCS: CSRF ok, input var ok, sanitization ok.
		$form_obj  = evf()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		// Leave only fields with limit.
		if ( ! empty( $form_data['form_fields'] ) ) {
			$form_fields = array_filter( $form_data['form_fields'], array( $this, 'field_is_limit' ) );

			if ( count( $form_fields ) ) {
				wp_enqueue_script( 'everest-forms-text-limit' );
			}
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
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<textarea placeholder="' . esc_attr( $placeholder ) . '" class="widefat" disabled></textarea>';

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
		$value   = '';
		$primary = $field['properties']['inputs']['primary'];

		if ( isset( $primary['attr']['value'] ) ) {
			$value = evf_sanitize_textarea_field( $primary['attr']['value'] );
			unset( $primary['attr']['value'] );
		}

		// Limit length.
		if ( isset( $field['limit_enabled'] ) ) {
			$limit_count = isset( $field['limit_count'] ) ? absint( $field['limit_count'] ) : 0;
			$limit_mode  = isset( $field['limit_mode'] ) ? sanitize_key( $field['limit_mode'] ) : 'characters';

			$primary['data']['form-id']  = $form_data['id'];
			$primary['data']['field-id'] = $field['id'];

			if ( 'characters' === $limit_mode ) {
				$primary['class'][]            = 'everest-forms-limit-characters-enabled';
				$primary['attr']['maxlength']  = $limit_count;
				$primary['data']['text-limit'] = $limit_count;
			} else {
				$primary['class'][]            = 'everest-forms-limit-words-enabled';
				$primary['data']['text-limit'] = $limit_count;
			}
		}

		// Primary field.
		printf(
			'<textarea %s %s>%s</textarea>',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] ),
			$value // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
