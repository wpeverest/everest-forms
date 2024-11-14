<?php
/**
 * Template Section Data Controller.
 *
 * @since 3.0.3
 *
 * @package EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everest_Forms_Template_Section_Data Class.
 */
class Everest_Forms_Template_Section_Data {

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
	protected $rest_base = 'templates';

	/**
	 * Register routes.
	 *
	 * @since 3.0.3
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_templates_data' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_templates' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/favorite',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_favorite_action' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);
	}

	/**
	 * Add or Remove templates from favourites.
	 *
	 * @since 3.0.3
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function handle_favorite_action( WP_REST_Request $request ) {

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_REST_Response( 'User not logged in.', 401 );
		}

		$action = $request->get_param( 'action' );
		$slug   = sanitize_text_field( $request->get_param( 'slug' ) );

		$user_favorites = get_option( 'user_favorites' );

		if ( ! is_array( $user_favorites ) ) {
			$user_favorites = array();
		}

		if ( ! isset( $user_favorites[ $user_id ] ) ) {
			$user_favorites[ $user_id ] = array();
		}

		if ( 'add_favorite' === $action ) {
			if ( ! in_array( $slug, $user_favorites[ $user_id ] ) ) {
				$user_favorites[ $user_id ][] = $slug;
			}
		} elseif ( 'remove_favorite' === $action ) {
			if ( ( $key = array_search( $slug, $user_favorites[ $user_id ] ) ) !== false ) {
				unset( $user_favorites[ $user_id ][ $key ] );
			}
		} else {
			return new WP_REST_Response( 'Invalid action.', 400 );
		}

		update_option( 'user_favorites', $user_favorites );

		return new WP_REST_Response( 'Favorite updated successfully.', 200 );
	}

	/**
	 * Get Template Lists.
	 *
	 * @since 3.0.3
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_templates_data( WP_REST_Request $request ) {

		$headers      = $request->get_headers( 'cookie' );
		$url          = isset( $headers['referer'][0] ) ? $headers['referer'][0] : '';
		$parsed_url   = parse_url( $url );
		$query_string = isset( $parsed_url['query'] ) ? $parsed_url['query'] : '';
		$query_params = array();
		parse_str( $query_string, $query_params );
		if ( isset( $query_params['refresh'] ) ) {
			delete_transient( 'everest_forms_templates_data' );
		}
		$template_url      = 'https://d3m99fsxk070py.cloudfront.net/';
		$template_json_url = $template_url . 'templates1.json';
		$transient_key     = 'everest_forms_templates_data';
		$cache_expiration  = DAY_IN_SECONDS;

		$template_data = get_transient( $transient_key );

		if ( false === $template_data ) {
			try {
				$response = wp_remote_get( $template_json_url );

				if ( is_wp_error( $response ) ) {
					return new WP_Error( 'http_request_failed', __( 'Failed to fetch templates.', 'everest-forms' ) );
				}

				$content_json  = wp_remote_retrieve_body( $response );
				$template_data = json_decode( $content_json );

				if ( empty( $template_data ) ) {
					return new WP_Error( 'no_templates', __( 'No templates found.', 'everest-forms' ), array( 'status' => 404 ) );
				}

				set_transient( $transient_key, $template_data, $cache_expiration );
			} catch ( Exception $e ) {
				return new WP_Error( 'exception_occurred', __( 'An error occurred while fetching templates.', 'everest-forms' ), array( 'status' => 500 ) );
			}
		}

		$folder_path = untrailingslashit( plugin_dir_path( EVF_PLUGIN_FILE ) . '/assets/images/templates' );

		foreach ( $template_data as $templates ) {
			foreach ( $templates as $template ) {
				foreach ( $template->templates as $temp ) {
					$image_url      = isset( $temp->image ) ? $temp->image : ( $template_url . 'images/' . $temp->slug . '.png' );
					$temp->imageUrl = $image_url;

					$temp_name     = explode( '/', $image_url );
					$relative_path = $folder_path . '/' . end( $temp_name );
					$exists        = file_exists( $relative_path );

					if ( $exists ) {
						$temp->imageUrl = untrailingslashit( plugin_dir_url( EVF_PLUGIN_FILE ) ) . '/assets/images/templates/' . $temp->slug . '.png';
					}

					$user_id = get_current_user_id();
					if ( $user_id ) {
						$user_favorites = get_option( 'user_favorites', array() );
						$favorite_slugs = isset( $user_favorites[ $user_id ] ) ? $user_favorites[ $user_id ] : array();

						if ( in_array( $temp->slug, $favorite_slugs ) && ! in_array( 'Favorites', $temp->categories ) ) {
							array_unshift( $temp->categories, 'Favorites' );
						}
					}
				}
			}
		}

		return rest_ensure_response( $template_data );
	}




	/**
	 * Get Templates Data List.
	 *
	 * @since 3.0.3
	 *
	 * @return array
	 */
	public static function get_templates_data_list() {
		$extension_data = evf_get_json_file_contents( 'assets/templates-json/templates.json' );
		return apply_filters( 'everest_forms_templates_section_data', $extension_data );
	}

	/**
	 * Create a Template.
	 *
	 * @since 3.0.3
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function create_templates( WP_REST_Request $request ) {
		// Retrieve and sanitize parameters.
		$title = sanitize_text_field( wp_unslash( $request->get_param( 'title' ) ) );
		$slug  = sanitize_text_field( wp_unslash( $request->get_param( 'slug' ) ) );

		// Check if the title parameter is empty.
		if ( empty( $title ) ) {
			return new WP_Error(
				'invalid_template_name',
				__( 'The template name is required and cannot be empty.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		// Ensure the slug is also not empty (optional check based on your needs).
		if ( empty( $slug ) ) {
			return new WP_Error(
				'invalid_template_slug',
				__( 'The template slug is required and cannot be empty.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		// Create the form using the title and slug.
		$form_id = evf()->form->create( $title, $slug );

		// Check if form creation was successful.
		if ( $form_id ) {
			$data = array(
				'id'       => $form_id,
				'redirect' => add_query_arg(
					array(
						'tab'     => 'fields',
						'form_id' => $form_id,
					),
					admin_url( 'admin.php?page=evf-builder' )
				),
			);

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $data,
				),
				200
			);
		} else {
			// Handle the case where form creation failed.
			return new WP_Error(
				'form_creation_failed',
				__( 'Something went wrong, please try again later.', 'everest-forms' ),
				array( 'status' => 500 )
			);
		}
	}



	/**
	 * Check if a given request has access.
	 *
	 * @since 3.0.3
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
