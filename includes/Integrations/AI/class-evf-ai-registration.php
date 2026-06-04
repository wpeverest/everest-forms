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

	const OPTION_KEY    = 'evf_ai_credentials';
	const LOCK_TRANSIENT = 'evf_ai_registration_lock';

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

		$response = EVF_AI_API::register_site();

		if ( is_wp_error( $response ) || empty( $response['site_token'] ) ) {
			return false;
		}

		update_option( self::OPTION_KEY, [
			'site_token'    => sanitize_text_field( $response['site_token'] ),
			'tier'          => sanitize_key( $response['tier'] ?? 'free' ),
			'registered_at' => time(),
		] );

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

		$creds         = get_option( self::OPTION_KEY, [] );
		$creds['tier'] = sanitize_key( $response['tier'] );
		$creds['plan'] = sanitize_key( $response['plan'] ?? '' );
		update_option( self::OPTION_KEY, $creds );

		return 'pro' === $creds['tier'];
	}

	/**
	 * Environment check — mirrors wp_get_environment_type() check in WPForms.
	 * Blocks registration on local/staging to prevent spam registrations.
	 */
	public static function is_production(): bool {
		// Allow override for local testing
		if ( defined( 'EVF_AI_FORCE_REGISTER' ) && EVF_AI_FORCE_REGISTER ) {
			return true;
		}
		return 'production' === wp_get_environment_type();
	}
}
