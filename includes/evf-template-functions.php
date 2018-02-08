<?php
/**
 * EverestForms Template
 *
 * Functions for the templating system.
 *
 * @author   WPEveresst
 * @category Core
 * @package  EverestForms/Functions
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle redirects before content is output - hooked into template_redirect so is_page works.
 */
function evf_template_redirect() {
	global $wp_query, $wp;


}

add_action( 'template_redirect', 'evf_template_redirect' );

/**
 * Output generator tag to aid debugging.
 *
 * @access public
 *
 * @param string $gen
 * @param string $type
 *
 * @return string
 */
function evf_generator_tag( $gen, $type ) {
	switch ( $type ) {
		case 'html':
			$gen .= "\n" . '<meta name="generator" content="EverestForms ' . esc_attr( EVF_VERSION ) . '">';
			break;
		case 'xhtml':
			$gen .= "\n" . '<meta name="generator" content="EverestForms ' . esc_attr( EVF_VERSION ) . '" />';
			break;
	}

	return $gen;
}

/**
 * Add body classes for EVF pages.
 *
 * @param  array $classes
 *
 * @return array
 */
function evf_body_class( $classes ) {
	$classes = (array) $classes;

	return array_unique( $classes );
}


/**
 * Outputs hidden form inputs for each query string variable.
 * @since      1.0.0
 *
 * @param array  $values      Name value pairs.
 * @param array  $exclude     Keys to exclude.
 * @param string $current_key Current key we are outputting.
 * @param bool   $return
 *
 * @return string
 */
function evf_query_string_form_fields( $values = null, $exclude = array(), $current_key = '', $return = false ) {
	if ( is_null( $values ) ) {
		$values = $_GET;
	}
	$html = '';

	foreach ( $values as $key => $value ) {
		if ( in_array( $key, $exclude, true ) ) {
			continue;
		}
		if ( $current_key ) {
			$key = $current_key . '[' . $key . ']';
		}
		if ( is_array( $value ) ) {
			$html .= evf_query_string_form_fields( $value, $exclude, $key, true );
		} else {
			$html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}
	}

	if ( $return ) {
		return $html;
	} else {
		echo $html;
	}
}
