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
