<?php
/**
 * EverestForms Field Style Config Functions
 *
 * @package EverestForms_Style_Customizer/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add everest forms style customizer sections.
 *
 * @param array $sections Array of sections.
 */
function evf_style_customizer_field_styles_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_field_styles' => array(
				'title'              => esc_html__( 'Field Styles', 'everest-forms' ),
				'description'        => esc_html__( 'This is field styles description.', 'everest-forms' ),
				'priority'           => 10,
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_field_styles_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array $controls Array of controls.
 */
function evf_style_customizer_field_styles_controls( $controls ) {
	$controls['field_styles'] = array(
		'font_size'              => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style font size in px.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'font_color'             => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style font color.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'placeholder_font_color' => array(
			'setting' => array(
				'default' => '#c6ccd7',
			),
			'control' => array(
				'label'       => esc_html__( 'Placeholder Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style placeholder font color.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'font_style'             => array(
			'setting' => array(
				'default' => array(
					'bold'      => false,
					'italic'    => false,
					'underline' => false,
					'uppercase' => false,
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Font Style', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style font style.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'alignment'              => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => esc_html__( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => esc_html__( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => esc_html__( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'border_type'            => array(
			'setting' => array(
				'default'           => 'solid',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Border Type', 'everest-forms' ),
				'description' => esc_html__( 'This is form field border type', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
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
		'border_width'           => array(
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
				'description' => esc_html__( 'This is a form field border width.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Dimension_Control',
				'input_attrs' => array(
					'min' => 0,
				),
				'custom_args' => array(
					'anchor'     => true,
					'input_type' => 'number',
				),
			),
		),
		'border_color'           => array(
			'setting' => array(
				'default' => '#969696',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style border color.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'border_focus_color'     => array(
			'setting' => array(
				'default' => '#7ca8eb',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Focus Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style border color on focus.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'border_radius'          => array(
			'setting' => array(
				'default' => array(
					'top'    => 3,
					'right'  => 3,
					'bottom' => 3,
					'left'   => 3,
					'unit'   => 'px',
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Border Radius', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field border radius.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
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
		'background_color'       => array(
			'setting' => array(
				'default' => 'rgba(255,255,255,0.99)',
			),
			'control' => array(
				'label'       => esc_html__( 'Background Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style background color.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'margin'                 => array(
			'setting' => array(
				'default' => array(
					'desktop' => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 10,
						'left'   => 0,
					),
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Field Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field margin.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'padding'                => array(
			'setting' => array(
				'default' => array(
					'desktop' => array(
						'top'    => 6,
						'right'  => 12,
						'bottom' => 6,
						'left'   => 12,
					),
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Field Padding', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field padding.', 'everest-forms' ),
				'section'     => 'everest_forms_field_styles',
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
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_field_styles_controls' );
