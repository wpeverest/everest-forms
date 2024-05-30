<?php
/**
 * EverestForms File Uploads Style Config Functions
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
function evf_style_customizer_file_upload_styles_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_file_upload_styles' => array(
				'title'              => esc_html__( 'File Upload Styles', 'everest-forms' ),
				'description'        => esc_html__( 'This is file uploads styles description.', 'everest-forms' ),
				'priority'           => 10,
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_file_upload_styles_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array $controls Array of controls.
 */
function evf_style_customizer_file_upload_styles_controls( $controls ) {
	$controls['file_upload_styles'] = array(
		'font_size'             => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'Set the font-size(px) for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'font_color'            => array(
			'setting' => array(
				'default' => '#494d50',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'Select the font color for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'background_color'      => array(
			'setting' => array(
				'default' => 'rgba(255,255,255,0.99)',
			),
			'control' => array(
				'label'       => esc_html__( 'File Upload Background', 'everest-forms' ),
				'description' => esc_html__( 'Choose background color for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),
		'icon_background_color' => array(
			'setting' => array(
				'default' => 'rgba(255,255,255,0.99)',
			),
			'control' => array(
				'label'       => esc_html__( 'Icon Background', 'everest-forms' ),
				'description' => esc_html__( 'Choose background color for icon inside the file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
				'type'        => 'EVF_Customize_Color_Control',
				'custom_args' => array(
					'alpha' => true,
				),
			),
		),

		'icon_color'            => array(
			'setting' => array(
				'default' => '#494d50',
			),
			'control' => array(
				'label'       => esc_html__( 'Icon Color', 'everest-forms' ),
				'description' => esc_html__( 'Fill color for icon inside file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'border_type'           => array(
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
		'border_color'          => array(
			'setting' => array(
				'default' => '#8e98a2',
			),
			'control' => array(
				'label'       => esc_html__( 'Border Color', 'everest-forms' ),
				'description' => esc_html__( 'Choose the border color for file upload fields.', 'everest-forms' ),
				'section'     => 'everest_forms_file_upload_styles',
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
		'margin'                => array(
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
				'section'     => 'everest_forms_file_upload_styles',
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
				'section'     => 'everest_forms_file_upload_styles',
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
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_file_upload_styles_controls' );
