<?php
/**
 * WPBakery Integration helper functions.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\WPBakeryBuilder
 */

namespace EverestForms\Addons\WPBakeryBuilder;

/**
 * WPBakery Integration helper functions.
 *
 * @package EverestForms\Addons\WPBakeryBuilder
 *
 * @since 3.0.5
 */
class Helper {

	/**
	 * Return if WPBakery is active.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public static function is_wpbakery_active() {
		return in_array( 'js_composer/js_composer.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Check if the current request is for WPBakery editor.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public static function is_WPBakery_editor() {
		return isset( $_REQUEST['action'] ) &&
				( in_array( $_REQUEST['action'], array( 'vc_load_shortcode', 'vc_inline', 'vc_frontend_editor' ), true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Notice if the WPBakery is not instaled.
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
					wp_kses_post( 'WPBakery Integration addon requires WPBakery to be installed and activated.', 'everest-forms' ),
					esc_html__( 'Dismiss this notice.', 'everest-forms' )
				);
			}
		);

		return;
	}
}
