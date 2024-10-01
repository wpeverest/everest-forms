<?php
/**
 * Oxygen Integration helper functions.
 *
 * @since xx.xx.xx
 * @package EverestForms\Addons\OxygenBuilder
 */

namespace EverestForms\Addons\OxygenBuilder;

/**
 * Oxygen Integration helper functions.
 *
 * @package EverestForms\Addons\OxygenBuilder
 *
 * @since xx.xx.xx
 */
class Helper {

	/**
	 * Return if Oxygen is active.
	 *
	 * @since xx.xx.xx
	 *
	 * @return boolean
	 */
	public static function is_oxygen_active() {
		return in_array( 'oxygen/functions.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Check if the current request is for oxygen editor.
	 *
	 * @since xx.xx.xx
	 *
	 * @return boolean
	 */
	public static function is_oxygen_editor() {
		return isset( $_REQUEST['action'] ) && ( in_array( $_REQUEST['action'], array( 'oxygen', 'oxygen_ajax' ), true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Notice if the oxygen is not instaled.
	 *
	 * @since xx.xx.xx
	 */
	public function print_admin_notice() {

		add_action(
			'admin_notices',
			function() {
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
					esc_html( 'Everest Forms:' ),
					wp_kses_post( 'Oxygen Integration addon requires Oxygen to be installed and activated.', 'everest-forms' ),
					esc_html__( 'Dismiss this notice.', 'everest-forms' )
				);
			}
		);

		return;
	}
}
