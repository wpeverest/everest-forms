<?php
/**
 * EverestForms Color Palette Config Functions.
 *
 * @package EverestForms_Style_Customizer/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add Everest Forms button customizer sections
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
 * Add Everest Forms style customizer controls.
 *
 * @param array                    $controls  Array of controls.
 * @param EVF_Style_Customizer_API $customize EVF_Style_Customizer_API instance.
 */
function evf_style_customizer_color_palette_controls( $controls, $customize ) {
	$color_palettes = array(
		array(
			'label'     => esc_html__( 'Classic', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#ffffff',
				'field_background'  => '#f6f6f6',
				'field_label'       => '#81d2b',
				'field_sublabel'    => '#0f3a57',
				'button_text'       => '#ffffff',
				'button_background' => '#3951a5',
			),
			'is_pro'    => false,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Monochrome', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#ffffff',
				'field_background'  => '#f7f7f7',
				'field_label'       => '#262626',
				'field_sublabel'    => '#666666',
				'button_text'       => '#ffffff',
				'button_background' => '#1a1a1a',
			),
			'is_pro'    => false,
			'is_custom' => false,
		),
	);

	$pro_palette = array(
		array(
			'label'     => esc_html__( 'Autumn Blaze', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#ffafa',
				'field_background'  => '#fff5f5',
				'field_label'       => '#330300',
				'field_sublabel'    => '#4d0500',
				'button_text'       => '#ffffff',
				'button_background' => '#ff5d52',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Sunset Glow', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#fffdfa',
				'field_background'  => '#fff9f0',
				'field_label'       => '#664000',
				'field_sublabel'    => '#805100',
				'button_text'       => '#ffffff',
				'button_background' => '#ffa305',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Majestic', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#fcfbfe',
				'field_background'  => '#f7f4fb',
				'field_label'       => '#3a225d',
				'field_sublabel'    => '#5d3795',
				'button_text'       => '#ffffff',
				'button_background' => '#7545bb',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Fresh Greenery', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#f9fdf6',
				'field_background'  => '#e9f6ea',
				'field_label'       => '#334745',
				'field_sublabel'    => '#557773',
				'button_text'       => '#ffffff',
				'button_background' => '#405956',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Cloudy Sky', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#f2f3f8',
				'field_background'  => '#e4e7f1',
				'field_label'       => '#252b41',
				'field_sublabel'    => '#2e3651',
				'button_text'       => '#ffffff',
				'button_background' => '#445079',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Earthy Warm', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#f7f6f0',
				'field_background'  => '#f1efe4',
				'field_label'       => '#474648',
				'field_sublabel'    => '#616062',
				'button_text'       => '#ffffff',
				'button_background' => '#463700',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),

		array(
			'label'     => esc_html__( 'Blushing Blossom', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#fdf7fa',
				'field_background'  => '#fbeff5',
				'field_label'       => '#532f42',
				'field_sublabel'    => '#824a68',
				'button_text'       => '#ffffff',
				'button_background' => '#46102c',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Thunder', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#ededed',
				'field_background'  => '#f7f7f7',
				'field_label'       => '#333333',
				'field_sublabel'    => '#595959',
				'button_text'       => '#ffffff',
				'button_background' => '#1a1a1a',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
		array(
			'label'     => esc_html__( 'Midnight Charm', 'everest-forms' ),
			'colors'    => array(
				'form_background'   => '#363636',
				'field_background'  => '#3d3d3d',
				'field_label'       => '#ffffff',
				'field_sublabel'    => '#f2f2f2',
				'button_text'       => '#1a1a1a',
				'button_background' => '#ffffff',
			),
			'is_pro'    => true,
			'is_custom' => false,
		),
	);
	if ( defined( 'EFP_PLUGIN_FILE' ) ) {
		$custom_palette = get_option( 'everest_forms_custom_color_palettes', array() );

		$custom_palette = array_filter(
			$custom_palette,
			function( $palette ) {
				return ! empty( $palette['colors'] );
			}
		);
	}

	$custom_palette   = isset( $custom_palette ) ? $custom_palette : array();
	$color_palettes   = array_merge( $custom_palette, $color_palettes, $pro_palette );
	$has_custom_field = false;

	foreach ( $color_palettes as $palette ) {
		if ( $palette['is_custom'] ) {
			$has_custom_field = true;
			break;
		}
	}

	$selected_color_key = '';
	$styles             = get_option( 'everest_forms_styles', array() );

	if ( ! empty( $styles ) ) {
		foreach ( $styles as $dynamic_key => $value ) {
			if ( isset( $value['color_palette'] ) ) {
				$selected_color_key = key( $value['color_palette'] );
				break;
			}
		}
	}

	if ( isset( $_COOKIE['color_palette'] ) ) {
		$selected_color_key = $_COOKIE['color_palette'];
	}

	foreach ( $color_palettes as $index => $palette ) {
		$colors_with_values = array();
		foreach ( $palette['colors'] as $color_name => $color_value ) {
			$colors_with_values[] = array(
				'name'       => $color_name,
				'color'      => $color_value,
				'color_name' => $color_name,
			);
		}

		$class       = $palette['is_pro'] && ! defined( 'EFP_PLUGIN_FILE' ) ? 'evf-pro-palette' : '';
		$input_attrs = array(
			'class' => $class,
		);

		if ( $palette['is_custom'] === true ) {
			$input_attrs['data-custom'] = 'evf-custom-color-palette';
		} else {
			if ( count( get_option( 'everest_forms_custom_color_palettes', array() ) ) > 0 ) {
				$input_attrs['data-custom'] = '';
			}
		}

		if ( 'color_' . $index === $selected_color_key ) {
			$input_attrs['checked'] = 'checked';
			$input_attrs['class']  .= ' evf-active-color-palette';
		}

		$controls['color_palette'][ 'color_' . $index ] = array(
			'setting' => array(
				'default'           => $palette['colors'],
				'sanitize_callback' => '',
				'transport'         => 'postMessage',
			),
			'control' => array(
				'label'       => $palette['label'],
				'section'     => 'everest_forms_color_palette',
				'type'        => 'EVF_Customize_Color_Palette_Control',
				'choices'     => $colors_with_values,
				'input_attrs' => $input_attrs,
			),
		);
	}
	return $controls;
}
add_filter( 'everest_forms_style_customizer_controls', 'evf_style_customizer_color_palette_controls', 10, 2 );
