<?php
/**
 * Beaver Integration helper functions.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\BeaverBuilder
 */

namespace EverestForms\Addons\BeaverBuilder;

/**
 * Beaver Integration helper functions.
 *
 * @package EverestForms\Addons\BeaverBuilder
 *
 * @since 3.0.5
 */
class Helper {

	/**
	 * Return if Beaver is active.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public static function is_beaver_active() {
		return in_array( 'bb-plugin/fl-builder.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Check if the current request is for beaver editor.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public static function is_beaver_editor() {
		return isset( $_REQUEST['fl_builder'] ) && isset( $_REQUEST['fl_builder_ui'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}


	/**
	 * Notice if the beaver is not installed.
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
					wp_kses_post( 'Beaver Integration addon requires Beaver to be installed and activated.', 'everest-forms' ),
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

		$forms[0] = esc_html__( 'Select a Form', 'everest-forms' );

		return $forms;
	}

		/**
		 * Return all the array data for courses categories settings.
		 *
		 * @since 1.10.0 [Free]
		 *
		 * @return array
		 */
	public static function get_everest_forms_setting() {
		return array(
			'tab-01' => array(
				'title'    => __( 'Everest Forms', 'everest-forms' ),
				'sections' => array(
					'everest-forms-selector' => array(
						'title'  => __( 'Form Selection', 'everest-forms' ),
						'fields' => array(
							'form_selection' => array(
								'type'    => 'select',
								'label'   => __( 'Select a Form', 'everest-forms' ),
								'options' => self::get_form_list(),
								'help'    => __( 'Choose a form from the list.', 'everest-forms' ),
							),
						),
					),
				),
			),
		);
	}
}
