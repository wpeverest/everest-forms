<?php
/**
 * Addons Page
 *
 * @package EverestForms/Admin
 * @version 1.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addons Class.
 */
class EVF_Admin_Addons {

	/**
	 * Get sections for the addons screen.
	 *
	 * @return array of objects
	 */
	public static function get_sections() {
		$addon_sections = get_transient( 'evf_addons_sections' );

		if ( false === $addon_sections ) {
			$raw_sections = file_get_contents( plugin_dir_path( EVF_PLUGIN_FILE ) . 'assets\extensions-json\addon-sections.json' );

			if ( $raw_sections ) {
				$addon_sections = json_decode( $raw_sections );

				if ( $addon_sections ) {
					set_transient( 'evf_addons_sections', $addon_sections, WEEK_IN_SECONDS );
				}
			}
		}

		return apply_filters( 'everest_forms_extensions_sections', $addon_sections );
	}

	/**
	 * Get section content for the extensions screen.
	 *
	 * @return array
	 */
	public static function get_extension_data() {
		$extension_data = get_transient( 'evf_extensions_section' );

		if ( false === $extension_data ) {
			$raw_extensions = file_get_contents( plugin_dir_path( EVF_PLUGIN_FILE ) . 'assets\extensions-json\sections\all_extensions.json' );

			if ( $raw_extensions ) {
				$extension_data = json_decode( $raw_extensions );

				if ( ! empty( $extension_data->products ) ) {
					set_transient( 'evf_extensions_section', $extension_data, WEEK_IN_SECONDS );
				}
			}
		}

		return apply_filters( 'everest_forms_extensions_section_data', $extension_data->products );
	}

	/**
	 * Handles output of the addons page in admin.
	 */
	public static function output() {
		$addons          = array();
		$sections        = self::get_sections();
		$refresh_url     = add_query_arg(
			array(
				'page'             => 'evf-addons',
				'action'           => 'evf-addons-refresh',
				'evf-addons-nonce' => wp_create_nonce( 'refresh' ),
			),
			admin_url( 'admin.php' )
		);
		$license_plan    = evf_get_license_plan();
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_all'; // phpcs:ignore WordPress.Security.NonceVerification

		if ( '_featured' !== $current_section ) {
			$category = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
			$addons   = self::get_extension_data( $category );
		}

		/**
		 * Addon page view.
		 *
		 * @uses $addons
		 * @uses $sections
		 * @uses $refresh_url
		 * @uses $current_section
		 */
		include_once dirname( __FILE__ ) . '/views/html-admin-page-addons.php';
	}
}
