<?php
/**
 * EverestForms Radio and Checkbox Style Config Functions
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
function evf_style_customizer_radio_checkbox_styles_sections( $sections ) {
	return array_merge(
		$sections,
		array(
			'everest_radio_checkbox_style' => array(
				'title'              => esc_html__( 'Radio/Checkbox Style', 'everest-forms' ),
				'description'        => esc_html__( 'This is radio and checkbox style description.', 'everest-forms' ),
				'priority'           => 10,
				'description_hidden' => true,
			),
		)
	);
}
add_filter( 'everest_forms_style_customizer_sections', 'evf_style_customizer_radio_checkbox_styles_sections' );

/**
 * Add everest forms style customizer controls.
 *
 * @param array $controls Array of controls.
 */
function evf_style_customizer_radio_checkbox_styles_controls( $controls ) {
	$controls['checkbox_radio_styles'] = array(
		'font_size'       => array(
			'setting' => array(
				'default'           => '14',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio font size (px).', 'everest-forms' ),
				'section'     => 'everest_radio_checkbox_style',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 12,
					'max'  => 50,
					'step' => 1,
				),
			),
		),
		'font_color'      => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Font Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio font color.', 'everest-forms' ),
				'section'     => 'everest_radio_checkbox_style',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'font_style'      => array(
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
				'section'     => 'everest_radio_checkbox_style',
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
		'alignment'       => array(
			'setting' => array(
				'default'           => 'left',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Alignment', 'everest-forms' ),
				'description' => esc_html__( 'This is a form field alignment only for default style.', 'everest-forms' ),
				'section'     => 'everest_radio_checkbox_style',
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
		'style_variation' => array(
			'setting' => array(
				'default' => 'default',
			),
			'control' => array(
				'label'       => esc_html__( 'Style Variation', 'everest-forms' ),
				'description' => esc_html__( 'This is a form radio/checkbox style variation.', 'everest-forms' ),
				'section'     => 'everest_radio_checkbox_style',
				'type'        => 'radio',
				'choices'     => array(
					'default' => esc_html__( 'Default', 'everest-forms' ),
					'outline' => esc_html__( 'Outline', 'everest-forms' ),
					'filled'  => esc_html__( 'Filled', 'everest-forms' ),
				),
			),
		),
		'size'            => array(
			'setting' => array(
				'default'           => '16',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'control' => array(
				'label'       => esc_html__( 'Radio/Checkbox Size', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio size (px).', 'everest-forms' ),
				'section'     => 'everest_radio_checkbox_style',
				'type'        => 'EVF_Customize_Slider_Control',
				'input_attrs' => array(
					'min'  => 16,
					'max'  => 50,
					'step' => 1,
				),
			),
		),
		'color'           => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Radio/Checkbox Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio color.', 'everest-forms' ),
				'section'     => 'everest_radio_checkbox_style',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'checked_color'   => array(
			'setting' => array(
				'default' => '#575757',
			),
			'control' => array(
				'label'       => esc_html__( 'Radio/Checkbox Checked Color', 'everest-forms' ),
				'description' => esc_html__( 'This is a form checkbox/radio checked color.', 'everest-forms' ),
				'section'     => 'everest_radio_checkbox_style',
				'type'        => 'EVF_Customize_Color_Control',
			),
		),
		'margin'          => array(
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
				'section'     => 'everest_radio_checkbox_style',
				'type'        => 'EVF_Customize_Dimension_Control',
				'custom_args' => array(
					'anchor'     => true,
					'responsive' => true,
					'input_type' => 'number',
				),
			),
		),
	);

	// Everest Forms 1.6.0, deprecates the inline styles control.
	if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '1.6.0', '<' ) ) {
		$deprecated_controls = array(
			'inline_style' => array(
				'setting' => array(
					'default' => 'default',
				),
				'control' => array(
					'label'       => esc_html__( 'Inline Style', 'everest-forms' ),
					'description' => esc_html__( 'This is a form radio/checkbox inline style.', 'everest-forms' ),
					'section'     => 'everest_radio_checkbox_style',
					'type'        => 'radio',
					'choices'     => array(
						'default'     => esc_html__( 'Default', 'everest-forms' ),
						'inline'      => esc_html__( 'Inline', 'everest-forms' ),
						'two_columns' => esc_html__( 'Two Columns', 'everest-forms' ),
					),
				),
			),
			'padding'      => array(
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
					'description' => esc_html__( 'This is a form radio/checkbox padding.', 'everest-forms' ),
					'section'     => 'everest_radio_checkbox_style',
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

		// Add new deprecated inline style controls to the existing ones.
		foreach ( array_keys( $controls['checkbox_radio_styles'] ) as $key => $control ) {
			if ( 'alignment' === $control ) {
				evf_array_splice_preserve_keys( $controls['checkbox_radio_styles'], $key + 1, 0, array( $deprecated_controls['inline_style'] ) );
			}
		}

		// Add new deprecated padding control to the existing ones.
		$controls['checkbox_radio_styles'] = array_merge( $controls['checkbox_radio_styles'], array( $deprecated_controls['padding'] ) );
	}

	return $controls;
}
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_radio_checkbox_styles_controls' );

if ( ! function_exists( 'evf_array_splice_preserve_keys' ) ) {

	/**
	 * An `array_splice` which does preverse the keys of the replacement array
	 *
	 * The argument list is identical to `array_splice`
	 *
	 * @link https://github.com/lode/gaps/blob/master/src/gaps.php
	 *
	 * @param  array $input       The input array.
	 * @param  int   $offset      The offeset to start.
	 * @param  int   $length      Optional length.
	 * @param  array $replacement The replacement array.
	 *
	 * @return array the array consisting of the extracted elements.
	 */
	function evf_array_splice_preserve_keys( &$input, $offset, $length = null, $replacement = array() ) {
		if ( empty( $replacement ) ) {
			return array_splice( $input, $offset, $length );
		}

		$part_before  = array_slice( $input, 0, $offset, true );
		$part_removed = array_slice( $input, $offset, $length, true );
		$part_after   = array_slice( $input, $offset + $length, null, true );

		$input = $part_before + $replacement + $part_after;

		return $part_removed;
	}
}
