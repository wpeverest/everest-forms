<?php
/**
 * License management module for ThemeGrill SDK.
 *
 * Handles activate / deactivate / check against api.themegrill.com.
 * Requires the product file to declare:
 *   Requires License: yes
 *   TG Item ID: 123
 *
 * @package     ThemeGrillSDK
 * @subpackage  Modules
 * @copyright   Copyright (c) 2025, ThemeGrill
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.1
 */

namespace ThemeGrillSDK\Modules;

use ThemeGrillSDK\Common\AbstractModule;
use ThemeGrillSDK\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Licenser module for ThemeGrill SDK.
 */
class Licenser extends AbstractModule {

	/**
	 * Licensing API base path.
	 */
	const LICENSES_PATH = 'licenses/';

	/**
	 * How long to cache a license check (seconds).
	 */
	const CACHE_TTL = 43200; // 12 hours

	/**
	 * Check if the module should load for this product.
	 *
	 * @param Product $product Product to check.
	 *
	 * @return bool
	 */
	public function can_load( $product ) {
		return $product->requires_license() && $product->get_item_id() > 0;
	}

	/**
	 * Bootstrap the module.
	 *
	 * @param Product $product Product to load.
	 *
	 * @return Licenser
	 */
	public function load( $product ) {
		$this->product = $product;

		// Feed stored status into Logger's license_status filter.
		add_filter( $product->get_key() . '_license_status', array( $this, 'get_stored_status' ) );

		// Periodic background check.
		$cron_key = $product->get_key() . '_license_check';
		if ( ! wp_next_scheduled( $cron_key ) ) {
			wp_schedule_event( time(), 'daily', $cron_key );
		}
		add_action( $cron_key, array( $this, 'background_check' ) );

		// Clean up cron on deactivation.
		if ( $product->is_plugin() ) {
			register_deactivation_hook( $product->get_basefile(), array( $this, 'clear_cron' ) );
		} elseif ( $product->is_theme() ) {
			add_action( 'switch_theme', array( $this, 'clear_cron' ) );
		}

		return $this;
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Activate a license key for the current site.
	 *
	 * @param string $license_key The license key to activate.
	 * @param string $site_url    The site URL to activate against.
	 *
	 * @return array Response from the API.
	 */
	public function activate( $license_key, $site_url = '' ) {
		if ( '' === $site_url ) {
			$site_url = home_url();
		}

		$response = $this->request(
			'activate',
			array(
				'license_key' => $license_key,
				'item_id'     => $this->product->get_item_id(),
				'site_url'    => $site_url,
			)
		);

		if ( ! empty( $response['success'] ) ) {
			update_option( $this->product->get_key() . '_license', $license_key );
			$this->store_status( $response['license'] ?? 'active', $response );
		}

		return $response;
	}

	/**
	 * Deactivate a license key for the current site.
	 *
	 * @param string $license_key The license key to deactivate.
	 * @param string $site_url    The site URL to deactivate.
	 *
	 * @return array Response from the API.
	 */
	public function deactivate( $license_key, $site_url = '' ) {
		if ( '' === $site_url ) {
			$site_url = home_url();
		}

		$response = $this->request(
			'deactivate',
			array(
				'license_key' => $license_key,
				'item_id'     => $this->product->get_item_id(),
				'site_url'    => $site_url,
			)
		);

		if ( ! empty( $response['success'] ) ) {
			$this->store_status( 'inactive', $response );
		}

		return $response;
	}

	/**
	 * Check license validity. Caches result for 12 hours.
	 *
	 * @param string $license_key The license key to check.
	 * @param string $site_url    Optional site URL for site-active check.
	 *
	 * @return array Response from the API.
	 */
	public function check( $license_key, $site_url = '' ) {
		$body = array(
			'license_key' => $license_key,
			'item_id'     => $this->product->get_item_id(),
		);

		if ( '' !== $site_url ) {
			$body['site_url'] = $site_url;
		}

		$response = $this->request( 'check', $body );

		$status = $response['license'] ?? 'inactive';
		$this->store_status( $status, $response );

		return $response;
	}

	/**
	 * Return the stored license status string.
	 *
	 * @param string $default Fallback value (used as filter default).
	 *
	 * @return string
	 */
	public function get_stored_status( $default = '' ) {
		$status = get_option( $this->product->get_key() . '_license_status', $default );
		return (string) $status;
	}

	/**
	 * True when the locally cached status is 'active'.
	 *
	 * @return bool
	 */
	public function is_valid() {
		return $this->get_stored_status() === 'valid';
	}

	// -------------------------------------------------------------------------
	// Internal
	// -------------------------------------------------------------------------

	/**
	 * Remove the scheduled license check cron event.
	 */
	public function clear_cron() {
		$cron_key  = $this->product->get_key() . '_license_check';
		$timestamp = wp_next_scheduled( $cron_key );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $cron_key );
		}
	}

	/**
	 * Background cron: re-check the stored license key.
	 */
	public function background_check() {
		$key = $this->product->get_license();
		if ( empty( $key ) || 'free' === $key ) {
			return;
		}
		$this->check( $key, home_url() );
	}

	/**
	 * POST to a licensing endpoint.
	 *
	 * @param  string  $action  'activate' | 'deactivate' | 'check'
	 * @param  array   $body
	 *
	 * @return array
	 */
	private function request( $action, array $body ) {
		$url = Product::API_URL . self::LICENSES_PATH . $action;

		$result = wp_remote_post(
			$url,
			array(
				'timeout'     => 15,
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( $body ),
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'error'   => $result->get_error_message(),
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $result ), true );

		if ( ! is_array( $data ) ) {
			return array(
				'success' => false,
				'error'   => 'invalid_response',
			);
		}

		// activate/deactivate wrap payload in a `data` key; check returns flat.
		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			return $data['data'];
		}

		return $data;
	}

	/**
	 * Persist license status and full response data.
	 *
	 * @param string $status
	 * @param array  $data
	 */
	private function store_status( $status, array $data ) {
		update_option( $this->product->get_key() . '_license_status', $status );
		update_option( $this->product->get_key() . '_license_data', (object) $data );
	}
}
