<?php
/**
 * Bricks Integration helper functions.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\BricksBuilder
 */

namespace EverestForms\Addons\BricksBuilder;

/**
 * bricks Integration helper functions.
 *
 * @package EverestForms\Addons\BricksBuilder
 *
 * @since 3.0.5
 */
class Helper {

	/**
	 * Return if Bricks is active.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public static function is_bricks_active() {
		$active_theme = wp_get_theme();

		if ( $active_theme->stylesheet === 'bricks' && $active_theme->template === 'bricks' ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Notice if the bricks is not installed.
	 *
	 * @since 3.0.5
	 */
	public static function print_admin_notice() {

		add_action(
			'admin_notices',
			function() {
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
					esc_html( 'Everest Forms:' ),
					wp_kses_post( 'Bricks Integration addon requires Bricks to be installed and activated.', 'everest-forms' ),
					esc_html__( 'Dismiss this notice.', 'everest-forms' )
				);
			}
		);

		return;
	}

	/**
	 * Get the form list.
	 *
	 * @since 3.0.5
	 */
	public static function get_form_list() {
		$forms = evf_get_all_forms();

		if ( empty( $forms ) ) {
			return $forms;
		}
		return $forms;
	}
}
