<?php
/**
 * Plugin Name: Everest Forms
 * Plugin URI: https://wpeverest.com/wordpress-plugins/everest-forms/
 * Description: Drag and Drop contact form builder to easily create simple to complex forms for any purpose. Lightweight, Beautiful design, responsive and more.
 * Version: 2.0.0.1
 * Author: WPEverest
 * Author URI: https://wpeverest.com
 * Text Domain: everest-forms
 * Domain Path: /languages/
 *
 * @package EverestForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define EVF_PLUGIN_FILE.
if ( ! defined( 'EVF_PLUGIN_FILE' ) ) {
	define( 'EVF_PLUGIN_FILE', __FILE__ );
}

// Include the main EverestForms class.
if ( ! class_exists( 'EverestForms' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-everest-forms.php';
}

/**
 * Main instance of EverestForms.
 *
 * Returns the main instance of EVF to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return EverestForms
 */
function evf() {
	return EverestForms::instance();
}

// Global for backwards compatibility.
$GLOBALS['everest-forms'] = evf();
