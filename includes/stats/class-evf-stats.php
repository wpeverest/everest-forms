<?php
/**
 * Class
 *
 * EVF_Stats
 *
 * @package EverestForms/Stats
 * @since   1.9.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EVF_Stats' ) ) {

	/**
	 * EVF_Stats class.
	 */
	class EVF_Stats {

		/**
		 * Remote URl Constant.
		 */
		const REMOTE_URL = 'https://stats.wpeverest.com/wp-json/tgreporting/v1/process-free/';

		const LAST_RUN_STAMP = 'everest_forms_send_usage_last_run';


		/**
		 * Constructor of the class.
		 */
		public function __construct() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			add_action( 'init', array( $this, 'init_usage' ), 4 );
			add_action( 'update_option_everest_forms_allow_usage_tracking', array( $this, 'run_on_save' ), 10, 3 );
		}

		/**
		 * Get product license key.
		 */
		public function get_base_product_license() {
			return get_option( 'everest-forms-pro_license_key' );
		}

		/**
		 * Get Pro addon file.
		 */
		public function get_base_product() {
			if ( $this->is_premium() ) {
				return 'everest-forms-pro/everest-forms-pro.php';
			} else {
				return 'everest-forms/everest-forms.php';
			}
		}

		/**
		 * Check the is premium or not.
		 *
		 * @return boolean
		 */
		public function is_premium() {
			return ( false === evf_get_license_plan() ) ? false : true;
		}

		/**
		 * Get the number of entries
		 *
		 * @return int The number of entries
		 */
		public function get_entry_count() {
			global $wpdb;

			return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}evf_entries" );
		}

		/**
		 * Get the number of published forms
		 *
		 * @return int The number of published forms
		 */
		public function get_form_count() {
			global $wpdb;

			return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $wpdb->posts WHERE post_type=%s AND post_status=%s",
					'everest_form',
					'publish'
				)
			);
		}

		/**
		 * Get the plugin information of active plugins and the base plugin
		 *
		 * @return array The plugin information of active plugins and the base plugin including product name, version, type, slug, form count, entry count, and license key if it's a premium plugin.
		 */
		public function get_plugin_lists() {

			$is_premium = $this->is_premium();

			$base_product = $this->get_base_product();

			$active_plugins = get_option( 'active_plugins', array() );

			$base_product_name = $is_premium ? 'Everest Forms Pro' : 'Everest Forms';

			$product_meta = array();

			$product_meta['form_count'] = $this->get_form_count();

			$product_meta['entry_count'] = $this->get_entry_count();

			$license_key = $this->get_base_product_license();

			if ( $is_premium ) {
				$product_meta['license_key'] = $license_key;
			}

			$addons_data = array(
				$base_product => array(
					'product_name'    => $base_product_name,
					'product_version' => $is_premium ? EFP_VERSION : evf()->version,
					'product_meta'    => $product_meta,
					'product_type'    => 'plugin',
					'product_slug'    => $base_product,
					'is_premium'      => $is_premium,
				),
			);

			foreach ( $active_plugins as $plugin ) {

				$addon_file      = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin;
				$addon_file_data = get_plugin_data( $addon_file );
				if ( $base_product !== $plugin ) {
					$addons_data[ $plugin ] = array(
						'product_name'    => isset( $addon_file_data['Name'] ) ? trim( $addon_file_data['Name'] ) : '',
						'product_version' => isset( $addon_file_data['Version'] ) ? trim( $addon_file_data['Version'] ) : '',
						'product_type'    => 'plugin',
						'product_slug'    => $plugin,
					);
				}
			}

			return $addons_data;
		}

		/**
		 * Check if usage tracking is allowed
		 *
		 * @return bool True if usage tracking is allowed, False otherwise
		 */
		public function is_usage_allowed() {

			return 'yes' === get_option( 'everest_forms_allow_usage_tracking', 'no' );
		}

		/**
		 * Initialize usage tracking
		 * This function adds an action hook that runs the 'process' method on a bi-weekly basis,
		 * only when the WordPress cron system is running.
		 */
		public function init_usage() {

			if ( wp_doing_cron() ) {
				add_action( 'everest_forms_biweekly_scheduled_events', array( $this, 'process' ) );
			}
		}

		/**
		 * Run the process once when user gives consent.
		 *
		 * @param mixed  $old_value The old value of the option.
		 * @param mixed  $value The new value of the option.
		 * @param string $option The name of the option.
		 * @return mixed The new value of the option.
		 */
		public function run_on_save( $old_value, $value, $option ) {

			if ( $value !== $old_value && 'yes' === $value && ( false === get_option( self::LAST_RUN_STAMP ) ) ) {
				$this->process();
			}

			return $value;
		}

		/**
		 * Process the API call
		 * This function will check if the usage is allowed and if it has been more than 15 days since the last API call.
		 * If usage is allowed and it has been more than 15 days, it will call the API and update the last run timestamp.
		 *
		 * @return void
		 */
		public function process() {

			if ( ! $this->is_usage_allowed() ) {
				return;
			}

			$last_send = get_option( self::LAST_RUN_STAMP );

			// Make sure we do not run it more than once on each 15 days.
			if ( false !== $last_send && ( time() - $last_send ) < ( DAY_IN_SECONDS * 15 ) ) {
				return;
			}

			$this->call_api();

			// Update the last run option to the current timestamp.
			update_option( self::LAST_RUN_STAMP, time() );
		}

		/**
		 * Call API and gather system information
		 * This function gathers information about the plugin, theme, and site,
		 * such as the WordPress version, PHP version, MySQL version, server software,
		 * SSL status, multi-site status, and theme name and version.
		 * It also retrieves the number of sites in the network and the time zone offset.
		 *
		 * @return void
		 */
		public function call_api() {
			global $wpdb;
			$theme                        = wp_get_theme();
			$data                         = array();
			$data['product_data']         = $this->get_plugin_lists();
			$data['admin_email']          = get_bloginfo( 'admin_email' );
			$data['website_url']          = get_bloginfo( 'url' );
			$data['wp_version']           = get_bloginfo( 'version' );
			$data['php_version']          = phpversion();
			$data['mysql_version']        = $wpdb->db_version();
			$data['server_software']      = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
			$data['is_ssl']               = is_ssl();
			$data['is_multisite']         = is_multisite();
			$data['is_wp_com']            = defined( 'IS_WPCOM' ) && IS_WPCOM;
			$data['is_wp_com_vip']        = ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) || ( function_exists( 'wpcom_is_vip' ) && wpcom_is_vip() );
			$data['is_wp_cache']          = defined( 'WP_CACHE' ) && WP_CACHE;
			$data['multi_site_count']     = $this->get_sites_total();
			$data['active_theme']         = $theme->name;
			$data['active_theme_version'] = $theme->version;
			$data['locale']               = get_locale();
			$data['timezone']             = $this->get_timezone_offset();
			$data['base_product']         = $this->get_base_product();

			$this->send_request( self::REMOTE_URL, $data );
		}

		/**
		 * Get the total number of sites in a multi-site installation
		 *
		 * @return int The total number of sites in the multi-site installation. If the current installation is not multi-site, returns 1.
		 */
		private function get_sites_total() {

			return function_exists( 'get_blog_count' ) ? (int) get_blog_count() : 1;
		}

		/**
		 * Get the timezone offset
		 * Returns the timezone string in the format +00:00 or -00:00 if WordPress version is greater than 5.3.
		 * Otherwise, get the timezone offset from the 'timezone_string' option or 'gmt_offset' option.
		 *
		 * @return string The timezone offset
		 */
		private function get_timezone_offset() {

			// It was added in WordPress 5.3.
			if ( function_exists( 'wp_timezone_string' ) ) {
				return wp_timezone_string();
			}

			/*
			 * The code below is basically a copy-paste from that function.
			 */

			$timezone_string = get_option( 'timezone_string' );

			if ( $timezone_string ) {
				return $timezone_string;
			}

			$offset  = (float) get_option( 'gmt_offset' );
			$hours   = (int) $offset;
			$minutes = ( $offset - $hours );

			$sign      = ( $offset < 0 ) ? '-' : '+';
			$abs_hour  = abs( $hours );
			$abs_mins  = abs( $minutes * 60 );
			$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

			return $tz_offset;
		}

		/**
		 * Send Request to API.
		 *
		 * @param string $url URL.
		 * @param array  $data Data.
		 */
		public function send_request( $url, $data ) {
			$headers = array(
				'user-agent' => 'EverestForms/' . ( $this->is_premium() ? EFP_VERSION : evf()->version ) . '; ' . get_bloginfo( 'url' ),
			);

			$response = wp_remote_post(
				$url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => $headers,
					'body'        => array( 'free_data' => $data ),
				)
			);
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}
	}
}

new EVF_Stats();
