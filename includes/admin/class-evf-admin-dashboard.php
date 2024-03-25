<?php
/**
 * Dashboard page.
 *
 * @package EverestForms/Admin/Dashboard
 * @since 2.0.8.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Dashboard Class.
 */
class EVF_Admin_Dashboard {

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function page_output() {
		if ( ! empty( $_GET['page'] ) && 'everest-forms-dashboard' === $_GET['page'] ) { //phpcs:ignore WordPress.Security.NonceVerification
			wp_enqueue_script( 'evf-dashboard-script', EVF()->plugin_url() . '/dist/dashboard.min.js', array('wp-element','react', 'react-dom' ), EVF()->version, true );
			ob_start();
			self::dashboard_page_body();
			self::dashboard_page_footer();
			exit;
		}
	}
	/**
	 * Dashboard Page body content.
	 *
	 * @since 1.0.0
	 */
	public static function dashboard_page_body() {
		?>
			<body class="everest-forms-dashboard notranslate" translate="no">
				<div id="everest-forms-dashboard"></div>
			</body>
		<?php
	}

	/**
	 * Dashboard Page footer content.
	 *
	 * @since 1.0.0
	 */
	public static function dashboard_page_footer() {
		if ( function_exists( 'wp_print_media_templates' ) ) {
			wp_print_media_templates();
		}
		wp_print_footer_scripts();
		wp_print_scripts( 'evf-dashboard-script' );
		?>
		</html>
		<?php
	}

}
