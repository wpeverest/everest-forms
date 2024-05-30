<?php
/**
 * EverestForms Button Config Functions
 *
 * @package EverestForms_Style_Customizer/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add everest forms button customizer sections.
 *
 * @param array $sections Array of sections.
 */
function evf_style_customizer_button_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_buttons' => array(
				'title'              => esc_html__( 'Button Styles', 'everest-forms' ),
				'description'        => esc_html__( 'This is field labels description.', 'everest-forms' ),
				'priority'           => 10,
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_button_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array                    $controls  Array of controls.
 * @param EVF_Style_Customizer_API $customize EVF_Style_Customizer_API instance.
 */
function evf_style_customizer_button_controls( $controls, $customize ) {
	$controls['button'] = array(
		'font_size'              => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button font size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
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
				'description' => esc_html__( 'This is a form button font style.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
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
		'font_color'             => array(
			'setting' => array(
				'default'           => '#555555',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button font color.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'hover_font_color'       => array(
			'setting' => array(
				'default'           => '#23282d',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Hover Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button hover font color.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'background_color'       => array(
			'setting' => array(
				'default'           => '#f7f7f7',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Button Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button color.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'hover_background_color' => array(
			'setting' => array(
				'default'           => '#eeeeee',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Button Hover Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button hover color.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'alignment'              => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => __( 'Button Alignment', 'everest-forms' ),
				'description' => __( 'This is a form button alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => __( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => __( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'includes/addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => __( 'Right', 'everest-forms' ),
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
				'description' => esc_html__( 'This is form button border type', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
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
				'description' => esc_html__( 'This is a form button border width.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
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
				'default'           => '#cccccc',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button style border color.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'border_hover_color'     => array(
			'setting' => array(
				'default'           => '#cccccc',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Hover Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button style border color in hover.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Color_Control',
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
				'description' => esc_html__( 'This is a button border radius.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
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
		'line_height'            => array(
			'setting' => array(
				'default'           => '1.5',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Line Height', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button line height.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 3,
					'step' => .01,
				),
			),
		),
		'margin'                 => array(
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
				'label'       => esc_html__( 'Button Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button margin.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
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
						'top'    => 10,
						'right'  => 15,
						'bottom' => 10,
						'left'   => 15,
					),
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Button Padding', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button padding.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
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

	// If multi-part is enabled then we won't need button alignment to remove confusion.
	if ( isset( $customize->form_data['settings']['enable_multi_part'] ) && ! evf_string_to_bool( $customize->form_data['settings']['enable_multi_part'] ) ) {
		$advanced_controls['alignment'] = array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Button Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_buttons',
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
		);

		// Add new button alignment controls to the existing ones.
		foreach ( array_keys( $controls['button'] ) as $key => $control ) {
			if ( 'hover_background_color' === $control ) {
				evf_array_splice_preserve_keys( $controls['button'], $key + 1, 0, $advanced_controls );
				break;
			}
		}
	}

	return $controls;
}
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_button_controls', 10, 2 );
