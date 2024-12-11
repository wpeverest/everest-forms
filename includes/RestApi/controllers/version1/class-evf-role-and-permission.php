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
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-managers',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_managers' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/remove-manager',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'remove_managers' ),
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

		$user = get_user_by( 'email', $user_email );

		if ( empty( $user ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => array(
						'user_email' => esc_html__( 'User not found with this email.', 'everest-forms' ),
					),
				),
				200
			);
		}

		self::attach_permission( $user, $assigned_permission );

		update_user_meta( $user->ID, '_everest_forms_has_role', 1 );

		$updated_user = array(
			'id'          => $user->ID,
			'first_name'  => $user->first_name,
			'last_name'   => $user->last_name,
			'email'       => $user->user_email,
			'permissions' => self::get_user_permissions( $user ),
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $updated_user,
				'message' => __( 'Manager added successfully.', 'everest-forms' ),
			),
			200
		);
	}


	public static function get_user_permissions( $user = false ) {
		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		if ( ! $user ) {
			return array();
		}

		$permission_set = self::get_evf_permissions();
		$is_admin       = self::is_admin( $user );
		// $capability    = self::find_user_capability( $user );

		if ( $is_admin ) {
			if ( $is_admin ) {
				$permission_set[] = 'administrator';
			}

			return $permission_set;
		}

		$user_permissions = array_values( array_intersect( array_keys( $user->allcaps ), array_keys( $permission_set['permissions'] ) ) );

		return apply_filters( 'everest_forms_current_user_permissions', $user_permissions );
	}

	public static function is_admin( $user = false ) {
		if ( $user ) {
			return $user->has_cap( 'manage_options' );
		} else {
			return current_user_can( 'manage_options' );
		}
	}

	public static function attach_permission( $user, $assigned_permission ) {
		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		if ( ! $user ) {
			return false;
		}

		if ( user_can( $user, 'manage_options' ) ) {
			return $user;
		}

		$all_permissions = self::get_evf_permissions();

		foreach ( $all_permissions['permissions'] as $permission => $name ) {
			error_log( print_r( $permission, true ) );
			$user->remove_cap( $permission );
		}

		$assigned_permission = array_intersect( array_keys( $all_permissions['permissions'] ), $assigned_permission );

		foreach ( $assigned_permission as $permission ) {
			$user->add_cap( $permission );
		}

		return $user;
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

		return $capabilities;
	}

	public static function get_managers( $attributes = array() ) {
		error_log( print_r( 'get_managers', true ) );
		$limit  = 10;
		$page   = 1;
		$offset = $page == 1 ? 0 : ( $page - 1 ) * $limit;

		$query = new \WP_User_Query(
			array(
				'meta_key'     => '_everest_forms_has_role',
				'meta_value'   => 1,
				'meta_compare' => '=',
				'number'       => $limit,
				'offset'       => $offset,
			)
		);

		$managers = array();

		foreach ( $query->get_results() as $user ) {
			$managers[] = array(
				'id'          => $user->ID,
				'first_name'  => $user->first_name,
				'last_name'   => $user->last_name,
				'email'       => $user->user_email,
				'permissions' => self::get_user_permissions( $user ),
				'roles'       => self::get_user_roles( $user->roles ),
			);
		}

		$total = $query->get_total();

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'managers'    => $managers,
				'total'       => $total,
				'permissions' => self::get_evf_permissions(),
			),
			200
		);
	}

	private static function get_user_roles( $roles ) {
		$role_str = '';
		if ( count( $roles ) > 1 ) {
			foreach ( $roles as $role ) {
				$role_str .= $role . ', ';
			}
		} else {
			$role_str = $roles[0];
		}

		return ucfirst( $role_str );
	}

	public static function remove_managers( $request ) {
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

		$user_id = $requested_data['user_id'];
		$user    = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Associate user could not be found.', 'everest-forms' ),
				),
				200
			);
		}

		self::attach_permission( $user, array() );

		delete_user_meta( $user->ID, '_everest_forms_has_role' );

		$deleted_user = array(
			'id'          => $user->ID,
			'first_name'  => $user->first_name,
			'last_name'   => $user->last_name,
			'email'       => $user->user_email,
			'permissions' => self::get_user_permissions( $user ),
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $deleted_user,
				'message' => __( 'Manager deleted successfully.', 'everest-forms' ),
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
	public static function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}
}
