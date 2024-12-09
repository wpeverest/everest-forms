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

		$roles        = array();
		$ignore_roles = apply_filters( 'everest_forms_ignore_roles_to_give_permissions', array( 'administrator', 'subscriber' ) );

		foreach ( $wp_roles->roles as $key => $value ) {
			if ( ! in_array( $key, $ignore_roles ) ) {
				$roles[ $key ] = $value['name'];
			}
		}

		wp_send_json_success( $roles );
	}

	public static function evf_add_user_manager( $request ) {
		if ( ! isset( $request['request'] ) || empty( $request['request'] ) ) {

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Request data not found.', 'easy-mail-smtp' ),
				),
				200
			);
		}

		$requested_data = $request['request'];
		error_log( print_r( 'add_user_manager', true ) );
		error_log( print_r( $requested_data, true ) );
		wp_send_json_success();
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
