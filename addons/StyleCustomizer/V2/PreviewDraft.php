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
	 * `?evf_preview` request carrying our flag, from a logged-in form manager.
	 *
	 * @return bool
	 */
	protected static function is_style_preview_request() {
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
	 * @return bool True if a renderable draft was stored.
	 */
	public static function store( $form_id, $form_data_json, $session ) {
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

		// A draft with no fields isn't renderable — clear any stale draft instead.
		if ( empty( $data['form_fields'] ) ) {
			self::clear( $form_id, $session );
			return false;
		}

		set_transient( self::key( $form_id, $user_id, $session ), $data, self::TTL );
		return true;
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
	 * Pagination.* token overrides from an in-progress, unsaved Style-panel edit.
	 *
	 * This class only ever drafts the builder's FIELD STRUCTURE (see {@see store()}/{@see parse()}
	 * — a nested `form_fields`/`structure`/`settings` shape, never a `tokens` map), because a style
	 * token edit is applied straight to the live preview client-side and only reaches the server on
	 * Save (see RestController::save_item()). There is no unsaved-style-token draft to read here yet,
	 * so this always returns empty; {@see \EverestForms_MultiPart::overlay_v2_pagination_tokens()}
	 * falls back to the last SAVED tokens, which is correct today.
	 *
	 * @param int $form_id Form id.
	 * @return array Token key => value overrides (currently always empty).
	 */
	public static function preview_style_tokens( $form_id ) {
		return array();
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
}
