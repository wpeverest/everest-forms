<?php
/**
 * The dependency model class for ThemeGrill SDK
 *
 * @package     ThemeGrillSDK
 * @subpackage  Modules
 * @copyright   Copyright (c) 2025, ThemeGrill
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */

namespace ThemeGrillSDK\Modules;

use ThemeGrillSDK\Common\AbstractModule;
use ThemeGrillSDK\Product;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Script loader module for ThemeGrill SDK.
 */
class ScriptLoader extends AbstractModule {
	/**
	 * Check if we should load the module for this product.
	 *
	 * @param Product $product Product to load the module for.
	 *
	 * @return bool Should we load ?
	 */
	public function can_load( $product ) {
		if ( $this->is_from_partner( $product ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Load module logic.
	 *
	 * @param Product $product Product to load.
	 *
	 * @return Script_Loader Module object.
	 */
	public function load( $product ) {
		$this->product = $product;
		$this->setup_actions();
		return $this;
	}

	/**
	 * Setup actions. Once for all products.
	 */
	private function setup_actions() {

		if ( apply_filters( 'themegrill_sdk_script_setup', false ) ) {
			return;
		}

		add_filter( 'themegrill_sdk_dependency_script_handler', [ $this, 'get_script_handler' ], 10, 1 );
		add_action( 'themegrill_sdk_dependency_enqueue_script', [ $this, 'enqueue_script' ], 10, 1 );
		add_filter( 'themegrill_sdk_secret_masking', [ $this, 'secret_masking' ], 10, 1 );

		add_filter( 'themegrill_sdk_script_setup', '__return_true' );

		add_action( 'themegrill_internal_page', [ $this, 'load_survey_for_product' ], 10, 2 );
	}

	/**
	 * Load survey for product using internal pages.
	 *
	 * @param string $product_slug Product slug.
	 * @param string $page_slug    Page slug.
	 */
	public function load_survey_for_product( $product_slug, $page_slug ) {
		$data = apply_filters( 'themegrill-sdk/survey/' . $product_slug, [], $page_slug );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}

		$handler = $this->get_script_handler( 'survey' );
		$this->load_survey( $handler, $data );
	}

	/**
	 * Get the script handler.
	 *
	 * @param string $slug The slug of the script.
	 *
	 * @return string The script handler. Empty if slug is not a string or not implemented.
	 */
	public function get_script_handler( $slug ) {
		if ( ! is_string( $slug ) ) {
			return '';
		}

		if ( 'tracking' !== $slug && 'survey' !== $slug ) {
			return '';
		}

		return apply_filters( 'themegrill_sdk_dependency_script_handler_name', 'themegrill_sdk_' . $slug . '_script', $slug );
	}

	/**
	 * Enqueue the script.
	 *
	 * @param string $slug The slug of the script.
	 */
	public function enqueue_script( $slug ) {
		$handler = apply_filters( 'themegrill_sdk_dependency_script_handler', $slug );
		if ( empty( $handler ) ) {
			return;
		}

		if ( 'tracking' === $slug ) {
			$this->load_tracking( $handler );
		} elseif ( 'survey' === $slug ) {
			$this->load_survey( $handler );
		}
	}

	/**
	 * Load the survey script.
	 *
	 * @param string $handler The script handler.
	 * @param array  $data The survey data.
	 *
	 * @return void
	 */
	public function load_survey( $handler, $data = array() ) {
		global $themegrill_sdk_max_path;
		$asset_file = require $themegrill_sdk_max_path . '/assets/js/build/survey/survey_deps.asset.php';

		wp_enqueue_script(
			$handler,
			$this->get_sdk_uri() . 'assets/js/build/survey/survey_deps.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		$data = array_replace_recursive( $this->get_survey_common_data( $data ), $data );

		wp_localize_script( $handler, 'tgsdk_survey_data', $data );
	}

	/**
	 * Get the common data in the Formbrick survey format.
	 *
	 * @param array $reference_data Reference data to extrapolate common properties.
	 *
	 * @return array
	 */
	public function get_survey_common_data( $reference_data = array() ) {
		$language            = apply_filters( 'themegrill_sdk_current_lang', get_user_locale() );
		$available_languages = [
			'de_DE'        => 'de',
			'de_DE_formal' => 'de',
		];
		$lang_code           = isset( $available_languages[ $language ] ) ? $available_languages[ $language ] : 'en';

		$url_parts = wp_parse_url( apply_filters( 'themegrill_sdk_current_site_url', get_site_url() ) );
		$clean_url = str_replace( 'www.', '', $url_parts['host'] );
		if ( isset( $url_parts['path'] ) ) {
			$clean_url .= $url_parts['path'];
		}
		$user_id = 'u_' . hash( 'crc32b', $clean_url );

		$common_data = [
			'userId'     => $user_id,
			'appUrl'     => 'https://app.formbricks.com',
			'attributes' => [
				'language' => $lang_code,
			],
		];

		if (
			isset( $reference_data['attributes'], $reference_data['attributes']['install_days_number'] )
			&& is_int( $reference_data['attributes']['install_days_number'] )
		) {
			$common_data['attributes']['days_since_install'] = $this->install_time_category( $reference_data['attributes']['install_days_number'] );
		}

		return $common_data;
	}

	/**
	 * Compute the install time category.
	 *
	 * @param int $install_days_number The number of days passed since installation.
	 *
	 * @return int The category.
	 */
	private function install_time_category( $install_days_number ) {
		if ( 1 < $install_days_number && 8 > $install_days_number ) {
			return 7;
		}

		if ( 8 <= $install_days_number && 31 > $install_days_number ) {
			return 30;
		}

		if ( 30 < $install_days_number && 90 > $install_days_number ) {
			return 90;
		}

		if ( 90 <= $install_days_number ) {
			return 91;
		}

		return 0;
	}

	/**
	 * Load the tracking script.
	 *
	 * @param string $handler The script handler.
	 *
	 * @return void
	 */
	public function load_tracking( $handler ) {
		global $themegrill_sdk_max_path;
		$asset_file = require $themegrill_sdk_max_path . '/assets/js/build/tracking/tracking.asset.php';

		wp_enqueue_script(
			$handler,
			$this->get_sdk_uri() . 'assets/js/build/tracking/tracking.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
	}

	/**
	 * Mask a secret with `*` for half of its length.
	 *
	 * @param mixed $secret The secret.
	 *
	 * @return mixed The masked secret if secret is a valid string.
	 */
	public function secret_masking( $secret ) {
		if ( empty( $secret ) || ! is_string( $secret ) ) {
			return $secret;
		}

		$half_len = intval( strlen( $secret ) / 2 );
		return str_repeat( '*', $half_len ) . substr( $secret, $half_len );
	}
}
