<?php
/**
 * Site Assistant API Controller.
 *
 * REST API endpoints for managing site assistant setup and configuration.
 *
 * @package EverestForms
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Site_Assistant Class.
 */
class EVF_Site_Assistant {

	protected $namespace = 'everest-forms/v1';
	protected $rest_base = 'site-assistant';

	const TEST_EMAIL_SENT         = 'everest_forms_test_email_sent';
	const SPAM_PROTECTION_SKIPPED = 'everest_forms_spam_protection_skipped';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Ensure the test-email option exists and defaults to false on first run.
		$initial_test_email = get_option( self::TEST_EMAIL_SENT, null );
		if ( null === $initial_test_email ) {
			add_option( self::TEST_EMAIL_SENT, false );
		}

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/skip-setup',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'skip_setup' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'step' => array(
							'required'          => false,
							'type'              => 'string',
							'default'           => 'all',
							'description'       => 'Step to skip, e.g. "spam_protection" or "all".',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/test-email',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_test_email' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'email' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => 'Email address to send test email to.',
							'sanitize_callback' => 'sanitize_email',
							'validate_callback' => 'is_email',
						),
					),
				),
			)
		);
	}

	/**
	 * Ensure admin permissions.
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return bool|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function ensure_admin_permissions( $request ) {
		return $this->check_admin_permissions( $request );
	}

	/**
	 * Get setup status.
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_status( $request ) {
		$perm = $this->ensure_admin_permissions( $request );
		if ( is_wp_error( $perm ) ) {
			return $perm;
		}

		$skipped_steps = array();
		if ( get_option( self::SPAM_PROTECTION_SKIPPED, false ) ) {
			$skipped_steps[] = 'spam_protection';
		}

		// Get test_email_sent status (defaults to false)
		$test_email_sent = (bool) get_option( self::TEST_EMAIL_SENT, false );

		$response_data = array(
			'skipped_steps'   => $skipped_steps,
			'test_email_sent' => $test_email_sent,
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $response_data,
			)
		);
	}

	/**
	 * Skip setup steps.
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function skip_setup( $request ) {
		$perm = $this->ensure_admin_permissions( $request );
		if ( is_wp_error( $perm ) ) {
			return $perm;
		}

		$step = $request->get_param( 'step' );
		$step = is_string( $step ) ? sanitize_text_field( $step ) : 'all';

		$skipped = array();

		if ( 'all' === $step ) {
			update_option( self::SPAM_PROTECTION_SKIPPED, true );
			$skipped[] = 'spam_protection';
		} elseif ( 'spam_protection' === $step ) {
			update_option( self::SPAM_PROTECTION_SKIPPED, true );
			$skipped[] = 'spam_protection';
		} else {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'Invalid step provided.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		do_action( 'everest_forms_setup_skipped', $step );

		// Get updated status to match get_status response structure
		$skipped_steps = array();
		if ( get_option( self::SPAM_PROTECTION_SKIPPED, false ) ) {
			$skipped_steps[] = 'spam_protection';
		}

		$test_email_sent = (bool) get_option( self::TEST_EMAIL_SENT, false );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => sprintf(
					__( 'Setup steps skipped: %s', 'everest-forms' ),
					implode( ',', $skipped )
				),
				'data'    => array(
					'skipped_steps'   => $skipped_steps,
					'test_email_sent' => $test_email_sent,
				),
			)
		);
	}

	/**
	 * Send test email.
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function send_test_email( $request ) {
		$perm = $this->ensure_admin_permissions( $request );
		if ( is_wp_error( $perm ) ) {
			return $perm;
		}

		$email = $request->get_param( 'email' );

		if ( ! is_email( $email ) ) {
			return new \WP_Error(
				'invalid_email',
				__( 'Invalid email address provided.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		$email_sent = $this->process_test_email( $email );

		if ( $email_sent ) {
			update_option( self::TEST_EMAIL_SENT, true );
			do_action( 'everest_forms_test_email_sent', $email );

			$skipped_steps = array();
			if ( get_option( self::SPAM_PROTECTION_SKIPPED, false ) ) {
				$skipped_steps[] = 'spam_protection';
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'Test email sent successfully.', 'everest-forms' ),
					'data'    => array(
						'test_email_sent' => true,
						'skipped_steps'   => $skipped_steps,
					),
				)
			);
		} else {
			return new \WP_Error(
				'email_send_failed',
				__( 'Failed to send test email. Please check your email configuration.', 'everest-forms' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Process and send the test email.
	 *
	 * @param string $email Email address to send to.
	 * @return bool True if email sent successfully, false otherwise.
	 */
	protected function process_test_email( $email ) {
		$from    = esc_attr( get_bloginfo( 'name', 'display' ) );
		$to      = $email;
		$subject = 'Everest Form: ' . sprintf( esc_html__( 'Test email from %s', 'everest-forms' ), $from );
		$header  = "Reply-To: {{from}} \r\n";
		$header .= 'Content-Type: text/html; charset=UTF-8';
		$message = sprintf(
			'%s <br /> %s <br /> %s <br /> %s <br /> %s',
			__( 'Congratulations,', 'everest-forms' ),
			__( 'Your test email has been received successfully.', 'everest-forms' ),
			__( 'We thank you for trying out Everest Forms and joining our mission to make sure you get your emails delivered.', 'everest-forms' ),
			__( 'Regards,', 'everest-forms' ),
			__( 'Everest Forms Team', 'everest-forms' )
		);

		return wp_mail( $to, $subject, $message, $header );
	}

	/**
	 * Check admin permissions.
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return bool|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function check_admin_permissions( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access this resource.', 'everest-forms' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
