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
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_everest_forms' ) ) {
			return;
		}
		if ( ! empty( $_GET['page'] ) && 'evf-dashboard' === $_GET['page'] ) { //phpcs:ignore WordPress.Security.NonceVerification
			wp_enqueue_script( 'evf-dashboard-script', EVF()->plugin_url() . '/dist/dashboard.min.js', array( 'wp-element', 'react', 'react-dom' ), EVF()->version, true );
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! function_exists( 'wp_get_themes' ) ) {
				require_once ABSPATH . 'wp-admin/includes/theme.php';
			}
			$installed_plugin_slugs = array_keys( get_plugins() );
			$allowed_plugin_slugs   = array(
				'user-registration/user-registration.php',
				'blockart-blocks/blockart.php',
				'learning-management-system/lms.php',
				'magazine-blocks/magazine-blocks.php',
			);

			$installed_theme_slugs = array_keys( wp_get_themes() );
			$current_theme         = get_stylesheet();

			wp_localize_script(
				'evf-dashboard-script',
				'_EVF_DASHBOARD_',
				array(
					'adminURL'             => esc_url( admin_url() ),
					'settingsURL'          => esc_url( admin_url( '/admin.php?page=evf-settings' ) ),
					'siteURL'              => esc_url( home_url( '/' ) ),
					'liveDemoURL'          => esc_url_raw( 'https://everestforms.demoswp.net/' ),
					'assetsURL'            => esc_url( EVF()->plugin_url() . '/assets/' ),
					'evfRestApiNonce'      => wp_create_nonce( 'wp_rest' ),
					'newFormURL'           => esc_url( admin_url( '/admin.php?page=evf-builder&create-form=1' ) ),
					'allFormsURL'          => esc_url( admin_url( '/admin.php?page=evf-builder' ) ),
					'restURL'              => rest_url(),
					'version'              => EVF()->version,
					'isPro'                => is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' ),
					'licensePlan'          => evf_get_license_plan(),
					'licenseActivationURL' => esc_url_raw( admin_url( 'plugins.php' ) ),
					'utmCampaign'          => EVF()->utm_campaign,
					'upgradeURL'           => esc_url_raw( 'https://everestforms.net/pricing/?utm_campaign=' . EVF()->utm_campaign ),
					'plugins'              => array_reduce(
						$allowed_plugin_slugs,
						function ( $acc, $curr ) use ( $installed_plugin_slugs ) {
							if ( in_array( $curr, $installed_plugin_slugs, true ) ) {

								if ( is_plugin_active( $curr ) ) {
									$acc[ $curr ] = 'active';
								} else {
									$acc[ $curr ] = 'inactive';
								}
							} else {
								$acc[ $curr ] = 'not-installed';
							}
							return $acc;
						},
						array()
					),
					'themes'               => array(
						'zakra'    => strpos( $current_theme, 'zakra' ) !== false ? 'active' : (
							in_array( 'zakra', $installed_theme_slugs, true ) ? 'inactive' : 'not-installed'
						),
						'colormag' => strpos( $current_theme, 'colormag' ) !== false || strpos( $current_theme, 'colormag-pro' ) !== false ? 'active' : (
							in_array( 'colormag', $installed_theme_slugs, true ) || in_array( 'colormag-pro', $installed_theme_slugs, true ) ? 'inactive' : 'not-installed'
						),
					),
				)
			);

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
