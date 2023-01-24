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
					'id'      => 'everest_forms_uninstall_option',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => esc_html__( 'Allow Usage Tracking', 'everest-forms' ),
					'desc'    => esc_html__( 'Get improved features by sharing non-sensitive plugin data and receive occasional email updates. Get a discount code emailed immediately.', 'everest-forms' ),
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
