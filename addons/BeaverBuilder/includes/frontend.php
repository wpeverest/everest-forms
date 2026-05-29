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
	echo wp_kses( $form_render, evf_get_allowed_html_tags( 'builder' ) );
} else {
	$image_path = evf()->plugin_url() . '/assets/images/everest-forms-logo.png';
	$class      = 'everest-forms-logo__beaver';


	if ( empty( $form_list ) ) {
		$page_id      = get_the_ID();
		$page_content = get_post_field( 'post_content', $page_id );
		$page_meta    = get_post_meta( $page_id, '_fl_builder_data', true );
		foreach ( $page_meta as $node ) {
			if ( isset( $node->settings ) && isset( $node->settings->form_selection ) ) {
				$form_selection = $node->settings->form_selection;
			}
		}
		if ( ! empty( $form_selection ) ) {
			$render = EVF_Shortcodes::form( $form_atts );
		} else {
			$render  = '<div class="everest-forms-beaver__container">';
			$render .= '<img src="' . esc_url( $image_path ) . '" class="' . esc_attr( $class ) . '" alt="Everest Forms Logo" />';
			$render .= '<p>' . esc_html__( 'Seems like you haven\'t created a form. Please create one to use it.', 'everest-forms' ) . '</p>';
			$render .= '</div>';
		}
	} else {
		$render  = '<div class="everest-forms-beaver__container">';
		$render .= '<img src="' . esc_url( $image_path ) . '" class="' . esc_attr( $class ) . '" alt="Everest Forms Logo" />';
		$render .= '<p>' . esc_html__( 'Please select a form', 'everest-forms' ) . '</p>';
		$render .= '</div>';
	}

	$render .= '</div>';
	echo wp_kses( $render, evf_get_allowed_html_tags( 'builder' ) );
}
