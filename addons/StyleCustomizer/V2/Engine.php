<?php
/**
 * Style Customizer v2 — Engine gate.
 *
 * The single place that answers "is v2 active?" and "is this form a v2 form?". Everything
 * v2 ships dark behind these gates until Phase 5 (STYLE-CUSTOMIZER-V2-PLAN.md §8).
 *
 * Phase 0 note: {@see self::boot()} is intentionally NOT called from the live addon yet —
 * there is nothing user-facing to mount. Phase 1 wires it into the builder + frontend
 * enqueue. The gate helpers below are already safe to use anywhere.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * The v2 feature gate.
 */
final class Engine {

	/**
	 * Is the v2 engine enabled? Off unless the `EVF_STYLE_V2` constant is truthy, and always
	 * overridable via the `evf_style_v2_enabled` filter (staged rollout, plan §8).
	 *
	 * @return bool
	 */
	public static function enabled() {
		$enabled = defined( 'EVF_STYLE_V2' ) && \EVF_STYLE_V2;

		/**
		 * Filter whether the Style Customizer v2 engine is active.
		 *
		 * @param bool $enabled Default from the EVF_STYLE_V2 constant.
		 */
		return (bool) apply_filters( 'evf_style_v2_enabled', $enabled );
	}

	/**
	 * Is a stored record a v2 record? Derived purely from the presence of `schema_version`
	 * — no separate option/meta (plan §3). This is what decides v2-compiled vs
	 * legacy-compiled per form, so exactly one stylesheet is ever enqueued.
	 *
	 * @param mixed $record Stored `everest_forms_styles[form_id]` record.
	 * @return bool
	 */
	public static function is_v2_record( $record ) {
		return is_array( $record ) && isset( $record['schema_version'] );
	}

	/**
	 * Boot the v2 engine. No-op unless enabled; safe to call more than once. Phase 1+ mounts
	 * the builder tab + REST routes on the `evf_style_v2_booted` action below.
	 */
	public static function boot() {
		if ( ! self::enabled() ) {
			return;
		}

		// Engine-aware frontend rendering (v2-compiled vs legacy per form).
		FrontendEnqueue::register();

		// REST read/save for the builder panel.
		RestController::register();

		// Builder "Style" tab + React panel.
		BuilderPanel::register();

		/**
		 * Fires once the v2 engine boots.
		 */
		do_action( 'evf_style_v2_booted' );
	}
}
