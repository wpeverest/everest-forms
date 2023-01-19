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
					'id'      => 'everest_forms_misc_setting_uninstall_option',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'    => esc_html__( 'Allow Usage Tracking', 'everest-forms' ),
					'desc'     => esc_html__( 'Tracking usage data enables us to optimize our support by identifying popular WordPress configurations, themes, and plugins. This allows us to focus our testing on areas that will benefit the most users.', 'everest-forms' ),
					'desc_tip' => esc_html__( 'By gathering usage data, we can improve the support we provide to our users by gaining insight into which WordPress configurations, themes, and plugins are most commonly used. This knowledge allows us to focus our testing efforts on the areas that will have the greatest impact on our users\' experience.', 'everest-forms' ),
					'id'       => 'everest_forms_misc_setting_allow_usage_tracking',
					'type'     => 'checkbox',
					'default'  => 'no',
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
