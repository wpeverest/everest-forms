<?php
/**
 * Hidden text field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Hidden Class.
 */
class EVF_Field_Recaptcha extends \EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'reCaptcha', 'everest-forms' );
		$this->type     = 'recaptcha';
		$this->icon     = 'evf-icon evf-icon-recaptcha';
		$this->order    = 241;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options' => array(
				'field_options' => array(
					'label',
					'meta',
				),
			),
		);

		parent::__construct();
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

		// Default value.
		$default_value = isset( $field['default_value'] ) && ! empty( $field['default_value'] ) ? $field['default_value'] : '';

		// Primary input.
		echo '<input type="text" value="' . esc_attr( $default_value ) . '" class="widefat" disabled>';
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
		// // Define data.
		// $primary = $field['properties']['inputs']['primary'];

		// // Primary field.
		// printf(
		// 	'<input type="hidden" %s>',
		// 	evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
		// );
	}
}
