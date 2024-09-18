<?php
/**
 * Modules controller class.
 *
 * @since 3.0.0
 *
 * @package  EverestFroms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Modules Class
 */
class EVF_Modules {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'everest-forms/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'modules';

	/**
	 * Register routes.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_modules' ),
				'permission_callback' => array( __CLASS__, 'check_admin_plugin_activation_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'activate_module' ),
				'permission_callback' => array( __CLASS__, 'check_admin_plugin_activation_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/deactivate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'deactivate_module' ),
				'permission_callback' => array( __CLASS__, 'check_admin_plugin_activation_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk-activate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'bulk_activate_modules' ),
				'permission_callback' => array( __CLASS__, 'check_admin_plugin_activation_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk-deactivate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'bulk_deactivate_modules' ),
				'permission_callback' => array( __CLASS__, 'check_admin_plugin_activation_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate-license',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'activate_license' ),
				'permission_callback' => array( __CLASS__, 'check_admin_plugin_activation_permissions' ),
			)
		);
	}

	/**
	 * Get Addons Lists.
	 *
	 * @since 3.0.0
	 *
	 * @return array Module lists.
	 */
	public static function get_modules() {
		$extension_data = self::get_extensions_data();
		$features_lists = $extension_data->features;

		$enabled_features = get_option( 'everest_forms_enabled_features', array() );
		foreach ( $features_lists as $key => $feature ) {
			if ( in_array( $feature->slug, $enabled_features, true ) ) {
				$feature->status = 'active';
			} else {
				$feature->status = 'inactive';
			}
			$feature->link          = $feature->link . '&utm_campaign=' . EVF()->utm_campaign;
			$feature->type          = 'feature';
			$features_lists[ $key ] = $feature;
		}

		// Get Addons Lists.
		$addons_lists = $extension_data->products;

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed_plugin_slugs = array_keys( get_plugins() );

		foreach ( $addons_lists as $key => $addon ) {
			$addon_file = $addon->slug . '/' . $addon->slug . '.php';
			if ( in_array( $addon_file, $installed_plugin_slugs, true ) ) {
				if ( is_plugin_active( $addon_file ) ) {
					$addon->status = 'active';
				} else {
					$addon->status = 'inactive';
				}
			} else {
				$addon->status = 'not-installed';
			}

			if ( in_array( 'personal', $addon->plan, true ) ) {
				$addon->required_plan = __( 'Pro', 'everest-forms' );
			}
			$addon->link          = $addon->link . '&utm_campaign=' . EVF()->utm_campaign;
			$addon->type          = 'addon';
			$addons_lists[ $key ] = $addon;
		}

		$modules_lists = array_merge( $features_lists, $addons_lists );

		return new \WP_REST_Response(
			array(
				'success'       => true,
				'modules_lists' => $modules_lists,
			),
			200
		);
	}

	/**
	 * Active a module.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function activate_module( $request ) {
		if ( ! isset( $request['slug'] ) || empty( trim( $request['slug'] ) ) ) { //phpcs:ignore

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Module slug is a required field', 'everest-forms' ),
				),
				400
			);
		}

		$slug = is_array( $request['slug'] ) ? current( $request['slug'] ) : $request['slug'];
		$type = isset( $request['type'] ) ? $request['type'] : '';

		$slug        = sanitize_key( wp_unslash( $request['name'] ) );
		$name        = sanitize_text_field( $request['name'] );
		$plugin_slug = wp_unslash( $request['slug'] ) . '/' . wp_unslash( $request['slug'] ) . '.php'; // phpcs:ignore
		$plugin      = plugin_basename( sanitize_text_field( $plugin_slug ) );

		$status = array();

		if ( 'addon' === $type ) {
			$status = self::install_addons( $slug, $name, $plugin );
		} else {
			$status = self::enable_feature( $request['slug'] );
		}

		if ( isset( $status['success'] ) && ! $status['success'] ) {

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( "Module couldn't be activated at the moment. Please try again later.", 'everest-forms' ),
				),
				400
			);
		} else {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Module activated successfully', 'everest-forms' ),
				),
				200
			);
		}
	}
		/**
		 * Handler for installing or activating a addon.
		 *
		 * @since 3.0.0
		 *
		 * @param string $slug Slug of the addon to install/activate.
		 * @param string $name Name of the addon to install/activate.
		 * @param string $plugin Basename of the addon to install/activate.
		 *
		 * @see Plugin_Upgrader
		 *
		 * @global WP_Filesystem_Base $wp_filesystem Subclass
		 */
	public static function install_addons( $slug, $name, $plugin ) {

		$status = array(
			'install' => 'plugin',
			'slug'    => $slug,
		);

		$status = self::install_individual_addon( $slug, $plugin, $name, $status );

		return $status;
	}
	/**
	 * Enable a feature.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Slug of the feature to enable.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function enable_feature( $slug ) {

		// Logic to enable Feature.
		$enabled_features = get_option( 'everest_forms_enabled_features', array() );
		array_push( $enabled_features, $slug );
		update_option( 'everest_forms_enabled_features', $enabled_features );

		return array( 'success' => true );
	}

	/**
	 * Handler for installing a extension.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Slug of the addon to install.
	 * @param string $plugin Plugin file of the addon to install.
	 * @param string $name Name of the addon to install.
	 * @param array  $status Staus array to track addon installation status.
	 *
	 * @see Plugin_Upgrader
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 */
	public static function install_individual_addon( $slug, $plugin, $name, $status ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
			$plugin_data          = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$status['plugin']     = $plugin;
			$status['pluginName'] = $plugin_data['Name'];

			if ( is_plugin_inactive( $plugin ) ) {
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					$status['errorCode']    = $result->get_error_code();
					$status['errorMessage'] = $result->get_error_message();
					$status['success']      = false;
					return $status;
				}
				$status['success'] = true;
				$status['message'] = __( 'Addons activated successfully', 'everest-forms' );
				return $status;
			}
		}

		if ( 'aicontactform' === $slug && ! evf_string_to_bool( evf_get_license_plan() ) ) {
			$args = array(
				'slug'   => 'ai-contact-form',
				'fields' => array(
					'short_description' => true,
					'sections'          => true,
					'requires'          => true,
					'tested'            => true,
					'rating'            => true,
					'downloaded'        => true,
					'last_updated'      => true,
					'added'             => true,
					'tags'              => true,
					'homepage'          => true,
					'donate_link'       => true,
					'reviews'           => true,
					'download_link'     => true,
					'screenshots'       => true,
					'active_installs'   => true,
					'version'           => true,
				),
			);
			$api  = plugins_api( 'plugin_information', $args );
		} else {
			$api = json_decode(
				EVF_Updater_Key_API::version(
					array(
						'license'   => get_option( 'everest-forms-pro_license_key' ),
						'item_name' => ! empty( $name ) ? sanitize_text_field( wp_unslash( $name ) ) : '',
					)
				)
			);
		}

		if ( is_wp_error( $api ) ) {
			$status['success']      = false;
			$status['errorMessage'] = $api['msg'];
			return $status;
		}

		$status['pluginName'] = $api->name;

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['success']      = false;
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();
			return $status;
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['success']      = false;
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();
			return $status;
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['success']      = false;
			$status['errorMessage'] = $skin->get_error_messages();
			return $status;
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;
			$status['success']      = false;
			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = esc_html__( 'Unable to connect to the filesystem. Please confirm your credentials.', 'everest-forms' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}
			return $status;
		}

		$api->version   = isset( $api->new_version ) ? $api->new_version : '';
		$install_status = install_plugin_install_status( $api );
		activate_plugin( $plugin );
		$status['success'] = true;
		$status['message'] = __( 'Addon installed Successfully', 'everest-forms' );
		return $status;
	}
	/**
	 * Deactive a module.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function deactivate_module( $request ) {
		if ( ! isset( $request['slug'] ) || empty( trim( $request['slug'] ) ) ) { //phpcs:ignore

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Addon slug is a required field', 'everest-forms' ),
				),
				400
			);
		}

		$slug = is_array( $request['slug'] ) ? current( $request['slug'] ) : $request['slug'];
		$type = isset( $request['type'] ) ? $request['type'] : '';

		$status = array();

		if ( 'addon' === $type ) {
			$slug   = $slug . '/' . $slug . '.php';
			$status = self::deactivate_addon( $slug );
		} else {
			$status = self::disable_feature( $slug );
		}

		if ( isset( $status['success'] ) && ! $status['success'] ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( "Module couldn't be deactivated. Please try again later.", 'everest-forms' ),
				),
				400
			);
		} else {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => esc_html__( 'Module deactivated successfully', 'everest-forms' ),
				),
				200
			);
		}
	}

	/**
	 * Deactive a addon.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Slug of the addon to deactivate.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function deactivate_addon( $slug ) {
		deactivate_plugins( $slug );
		$active_plugins = get_option( 'active_plugins', array() );

		return in_array( $slug, $active_plugins, true ) ? array( 'success' => false ) : array( 'success' => true );
	}
	/**
	 * Disable a feature.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug Slug of the feature to disable.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function disable_feature( $slug ) {

		// Logic to disable Feature.
		$enabled_features = get_option( 'everest_forms_enabled_features', array() );
		$enabled_features = array_values( array_diff( $enabled_features, array( $slug ) ) );
		update_option( 'everest_forms_enabled_features', $enabled_features );

		return in_array( $slug, $enabled_features, true ) ? array( 'success' => false ) : array( 'success' => true );
	}

	/**
	 * Bulk Activate modules.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function bulk_activate_modules( $request ) {

		if ( ! isset( $request['moduleData'] ) || empty( $request['moduleData'] ) ) {

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Please select addons to activate', 'everest-forms' ),
				),
				400
			);
		}

		$feature_slugs = array();
		$addon_slugs   = array();

		foreach ( $request['moduleData'] as $slug => $addon ) {
			if ( 'addon' === $addon['type'] ) {
				array_push( $addon_slugs, $addon );
			} else {
				$feature_slugs[ $slug ] = $addon['name'];
			}
		}

		$failed_modules = array();

		if ( ! empty( $addon_slugs ) ) {
			$failed_modules = array_merge( $failed_modules, self::bulk_install_addons( $addon_slugs ) );
		}

		if ( ! empty( $feature_slugs ) ) {
			$failed_modules = array_merge( $failed_modules, self::bulk_enable_feature( $feature_slugs ) );
		}

		if ( count( $failed_modules ) > 0 ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						/* translators: 1: Failed Addon Names */
						'message' => sprintf( __( '%1$s activation failed. Please try again sometime later.', 'everest-forms' ), implode( ', ', $failed_modules ) ),
					),
					400
				);
		} else {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'All of the selected modules have been activated successfully.', 'everest-forms' ),
				),
				200
			);
		}
	}
	/**
	 * Bulk Deactivate Modules.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function bulk_deactivate_modules( $request ) {

		if ( ! isset( $request['moduleData'] ) || empty( $request['moduleData'] ) ) {

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Please select a module to deactivate', 'everest-forms' ),
				),
				400
			);
		}

		$feature_slugs = array();
		$addon_slugs   = array();

		foreach ( $request['moduleData'] as $slug => $module ) {
			if ( isset( $module['type'] ) && 'addon' === $module['type'] ) {
				array_push( $addon_slugs, $module['slug'] );
			} else {
				array_push( $feature_slugs, $slug );
			}
		}

		$deactivated_count = 0;

		if ( ! empty( $addon_slugs ) ) {
			$deactivated_count += count( self::bulk_deactivate_addon( $addon_slugs ) );
		}

		if ( ! empty( $feature_slugs ) ) {
			$deactivated_count += self::bulk_disable_feature( $feature_slugs );
		}

		if ( count( $request['moduleData'] ) === $deactivated_count ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => esc_html__( 'All of the selected modules have been deactivated.', 'everest-forms' ),
				),
				200
			);
		} else {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Some of the selected modules may not have been deactivated. Please try again later', 'everest-forms' ),
				),
				400
			);
		}
	}
	/**
	 * Handler for installing bulk extension.
	 *
	 * @since 3.0.0
	 *
	 * @param array $addon_data Datas of addons to activate.
	 *
	 * @see Plugin_Upgrader
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 */
	public static function bulk_install_addons( $addon_data ) {

		$failed_addon = array();

		foreach ( $addon_data as $addon ) {
			$slug        = isset( $addon['name'] ) ? sanitize_key( wp_unslash( $addon['name'] ) ) : '';
			$plugin_slug = isset( $addon['slug'] ) ? sanitize_text_field( $addon['slug'] ) : '';
			$name        = isset( $addon['name'] ) ? sanitize_text_field( $addon['name'] ) : '';
			$plugin      = plugin_basename( sanitize_text_field( $plugin_slug ) );
			$status      = array(
				'install' => 'plugin',
				'slug'    => $slug,
			);
			$status      = self::install_individual_addon( $slug, $plugin, $name, $status );

			if ( isset( $status['success'] ) && '' === $status['success'] ) {
				array_push( $failed_addon, $name );
				continue;
			}
		}

		return $failed_addon;
	}
	/**
	 * Bulk enable features.
	 *
	 * @since 3.0.0
	 *
	 * @param array $feature_data Data of the features to enable.
	 */
	public static function bulk_enable_feature( $feature_data ) {
		$failed_to_enable = array(); // Add Names of failed feature enable process.

		// Logic to enable Feature.
		$enabled_features = get_option( 'everest_forms_enabled_features', array() );

		foreach ( $feature_data as $slug => $name ) {
			array_push( $enabled_features, $slug );
		}

		update_option( 'everest_forms_enabled_features', $enabled_features );

		return $failed_to_enable;
	}
	/**
	 * Bulk Deactivate addons.
	 *
	 * @since 3.0.0
	 *
	 * @param array $addon_slugs Slugs of the addons to deactivate.
	 */
	public static function bulk_deactivate_addon( $addon_slugs ) {

		deactivate_plugins( $addon_slugs );

		$active_plugins = get_option( 'active_plugins', array() );

		return array_diff( $addon_slugs, $active_plugins );
	}

	/**
	 * Bulk disable features.
	 *
	 * @since 3.2.0
	 *
	 * @param array $feature_slugs Slugs of the features to disable.
	 */
	public static function bulk_disable_feature( $feature_slugs ) {

		// Logic to enable Feature.
		$enabled_features = get_option( 'everest_forms_enabled_features', array() );
		$enabled_features = array_values( array_diff( $enabled_features, $feature_slugs ) );
		update_option( 'everest_forms_enabled_features', $enabled_features );

		return count( $feature_slugs );
	}

	/**
	 * Get section content for the extensions screen.
	 *
	 * @return array
	 */
	public static function get_extensions_data() {

		$extension_data = evf_get_json_file_contents( 'assets/extensions-json/sections/all_extensions.json' );
		return apply_filters( 'everest_forms_extensions_section_data', $extension_data );
	}
	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_plugin_activation_permissions( $request ) {
		return current_user_can( 'activate_plugin' );
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_plugin_installation_permissions( $request ) {
		return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugin' );
	}

	/**
	 * Activate the plugin license.
	 *
	 * @since 3.0.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function activate_license( $request ) {
		if ( isset( $request['licenseActivationKey'] ) ) {
			$evf_dashboard_plugin_updater   = new EVF_Plugin_Updater();
			$evf_dashboard_plugin_activator = $evf_dashboard_plugin_updater->activate_license( $request['licenseActivationKey'] );

			if ( isset( $evf_dashboard_plugin_activator ) && $evf_dashboard_plugin_activator ) {
				return new \WP_REST_Response(
					array(
						'status'  => true,
						'message' => esc_html__( 'Everest Forms Pro activated successfully.', 'everest-forms' ),
						'code'    => 200,
					),
					200
				);
			} else {
				return new \WP_REST_Response(
					array(
						'status'  => true,
						'message' => esc_html__( 'Please enter the valid license key.', 'everest-forms' ),
						'code'    => 400,
					),
					200
				);
			}
		}
	}
}
