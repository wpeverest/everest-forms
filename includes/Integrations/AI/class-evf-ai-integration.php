<?php
/**
 * EVF AI Integration — main loader. Boots all AI classes and hooks into EVF.
 *
 * Loaded via EVF's existing integration system.
 * Add to your wp-config.php for local testing:
 *   define( 'EVF_AI_GATEWAY_URL', 'http://localhost:8000' );
 *   define( 'EVF_AI_FORCE_REGISTER', true );
 */

defined( 'ABSPATH' ) || exit;

class EVF_AI_Integration {

	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	private function includes() {
		$dir = __DIR__;
		require_once $dir . '/class-evf-ai-registration.php';
		require_once $dir . '/class-evf-ai-api.php';
		require_once $dir . '/class-evf-ai-form-builder.php';
		require_once $dir . '/class-evf-ai-ajax.php';
	}

	private function hooks() {
		// Boot AJAX handlers
		new EVF_AI_Ajax();

		// Enqueue builder script + pass nonce/config to JS
		add_action( 'evf_builder_enqueue_scripts', [ $this, 'enqueue_builder_scripts' ] );

		// License is now verified inline per generate request (WPForms pattern).
		// No separate activation hook needed.
	}

	public function enqueue_builder_scripts() {
		$plugin_url = EVF()->plugin_url();

		wp_enqueue_script(
			'evf-ai-builder',
			$plugin_url . '/assets/js/admin/evf-ai-builder.js',
			[ 'jquery', 'wp-util' ],
			EVF_VERSION,
			true
		);

		wp_localize_script( 'evf-ai-builder', 'evfAI', [
			'nonce'        => wp_create_nonce( 'evf_ai_nonce' ),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'isRegistered' => EVF_AI_Registration::is_registered(),
			'tier'         => EVF_AI_Registration::get_tier(),
			'i18n'         => [
				'placeholder'   => __( 'Describe the form you want to create…', 'everest-forms' ),
				'generating'    => __( 'Generating your form…', 'everest-forms' ),
				'error_generic' => __( 'Something went wrong. Please try again.', 'everest-forms' ),
				'limit_reached' => __( 'You have used all your free requests for today. Upgrade to EVF Pro for unlimited access.', 'everest-forms' ),
				'not_available' => __( 'AI features are not available on local or staging sites.', 'everest-forms' ),
			],
		] );
	}

}
