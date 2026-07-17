<?php
/**
 * Style Customizer v2 — Engine gate.
 *
 * The single place that answers "is v2 active?" and "is this form a v2 form?". v2 is ON by
 * default; {@see self::enabled()} only exposes an off-switch.
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
	 * Is the v2 engine enabled? ON by default — no constant or flag is needed to get the Style
	 * tab. The `EVF_STYLE_V2` constant (only when explicitly defined) and the
	 * `evf_style_v2_enabled` filter remain purely as an off-switch.
	 *
	 * @return bool
	 */
	public static function enabled() {
		$enabled = defined( 'EVF_STYLE_V2' ) ? (bool) \EVF_STYLE_V2 : true;

		/**
		 * Filter whether the Style Customizer v2 engine is active.
		 *
		 * @param bool $enabled Whether v2 is active (default true).
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
	 * Is the Pro tier active? THE single, server-side authoritative gate for every pro-tier
	 * feature (pro tokens/palettes/sections). It is what makes the free/pro split
	 * non-bypassable: the sanitizer, compiler and frontend enqueue all consult it, so a crafted
	 * REST request can never persist or render pro styling on a site without Pro.
	 *
	 * Defaults to whether Everest Forms Pro is loaded (its `EFP_PLUGIN_FILE` constant) — the same
	 * signal v1 used to gate the submission-message panel. Pro can tighten this to a licence check
	 * via the `evf_style_v2_pro_active` filter. Note that the primary security guarantee is
	 * structural (pro tokens/palettes are *defined only in the Pro plugin*, so they are physically
	 * absent from a free-only site's schema); this gate is the authoritative backstop.
	 *
	 * @return bool
	 */
	public static function pro_active() {
		/**
		 * Filter whether the Style Customizer v2 Pro tier is active.
		 *
		 * @param bool $active Default: the Everest Forms Pro plugin is loaded.
		 */
		return (bool) apply_filters( 'evf_style_v2_pro_active', defined( 'EFP_PLUGIN_FILE' ) );
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

		// Reusable custom colour palettes (registers the schema filter; must run before REST).
		Palettes::register();

		// REST read/save for the builder panel.
		RestController::register();

		// Live Fields → Style sync: render the builder's current (unsaved) structure in the
		// style-preview iframe. Registers a front-end filter, so it must boot on the front end too.
		PreviewDraft::register();

		// Builder "Style" tab + React panel.
		BuilderPanel::register();

		/**
		 * Fires once the v2 engine boots.
		 */
		do_action( 'evf_style_v2_booted' );
	}
}
