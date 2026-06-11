<?php
/**
 * Tests for Shield silentCAPTCHA integration behavior.
 *
 * @since 3.7.1
 */

use EverestForms\Addons\ShieldSilentCaptcha\ShieldSilentCaptcha;

/**
 * Test double for Shield silentCAPTCHA integration.
 */
class EVF_Shield_Silent_Captcha_Test_Double extends ShieldSilentCaptcha {

	/**
	 * Sentinel value returned by threshold-zero notice branch.
	 */
	const THRESHOLD_NOTICE_SENTINEL = '__threshold_zero_notice__';

	/**
	 * Sentinel value returned by unavailable notice branch.
	 */
	const UNAVAILABLE_NOTICE_SENTINEL = '__unavailable_notice__';

	/**
	 * Callable list used by tests.
	 *
	 * @var array
	 */
	private $test_callables = array();

	/**
	 * Threshold callable list used by tests.
	 *
	 * @var array
	 */
	private $test_threshold_callables = array();

	/**
	 * Whether to replace notice HTML with sentinel output.
	 *
	 * @var bool
	 */
	private $use_notice_sentinels = false;

	/**
	 * Threshold notice call count.
	 *
	 * @var int
	 */
	private $threshold_notice_calls = 0;

	/**
	 * Unavailable notice call count.
	 *
	 * @var int
	 */
	private $unavailable_notice_calls = 0;

	/**
	 * Constructor.
	 *
	 * @param array $test_callables           Callable list.
	 * @param array $test_threshold_callables Threshold callable list.
	 */
	public function __construct( $test_callables = array(), $test_threshold_callables = array() ) {
		$this->test_callables           = $test_callables;
		$this->test_threshold_callables = $test_threshold_callables;
	}

	/**
	 * Override callable list for tests.
	 *
	 * @return array
	 */
	protected function get_shield_callables() {
		return $this->test_callables;
	}

	/**
	 * Override threshold callable list for tests.
	 *
	 * @return array
	 */
	protected function get_shield_threshold_callables() {
		return $this->test_threshold_callables;
	}

	/**
	 * Expose global toggle field for tests.
	 *
	 * @param bool $is_available Availability state.
	 * @return array
	 */
	public function get_global_toggle_field_for_test( $is_available ) {
		return $this->get_global_toggle_field( $is_available );
	}

	/**
	 * Expose global toggle description for tests.
	 *
	 * @param bool $is_available Availability state.
	 * @return string
	 */
	public function get_global_toggle_desc_for_test( $is_available ) {
		return $this->get_global_toggle_desc( $is_available );
	}

	/**
	 * Expose admin control config for tests.
	 *
	 * @return array
	 */
	public function get_admin_control_config_for_test() {
		return $this->get_admin_control_config();
	}

	/**
	 * Enable or disable sentinel mode for notice HTML.
	 *
	 * @param bool $is_enabled Enable sentinel mode.
	 */
	public function set_notice_sentinel_mode_for_test( $is_enabled ) {
		$this->use_notice_sentinels = (bool) $is_enabled;
	}

	/**
	 * Override threshold-zero notice output for branch assertions.
	 *
	 * @return string
	 */
	protected function get_threshold_zero_notice_html() {
		++$this->threshold_notice_calls;

		if ( $this->use_notice_sentinels ) {
			return self::THRESHOLD_NOTICE_SENTINEL;
		}

		return parent::get_threshold_zero_notice_html();
	}

	/**
	 * Override unavailable notice output for branch assertions.
	 *
	 * @return string
	 */
	protected function get_unavailable_notice_html() {
		++$this->unavailable_notice_calls;

		if ( $this->use_notice_sentinels ) {
			return self::UNAVAILABLE_NOTICE_SENTINEL;
		}

		return parent::get_unavailable_notice_html();
	}

	/**
	 * Get threshold notice call count.
	 *
	 * @return int
	 */
	public function get_threshold_notice_calls_for_test() {
		return $this->threshold_notice_calls;
	}

	/**
	 * Get unavailable notice call count.
	 *
	 * @return int
	 */
	public function get_unavailable_notice_calls_for_test() {
		return $this->unavailable_notice_calls;
	}
}

/**
 * Shield silentCAPTCHA tests.
 */
class Shield_Silent_Captcha_Tests extends WP_UnitTestCase {

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		delete_option( ShieldSilentCaptcha::SETTING_KEY );
		$_POST = array();
	}

	/**
	 * Teardown.
	 */
	public function tearDown() {
		delete_option( ShieldSilentCaptcha::SETTING_KEY );
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * Data provider for tri-state normalization.
	 *
	 * @return array
	 */
	public function shield_verdict_provider() {
		return array(
			'strict_true'     => array( true, true ),
			'strict_false'    => array( false, false ),
			'string_true'     => array( 'true', null ),
			'numeric_one'     => array( 1, null ),
			'array_unknown'   => array( array( 'bot' => true ), null ),
			'null_unknown'    => array( null, null ),
		);
	}

	/**
	 * Data provider for closed gate combinations.
	 *
	 * @return array
	 */
	public function closed_gate_provider() {
		return array(
			'global_off_form_on'  => array( 'no', '1' ),
			'global_on_form_off'  => array( 'yes', '0' ),
			'global_off_form_off' => array( 'no', '0' ),
		);
	}

	/**
	 * AC7: tri-state normalization should only accept strict booleans.
	 *
	 * @dataProvider shield_verdict_provider
	 *
	 * @param mixed    $raw_verdict      Callable return value.
	 * @param bool|null $expected_verdict Expected normalized verdict.
	 */
	public function test_tri_state_normalization_behavior( $raw_verdict, $expected_verdict ) {
		$shield = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () use ( $raw_verdict ) {
					return $raw_verdict;
				},
			)
		);

		$this->assertSame( $expected_verdict, $shield->get_shield_bot_verdict_for_ip( '127.0.0.1' ) );
	}

	/**
	 * Ensure callable order remains stable.
	 */
	public function test_callable_order_behavior() {
		$call_sequence = array();
		$shield        = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () use ( &$call_sequence ) {
					$call_sequence[] = 'first';
					return 'unknown';
				},
				function () use ( &$call_sequence ) {
					$call_sequence[] = 'second';
					return true;
				},
			)
		);

		$this->assertTrue( $shield->get_shield_bot_verdict_for_ip( '127.0.0.1' ) );
		$this->assertSame( array( 'first', 'second' ), $call_sequence );
	}

	/**
	 * AC1/AC2: callable absence should fail open.
	 */
	public function test_absent_shield_callable_fails_open_behavior() {
		$shield = new EVF_Shield_Silent_Captcha_Test_Double( array() );

		update_option( ShieldSilentCaptcha::SETTING_KEY, 'yes' );

		$form_data = array(
			'id'       => 9,
			'settings' => array(
				ShieldSilentCaptcha::SETTING_KEY => '1',
			),
		);

		$this->assertSame( array(), $shield->filter_initial_errors( array(), $form_data ) );
	}

	/**
	 * AC7: throwable from callable should fail open.
	 */
	public function test_throwable_fail_open_behavior() {
		$shield = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					throw new Exception( 'shield failure' );
				},
			)
		);

		$this->assertNull( $shield->get_shield_bot_verdict_for_ip( '127.0.0.1' ) );
	}

	/**
	 * AC3/AC4: closed gates should no-op and not call Shield verdict.
	 *
	 * @dataProvider closed_gate_provider
	 *
	 * @param string $global_option Global option value.
	 * @param string $form_option   Form option value.
	 */
	public function test_closed_gates_skip_shield_evaluation( $global_option, $form_option ) {
		$shield_called = 0;
		$shield        = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () use ( &$shield_called ) {
					++$shield_called;
					return true;
				},
			)
		);

		update_option( ShieldSilentCaptcha::SETTING_KEY, $global_option );

		$form_data = array(
			'id'       => 10,
			'settings' => array(
				ShieldSilentCaptcha::SETTING_KEY => $form_option,
			),
		);

		$this->assertSame( array(), $shield->filter_initial_errors( array(), $form_data ) );
		$this->assertSame( 0, $shield_called );
	}

	/**
	 * AC8: existing errors should bypass Shield evaluation.
	 */
	public function test_existing_error_short_circuits_before_shield_evaluation() {
		$shield_called = 0;
		$shield        = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () use ( &$shield_called ) {
					++$shield_called;
					return true;
				},
			)
		);
		update_option( ShieldSilentCaptcha::SETTING_KEY, 'yes' );

		$form_data = array(
			'id'       => 20,
			'settings' => array(
				ShieldSilentCaptcha::SETTING_KEY => '1',
			),
		);
		$errors    = array(
			20 => array(
				'header' => 'Existing header',
			),
		);

		$this->assertSame( $errors, $shield->filter_initial_errors( $errors, $form_data ) );
		$this->assertSame( 0, $shield_called );
	}

	/**
	 * AC8 proxy: existing CAPTCHA error should short-circuit before Shield callable.
	 */
	public function test_recaptcha_failure_short_circuits_before_shield_behavior_proxy() {
		$shield_called = 0;
		$shield        = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () use ( &$shield_called ) {
					++$shield_called;
					return true;
				},
			)
		);
		update_option( ShieldSilentCaptcha::SETTING_KEY, 'yes' );

		$form_data = array(
			'id'       => 25,
			'settings' => array(
				ShieldSilentCaptcha::SETTING_KEY => '1',
			),
		);
		$errors    = array(
			25 => array(
				'header' => 'CAPTCHA token missing. Please try again.',
			),
		);

		$this->assertSame( $errors, $shield->filter_initial_errors( $errors, $form_data ) );
		$this->assertSame( 0, $shield_called );
	}

	/**
	 * AC6: strict true verdict should block via standard header contract.
	 */
	public function test_strict_true_blocks_with_standard_header() {
		update_option( ShieldSilentCaptcha::SETTING_KEY, 'yes' );

		$form_data = array(
			'id'       => 30,
			'settings' => array(
				ShieldSilentCaptcha::SETTING_KEY => '1',
			),
		);
		$shield    = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					return true;
				},
			)
		);

		$errors = $shield->filter_initial_errors( array(), $form_data );
		$this->assertArrayHasKey( 30, $errors );
		$this->assertArrayHasKey( 'header', $errors[30] );
		$this->assertSame( $this->get_standard_form_error_header(), $errors[30]['header'] );
	}

	/**
	 * Ensure hook registration path behaves as expected.
	 */
	public function test_initial_errors_hook_integration_behavior() {
		update_option( ShieldSilentCaptcha::SETTING_KEY, 'yes' );

		$form_data = array(
			'id'       => 31,
			'settings' => array(
				ShieldSilentCaptcha::SETTING_KEY => '1',
			),
		);
		$shield    = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					return true;
				},
			)
		);

		add_filter( 'everest_forms_process_initial_errors', array( $shield, 'filter_initial_errors' ), 10, 2 );
		$errors = apply_filters( 'everest_forms_process_initial_errors', array(), $form_data );
		remove_filter( 'everest_forms_process_initial_errors', array( $shield, 'filter_initial_errors' ), 10 );

		$this->assertArrayHasKey( 31, $errors );
		$this->assertArrayHasKey( 'header', $errors[31] );
	}

	/**
	 * AC7: AMP verify pass should bypass Shield block logic.
	 */
	public function test_amp_verify_bypasses_shield_behavior() {
		$shield_called = 0;
		$shield        = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () use ( &$shield_called ) {
					++$shield_called;
					return true;
				},
			)
		);
		update_option( ShieldSilentCaptcha::SETTING_KEY, 'yes' );

		$form_data = array(
			'id'       => 40,
			'settings' => array(
				ShieldSilentCaptcha::SETTING_KEY => '1',
			),
		);

		$_POST['__amp_form_verify'] = '1';
		$this->assertSame( array(), $shield->filter_initial_errors( array(), $form_data ) );
		$this->assertSame( 0, $shield_called );
	}

	/**
	 * AC9: unavailable global toggle should remain interactive and persistable.
	 */
	public function test_unavailable_global_toggle_contract() {
		$shield = new EVF_Shield_Silent_Captcha_Test_Double();
		$field  = $shield->get_global_toggle_field_for_test( false );

		$this->assertArrayNotHasKey( 'is_option', $field );
		$this->assertArrayNotHasKey( 'custom_attributes', $field );
	}

	/**
	 * AC10: builder config should carry hidden-value preservation selector contract.
	 */
	public function test_builder_hidden_value_preservation_contract() {
		$shield  = new EVF_Shield_Silent_Captcha_Test_Double();
		$configs = $shield->get_admin_control_config_for_test();

		$this->assertTrue( $this->has_admin_control_config( $configs, '#shield_silent_captcha', '' ) );
		$this->assertTrue( $this->has_admin_control_config( $configs, '#everest-forms-panel-field-settings-shield_silent_captcha', 'input[type="hidden"][name="settings[shield_silent_captcha]"]' ) );
	}

	/**
	 * Threshold check should fail open when threshold callable is absent.
	 */
	public function test_global_toggle_desc_threshold_callable_absent_uses_standard_available_copy() {
		$shield = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					return false;
				},
			),
			array( 'missing_threshold_callable' )
		);
		$shield->set_notice_sentinel_mode_for_test( true );

		$desc = $shield->get_global_toggle_desc_for_test( true );
		$this->assertSame( 0, $shield->get_threshold_notice_calls_for_test() );
		$this->assertSame( 0, $shield->get_unavailable_notice_calls_for_test() );
		$this->assertTrue( is_string( $desc ) && '' !== $desc );
	}

	/**
	 * Threshold check should fail open when threshold callable throws.
	 */
	public function test_global_toggle_desc_threshold_callable_throwing_uses_standard_available_copy() {
		$threshold_called = 0;
		$shield           = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					return false;
				},
			),
			array(
				function () use ( &$threshold_called ) {
					++$threshold_called;
					throw new Exception( 'threshold failure' );
				},
			)
		);
		$shield->set_notice_sentinel_mode_for_test( true );

		$desc = $shield->get_global_toggle_desc_for_test( true );
		$this->assertSame( 1, $threshold_called );
		$this->assertSame( 0, $shield->get_threshold_notice_calls_for_test() );
		$this->assertSame( 0, $shield->get_unavailable_notice_calls_for_test() );
		$this->assertTrue( is_string( $desc ) && '' !== $desc );
	}

	/**
	 * Threshold check should show warning when value is zero.
	 */
	public function test_global_toggle_desc_threshold_zero_shows_warning_message() {
		$threshold_called = 0;
		$shield           = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					return false;
				},
			),
			array(
				function () use ( &$threshold_called ) {
					++$threshold_called;
					return 0;
				},
			)
		);
		$shield->set_notice_sentinel_mode_for_test( true );

		$desc = $shield->get_global_toggle_desc_for_test( true );
		$this->assertSame( 1, $threshold_called );
		$this->assertSame( 1, $shield->get_threshold_notice_calls_for_test() );
		$this->assertSame( 0, $shield->get_unavailable_notice_calls_for_test() );
		$this->assertTrue( is_string( $desc ) && '' !== $desc );
	}

	/**
	 * Threshold check should keep standard copy when value is non-zero.
	 */
	public function test_global_toggle_desc_threshold_non_zero_uses_standard_available_copy() {
		$threshold_called = 0;
		$shield           = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					return false;
				},
			),
			array(
				function () use ( &$threshold_called ) {
					++$threshold_called;
					return 1;
				},
			)
		);
		$shield->set_notice_sentinel_mode_for_test( true );

		$desc = $shield->get_global_toggle_desc_for_test( true );
		$this->assertSame( 1, $threshold_called );
		$this->assertSame( 0, $shield->get_threshold_notice_calls_for_test() );
		$this->assertSame( 0, $shield->get_unavailable_notice_calls_for_test() );
		$this->assertTrue( is_string( $desc ) && '' !== $desc );
	}

	/**
	 * Unavailable state should remain unchanged.
	 */
	public function test_global_toggle_desc_unavailable_state_remains_unavailable_notice() {
		$threshold_called = 0;
		$shield           = new EVF_Shield_Silent_Captcha_Test_Double(
			array(),
			array(
				function () use ( &$threshold_called ) {
					++$threshold_called;
					return 0;
				},
			)
		);
		$shield->set_notice_sentinel_mode_for_test( true );
		$desc = $shield->get_global_toggle_desc_for_test( false );

		$this->assertSame( 0, $threshold_called );
		$this->assertSame( 0, $shield->get_threshold_notice_calls_for_test() );
		$this->assertSame( 1, $shield->get_unavailable_notice_calls_for_test() );
		$this->assertTrue( is_string( $desc ) && '' !== $desc );
	}

	/**
	 * Threshold lookup should be cached to avoid repeated external calls.
	 */
	public function test_global_toggle_desc_threshold_lookup_cached_after_first_resolution() {
		$threshold_called = 0;
		$shield           = new EVF_Shield_Silent_Captcha_Test_Double(
			array(
				function () {
					return false;
				},
			),
			array(
				function () use ( &$threshold_called ) {
					++$threshold_called;
					return 0;
				},
			)
		);
		$shield->set_notice_sentinel_mode_for_test( true );

		$desc_1 = $shield->get_global_toggle_desc_for_test( true );
		$desc_2 = $shield->get_global_toggle_desc_for_test( true );

		$this->assertTrue( is_string( $desc_1 ) && '' !== $desc_1 );
		$this->assertTrue( is_string( $desc_2 ) && '' !== $desc_2 );
		$this->assertSame( $desc_1, $desc_2 );
		$this->assertSame( 1, $threshold_called );
		$this->assertSame( 0, $shield->get_unavailable_notice_calls_for_test() );
	}

	/**
	 * Get the expected standard form error header.
	 *
	 * @return string
	 */
	private function get_standard_form_error_header() {
		return apply_filters(
			'everest_forms_process_form_error_header',
			__( 'Form has not been submitted, please see the errors below.', 'everest-forms' )
		);
	}

	/**
	 * Check if the config list contains a selector pair.
	 *
	 * @param array  $configs         Config list.
	 * @param string $toggle_selector Toggle selector.
	 * @param string $hidden_selector Hidden input selector.
	 * @return bool
	 */
	private function has_admin_control_config( $configs, $toggle_selector, $hidden_selector ) {
		foreach ( (array) $configs as $config ) {
			if ( ! is_array( $config ) ) {
				continue;
			}

			$current_toggle = isset( $config['shieldSilentCaptchaToggleSelector'] ) ? $config['shieldSilentCaptchaToggleSelector'] : '';
			$current_hidden = isset( $config['shieldSilentCaptchaHiddenSelector'] ) ? $config['shieldSilentCaptchaHiddenSelector'] : '';

			if ( $toggle_selector === $current_toggle && $hidden_selector === $current_hidden ) {
				return true;
			}
		}

		return false;
	}
}
