<?php
/**
 * Style Customizer v2 — engine-aware frontend enqueue.
 *
 * Decides, per form, whether the front end renders through the v2 engine or the legacy
 * one — the "exactly one stylesheet per form" rule (plan §3). For a v2 form it:
 *   1. adds the `evf-style-v2` marker class to the form wrapper (so the shared rule
 *      template applies only to v2 forms — v1 forms keep their legacy compiled CSS),
 *   2. enqueues the shared rule template (`assets/css/frontend.css`), and
 *   3. inlines the per-form CSS-variable block from {@see Compiler} onto `#evf-{id}`.
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

		// Shared rule template: registered once, reused by every v2 form on the page.
		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			wp_register_style( self::HANDLE, plugins_url( 'assets/css/frontend.css', __FILE__ ), array(), (string) Schema::version() );
		}
		wp_enqueue_style( self::HANDLE );

		// Per-form variable block (scoped to #evf-{id}; the compiler css_safe()-guards values).
		$css = Compiler::compile( $record, $form_id );
		if ( '' !== $css ) {
			wp_add_inline_style( self::HANDLE, $css );
		}

		// User custom CSS — sanitized on save, scoped to the wrapper here so it can't leak
		// site-wide. Emitted after the variable block so it can override token output.
		if ( ! empty( $record['custom_css'] ) ) {
			$custom = Compiler::scope_custom_css( $record['custom_css'], $form_id );
			if ( '' !== $custom ) {
				wp_add_inline_style( self::HANDLE, $custom );
			}
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
