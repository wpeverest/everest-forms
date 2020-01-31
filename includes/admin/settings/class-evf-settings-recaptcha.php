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
		$recaptcha_type = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$invisible      = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );
		$settings       = apply_filters(
			'everest_forms_recaptcha_settings',
			array(
				array(
					'title' => __( 'Google reCAPTCHA Integration', 'everest-forms' ),
					'type'  => 'title',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'  => sprintf( __( '<p>Google\'s reCAPTCHA is a free service that protects your website from spam and abuse while letting valid users pass through with ease.</p><p>reCAPTCHA uses an advanced risk analysis engine and adaptive challenges to keep automated software from engaging in abusive activities on your site.</p><p>Sites already using v2 reCAPTCHA will need to create new site keys before switching to the Invisible reCAPTCHA or v3 reCAPTCHA.</p><p><a href="%1$s" target="_blank">Read our documentation</a> for step-by-step instructions.</p>', 'everest-forms' ), 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ),
					'id'    => 'integration_options',
				),
				array(
					'title'    => __( 'reCAPTCHA type', 'everest-forms' ),
					'desc'     => __( 'Choose the type of reCAPTCHA for this site key.', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_type',
					'default'  => 'v2',
					'type'     => 'radio',
					'options'  => array(
						'v2' => esc_html__( 'reCAPTCHA v2', 'everest-forms' ),
						'v3' => esc_html__( 'reCAPTCHA v3', 'everest-forms' ),
					),
					'class'    => 'everest-forms-recaptcha-type',
					'desc_tip' => true,
				),
				array(
					'title'      => __( 'Site Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( __( 'Please enter your site key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_site_key',
					'is_visible' => 'v2' === $recaptcha_type && 'no' === $invisible,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => __( 'Secret Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( __( 'Please enter your secret key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_secret_key',
					'is_visible' => 'v2' === $recaptcha_type && 'no' === $invisible,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => __( 'Site Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( __( 'Please enter your site key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_invisible_site_key',
					'is_visible' => 'yes' === $invisible && 'v2' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => __( 'Secret Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( __( 'Please enter your secret key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_invisible_secret_key',
					'is_visible' => 'yes' === $invisible && 'v2' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => __( 'Invisible reCAPTCHA', 'everest-forms' ),
					'type'       => 'checkbox',
					'desc'       => __( 'Enable Invisible reCAPTCHA.', 'everest-forms' ),
					'id'         => 'everest_forms_recaptcha_v2_invisible',
					'is_visible' => 'v2' === $recaptcha_type,
					'default'    => 'no',
				),
				array(
					'title'      => __( 'Site Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( __( 'Please enter your site key for your reCAPTCHA v3. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v3_site_key',
					'is_visible' => 'v3' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => __( 'Secret Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( __( 'Please enter your secret key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v3_secret_key',
					'is_visible' => 'v3' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
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
