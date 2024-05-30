<?php
/**
 * EverestForms Button Config Functions
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
function evf_style_customizer_form_wrapper_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_wrapper' => array(
				'title'              => esc_html__( 'Form Wrapper', 'everest-forms' ),
				'description'        => esc_html__( 'This is form wrapper description.', 'everest-forms' ),
				'priority'           => 10,
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_form_wrapper_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array                    $controls  Array of controls.
 * @param EVF_Style_Customizer_API $customize EVF_Style_Customizer_API instance.
 */
function evf_style_customizer_wrapper_controls( $controls, $customize ) {
	$controls['wrapper'] = array(
		'width'                 => array(
			'setting' => array(
				'default'           => '100',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Width', 'everest-forms' ),
				'description' => esc_html__( 'Choose a form width (in %).', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 50,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'font_family'           => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Family', 'everest-forms' ),
				'description' => esc_html__( 'Select a desire Google font.', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Select2_Control',
				'input_attrs' => array(
					'data-allow_clear' => true,
					'data-placeholder' => _x( 'Select Font Family&hellip;', 'enhanced select', 'everest-forms' ),
				),
				'custom_args' => array(
					'google_font' => true,
				),
			),
		),
		'background_color'      => array(
			'setting' => array(
				'default' => '#ffffff',
			),
			'control' => array(
				'label'       => esc_html__( 'Background Color', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'background_image'      => array(
			'setting' => array(
				'default'           => get_theme_support( 'custom-background', 'default-image' ),
				'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
			),
			'control' => array(
				'label'   => esc_html__( 'Background Image', 'everest-forms' ),
				'section' => 'everest_forms_wrapper',
				'type'    => 'EVF_Customize_Background_Image_Control',
			),
		),
		'opacity'               => array(
			'setting' => array(
				'default'           => '1',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Image Opacity', 'everest-forms' ),
				'description' => esc_html__( 'Choose a Image opacity (in %).', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 0.0,
					'max'  => 1.0,
					'step' => 0.1,
				),
			),
		),
		'background_preset'     => array(
			'setting' => array(
				'default'           => get_theme_support( 'custom-background', 'default-preset' ),
				'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
			),
			'control' => array(
				'label'   => esc_html__( 'Background Preset', 'everest-forms' ),
				'section' => 'everest_forms_wrapper',
				'type'    => 'select',
				'choices' => array(
					'default' => _x( 'Default', 'Default Preset', 'everest-forms' ),
					'fill'    => esc_html__( 'Fill Screen', 'everest-forms' ),
					'fit'     => esc_html__( 'Fit to Screen', 'everest-forms' ),
					'repeat'  => _x( 'Repeat', 'Repeat Image', 'everest-forms' ),
					'custom'  => _x( 'Custom', 'Custom Preset', 'everest-forms' ),
				),
			),
		),
		'background_position'   => array(
			'settings' => array(
				'background_position_x' => array(
					'default'           => get_theme_support( 'custom-background', 'default-position-x' ),
					'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
				),
				'background_position_y' => array(
					'default'           => get_theme_support( 'custom-background', 'default-position-y' ),
					'theme_supports'    => 'custom-background',
					'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
				),
			),
			'control'  => array(
				'label'    => esc_html__( 'Image Position', 'everest-forms' ),
				'section'  => 'everest_forms_wrapper',
				'type'     => 'WP_Customize_Background_Position_Control',
				'settings' => array(
					'x' => 'background_position_x',
					'y' => 'background_position_y',
				),
			),
		),
		'background_size'       => array(
			'setting' => array(
				'default'           => get_theme_support( 'custom-background', 'default-size' ),
				'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
			),
			'control' => array(
				'label'   => esc_html__( 'Image Size', 'everest-forms' ),
				'section' => 'everest_forms_wrapper',
				'type'    => 'select',
				'choices' => array(
					'auto'    => esc_html__( 'Original', 'everest-forms' ),
					'contain' => esc_html__( 'Fit to Screen', 'everest-forms' ),
					'cover'   => esc_html__( 'Fill Screen', 'everest-forms' ),
				),
			),
		),
		'background_repeat'     => array(
			'setting' => array(
				'default'           => get_theme_support( 'custom-background', 'default-repeat' ),
				'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
			),
			'control' => array(
				'label'   => esc_html__( 'Repeat Background Image', 'everest-forms' ),
				'section' => 'everest_forms_wrapper',
				'type'    => 'checkbox',
			),
		),
		'background_attachment' => array(
			'setting' => array(
				'default'           => get_theme_support( 'custom-background', 'default-attachment' ),
				'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
			),
			'control' => array(
				'label'   => esc_html__( 'Scroll with Page', 'everest-forms' ),
				'section' => 'everest_forms_wrapper',
				'type'    => 'checkbox',
			),
		),
		'border_type'           => array(
			'setting' => array(
				'default'           => 'none',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Border Type', 'everest-forms' ),
				'description' => esc_html__( 'This is form wrapper border type', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'choices'     => array(
					'none'    => esc_html__( 'None', 'everest-forms' ),
					'hidden'  => esc_html__( 'Hidden', 'everest-forms' ),
					'dotted'  => esc_html__( 'Dotted', 'everest-forms' ),
					'dashed'  => esc_html__( 'Dashed', 'everest-forms' ),
					'solid'   => esc_html__( 'Solid', 'everest-forms' ),
					'double'  => esc_html__( 'Double', 'everest-forms' ),
					'groove'  => esc_html__( 'Groove', 'everest-forms' ),
					'ridge'   => esc_html__( 'Ridge', 'everest-forms' ),
					'inset'   => esc_html__( 'Inset', 'everest-forms' ),
					'outset'  => esc_html__( 'Outset', 'everest-forms' ),
					'initial' => esc_html__( 'Initial', 'everest-forms' ),
					'inherit' => esc_html__( 'Inherit', 'everest-forms' ),
				),
			),
		),
		'border_width'          => array(
			'setting' => array(
				'default' => array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Border Width', 'everest-forms' ),
				'description' => esc_html__( 'This is a form wrapper border width.', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Dimension_Control',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 50,
					'step' => 1,
				),
				'custom_args' => array(
					'anchor'     => true,
					'input_type' => 'number',
				),
			),
		),
		'border_color'          => array(
			'setting' => array(
				'default' => '#969696',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form border color.', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'border_radius'         => array(
			'setting' => array(
				'default' => array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
					'unit'   => 'px',
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Border Radius', 'everest-forms' ),
				'description' => esc_html__( 'This is a form border radius.', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Dimension_Control',
				'input_attrs' => array(
					'min' => 0,
				),
				'custom_args' => array(
					'anchor'       => true,
					'input_type'   => 'number',
					'unit_choices' => array(
						'px' => esc_attr__( 'PX', 'everest-forms' ),
						'%'  => esc_attr__( '%', 'everest-forms' ),
					),
				),
			),
		),
		'margin'                => array(
			'setting' => array(
				'default' => array(
					'desktop' => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 30,
						'left'   => 0,
					),
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Form Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a form margin.', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'padding'               => array(
			'setting' => array(
				'default' => array(
					'desktop' => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 0,
						'left'   => 0,
					),
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Form Padding', 'everest-forms' ),
				'description' => esc_html__( 'This is a form padding.', 'everest-forms' ),
				'section'     => 'everest_forms_wrapper',
				'type'        => 'EVF_Customize_Dimension_Control',
				'input_attrs' => array(
					'min' => 0,
				),
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
	);

	return $controls;
}
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_wrapper_controls', 10, 2 );
