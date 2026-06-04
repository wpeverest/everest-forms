<?php
/**
 * EVF AI AJAX — handles all wp_ajax_ actions for AI form generation.
 */

defined( 'ABSPATH' ) || exit;

class EVF_AI_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_evf_ai_generate_form',  [ $this, 'generate_form' ] );
		add_action( 'wp_ajax_evf_ai_get_usage',      [ $this, 'get_usage' ] );
		add_action( 'wp_ajax_evf_ai_activate_form',  [ $this, 'activate_form' ] );
	}

	/**
	 * Generate a form from a user prompt and return the new form's edit URL.
	 */
	public function generate_form() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to use this feature.', 'everest-forms' ) ], 403 );
		}

		$prompt = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );

		if ( empty( $prompt ) ) {
			wp_send_json_error( [ 'message' => __( 'Please describe the form you want to create.', 'everest-forms' ) ] );
		}

		if ( strlen( $prompt ) < 5 ) {
			wp_send_json_error( [ 'message' => __( 'Prompt is too short. Please provide more detail.', 'everest-forms' ) ] );
		}

		// Auto-register on first use
		if ( ! EVF_AI_Registration::is_registered() ) {
			EVF_AI_Registration::register();
		}

		if ( ! EVF_AI_Registration::is_registered() ) {
			wp_send_json_error( [
				'message' => __( 'AI features are not available on local or staging sites.', 'everest-forms' ),
				'code'    => 'not_registered',
			] );
		}

		// Call gateway
		$ai_response = EVF_AI_API::generate_form( $prompt );

		// "Invalid token" means the stored token is stale (gateway restarted, URL changed, etc.)
		// Auto-heal: clear credentials, re-register, and retry once — transparent to the user.
		if ( is_wp_error( $ai_response ) && 'api_error' === $ai_response->get_error_code()
			&& false !== strpos( $ai_response->get_error_message(), 'Invalid token' ) ) {

			EVF_AI_Registration::clear_credentials();
			EVF_AI_Registration::register();
			$ai_response = EVF_AI_API::generate_form( $prompt );
		}

		if ( is_wp_error( $ai_response ) ) {
			$code = $ai_response->get_error_code();
			wp_send_json_error( [
				'message' => $ai_response->get_error_message(),
				'code'    => $code,
			] );
		}

		// Build and insert the EVF form
		$form_id = EVF_AI_Form_Builder::create_form( $ai_response );

		if ( is_wp_error( $form_id ) ) {
			wp_send_json_error( [ 'message' => $form_id->get_error_message() ] );
		}

		wp_send_json_success( [
			'form_id'    => $form_id,
			'form_title' => get_the_title( $form_id ),
			'edit_url'   => admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $form_id ),
			'tier'       => EVF_AI_Registration::get_tier(),
			'fields'     => EVF_AI_Form_Builder::get_field_summary( $form_id ),
		] );
	}

	/**
	 * Publish a draft AI form — called when user clicks "Use This Form".
	 */
	public function activate_form() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( [], 403 );
		}

		$form_id = absint( $_POST['form_id'] ?? 0 );
		if ( ! $form_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid form ID.', 'everest-forms' ) ] );
		}

		$ok = EVF_AI_Form_Builder::activate_form( $form_id );
		if ( ! $ok ) {
			wp_send_json_error( [ 'message' => __( 'Could not activate form.', 'everest-forms' ) ] );
		}

		wp_send_json_success( [
			'form_id'  => $form_id,
			'edit_url' => admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $form_id ),
		] );
	}

	/**
	 * Return usage stats for display in the builder UI (requests used today, limit, etc.).
	 */
	public function get_usage() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( [], 403 );
		}

		$usage = EVF_AI_API::get_usage();

		if ( is_wp_error( $usage ) ) {
			wp_send_json_error( [ 'message' => $usage->get_error_message() ] );
		}

		wp_send_json_success( $usage );
	}
}
