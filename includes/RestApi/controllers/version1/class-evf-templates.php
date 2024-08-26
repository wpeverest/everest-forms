<?php
/**
 * Template Section Data Controller.
 *
 * @since x.x.x
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
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_templates_data' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
			)
		);
	}

	/**
	 * Get Template Lists.
	 *
	 * @since x.x.x
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_templates_data() {
		$template_data = self::get_templates_data_list();

		if ( empty( $template_data ) ) {
			return new WP_Error( 'no_templates', __( 'No templates found', 'everest-forms' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $template_data );
	}

	/**
	 * Get Templates Data List.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function get_templates_data_list() {
		$extension_data = evf_get_json_file_contents( 'assets/templates-json/templates.json' );
		return apply_filters( 'everest_forms_templates_section_data', $extension_data );
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
