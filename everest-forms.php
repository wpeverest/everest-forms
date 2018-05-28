<?php
/**
 * Plugin Name: Everest Forms
 * Plugin URI: https://wpeverest.com/wordpress-plugins/everest-forms/
 * Description: Drag and Drop form builder to easily create contact forms and more.
 * Version: 1.1.7
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
