<?php
/**
 * Hidden text field
 *
 * @package EverestForms\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Hidden Class.
 */
class EVF_Field_Hidden extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Hidden Field', 'everest-forms' );
		$this->type     = 'hidden';
		$this->icon     = 'evf-icon evf-icon-hidden';
		$this->order    = 50;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options' => array(
				'field_options' => array(
					'label',
					'meta',
					'label_disable',
					'default_value',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Label disable field option.
	 *
	 * @param array $field Field settings.
	 */
	public function label_disable( $field ) {
		$args = array(
			'type'  => 'hidden',
			'slug'  => 'label_disable',
			'value' => '1',
		);
		$this->field_element( 'text', $field, $args );
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
		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// For edit purpose.
		$is_edit_entry = isset( $_GET['edit-entry'] ) && sanitize_text_field( wp_unslash( $_GET['edit-entry'] ) ) ? true : false;
		$field_type    = $is_edit_entry ? 'text' : 'hidden';

		// Primary field.
		printf(
			'<input type="%s" %s>',
			$field_type,
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
		);
	}
}
