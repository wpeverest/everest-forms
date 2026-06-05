<?php
/**
 * Reset field
 *
 * @package EverestForms\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Reset Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * processing) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Reset extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Reset', 'everest-forms' );
		$this->type     = 'reset';
		$this->icon     = 'evf-icon evf-icon-reset';
		$this->order    = 15;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'M7GE7EwSgx8',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'button_text',
					'button_type',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Apply Button Text.
	 *
	 * @param array $field Field data object.
	 */
	public function button_text( $field ) {

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'button_text',
				'value'   => esc_html__( 'Button Text', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add text to the reset button', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'button_text',
				'value' => ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Reset', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'button_text',
				'content' => $lbl . $fld,
			)
		);
	}


	/**
	 * Apply Button Text.
	 *
	 * @param array $field Field data object.
	 */
	public function button_type( $field ) {

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'button_type',
				'value'   => esc_html__( 'Button Type', 'everest-forms' ),
				'tooltip' => esc_html__( '1. Reset - Removes all the field values except the default values.
				2. Clear - Removes all field values, including the default values.
				', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'button_type',
				'value'   => ! empty( $field['button_type'] ) ? esc_attr( $field['button_type'] ) : 'reset',
				'options' =>
					array(
						'reset'  => esc_html__( 'Reset', 'everest-forms' ),
						'button' => esc_html__( 'Clear', 'everest-forms' ),
					),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'button_text',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Label.
		$this->field_preview_option( 'label', $field );

		// Define data.
		$button_label = ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Reset', 'everest-forms' );

		// Primary Input.

		echo '<div class="everest-forms-reset-button">';

		printf(
			'<button type="button" class="evf-reset-button" disabled>%s</button>',
			esc_html( $button_label )
		);

		echo '</div>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
