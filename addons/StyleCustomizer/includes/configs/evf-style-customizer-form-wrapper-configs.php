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

if ( defined( 'EFP_PLUGIN_FILE' ) ) {
	function evf_style_customizer_general_panels( $panels ) {
		return array_merge(
			$panels,
			array(
				'everest_forms_general_section' => array(
					'title'       => esc_html__( 'General', 'everest-forms' ),
					'description' => esc_html__( 'This is field Submission message description.', 'everest-forms' ),
				),
			)
		);
	}

	add_filter( 'everest_forms_style_customizer_panels', 'evf_style_customizer_general_panels' );
}

function evf_style_customizer_general_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_general_font'           => array(
				'title'              => esc_html__( 'Font', 'everest-forms' ),
				'description'        => esc_html__( 'This is font description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_general_section',
				'description_hidden' => true,
			),
			'everest_forms_general_form_container' => array(
				'title'              => esc_html__( 'Form Container', 'everest-forms' ),
				'description'        => esc_html__( 'This is font description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_general_section',
				'description_hidden' => true,
			),
			'everest_forms_general_field_styles'   => array(
				'title'              => esc_html__( 'Field Styles', 'everest-forms' ),
				'description'        => esc_html__( 'This is font description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_general_section',
				'description_hidden' => true,
			),
			'everest_forms_file_upload_styles'     => array(
				'title'              => esc_html__( 'File Upload Styles', 'everest-forms' ),
				'description'        => esc_html__( 'This is font description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_general_section',
				'description_hidden' => true,
			),
			'everest_forms_general_buttons'        => array(
				'title'              => esc_html__( 'Button', 'everest-forms' ),
				'description'        => esc_html__( 'This is font description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_general_section',
				'description_hidden' => true,
			),
			'everest_forms_general_radio_checkbox' => array(
				'title'              => esc_html__( 'Radio Checkbox', 'everest-forms' ),
				'description'        => esc_html__( 'This is font description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_general_section',
				'description_hidden' => true,
			),
			'everest_forms_general_typography'     => array(
				'title'              => esc_html__( 'Typography', 'everest-forms' ),
				'description'        => esc_html__( 'This is font description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_general_section',
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_general_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array                    $controls  Array of controls.
 * @param EVF_Style_Customizer_API $customize EVF_Style_Customizer_API instance.
 */
function evf_style_customizer_wrapper_controls( $controls, $customize ) {

	$controls['font'] = array(
		'show_theme_font' => array(
			'setting' => array(
				'default' => true,
			),
			'control' => array(
				'label'   => esc_html__( 'Use Theme Font', 'everest-forms' ),
				'section' => 'everest_forms_general_font',
				'type'    => 'EVF_Customize_Toggle_Control',
			),
		),
		'font_family'     => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Family', 'everest-forms' ),
				'description' => esc_html__( 'Select a desire Google font.', 'everest-forms' ),
				'section'     => 'everest_forms_general_font',
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
	);

	$controls['form_container'] = array(
		'width'                 => array(
			'setting' => array(
				'default'           => '100',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Width', 'everest-forms' ),
				'description' => esc_html__( 'Choose a form width (in %).', 'everest-forms' ),
				'section'     => 'everest_forms_general_form_container',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 50,
					'max'  => 100,
					'step' => 1,
				),
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
				'section'     => 'everest_forms_general_form_container',
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
				'description' => esc_html__( 'This is a form button border width.', 'everest-forms' ),
				'section'     => 'everest_forms_general_form_container',
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
		'border_color'          => array(
			'setting' => array(
				'default' => '#969696',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form border color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_form_container',
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
				'section'     => 'everest_forms_general_form_container',
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
		'background_image'      => array(
			'setting' => array(
				'default'           => get_theme_support( 'custom-background', 'default-image' ),
				'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
			),
			'control' => array(
				'label'   => esc_html__( 'Background Image', 'everest-forms' ),
				'section' => 'everest_forms_general_form_container',
				'type'    => 'EVF_Customize_Background_Image_Control',
			),
		),
		'background_preset'     => array(
			'setting' => array(
				'default'           => get_theme_support( 'custom-background', 'default-preset' ),
				'sanitize_callback' => array( $customize, '_sanitize_background_setting' ),
			),
			'control' => array(
				'label'   => esc_html__( 'Background Preset', 'everest-forms' ),
				'section' => 'everest_forms_general_form_container',
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
				'section'  => 'everest_forms_general_form_container',
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
				'section' => 'everest_forms_general_form_container',
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
				'section' => 'everest_forms_general_form_container',
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
				'section' => 'everest_forms_general_form_container',
				'type'    => 'checkbox',
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
				'section'     => 'everest_forms_general_form_container',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 0.0,
					'max'  => 1.0,
					'step' => 0.1,
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
				'section'     => 'everest_forms_general_form_container',
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
				'section'     => 'everest_forms_general_form_container',
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

	$controls['field_styles'] = array(
		'border_type'   => array(
			'setting' => array(
				'default'           => 'solid',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Border Type', 'everest-forms' ),
				'description' => esc_html__( 'This is form field border type', 'everest-forms' ),
				'section'     => 'everest_forms_general_field_styles',
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
		'border_width'  => array(
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
				'section'     => 'everest_forms_general_field_styles',
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
		'border_radius' => array(
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
				'section'     => 'everest_forms_general_field_styles',
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
	);

	$controls['file_upload_styles'] = array(
		'border_type'   => array(
			'setting' => array(
				'default'           => 'dashed',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Border Type', 'everest-forms' ),
				'description' => esc_html__( 'Set the border type for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
				'choices'     => array(
					'dashed' => esc_html__( 'Dashed', 'everest-forms' ),
					'dotted' => esc_html__( 'Dotted', 'everest-forms' ),
				),
			),
		),
		'border_width'  => array(
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
				'description' => esc_html__( 'Set the border width for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
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
		'border_radius' => array(
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
				'description' => esc_html__( 'Set the border radius for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
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
	);
	$controls['button']             = array(
		'border_type'   => array(
			'setting' => array(
				'default'           => 'solid',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Border Type', 'everest-forms' ),
				'description' => esc_html__( 'This is form field border type', 'everest-forms' ),
				'section'     => 'everest_forms_general_buttons',
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
		'border_width'  => array(
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
				'section'     => 'everest_forms_general_buttons',
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
		'border_radius' => array(
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
				'section'     => 'everest_forms_general_buttons',
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
	);

	$controls['typography'] = array(
		'field_labels'                        => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Field Labels', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'field_labels_font_size'              => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label font size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'field_labels_font_style'             => array(
			'setting' => array(
				'default' => array(
					'bold'      => true,
					'italic'    => false,
					'underline' => false,
					'uppercase' => false,
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Font Style', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label font style.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'field_labels_text_alignment'         => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Text Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label text alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => esc_html__( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => esc_html__( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => esc_html__( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'field_labels_line_height'            => array(
			'setting' => array(
				'default'           => '1.7',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Line Height', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label line height.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 3,
					'step' => .01,
				),
			),
		),
		'field_labels_margin'                 => array(
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
				'label'       => esc_html__( 'Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label margin.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'field_labels_padding'                => array(
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
				'label'       => esc_html__( 'Padding', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label padding.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
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
		'field_sublabels'                     => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Field sublabels', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'field_sublabels_font_size'           => array(
			'setting' => array(
				'default'           => '12',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field sublabel font size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'field_sublabels_font_style'          => array(
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
				'description' => esc_html__( 'This is a form field sublabel font style.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'field_sublabels_text_alignment'      => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Text Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field sublabel text alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => esc_html__( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => esc_html__( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => esc_html__( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'field_sublabels_line_height'         => array(
			'setting' => array(
				'default'           => '1.5',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Line Height', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field sublabel line height.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 3,
					'step' => .01,
				),
			),
		),
		'field_sublabels_margin'              => array(
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
				'label'       => esc_html__( 'Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field sublabel margin.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'field_sublabels_padding'             => array(
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
				'label'       => esc_html__( 'Padding', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field sublabel padding.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
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
		'field_styles'                        => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Field Styles', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'field_styles_font_size'              => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style font size in px.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'field_styles_font_color'             => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style font color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'field_styles_placeholder_font_color' => array(
			'setting' => array(
				'default' => '#c6ccd7',
			),
			'control' => array(
				'label'       => esc_html__( 'Placeholder Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style placeholder font color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'field_styles_font_style'             => array(
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
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'field_styles_alignment'              => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => esc_html__( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => esc_html__( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => esc_html__( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),

		'field_styles_border_color'           => array(
			'setting' => array(
				'default' => '#969696',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style border color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'field_styles_border_focus_color'     => array(
			'setting' => array(
				'default' => '#7ca8eb',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Focus Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field style border color on focus.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),

		'field_styles_margin'                 => array(
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
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'field_styles_padding'                => array(
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
				'section'     => 'everest_forms_general_typography',
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
		'field_description'                   => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Field Description', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'field_description_font_size'         => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field description font size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 10,
					'max'  => 50,
					'step' => 1,
				),
			),
		),
		'field_description_font_color'        => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field description font color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'field_description_font_style'        => array(
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
				'description' => esc_html__( 'This is a form field description font style.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'field_description_text_alignment'    => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Text Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field description text alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => esc_html__( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => esc_html__( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => esc_html__( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'field_description_line_height'       => array(
			'setting' => array(
				'default'           => '1.7',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Line Height', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field description line height.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 3,
					'step' => .01,
				),
			),
		),
		'field_description_margin'            => array(
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
				'label'       => esc_html__( 'Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field description margin.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'field_description_padding'           => array(
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
				'label'       => esc_html__( 'Padding', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field description padding.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
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
		'section_title'                       => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Section Title', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'section_title_font_size'             => array(
			'setting' => array(
				'default'           => '16',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a section title font size.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'section_title_font_color'            => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a section title font color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'section_title_font_style'            => array(
			'setting' => array(
				'default' => array(
					'bold'      => true,
					'italic'    => false,
					'underline' => false,
					'uppercase' => false,
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Font Style', 'everest-forms' ),
				'description' => esc_html__( 'This is a form section title font style.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'section_title_text_alignment'        => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Text Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a section title text alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => esc_html__( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => esc_html__( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => esc_html__( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'section_title_line_height'           => array(
			'setting' => array(
				'default'           => '1.5',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Line Height', 'everest-forms' ),
				'description' => esc_html__( 'This is a section title line height.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 3,
					'step' => .01,
				),
			),
		),
		'section_title_margin'                => array(
			'setting' => array(
				'default' => array(
					'desktop' => array(
						'top'    => 25,
						'right'  => 0,
						'bottom' => 25,
						'left'   => 0,
					),
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Form Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a section title margin.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'section_title_padding'               => array(
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
				'description' => esc_html__( 'This is a section title padding.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
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
		'file_upload'                         => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'File Upload', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'file_upload_font_size'               => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'Set the font-size(px) for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'file_upload_font_color'              => array(
			'setting' => array(
				'default' => '#494d50',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'Select the font color for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'file_upload_background_color'        => array(
			'setting' => array(
				'default' => 'rgba(255,255,255,0.99)',
			),
			'control' => array(
				'label'       => esc_html__( 'File Upload Background', 'everest-forms' ),
				'description' => esc_html__( 'Choose background color for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'file_upload_icon_background_color'   => array(
			'setting' => array(
				'default' => 'rgba(255,255,255,0.99)',
			),
			'control' => array(
				'label'       => esc_html__( 'Icon Background', 'everest-forms' ),
				'description' => esc_html__( 'Choose background color for icon inside the file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'file_upload_icon_color'              => array(
			'setting' => array(
				'default' => '#494d50',
			),
			'control' => array(
				'label'       => esc_html__( 'Icon Color', 'everest-forms' ),
				'description' => esc_html__( 'Fill color for icon inside file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'file_upload_border_color'            => array(
			'setting' => array(
				'default' => '#8e98a2',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'Choose the border color for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'file_upload_margin'                  => array(
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
				'label'       => esc_html__( 'File Upload Margin', 'everest-forms' ),
				'description' => esc_html__( 'Set the margins for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'file_upload_padding'                 => array(
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
				'label'       => esc_html__( 'File Uploads Padding', 'everest-forms' ),
				'description' => esc_html__( 'Set the paddings for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
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
		'checkbox_radio'                      => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Radio/Checkbox Styles', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'checkbox_radio_font_size'            => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio font size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 12,
					'max'  => 50,
					'step' => 1,
				),
			),
		),
		'checkbox_radio_font_color'           => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio font color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'checkbox_radio_font_style'           => array(
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
				'description' => esc_html__( 'This is a form checkbox/radio font style.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'checkbox_radio_alignment'            => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field alignment only for default style.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => esc_html__( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => esc_html__( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => esc_html__( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),
		'checkbox_radio_style_variation'            => array(
			'setting' => array(
				'default' => 'default',
			),
			'control' => array(
				'label'       => esc_html__( 'Style Variation', 'everest-forms' ),
				'description' => esc_html__( 'This is a form radio/checkbox style variation.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'radio',
				'choices'     => array(
					'default' => esc_html__( 'Default', 'everest-forms' ),
					'outline' => esc_html__( 'Outline', 'everest-forms' ),
					'filled'  => esc_html__( 'Filled', 'everest-forms' ),
				),
			),
		),
		'checkbox_radio_size'                 => array(
			'setting' => array(
				'default'           => '16',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Radio/Checkbox Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 16,
					'max'  => 50,
					'step' => 1,
				),
			),
		),
		'checkbox_radio_color'                => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Radio/Checkbox Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'checkbox_radio_checked_color'        => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Radio/Checkbox Checked Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio checked color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'checkbox_radio_margin'               => array(
			'setting' => array(
				'default' => array(
					'desktop' => array(
						'top'    => 0,
						'right'  => 20,
						'bottom' => 5,
						'left'   => 0,
					),
				),
			),
			'control' => array(
				'label'       => esc_html__( 'Form Margin', 'everest-forms' ),
				'description' => esc_html__( 'This is a form radio/checkbox margin.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'button'                              => array(
			'setting' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Button', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Toggle_Control',
				'custom_args' => array(
					'class' => 'accordion-toggle',
				),
			),
		),
		'button_font_size'                    => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button font size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'button_font_style'                   => array(
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
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Checkbox_Control',
				'choices'     => array(
					'bold'      => array(
						'name'  => esc_html__( 'Bold', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/bold.svg', EVF_PLUGIN_FILE ),
					),
					'italic'    => array(
						'name'  => esc_html__( 'Italic', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/italic.svg', EVF_PLUGIN_FILE ),
					),
					'underline' => array(
						'name'  => esc_html__( 'Underline', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/underline.svg', EVF_PLUGIN_FILE ),
					),
					'uppercase' => array(
						'name'  => esc_html__( 'Uppercase', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/uppercase.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),

		'button_hover_font_color'             => array(
			'setting' => array(
				'default'           => '#23282d',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Hover Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button hover font color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'button_hover_background_color'       => array(
			'setting' => array(
				'default'           => '#eeeeee',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Button Hover Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button hover color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'button_alignment'                    => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => __( 'Button Alignment', 'everest-forms' ),
				'description' => __( 'This is a form button alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Image_Radio_Control',
				'choices'     => array(
					'left'   => array(
						'name'  => __( 'Left', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-left.svg', EVF_PLUGIN_FILE ),
					),
					'center' => array(
						'name'  => __( 'Center', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-center.svg', EVF_PLUGIN_FILE ),
					),
					'right'  => array(
						'name'  => __( 'Right', 'everest-forms' ),
						'image' => plugins_url( 'addons/StyleCustomizer/assets/images/align-right.svg', EVF_PLUGIN_FILE ),
					),
				),
			),
		),

		'button_border_color'                 => array(
			'setting' => array(
				'default'           => '#cccccc',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button style border color.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'button_border_hover_color'           => array(
			'setting' => array(
				'default'           => '#cccccc',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Hover Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button style border color in hover.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'button_line_height'                  => array(
			'setting' => array(
				'default'           => '1.5',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Line Height', 'everest-forms' ),
				'description' => esc_html__( 'This is a form button line height.', 'everest-forms' ),
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 3,
					'step' => .01,
				),
			),
		),
		'button_margin'                       => array(
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
				'section'     => 'everest_forms_general_typography',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'button_padding'                      => array(
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
				'section'     => 'everest_forms_general_typography',
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
