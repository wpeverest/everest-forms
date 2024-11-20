<?php
/**
 * EverestForms Info and Message Config Functions
 *
 * @package EverestForms_Style_Customizer/Functions
 * @version 1.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add everest forms Info and Message customizer sections.
 *
 * @param array $panels Array of panels.
 */
if ( defined( 'EFP_PLUGIN_FILE' ) ) {
	function evf_style_customizer_submission_message_panels( $panels ) {
		return array_merge(
			$panels,
			array(
				'everest_forms_submission_message' => array(
					'title'       => esc_html__( 'Form Messages', 'everest-forms' ),
					'description' => esc_html__( 'This is field Submission message description.', 'everest-forms' ),
				),
			)
		);
	}

	add_filter( 'everest_forms_style_customizer_panels', 'evf_style_customizer_submission_message_panels' );
}

/**
 * Add everest forms Info and Message customizer sections.
 *
 * @param array $sections Array of sections.
 */
function evf_style_customizer_submission_message_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_forms_submission_success_message'    => array(
				'title'              => esc_html__( 'Success Message', 'everest-forms' ),
				'description'        => esc_html__( 'This is field Submission message description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_submission_message',
				'description_hidden' => true,
			),
			'everest_forms_submission_error_message'      => array(
				'title'              => esc_html__( 'Error Message', 'everest-forms' ),
				'description'        => esc_html__( 'This is field Submission message description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_submission_message',
				'description_hidden' => true,
			),
			'everest_forms_submission_info_message'       => array(
				'title'              => esc_html__( 'Info Message', 'everest-forms' ),
				'description'        => esc_html__( 'This is field Submission message description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_submission_message',
				'description_hidden' => true,
			),
			'everest_forms_submission_warning_message'    => array(
				'title'              => esc_html__( 'Warning Message', 'everest-forms' ),
				'description'        => esc_html__( 'This is field Submission message description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_submission_message',
				'description_hidden' => true,
			),
			'everest_forms_submission_validation_message' => array(
				'title'              => esc_html__( 'Validation Message', 'everest-forms' ),
				'description'        => esc_html__( 'This is field validation message description.', 'everest-forms' ),
				'priority'           => 10,
				'panel'              => 'everest_forms_submission_message',
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_submission_message_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array $controls Array of controls.
 */
function evf_style_customizer_submission_message_controls( $controls ) {
	$section_types = array( 'success_message', 'error_message', 'validation_message' );
	foreach ( $section_types as $section_name ) {
		$controls[ $section_name ] = array(
			'show_submission_message' => array(
				'setting' => array(
					'default' => false,
				),
				'control' => array(
					'label'   => esc_html__( 'Typography', 'everest-forms' ),
					'section' => 'everest_forms_submission_' . $section_name,
					'type'    => 'EVF_Customize_Toggle_Control',
				),
			),

			'font_size'               => array(
				'setting' => array(
					'default'           => '14',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'control' => array(
					'label'       => esc_html__( 'Font Size', 'everest-forms' ),
					'section'     => 'everest_forms_submission_' . $section_name,
					'type'        => 'EVF_Customize_Slider_Control',
					'input_attrs' => array(
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					),
				),
			),
			'font_style'              => array(
				'setting' => array(
					'default' => array(
						'bold'      => false,
						'italic'    => false,
						'underline' => false,
						'uppercase' => false,
					),
				),
				'control' => array(
					'label'   => esc_html__( 'Font Style', 'everest-forms' ),
					'section' => 'everest_forms_submission_' . $section_name,
					'type'    => 'EVF_Customize_Image_Checkbox_Control',
					'choices' => array(
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
			'text_alignment'          => array(
				'setting' => array(
					'default'           => 'left',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'control' => array(
					'label'   => esc_html__( 'Text Alignment', 'everest-forms' ),
					'section' => 'everest_forms_submission_' . $section_name,
					'type'    => 'EVF_Customize_Image_Radio_Control',
					'choices' => array(
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
			'font_color'              => array(
				'setting' => array(
					'default' => '#fff',
				),
				'control' => array(
					'label'   => esc_html__( 'Font Color', 'everest-forms' ),
					'section' => 'everest_forms_submission_' . $section_name,
					'type'    => 'EVF_Customize_Color_Control',
				),
			),
			'background_color'        => array(
				'setting' => array(
					'default'           => '#fff',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'control' => array(
					'label'   => esc_html__( 'Background Color', 'everest-forms' ),
					'section' => 'everest_forms_submission_' . $section_name,
					'type'    => 'EVF_Customize_Color_Control',
				),
			),
			'border_type'             => array(
				'setting' => array(
					'default'           => 'none',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'control' => array(
					'type'    => 'select',
					'label'   => esc_html__( 'Border Type', 'everest-forms' ),
					'section' => 'everest_forms_submission_' . $section_name,
					'choices' => array(
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
			'border_width'            => array(
				'setting' => array(
					'default' => array(
						'top'    => 0,
						'right'  => 0,
						'bottom' => 0,
						'left'   => 0,
					),
				),
				'control' => array(
					'label'       => esc_html__( 'Border Width', 'everest-forms' ),
					'section'     => 'everest_forms_submission_' . $section_name,
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
			'border_color'            => array(
				'setting' => array(
					'default'           => '#cccccc',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'control' => array(
					'label'   => esc_html__( 'Border Color', 'everest-forms' ),
					'section' => 'everest_forms_submission_' . $section_name,
					'type'    => 'EVF_Customize_Color_Control',
				),
			),
			'border_radius'           => array(
				'setting' => array(
					'default' => array(
						'top'    => 5,
						'right'  => 5,
						'bottom' => 5,
						'left'   => 5,
						'unit'   => 'px',
					),
				),
				'control' => array(
					'label'       => esc_html__( 'Border Radius', 'everest-forms' ),
					'section'     => 'everest_forms_submission_' . $section_name,
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
	}

	// Set default font and background colors.
	$controls['validation_message']['font_color']['setting']['default']    = '#fa5252';
	$controls['success_message']['background_color']['setting']['default'] = '#5cb85c';
	$controls['error_message']['background_color']['setting']['default']   = '#d9534f';

	return $controls;
}
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_submission_message_controls' );
