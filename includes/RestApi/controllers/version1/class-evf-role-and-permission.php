<?php
/**
 * Roles and Permission controller class.
 *
 * @since 3.0.8
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
	 * @since 3.0.8
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
				'methods'             => 'POST',
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
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk-remove-managers',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'bulk_remove_managers' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
	}

	/**
	 * Assign permissions based on role.
	 *
	 * @since 3.0.8
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public static function assign_permission_based_on_role( $request ) {
		if ( ! isset( $request['request'] ) || empty( $request['request'] ) ) {

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Request data not found.', 'everest-forms' ),
				),
				200
			);
		}
		global $wp_roles;

		$requested_data = $request['request'];

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$checked_roles = isset( $requested_data['checked_roles'] ) && ! empty( $requested_data['checked_roles'] ) ? $requested_data['checked_roles'] : array();

		if ( is_array( $checked_roles ) ) {
			foreach ( $checked_roles as $role => $checked ) {
				$permission = self::get_evf_permissions();
				if ( $checked ) {
					if ( 'subscriber' == strtolower( $role ) ) {
						return new \WP_REST_Response(
							array(
								'success' => false,
								'message' => esc_html__( 'Sorry, you can not give access to the Subscriber role.', 'everest-forms' ),
							),
							200
						);
					}
					$wp_role = $wp_roles->get_role( $role );

					foreach ( array_keys( $permission['permissions'] ) as $value ) {
						$wp_role->add_cap( $value );
					}
				} else {
					$wp_role = $wp_roles->get_role( $role );

					foreach ( array_keys( $permission['permissions'] ) as $value ) {
						$wp_role->remove_cap( $value );
					}
				}
			}
		}

		update_option( '_everest_forms_permission', $checked_roles, 'no' );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => esc_html__( 'Successfully role saved.', 'everest-forms' ),
			),
			200
		);
	}

	/**
	 * Get WordPress roles.
	 *
	 * @since 3.0.8
	 *
	 * @return void
	 */
	public static function get_wp_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$permissions = self::get_evf_permissions();

		$roles              = array();
		$ignore_roles       = apply_filters( 'everest_forms_ignore_roles_to_give_permissions', array( 'administrator', 'subscriber' ) );
		$role_based_list    = get_option( '_everest_forms_permission', array() );
		$checked_roles_list = array();

		if ( ! empty( $role_based_list ) ) {
			foreach ( $role_based_list as $role => $checked ) {
				if ( $checked ) {
					$checked_roles_list[] = $role;
				}
			}
		}

		foreach ( $wp_roles->roles as $key => $value ) {
			if ( ! in_array( $key, $ignore_roles ) ) {
				$roles['roles'][ $key ] = array(
					'name'    => $value['name'],
					'checked' => in_array( $key, $checked_roles_list ),
				);
			}
		}

		$roles['permission'] = $permissions;

		wp_send_json_success( $roles );
	}

	/**
	 * Add user manager.
	 *
	 * @since 3.0.8
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
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

		$user_emails         = isset( $requested_data['user_email'] ) ? ( empty( $requested_data['user_email'] ) ? '' : explode( ',', $requested_data['user_email'] ) ) : '';
		$assigned_permission = isset( $requested_data['assigned_permission'] ) && ! empty( $requested_data['assigned_permission'] ) ? $requested_data['assigned_permission'] : array();

		if ( empty( $user_emails ) && empty( $assigned_permission ) ) {
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

		if ( empty( $user_emails ) ) {
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

		$users_data     = array();
		$user_not_found = array();

		foreach ( $user_emails as $user_email ) {
			$per_user_data = get_user_by( 'email', trim( $user_email ) );

			$current_user = wp_get_current_user();

			if ( $current_user->user_email === $user_email ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => array(
							'user_email' => esc_html__( 'Assigning permissions to yourself is not allowed.', 'everest-forms' ),
						),

					),
					200
				);
			}

			if ( empty( $per_user_data ) ) {
				$user_not_found[] = trim( $user_email );
			}

			$users_data[] = $per_user_data;
		}

		if ( ! empty( $user_not_found ) ) {
			$not_found_user_emails = implode( ', ', $user_not_found );
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => array(
						'user_email' => esc_html__( 'User not found with ' . $not_found_user_emails . ' emails.', 'everest-forms' ),
					),

				),
				200
			);
		}

		foreach ( $users_data as $user ) {
			self::attach_permission( $user, $assigned_permission );

			update_user_meta( $user->ID, '_everest_forms_has_role', 1 );

			$updated_user = array(
				'id'          => $user->ID,
				'first_name'  => $user->first_name,
				'last_name'   => $user->last_name,
				'email'       => $user->user_email,
				'permissions' => self::get_user_permissions( $user ),
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $updated_user,
				'message' => __( 'Manager added successfully.', 'everest-forms' ),
			),
			200
		);
	}

	/**
	 * Get user permissions.
	 *
	 * @since 3.0.8
	 *
	 * @param mixed $user User object or user ID.
	 * @return array User permissions.
	 */
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
			return array_keys( $permission_set['permissions'] );
		}

		$user_permissions = array_values( array_intersect( array_keys( $user->allcaps ), array_keys( $permission_set['permissions'] ) ) );

		return apply_filters( 'everest_forms_current_user_permissions', $user_permissions );
	}

	/**
	 * Check if the user is an admin.
	 *
	 * @since 3.0.8
	 *
	 * @param mixed $user User object or user ID.
	 * @return bool True if the user is an admin, false otherwise.
	 */
	public static function is_admin( $user = false ) {
		if ( $user ) {
			return $user->has_cap( 'manage_options' );
		} else {
			return current_user_can( 'manage_options' );
		}
	}

	/**
	 * Attach permissions to a user.
	 *
	 * @since 3.0.8
	 *
	 * @param mixed $user User object or user ID.
	 * @param array $assigned_permission List of permissions to assign.
	 * @return mixed User object on success, false on failure.
	 */
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
			$user->remove_cap( $permission );
		}

		$assigned_permission = array_intersect( array_keys( $all_permissions['permissions'] ), $assigned_permission );

		foreach ( $assigned_permission as $permission ) {
			$user->add_cap( $permission );
		}

		return $user;
	}

	/**
	 * Get EVF permissions.
	 *
	 * @since 3.0.8
	 *
	 * @return array List of EVF permissions.
	 */
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

	/**
	 * Retrieves a list of managers based on the provided attributes.
	 *
	 * @since 3.0.8
	 *
	 * @param array $attributes {
	 *     Array of attributes for querying managers.
	 *
	 *     @type array $request {
	 *         Request parameters.
	 *
	 *         @type int    $page_size       Number of managers to retrieve.
	 *         @type int    $offset          Offset for the query.
	 *         @type string $search_manager  Optional. Search term to filter managers by username, email, or display name.
	 *     }
	 * }
	 * @return WP_REST_Response Response object containing the list of managers and additional data.
	 */
	public static function get_managers( $attributes = array() ) {
		$limit          = $attributes['request']['page_size'];
		$offset         = $attributes['request']['offset'];
		$search_manager = isset( $attributes['request']['search_manager'] ) ? esc_attr( $attributes['request']['search_manager'] ) : '';

		$query_args = array(
			'meta_key'     => '_everest_forms_has_role',
			'meta_value'   => 1,
			'meta_compare' => '=',
			'number'       => $limit,
			'offset'       => $offset,
		);

		if ( ! empty( $search_manager ) ) {
			$query_args['search']         = '*' . esc_attr( $search_manager ) . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$query = new \WP_User_Query( $query_args );

		$managers = array();

		foreach ( $query->get_results() as $user ) {
			$managers[] = array(
				'id'          => $user->ID,
				'first_name'  => $user->first_name,
				'last_name'   => $user->last_name,
				'email'       => $user->user_email,
				'permissions' => self::get_user_permissions( $user ),
				'roles'       => self::get_user_roles( $user->ID ),
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

	/**
	 * Get user roles as a string.
	 *
	 * @since 3.0.8
	 *
	 * @param array $roles Array of user roles.
	 * @return string User roles as a comma-separated string.
	 */
	private static function get_user_roles( $user_id ) {
		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;
		return ucfirst( reset( $user_roles ) );
	}

	/**
	 * Remove a manager.
	 *
	 * @since 3.0.8
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
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
	 * Remove multiple managers.
	 *
	 * @since 3.0.8
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public static function bulk_remove_managers( $request ) {
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

		$user_ids = $requested_data['user_ids'];

		if ( empty( $user_ids ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Please select user.', 'everest-forms' ),
				),
				200
			);
		}

		foreach ( $user_ids as $ID ) {
			$user = get_user_by( 'ID', $ID );

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

		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Managers deleted successfully.', 'everest-forms' ),
			),
			200
		);
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @since 3.0.8
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}
}
