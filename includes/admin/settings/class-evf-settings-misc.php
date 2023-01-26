<?php
/**
 * EverestForms Misc Settings
 *
 * @package EverestForms\Admin
 * @version 1.9.8
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Misc', false ) ) {
	return new EVF_Settings_Misc();
}

/**
 * EVF_Settings_Misc.
 */
class EVF_Settings_Misc extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'misc';
		$this->label = esc_html__( 'Misc', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$allow_usage_notice_msg = wp_kses(
			__( ' Help us improve the plugin\'s features and receive an instant discount coupon with occasional email updates by sharing <a href="https://docs.wpeverest.com/everest-forms/docs/misc-settings/#2-toc-title" target="_blank">non-sensitive plugin data</a> with us.', 'everest-forms' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array()
				)
			)
		);

		if ( false !== evf_get_license_plan() ) {
			$allow_usage_notice_msg = wp_kses(
				__( 'Help us improve the plugin\'s features by sharing <a href="https://docs.wpeverest.com/everest-forms/docs/misc-settings/#2-toc-title" target="_blank">non-sensitive plugin data</a> with us.', 'everest-forms' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array()
					)
				)
			);
		}

		$settings = apply_filters(
			'everest_forms_misc_settings',
			array(
				array(
					'title' => esc_html__( 'Advanced Options', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'misc_options',
				),
				array(
					'title'   => esc_html__( 'Uninstall Everest Forms', 'everest-forms' ),
					'desc'    => __( '<strong>Heads Up!</strong> Check this if you would like to remove ALL Everest Forms data upon plugin deletion.', 'everest-forms' ),
					'id'      => 'everest_forms_uninstall_option',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => esc_html__( 'Allow Usage Tracking', 'everest-forms' ),
					'desc'    => $allow_usage_notice_msg,
					'id'      => 'everest_forms_allow_usage_tracking',
					'type'    => 'checkbox',
					'default' => 'no',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'misc_options',
				),
			)
		);

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		EVF_Admin_Settings::save_fields( $settings );
	}
}

return new EVF_Settings_Misc();
