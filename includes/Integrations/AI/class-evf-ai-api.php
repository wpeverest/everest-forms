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

		$logger = evf_get_logger();
		$logger->info( sprintf( 'AI Form Generation started | prompt: %s', $prompt ), array( 'source' => 'evf-ai' ) );

		// Send license key if EVF Pro is active — gateway verifies inline (WPForms pattern).
		// If no license key, gateway treats site as free tier.
		$license_key = self::get_license_key();

		$response = self::request(
			'POST',
			'/ai/v1/generate',
			array(
				'prompt'           => $prompt,
				'license_key'      => $license_key,
				'available_fields' => implode( ',', evf()->form_fields->get_form_field_types() ),
			),
			$token
		);

		if ( is_wp_error( $response ) ) {
			$logger->error( sprintf( 'AI Form Generation failed | %s: %s', $response->get_error_code(), $response->get_error_message() ), array( 'source' => 'evf-ai' ) );
			return $response;
		}

		if ( empty( $response['success'] ) || empty( $response['form'] ) ) {
			$logger->error( 'AI Form Generation bad_response | missing success or form key', array( 'source' => 'evf-ai' ) );
			return new WP_Error( 'bad_response', __( 'Unexpected response from AI service.', 'everest-forms' ) );
		}

		$logger->info(
			sprintf( 'AI Form Generation succeeded | form_type: %s, fields: %d', $response['form']['form_type'] ?? 'standard', count( $response['form']['fields'] ?? array() ) ),
			array( 'source' => 'evf-ai' )
		);

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

		$logger = evf_get_logger();
		$logger->info(
			sprintf( 'AI Form Update started | form_id: %d, prompt: %s, refine_prompt: %s', $form_id, $prompt, $refine_prompt ),
			array( 'source' => 'evf-ai' )
		);

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

			$logger->warning( 'AI Form Update stale token — re-registering and retrying', array( 'source' => 'evf-ai' ) );
			EVF_AI_Registration::clear_credentials();
			EVF_AI_Registration::register();
			$token    = EVF_AI_Registration::get_site_token();
			$response = self::request( 'POST', '/ai/v1/update', $body, $token );
		}

		if ( is_wp_error( $response ) ) {
			$logger->error( sprintf( 'AI Form Update failed | %s: %s', $response->get_error_code(), $response->get_error_message() ), array( 'source' => 'evf-ai' ) );
			return $response;
		}

		if ( empty( $response['success'] ) || empty( $response['form'] ) ) {
			$logger->error( 'AI Form Update bad_response | missing success or form key', array( 'source' => 'evf-ai' ) );
			return new WP_Error( 'bad_response', __( 'Unexpected response from AI service.', 'everest-forms' ) );
		}

		$logger->info(
			sprintf( 'AI Form Update succeeded | form_id: %d, form_type: %s, fields: %d', $form_id, $response['form']['form_type'] ?? 'standard', count( $response['form']['fields'] ?? array() ) ),
			array( 'source' => 'evf-ai' )
		);

		return $response['form'];
	}

	/**
	 * Extract a lightweight form context for the AI.
	 * Includes type, label, and any non-default field settings so that subsequent
	 * AI requests preserve changes made by earlier ones (e.g. label_hide, required).
	 *
	 * @param int $form_id
	 * @return array  { form_title, fields: [ { type, label, ...settings } ] }
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

			$entry = [
				'type'  => $type,
				'label' => $field['label'] ?? '',
			];

			// Include non-default field settings so the AI can preserve them on
			// subsequent requests without the user having to repeat their instructions.
			if ( ! empty( $field['label_hide'] ) && '1' === $field['label_hide'] ) {
				$entry['label_hide'] = true;
			}
			if ( ! empty( $field['required'] ) && '1' === $field['required'] ) {
				$entry['required'] = true;
			}
			if ( ! empty( $field['description'] ) ) {
				$entry['description'] = $field['description'];
			}
			if ( ! empty( $field['placeholder'] ) ) {
				$entry['placeholder'] = $field['placeholder'];
			}
			if ( ! empty( $field['sublabel_hide'] ) && '1' === $field['sublabel_hide'] ) {
				$entry['sublabel_hide'] = true;
			}
			if ( ! empty( $field['css'] ) ) {
				$entry['css'] = $field['css'];
			}

			$summary[] = $entry;
		}

		// Detect form type so the gateway can preserve it during refine/regenerate
		$form_type = 'standard';
		if ( ! empty( $data['settings']['enable_multi_part'] ) && '1' === $data['settings']['enable_multi_part'] ) {
			$form_type = 'multipart';
		} elseif ( ! empty( $data['settings']['enable_conversational_forms'] ) && '1' === $data['settings']['enable_conversational_forms'] ) {
			$form_type = 'conversational';
		}

		// Include multipart step titles so AI can preserve/extend them
		$multipart_steps = [];
		if ( 'multipart' === $form_type ) {
			foreach ( ( $data['multi_part'] ?? [] ) as $part ) {
				$multipart_steps[] = [
					'title'       => $part['name'] ?? '',
					'field_count' => count( $part['fields'] ?? [] ),
				];
			}
		}

		$email_conns = $data['settings']['email'] ?? [];
		$conn1       = $email_conns['connection_1'] ?? [];

		return [
			'form_title'              => $post->post_title,
			'form_type'               => $form_type,
			'multipart_steps'         => $multipart_steps,
			'fields'                  => $summary,
			// Settings context so the gateway preserves them on refine
			'redirect_to'             => $data['settings']['redirect_to'] ?? 'same',
			'redirect_custom_page_id' => absint( $data['settings']['custom_page'] ?? 0 ),
			'redirect_external_url'   => $data['settings']['external_url'] ?? '',
			'notification'            => [
				'from_name' => $conn1['evf_from_name'] ?? '',
				'reply_to'  => $conn1['evf_reply_to'] ?? 'auto',
				'message'   => $conn1['evf_email_message'] ?? '{all_fields}',
				'subject'   => $conn1['evf_email_subject'] ?? '',
			],
			'user_confirmation'       => ! empty( $email_conns['connection_2'] ) ? [
				'from_name' => $email_conns['connection_2']['evf_from_name'] ?? '',
				'reply_to'  => $email_conns['connection_2']['evf_reply_to'] ?? 'auto',
			] : [],
		];
	}

	/**
	 * Register this site with the ThemeGrill AI Cloud gateway (free tier).
	 * Called once on plugin activation — silent, no admin action required.
	 *
	 * @param string $verify_token One-time ownership token; gateway calls back to confirm.
	 * @return array|WP_Error  { site_token, tier, product }
	 */
	public static function register_site( string $verify_token = '' ) {
		$payload = array(
			'domain'      => self::get_domain(),
			'admin_email' => get_bloginfo( 'admin_email' ),
			'wp_version'  => get_bloginfo( 'version' ),
			'product'     => self::PRODUCT,
		);

		if ( $verify_token ) {
			$payload['verify_token'] = $verify_token;
		}

		return self::request( 'POST', '/ai/v1/register', $payload );
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

		// Log outgoing request (license_key redacted).
		$log_body = $body;
		if ( isset( $log_body['license_key'] ) ) {
			$log_body['license_key'] = $log_body['license_key'] ? '[redacted]' : '';
		}
		$logger = evf_get_logger();
		$logger->debug(
			sprintf( "AI Request: %s %s\n%s", strtoupper( $method ), $path, wp_json_encode( $log_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
			array( 'source' => 'evf-ai' )
		);

		$wp_response = wp_remote_request( $url, $args );

		if ( is_wp_error( $wp_response ) ) {
			$logger->error(
				sprintf( 'AI Request failed: %s', $wp_response->get_error_message() ),
				array( 'source' => 'evf-ai' )
			);
			return new WP_Error(
				'request_failed',
				sprintf( __( 'Could not reach AI service: %s', 'everest-forms' ), $wp_response->get_error_message() )
			);
		}

		$status = wp_remote_retrieve_response_code( $wp_response );
		$body   = json_decode( wp_remote_retrieve_body( $wp_response ), true );

		// Log the raw response.
		$logger->debug(
			sprintf( "AI Response: HTTP %d\n%s", $status, wp_json_encode( $body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
			array( 'source' => 'evf-ai' )
		);

		if ( 429 === $status ) {
			$msg  = is_array( $body ) && isset( $body['detail']['message'] )
				? $body['detail']['message']
				: __( 'Request limit reached. Please try again later.', 'everest-forms' );
			$code = is_array( $body ) && isset( $body['detail']['error'] )
				? $body['detail']['error']
				: 'rate_limited';
			return new WP_Error( $code, $msg );
		}

		if ( $status < 200 || $status >= 300 ) {
			$detail = is_array( $body ) ? ( $body['detail'] ?? $body['message'] ?? '' ) : '';
			// FastAPI 400s send detail as an object: {"error": "...", "message": "..."}.
			$msg  = is_array( $detail ) ? ( $detail['message'] ?? $detail['error'] ?? '' ) : $detail;
			$code = ( is_array( $detail ) && ! empty( $detail['error'] ) ) ? $detail['error'] : 'api_error';
			return new WP_Error(
				$code,
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
