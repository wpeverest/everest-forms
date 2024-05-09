<?php
/**
 * WysiWyg field.
 *
 * @package EverestForms\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_wysiwyg class.
 */
class EVF_Field_Wysiwyg extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'WYSIWYG', 'everest-forms' );
		$this->type     = 'wysiwyg';
		$this->icon     = 'evf-icon evf-icon-wysiwyg';
		$this->order    = 170;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
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
		add_filter( 'everest_forms_html_field_value', array( $this, 'html_field_value' ), 10, 4 );
		add_filter( 'everest_forms_entry_single_data', array( $this, 'entry_single_data' ), 10, 2 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
	}

	/**
	 * Returns values for single form entries.
	 *
	 * @since 1.4.8
	 *
	 * @param string $value     Field value.
	 * @param array  $field     Field settings.
	 * @param array  $form_data Form data and settings.
	 * @param string $context   Value display context.
	 *
	 * @return string HTML Field Value to show in the single entries.
	 */
	public function html_field_value( $value, $field, $form_data = array(), $context = '' ) {
		if ( is_serialized( $field ) || in_array( $context, array( 'email-html', 'export-pdf' ), true ) ) {
			$field_value = maybe_unserialize( $field );
			if ( ! empty( $field_value['type'] ) && $this->type === $field_value['type'] ) {
				return ! empty( $field_value['value'] ) ? $field_value['value'] : '';
			}
		}
		return $value;
	}

	/**
	 * Hook in tabs.
	 *
	 * @since 1.4.8
	 *
	 * @param String $meta      Meta Value.
	 * @param mixed  $entry      Entry.
	 */
	public function entry_single_data( $meta, $entry ) {
		foreach ( evf_decode( $entry->fields ) as $key => $entry ) {
			if ( $this->type === $entry['type'] ) {
				$meta[ $entry['meta_key'] ] = serialize(
					array(
						'type'  => $this->type,
						'value' => $entry['value_raw'],
					)
				);
			}
		}
		return $meta;
	}


	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.8.2.4
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
			$settings = array(
				'media_buttons' => false,
				'editor_class'  => 'wysiwyg input-text widefat ',
			);
			wp_editor( '', 'evf-input-type-wysiwyg', $settings );

			// Description.
			$this->field_preview_option( 'description', $field );
	}

		/**
		 * Field display on the form front-end.
		 *
		 * @since 1.8.2.4
		 *
		 * @param array $field Field Data.
		 * @param array $field_atts Field attributes.
		 * @param array $form_data All Form Data.
		 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Define data.
		$value             = '';
		$primary           = $field['properties']['inputs']['primary'];
		$conditional_id    = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$conditional_rules = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';

		if ( isset( $primary['attr']['value'] ) ) {
			$value = evf_sanitize_textarea_field( $primary['attr']['value'] );
			unset( $primary['attr']['value'] );
		}

		printf(
			'<div id="everest_form_wysiwyg_%s"   class="input-text" data-form-id="%s" data-field-id="%s" conditional_rules="%s" conditional_id="%s">',
			esc_html( $field['id'] ),
			esc_html( $form_data['id'] ),
			esc_html( $field['id'] ),
			esc_attr( $conditional_rules ),
			esc_attr( $conditional_id )
		);
		// Primary field.
		$settings = array(
			'media_buttons' => false,
			'editor_class'  => 'evf-wysiwyg ' . implode( ' ', $primary['class'] ),
			'textarea_name' => $primary['attr']['name'],
		);
		ob_start();
		wp_editor( $value, $primary['id'], $settings );
		$output = ob_get_clean();
		printf( $output ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '</div>' );
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since 1.4.8
	 *
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Field.
	 * @param array  $form_data All Form Data.
	 * @param string $meta_key Field Meta Key.
	 * @return void
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field                                = $form_data['form_fields'][ $field_id ];
		$name                                 = make_clickable( $field['label'] );
		evf()->task->form_fields[ $field_id ] = array(
			'name'      => $name,
			'value'     => evf_sanitize_textarea( $field_submit ),
			'value_raw' => wp_kses_post( $field_submit ),
			'id'        => $field_id,
			'type'      => $this->type,
			'meta_key'  => $meta_key,
		);
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @since 1.4.8
	 *
	 * @param array $field Field Data.
	 * @return array Data for field exporter PDF or Email.
	 */
	public function field_exporter( $field ) {
		$empty_message = '<em>' . __( '(empty)', 'everest-forms-repeater-fields' ) . '</em>';
		$field_value   = maybe_unserialize( $field );
		$field_type    = isset( $field_value['type'] ) ? sanitize_text_field( $field_value['type'] ) : $this->type;

		if ( array_key_exists( 'value_raw', $field_value ) && ! empty( $field_value['value_raw'] ) ) {

			if ( ! in_array( $field['meta_key'], apply_filters( 'everest_forms_hidden_entry_fields', array() ), true ) ) {
				$output = $field['value_raw'];
			}
		}

		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => ! empty( $output ) ? $output : false,
		);
	}

}
