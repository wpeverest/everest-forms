<?php
/**
 * Style Customizer v2 — live builder-structure preview draft.
 *
 * Lets the Style panel's preview iframe show unsaved Fields-tab edits: the panel POSTs the
 * builder's current serialized form data to {@see self::store()}, which caches it as a
 * short-lived per-user transient; {@see self::filter_form_data()} swaps it in for the
 * style-preview request only, leaving the real front end and normal preview untouched.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Per-user builder-structure draft for the live style preview.
 */
final class PreviewDraft {

	/**
	 * How long a draft stays warm (seconds). Short — it only needs to survive the round-trip
	 * from "panel POSTs the structure" to "iframe reloads and renders it".
	 */
	const TTL = 1800;

	/**
	 * The preview query flag that scopes the draft swap to the style customizer's own iframe.
	 */
	const PREVIEW_FLAG = 'evf_style_preview';

	/**
	 * The query arg carrying the per-page-load session token (see BuilderPanel). The draft is keyed
	 * by it, so a stale draft from an earlier builder load never applies to a freshly reloaded one.
	 */
	const SESSION_ARG = 'evf_style_session';

	/**
	 * The most recently parsed draft per form, valid only for the current request. Lets code
	 * that runs inside the SAME `save_preview_draft` request (e.g. {@see RestController::form_field_types()},
	 * the Multi-Part addon's section-visibility filter) react to the just-POSTed draft's
	 * fields/settings instead of the last-saved DB row, without a second lookup mechanism.
	 *
	 * @var array
	 */
	protected static $current = array();

	/**
	 * The current request's just-parsed draft for a form, if any.
	 *
	 * @param int $form_id Form id.
	 * @return array|null
	 */
	public static function current( $form_id ) {
		$form_id = absint( $form_id );
		return isset( self::$current[ $form_id ] ) ? self::$current[ $form_id ] : null;
	}

	/**
	 * Wire the front-end filter. Called from {@see Engine::boot()} (runs on the front end too,
	 * where the preview iframe is rendered). Priority 5 so the swap happens before add-ons that
	 * read the form data on the default priority.
	 */
	public static function register() {
		add_filter( 'everest_forms_frontend_form_data', array( __CLASS__, 'filter_form_data' ), 5 );

		// Server-side chrome hide for the style-preview iframe, set up before the template runs.
		add_action( 'wp', array( __CLASS__, 'maybe_setup_embed' ) );
	}

	/**
	 * Is the current request the style customizer's own preview iframe? Tight gate: a front-end
	 * `?evf_preview` request carrying our flag, from a logged-in form manager. Public so addons
	 * can reuse the same gate (e.g. {@see self::preview_style_tokens()}'s callers).
	 *
	 * @return bool
	 */
	public static function is_style_preview_request() {
		if ( is_admin() ) {
			return false;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only gating on the public preview route; capability enforced below.
		$is = isset( $_GET['evf_preview'] ) && isset( $_GET[ self::PREVIEW_FLAG ] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return $is && is_user_logged_in() && current_user_can( 'manage_everest_forms' );
	}

	/**
	 * When the request is the style-preview iframe, hide all preview chrome (admin bar, preview
	 * toolbar, side panel) so only the form renders. Hooked on `wp` so it runs before the template.
	 */
	public static function maybe_setup_embed() {
		if ( ! self::is_style_preview_request() ) {
			return;
		}
		add_filter( 'show_admin_bar', '__return_false', 100 );
		add_action( 'wp_head', array( __CLASS__, 'print_embed_css' ), 100 );
	}

	/**
	 * Inline CSS that reduces the preview page to just the form. Mirrors the bridge's runtime
	 * chrome-hide, but server-rendered so it applies immediately in every browser.
	 */
	public static function print_embed_css() {
		echo '<style id="evf-style-preview-embed">'
			. 'html{margin-top:0 !important;scrollbar-gutter:stable;}'
			. '*,*::before,*::after{box-sizing:border-box;}'
			. 'body.evf-multi-device-form-preview{margin:0 !important;padding:0 !important;background:#fff !important;}'
			. '#wpadminbar,#nav-menu-header,.evf-form-side-panel,.evf-form-preview-sidepanel-toggler,.evf-form-preview-devices,.evf-form-preview-dropdown-container,.major-publishing-actions{display:none !important;}'
			. '.evf-form-preview-main-content,.evf-form-preview-overlay{display:block !important;position:static !important;inset:auto !important;margin:0 !important;padding:16px !important;width:100% !important;max-width:100% !important;min-height:0 !important;height:auto !important;box-shadow:none !important;background:transparent !important;}'
			// Hides evf-form-preview.scss's dark ::after scrim below a 992px container width.
			. '.evf-form-preview-overlay::after{display:none !important;}'
			. '.evf-form-preview-form{width:100% !important;max-width:100% !important;margin:0 !important;padding:0 !important;}'
			. '.evf-preview-content{width:100% !important;max-width:100% !important;}'
			. '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static developer-controlled CSS.
	}

	/**
	 * Transient key for a given form + user + builder session. Scoped per user so two editors never
	 * clobber each other, and per session so a stale draft from an earlier builder load is ignored.
	 *
	 * @param int    $form_id Form id.
	 * @param int    $user_id User id.
	 * @param string $session Per-page-load session token.
	 * @return string
	 */
	protected static function key( $form_id, $user_id, $session ) {
		return 'evf_style_v2_draft_' . (int) $user_id . '_' . (int) $form_id . '_' . md5( (string) $session );
	}

	/**
	 * Store a draft from the serialized builder form data.
	 *
	 * @param int    $form_id        Form id.
	 * @param string $form_data_json JSON of the builder's serialized `[{name,value},…]` array
	 *                               (form inputs + layout structure), exactly as the save AJAX sends.
	 * @param string $session        Per-page-load session token (from BuilderPanel).
	 * @param array  $style_tokens   Optional. Unsaved Style Customizer v2 token values with no
	 *                               CSS-variable fast path (e.g. `pagination.indicatorType`),
	 *                               keyed by token key — see {@see self::preview_style_tokens()}.
	 * @return bool True if a renderable draft was stored.
	 */
	public static function store( $form_id, $form_data_json, $session, $style_tokens = null ) {
		$form_id = absint( $form_id );
		$user_id = get_current_user_id();
		$session = sanitize_text_field( (string) $session );
		if ( ! $form_id || ! $user_id || '' === $session ) {
			return false;
		}

		$decoded = json_decode( wp_unslash( (string) $form_data_json ) );
		if ( ! is_array( $decoded ) ) {
			return false;
		}

		$data = self::parse( $decoded );

		// Each push only carries the style tokens edited SINCE the last one (the client clears its
		// pending set after every successful POST) — merge onto whatever's already staged instead
		// of replacing it wholesale, or editing e.g. indicatorColor after indicatorType would drop
		// the still-unsaved indicatorType override (a structure-only push, with no style_tokens at
		// all, must also carry the existing overrides forward for the same reason).
		$existing_draft  = get_transient( self::key( $form_id, $user_id, $session ) );
		$existing_tokens = ( is_array( $existing_draft ) && isset( $existing_draft['style_tokens'] ) && is_array( $existing_draft['style_tokens'] ) )
			? $existing_draft['style_tokens']
			: array();

		if ( is_array( $style_tokens ) ) {
			$clean = array();
			foreach ( $style_tokens as $key => $value ) {
				if ( ! is_string( $key ) ) {
					continue;
				}
				$sanitized = self::sanitize_style_token_value( $value );
				if ( null !== $sanitized ) {
					$clean[ $key ] = $sanitized;
				}
			}
			$existing_tokens = array_merge( $existing_tokens, $clean );
		}

		if ( ! empty( $existing_tokens ) ) {
			$data['style_tokens'] = $existing_tokens;
		}

		self::$current[ $form_id ] = $data;

		// A draft with no fields isn't renderable — clear any stale draft instead.
		if ( empty( $data['form_fields'] ) ) {
			self::clear( $form_id, $session );
			return false;
		}

		set_transient( self::key( $form_id, $user_id, $session ), $data, self::TTL );
		return true;
	}

	/**
	 * Sanitize one staged style-token value — either a scalar (color/select value) or a box4
	 * shape `{top,right,bottom,left[,unit]}` (e.g. `pagination.margin`, sent as the resolved
	 * device value straight from the JS store — see PreviewBridge.ts's PAGINATION_STRUCTURAL_KEYS).
	 * Returns null for anything else (rejected, same as the old is_scalar()-only check did).
	 *
	 * @param mixed $value Raw staged value.
	 * @return string|array|null
	 */
	protected static function sanitize_style_token_value( $value ) {
		if ( is_scalar( $value ) ) {
			return sanitize_text_field( (string) $value );
		}
		if ( is_array( $value ) ) {
			$sides = array( 'top', 'right', 'bottom', 'left' );
			if ( array_diff( $sides, array_keys( $value ) ) ) {
				return null;
			}
			$box = array();
			foreach ( $sides as $side ) {
				if ( ! is_numeric( $value[ $side ] ) ) {
					return null;
				}
				$box[ $side ] = (float) $value[ $side ];
			}
			if ( isset( $value['unit'] ) && is_string( $value['unit'] ) ) {
				$box['unit'] = sanitize_text_field( $value['unit'] );
			}
			return $box;
		}
		return null;
	}

	/**
	 * Drop the current user's draft for a form + session.
	 *
	 * @param int    $form_id Form id.
	 * @param string $session Session token.
	 */
	public static function clear( $form_id, $session ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			delete_transient( self::key( absint( $form_id ), $user_id, $session ) );
		}
	}

	/**
	 * Parse the serialized builder array into the nested form structure. Mirrors
	 * {@see \EVF_AJAX::save_form()} so the draft renders identically to a saved form.
	 *
	 * @param array $form_post Array of `{name,value}` objects (already JSON-decoded).
	 * @return array Nested form data (`form_fields`, `structure`, `settings`, …).
	 */
	protected static function parse( $form_post ) {
		$form_post = function_exists( 'evf_sanitize_builder' ) ? evf_sanitize_builder( $form_post ) : $form_post;

		$data = array();
		if ( is_null( $form_post ) || ! $form_post ) {
			return $data;
		}

		foreach ( $form_post as $post_index => $post_input_data ) {
			if ( ! is_object( $post_input_data ) || ! isset( $post_input_data->name ) ) {
				continue;
			}
			// For input names that are arrays (e.g. `form_fields[3][choices][1][label]`), derive
			// the array path keys via regex and rebuild the value leaf-to-trunk.
			preg_match( '#([^\[]*)(\[(.+)\])?#', $post_input_data->name, $matches );

			$array_bits = array( $matches[1] );
			if ( isset( $matches[3] ) ) {
				$array_bits = array_merge( $array_bits, explode( '][', $matches[3] ) );
			}

			$new_post_data = array();
			for ( $i = count( $array_bits ) - 1; $i >= 0; $i-- ) {
				if ( count( $array_bits ) - 1 === $i ) {
					if ( '' === $array_bits[ $i ] ) {
						$new_post_data[ $post_index ] = wp_slash( $post_input_data->value );
					} else {
						$new_post_data[ $array_bits[ $i ] ] = wp_slash( $post_input_data->value );
					}
				} else {
					$new_post_data = array(
						$array_bits[ $i ] => $new_post_data,
					);
				}
			}
			$data = array_replace_recursive( $data, $new_post_data );
		}

		return $data;
	}

	/**
	 * Swap the draft structure into the front-end form data — style-preview iframe only.
	 *
	 * @param array $form_data Decoded saved form data.
	 * @return array
	 */
	public static function filter_form_data( $form_data ) {
		if ( is_admin() || ! is_array( $form_data ) ) {
			return $form_data;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only gating on the public preview route; capability is enforced below.
		if ( ! isset( $_GET['evf_preview'] ) || ! isset( $_GET[ self::PREVIEW_FLAG ] ) ) {
			return $form_data;
		}
		$req_form_id = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0;
		$session     = isset( $_GET[ self::SESSION_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::SESSION_ARG ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $req_form_id || '' === $session || ! is_user_logged_in() || ! current_user_can( 'manage_everest_forms' ) ) {
			return $form_data;
		}

		$data_form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
		if ( $data_form_id && $data_form_id !== $req_form_id ) {
			return $form_data;
		}

		$draft = get_transient( self::key( $req_form_id, get_current_user_id(), $session ) );
		if ( ! is_array( $draft ) || empty( $draft['form_fields'] ) ) {
			return $form_data;
		}

		return array_replace( $form_data, $draft );
	}

	/**
	 * The unsaved style-token overrides for the CURRENT style-preview iframe request, if any —
	 * same gating as {@see self::filter_form_data()}, but exposed for addons to consult while
	 * rendering (e.g. Multi-Part's `apply_v2_pagination_tokens()`) for tokens that change the
	 * front-end DOM structure and so can't be live-patched by the panel's CSS-variable fast path
	 * (see PreviewBridge.ts's `applyToken()`/`applyKeys()`).
	 *
	 * @param int $form_id Form id being rendered.
	 * @return array Token key => value, or empty if there's no matching draft.
	 */
	public static function preview_style_tokens( $form_id ) {
		if ( ! self::is_style_preview_request() ) {
			return array();
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only gating, matches is_style_preview_request()/filter_form_data().
		$req_form_id = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0;
		$session     = isset( $_GET[ self::SESSION_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::SESSION_ARG ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $req_form_id || absint( $form_id ) !== $req_form_id || '' === $session ) {
			return array();
		}

		$draft = get_transient( self::key( $req_form_id, get_current_user_id(), $session ) );
		return is_array( $draft ) && isset( $draft['style_tokens'] ) && is_array( $draft['style_tokens'] )
			? $draft['style_tokens']
			: array();
	}
}
