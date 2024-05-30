<?php
/**
 * EverestForms Field Label Config Functions
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
function evf_style_customizer_field_label_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_field_labels' => array(
				'title'              => esc_html__( 'Field Labels', 'everest-forms' ),
				'description'        => esc_html__( 'This is field labels description.', 'everest-forms' ),
				'priority'           => 10,
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_field_label_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array $controls Array of controls.
 */
function evf_style_customizer_field_label_controls( $controls ) {
	$controls['field_label'] = array(
		'font_size'      => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label font size (px).', 'everest-forms' ),
				'section'     => 'everest_forms_field_labels',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),
		),
		'font_color'     => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label font color.', 'everest-forms' ),
				'section'     => 'everest_forms_field_labels',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'font_style'     => array(
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
				'section'     => 'everest_forms_field_labels',
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
		'text_alignment' => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Text Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label text alignment.', 'everest-forms' ),
				'section'     => 'everest_forms_field_labels',
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
		'line_height'    => array(
			'setting' => array(
				'default'           => '1.7',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Line Height', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field label line height.', 'everest-forms' ),
				'section'     => 'everest_forms_field_labels',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => 3,
					'step' => .01,
				),
			),
		),
		'margin'         => array(
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
				'section'     => 'everest_forms_field_labels',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
		'padding'        => array(
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
				'section'     => 'everest_forms_field_labels',
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
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_field_label_controls' );
