<?php
/**
 * Phone number field
 *
 * @package EverestForms\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Phone Class.
 */
class EVF_Field_Phone extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Phone', 'everest-forms' );
		$this->type     = 'phone';
		$this->icon     = 'evf-icon evf-icon-phone';
		$this->order    = 60;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'choose_format',
					'description',
					'input_mask',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
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
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Date field format option.
	 *
	 * @since 1.2.9
	 * @param array $field Field Data.
	 */
	public function choose_format( $field ) {
		$format        = ! empty( $field['phone_format'] ) ? esc_attr( $field['phone_format'] ) : 'smart';
		$format_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'phone_format',
				'value'   => __( 'Format', 'everest-forms' ),
				'tooltip' => __( 'Select a format for the phone field.', 'everest-forms' ),
			),
			false
		);
		$format_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'phone_format',
				'value'   => $format,
				'options' => array(
					'default' => __( 'Default', 'everest-forms' ),
					'smart'   => __( 'Smart', 'everest-forms' ),
				),
			),
			false
		);
		$args          = array(
			'slug'    => 'phone_format',
			'content' => $format_label . $format_select,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Input mask field option.
	 *
	 * @param array $field Field Data.
	 */
	public function input_mask( $field ) {
		$format = ! empty( $field['phone_format'] ) ? esc_attr( $field['phone_format'] ) : 'smart';

		// Input Mask.
		$input_mask_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'          => 'input_mask',
				'value'         => esc_html__( 'Input Mask', 'everest-forms' ),
				'tooltip'       => esc_html__( 'Enter your custom input mask.', 'everest-forms' ),
				'after_tooltip' => '<a href="https://docs.everestforms.net/docs/how-to-use-custom-input-mask/" class="after-label-description" target="_blank" rel="noopener noreferrer">' . esc_html__( 'See Examples & Docs', 'everest-forms' ) . '</a>',
			),
			false
		);
		$input_mask_field = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'input_mask',
				'value' => ! empty( $field['input_mask'] ) ? esc_attr( $field['input_mask'] ) : '(999) 999-9999',
			),
			false
		);

		echo '<div class="format-selected-' . esc_attr( $format ) . ' format-selected">';

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'input_mask',
				'content' => $input_mask_label . $input_mask_field,
			)
		);

		echo '</div>';
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
		// Input primary: add validation rule and class for smart phone field.
		if ( ! empty( $field['phone_format'] ) && 'smart' === $field['phone_format'] ) {
			$properties['inputs']['primary']['class'][]                        = 'evf-smart-phone-field';
			$properties['inputs']['primary']['data']['rule-smart-phone-field'] = 'true';
		} else {
			$properties['inputs']['primary']['data']['rule-phone-field'] = 'true';

			if ( ! empty( $field['input_mask'] ) ) {
				// Add class that will trigger custom mask.
				$properties['inputs']['primary']['class'][] = 'evf-masked-input';

				// Register string for translation.
				$field['input_mask'] = evf_string_translation( $form_data['id'], $field['id'], $field['input_mask'], '-input-mask' );

				if ( false !== strpos( $field['input_mask'], 'alias:' ) ) {
					$mask = str_replace( 'alias:', '', $field['input_mask'] );
					$properties['inputs']['primary']['data']['inputmask-alias'] = $mask;
				} elseif ( false !== strpos( $field['input_mask'], 'regex:' ) ) {
					$mask = str_replace( 'regex:', '', $field['input_mask'] );
					$properties['inputs']['primary']['data']['inputmask-regex'] = $mask;
				} else {
					$properties['inputs']['primary']['data']['inputmask-mask'] = $field['input_mask'];
				}

				// Input primary: RTL support for input masks.
				if ( is_rtl() ) {
					$properties['inputs']['primary']['attr']['dir'] = 'rtl';
				}
			}
		}

		return $properties;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="text" placeholder="' . esc_attr( $placeholder ) . '" class="widefat primary-input" disabled>';

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
		$primary = $field['properties']['inputs']['primary'];

		// Allow input type to be changed for this particular field.
		$type = apply_filters( 'everest_forms_phone_field_input_type', 'tel' );
		// Primary field.
		printf(
			'<input type="%s" %s %s>',
			esc_attr( $type ),
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}
}
