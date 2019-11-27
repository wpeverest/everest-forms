<?php
/**
 * Text field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Text class.
 */
class EVF_Field_Text extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Single Line Text', 'everest-forms' );
		$this->type     = 'text';
		$this->icon     = 'evf-icon evf-icon-text';
		$this->order    = 30;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'label_hide',
					'limit_length',
					'default_value',
					'css',
					'input_mask',
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

		$count = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
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
				'value'   => ! empty( $field['limit_mode'] ) ? esc_attr( $field['limit_mode'] ) : 'characters',
				'options' => array(
					'characters' => esc_html__( 'Characters', 'everest-forms' ),
					'words'      => esc_html__( 'Words', 'everest-forms' ),
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
	 * Input mask field option.
	 *
	 * @param array $field Field settings.
	 */
	public function input_mask( $field ) {
		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'          => 'input_mask',
				'value'         => esc_html__( 'Input Mask', 'everest-forms' ),
				'tooltip'       => esc_html__( 'Enter your custom input mask.', 'everest-forms' ),
				'after_tooltip' => '<a href="https://docs.wpeverest.com/docs/everest-forms/how-to-use-custom-input-mask/" class="after-label-description" target="_blank" rel="noopener noreferrer">' . esc_html__( 'See Examples & Docs', 'everest-forms' ) . '</a>',
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'input_mask',
				'value' => ! empty( $field['input_mask'] ) ? esc_attr( $field['input_mask'] ) : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'input_mask',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Define additional field properties.
	 *
	 * @param  array $properties Field properties.
	 * @param  array $field      Field settings.
	 * @param  array $form_data  Form data and settings.
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		// Input primary: Detect custom input mask.
		if ( ! empty( $field['input_mask'] ) ) {
			// Add class that will trigger custom mask.
			$properties['inputs']['primary']['class'][] = 'evf-masked-input';

			if ( false !== strpos( $field['input_mask'], 'alias:' ) ) {
				$mask = str_replace( 'alias:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-alias'] = $mask;
			} elseif ( false !== strpos( $field['input_mask'], 'regex:' ) ) {
				$mask = str_replace( 'regex:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-regex'] = $mask;
			} elseif ( false !== strpos( $field['input_mask'], 'date:' ) ) {
				$mask = str_replace( 'date:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-alias']       = 'datetime';
				$properties['inputs']['primary']['data']['inputmask-inputformat'] = $mask;
			} else {
				$properties['inputs']['primary']['data']['inputmask-mask'] = $field['input_mask'];
			}
		}

		return $properties;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @param array $field
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="text" placeholder="' . $placeholder . '" class="widefat" disabled>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @param array $field
	 * @param array $deprecated
	 * @param array $form_data
	 */
	public function field_display( $field, $deprecated, $form_data ) {
		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Primary field.
		printf(
			'<input type="text" %s %s>',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}
}
