<?php
/**
 *  Signature field.
 *
 * @package EverestForms\Fields
 * @since   1.4.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Signature Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * upload, exporters) lives in Everest Forms Pro, whose class loads in place of
 * this one when the plugin is active (this file is only autoloaded when that
 * class is not already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Signature extends EVF_Form_Fields {

	/**
	 * Default value options.
	 *
	 * @var array
	 */
	public $defaults_option_value;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Signature', 'everest-forms' );
		$this->type     = 'signature';
		$this->icon     = 'evf-icon evf-icon-signature';
		$this->order    = 100;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'oF-9ooqfUpk',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
					'option_display',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'label_hide',
					'css',
				),
			),
		);

		$this->defaults_option_value = array(
			'background_color' => 'rgb(255, 255, 255)',
			'pen_color'        => 'rgba(0,0,0,1)',
			'image_format'     => 'png',
		);

		parent::__construct();
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		echo "<canvas style='width:100%;height:100px;max-width:100%;max-height:100%;'></canvas>";

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Option display in sidebar.
	 *
	 * @param array $field Field Data.
	 */
	public function option_display( $field ) {
		$file_format   = $this->defaults_option_value['image_format'];
		$field_options = sprintf( '<input type="hidden"  name="form_fields[%s][signature_file_format]" value="%s" />', esc_attr( $field['id'] ), esc_attr( $file_format ) );

		// Field option row (markup) including label and input.
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'signature',
				'content' => $field_options,
			)
		);
	}
}
