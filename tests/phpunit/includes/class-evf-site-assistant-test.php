<?php
/**
 * Tests for EVF_Site_Assistant REST API extensions.
 */
class EVF_Site_Assistant_Test extends WP_UnitTestCase {

	protected $assistant;

	public function setUp(): void {
		parent::setUp();
		require_once EVF_ABSPATH . 'includes/RestApi/controllers/version1/class-evf-site-assistant.php';
		$this->assistant = new EVF_Site_Assistant();
	}

	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'active_plugins' );
		delete_option( 'everest_forms_last_form_email_status' );
	}

	/** Smart SMTP not in active_plugins → is_smart_smtp_active returns false */
	public function test_is_smart_smtp_active_returns_false_when_not_active() {
		update_option( 'active_plugins', array() );
		$result = $this->call_protected( 'is_smart_smtp_active' );
		$this->assertFalse( $result );
	}

	/** Smart SMTP in active_plugins → is_smart_smtp_active returns true */
	public function test_is_smart_smtp_active_returns_true_when_active() {
		update_option( 'active_plugins', array( 'smart-smtp/smart-smtp.php' ) );
		$result = $this->call_protected( 'is_smart_smtp_active' );
		$this->assertTrue( $result );
	}

	/** GET response includes last_form_email_status from WP option */
	public function test_get_status_includes_last_form_email_status() {
		update_option( 'everest_forms_last_form_email_status', 'failed' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request  = new WP_REST_Request( 'GET', '/everest-forms/v1/site-assistant' );
		$response = $this->assistant->get_status( $request );
		$data     = $response->get_data();
		$this->assertSame( 'failed', $data['data']['last_form_email_status'] );
	}

	/** GET response includes is_smart_smtp_installed as boolean */
	public function test_get_status_includes_is_smart_smtp_installed() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request  = new WP_REST_Request( 'GET', '/everest-forms/v1/site-assistant' );
		$response = $this->assistant->get_status( $request );
		$data     = $response->get_data();
		$this->assertArrayHasKey( 'is_smart_smtp_installed', $data['data'] );
		$this->assertIsBool( $data['data']['is_smart_smtp_installed'] );
	}

	/** GET response includes is_smart_smtp_active as boolean */
	public function test_get_status_includes_is_smart_smtp_active() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request  = new WP_REST_Request( 'GET', '/everest-forms/v1/site-assistant' );
		$response = $this->assistant->get_status( $request );
		$data     = $response->get_data();
		$this->assertArrayHasKey( 'is_smart_smtp_active', $data['data'] );
		$this->assertIsBool( $data['data']['is_smart_smtp_active'] );
	}

	/** test-email endpoint returns HTTP 200 with email_sent=false when wp_mail returns false */
	public function test_send_test_email_returns_200_on_mail_failure() {
		add_filter( 'pre_wp_mail', '__return_false' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'POST', '/everest-forms/v1/site-assistant/test-email' );
		$request->set_param( 'email', 'test@example.com' );
		$response = $this->assistant->send_test_email( $request );
		remove_filter( 'pre_wp_mail', '__return_false' );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertFalse( $data['data']['email_sent'] );
	}

	/** test-email endpoint returns email_sent=true on success */
	public function test_send_test_email_returns_email_sent_true_on_success() {
		add_filter( 'pre_wp_mail', '__return_true' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'POST', '/everest-forms/v1/site-assistant/test-email' );
		$request->set_param( 'email', 'test@example.com' );
		$response = $this->assistant->send_test_email( $request );
		remove_filter( 'pre_wp_mail', '__return_true' );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertTrue( $data['data']['email_sent'] );
	}

	/** EVF_Emails::send() writes 'failed' to WP option when wp_mail returns false */
	public function test_emails_send_writes_failed_status_on_mail_failure() {
		add_filter( 'pre_wp_mail', '__return_false' );
		delete_option( 'everest_forms_last_form_email_status' );

		$emails            = new EVF_Emails();
		$emails->form_data = array(
			'id'       => 1,
			'settings' => array( 'email' => array() ),
		);
		$emails->fields   = array();
		$emails->entry_id = 0;
		@$emails->send( 'test@example.com', 'Subject', 'Body' );

		remove_filter( 'pre_wp_mail', '__return_false' );
		$this->assertSame( 'failed', get_option( 'everest_forms_last_form_email_status' ) );
	}

	/** EVF_Emails::send() writes 'success' to WP option when wp_mail returns true */
	public function test_emails_send_writes_success_status_on_mail_success() {
		add_filter( 'pre_wp_mail', '__return_true' );
		delete_option( 'everest_forms_last_form_email_status' );

		$emails            = new EVF_Emails();
		$emails->form_data = array(
			'id'       => 1,
			'settings' => array( 'email' => array() ),
		);
		$emails->fields   = array();
		$emails->entry_id = 0;
		@$emails->send( 'test@example.com', 'Subject', 'Body' );

		remove_filter( 'pre_wp_mail', '__return_true' );
		$this->assertSame( 'success', get_option( 'everest_forms_last_form_email_status' ) );
	}

	/** Helper to call protected methods */
	private function call_protected( string $method, array $args = [] ) {
		$ref = new ReflectionMethod( EVF_Site_Assistant::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( $this->assistant, $args );
	}
}
