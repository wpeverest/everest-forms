<?php
/**
 * Divi Integration helper functions.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\DiviBuilder
 */

namespace EverestForms\Addons\DiviBuilder;

/**
 * Oxygen Integration helper functions.
 *
 * @package EverestForms\Addons\DiviBuilder
 *
 * @since 3.0.5
 */
class Helper {

	/**
	 * Return if Divi is active.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public static function is_divi_active() {
		$active_theme_details = wp_get_theme();
		$theme_name           = $active_theme_details->Name;

		if ( 'Divi' === $theme_name ) {
			return true;
		}
	}

	/**
	 * Notice if the divi is not installed.
	 *
	 * @since 3.0.5
	 */
	public static function print_admin_notice() {
		add_action(
			'admin_notices',
			function () {
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
					esc_html( 'Everest Forms:' ),
					wp_kses_post( 'Divi Integration addon requires Divi theme to be installed and activated.', 'everest-forms' ),
					esc_html__( 'Dismiss this notice.', 'everest-forms' )
				);
			}
		);

		return;
	}
}
