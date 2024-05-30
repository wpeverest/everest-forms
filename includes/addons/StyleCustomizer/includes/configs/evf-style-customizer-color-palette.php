<?php
/**
 * EverestForms Color Palette Config Functions.
 *
 * @package EverestForms_Style_Customizer/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add everest forms button customizer sections
 *
 * @param array $sections Array of sections.
 */
function evf_style_customizer_color_palette_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_color_palette' => array(
				'title'              => esc_html__( 'Color Palette', 'everest-forms' ),
				'description'        => esc_html__( 'This is color palette description.', 'everest-forms' ),
				'priority'           => 10,
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_color_palette_sections' );


/**
 * Add everest forms style customizer controls.
 *
 * @param array                    $controls  Array of controls.
 * @param EVF_Style_Customizer_API $customize EVF_Style_Customizer_API instance.
 */
function evf_style_customizer_color_palette_controls( $controls, $customize ) {
	$controls['color_palette'] = array(
		'color' => array(
			'setting' => array(
				'default'           => '#ff0000',
				'sanitize_callback' => 'sanitize_hex_color',
			),
			'control' => array(
				'label'   => esc_html__( 'Blue Berry', 'everest-forms' ),
				'section' => 'everest_forms_color_palette',
				'type'    => 'EVF_Customize_Color_Palette_Control',
				'choices' => array(
					'form_background'   => array(
						'name'  => 'Form Background',
						'color' => '#fffff',
					),
					'field_background'  => array(
						'name'  => 'Field Background',
						'color' => '#fffff',
					),
					'field_sublabel'    => array(
						'name'  => 'Field Sublabel/Description',
						'color' => '#0f3a57',
					),
					'field_label'       => array(
						'name'  => 'Field Label',
						'color' => '#0c2e45',
					),
					'button_text'       => array(
						'name'  => 'Button Text',
						'color' => '#fffff',
					),
					'button_background' => array(
						'name'  => 'Button Background',
						'color' => '#3951a5',
					),
				),
			),
		),

	);

	return $controls;
}
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_color_palette_controls', 10, 2 );
