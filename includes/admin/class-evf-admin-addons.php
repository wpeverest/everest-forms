<?php
/**
 * Addons Page
 *
 * @package EverestForms/Admin
 * @version 1.1.1
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
			$raw_sections = wp_safe_remote_get( 'https://raw.githubusercontent.com/wpeverest/extensions-json/master/everest-forms/addon-sections.json' );
			if ( ! is_wp_error( $raw_sections ) ) {
				$addon_sections = json_decode( wp_remote_retrieve_body( $raw_sections ) );

				if ( $addon_sections ) {
					set_transient( 'evf_addons_sections', $addon_sections, WEEK_IN_SECONDS );
				}
			}
		}

		return apply_filters( 'everest_forms_extensions_sections', $addon_sections );
	}

	/**
	 * Get section for the extensions screen.
	 *
	 * @param  string $section_id
	 * @return object|bool
	 */
	public static function get_section( $section_id ) {
		$sections = self::get_sections();
		if ( isset( $sections[ $section_id ] ) ) {
			return $sections[ $section_id ];
		}
		return false;
	}

	/**
	 * Get section content for the extensions screen.
	 *
	 * @param  string $section_id
	 * @return array
	 */
	public static function get_section_data( $section_id ) {
		$section      = self::get_section( $section_id );
		$section_data = '';

		if ( ! empty( $section->endpoint ) ) {
			if ( false === ( $section_data = get_transient( 'evf_extensions_section_' . $section_id ) ) ) {
				$raw_section = wp_safe_remote_get( esc_url_raw( $section->endpoint ), array( 'user-agent' => 'RestaurantPress Extensions Page' ) );

				if ( ! is_wp_error( $raw_section ) ) {
					$section_data = json_decode( wp_remote_retrieve_body( $raw_section ) );

					if ( ! empty( $section_data->products ) ) {
						set_transient( 'evf_extensions_section_' . $section_id, $section_data, WEEK_IN_SECONDS );
					}
				}
			}
		}

		return apply_filters( 'everest_forms_extensions_section_data', $section_data->products, $section_id );
	}

	/**
	 * Handles output of the addons page in admin.
	 */
	public static function output() {
		$addons          = array();
		$sections        = self::get_sections();
		$refresh_url     = add_query_arg(
			array(
				'page'              => 'evf-addons',
				'evf-addons-refresh' => 1,
				'evf-addons-nonce'   => wp_create_nonce( 'refresh' ),
			), admin_url( 'admin.php' )
		);
		$section_keys    = wp_list_pluck( $sections, 'slug' );
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : current( $section_keys );

		/**
		 * Addon page view.
		 *
		 * @uses $addons
		 * @uses $sections
		 * @uses $refresh_url
		 * @uses $section_keys
		 * @uses $current_section
		 */
		include_once dirname( __FILE__ ) . '/views/html-admin-page-addons.php';
	}
}
