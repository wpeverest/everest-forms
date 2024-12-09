<?php
/**
 * Roles and Permission controller class.
 *
 * @since xx.xx.xx
 *
 * @package  EverestFroms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Roles_And_Permission Class
 */
class EVF_Roles_And_Permission {
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
	protected $rest_base = 'roels_and_permission';

	/**
	 * Register routes.
	 *
	 * @since xx.xx.xx
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk-assign-permission-based-on-role',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'assign_permission_based_on_role' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/add-user-manager',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'evf_add_user_manager' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-wp-roles',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_wp_roles' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
	}

	public static function assign_permission_based_on_role() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		// error_log( print_r( $wp_roles->get_names(), true ) );
		// wp_send_json_success();
	}

	public static function get_wp_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$permissions = self::get_evf_permissions();

		$roles        = array();
		$ignore_roles = apply_filters( 'everest_forms_ignore_roles_to_give_permissions', array( 'administrator', 'subscriber' ) );

		foreach ( $wp_roles->roles as $key => $value ) {
			if ( ! in_array( $key, $ignore_roles ) ) {
				$roles['roles'][ $key ] = $value['name'];
			}
		}

		$roles['permission'] = $permissions;

		wp_send_json_success( $roles );
	}

	public static function evf_add_user_manager( $request ) {
		if ( ! isset( $request['request'] ) || empty( $request['request'] ) ) {

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Request data not found.', 'everest-forms' ),
				),
				200
			);
		}

		$requested_data = $request['request'];

		$user_email          = isset( $requested_data['user_email'] ) ? $requested_data['user_email'] : '';
		$assigned_permission = isset( $requested_data['assigned_permission'] ) && ! empty( $requested_data['assigned_permission'] ) ? $requested_data['assigned_permission'] : array();

		error_log( print_r( $requested_data, true ) );
		if ( empty( $user_email ) && empty( $assigned_permission ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => array(
						'user_email'          => esc_html__( 'User email is required.', 'everest-forms' ),
						'assigned_permission' => esc_html__( 'User permission is required', 'everest-forms' ),
					),
				),
				200
			);
		}

		if ( empty( $assigned_permission ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => array(
						'assigned_permission' => esc_html__( 'User permission is required', 'everest-forms' ),
					),
				),
				200
			);
		}

		if ( empty( $user_email ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => array(
						'user_email' => esc_html__( 'User email is required.', 'everest-forms' ),
					),
				),
				200
			);
		}

		error_log( print_r( 'add_user_manager', true ) );
		error_log( print_r( $requested_data, true ) );
		wp_send_json_success();
	}

	private static function get_evf_permissions() {
		$capabilities = array();

		$capabilities['permissions'] = array(
			'manage_everest_forms' => 'Manage Everest Forms',
		);

		$capability_types = array( 'forms', 'entries' );

		foreach ( $capability_types as $capability_type ) {
			if ( 'forms' === $capability_type ) {
				$capabilities['permissions'][ "everest_forms_create_{$capability_type}" ] = 'Create ' . ucfirst( $capability_type );
			}

			foreach ( array( 'view', 'edit', 'delete' ) as $context ) {
				$capabilities['permissions'][ "everest_forms_{$context}_{$capability_type}" ]        = ucfirst( $context ) . ' ' . ucfirst( $capability_type );
				$capabilities['permissions'][ "everest_forms_{$context}_others_{$capability_type}" ] = ucfirst( $context ) . ' Others ' . ucfirst( $capability_type );
			}
		}

		error_log( print_r( $capabilities, true ) );

		return $capabilities;
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}
}
