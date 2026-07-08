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

		// Create a user template from the current styles.
		register_rest_route(
			$this->namespace,
			'/style-templates',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_template' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// Delete a user template.
		register_rest_route(
			$this->namespace,
			'/style-templates/(?P<tid>[A-Za-z0-9\-]+)',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_template' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * POST /style-templates — save the current styles as a reusable user template.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_template( $request ) {
		$name     = $request->get_param( 'name' );
		$incoming = $request->get_param( 'record' );
		if ( ! is_array( $incoming ) ) {
			return new \WP_Error(
				'evf_style_bad_request',
				__( 'Missing or invalid "record".', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}
		// Sanitize the incoming styles before persisting them as a template.
		$clean    = Sanitizer::sanitize_record( $incoming );
		$template = Templates::save_user_template( $name, $clean );

		return rest_ensure_response(
			array(
				'saved'    => true,
				'template' => $template,
			)
		);
	}

	/**
	 * DELETE /style-templates/{tid} — remove a user template.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function delete_template( $request ) {
		$deleted = Templates::delete_user_template( (string) $request['tid'] );
		return rest_ensure_response( array( 'deleted' => (bool) $deleted ) );
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
	 * Detect which named palette a legacy record's `color_palette` selection matches, so the
	 * panel can show it as the active palette. Returns an empty string if it matches none
	 * (a custom palette) — the six colours still migrate onto their tokens regardless.
	 *
	 * @param array $legacy Legacy record.
	 * @return string Palette id, or ''.
	 */
	protected static function detect_palette( $legacy ) {
		if ( empty( $legacy['color_palette'] ) || ! is_array( $legacy['color_palette'] ) ) {
			return '';
		}
		$colors = reset( $legacy['color_palette'] );
		if ( ! is_array( $colors ) ) {
			return '';
		}
		$norm = static function ( $v ) {
			return strtolower( trim( (string) $v ) );
		};
		foreach ( Schema::palettes() as $palette ) {
			$match = true;
			foreach ( $palette['colors'] as $slot => $value ) {
				if ( ! isset( $colors[ $slot ] ) || $norm( $colors[ $slot ] ) !== $norm( $value ) ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				return $palette['id'];
			}
		}
		return '';
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
		$stored  = isset( $all[ $form_id ] ) && is_array( $all[ $form_id ] ) ? $all[ $form_id ] : array();

		if ( Engine::is_v2_record( $stored ) ) {
			// Already a v2 record — serve it as-is.
			$record = $stored;
		} elseif ( ! empty( $stored ) ) {
			// A legacy (v1) record: migrate on read so an existing styled form opens with its
			// real styles intact (backward compatibility). The migration is lossless (renames
			// settings, never reshapes values); it is only persisted when the user saves.
			$record            = Migrator::migrate_record( $stored );
			$record['palette'] = self::detect_palette( $stored );
		} else {
			// Never styled — an empty v2 default record.
			$record = array(
				'schema_version' => Schema::version(),
				'tokens'         => array(),
			);
		}

		return rest_ensure_response(
			array(
				'form_id'        => $form_id,
				'schema_version' => Schema::version(),
				'schema'         => Schema::tokens(),
				'sections'       => Schema::sections(),
				'palettes'       => Schema::palettes(),
				'palette_map'    => Schema::palette_map(),
				'templates'      => Templates::all(),
				'user_templates' => Templates::user_templates(),
				'breakpoints'    => Compiler::breakpoints(),
				/**
				 * Whether the Pro tier is active. Defaults to whether Everest Forms Pro is loaded
				 * (its `EFP_PLUGIN_FILE` constant), so pro-tier tokens unlock automatically when Pro
				 * is active; in free they render locked with the upgrade prompt. Filterable for
				 * licence-aware gating.
				 *
				 * @param bool $pro_active Default: Pro plugin active.
				 */
				'pro_active'     => (bool) apply_filters( 'evf_style_v2_pro_active', defined( 'EFP_PLUGIN_FILE' ) ),
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

		// Back up a legacy record ONCE before v2 first overwrites it, so a rollback is always
		// possible (plan §5). Only the very first v2 save (when the stored record is still
		// legacy) triggers the backup; subsequent v2 saves leave the backup untouched.
		if ( isset( $all[ $form_id ] ) && ! Engine::is_v2_record( $all[ $form_id ] ) ) {
			$backups = get_option( 'everest_forms_styles_legacy_backup', array() );
			if ( ! isset( $backups[ $form_id ] ) ) {
				$backups[ $form_id ] = $all[ $form_id ];
				update_option( 'everest_forms_styles_legacy_backup', $backups, false );
			}
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
