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
		$this->label = esc_html__( 'CAPTCHA', 'everest-forms' );

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
		$languages      = '{"languages":[{"Language":"Arabic","Value":"ar"},{"Language":"Afrikaans","Value":"af"},{"Language":"Amharic","Value":"am"},{"Language":"Armenian","Value":"hy"},{"Language":"Azerbaijani","Value":"az"},{"Language":"Basque","Value":"eu"},{"Language":"Bengali","Value":"bn"},{"Language":"Bulgarian","Value":"bg"},{"Language":"Catalan","Value":"ca"},{"Language":"Chinese (Hong Kong)","Value":"zh-HK"},{"Language":"Chinese (Simplified)","Value":"zh-CN"},{"Language":"Chinese (Traditional)","Value":"zh-TW"},{"Language":"Croatian","Value":"hr"},{"Language":"Czech","Value":"cs"},{"Language":"Danish","Value":"da"},{"Language":"Dutch *","Value":"nl"},{"Language":"English (UK)","Value":"en-GB"},{"Language":"English (US) *","Value":"en"},{"Language":"Estonian","Value":"et"},{"Language":"Filipino","Value":"fil"},{"Language":"Finnish","Value":"fi"},{"Language":"French *","Value":"fr"},{"Language":"French (Canadian)","Value":"fr-CA"},{"Language":"Galician","Value":"gl"},{"Language":"Georgian","Value":"ka"},{"Language":"German *","Value":"de"},{"Language":"German (Austria)","Value":"de-AT"},{"Language":"German (Switzerland)","Value":"de-CH"},{"Language":"Greek","Value":"el"},{"Language":"Gujarati","Value":"gu"},{"Language":"Hebrew","Value":"iw"},{"Language":"Hindi","Value":"hi"},{"Language":"Hungarain","Value":"hu"},{"Language":"Icelandic","Value":"is"},{"Language":"Indonesian","Value":"id"},{"Language":"Italian *","Value":"it"},{"Language":"Japanese","Value":"ja"},{"Language":"Kannada","Value":"kn"},{"Language":"Korean","Value":"ko"},{"Language":"Laothian","Value":"lo"},{"Language":"Latvian","Value":"lv"},{"Language":"Lithuanian","Value":"lt"},{"Language":"Malay","Value":"ms"},{"Language":"Malayalam","Value":"ml"},{"Language":"Marathi","Value":"mr"},{"Language":"Mongolian","Value":"mn"},{"Language":"Norwegian","Value":"no"},{"Language":"Persian","Value":"fa"},{"Language":"Polish","Value":"pl"},{"Language":"Portuguese *","Value":"pt"},{"Language":"Portuguese (Brazil)","Value":"pt-BR"},{"Language":"Portuguese (Portugal)","Value":"pt-PT"},{"Language":"Romanian","Value":"ro"},{"Language":"Russian","Value":"ru"},{"Language":"Serbian","Value":"sr"},{"Language":"Sinhalese","Value":"si"},{"Language":"Slovak","Value":"sk"},{"Language":"Slovenian","Value":"sl"},{"Language":"Spanish *","Value":"es"},{"Language":"Spanish (Latin America)","Value":"es-419"},{"Language":"Swahili","Value":"sw"},{"Language":"Swedish","Value":"sv"},{"Language":"Tamil","Value":"ta"},{"Language":"Telugu","Value":"te"},{"Language":"Thai","Value":"th"},{"Language":"Turkish","Value":"tr"},{"Language":"Ukrainian","Value":"uk"},{"Language":"Urdu","Value":"ur"},{"Language":"Vietnamese","Value":"vi"},{"Language":"Zulu","Value":"zu"}]}';
		$languages      = json_decode( $languages, true );
		$lang_options   = array();

		foreach ( $languages['languages'] as $key => $value ) {
			/* translators: %1$s - Langauge Name */
			$lang_options[ $value['Value'] ] = sprintf( esc_html__( '%s', 'everest-forms' ), $value['Language'] ); // phpcs:ignore
		}

		$settings = apply_filters(
			'everest_forms_recaptcha_settings',
			array(
				array(
					'title' => esc_html__( 'CAPTCHA Integration', 'everest-forms' ),
					'type'  => 'title',
					/* translators: %1$s - reCAPTCHA Integration Doc URL, %2$s - hCaptcha Integration Doc URL */
					'desc'  => sprintf( __( 'Get detailed documentation on integrating <a href="%1$s" target="_blank">reCAPTCHA</a> and <a href="%2$s" target="_blank">hCaptcha</a> with Everest forms.', 'everest-forms' ), 'https://docs.wpeverest.com/everest-forms/docs/how-to-integrate-google-recaptcha/', 'https://docs.wpeverest.com/everest-forms/docs/how-to-integrate-hcaptcha/' ),
					'id'    => 'integration_options',
				),
				array(
					'title'    => esc_html__( 'CAPTCHA Type', 'everest-forms' ),
					'desc'     => esc_html__( 'Choose the type of CAPTCHA for this site key.', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_type',
					'default'  => 'v2',
					'type'     => 'radio',
					'options'  => array(
						'v2'       => esc_html__( 'reCAPTCHA v2', 'everest-forms' ),
						'v3'       => esc_html__( 'reCAPTCHA v3', 'everest-forms' ),
						'hcaptcha' => esc_html__( 'hCaptcha', 'everest-forms' ),
					),
					'class'    => 'everest-forms-recaptcha-type',
					'desc_tip' => true,
				),
				array(
					'title'      => esc_html__( 'Site Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your site key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_site_key',
					'is_visible' => 'v2' === $recaptcha_type && 'no' === $invisible,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => esc_html__( 'Secret Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your secret key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_secret_key',
					'is_visible' => 'v2' === $recaptcha_type && 'no' === $invisible,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => esc_html__( 'Site Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your site key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_invisible_site_key',
					'is_visible' => 'v2' === $recaptcha_type && 'yes' === $invisible,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => esc_html__( 'Secret Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your secret key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v2_invisible_secret_key',
					'is_visible' => 'yes' === $invisible && 'v2' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => esc_html__( 'Invisible reCAPTCHA', 'everest-forms' ),
					'type'       => 'checkbox',
					'desc'       => esc_html__( 'Enable Invisible reCAPTCHA.', 'everest-forms' ),
					'id'         => 'everest_forms_recaptcha_v2_invisible',
					'is_visible' => 'v2' === $recaptcha_type,
					'default'    => 'no',
				),
				array(
					'title'      => esc_html__( 'Site Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your site key for your reCAPTCHA v3. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v3_site_key',
					'is_visible' => 'v3' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => esc_html__( 'Secret Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your secret key for your reCAPTCHA v3. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_v3_secret_key',
					'is_visible' => 'v3' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => esc_html__( 'Site Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your site key for your hCaptcha. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'is_visible' => 'hcaptcha' === $recaptcha_type,
					'id'         => 'everest_forms_recaptcha_hcaptcha_site_key',
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'      => esc_html__( 'Secret Key', 'everest-forms' ),
					'type'       => 'text',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'       => sprintf( esc_html__( 'Please enter your secret key for your hCaptcha. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/tutorials/how-to-integrate-google-recaptcha/' ) ),
					'id'         => 'everest_forms_recaptcha_hcaptcha_secret_key',
					'is_visible' => 'hcaptcha' === $recaptcha_type,
					'default'    => '',
					'desc_tip'   => true,
				),
				array(
					'title'             => esc_html__( 'Threshold Score', 'everest-forms' ),
					'type'              => 'number',
					/* translators: %1$s - Google reCAPTCHA docs url */
					'desc'              => esc_html__( 'reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot). If the score less than or equal to this threshold', 'everest-forms' ),
					'id'                => 'everest_forms_recaptcha_v3_threshold_score',
					'is_visible'        => 'v3' === $recaptcha_type,
					'custom_attributes' => array(
						'step' => '0.1',
						'min'  => '0.0',
						'max'  => '1.0',
					),
					'default'           => '0.4',
					'desc_tip'          => true,
				),
				array(
					'title'    => esc_html__( 'CAPTCHA Language ', 'everest-forms' ),
					'type'     => 'select',
					'desc'     => esc_html__( 'Choose a preferred language for displaying CAPTCHA text.', 'everest-forms' ),
					'id'       => 'everest_forms_recaptcha_recaptcha_language',
					'options'  => $lang_options,
					'class'    => 'evf-enhanced-select',
					'value'    => get_option( 'everest_forms_recaptcha_recaptcha_language', 'en-GB' ),
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
