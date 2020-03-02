<?php
/**
 * EverestForms Conditional Functions
 *
 * Functions for determining the current query/page.
 *
 * @package EverestForms/Functions
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * Is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function is_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
	}
}

/**
 * Check if the string is JSON.
 *
 * @param  string $string String to check.
 * @return bool
 */
function evf_is_json( $string ) {
	return is_string( $string ) ? is_object( json_decode( $string ) ) : false;
}

/**
 * Checks if field exists within the form.
 *
 * @since 1.5.7
 * @param int    $form_id Form ID.
 * @param string $field   Field ID.
 * @return bool  True if the field exists in the form.
 */
function evf_is_field_exists( $form_id, $field ) {
	$form_obj  = evf()->form->get( $form_id );
	$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

	if ( ! empty( $form_data['form_fields'] ) ) {
		foreach ( $form_data['form_fields'] as $form_field ) {
			if ( $field === $form_field['type'] ) {
				return true;
			}
		}
	}

	return false;
}
