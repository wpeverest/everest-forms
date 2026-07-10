<?php
/**
 * Auto-updater module for ThemeGrill SDK.
 *
 * Hooks into WordPress update system and fetches version info from
 * api.themegrill.com/licenses/version for both plugins and themes.
 * Requires the product file to declare:
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
use ThemeGrillSDK\Common\ModuleFactory;
use ThemeGrillSDK\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updater module for ThemeGrill SDK.
 */
class Updater extends AbstractModule {

	/**
	 * Version API path.
	 */
	const VERSION_PATH = 'licenses/version';

	/**
	 * Transient TTL for version info cache (seconds).
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
		return $product->get_item_id() > 0;
	}

	/**
	 * Bootstrap the module.
	 *
	 * @param Product $product Product to load.
	 *
	 * @return Updater
	 */
	public function load( $product ) {
		$this->product = $product;

		if ( $product->is_plugin() ) {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_plugin_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugins_api_info' ), 20, 3 );
		}

		if ( $product->is_theme() ) {
			add_filter( 'pre_set_site_transient_update_themes', array( $this, 'inject_theme_update' ) );
		}

		// Clear version cache when license status changes so package URL is refreshed.
		add_action( 'update_option_' . $product->get_key() . '_license_status', array( $this, 'on_license_status_change' ), 10, 2 );

		add_action( 'upgrader_process_complete', array( $this, 'clear_version_cache' ), 10, 2 );

		return $this;
	}

	// -------------------------------------------------------------------------
	// License gate
	// -------------------------------------------------------------------------

	/**
	 * True when the product requires a license and that license is currently valid.
	 * Returns true for products that do not require a license (free/unmetered updates).
	 *
	 * @return bool
	 */
	private function has_valid_license() {
		if ( ! $this->product->requires_license() ) {
			return true;
		}

		/** @var \ThemeGrillSDK\Modules\Licenser|null $licenser */
		$licenser = ModuleFactory::get_module( $this->product->get_slug(), 'licenser' );
		if ( $licenser instanceof Licenser ) {
			return $licenser->is_valid();
		}

		// Fallback: read option directly.
		return get_option( $this->product->get_key() . '_license_status', '' ) === 'valid';
	}

	/**
	 * Clear version cache when license status changes so package URL refreshes.
	 *
	 * @param mixed $old_value Previous status.
	 * @param mixed $new_value New status.
	 */
	public function on_license_status_change( $old_value, $new_value ) {
		if ( $old_value !== $new_value ) {
			delete_transient( $this->get_cache_key() );
		}
	}

	// -------------------------------------------------------------------------
	// Plugin update hooks
	// -------------------------------------------------------------------------

	/**
	 * Inject update data into the plugins transient.
	 *
	 * @param object $transient WordPress update_plugins transient.
	 *
	 * @return object
	 */
	public function inject_plugin_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$plugin_file = $this->product->get_slug() . '/' . $this->product->get_file();
		$version     = $transient->checked[ $plugin_file ] ?? $this->product->get_version();
		$info        = $this->get_version_info();

		if ( empty( $info ) || empty( $info['version'] ) ) {
			return $transient;
		}

		if ( version_compare( $info['version'], $version, '>' ) ) {
			$update                              = array(
				'slug'         => $this->product->get_slug(),
				'plugin'       => $plugin_file,
				'new_version'  => $info['version'],
				'url'          => $info['homepage'] ?? '',
				'package'      => $info['package'] ?? '',
				'icons'        => array(),
				'banners'      => $info['banners'] ?? array(),
				'tested'       => $info['tested'] ?? '',
				'requires_php' => $info['requires_php'] ?? '',
			);
			$transient->response[ $plugin_file ] = (object) $update;
		}

		return $transient;
	}

	/**
	 * Provide plugin info for the WordPress update details screen.
	 *
	 * @param false|object|array $result Default value.
	 * @param string             $action plugins_api action.
	 * @param object             $args   Request args.
	 *
	 * @return false|object
	 */
	public function plugins_api_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->product->get_slug() ) {
			return $result;
		}

		$info = $this->get_version_info();

		if ( empty( $info ) ) {
			return $result;
		}

		$api                = new \stdClass();
		$api->name          = $info['name'] ?? $this->product->get_name();
		$api->slug          = $this->product->get_slug();
		$api->version       = $info['version'] ?? $this->product->get_version();
		$api->author        = $this->product->get_store_name();
		$api->homepage      = $info['homepage'] ?? $this->product->get_store_url();
		$api->requires      = $info['requires'] ?? '';
		$api->tested        = $info['tested'] ?? '';
		$api->requires_php  = $info['requires_php'] ?? '';
		$api->download_link = $info['package'] ?? '';
		$api->last_updated  = $info['last_updated'] ?? '';
		$api->sections      = $info['sections'] ?? array();
		$api->banners       = $info['banners'] ?? array();

		return $api;
	}

	// -------------------------------------------------------------------------
	// Theme update hooks
	// -------------------------------------------------------------------------

	/**
	 * Inject update data into the themes transient.
	 *
	 * @param object $transient WordPress update_themes transient.
	 *
	 * @return object
	 */
	public function inject_theme_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$slug    = $this->product->get_slug();
		$version = $transient->checked[ $slug ] ?? $this->product->get_version();
		$info    = $this->get_version_info();

		if ( empty( $info ) || empty( $info['version'] ) ) {
			return $transient;
		}

		if ( version_compare( $info['version'], $version, '>' ) ) {
			$transient->response[ $slug ] = array(
				'theme'        => $slug,
				'new_version'  => $info['version'],
				'url'          => $info['homepage'] ?? '',
				'package'      => $info['package'] ?? '',
				'requires'     => $info['requires'] ?? '',
				'requires_php' => $info['requires_php'] ?? '',
			);
		}

		return $transient;
	}

	// -------------------------------------------------------------------------
	// Cache management
	// -------------------------------------------------------------------------

	/**
	 * Clear version cache after an upgrade completes.
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance.
	 * @param array        $hook_extra Extra upgrade data.
	 */
	public function clear_version_cache( $upgrader, $hook_extra ) {
		$slug = $this->product->get_slug();

		$is_target = false;
		if ( isset( $hook_extra['plugin'] ) && false !== strpos( $hook_extra['plugin'], $slug ) ) {
			$is_target = true;
		}
		if ( isset( $hook_extra['theme'] ) && $hook_extra['theme'] === $slug ) {
			$is_target = true;
		}

		if ( $is_target ) {
			delete_transient( $this->get_cache_key() );
		}
	}

	// -------------------------------------------------------------------------
	// Internal
	// -------------------------------------------------------------------------

	/**
	 * Fetch version info from API, with transient cache.
	 *
	 * @return array|null Version info array or null on failure.
	 */
	private function get_version_info() {
		$cache_key = $this->get_cache_key();
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$license = $this->product->get_license();
		$params  = array_filter(
			array(
				'item_id' => $this->product->get_item_id(),
				'license' => ( ! empty( $license ) && 'free' !== $license ) ? $license : null,
				'url'     => home_url(),
			)
		);

		$url = add_query_arg( $params, Product::API_URL . self::VERSION_PATH );

		$response = $this->safe_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status !== 200 || ! is_array( $data ) || empty( $data ) || json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		set_transient( $cache_key, $data, self::CACHE_TTL );

		return $data;
	}

	/**
	 * Transient key for this product's version cache.
	 *
	 * @return string
	 */
	private function get_cache_key() {
		return $this->product->get_key() . '_updater_version_info';
	}
}
