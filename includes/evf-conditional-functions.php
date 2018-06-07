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
