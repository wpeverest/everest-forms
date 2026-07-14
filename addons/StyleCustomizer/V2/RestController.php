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

		// Store the builder's CURRENT (possibly unsaved) structure so the live preview can render
		// it — the Fields ↔ Style synchronisation pipeline (see PreviewDraft).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/preview-draft',
			array(
				'args' => array(
					'id' => array(
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_preview_draft' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// AI styling — turn a plain-text prompt into style tokens (see ai_style()).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/ai',
			array(
				'args' => array(
					'id' => array(
						'validate_callback' => static function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'ai_style' ),
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
		// Saving your own template is a Pro feature — reject on a site without Pro, authoritatively
		// (the panel also locks the UI, but the server is the boundary).
		if ( ! Engine::pro_active() ) {
			return new \WP_Error(
				'evf_style_pro_only',
				__( 'Saving custom style templates is a Pro feature.', 'everest-forms' ),
				array( 'status' => 403 )
			);
		}

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
	 * POST /styles/{id}/ai — turn a plain-text prompt into style tokens via the ThemeGrill AI
	 * Cloud gateway (see EVF_AI_API::style_form()). Returns the AI's intent SANITIZED into a
	 * partial v2 record — the panel applies it to the live store for an instant preview and the
	 * user Saves through the existing flow; nothing is persisted here.
	 *
	 * Because {@see Sanitizer::sanitize_record()} is the single authoritative gate (same one
	 * every manual edit and template apply goes through), a malformed or pro-locked AI response
	 * can only ever under-deliver — it can never produce an unsafe or tier-violating record.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function ai_style( $request ) {
		if ( ! class_exists( 'EVF_AI_API' ) || ! class_exists( 'EVF_AI_Registration' ) ) {
			return new \WP_Error(
				'evf_ai_unavailable',
				__( 'AI features are unavailable on this site.', 'everest-forms' ),
				array( 'status' => 501 )
			);
		}

		if ( \EVF_AI_Registration::is_local_site() ) {
			return new \WP_Error(
				'evf_ai_not_registered',
				__( 'AI features are not available on local or staging sites.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		$prompt        = sanitize_textarea_field( (string) $request->get_param( 'prompt' ) );
		$refine_prompt = sanitize_textarea_field( (string) $request->get_param( 'refine_prompt' ) );
		$current       = $request->get_param( 'current_record' );
		$current       = is_array( $current ) ? $current : array();

		if ( '' === $prompt ) {
			return new \WP_Error(
				'evf_style_bad_request',
				__( 'Please describe the look you want.', 'everest-forms' ),
				array( 'status' => 400 )
			);
		}

		// Auto-register on first use — identical pattern to EVF_AI_Ajax::generate_form().
		if ( ! \EVF_AI_Registration::is_registered() ) {
			\EVF_AI_Registration::register();
		}
		if ( ! \EVF_AI_Registration::is_registered() ) {
			return new \WP_Error(
				'evf_ai_not_registered',
				__( 'This site could not be registered with the AI service. Please try again.', 'everest-forms' ),
				array( 'status' => 502 )
			);
		}

		$ai_style = \EVF_AI_API::style_form( $prompt, $current, $refine_prompt );

		if ( is_wp_error( $ai_style ) ) {
			$status = 'rate_limit' === $ai_style->get_error_code() || 'daily_limit_reached' === $ai_style->get_error_code() ? 429 : 502;
			return new \WP_Error( $ai_style->get_error_code(), $ai_style->get_error_message(), array( 'status' => $status ) );
		}

		// The AI's `{ tokens, palette }` shape is already exactly what Sanitizer::sanitize_record()
		// expects as input — tokens as bare values (wrapped into a desktop-only bag) or device
		// bags, palette as a string id. This is the SAME authoritative gate every manual edit and
		// template apply goes through: unknown keys are dropped, values are type/range-clamped,
		// and a pro-tier token or palette is stripped outright on a site without Pro.
		$clean = Sanitizer::sanitize_record(
			array(
				'tokens'  => isset( $ai_style['tokens'] ) && is_array( $ai_style['tokens'] ) ? $ai_style['tokens'] : array(),
				'palette' => isset( $ai_style['palette'] ) ? $ai_style['palette'] : '',
			)
		);

		return rest_ensure_response(
			array(
				'tokens'  => $clean['tokens'],
				'palette' => isset( $clean['palette'] ) ? $clean['palette'] : '',
				'summary' => isset( $ai_style['summary'] ) ? sanitize_text_field( (string) $ai_style['summary'] ) : '',
			)
		);
	}

	/**
	 * POST /styles/{id}/preview-draft — cache the builder's current serialized structure so the
	 * live preview iframe can render unsaved Fields-tab edits (labels, add/delete/reorder, …).
	 * Nothing is written to the form; the draft is a short-lived per-user transient (see
	 * {@see PreviewDraft}).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function save_preview_draft( $request ) {
		$form_id   = absint( $request['id'] );
		$form_data = $request->get_param( 'form_data' );
		$session   = (string) $request->get_param( 'session' );

		// `form_data` is the raw serialized builder array as a JSON string (the same payload the
		// save AJAX sends). Absence/emptiness clears any stale draft rather than erroring.
		if ( ! is_string( $form_data ) || '' === $form_data ) {
			PreviewDraft::clear( $form_id, $session );
			return rest_ensure_response( array( 'stored' => false ) );
		}

		$stored = PreviewDraft::store( $form_id, $form_data, $session );
		return rest_ensure_response( array( 'stored' => (bool) $stored ) );
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
	 * Read the per-form "Apply Theme Style" flag. Reuses the exact meta the v1 preview toggle
	 * and the front-end shortcode wrapper use (`everest_forms_enable_theme_style`), so v1 and v2
	 * stay in sync and no migration is needed. Only the explicit `'default'` value disables theme
	 * styling; anything else (incl. an unset meta) means "apply the theme style" — matching v1.
	 *
	 * @param int $form_id Form id.
	 * @return bool
	 */
	protected static function get_apply_theme_style( $form_id ) {
		return 'default' !== get_post_meta( $form_id, 'everest_forms_enable_theme_style', true );
	}

	/**
	 * Build the full styles payload for a form — the saved record (or an empty default) plus
	 * the schema the panel renders from, so the client never hardcodes the token contract.
	 *
	 * Pure/static so it can be called from two places that need the IDENTICAL shape: this
	 * controller's `get_item()` (the REST route, kept for API completeness / defensive re-fetch)
	 * and {@see BuilderPanel::enqueue()}, which localizes it directly into the builder page so
	 * the panel can initialize synchronously — no network round-trip, no loading state, because
	 * every one of these values is already knowable in PHP at the moment the page renders.
	 *
	 * @param int $form_id Form id.
	 * @return array
	 */
	public static function build_payload( $form_id ) {
		$form_id = absint( $form_id );
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

		return array(
			'form_id'        => $form_id,
			'schema_version' => Schema::version(),
			'schema'         => Schema::tokens(),
			'sections'       => Schema::sections(),
			'palettes'       => Schema::palettes(),
			'palette_map'    => Schema::palette_map(),
			'templates'      => Templates::all(),
			'user_templates' => Templates::user_templates(),
			'breakpoints'    => Compiler::breakpoints(),
			// The Font Family dropdown list — the SAME cached Google Fonts list the legacy
			// customizer uses (order/labels identical), fetched once via the shared helper.
			'google_fonts'   => function_exists( 'evfsc_get_google_font_families' ) ? evfsc_get_google_font_families() : array(),
			// "Apply Theme Style" (a per-form post meta reused from v1): true = use the active
			// theme's styling; false ('default') = load Everest Forms' bundled default styling.
			'apply_theme_style' => self::get_apply_theme_style( $form_id ),
			// Whether the Pro tier is active — the SAME authoritative gate the sanitizer and
			// compiler enforce (see Engine::pro_active()). Drives the panel's locked-teaser UI;
			// display-only, never the security boundary (that is enforced server-side on save).
			'pro_active'     => Engine::pro_active(),
			'record'         => $record,
			// Drives the panel's migration banner (see panes.tsx MigrationBanner): true while the
			// stored record is still the raw legacy shape — migration is one-way and compulsory,
			// so this is purely informational (dismissible), not an offer to opt back out.
			'migration'      => array(
				'just_migrated' => ! empty( $stored ) && ! Engine::is_v2_record( $stored ),
			),
		);
	}

	/**
	 * GET — thin wrapper around {@see self::build_payload()}. Kept for API completeness and as
	 * the panel's defensive fallback (see index.tsx) if the localized payload is ever missing.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) {
		return rest_ensure_response( self::build_payload( absint( $request['id'] ) ) );
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

		// "Apply Theme Style" — persisted to the same per-form meta the v1 preview toggle and the
		// front-end shortcode wrapper read, so the setting is honoured everywhere with no extra
		// wiring. Only written when the client sends it, so a missing flag never clobbers it.
		$apply_theme = $request->get_param( 'apply_theme_style' );
		if ( null !== $apply_theme ) {
			update_post_meta(
				$form_id,
				'everest_forms_enable_theme_style',
				rest_sanitize_boolean( $apply_theme ) ? 'theme' : 'default'
			);
		}

		return rest_ensure_response(
			array(
				'saved'             => true,
				'record'            => $clean,
				'_updated_at'       => $clean['_updated_at'],
				'apply_theme_style' => self::get_apply_theme_style( $form_id ),
			)
		);
	}

}
