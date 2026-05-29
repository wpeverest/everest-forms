<?php
/**
 * Section Title field.
 *
 * @package EverestForms_Pro\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Title Class.
 */
class EVF_Field_Title extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = __( 'Section Title', 'everest-forms' );
		$this->type     = 'title';
		$this->icon     = 'evf-icon evf-icon-section-divider';
		$this->order    = 90;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'label_disable',
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
	 * Label disable field option.
	 *
	 * @param array $field Field data.
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
		$label   = $field['properties']['label'];

		// Primary field.
		if ( ! empty( $label['value'] ) ) {
			printf(
				'<h3 %s>%s</h3>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_html( evf_string_translation( absint( $form_data['id'] ), $field['id'], $label['value'] ) )
			);
		}
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @param int    $field_id     Field id.
	 * @param array  $field_submit Field submit value.
	 * @param array  $form_data    Form data object.
	 * @param string $meta_key     Meta key data for the field.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {}
}
