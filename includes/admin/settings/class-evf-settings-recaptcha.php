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
		add_action( 'everest_forms_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'everest_forms_update_options_' . $this->id, array( $this, 'handle_captcha_enable_toggle' ), 5 );

		$this->maybe_migrate_legacy_settings();
	}

	/**
	 * Migrate legacy CAPTCHA settings to new accordion format.
	 * Only runs once when transitioning from old to new format.
	 */
	private function maybe_migrate_legacy_settings() {
		$migration_complete = get_option( 'everest_forms_recaptcha_migration_v2_complete', false );

		if ( $migration_complete ) {
			return;
		}

		$captcha_types      = array( 'v2', 'v3', 'hcaptcha', 'turnstile' );
		$has_enable_options = false;

		foreach ( $captcha_types as $type ) {
			if ( false !== get_option( 'everest_forms_recaptcha_' . $type . '_enable', false ) ) {
				$has_enable_options = true;
				break;
			}
		}

		if ( $has_enable_options ) {
			update_option( 'everest_forms_recaptcha_migration_v2_complete', true );
			return;
		}

		$active_type = get_option( 'everest_forms_recaptcha_type', '' );

		if ( ! empty( $active_type ) && in_array( $active_type, $captcha_types, true ) ) {
			foreach ( $captcha_types as $type ) {
				$enable_value = ( $type === $active_type ) ? 'yes' : 'no';
				update_option( 'everest_forms_recaptcha_' . $type . '_enable', $enable_value );
			}
		} else {
			foreach ( $captcha_types as $type ) {
				update_option( 'everest_forms_recaptcha_' . $type . '_enable', 'no' );
			}
		}

		update_option( 'everest_forms_recaptcha_migration_v2_complete', true );
	}

	/**
	 * Handle CAPTCHA enable toggle to ensure only one is active.
	 */
	public function handle_captcha_enable_toggle() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'everest-forms-settings' ) ) {
			return;
		}

		$captcha_types = array( 'v2', 'v3', 'hcaptcha', 'turnstile' );

		$has_enable_field = false;
		foreach ( $captcha_types as $type ) {
			if ( isset( $_POST[ 'everest_forms_recaptcha_' . $type . '_enable' ] ) ) {
				$has_enable_field = true;
				break;
			}
		}

		if ( ! $has_enable_field ) {
			return;
		}

		$enabled_captcha   = '';
		$validation_errors = array();

		// Check reCAPTCHA v2
		if ( isset( $_POST['everest_forms_recaptcha_v2_enable'] ) ) {
			$v2_enable = sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v2_enable'] ) );
			if ( 'yes' === $v2_enable ) {
				$enabled_captcha = 'v2';

				// Check for invisible mode
				$invisible = isset( $_POST['everest_forms_recaptcha_v2_invisible'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v2_invisible'] ) ) : 'no';

				if ( 'yes' === $invisible ) {
					// Validate invisible keys
					$invisible_site_key   = isset( $_POST['everest_forms_recaptcha_v2_invisible_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v2_invisible_site_key'] ) ) : '';
					$invisible_secret_key = isset( $_POST['everest_forms_recaptcha_v2_invisible_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v2_invisible_secret_key'] ) ) : '';

					if ( empty( $invisible_site_key ) || empty( $invisible_secret_key ) ) {
						$validation_errors[] = __( 'Please enter both Site Key and Secret Key for Invisible reCAPTCHA v2.', 'everest-forms' );
					}
				} else {
					// Validate regular v2 keys
					$site_key   = isset( $_POST['everest_forms_recaptcha_v2_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v2_site_key'] ) ) : '';
					$secret_key = isset( $_POST['everest_forms_recaptcha_v2_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v2_secret_key'] ) ) : '';

					if ( empty( $site_key ) || empty( $secret_key ) ) {
						$validation_errors[] = __( 'Please enter both Site Key and Secret Key for reCAPTCHA v2.', 'everest-forms' );
					}
				}
			}
		}

		// Check reCAPTCHA v3
		if ( empty( $enabled_captcha ) && isset( $_POST['everest_forms_recaptcha_v3_enable'] ) ) {
			$v3_enable = sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v3_enable'] ) );
			if ( 'yes' === $v3_enable ) {
				$enabled_captcha = 'v3';

				$site_key   = isset( $_POST['everest_forms_recaptcha_v3_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v3_site_key'] ) ) : '';
				$secret_key = isset( $_POST['everest_forms_recaptcha_v3_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_v3_secret_key'] ) ) : '';

				if ( empty( $site_key ) || empty( $secret_key ) ) {
					$validation_errors[] = __( 'Please enter both Site Key and Secret Key for reCAPTCHA v3.', 'everest-forms' );
				}
			}
		}

		// Check hCaptcha
		if ( empty( $enabled_captcha ) && isset( $_POST['everest_forms_recaptcha_hcaptcha_enable'] ) ) {
			$hcaptcha_enable = sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_hcaptcha_enable'] ) );
			if ( 'yes' === $hcaptcha_enable ) {
				$enabled_captcha = 'hcaptcha';

				$site_key   = isset( $_POST['everest_forms_recaptcha_hcaptcha_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_hcaptcha_site_key'] ) ) : '';
				$secret_key = isset( $_POST['everest_forms_recaptcha_hcaptcha_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_hcaptcha_secret_key'] ) ) : '';

				if ( empty( $site_key ) || empty( $secret_key ) ) {
					$validation_errors[] = __( 'Please enter both Site Key and Secret Key for hCaptcha.', 'everest-forms' );
				}
			}
		}

		// Check Cloudflare Turnstile
		if ( empty( $enabled_captcha ) && isset( $_POST['everest_forms_recaptcha_turnstile_enable'] ) ) {
			$turnstile_enable = sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_turnstile_enable'] ) );
			if ( 'yes' === $turnstile_enable ) {
				$enabled_captcha = 'turnstile';

				$site_key   = isset( $_POST['everest_forms_recaptcha_turnstile_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_turnstile_site_key'] ) ) : '';
				$secret_key = isset( $_POST['everest_forms_recaptcha_turnstile_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['everest_forms_recaptcha_turnstile_secret_key'] ) ) : '';

				if ( empty( $site_key ) || empty( $secret_key ) ) {
					$validation_errors[] = __( 'Please enter both Site Key and Secret Key for Cloudflare Turnstile.', 'everest-forms' );
				}
			}
		}

		if ( ! empty( $validation_errors ) ) {

			foreach ( $captcha_types as $type ) {
				update_option( 'everest_forms_recaptcha_' . $type . '_enable', 'no' );
			}
			update_option( 'everest_forms_recaptcha_type', '' );

			$error_message = implode( ' ', $validation_errors );
			$this->add_toast_redirect( $error_message, 'error' );
			return;
		}

		if ( ! empty( $enabled_captcha ) ) {
			foreach ( $captcha_types as $type ) {
				if ( $type === $enabled_captcha ) {
					update_option( 'everest_forms_recaptcha_' . $type . '_enable', 'yes' );
					update_option( 'everest_forms_recaptcha_type', $type );
				} else {
					update_option( 'everest_forms_recaptcha_' . $type . '_enable', 'no' );
				}
			}
		} else {
			foreach ( $captcha_types as $type ) {
				update_option( 'everest_forms_recaptcha_' . $type . '_enable', 'no' );
			}
			update_option( 'everest_forms_recaptcha_type', '' );
		}
	}

	/**
	 * Add toast message and redirect.
	 *
	 * @param string $message The message to display.
	 * @param string $type The type of toast (success, error, warning, info).
	 */
	private function add_toast_redirect( $message, $type = 'error' ) {
		$redirect_url = add_query_arg(
			array(
				'page'           => 'evf-settings',
				'tab'            => 'recaptcha',
				'section'        => 'integration',
				'evf_toast'      => rawurlencode( base64_encode( $message ) ),
				'evf_toast_type' => $type,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}


	/**
	 * Get sections for CAPTCHA tab.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'integration' => esc_html__( 'Integration', 'everest-forms' ),
			'language'    => esc_html__( 'Language', 'everest-forms' ),
		);

		return apply_filters( 'everest_forms_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output sections in navigation sidebar.
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
			return;
		}

		echo '<ul class="evf-subsections">';

		foreach ( $sections as $id => $label ) {
			$url = add_query_arg(
				array(
					'page'    => 'evf-settings',
					'tab'     => $this->id,
					'section' => sanitize_title( $id ),
				),
				admin_url( 'admin.php' )
			);

			$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'integration';

			echo '<li><a href="' . esc_url( $url ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a></li>';
		}

		echo '</ul>';
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'integration';

		if ( 'language' === $current_section ) {
			$settings = $this->get_language_settings();
		} else {
			$settings = $this->get_integration_settings();
		}

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Get CAPTCHA integration settings (all CAPTCHA providers as accordions).
	 *
	 * @return array
	 */
	public function get_integration_settings() {
		$recaptcha_type = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$invisible      = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );

		$v2_enabled        = get_option( 'everest_forms_recaptcha_v2_enable', 'no' );
		$v3_enabled        = get_option( 'everest_forms_recaptcha_v3_enable', 'no' );
		$hcaptcha_enabled  = get_option( 'everest_forms_recaptcha_hcaptcha_enable', 'no' );
		$turnstile_enabled = get_option( 'everest_forms_recaptcha_turnstile_enable', 'no' );

		$settings = apply_filters(
			'everest_forms_recaptcha_integration_settings',
			array(
				array(
					'title' => esc_html__( 'CAPTCHA Integration', 'everest-forms' ),
					'type'  => 'title',
					/* translators: %1$s - reCAPTCHA Integration Doc URL, %2$s - hCaptcha Integration Doc URL, %3$s - Cloudflare Turnstile Integration Doc URL */
					'desc'  => sprintf( __( 'Get detailed documentation on integrating <a href="%1$s" target="_blank">reCAPTCHA</a>, <a href="%2$s" target="_blank">hCaptcha</a> and <a href="%3$s" target="_blank">Cloudflare Turnstile</a> with Everest forms.', 'everest-forms' ), 'https://docs.everestforms.net/docs/how-to-integrate-google-recaptcha/', 'https://docs.everestforms.net/docs/how-to-integrate-hcaptcha/', 'https://docs.everestforms.net/docs/how-to-integrate-cloudflare-turnstile-with-the-everest-forms/' ),
					'id'    => 'integration_options',
				),
				array(
					'type'  => 'accordion',
					'items' => array(
						array(
							'title'      => esc_html__( 'reCAPTCHA v2', 'everest-forms' ),
							'icon'       => plugins_url( 'assets/images/captcha/reCAPTCHA-v2-v3.png', EVF_PLUGIN_FILE ),
							'is_enabled' => 'yes' === $v2_enabled,
							'fields'     => array(
								array(
									'title'    => esc_html__( 'Enable reCAPTCHA v2', 'everest-forms' ),
									'type'     => 'toggle',
									'desc'     => esc_html__( 'Enable reCAPTCHA v2. Note: Enabling this will automatically disable other CAPTCHA providers.', 'everest-forms' ),
									'id'       => 'everest_forms_recaptcha_v2_enable',
									'default'  => 'no',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Invisible reCAPTCHA', 'everest-forms' ),
									'type'     => 'toggle',
									'desc'     => esc_html__( 'Enable Invisible reCAPTCHA.', 'everest-forms' ),
									'id'       => 'everest_forms_recaptcha_v2_invisible',
									'default'  => 'no',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Site Key (reCAPTCHA V2)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - Google reCAPTCHA docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your site key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-google-recaptcha/' ) ),
									'id'       => 'everest_forms_recaptcha_v2_site_key',
									'default'  => '',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Secret Key (reCAPTCHA V2)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - Google reCAPTCHA docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your secret key for your reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-google-recaptcha/' ) ),
									'id'       => 'everest_forms_recaptcha_v2_secret_key',
									'default'  => '',
									'desc_tip' => true,
								),
								array(
									'title'      => esc_html__( 'Invisible Site Key', 'everest-forms' ),
									'type'       => 'text',
									/* translators: %1$s - Google reCAPTCHA docs url */
									'desc'       => sprintf( esc_html__( 'Please enter your site key for invisible reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-google-recaptcha/' ) ),
									'id'         => 'everest_forms_recaptcha_v2_invisible_site_key',
									'is_visible' => 'yes' === $invisible,
									'default'    => '',
									'desc_tip'   => true,
								),
								array(
									'title'      => esc_html__( 'Invisible Secret Key', 'everest-forms' ),
									'type'       => 'text',
									/* translators: %1$s - Google reCAPTCHA docs url */
									'desc'       => sprintf( esc_html__( 'Please enter your secret key for invisible reCAPTCHA v2. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-google-recaptcha/' ) ),
									'id'         => 'everest_forms_recaptcha_v2_invisible_secret_key',
									'is_visible' => 'yes' === $invisible,
									'default'    => '',
									'desc_tip'   => true,
								),
							),
						),
						array(
							'title'      => esc_html__( 'reCAPTCHA v3', 'everest-forms' ),
							'icon'       => plugins_url( 'assets/images/captcha/reCAPTCHA-v2-v3.png', EVF_PLUGIN_FILE ),
							'is_enabled' => 'yes' === $v3_enabled,
							'fields'     => array(
								array(
									'title'    => esc_html__( 'Enable reCAPTCHA v3', 'everest-forms' ),
									'type'     => 'toggle',
									'desc'     => esc_html__( 'Enable reCAPTCHA v3. Note: Enabling this will automatically disable other CAPTCHA providers.', 'everest-forms' ),
									'id'       => 'everest_forms_recaptcha_v3_enable',
									'default'  => 'no',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Site Key (reCAPTCHA V3)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - Google reCAPTCHA docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your site key for your reCAPTCHA v3. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-google-recaptcha/' ) ),
									'id'       => 'everest_forms_recaptcha_v3_site_key',
									'default'  => '',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Secret Key (reCAPTCHA V3)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - Google reCAPTCHA docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your secret key for your reCAPTCHA v3. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-google-recaptcha/' ) ),
									'id'       => 'everest_forms_recaptcha_v3_secret_key',
									'default'  => '',
									'desc_tip' => true,
								),
								array(
									'title'             => esc_html__( 'Threshold Score', 'everest-forms' ),
									'type'              => 'number',
									'desc'              => esc_html__( 'reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot). If the score is less than or equal to this threshold, the form submission will be blocked.', 'everest-forms' ),
									'id'                => 'everest_forms_recaptcha_v3_threshold_score',
									'custom_attributes' => array(
										'step' => '0.1',
										'min'  => '0.0',
										'max'  => '1.0',
									),
									'default'           => '0.4',
									'desc_tip'          => true,
								),
							),
						),
						array(
							'title'      => esc_html__( 'hCaptcha', 'everest-forms' ),
							'icon'       => plugins_url( 'assets/images/captcha/hCAPTCHA-logo.png', EVF_PLUGIN_FILE ),
							'is_enabled' => 'yes' === $hcaptcha_enabled,
							'fields'     => array(
								array(
									'title'    => esc_html__( 'Enable hCaptcha', 'everest-forms' ),
									'type'     => 'toggle',
									'desc'     => esc_html__( 'Enable hCaptcha. Note: Enabling this will automatically disable other CAPTCHA providers.', 'everest-forms' ),
									'id'       => 'everest_forms_recaptcha_hcaptcha_enable',
									'default'  => 'no',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Site Key (hCaptcha)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - hCaptcha docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your site key for your hCaptcha. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-hcaptcha/' ) ),
									'id'       => 'everest_forms_recaptcha_hcaptcha_site_key',
									'default'  => '',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Secret Key (hCaptcha)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - hCaptcha docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your secret key for your hCaptcha. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-hcaptcha/' ) ),
									'id'       => 'everest_forms_recaptcha_hcaptcha_secret_key',
									'default'  => '',
									'desc_tip' => true,
								),
							),
						),
						array(
							'title'      => esc_html__( 'Cloudflare Turnstile', 'everest-forms' ),
							'icon'       => plugins_url( 'assets/images/captcha/cloudflare-logo.png', EVF_PLUGIN_FILE ),
							'is_enabled' => 'yes' === $turnstile_enabled,
							'fields'     => array(
								array(
									'title'    => esc_html__( 'Enable Cloudflare Turnstile', 'everest-forms' ),
									'type'     => 'toggle',
									'desc'     => esc_html__( 'Enable Cloudflare Turnstile. Note: Enabling this will automatically disable other CAPTCHA providers.', 'everest-forms' ),
									'id'       => 'everest_forms_recaptcha_turnstile_enable',
									'default'  => 'no',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Site Key (Cloudflare Turnstile)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - Cloudflare Turnstile docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your site key for your Cloudflare Turnstile. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-cloudflare-turnstile-with-the-everest-forms/' ) ),
									'id'       => 'everest_forms_recaptcha_turnstile_site_key',
									'default'  => '',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Secret Key (Cloudflare Turnstile)', 'everest-forms' ),
									'type'     => 'text',
									/* translators: %1$s - Cloudflare Turnstile docs url */
									'desc'     => sprintf( esc_html__( 'Please enter your secret key for your Cloudflare Turnstile. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-cloudflare-turnstile-with-the-everest-forms/' ) ),
									'id'       => 'everest_forms_recaptcha_turnstile_secret_key',
									'default'  => '',
									'desc_tip' => true,
								),
								array(
									'title'    => esc_html__( 'Theme', 'everest-forms' ),
									'type'     => 'select',
									/* translators: %1$s - Cloudflare Turnstile docs url */
									'desc'     => sprintf( esc_html__( 'Please select theme mode for your Cloudflare Turnstile. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/how-to-integrate-cloudflare-turnstile-with-the-everest-forms/' ) ),
									'id'       => 'everest_forms_recaptcha_turnstile_theme',
									'options'  => array(
										'auto'  => esc_html__( 'Auto', 'everest-forms' ),
										'light' => esc_html__( 'Light', 'everest-forms' ),
										'dark'  => esc_html__( 'Dark', 'everest-forms' ),
									),
									'default'  => 'auto',
									'class'    => 'evf-enhanced-select',
									'desc_tip' => true,
								),
							),
						),
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'integration_options',
				),
			)
		);

		return $settings;
	}

	/**
	 * Get CAPTCHA language settings.
	 *
	 * @return array
	 */
	public function get_language_settings() {
		$languages    = '{"languages":[{"Language":"Arabic","Value":"ar"},{"Language":"Afrikaans","Value":"af"},{"Language":"Amharic","Value":"am"},{"Language":"Armenian","Value":"hy"},{"Language":"Azerbaijani","Value":"az"},{"Language":"Basque","Value":"eu"},{"Language":"Bengali","Value":"bn"},{"Language":"Bulgarian","Value":"bg"},{"Language":"Catalan","Value":"ca"},{"Language":"Chinese (Hong Kong)","Value":"zh-HK"},{"Language":"Chinese (Simplified)","Value":"zh-CN"},{"Language":"Chinese (Traditional)","Value":"zh-TW"},{"Language":"Croatian","Value":"hr"},{"Language":"Czech","Value":"cs"},{"Language":"Danish","Value":"da"},{"Language":"Dutch *","Value":"nl"},{"Language":"English (UK)","Value":"en-GB"},{"Language":"English (US) *","Value":"en"},{"Language":"Estonian","Value":"et"},{"Language":"Filipino","Value":"fil"},{"Language":"Finnish","Value":"fi"},{"Language":"French *","Value":"fr"},{"Language":"French (Canadian)","Value":"fr-CA"},{"Language":"Galician","Value":"gl"},{"Language":"Georgian","Value":"ka"},{"Language":"German *","Value":"de"},{"Language":"German (Austria)","Value":"de-AT"},{"Language":"German (Switzerland)","Value":"de-CH"},{"Language":"Greek","Value":"el"},{"Language":"Gujarati","Value":"gu"},{"Language":"Hebrew","Value":"iw"},{"Language":"Hindi","Value":"hi"},{"Language":"Hungarain","Value":"hu"},{"Language":"Icelandic","Value":"is"},{"Language":"Indonesian","Value":"id"},{"Language":"Italian *","Value":"it"},{"Language":"Japanese","Value":"ja"},{"Language":"Kannada","Value":"kn"},{"Language":"Korean","Value":"ko"},{"Language":"Laothian","Value":"lo"},{"Language":"Latvian","Value":"lv"},{"Language":"Lithuanian","Value":"lt"},{"Language":"Malay","Value":"ms"},{"Language":"Malayalam","Value":"ml"},{"Language":"Marathi","Value":"mr"},{"Language":"Mongolian","Value":"mn"},{"Language":"Norwegian","Value":"no"},{"Language":"Persian","Value":"fa"},{"Language":"Polish","Value":"pl"},{"Language":"Portuguese *","Value":"pt"},{"Language":"Portuguese (Brazil)","Value":"pt-BR"},{"Language":"Portuguese (Portugal)","Value":"pt-PT"},{"Language":"Romanian","Value":"ro"},{"Language":"Russian","Value":"ru"},{"Language":"Serbian","Value":"sr"},{"Language":"Sinhalese","Value":"si"},{"Language":"Slovak","Value":"sk"},{"Language":"Slovenian","Value":"sl"},{"Language":"Spanish *","Value":"es"},{"Language":"Spanish (Latin America)","Value":"es-419"},{"Language":"Swahili","Value":"sw"},{"Language":"Swedish","Value":"sv"},{"Language":"Tamil","Value":"ta"},{"Language":"Telugu","Value":"te"},{"Language":"Thai","Value":"th"},{"Language":"Turkish","Value":"tr"},{"Language":"Ukrainian","Value":"uk"},{"Language":"Urdu","Value":"ur"},{"Language":"Vietnamese","Value":"vi"},{"Language":"Zulu","Value":"zu"}]}';
		$languages    = json_decode( $languages, true );
		$lang_options = array();

		foreach ( $languages['languages'] as $key => $value ) {
			/* translators: %1$s - Langauge Name */
			$lang_options[ $value['Value'] ] = sprintf( esc_html__( '%s', 'everest-forms' ), $value['Language'] );
		}

		$settings = array(
			array(
				'title' => esc_html__( 'CAPTCHA Language', 'everest-forms' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'language_options',
			),
			array(
				'title'    => esc_html__( 'CAPTCHA Language', 'everest-forms' ),
				'type'     => 'select',
				'desc'     => esc_html__( 'Choose a preferred language for displaying CAPTCHA text.', 'everest-forms' ),
				'id'       => 'everest_forms_recaptcha_recaptcha_language',
				'options'  => $lang_options,
				'class'    => 'evf-enhanced-select',
				'default'  => 'en-GB',
				'desc_tip' => true,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'language_options',
			),
		);

		return $settings;
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
