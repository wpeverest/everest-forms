<?php
/**
 * Frontend section for the Everest Forms.
 *
 * @package EverestForms\Addons\BeaverBuilder\Includes
 * @since 3.0.5
 */
use EverestForms\Addons\BeaverBuilder\Helper;

$form_list       = Helper::get_form_list();
$settings_attr   = get_object_vars( $settings );
$form_atts       = array();
$form_atts['id'] = isset( $settings_attr['form_selection'] ) ? $settings_attr['form_selection'] : '';

if ( ! empty( $settings_attr['form_selection'] ) && ! empty( $form_list ) ) {
	$form_render = EVF_Shortcodes::form( $form_atts );
	echo $form_render;
} else {
	$image_path = evf()->plugin_url() . '/assets/images/everest-forms-logo.png';
	$class      = 'everest-forms-logo__beaver';
	$render     = '<div class="everest-forms-beaver__container">';
	$render    .= '<img src="' . esc_url( $image_path ) . '" class="' . esc_attr( $class ) . '" alt="Everest Forms Logo" />';

	if ( empty( $form_list ) ) {
		$render .= '<p>' . esc_html__( 'Seems like you haven\'t created a form. Please create one to use it.', 'everest-forms' ) . '</p>';
	} else {
		$render .= '<p>' . esc_html__( 'Please select a form', 'everest-forms' ) . '</p>';
	}

	$render .= '</div>';
	echo $render;
}
