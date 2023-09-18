<?php
/**
 * AI  field
 *
 * @package EverestForms\Fields
 * @since   1.9.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_AI  Class.
 */
class EVF_Field_AI extends EVF_Form_Fields {


	/**
	 * Primary class constructor.
	 */
	public function __construct() {

		if ( ! class_exists( '\EverestForms\AI' ) ) {
			return;
		}

		$this->name     = esc_html__( 'AI', 'everest-forms' );
		$this->type     = 'ai';
		$this->icon     = 'evf-icon evf-icon-ai';
		$this->order    = 240;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'ai_chatbot',
					'ai_prompt',
					'ai_type',
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
	 * AI chatbot.
	 *
	 * @param array $field Field data.
	 */
	public function ai_chatbot( $field ) {
		$value             = ! empty( $field['ai_chatbot'] ) ? esc_attr( $field['ai_chatbot'] ) : '';
		$ai_prompt_chatbot = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'ai_chatbot',
				'value'   => $value,
				'desc'    => esc_html__( 'Enable Chatbot', 'everest-forms' ),
				/* translators: %1$s -  ai settings docs url */
				'tooltip' => sprintf( esc_html__( 'Check this option to enable chatbot. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/ai/#5-toc-title' ) ),
			),
			false
		);
		$ai_prompt_chatbot = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'ai_chatbot',
				'content' => $ai_prompt_chatbot,
			),
			false
		);
		$args              = array(
			'slug'    => 'ai_chatbot',
			'content' => $ai_prompt_chatbot,
		);
		$this->field_element( 'row', $field, $args );
	}



	/**
	 * AI Prompt field option.
	 *
	 * @param array $field Field data.
	 */
	public function ai_prompt( $field ) {
		$ai_prompt        = ! empty( $field['ai_input'] ) ? sanitize_text_field( $field['ai_input'] ) : '';
		$ai_chatbot_input = ! empty( $field['ai_chatbot_input'] ) ? sanitize_text_field( $field['ai_chatbot_input'] ) : '';
		$ai_prompt_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'ai_input',
				'value'   => esc_html__( 'Prompt', 'everest-forms' ),
				/* translators: %1$s -  ai settings docs url */
				'tooltip' => sprintf( esc_html__( 'Enter a question or choose a field in the prompt to generate a response. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/ai/#7-toc-title' ) ),
			),
			false
		);
		$ai_prompt_input  = $this->field_element(
			'textarea',
			$field,
			array(
				'slug'        => 'ai_input',
				'value'       => $ai_prompt,
				'placeholder' => 'Enter a question or choose a field in the prompt to generate a response',
			),
			false
		);
		$ai_prompt_input .= '<a href="#" class="evf-toggle-smart-tag-display" data-type="fields"><span class="dashicons dashicons-editor-code"></span></a>';
		$ai_prompt_input .= '<div class="evf-smart-tag-lists" style="display: none">';
		$ai_prompt_input .= '<div class="smart-tag-title other-tag-title">Available fields</div><ul class="evf-fields"></ul></div>';
		$args             = array(
			'slug'    => 'ai_input',
			'content' => $ai_prompt_label . $ai_prompt_input,
			'class'   => isset( $field['ai_chatbot'] ) ? 'hidden' : '',
		);
		$this->field_element( 'row', $field, $args );

		$ai_prompt_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'ai_chatbot_input',
				'value'   => esc_html__( 'Field Mapping', 'everest-forms' ),
				/* translators: %1$s -  ai settings docs url */
				'tooltip' => sprintf( esc_html__( 'Click on <> and map the field for your question. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/ai/#5-toc-title' ) ),
			),
			false
		);
		$ai_prompt_input  = $this->field_element(
			'textarea',
			$field,
			array(
				'slug'        => 'ai_chatbot_input',
				'value'       => $ai_chatbot_input,
				'placeholder' => 'Enter a question or choose a field in the prompt to generate a response.',
			),
			false
		);
		$ai_prompt_input .= '<a href="#" class="evf-toggle-smart-tag-display" data-type="ai-fields"><span class="dashicons dashicons-editor-code"></span></a>';
		$ai_prompt_input .= '<div class="evf-smart-tag-lists" style="display: none">';
		$ai_prompt_input .= '<div class="smart-tag-title other-tag-title">Available fields</div><ul class="evf-fields-ai"></ul></div>';
		$args             = array(
			'slug'    => 'ai_chatbot_input',
			'content' => $ai_prompt_label . $ai_prompt_input,
			'class'   => isset( $field['ai_chatbot'] ) ? '' : 'hidden',
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Ai type field option.
	 *
	 * @param array $field Field data.
	 */
	public function ai_type( $field ) {
		$ai_type         = ! empty( $field['ai_type'] ) ? esc_attr( $field['ai_type'] ) : 'hidden';
		$ai_format_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'ai_type',
				'value'   => esc_html__( 'Field Type', 'everest-forms' ),
				/* translators: %1$s -  ai settings docs url */
				'tooltip' => esc_html__( 'Please select the field type.', 'everest-forms' ),
			),
			false
		);
		if ( 'hidden' === $ai_type ) {
			$ai_format_select = $this->field_element(
				'select',
				$field,
				array(
					'slug'    => 'ai_type',
					'value'   => $ai_type,
					'options' => array(
						'hidden' => esc_html__( 'Hidden', 'everest-forms' ),
					),
				),
				false
			);
		} else {
			$ai_format_select = $this->field_element(
				'select',
				$field,
				array(
					'slug'    => 'ai_type',
					'value'   => $ai_type,
					'options' => array(
						'textarea' => esc_html__( 'Textarea', 'everest-forms' ),
						'html'     => esc_html__( 'HTML', 'everest-forms' ),
					),
				),
				false
			);
		}

		$args = array(
			'slug'    => 'ai_type',
			'content' => $ai_format_label . $ai_format_select,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.6.1
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
			// Label.
			$this->field_preview_option( 'label', $field );

			// Default value.
			$default_value = isset( $field['default_value'] ) && ! empty( $field['default_value'] ) ? $field['default_value'] : '';

			// Primary input.
			echo '<input type="text" value="' . esc_attr( $default_value ) . '" class="widefat" disabled>';
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
		$ai_type = ! empty( $field['ai_type'] ) ? $field['ai_type'] : 'hidden';
		if ( isset( $field['ai_chatbot'] ) ) {
			$properties['inputs']['primary']['attr']['ai_chatbot'] = $field['ai_chatbot'];
		}
		if ( 'hidden' === $ai_type ) {
			$properties['label']['attr']['style'] = 'display:none';
		}
		return $properties;
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
		$value   = '';
		$primary = $field['properties']['inputs']['primary'];
		$ai_type = ! empty( $field['ai_type'] ) ? $field['ai_type'] : 'hidden';
		switch ( $ai_type ) {
			case 'hidden':
				printf(
					'<input type="hidden" %s>',
					evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
				);
				break;
			case 'textarea':
				printf(
					'<textarea %s %s >%s</textarea>',
					evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
					esc_attr( $primary['required'] ),
					esc_html( $value )
				);
				break;
			case 'html':
				printf(
					'<div %s>%s</div>',
					evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
					esc_html( $value )
				);
				break;
		}
	}
}
