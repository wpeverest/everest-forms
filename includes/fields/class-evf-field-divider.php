<?php
/**
 * Divider field.
 *
 * @package EverestForms_Pro\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Divider Class.
 */
class EVF_Field_Divider extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = __( 'Divider', 'everest-forms' );
		$this->type     = 'divider';
		$this->icon     = 'evf-icon evf-icon-divider';
		$this->order    = 85;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'divider_type',
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
	 * Type field option.
	 *
	 * @param array $field Field data.
	 */
	public function divider_type( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'divider_type',
				'value'   => esc_html__( 'Divider Type', 'everest-forms' ),
				'tooltip' => sprintf( esc_html__( 'Select the type of seperator displayed on frontend.', 'everest-forms' ) ),
			),
			false
		);
		$fld  = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'divider_type',
				'value'   => ! empty( $field['divider_type'] ) ? $field['divider_type'] : '',
				'options' => array(
					'default' => esc_html__( 'Plain', 'everest-forms' ),
					'dashed'  => esc_html__( 'Dashed', 'everest-forms' ),
					'dotted'  => esc_html__( 'Dotted', 'everest-forms' ),
					'thick'   => esc_html__( 'Thick', 'everest-forms' ),
					'double'  => esc_html__( 'Double', 'everest-forms' ),
				),
			),
			false
		);
		$args = array(
			'slug'    => 'divider_type',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.7.5
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$divider_type = ! empty( $field['divider_type'] ) ? esc_attr( $field['divider_type'] ) : '';
		printf( '<hr class = "evf-divider %s"/>', esc_attr( $divider_type ) );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.7.5
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Setup and sanitize the necessary data.
		$primary          = $field['properties']['inputs']['primary'];
		$field            = apply_filters( 'everest_forms_select_field_display', $field, $field_atts, $form_data );
		$divider_type     = ! empty( $field['divider_type'] ) ? esc_attr( $field['divider_type'] ) : '';
		$primary['class'] = array_merge( $primary['class'], array( 'evf-divider ', $divider_type ) );
		printf(
			'<hr %s/>',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
		);
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
