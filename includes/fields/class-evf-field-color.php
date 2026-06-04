<?php
/**
 * Color field
 *
 * @package EverestForms\Fields
 * @since   1.9.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Color Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * processing, exporters) lives in Everest Forms Pro, whose class loads in place
 * of this one when the plugin is active (this file is only autoloaded when that
 * class is not already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Color extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Color', 'everest-forms' );
		$this->type     = 'color';
		$this->icon     = 'evf-icon evf-icon-color';
		$this->order    = 210;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'M9Rb0RWrf2I',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'meta',
					'default_color',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Color field default value option.
	 *
	 * @param array $field Field settings.
	 */
	public function default_color( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'default',
				'value'   => esc_html__( 'Default Color', 'everest-forms' ),
				'tooltip' => esc_html__( 'Choose the default color selected in the field.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'default',
				'value' => ! empty( $field['default'] ) ? esc_attr( $field['default'] ) : '#000000',
				'class' => 'evf-colorpicker',
				'data'  => array(
					'default-color' => '#000000',
				),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'default',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.6.1
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$default = ! empty( $field['default'] ) ? esc_attr( $field['default'] ) : '#000000';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<div class="evf-color-picker-bg" style="background: ' . esc_attr( $default ) . ';"></div><input type="text" class="widefat colorpickpreview" disabled>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
