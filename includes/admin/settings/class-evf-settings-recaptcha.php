<?php
/**
 * EverestForms reCAPTCHA Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_reCAPTCHA', false ) ) {
	return new EVF_Settings_reCAPTCHA();
}

/**
 * EVF_Settings_reCAPTCHA.
 */
class EVF_Settings_reCAPTCHA extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'recaptcha';
		$this->label = __( 'reCAPTCHA', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'everest_forms_recaptcha_settings',
			array(
				array(
					'title' => __( 'Google reCaptcha Integation', 'everest-forms' ),
					'type'  => 'title',
					/* translators: %s - Google reCaptch URL. */
					'desc'  => sprintf( __( 'Get site key and secret key from google <a href="%s" target="_blank">reCaptcha</a>.', 'everest-forms' ), 'https://www.google.com/recaptcha' ),
					'id'    => 'integration_options',
				),
				array(
					'title'    => __( 'reCaptcha version', 'everest-forms' ),
					'desc'     => __( 'Choose the reCaptcha version', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_version',
					'default'  => 'v2',
					'type'     => 'radio',
					'options'  => array(
						'v2' => esc_html__( 'reCaptcha v2', 'everest-forms' ),
						'v3' => esc_html__( 'reCaptcha v3', 'everest-forms' ),
					),
					'class'    => '',
					'css'      => '',
					'desc_tip' => true,

				),
				array(
					'title'    => __( 'Site Key', 'everest-forms' ),
					'desc'     => __( 'Get site key from google.', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_site_key',
					'default'  => '',
					'type'     => 'text',
					'class'    => 'everest_foms_recaptcha_v2',
					'css'      => 'min-width: 350px;',
					'desc_tip' => true,

				),
				array(
					'title'    => __( 'Secret Key', 'everest-forms' ),
					'desc'     => __( 'Get secret key from google.', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_site_secret',
					'default'  => '',
					'type'     => 'text',
					'class'    => 'everest_foms_recaptcha_v2',
					'css'      => 'min-width: 350px;',
					'desc_tip' => true,

				),
				array(
					'title'   => __( 'Invisible reCaptcha', 'everest-forms' ),
					'desc'    => __( 'Check this option to activate invisible reCaptcha.', 'everest-forms' ),
					'id'      => 'everest_forms_recaptcha_v2_invisible',
					'default' => 'no',
					'class'   => 'everest_foms_recaptcha_v2',
					'type'    => 'checkbox',
				),
				array(
					'title'    => __( 'Site Key', 'everest-forms' ),
					'desc'     => __( 'Get site key from google.', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_v3_site_key',
					'default'  => '',
					'type'     => 'text',
					'class'    => '',
					'css'      => 'min-width: 350px;',
					'desc_tip' => true,

				),
				array(
					'title'    => __( 'Secret Key', 'everest-forms' ),
					'desc'     => __( 'Get secret key from google.', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_v3_site_secret',
					'default'  => '',
					'type'     => 'text',
					'class'    => '',
					'css'      => 'min-width: 350px;',
					'desc_tip' => true,

				),
				array(
					'type' => 'sectionend',
					'id'   => 'integration_options',
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

return new EVF_Settings_reCAPTCHA();
