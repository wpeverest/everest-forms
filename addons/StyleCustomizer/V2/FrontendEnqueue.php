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
	 * Wire the frontend hooks. Called from {@see Engine::boot()} (only when enabled).
	 */
	public static function register() {
		add_action( 'everest_forms_shortcode_scripts', array( __CLASS__, 'enqueue' ) );
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
