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

		// TODO: REMOVE — Temporary PHP fallback for AI form creation.
		// The Python AI backend will handle field generation and call the EVF
		// create-from-ai endpoint (or a native EVF save endpoint) directly.
		// Once the Python API is integrated end-to-end, delete this route
		// registration AND the create_from_ai() method below.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-from-ai',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_from_ai' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);
		// Render a pixel-perfect builder-canvas preview of an AI form schema.
		// Uses the SAME schema builder + field renderer as create-from-ai, so the
		// preview is identical to what the builder shows after import.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ai-preview',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_ai_preview' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);
		// END TODO: REMOVE

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/favorite',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_favorite_action' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/favorite_forms',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_get_favorites' ),
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
		$template_url      = 'https://assets.wpeverest.com/everestforms/forms/';
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

		// When "Edit with AI" is used, create the form as a DRAFT so it only
		// becomes a real form once the user clicks "Use This Form" in the AI flow.
		$draft = evf_string_to_bool( $request->get_param( 'draft' ) );
		$args  = $draft ? array( 'post_status' => 'draft' ) : array();

		// Create the form using the title and slug.
		$form_id = evf()->form->create( $title, $slug, $args );

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



	// TODO: REMOVE — See route registration note above.
	// This entire method is a temporary PHP bridge while the Python AI API is
	// being built. When the Python backend is live, remove this method and its
	// route registration, and point the frontend directly at the Python endpoint
	// (or whichever native EVF endpoint replaces it).
	/**
	 * Create a form from AI-generated field definitions.
	 *
	 * Accepts a simplified field list from the AI creation UI, builds a blank
	 * EVF form, injects the field data, and returns the builder redirect URL.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_from_ai( WP_REST_Request $request ) {
		$title  = sanitize_text_field( wp_unslash( $request->get_param( 'title' ) ) );
		$fields = $request->get_param( 'fields' );

		if ( empty( $title ) ) {
			return new WP_Error( 'invalid_title', __( 'Form title is required.', 'everest-forms' ), array( 'status' => 400 ) );
		}

		// Prevent KSES / content filters from corrupting the JSON — same pattern as EVF_Form_Handler::create().
		$has_kses = false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' );
		if ( $has_kses ) {
			kses_remove_filters();
		}
		$has_link_rel = false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' );
		if ( $has_link_rel ) {
			wp_remove_targeted_link_rel_filters();
		}

		// Build the form content (fields, structure, settings) from the AI schema.
		// This is the SINGLE source of truth shared with the ai-preview endpoint.
		$form_content = $this->build_form_content( $title, is_array( $fields ) ? $fields : array() );

		// Insert the post.
		$form_id = wp_insert_post( array(
			'post_title'   => esc_html( $title ),
			'post_status'  => 'publish',
			'post_type'    => 'everest_form',
			'post_content' => '{}',
		) );

		if ( ! $form_id || is_wp_error( $form_id ) ) {
			if ( $has_kses )    kses_init_filters();
			if ( $has_link_rel ) wp_init_targeted_link_rel_filters();
			return new WP_Error( 'form_creation_failed', __( 'Could not create form.', 'everest-forms' ), array( 'status' => 500 ) );
		}

		$form_content['id'] = $form_id;

		wp_update_post( array(
			'ID'           => $form_id,
			'post_title'   => esc_html( $title ),
			'post_content' => evf_encode( $form_content ),
		) );

		// Restore filters.
		if ( $has_kses )    kses_init_filters();
		if ( $has_link_rel ) wp_init_targeted_link_rel_filters();

		do_action( 'everest_forms_create_form', $form_id, $form_content, array(), false );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id'       => $form_id,
					'redirect' => add_query_arg(
						array(
							'tab'     => 'fields',
							'form_id' => $form_id,
						),
						admin_url( 'admin.php?page=evf-builder' )
					),
				),
			),
			200
		);
	}

	/**
	 * Build an EVF form_content array from an AI field schema.
	 *
	 * Shared by create_from_ai() (import) and get_ai_preview() (preview) so the
	 * preview the user approves is byte-for-byte the form that gets created.
	 *
	 * Any registered field type is supported (free or Pro/locked); Pro fields
	 * are included so they render as a locked upsell in both the preview and the
	 * builder. Each field is seeded with its field class defaults (e.g. choices)
	 * so its settings render realistically.
	 *
	 * @param string $title  Form title.
	 * @param array  $fields AI field definitions ( type, label, required, placeholder ).
	 * @return array Form content ( form_fields, structure, settings, ... ).
	 */
	public function build_form_content( $title, $fields ) {
		// Legacy AI type aliases → EVF field types. Any type already registered
		// in EVF is used as-is, so all Pro/addon field slugs work out of the box.
		$type_map = array(
			'date'    => 'date-time',
			'name'    => 'first-name',
			'tel'     => 'phone',
			'url'     => 'url',
		);

		$registered = evf()->form_fields->get_form_field_types();

		$form_fields = array();
		$field_index = 1;

		foreach ( $fields as $field_def ) {
			$ai_type = isset( $field_def['type'] ) ? sanitize_key( $field_def['type'] ) : 'text';

			if ( in_array( $ai_type, $registered, true ) ) {
				$evf_type = $ai_type;
			} elseif ( isset( $type_map[ $ai_type ] ) && in_array( $type_map[ $ai_type ], $registered, true ) ) {
				$evf_type = $type_map[ $ai_type ];
			} else {
				$evf_type = 'text';
			}

			$label = isset( $field_def['label'] ) ? sanitize_text_field( $field_def['label'] ) : '';

			if ( empty( $label ) ) {
				continue;
			}

			// Field ID: 10-char hash + index, matching EVF's evf_get_random_string() pattern.
			$field_id = substr( md5( uniqid( $label, true ) ), 0, 10 ) . '-' . $field_index;
			$meta_key = sanitize_key( str_replace( ' ', '_', strtolower( $label ) ) ) . '_' . wp_rand( 1000, 9999 );

			$field = array(
				'id'                 => $field_id,
				'type'               => $evf_type,
				'label'              => $label,
				'meta-key'           => $meta_key,
				'description'        => '',
				'required'           => ! empty( $field_def['required'] ) ? '1' : '',
				'placeholder'        => isset( $field_def['placeholder'] ) ? sanitize_text_field( $field_def['placeholder'] ) : '',
				'css'                => '',
				'conditional_option' => 'show',
				'conditionals'       => array(
					'1' => array(
						'1' => array(
							'field'    => '---Select Field---',
							'operator' => 'is',
							'value'    => '',
						),
					),
				),
			);

			// Seed the field class defaults (e.g. choices for select/radio/checkbox)
			// so the rendered settings look complete and the form works once unlocked.
			$field = $this->seed_field_defaults( $evf_type, $field );

			/**
			 * Filter an individual AI-generated field's data before it is added.
			 *
			 * @since 3.2.0
			 *
			 * @param array  $field     The built EVF field data.
			 * @param array  $field_def The raw AI field definition.
			 * @param string $evf_type  Resolved EVF field type.
			 */
			$form_fields[ $field_id ] = apply_filters( 'everest_forms_ai_field_data', $field, $field_def, $evf_type );

			$field_index++;
		}

		// Build structure: one field per row, single grid column — matches the builder canvas layout.
		$structure   = array();
		$row_counter = 1;
		foreach ( array_keys( $form_fields ) as $fid ) {
			$structure[ 'row_' . $row_counter ] = array( 'grid_1' => array( $fid ) );
			$row_counter++;
		}

		return array(
			'id'            => 0, // Set after insert (0 for preview).
			'form_enabled'  => '1',
			'form_field_id' => $field_index,
			'form_fields'   => $form_fields,
			'structure'     => $structure,
			'settings'      => array(
				'form_title'                          => $title,
				'form_description'                    => '',
				'form_disable_message'                => __( 'This form is disabled.', 'everest-forms' ),
				'successful_form_submission_message'  => __( 'Thanks for contacting us! We will be in touch with you shortly.', 'everest-forms' ),
				'submission_type'                     => 'message',
				'hide_title'                          => '0',
			),
		);
	}

	/**
	 * Seed a field's defaults from its registered field class (e.g. choices).
	 *
	 * @param string $evf_type EVF field type.
	 * @param array  $field    Field data being built.
	 * @return array Field data with defaults merged in.
	 */
	protected function seed_field_defaults( $evf_type, $field ) {
		$field_obj = $this->get_field_object( $evf_type );

		if ( $field_obj && ! empty( $field_obj->defaults ) && is_array( $field_obj->defaults ) ) {
			$field['choices'] = $field_obj->defaults;
		}

		return $field;
	}

	/**
	 * Locate the registered field object for a given type.
	 *
	 * @param string $evf_type EVF field type.
	 * @return EVF_Form_Fields|null
	 */
	protected function get_field_object( $evf_type ) {
		foreach ( evf()->form_fields->form_fields() as $group_fields ) {
			foreach ( $group_fields as $field_obj ) {
				if ( isset( $field_obj->type ) && $field_obj->type === $evf_type ) {
					return $field_obj;
				}
			}
		}

		return null;
	}

	/**
	 * Render a pixel-perfect builder-canvas preview of an AI form schema.
	 *
	 * Builds the form content with build_form_content() and renders it through
	 * the builder's own EVF_Builder_Fields::output_fields_preview(), guaranteeing
	 * the preview is identical to the post-import builder canvas (locked Pro
	 * fields included).
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_ai_preview( WP_REST_Request $request ) {
		$form_id = absint( $request->get_param( 'form_id' ) );

		if ( $form_id ) {
			// Preview an existing (AI-generated draft) form by its stored content,
			// so the preview is byte-identical to the form that gets activated.
			$post = get_post( $form_id );
			if ( ! $post || 'everest_form' !== $post->post_type ) {
				return new WP_Error( 'invalid_form', __( 'Form not found.', 'everest-forms' ), array( 'status' => 404 ) );
			}
			$form_content = evf_decode( $post->post_content );
			if ( empty( $form_content ) || ! is_array( $form_content ) ) {
				return new WP_Error( 'invalid_form', __( 'Form content is empty.', 'everest-forms' ), array( 'status' => 422 ) );
			}
		} else {
			$title  = sanitize_text_field( wp_unslash( (string) $request->get_param( 'title' ) ) );
			$fields = $request->get_param( 'fields' );

			if ( empty( $title ) ) {
				$title = __( 'AI Generated Form', 'everest-forms' );
			}

			$form_content = $this->build_form_content( $title, is_array( $fields ) ? $fields : array() );
		}

		// Load the builder field renderer (mirrors EVF_Admin_Builder bootstrap).
		if ( ! class_exists( 'EVF_Builder_Fields', false ) ) {
			include_once dirname( EVF_PLUGIN_FILE ) . '/includes/admin/builder/class-evf-builder-page.php';
			include_once dirname( EVF_PLUGIN_FILE ) . '/includes/admin/builder/class-evf-builder-fields.php';
		}

		if ( ! class_exists( 'EVF_Builder_Fields', false ) ) {
			return new WP_Error( 'preview_unavailable', __( 'Form preview is unavailable.', 'everest-forms' ), array( 'status' => 500 ) );
		}

		$builder            = new EVF_Builder_Fields();
		$builder->form_data = $form_content;

		// Read-only, edit-chrome-free render (same per-field markup as the builder).
		$html = $builder->render_ai_preview( $form_content );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'html' => $html,
				),
			),
			200
		);
	}
	// END TODO: REMOVE (create_from_ai method)

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
		if ( ! current_user_can( 'manage_everest_forms' ) && ! current_user_can( 'everest_forms_create_forms' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You are not allowed to access this resource.', 'everest-forms' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Retrieves the favorite forms of user.
	 *
	 * @since 3.0.8
	 *
	 * @param  WP_REST_Request $request
	 */
	public function handle_get_favorites( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_REST_Response( 'User not logged in.', 401 );
		}

		$user_favorites = get_option( 'user_favorites' );
		if ( ! is_array( $user_favorites ) || ! isset( $user_favorites[ $user_id ] ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		return new WP_REST_Response( $user_favorites[ $user_id ], 200 );
	}
}
