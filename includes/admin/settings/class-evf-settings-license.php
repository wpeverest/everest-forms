<?php
/**
 * EverestForms License Settings.
 *
 * @package EverestForms\Pro\Admin.
 * @version xx.xx.xx
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_License', false ) ) {
	return new EVF_Settings_License();
}

/**
 * EVF_Settings_License.
 */
class EVF_Settings_License extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'license';
		$this->label = __( 'License', 'everest-forms-pro' );

		add_filter( 'everest_forms_admin_field_license_options', array( $this, 'license_options_settings' ), 10, 2 );
		parent::__construct();

		if ( isset( $_GET['tab'] ) && 'license' === $_GET['tab'] ) { // phpcs:ignore
			add_filter( 'everest_forms_setting_save_label', array( $this, 'everest_forms_license_setting_label' ) );
		}
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 *
	 * @since xx.xx.xx
	 */
	public function get_settings() {
		$license_plan   = evf_get_license_plan();
		$license_expiry = get_option( 'everest-forms-pro_license_active', '' );

		if ( ! empty( $license_plan ) && isset( $license_expiry->expires ) && '' !== $license_expiry->expires ) {
			$license_expiry_date           = new DateTime( $license_expiry->expires );
			$formatted_license_expiry_date = $license_expiry_date->format( 'jS F Y h:i A' );
		}

		if ( ! empty( $license_plan ) ) {
			$settings                    = apply_filters(
				'everest_forms_license_settings',
				array(
					array(
						'title' => esc_html__( 'License Activation', 'everest-forms-pro' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'license_options',
					),
					array(
						'title'    => esc_html__( 'Deactivate License', 'everest-forms-pro' ),
						'desc'     => esc_html__( 'Deactivate the license of Everest Forms plugin', 'everest-forms-pro' ),
						'id'       => '',
						'default'  => 'no',
						'type'     => 'link',
						'id'       => 'everest-forms_deactivate-license_key',
						'css'      => 'background:red; border:none; color:white;',
						'desc_tip' => true,
						'buttons'  => array(
							array(
								'title' => __( 'Deactivate License', 'everest-forms-pro' ),
								'href'  => add_query_arg( 'everest-forms-pro_deactivate_license', 1, admin_url( 'admin.php?page=evf-settings&tab=license' ) ),
								'class' => 'everest_forms_license_key_deactivation',
							),
						),
					),
					array(
						'title' => esc_html__( 'License Plan', 'everest-forms-pro' ),
						'id'    => 'everest_forms_license_plan',
						'type'  => 'display_div',
						'value' => ucfirst( $license_plan ),
					),
					array(
						'title' => esc_html__( 'License Expiry Date', 'everest-forms-pro' ),
						'id'    => 'everest_forms_license_expiry_date',
						'type'  => 'display_div',
						'value' => $formatted_license_expiry_date,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'license_options',
					),
				)
			);
			$GLOBALS['hide_save_button'] = true;
		} else {
			$settings = apply_filters(
				'everest_forms_license_settings',
				array(
					array(
						'title' => esc_html__( 'License Activation', 'everest-forms' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'license_options',
					),
					array(
						'title'    => esc_html__( 'License Key', 'everest-forms-pro' ),
						'desc'     => __( 'Please enter the license key', 'everest-forms-pro' ),
						'id'       => 'everest-forms-pro_license_key',
						'type'     => 'text',
						'default'  => '',
						'desc_tip' => true,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'license_options',
					),
				)
			);
		}

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		EVF_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Label for Save button.
	 *
	 * @return string
	 */
	public function everest_forms_license_setting_label() {
		return esc_html__( 'Activate License', 'user-registration' );
	}
}

return new EVF_Settings_License();
