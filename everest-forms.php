<?php
/**
 * Plugin Name: Everest Forms
 * Plugin URI: https://everestforms.net/
 * Description: Best WordPress Form Plugin to Create Contact Forms, Surveys, Quizzes, Payment Forms, & Custom Forms Using Drag & Drop Form Builder.
 * Version: 3.0.9.5
 * Author: Everest Forms
 * Author URI: https://everestforms.net/
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

/**
 * Autoload the packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoloader ) && version_compare( PHP_VERSION, '7.1.3', '>=' ) ) {
	require $autoloader;
} else {
	if ( version_compare( PHP_VERSION, '7.1.3', '<=' ) ) {
		return;
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the Everest Forms plugin is incomplete. Please run %1$s within the %2$s directory.', 'everest-forms' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}

	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function () {
			?>
<div class="notice notice-error">
	<p>
			<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the Everest Forms plugin is incomplete. Please run %1$s within the %2$s directory.', 'everest-forms' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
			?>
	</p>
</div>
			<?php
		}
	);
	return;
}

// Include the main EverestForms class.
if ( ! class_exists( 'EverestForms' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-everest-forms.php'; // phpcs:ignore
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
