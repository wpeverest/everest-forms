<?php
/**
 * Style Customizer v2 — engine-aware frontend enqueue.
 *
 * Decides, per form, whether the front end renders through the v2 engine or the legacy
 * one — the "exactly one stylesheet per form" rule (plan §3). For a v2 form it:
 *   1. adds the `evf-style-v2` marker class to the form wrapper (so the rule template
 *      applies only to v2 forms — v1 forms keep their legacy compiled CSS),
 *   2. inlines the rule template ID-scoped to `#evf-{id}` (the selectors that READ the vars),
 *   3. inlines the per-form CSS-variable block from {@see Compiler} onto `#evf-{id}`, and
 *   4. loads the selected Google font (parity with v1's `evfsc_enqueue_fonts()`).
 *
 * Why inline + ID-scoped (not a shared class-only stylesheet): Everest Forms' base frontend
 * CSS nests some selectors up to six classes deep (e.g. the section-title `h3` at `(0,6,1)`
 * and the field description at `(0,4,0)`). A shared `.everest-forms .evf-style-v2 …` rule is
 * only three classes and loses that cascade, so those token values silently never apply. The
 * legacy engine avoided this by scoping its per-form sheet under the `#evf-{id}` ID; we do the
 * same by compounding the marker with the form id (`.evf-style-v2#evf-{id}`) — an ID always
 * out-specifies any number of base classes. `assets/css/frontend.css` stays the single source
 * of the rule text (also fetched by the live-preview bridge); we only rescope it per form.
 *
 * A form is "v2" purely by `Engine::is_v2_record()` (presence of `schema_version`), so a
 * legacy form is left entirely to the v1 path. Registered only when `Engine::enabled()`.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend enqueue for v2 forms.
 */
final class FrontendEnqueue {

	/**
	 * Shared stylesheet handle.
	 */
	const HANDLE = 'evf-style-v2';

	/**
	 * Marker class added to a v2 form's wrapper. Shared with the builder panel so its live
	 * preview bridge can scope the same rule template inside the `?evf_preview` iframe.
	 */
	const MARKER_CLASS = 'evf-style-v2';

	/**
	 * Memoized rule-template text (read once per request from the shared CSS file).
	 *
	 * @var string|null
	 */
	protected static $template = null;

	/**
	 * Wire the frontend hooks. Called from {@see Engine::boot()} (only when enabled).
	 *
	 * The enqueue runs at priority 20 — AFTER the legacy Style Customizer's
	 * `enqueue_shortcode_scripts` (priority 10) — so it can dequeue the legacy per-form
	 * stylesheet for v2 forms and guarantee the plan's "exactly one stylesheet per form" rule.
	 */
	public static function register() {
		add_action( 'everest_forms_shortcode_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_filter( 'everest_forms_frontend_container_class', array( __CLASS__, 'container_class' ), 10, 2 );
	}

	/**
	 * Enqueue the rule template + per-form variables for a v2 form. Mirrors the v1 enqueue
	 * hook signature (`$atts['id']`).
	 *
	 * @param array $atts Shortcode atts (`id`).
	 */
	public static function enqueue( $atts ) {
		$form_id = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;
		if ( ! $form_id ) {
			return;
		}

		$record = self::record( $form_id );
		if ( ! Engine::is_v2_record( $record ) ) {
			return; // Legacy form — the v1 enqueue handles it.
		}

		// Exactly one stylesheet per form (plan §3): a v2 form must NOT also load the legacy
		// per-form compiled sheet. That legacy sheet is scoped with the `#evf-{id}` ID, so it
		// out-specifies v2's class-scoped rules and silently overrides token values. The legacy
		// enqueue ran at priority 10; we run at 20, so dequeuing here reliably wins.
		self::suppress_legacy_styles( $form_id );

		// Inline-only handle (no src): everything is printed inline and ID-scoped so it reliably
		// beats Everest Forms' deeply-nested base CSS — see the class docblock.
		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			wp_register_style( self::HANDLE, false, array(), (string) Schema::version() );
		}
		wp_enqueue_style( self::HANDLE );

		// 1) The rule template (selectors reading the vars), ID-scoped to this form.
		$css = self::scoped_rule_template( $form_id );

		// 2) Per-form variable block (scoped to #evf-{id}; the compiler css_safe()-guards values).
		$css .= Compiler::compile( $record, $form_id );

		// 3) User custom CSS — sanitized on save, scoped to the wrapper here so it can't leak
		// site-wide. Emitted last so it can override token output.
		if ( ! empty( $record['custom_css'] ) ) {
			$css .= Compiler::scope_custom_css( $record['custom_css'], $form_id );
		}

		if ( '' !== $css ) {
			wp_add_inline_style( self::HANDLE, $css );
		}

		// Load the selected Google font, exactly as the v1 engine did (reused helper).
		self::maybe_enqueue_font( $record );
	}

	/**
	 * The shared rule template, ID-scoped to a form. Every `.evf-style-v2` in the template is
	 * compounded with the form's id (`.evf-style-v2#evf-{id}`) so the token-driven rules
	 * out-specify base CSS — the identical transform the live-preview bridge applies inside the
	 * iframe, keeping frontend and preview in lockstep.
	 *
	 * @param int $form_id Form id.
	 * @return string Rescoped CSS (empty if the template file is unreadable).
	 */
	protected static function scoped_rule_template( $form_id ) {
		$template = self::rule_template();
		if ( '' === $template ) {
			return '';
		}
		$scope = self::MARKER_CLASS . '#evf-' . (int) $form_id;
		// Replace the marker class only where it is NOT followed by a name char (so a compound
		// like `.evf-style-v2.evf-container` still matches) — mirrors PreviewBridge's regex.
		return (string) preg_replace(
			'/\.' . preg_quote( self::MARKER_CLASS, '/' ) . '(?![\w-])/',
			'.' . $scope,
			$template
		);
	}

	/**
	 * Read (and memoize) the shared rule-template file — the single source of the selectors
	 * that read the CSS variables (also fetched by the preview bridge).
	 *
	 * @return string
	 */
	protected static function rule_template() {
		if ( null === self::$template ) {
			$path           = plugin_dir_path( __FILE__ ) . 'assets/css/frontend.css';
			self::$template = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a bundled plugin asset, not a remote/user file.
		}
		return self::$template;
	}

	/**
	 * Enqueue the form's selected Google font. Parity with the v1 engine: only when the form is
	 * NOT using the theme font and a family is set. Reuses the addon's shared `evfsc_enqueue_fonts()`
	 * so there is exactly one font-loading code path.
	 *
	 * @param array $record V2 style record.
	 */
	protected static function maybe_enqueue_font( $record ) {
		$tokens     = isset( $record['tokens'] ) && is_array( $record['tokens'] ) ? $record['tokens'] : array();
		$theme_font = ! empty( $tokens['fonts.theme']['desktop'] );
		$family     = isset( $tokens['fonts.family']['desktop'] ) ? trim( (string) $tokens['fonts.family']['desktop'] ) : '';

		if ( $theme_font || '' === $family ) {
			return;
		}
		if ( function_exists( 'evfsc_enqueue_fonts' ) ) {
			evfsc_enqueue_fonts( $family );
		}
	}

	/**
	 * Add the `evf-style-v2` marker class to a v2 form's wrapper so the rule template scopes
	 * to it (and never to legacy forms).
	 *
	 * @param array $classes   Container classes.
	 * @param array $form_data Form data (`id`).
	 * @return array
	 */
	public static function container_class( $classes, $form_data ) {
		$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
		if ( $form_id && Engine::is_v2_record( self::record( $form_id ) ) ) {
			$classes[] = 'evf-style-v2';
		}
		return $classes;
	}

	/**
	 * Prevent the legacy Style Customizer's per-form stylesheet (and any font it queued) from
	 * loading for a v2 form, so the v2 engine is the single source of truth. Dequeues +
	 * deregisters the `everest-forms-style-{id}` handle registered by
	 * {@see \Everest_Forms_Style_Customizer::enqueue_shortcode_scripts()}.
	 *
	 * @param int $form_id Form id.
	 */
	protected static function suppress_legacy_styles( $form_id ) {
		$handle = 'everest-forms-style-' . $form_id;
		if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	/**
	 * The stored style record for a form.
	 *
	 * @param int $form_id Form id.
	 * @return array
	 */
	protected static function record( $form_id ) {
		$all = get_option( 'everest_forms_styles', array() );
		return isset( $all[ $form_id ] ) && is_array( $all[ $form_id ] ) ? $all[ $form_id ] : array();
	}
}
