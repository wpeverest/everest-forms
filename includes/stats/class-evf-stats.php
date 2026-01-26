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
		 * Constructor of the class.
		 */
		public function __construct() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			add_filter( 'everest_forms_logger_data', array( $this, 'provide_tracking_data' ) );

			add_filter(
				'pre_option_everest_forms_sdk_enable_logger',
				function ( $enabled ) {
					return 'yes' === get_option( 'everest_forms_allow_usage_tracking' ) ? 'yes' : 'no';
				}
			);

			add_action(
				'update_option_everest_forms_sdk_enable_logger',
				function ( $old_value, $value ) {
					if ( 'yes' === $value ) {
						update_option( 'everest_forms_allow_usage_tracking', 'yes' );
					} elseif ( 'no' === $value ) {
						update_option( 'everest_forms_allow_usage_tracking', 'no' );
					}
				},
				10,
				2
			);
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
			$is_premium        = $this->is_premium();
			$base_product      = $this->get_base_product();
			$base_product_name = $is_premium ? 'Everest Forms Pro' : 'Everest Forms';

			// Build base product metadata.
			$product_meta = array(
				'active_features' => get_option( 'everest_forms_enabled_features', array() ),
				'form_count'      => $this->get_form_count(),
				'entry_count'     => $this->get_entry_count(),
			);

			if ( $is_premium ) {
				$product_meta['license_key'] = $this->get_base_product_license();
			}

			// Initialize with base product.
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

			// Get installed and active plugins - cache this.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins            = get_plugins();
			$active_plugins         = get_option( 'active_plugins', array() );
			$installed_plugin_slugs = array_keys( $all_plugins );

			// Get addons list.
			$extension_data = evf_get_json_file_contents( 'assets/extensions-json/sections/all_extensions.json' );
			if ( empty( $extension_data->products ) ) {
				return $addons_data;
			}

			// Process only active addons.
			foreach ( $extension_data->products as $addon ) {
				$addon_file = $addon->slug . '/' . $addon->slug . '.php';

				// Skip if not installed or not active.
				if ( ! in_array( $addon_file, $installed_plugin_slugs, true ) || ! is_plugin_active( $addon_file ) ) {
					continue;
				}

				$addons_data['active_addons'][ $addon->slug ] = array(
					'product_name'    => isset( $addon->name ) ? trim( $addon->name ) : '',
					'product_version' => $this->get_addon_version( $addon, $all_plugins, $active_plugins ),
					'product_type'    => 'addon',
					'product_slug'    => $addon->slug,
				);
			}

			return $addons_data;
		}

		/**
		 * Get the version of an addon
		 *
		 * @since 3.4.2
		 *
		 * @param object $addon The addon object with slug property.
		 * @param array  $all_plugins Array of all installed plugins.
		 * @param array  $active_plugins Array of active plugin file paths.
		 * @return string The addon version or empty string if not found.
		 */
		private function get_addon_version( $addon, $all_plugins, $active_plugins ) {
			$addon_file = $addon->slug . '/' . $addon->slug . '.php';

			// First, check the standard addon file.
			if ( isset( $all_plugins[ $addon_file ]['Version'] ) ) {
				return $all_plugins[ $addon_file ]['Version'];
			}

			// Fallback: check active_plugins for any file in the addon folder.
			foreach ( $active_plugins as $active_file ) {
				if ( 0 === strpos( $active_file, $addon->slug . '/' ) && isset( $all_plugins[ $active_file ]['Version'] ) ) {
					return $all_plugins[ $active_file ]['Version'];
				}
			}

			return '';
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
		 * @since 3.4.2 Update logger flag option on settings save.
		 *
		 * @param mixed  $old_value The old value of the option.
		 * @param mixed  $value The new value of the option.
		 * @param string $option The name of the option.
		 * @return mixed The new value of the option.
		 */
		public function run_on_save( $old_value, $value, $option ) {
			update_option( 'everest_forms_logger_flag', $value );
			return $value;
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
		 * Calculates the number of days since the plugin was installed.
		 *
		 * Retrieves the installation date from the 'everest_forms_install' option.
		 * If the value is not numeric, it attempts to convert it to a timestamp.
		 * Returns the number of full days elapsed since installation.
		 *
		 *  @since 3.4.2
		 * @return int Number of days since the plugin was installed.
		 */
		public static function get_install_days() {
			$install_time = get_option( 'everest_forms_install', time() );
			if ( ! is_numeric( $install_time ) ) {
				$install_time = strtotime( $install_time );
			}
			$current_time       = time();
			$days_since_install = floor( ( $current_time - $install_time ) / DAY_IN_SECONDS );
			return $days_since_install;
		}


		/**
		 * Callback for SDK tracking filter.
		 *
		 * @return array Tracking data payload.
		 * @since 3.4.2
		 */
		public function provide_tracking_data() {
			if ( ! $this->is_usage_allowed() ) {
				return array();
			}

			global $wpdb;
			$data                     = array();
			$data['product_data']     = $this->get_plugin_lists();
			$data['admin_email']      = get_bloginfo( 'admin_email' );
			$data['website_url']      = get_bloginfo( 'url' );
			$data['install_days']     = $this->get_install_days() ?? null;
			$data['wp_version']       = get_bloginfo( 'version' );
			$data['php_version']      = phpversion();
			$data['mysql_version']    = $wpdb->db_version();
			$data['server_software']  = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
			$data['is_ssl']           = is_ssl();
			$data['is_multisite']     = is_multisite();
			$data['is_wp_com']        = defined( 'IS_WPCOM' ) && IS_WPCOM;
			$data['is_wp_com_vip']    = ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) || ( function_exists( 'wpcom_is_vip' ) && wpcom_is_vip() );
			$data['is_wp_cache']      = defined( 'WP_CACHE' ) && WP_CACHE;
			$data['multi_site_count'] = $this->get_sites_total();
			$data['locale']           = get_locale();
			$data['timezone']         = $this->get_timezone_offset();
			$data['base_product']     = $this->get_base_product();

			return $data;
		}
	}
}

new EVF_Stats();
