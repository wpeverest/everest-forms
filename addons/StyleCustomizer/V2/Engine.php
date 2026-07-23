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
	 * Is the v2 engine enabled? ON by default; `EVF_STYLE_V2` and the `evf_style_v2_enabled`
	 * filter are the off-switch.
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
	 * Is a stored record a v2 record? Derived from the presence of `schema_version`.
	 *
	 * @param mixed $record Stored `everest_forms_styles[form_id]` record.
	 * @return bool
	 */
	public static function is_v2_record( $record ) {
		return is_array( $record ) && isset( $record['schema_version'] );
	}

	/**
	 * Is the Pro tier active? The single, server-side authoritative gate for every pro-tier
	 * feature; defaults to whether Everest Forms Pro is loaded, filterable to a licence check.
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
	 * Boot the v2 engine. No-op unless enabled; safe to call more than once.
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

		// Live Fields → Style sync for the style-preview iframe.
		PreviewDraft::register();

		// Builder "Style" tab + React panel.
		BuilderPanel::register();

		/**
		 * Fires once the v2 engine boots.
		 */
		do_action( 'evf_style_v2_booted' );
	}
}
