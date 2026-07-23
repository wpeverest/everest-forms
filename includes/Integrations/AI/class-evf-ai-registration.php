<?php
/**
 * EVF AI Registration — manages site token lifecycle.
 *
 * Mirrors WPForms' LiteConnect pattern: the plugin silently registers with
 * the gateway on first use and stores a site_token in wp_options.
 * Pro users additionally activate their EVF license key to upgrade limits.
 *
 * Options used:
 *   evf_ai_credentials  { site_token, tier, registered_at }
 */

defined( 'ABSPATH' ) || exit;

class EVF_AI_Registration {

	const OPTION_KEY     = 'evf_ai_credentials';
	const LOCK_TRANSIENT = 'evf_ai_registration_lock';
	const VERIFY_PREFIX  = 'evf_ai_verify_';  // transient prefix for ownership tokens
	const VERIFY_TTL     = 300;               // 5 minutes

	/**
	 * Get the stored site token, or null if not yet registered.
	 */
	public static function get_site_token(): ?string {
		$creds = get_option( self::OPTION_KEY );
		return ! empty( $creds['site_token'] ) ? $creds['site_token'] : null;
	}

	/**
	 * Get the stored tier ("free" or "pro").
	 */
	public static function get_tier(): string {
		$creds = get_option( self::OPTION_KEY );
		return $creds['tier'] ?? 'free';
	}

	/**
	 * True if the site has been registered with the gateway.
	 */
	public static function is_registered(): bool {
		return (bool) self::get_site_token();
	}

	/**
	 * True if this is a local / development site where the AI features
	 * (Create with AI, in-builder AI Assistant) should be disabled.
	 *
	 * The gateway cannot verify domain ownership for non-public hosts, so the
	 * AI features can never work on local installs — we hide them entirely
	 * instead of letting the user hit a "not available" error.
	 *
	 * Detection: WP environment type "local" OR a non-public host
	 * (localhost / loopback IP / .local / .test / .localhost TLD).
	 *
	 * @return bool
	 */
	public static function is_local_site(): bool {
		$host = wp_parse_url( site_url(), PHP_URL_HOST );
		$host = $host ? strtolower( $host ) : '';

		// An explicit local-gateway override (see the themegrill-ai-cloud README's "Setup —
		// WordPress Plugin" instructions: `define('TG_AI_GATEWAY_URL', 'http://localhost:8000')`)
		// means the developer has deliberately pointed this install at a gateway running on
		// their own machine to test AI features end-to-end, so the heuristics below don't apply
		// — without this the documented local-dev setup could never work on ANY local WP
		// environment (Local by Flywheel, Valet, MAMP, …), since `WP_ENVIRONMENT_TYPE=local` and
		// `.local`/`.test` domains are exactly what those tools use by default.
		if ( defined( 'TG_AI_GATEWAY_URL' ) && self::is_loopback_url( TG_AI_GATEWAY_URL ) ) {
			$is_local = false;
		} else {
			$is_local = ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() )
				|| in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );

			if ( ! $is_local ) {
				foreach ( array( '.local', '.test', '.localhost' ) as $suffix ) {
					if ( '' !== $host && substr( $host, -strlen( $suffix ) ) === $suffix ) {
						$is_local = true;
						break;
					}
				}
			}
		}

		/**
		 * Filter whether the current site is treated as local for AI features — the final say
		 * over the heuristics above, e.g. to test the live gateway from a .local domain (set
		 * this to __return_false), or to block a host the heuristics miss.
		 *
		 * @param bool   $is_local Whether the built-in heuristics consider this site local.
		 * @param string $host     The detected site host.
		 */
		return (bool) apply_filters( 'everest_forms_ai_is_local_site', $is_local, $host );
	}

	/**
	 * Whether a URL's host is a loopback address (localhost / 127.0.0.1 / ::1) — i.e. a gateway
	 * that can only possibly be running on this same machine, never a real production endpoint.
	 *
	 * @param string $url URL to inspect.
	 * @return bool
	 */
	private static function is_loopback_url( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return in_array( strtolower( (string) $host ), array( 'localhost', '127.0.0.1', '::1' ), true );
	}

	/**
	 * Clear stored credentials and registration lock.
	 * Called automatically when the gateway returns "Invalid token" so the
	 * site re-registers transparently on the next request.
	 */
	public static function clear_credentials(): void {
		delete_option( self::OPTION_KEY );
		delete_transient( self::LOCK_TRANSIENT );
	}

	/**
	 * Register this site with the gateway (called silently on first AI use).
	 * Uses a transient lock to prevent concurrent registration attempts.
	 *
	 * @return bool  True on success.
	 */
	public static function register(): bool {
		// Prevent duplicate registration
		if ( self::is_registered() ) {
			return true;
		}

		// Transient lock — only one registration attempt per 60 seconds
		if ( get_transient( self::LOCK_TRANSIENT ) ) {
			return false;
		}
		set_transient( self::LOCK_TRANSIENT, true, 60 );

		// Generate a one-time ownership token the gateway will call back to verify.
		// The gateway hits /wp-json/everest-forms/v1/gateway-verify?token=... and we
		// confirm the transient exists — proving this WordPress install issued the request.
		$verify_token = wp_generate_password( 32, false );
		set_transient( self::VERIFY_PREFIX . $verify_token, 1, self::VERIFY_TTL );

		$response = EVF_AI_API::register_site( $verify_token );

		if ( is_wp_error( $response ) || empty( $response['site_token'] ) ) {
			delete_transient( self::VERIFY_PREFIX . $verify_token );
			return false;
		}

		update_option(
			self::OPTION_KEY,
			array(
				'site_token'    => sanitize_text_field( $response['site_token'] ),
				'tier'          => sanitize_key( $response['tier'] ?? 'free' ),
				'registered_at' => time(),
			)
		);

		delete_transient( self::LOCK_TRANSIENT );
		return true;
	}

	/**
	 * Activate pro tier using the EVF Pro license key.
	 * Called automatically when EVF Pro license is activated.
	 *
	 * @param string $license_key
	 * @return bool
	 */
	public static function activate_pro( string $license_key ): bool {
		$token = self::get_site_token();

		// Register first if needed
		if ( ! $token ) {
			if ( ! self::register() ) {
				return false;
			}
			$token = self::get_site_token();
		}

		$response = EVF_AI_API::activate_license( $token, $license_key );

		if ( is_wp_error( $response ) || empty( $response['tier'] ) ) {
			return false;
		}

		$creds         = get_option( self::OPTION_KEY, array() );
		$creds['tier'] = sanitize_key( $response['tier'] );
		$creds['plan'] = sanitize_key( $response['plan'] ?? '' );
		update_option( self::OPTION_KEY, $creds );

		return 'pro' === $creds['tier'];
	}

	/**
	 * Register the public REST endpoint the gateway calls to verify domain ownership.
	 * Hooked to rest_api_init from class-evf-ai-loader.php.
	 */
	public static function register_verify_endpoint(): void {
		register_rest_route(
			'everest-forms/v1',
			'/gateway-verify',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'handle_verify_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Respond to the gateway's ownership callback.
	 *
	 * The gateway sends the same verify_token we generated in register().
	 * We check the transient (one-time, 5-minute TTL) and delete it on success
	 * so the token cannot be reused.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return WP_REST_Response
	 */
	public static function handle_verify_request( WP_REST_Request $request ): WP_REST_Response {
		$token = sanitize_text_field( $request->get_param( 'token' ) );

		if ( ! $token ) {
			return new WP_REST_Response( array( 'valid' => false ), 400 );
		}

		$stored = get_transient( self::VERIFY_PREFIX . $token );

		if ( ! $stored ) {
			return new WP_REST_Response( array( 'valid' => false ), 403 );
		}

		// One-time use — delete immediately so the token cannot be replayed.
		delete_transient( self::VERIFY_PREFIX . $token );

		return new WP_REST_Response( array( 'valid' => true ), 200 );
	}
}
