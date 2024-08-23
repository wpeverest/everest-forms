<?php
/**
 * Changelog Controller.
 *
 * @since x.x.x
 *
 * @package  EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Changelog Class.
 */
class everest_forms_template_section_data {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string The namespace of this controller's route.
	 */
	protected $namespace = 'everest-forms/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string The base of this controller's route.
	 */
	protected $rest_base = 'templates';



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
			'/' . $this->rest_base,
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_templates_data' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
	}

	/**
	 * Get Template Lists.
	 *
	 * @since 3.0.0
	 *
	 * @return array Module lists.
	 */
	public function get_templates_data() {
		$template_data = get_transient( 'evf_template_section_list' );
		$template_url  = 'https://d3m99fsxk070py.cloudfront.net/';

		if ( false === $template_data ) {
			$template_json_url = $template_url . 'templates.json';
			try {
				$content       = wp_remote_get( $template_json_url );
				$content_json  = wp_remote_retrieve_body( $content );
				$template_data = json_decode( $content_json );
			} catch ( Exception $e ) {
				return new WP_Error( 'template_fetch_failed', __( 'Failed to fetch template data', 'everest-forms' ) );
			}

			$folder_path = untrailingslashit( plugin_dir_path( EVF_PLUGIN_FILE ) . '/assets/images/templates' );
			if ( isset( $template_data->templates ) ) {
				foreach ( $template_data->templates as $template_tuple ) {
					$image_url             = isset( $template_tuple->image ) ? $template_tuple->image : ( $template_url . 'images/' . $template_tuple->slug . '.png' );
					$template_tuple->image = $image_url;
					$temp_name             = explode( '/', $image_url );
					$relative_path         = $folder_path . '/' . end( $temp_name );
					$exists                = file_exists( $relative_path );

					if ( $exists ) {
						$template_tuple->image = untrailingslashit( plugin_dir_url( EVF_PLUGIN_FILE ) ) . '/assets/images/templates/' . untrailingslashit( $template_tuple->slug ) . '.png';
					}
				}
				set_transient( 'evf_template_section_list', $template_data, WEEK_IN_SECONDS );
			}
		}

		return rest_ensure_response( isset( $template_data->templates ) ? apply_filters( 'everest_forms_template_section_data', $template_data->templates ) : self::get_default_template() );
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_permissions( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		// Nonce check.
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permissions to perform this action.', 'everest-forms' ),
				array( 'status' => 403 )
			);
		}
		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You are not allowed to access this resource.', 'everest-forms' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}



}
