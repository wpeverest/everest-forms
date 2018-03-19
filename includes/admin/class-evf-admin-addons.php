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
	 * Get sections for the extensions screen
	 *
	 * @return array of objects
	 */
	public static function get_sections() {
		if ( false === ( $sections = get_transient( 'evf_extensions_sections' ) ) ) {
			$raw_sections = wp_safe_remote_get( 'https://raw.githubusercontent.com/wpeverest/extensions-json/master/everest-forms/addons-section.json', array( 'user-agent' => 'EverestForms Addons Page' ) );
			if ( ! is_wp_error( $raw_sections ) ) {
				$sections = json_decode( wp_remote_retrieve_body( $raw_sections ) );

				if ( $sections ) {
					set_transient( 'evf_extensions_sections', $sections, WEEK_IN_SECONDS );
				}
			}
		}

		$extension_sections = array();

		if ( $sections ) {
			foreach ( $sections as $sections_id => $section ) {
				if ( empty( $sections_id ) ) {
					continue;
				}
				$extension_sections[ $sections_id ]           = new stdClass;
				$extension_sections[ $sections_id ]->title    = rp_clean( $section->title );
				$extension_sections[ $sections_id ]->endpoint = rp_clean( $section->endpoint );
			}
		}

		return apply_filters( 'everest_forms_extensions_sections', $extension_sections );
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
		$theme           = wp_get_theme();
		$section_key     = array_keys( $sections );
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : current( $section_key );

		/**
		 * Addon page view.
		 *
		 * @uses $addons
		 * @uses $sections
		 * @uses $theme
		 * @uses $section_key
		 * @uses $current_section
		 */
		include_once dirname( __FILE__ ) . '/views/html-admin-page-addons.php';
	}
}
