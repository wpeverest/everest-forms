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
	$color_palettes = array(
		array(
			'label'  => esc_html__( 'Blueberry', 'everest-forms' ),
			'colors' => array(
				'form_background'   => '#ffffff',
				'field_background'  => '#ffffff',
				'field_sublabel'    => '#0f3a57',
				'field_label'       => '#0c2e45',
				'button_text'       => '#ffffff',
				'button_background' => '#3951a5',
			),
		),
		array(
			'label'  => esc_html__( 'Autumn', 'everest-forms' ),
			'colors' => array(
				'form_background'   => '#f7f7f7',
				'field_background'  => '#ffffff',
				'field_sublabel'    => '#666666',
				'field_label'       => '#262626',
				'button_text'       => '#ffffff',
				'button_background' => '#2691d9',
			),
		),
		array(
			'label'  => esc_html__( 'Blackberry', 'everest-forms' ),
			'colors' => array(
				'form_background'   => '#cccccc',
				'field_background'  => '#ffffff',
				'field_sublabel'    => '#333333',
				'field_label'       => '#444444',
				'button_text'       => '#ffffff',
				'button_background' => '#993399',
			),
		),
	);

	foreach ( $color_palettes as $index => $palette ) {
		$colors_with_values = array();
		foreach ( $palette['colors'] as $color_name => $color_value ) {
		
			$colors_with_values[] = array(
				'name'       => $color_name . ' (' . $color_value . ')',
				'color'      => $color_value,
				'color_name' => $color_name,
			);
		}

		$controls['color_palette'][ 'color_' . $index ] = array(
			'setting' => array(
				'default'           => $palette['colors'],
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'postMessage',
			),
			'control' => array(
				'label'   => $palette['label'],
				'section' => 'everest_forms_color_palette',
				'type'    => 'EVF_Customize_Color_Palette_Control',
				'choices' => $colors_with_values,
			),
		);
	}

	return $controls;
}
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_color_palette_controls', 10, 2 );
