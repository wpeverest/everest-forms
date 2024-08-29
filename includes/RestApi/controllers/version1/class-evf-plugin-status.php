<?php
/**
 *  Plugin Status Data Controller.
 *
 * @since x.x.x
 *
 * @package EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everest_Forms_Plugin_Status Class.
 */
class Everest_Forms_Plugin_Status {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'everest-forms/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'plugin';

	/**
	 * Register routes.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_plugin_status' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'plugin_activate' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);
	}

		/**
		 * Get Plugin Status.
		 *
		 * @since x.x.x
		 *
		 * @return WP_REST_Response|WP_Error
		 */
	public function get_plugin_status() {
		$extension_data   = self::get_addons_data();
		$features_lists   = $extension_data->features;
		$enabled_features = get_option( 'everest_forms_enabled_features', array() );
		$plugin_statuses  = array();

		foreach ( $features_lists as $feature ) {
			$plugin_statuses[ $feature->slug ] = in_array( $feature->slug, $enabled_features, true ) ? 'active' : 'inactive';
		}

		$addons_lists = $extension_data->products;
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed_plugin_slugs = array_keys( get_plugins() );

		foreach ( $addons_lists as $addon ) {
			$addon_file = $addon->slug . '/' . $addon->slug . '.php';
			if ( in_array( $addon_file, $installed_plugin_slugs, true ) ) {
				$plugin_statuses[ $addon->slug ] = is_plugin_active( $addon_file ) ? 'active' : 'inactive';
			} else {
				$plugin_statuses[ $addon->slug ] = 'not-installed';
			}
		}

		return new WP_REST_Response(
			array(
				'success'       => true,
				'plugin_status' => $plugin_statuses,
			),
			200
		);
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
	public static function plugin_activate( $request ) {
		$module_data = $request->get_param( 'moduleData' );

		if ( is_string( $module_data ) ) {
			$module_data = json_decode( $module_data, true );
		}

		if ( ! is_array( $module_data ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid module data format.', 'everest-forms' ),
				),
				400
			);
		}

		$addon_slugs   = array();
		$feature_slugs = array();

		foreach ( $module_data as $addon ) {
			if ( isset( $addon['type'] ) && 'addon' === $addon['type'] ) {
				array_push( $addon_slugs, $addon );
			} else {
				$slug                   = $addon['slug'];
				$feature_slugs[ $slug ] = isset( $addon['name'] ) ? $addon['name'] : $slug;
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
					'message' => sprintf( __( '%1$s activation failed. Please try again later.', 'everest-forms' ), implode( ', ', $failed_modules ) ),
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
			$slug        = isset( $addon['slug'] ) ? sanitize_key( wp_unslash( $addon['slug'] ) ) : '';
			$plugin_slug = isset( $addon['slug'] ) ? sanitize_key( $addon['slug'] ) : '';
			$name        = isset( $addon['name'] ) ? sanitize_text_field( $addon['name'] ) : '';
			$status      = array(
				'install' => 'plugin',
				'slug'    => $slug,
			);

			$status = self::install_individual_addon( $slug, $plugin, $name, $status );

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
		$failed_to_enable = array();
		$enabled_features = get_option( 'everest_forms_enabled_features', array() );

		foreach ( $feature_data as $slug => $name ) {
			if ( ! in_array( $slug, $enabled_features, true ) ) {
				$enabled_features[] = $slug;
			}
		}

		$update_success = update_option( 'everest_forms_enabled_features', $enabled_features );

		if ( ! $update_success ) {
			$failed_to_enable[] = __( 'Failed to update enabled features.', 'everest-forms' );
		}

		return $failed_to_enable;
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
		require_once ABSPATH . 'wp-admin/includes/file.php';
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
			} else {
				$status['success'] = true;
				$status['message'] = __( 'Addon is already active.', 'everest-forms' );
			}
			return $status;
		} else {
			$status['success'] = false;
			$status['message'] = __( 'Addon file not found.', 'everest-forms' );
			return $status;
		}
	}


	/**
	 * Retrieve addons data.
	 *
	 * @since x.x.x
	 *
	 * @return object
	 */
	public static function get_addons_data() {
		$addons_data = evf_get_json_file_contents( 'assets/extensions-json/sections/all_extensions.json' );
		lg( $addons_data );

		$new_product = (object) array(
			'products' => array(
				(object) array(
					'title'          => 'Everest Forms PRO',
					'slug'           => 'everest-forms-pro',
					'name'           => 'Everest Forms PRO',
					'image'          => '',
					'excerpt'        => '',
					'link'           => '',
					'released_date'  => '',
					'plan'           => array(
						'personal',
						'agency',
						'themegrill agency',
					),
					'setting_url'    => '',
					'demo_video_url' => '',
				),
			),
		);

		if ( isset( $addons_data->products ) ) {

			$existing_products = $addons_data->products;
			$new_products      = $new_product->products;

			$merged_products = array_merge(
				json_decode( json_encode( $existing_products ), true ),
				json_decode( json_encode( $new_products ), true )
			);

			$addons_data->products = json_decode( json_encode( $merged_products ) );
		}
		return apply_filters( 'everest_forms_addons_section_data', $addons_data );
	}

	/**
	 * Check if a given request has access.
	 *
	 * @since x.x.x
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function check_admin_permissions( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		// Nonce check.
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permissions to perform this action.', 'everest-forms' ),
				array( 'status' => 403 )
			);
		}

		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You are not allowed to access this resource.', 'everest-forms' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
