<?php
/**
 * Modules controller class.
 *
 * @since 2.0.8.1
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
	 * @since 2.0.8.1
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
	}

	/**
	 * Get Addons Lists.
	 *
	 * @since 2.0.8.1
	 *
	 * @return array Module lists.
	 */
	public static function get_modules() {
		// Get Addons Lists.
		$addons_lists = EVF_Admin_Addons::get_extension_data();

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

			if ( in_array( 'personal', $addon->plan ) ) {
				$addon->required_plan = __( 'Personal', 'everest-forms' );
			} elseif ( in_array( 'plus', $addon->plan ) ) {
				$addon->required_plan = __( 'Plus', 'everest-forms' );
			} else {
				$addon->required_plan = __( 'Professional', 'everest-forms' );
			}
			$addon->link          = $addon->link . '&utm_campaign=' . EVF()->utm_campaign;
			$addon->type          = 'addon';
			$addons_lists[ $key ] = $addon;
		}


		return new \WP_REST_Response(
			array(
				'success'       => true,
				'modules_lists' => $addons_lists,
			),
			200
		);
	}

	/**
	 * Active a module.
	 *
	 * @since 2.0.8.1
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
			$status = self::ur_enable_feature( $request['slug'] );
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
					'message' => __( 'Module Activated Successfully', 'everest-forms' ),
				),
				200
			);
		}
	}
		/**
	 * Handler for installing or activating a addon.
	 *
	 * @since 2.0.8.1
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
	 * Handler for installing a extension.
	 *
	 * @since 2.0.8.1
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

		$api = json_decode(
			EVF_Updater_Key_API::version(
				array(
					'license'   => get_option( 'everest-forms-pro_license_key' ),
					'item_name' => ! empty( $name ) ? sanitize_text_field( wp_unslash( $name ) ) : '',
				)
			)
		);
		error_log(print_r($api, true));
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
	 * @since 2.0.8.1
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
			$status = self::ur_deactivate_addon( $slug );
		} else {
			$status = self::ur_disable_feature( $slug );
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
	 * @since 2.0.8.1
	 *
	 * @param string $slug Slug of the addon to deactivate.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public static function ur_deactivate_addon( $slug ) {
		deactivate_plugins( $slug );
		$active_plugins = get_option( 'active_plugins', array() );

		return in_array( $slug, $active_plugins, true ) ? array( 'success' => false ) : array( 'success' => true );
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
}
