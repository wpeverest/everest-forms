<?php
/**
 * Blocks controller class.
 *
 * @since 2.0.8.1
 *
 * @package  EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * UR_AddonsClass
 */
class EVF_Gutenberg_Blocks {

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
	protected $rest_base = 'gutenberg-blocks';

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
			'/' . $this->rest_base . '/frontend-listing-list',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'evf_get_fronend_listing_list' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
	}
	/**
	 * Get Fronend Listing Lists.
	 *
	 * @since 2.0.8.1
	 *
	 * @return array Addon lists.
	 */
	public static function evf_get_fronend_listing_list() {
		$args           = array(
			'post_type'   => 'ef_frontend_listings',
			'post_status' => 'public',
		);
		$frontend_lists = get_posts( $args );
		$frontend_list  = array();
		foreach ( $frontend_lists as $frontend ) {
			$frontend_list[ $frontend->ID ] = $frontend->post_title;
		}
		return new \WP_REST_Response(
			array(
				'success'        => true,
				'frontend_lists' => $frontend_list,
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
