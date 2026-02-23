<?php
/**
 * Shield silentCAPTCHA integration.
 *
 * @since 3.7.1
 * @package EverestForms\Addons\ShieldSilentCaptcha
 */

namespace EverestForms\Addons\ShieldSilentCaptcha;

use EverestForms\Traits\Singleton;

/**
 * Shield silentCAPTCHA integration.
 *
 * @since 3.7.1
 */
class ShieldSilentCaptcha {

	use Singleton;

	/**
	 * Shield global option key.
	 */
	const SETTING_KEY = 'shield_silent_captcha';

	/**
	 * Help URL.
	 */
	const HELP_URL = 'https://clk.shldscrty.com/silentcaptchaintegrationhelp';

	/**
	 * Cached Shield availability.
	 *
	 * @var bool|null
	 */
	protected $shield_available = null;

	/**
	 * Cached Shield threshold-zero status.
	 *
	 * @var bool|null
	 */
	protected $shield_threshold_zero = null;

	/**
	 * Constructor.
	 *
	 * @since 3.7.1
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 3.7.1
	 */
	public function setup() {
		add_filter( 'everest_forms_recaptcha_integration_settings', array( $this, 'add_recaptcha_integration_settings' ) );
		add_action( 'everest_forms_inline_security_settings', array( $this, 'add_inline_security_settings' ) );
		add_action( 'everest_forms_admin_field_shield_silent_captcha_info', array( $this, 'render_global_info_field' ) );
		add_filter( 'everest_forms_process_initial_errors', array( $this, 'filter_initial_errors' ), 10, 2 );
	}

	/**
	 * Add Shield provider to CAPTCHA integration settings.
	 *
	 * @since 3.7.1
	 *
	 * @param array $settings Settings array.
	 * @return array
	 */
	public function add_recaptcha_integration_settings( $settings ) {
		$is_available     = $this->is_shield_available();
		$toggle_field     = $this->get_global_toggle_field( $is_available );
		$info_field       = array(
			'type' => 'shield_silent_captcha_info',
			'desc' => $this->get_global_toggle_desc( $is_available ),
		);
		$shield_accordion = array(
			'title'      => esc_html__( 'Shield silentCAPTCHA', 'everest-forms' ),
			'icon'       => plugins_url( 'assets/images/captcha/shield-security-logo.png', EVF_PLUGIN_FILE ),
			'is_enabled' => $this->is_global_shield_enabled(),
			'fields'     => array( $toggle_field, $info_field ),
		);

		foreach ( $settings as &$setting ) {
			if ( ! isset( $setting['type'] ) || 'accordion' !== $setting['type'] ) {
				continue;
			}

			if ( ! isset( $setting['items'] ) || ! is_array( $setting['items'] ) ) {
				continue;
			}

			$setting['items'][] = $shield_accordion;
			break;
		}

		return $settings;
	}

	/**
	 * Render Shield global info paragraph below toggle control.
	 *
	 * @since 3.7.1
	 *
	 * @param array $field Field config.
	 */
	public function render_global_info_field( $field ) {
		if ( empty( $field['desc'] ) ) {
			return;
		}
		?>
		<div class="everest-forms-global-settings">
			<div class="everest-forms-global-settings--field">
				<p class="description"><?php echo wp_kses_post( $field['desc'] ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Shield per-form toggle in builder security panel.
	 *
	 * @since 3.7.1
	 *
	 * @param object $builder Builder object.
	 */
	public function add_inline_security_settings( $builder ) {
		$is_available = $this->is_shield_available();
		$toggle_args  = array(
			'default' => '0',
			'tooltip' => esc_html__( 'Enable Shield silentCAPTCHA. Submission is checked only when this toggle and the global toggle are both enabled.', 'everest-forms' ),
		);

		echo '<div class="everest-forms-border-container">';
		echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Shield silentCAPTCHA', 'everest-forms' ) . '</h4>';

		everest_forms_panel_field(
			'toggle',
			'settings',
			self::SETTING_KEY,
			$builder->form_data,
			esc_html__( 'Enable Shield silentCAPTCHA bot protection', 'everest-forms' ),
			$toggle_args
		);

		if ( ! $is_available ) {
			printf(
				'<p class="everest-forms-notice everest-forms-notice-info">%s</p>',
				wp_kses_post( $this->get_unavailable_notice_html() )
			);
		}

		echo '</div>';
	}

	/**
	 * Filter initial errors with Shield decision gate.
	 *
	 * @since 3.7.1
	 *
	 * @param array $errors    Current error bag.
	 * @param array $form_data Current form data.
	 * @return array
	 */
	public function filter_initial_errors( $errors, $form_data ) {
		if ( isset( $_POST['__amp_form_verify'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $errors;
		}

		if ( ! is_array( $form_data ) || ! isset( $form_data['id'] ) ) {
			return $errors;
		}

		$form_id = absint( $form_data['id'] );
		if ( ! $form_id ) {
			return $errors;
		}

		if ( isset( $errors[ $form_id ] ) && ! empty( $errors[ $form_id ] ) ) {
			return $errors;
		}

		if ( ! $this->is_global_shield_enabled() ) {
			return $errors;
		}

		if ( ! $this->is_form_shield_enabled( $form_data ) ) {
			return $errors;
		}

		$request_ip = evf_get_ip_address();
		$verdict    = $this->get_shield_bot_verdict_for_ip( $request_ip );

		if ( true !== $verdict ) {
			return $errors;
		}

		$errors[ $form_id ]['header'] = apply_filters(
			'everest_forms_process_form_error_header',
			__( 'Form has not been submitted, please see the errors below.', 'everest-forms' )
		);

		return $errors;
	}

	/**
	 * Detect whether Shield callable is available.
	 *
	 * @since 3.7.1
	 *
	 * @return bool
	 */
	public function is_shield_available() {
		if ( null !== $this->shield_available ) {
			return $this->shield_available;
		}

		$this->shield_available = false;

		foreach ( $this->get_shield_callables() as $callable ) {
			if ( is_callable( $callable ) ) {
				$this->shield_available = true;
				break;
			}
		}

		return $this->shield_available;
	}

	/**
	 * Resolve Shield verdict for a given request IP.
	 *
	 * @since 3.7.1
	 *
	 * @param string $request_ip Request IP.
	 * @return bool|null
	 */
	public function get_shield_bot_verdict_for_ip( $request_ip ) {
		foreach ( $this->get_shield_callables() as $callable ) {
			if ( ! is_callable( $callable ) ) {
				continue;
			}

			try {
				$verdict = call_user_func( $callable, $request_ip );
			} catch ( \Throwable $e ) {
				continue;
			}

			$normalized = $this->normalize_shield_verdict( $verdict );

			if ( null !== $normalized ) {
				return $normalized;
			}
		}

		return null;
	}

	/**
	 * Get Shield callable priority list.
	 *
	 * @since 3.7.1
	 *
	 * @return array
	 */
	protected function get_shield_callables() {
		return array(
			'\\FernleafSystems\\Wordpress\\Plugin\\Shield\\Functions\\test_ip_is_bot',
			'shield_test_ip_is_bot',
		);
	}

	/**
	 * Get Shield threshold callable priority list.
	 *
	 * @since 3.7.1
	 *
	 * @return array
	 */
	protected function get_shield_threshold_callables() {
		return array(
			'\\FernleafSystems\\Wordpress\\Plugin\\Shield\\Functions\\get_silentcaptcha_bot_threshold',
			'shield_get_silentcaptcha_bot_threshold',
		);
	}

	/**
	 * Build notice HTML with standard Shield help link.
	 *
	 * @since 3.7.1
	 *
	 * @param string $notice_text Notice text.
	 * @return string
	 */
	protected function get_notice_with_help_link_html( $notice_text ) {
		return sprintf(
			/* translators: 1: notice text, 2: Shield help URL. */
			__( '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">Learn More</a>.', 'everest-forms' ),
			esc_html( $notice_text ),
			esc_url( self::HELP_URL )
		);
	}

	/**
	 * Get lock attributes used when Shield is unavailable.
	 *
	 * @since 3.7.1
	 *
	 * @return array
	 */
	protected function get_lock_attributes() {
		return array(
			'disabled'                             => 'disabled',
			'data-shield-silent-captcha-lock'      => '1',
			'data-shield-silent-captcha-available' => '0',
		);
	}

	/**
	 * Get unavailable notice plain text.
	 *
	 * @since 3.7.1
	 *
	 * @return string
	 */
	protected function get_unavailable_notice_text() {
		return esc_html__( 'Shield Security bot detection is not currently detected, so this setting has no effect right now. Install and activate Shield Security to enable silentCAPTCHA bot checks.', 'everest-forms' );
	}

	/**
	 * Get unavailable notice HTML with help link.
	 *
	 * @since 3.7.1
	 *
	 * @return string
	 */
	protected function get_unavailable_notice_html() {
		return $this->get_notice_with_help_link_html( $this->get_unavailable_notice_text() );
	}

	/**
	 * Get threshold-zero notice plain text.
	 *
	 * @since 3.7.1
	 *
	 * @return string
	 */
	protected function get_threshold_zero_notice_text() {
		return esc_html__( 'Shield is active and running, but your Shield silentcaptcha bot threshold is set to zero.', 'everest-forms' );
	}

	/**
	 * Get threshold-zero notice HTML with help link.
	 *
	 * @since 3.7.1
	 *
	 * @return string
	 */
	protected function get_threshold_zero_notice_html() {
		return $this->get_notice_with_help_link_html( $this->get_threshold_zero_notice_text() );
	}

	/**
	 * Build Shield global toggle field.
	 *
	 * @since 3.7.1
	 *
	 * @param bool $is_available Availability state.
	 * @return array
	 */
	protected function get_global_toggle_field( $is_available ) {
		$field = array(
			'title'    => esc_html__( 'Enable Shield silentCAPTCHA', 'everest-forms' ),
			'type'     => 'toggle',
			'desc'     => '',
			'id'       => self::SETTING_KEY,
			'default'  => 'no',
			'desc_tip' => false,
		);

		return $field;
	}

	/**
	 * Get global toggle description.
	 *
	 * @since 3.7.1
	 *
	 * @param bool $is_available Availability state.
	 * @return string
	 */
	protected function get_global_toggle_desc( $is_available ) {
		if ( ! $is_available ) {
			return $this->get_unavailable_notice_html();
		}

		if ( $this->is_shield_silentcaptcha_threshold_zero() ) {
			return $this->get_threshold_zero_notice_html();
		}

		return sprintf(
			'%s<br/>%s %s',
			/* translators: %s - Shield help URL. */
			__( 'With Shield silentCAPTCHA Bot SPAM Protection, form submissions are blocked when requests are identified as coming from automated bots.', 'everest-forms' ),
			__( "silentCAPTCHA is 100% invisible to your site visitors and you'll need to install the Shield Security plugin to use this feature.", 'everest-forms' ),
			/* translators: %s: Shield help URL. */
			sprintf( __( '<a href="%s" target="_blank" rel="noopener noreferrer">Learn More</a>.', 'everest-forms' ), esc_url( self::HELP_URL ) )
		);
	}

	/**
	 * Determine whether Shield silentCAPTCHA threshold is explicitly set to zero.
	 *
	 * @since 3.7.1
	 *
	 * @return bool
	 */
	protected function is_shield_silentcaptcha_threshold_zero() {
		if ( null !== $this->shield_threshold_zero ) {
			return $this->shield_threshold_zero;
		}

		$this->shield_threshold_zero = false;

		if ( ! $this->is_shield_available() ) {
			return $this->shield_threshold_zero;
		}

		foreach ( $this->get_shield_threshold_callables() as $callable ) {
			if ( ! is_callable( $callable ) ) {
				continue;
			}

			try {
				$threshold = call_user_func( $callable );
			} catch ( \Throwable $e ) {
				continue;
			}

			if ( ! is_numeric( $threshold ) ) {
				continue;
			}

			$this->shield_threshold_zero = 0 === (int) $threshold;
			break;
		}

		return $this->shield_threshold_zero;
	}

	/**
	 * Get admin control config for script locking.
	 *
	 * @since 3.7.1
	 *
	 * @return array
	 */
	protected function get_admin_control_config() {
		return array(
			array(
				'shieldSilentCaptchaToggleSelector' => '#shield_silent_captcha',
				'shieldSilentCaptchaHiddenSelector' => '',
			),
			array(
				'shieldSilentCaptchaToggleSelector' => '#everest-forms-panel-field-settings-shield_silent_captcha',
				'shieldSilentCaptchaHiddenSelector' => 'input[type="hidden"][name="settings[shield_silent_captcha]"]',
			),
		);
	}

	/**
	 * Normalize Shield callable result into strict tri-state.
	 *
	 * @since 3.7.1
	 *
	 * @param mixed $verdict Raw callable result.
	 * @return bool|null
	 */
	protected function normalize_shield_verdict( $verdict ) {
		if ( true === $verdict ) {
			return true;
		}

		if ( false === $verdict ) {
			return false;
		}

		return null;
	}

	/**
	 * Check whether Shield is globally enabled.
	 *
	 * @since 3.7.1
	 *
	 * @return bool
	 */
	protected function is_global_shield_enabled() {
		return 'yes' === get_option( self::SETTING_KEY, 'no' );
	}

	/**
	 * Check whether Shield is enabled for the form.
	 *
	 * @since 3.7.1
	 *
	 * @param array $form_data Form data.
	 * @return bool
	 */
	protected function is_form_shield_enabled( $form_data ) {
		$setting_value = isset( $form_data['settings'][ self::SETTING_KEY ] ) ? $form_data['settings'][ self::SETTING_KEY ] : '0';

		return '1' === (string) $setting_value;
	}
}
