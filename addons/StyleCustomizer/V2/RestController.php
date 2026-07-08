<?php
/**
 * Style Customizer v2 — REST controller.
 *
 * Read/save a form's v2 style record. Registers into the existing `everest-forms/v1`
 * namespace via the `everest_forms_rest_api_get_rest_namespaces` filter (non-invasive —
 * no change to core `EVF_REST_API`). Only wired when `Engine::enabled()`.
 *
 * Security: every route is gated by an admin capability (`permission_callback`); WordPress
 * additionally enforces the REST cookie-nonce for logged-in requests, so this is CSRF-safe
 * without a hand-rolled nonce check. Untrusted input is run through {@see Sanitizer} before
 * it ever touches the option.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * `everest-forms/v1/styles/{id}` controller.
 */
final class RestController {

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'everest-forms/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'styles';

	/**
	 * Hook the controller into EVF's REST registry. Called from {@see Engine::boot()}.
	 */
	public static function register() {
		add_filter( 'everest_forms_rest_api_get_rest_namespaces', array( __CLASS__, 'add_namespace' ) );
	}

	/**
	 * Append this controller to the v1 namespace so `EVF_REST_API` instantiates + registers it.
	 *
	 * @param array $namespaces namespace => class-names.
	 * @return array
	 */
	public static function add_namespace( $namespaces ) {
		$namespaces['everest-forms/v1'][] = __CLASS__;
		return $namespaces;
	}

	/**
	 * Register the routes (called by `EVF_REST_API::register_rest_routes()`).
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'Form ID.', 'everest-forms' ),
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Only users who can manage forms may read/write styles.
	 *
	 * @return bool
	 */
	public function permissions_check() {
		return current_user_can( 'manage_everest_forms' );
	}

	/**
	 * GET — the saved record (or an empty default) plus the schema the panel renders from,
	 * so the client never hardcodes the token contract.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) {
		$form_id = absint( $request['id'] );
		$all     = get_option( 'everest_forms_styles', array() );
		$record  = ( isset( $all[ $form_id ] ) && Engine::is_v2_record( $all[ $form_id ] ) )
			? $all[ $form_id ]
			: array(
				'schema_version' => Schema::version(),
				'tokens'         => array(),
			);

		return rest_ensure_response(
			array(
				'form_id'        => $form_id,
				'schema_version' => Schema::version(),
				'schema'         => Schema::tokens(),
				'sections'       => Schema::sections(),
				'palettes'       => Schema::palettes(),
				'record'         => $record,
			)
		);
	}

	/**
	 * POST — sanitize and persist the record. Optimistic-concurrency: if the caller sends a
	 * stale `base_updated_at`, reject with 409 so it can reload rather than clobber.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_item( $request ) {
		$form_id = absint( $request['id'] );

		// The id must be a real form — never litter the option with junk ids.
		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return new \WP_Error(
				'evf_style_no_form',
				__( 'Form not found.', 'everest-forms' ),
				array( 'status' => 404 )
			);
		}

		// Require an explicit record object. A missing/invalid body must never silently
		// wipe a form's styles, so reject rather than save an empty record.
		$incoming = $request->get_param( 'record' );
		if ( ! is_array( $incoming ) ) {
			return new \WP_Error(
				'evf_style_bad_request',
				__( 'Missing or invalid "record".', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		$all          = get_option( 'everest_forms_styles', array() );
		$base_updated = $request->get_param( 'base_updated_at' );

		if ( isset( $all[ $form_id ]['_updated_at'] ) && null !== $base_updated
			&& (int) $all[ $form_id ]['_updated_at'] !== (int) $base_updated ) {
			return new \WP_Error(
				'evf_style_conflict',
				__( 'These styles were changed somewhere else. Reload before saving.', 'everest-forms' ),
				array( 'status' => 409 )
			);
		}

		$clean           = Sanitizer::sanitize_record( $incoming );
		$all[ $form_id ] = $clean;
		update_option( 'everest_forms_styles', $all, false ); // autoload=no.

		return rest_ensure_response(
			array(
				'saved'       => true,
				'record'      => $clean,
				'_updated_at' => $clean['_updated_at'],
			)
		);
	}
}
