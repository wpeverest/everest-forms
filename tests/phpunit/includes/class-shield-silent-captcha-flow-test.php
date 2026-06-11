<?php
/**
 * Flow tests for Shield silentCAPTCHA sequencing with EVF_Form_Task.
 *
 * @since 3.7.1
 */

/**
 * Test double for EVF_Form_Task.
 */
class EVF_Form_Task_Shield_Initial_Errors_Test_Double extends EVF_Form_Task {

	/**
	 * Disable constructor hooks for isolated tests.
	 */
	public function __construct() {}
}

/**
 * Shield silentCAPTCHA flow tests.
 */
class Shield_Silent_Captcha_Flow_Tests extends WP_UnitTestCase {

	/**
	 * Tracks the test-added initial errors callback for cleanup.
	 *
	 * @var callable|null
	 */
	private $initial_errors_callback = null;

	/**
	 * Tracks the test-added pre_http_request callback for cleanup.
	 *
	 * @var callable|null
	 */
	private $pre_http_request_callback = null;

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		$_POST = array();
	}

	/**
	 * Teardown.
	 */
	public function tearDown() {
		if ( null !== $this->initial_errors_callback ) {
			remove_filter( 'everest_forms_process_initial_errors', $this->initial_errors_callback, 10 );
			$this->initial_errors_callback = null;
		}

		if ( null !== $this->pre_http_request_callback ) {
			remove_filter( 'pre_http_request', $this->pre_http_request_callback, 10 );
			$this->pre_http_request_callback = null;
		}

		delete_option( 'everest_forms_recaptcha_type' );
		delete_option( 'everest_forms_recaptcha_v2_invisible' );
		delete_option( 'everest_forms_recaptcha_v2_site_key' );
		delete_option( 'everest_forms_recaptcha_v2_secret_key' );
		delete_option( 'shield_silent_captcha' );

		$_POST = array();

		parent::tearDown();
	}

	/**
	 * Ensure reCAPTCHA failure short-circuits before initial-errors Shield stage.
	 */
	public function test_recaptcha_failure_short_circuits_before_shield_initial_errors_filter() {
		$form_id             = $this->create_test_form();
		$task                = new EVF_Form_Task_Shield_Initial_Errors_Test_Double();
		$initial_errors_hits = 0;

		$this->configure_recaptcha_settings();

		$this->initial_errors_callback = function ( $errors, $form_data ) use ( &$initial_errors_hits ) {
			++$initial_errors_hits;
			return $errors;
		};

		add_filter(
			'everest_forms_process_initial_errors',
			$this->initial_errors_callback,
			10,
			2
		);

		$_POST[ '_wpnonce' . $form_id ] = wp_create_nonce( 'everest-forms_process_submit' );

		$result = $task->do_task(
			array(
				'id'          => $form_id,
				'form_fields' => array(
					'field_1' => 'John Doe',
				),
			)
		);

		$this->assertSame( 0, $initial_errors_hits );
		$this->assertSame( $this->get_captcha_token_missing_message(), $result[ $form_id ]['header'] );
	}

	/**
	 * Ensure reCAPTCHA success reaches initial-errors stage where Shield can block.
	 */
	public function test_recaptcha_success_then_initial_errors_filter_can_block() {
		$form_id             = $this->create_test_form();
		$task                = new EVF_Form_Task_Shield_Initial_Errors_Test_Double();
		$initial_errors_hits = 0;

		$this->configure_recaptcha_settings();

		$this->pre_http_request_callback = function ( $preempt, $parsed_args, $url ) {
			if ( false === strpos( $url, 'google.com/recaptcha/api/siteverify' ) ) {
				return $preempt;
			}

			return array(
				'headers'  => array(),
				'body'     => wp_json_encode( array( 'success' => true ) ),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter(
			'pre_http_request',
			$this->pre_http_request_callback,
			10,
			3
		);

		$this->initial_errors_callback = function ( $errors, $form_data ) use ( &$initial_errors_hits ) {
			++$initial_errors_hits;

			if ( ! isset( $form_data['id'] ) ) {
				return $errors;
			}

			$form_id = absint( $form_data['id'] );
			if ( ! $form_id ) {
				return $errors;
			}

			$errors[ $form_id ]['header'] = $this->get_standard_form_error_header();
			return $errors;
		};

		add_filter(
			'everest_forms_process_initial_errors',
			$this->initial_errors_callback,
			10,
			2
		);

		$_POST[ '_wpnonce' . $form_id ]   = wp_create_nonce( 'everest-forms_process_submit' );
		$_POST['g-recaptcha-response'] = 'valid-token';

		$result = $task->do_task(
			array(
				'id'          => $form_id,
				'form_fields' => array(
					'field_1' => 'John Doe',
				),
			)
		);

		$this->assertSame( 1, $initial_errors_hits );
		$this->assertSame( $this->get_standard_form_error_header(), $result[ $form_id ]['header'] );
	}

	/**
	 * Create a minimal published form for process flow tests.
	 *
	 * @return int
	 */
	private function create_test_form() {
		$form_id = self::factory()->post->create(
			array(
				'post_type'   => 'everest_form',
				'post_status' => 'publish',
				'post_title'  => 'Shield Flow Test Form',
			)
		);

		$form_data = array(
			'id'            => $form_id,
			'form_field_id' => '1',
			'form_fields'   => array(
				'field_1' => array(
					'id'       => 'field_1',
					'type'     => 'text',
					'label'    => 'Name',
					'meta-key' => 'name',
				),
			),
			'settings'      => array(
				'form_title'                 => 'Shield Flow Test Form',
				'recaptcha_support'          => '1',
				'honeypot'                   => '0',
				'ajax_form_submission'       => '0',
			),
		);

		wp_update_post(
			array(
				'ID'           => $form_id,
				'post_content' => evf_encode( $form_data ),
			)
		);

		return $form_id;
	}

	/**
	 * Configure minimal reCAPTCHA v2 options.
	 */
	private function configure_recaptcha_settings() {
		update_option( 'everest_forms_recaptcha_type', 'v2' );
		update_option( 'everest_forms_recaptcha_v2_invisible', 'no' );
		update_option( 'everest_forms_recaptcha_v2_site_key', 'site-key' );
		update_option( 'everest_forms_recaptcha_v2_secret_key', 'secret-key' );
	}

	/**
	 * Get the expected CAPTCHA-missing message.
	 *
	 * @return string
	 */
	private function get_captcha_token_missing_message() {
		return esc_html__( 'CAPTCHA token missing. Please try again.', 'everest-forms' );
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
}
