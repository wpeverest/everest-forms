<?php
/**
 * Welcome Class
 *
 * Takes new users to Welcome Page.
 *
 * @package     EverestForms/Admin
 * @version     5.2.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Welcome.
 */
class EVF_Admin_Welcome {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( apply_filters( 'everest_forms_show_welcome_page', true ) && current_user_can( 'manage_everest_forms' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'welcome_page' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'evf-welcome', '' );
	}

	/**
	 * Show the welcome page.
	 */
	public function welcome_page() {
		echo 'Hello';
	}

}

new EVF_Admin_Welcome();
