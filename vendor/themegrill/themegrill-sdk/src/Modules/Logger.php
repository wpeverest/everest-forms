<?php
/**
 * The logger model class for ThemeGrill SDK
 *
 * @package     ThemeGrillSDK
 * @subpackage  Modules
 * @copyright   Copyright (c) 2025, ThemeGrill
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */

namespace ThemeGrillSDK\Modules;

use ThemeGrillSDK\Common\AbstractModule;
use ThemeGrillSDK\Loader;
use ThemeGrillSDK\Product;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger module for ThemeGrill SDK.
 */
class Logger extends AbstractModule {
	/**
	 * Endpoint where to collect logs.
	 */
	const TRACKING_ENDPOINT = 'https://api.themegrill.com/tracking/log';

	/**
	 * Check if we should load the module for this product.
	 *
	 * @param Product $product Product to load the module for.
	 *
	 * @return bool Should we load ?
	 */
	public function can_load( $product ) {
		return apply_filters( $product->get_slug() . '_sdk_enable_logger', true );
	}

	/**
	 * Load module logic.
	 *
	 * @param Product $product Product to load.
	 *
	 * @return Logger Module object.
	 */
	public function load( $product ) {
		$this->product = $product;
		add_action( 'wp_loaded', array( $this, 'setup_actions' ) );
		add_action( 'admin_init', array( $this, 'setup_notification' ) );
		return $this;
	}

	/**
	 * Setup notification on admin.
	 */
	public function setup_notification() {
		if ( $this->is_logger_active() ) {
			return;
		}
		add_filter( 'themegrill_sdk_registered_notifications', [ $this, 'add_notification' ] );
	}

	/**
	 * Setup tracking actions.
	 */
	public function setup_actions() {
		if ( ! $this->is_logger_active() ) {
			return;
		}
		$action_key = $this->product->get_key() . '_log_activity';
		if ( ! wp_next_scheduled( $action_key ) ) {
			wp_schedule_single_event( time() + ( wp_rand( 1, 24 ) * 3600 ), $action_key );
		}
		add_action( $action_key, array( $this, 'send_log' ) );
	}

	/**
	 * Check if the logger is active.
	 *
	 * @return bool Is logger active?
	 */
	private function is_logger_active() {
		$default = 'no';

		if ( ! $this->product->is_wordpress_available() ) {
			$default = 'yes';
		} else {
			$all_products = Loader::get_products();
			foreach ( $all_products as $product ) {
				if ( $product->requires_license() ) {
					$default = 'yes';
					break;
				}
			}
		}

		return ( get_option( $this->product->get_key() . '_logger_flag', $default ) === 'yes' );
	}
	/**
	 * Add notification to queue.
	 *
	 * @param array $all_notifications Previous notification.
	 *
	 * @return array All notifications.
	 */
	public function add_notification( $all_notifications ) {

		$message = apply_filters( $this->product->get_key() . '_logger_heading', Loader::$labels['logger']['notice'] );

		$message       = str_replace(
			array( '{product}' ),
			$this->product->get_friendly_name(),
			$message
		);
		$button_submit = apply_filters( $this->product->get_key() . '_logger_button_submit', Loader::$labels['logger']['cta_y'] );
		$button_cancel = apply_filters( $this->product->get_key() . '_logger_button_cancel', Loader::$labels['logger']['cta_n'] );

		$all_notifications[] = [
			'id'      => $this->product->get_key() . '_logger_flag',
			'message' => $message,
			'ctas'    => [
				'confirm' => [
					'link' => '#',
					'text' => $button_submit,
				],
				'cancel'  => [
					'link' => '#',
					'text' => $button_cancel,
				],
			],
		];

		return $all_notifications;
	}

	/**
	 * Send the statistics to the api endpoint.
	 */
	public function send_log() {
		$environment                    = array();
		$theme                          = wp_get_theme();
		$environment['theme']           = array();
		$environment['theme']['name']   = $theme->get( 'Name' );
		$environment['theme']['author'] = $theme->get( 'Author' );
		$environment['theme']['parent'] = $theme->parent() !== false ? $theme->parent()->get( 'Name' ) : $theme->get( 'Name' );
		$environment['plugins']         = get_option( 'active_plugins' );
		global $wp_version;
		wp_remote_post(
			self::TRACKING_ENDPOINT,
			array(
				'method'      => 'POST',
				'timeout'     => 3,
				'redirection' => 5,
				'body'        => wp_json_encode(
					array(
						'site'         => get_site_url(),
						'slug'         => $this->product->get_slug(),
						'version'      => $this->product->get_version(),
						'wp_version'   => $wp_version,
						'install_time' => $this->product->get_install_time(),
						'locale'       => get_locale(),
						'data'         => apply_filters( $this->product->get_key() . '_logger_data', array() ),
						'environment'  => $environment,
						'license'      => apply_filters( $this->product->get_key() . '_license_status', '' ),
					)
				),
				'headers'     => array(
					'Content-Type' => 'application/json',
					'User-Agent'   => 'ThemeGrillSDK',
				),
			)
		);
	}
}
