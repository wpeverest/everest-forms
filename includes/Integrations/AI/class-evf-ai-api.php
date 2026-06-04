<?php
/**
 * EVF AI API — HTTP client for the ThemeGrill AI Cloud gateway.
 *
 * Gateway URL is read from:
 *   1. TG_AI_GATEWAY_URL constant (wp-config.php) — local dev override
 *   2. 'evf_ai_gateway_url' option — settable from admin (future)
 *   3. Hardcoded production URL as final fallback
 *
 * License pattern (follows WPForms): license key is sent inline with every
 * generate request. The gateway verifies with wpeverest.com and caches for
 * 1 week. No separate "activate" step needed.
 */

defined( 'ABSPATH' ) || exit;

class EVF_AI_API {

	const PRODUCTION_URL = 'https://ai.themegrill.com';
	const PRODUCT        = 'everest-forms';
	const TIMEOUT        = 90;

	/**
	 * Generate a form from a plain-text prompt.
	 * Sends the EVF Pro license key inline — gateway verifies + caches (1 week).
	 *
	 * @param string $prompt
	 * @return array|WP_Error  Decoded AI response on success.
	 */
	public static function generate_form( string $prompt ) {
		$token = EVF_AI_Registration::get_site_token();
		if ( ! $token ) {
			return new WP_Error( 'not_registered', __( 'AI features are not yet active on this site.', 'everest-forms' ) );
		}

		// Send license key if EVF Pro is active — gateway verifies inline (WPForms pattern).
		// If no license key, gateway treats site as free tier.
		$license_key = self::get_license_key();

		$response = self::request(
			'POST',
			'/ai/v1/generate',
			array(
				'prompt'      => $prompt,
				'license_key' => $license_key,
			),
			$token
		);

		error_log( print_r( $response, true ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['success'] ) || empty( $response['form'] ) ) {
			return new WP_Error( 'bad_response', __( 'Unexpected response from AI service.', 'everest-forms' ) );
		}

		return $response['form'];
	}

	/**
	 * Regenerate / refine an existing AI form from a follow-up prompt.
	 *
	 * NOTE: the gateway does not implement /ai/v1/update yet — this wires the call
	 * so it works the moment the Python endpoint ships. Until then it returns the
	 * gateway's error (surfaced to the user).
	 *
	 * @param string $prompt  Refinement / follow-up prompt (or the original to regenerate).
	 * @param int    $form_id The draft form being refined.
	 * @return array|WP_Error  Decoded AI form schema on success.
	 */
	public static function update_form( string $prompt, int $form_id = 0, string $refine_prompt = '' ) {
		$token = EVF_AI_Registration::get_site_token();
		if ( ! $token ) {
			return new WP_Error( 'not_registered', __( 'AI features are not yet active on this site.', 'everest-forms' ) );
		}

		$body = array(
			'prompt'        => $prompt,
			'refine_prompt' => $refine_prompt,
			'form_id'       => $form_id,
			'license_key'   => self::get_license_key(),
			'current_form'  => self::get_current_form_context( $form_id ),
		);

		$response = self::request( 'POST', '/ai/v1/update', $body, $token );

		// Auto-heal stale token — same pattern as generate_form
		if ( is_wp_error( $response ) && 'api_error' === $response->get_error_code()
			&& false !== strpos( $response->get_error_message(), 'Invalid token' ) ) {

			EVF_AI_Registration::clear_credentials();
			EVF_AI_Registration::register();
			$token    = EVF_AI_Registration::get_site_token();
			$response = self::request( 'POST', '/ai/v1/update', $body, $token );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['success'] ) || empty( $response['form'] ) ) {
			return new WP_Error( 'bad_response', __( 'Unexpected response from AI service.', 'everest-forms' ) );
		}

		return $response['form'];
	}

	/**
	 * Extract a lightweight form context for the AI (type + label only).
	 * Keeps extra token cost to ~100-200 tokens for a typical form.
	 *
	 * @param int $form_id
	 * @return array  { form_title, fields: [ { type, label } ] }
	 */
	private static function get_current_form_context( int $form_id ): array {
		if ( ! $form_id ) {
			return [];
		}

		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return [];
		}

		$data    = evf_decode( $post->post_content );
		$summary = [];

		foreach ( ( $data['form_fields'] ?? [] ) as $field ) {
			$type = $field['type'] ?? '';
			if ( in_array( $type, [ 'hidden', 'html', 'divider' ], true ) ) {
				continue;
			}
			$summary[] = [
				'type'  => $type,
				'label' => $field['label'] ?? '',
			];
		}

		return [
			'form_title' => $post->post_title,
			'fields'     => $summary,
		];
	}

	/**
	 * Register this site with the ThemeGrill AI Cloud gateway (free tier).
	 * Called once on plugin activation — silent, no admin action required.
	 *
	 * @return array|WP_Error  { site_token, tier, product }
	 */
	public static function register_site() {
		return self::request(
			'POST',
			'/ai/v1/register',
			array(
				'domain'      => self::get_domain(),
				'admin_email' => get_bloginfo( 'admin_email' ),
				'wp_version'  => get_bloginfo( 'version' ),
				'product'     => self::PRODUCT,
			)
		);
	}

	/**
	 * Get current usage stats for display in the builder UI.
	 *
	 * @return array|WP_Error
	 */
	public static function get_usage() {
		$token = EVF_AI_Registration::get_site_token();
		if ( ! $token ) {
			return new WP_Error( 'not_registered', '' );
		}
		return self::request( 'GET', '/ai/v1/usage', array(), $token );
	}

	// ── Core HTTP request ─────────────────────────────────────────────────────

	private static function request( string $method, string $path, array $body = array(), string $token = '' ) {
		$url     = rtrim( self::gateway_url(), '/' ) . $path;
		$headers = array( 'Content-Type' => 'application/json' );

		if ( $token ) {
			$headers['X-TG-Token'] = $token;
		}

		$args = array(
			'method'  => strtoupper( $method ),
			'headers' => $headers,
			'timeout' => self::TIMEOUT,
		);

		if ( ! empty( $body ) && 'GET' !== strtoupper( $method ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$wp_response = wp_remote_request( $url, $args );

		if ( is_wp_error( $wp_response ) ) {
			return new WP_Error(
				'request_failed',
				sprintf( __( 'Could not reach AI service: %s', 'everest-forms' ), $wp_response->get_error_message() )
			);
		}

		$status = wp_remote_retrieve_response_code( $wp_response );
		$body   = json_decode( wp_remote_retrieve_body( $wp_response ), true );

		if ( 429 === $status ) {
			$msg = is_array( $body ) && isset( $body['detail']['message'] )
				? $body['detail']['message']
				: __( 'Request limit reached. Please try again later.', 'everest-forms' );
			return new WP_Error( 'rate_limited', $msg );
		}

		if ( $status < 200 || $status >= 300 ) {
			$msg = is_array( $body ) ? ( $body['detail'] ?? $body['message'] ?? '' ) : '';
			return new WP_Error(
				'api_error',
				$msg ?: sprintf( __( 'AI service returned an error (%d).', 'everest-forms' ), $status )
			);
		}

		return $body;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	public static function gateway_url(): string {
		if ( defined( 'TG_AI_GATEWAY_URL' ) ) {
			return TG_AI_GATEWAY_URL;
		}
		return get_option( 'evf_ai_gateway_url', self::PRODUCTION_URL );
	}

	/**
	 * Get EVF Pro license key if the license is active — empty string otherwise.
	 * Gateway treats an empty key as free tier.
	 */
	private static function get_license_key(): string {
		if ( ! function_exists( 'evf_get_license_plan' ) || ! evf_get_license_plan() ) {
			return '';
		}
		return (string) get_option( 'everest-forms-pro_license_key', '' );
	}

	private static function get_domain(): string {
		return preg_replace( '(^https?://)', '', rtrim( home_url(), '/' ) );
	}
}
