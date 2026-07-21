<?php
/**
 * Style Customizer v2 — Legacy → v2 migration.
 *
 * Converts a legacy `everest_forms_styles[form_id]` record (WP-Customizer engine) into a
 * v2 record ({@see Schema} token map). Storage is the same option on both sides, and v2
 * deliberately keeps the legacy VALUE shapes (associative {top,right,bottom,left}
 * dimensions, 0–1 opacity, bold/italic/underline/uppercase font-style) — so the migration
 * only renames *which setting* (group/prop → dotted token key) and copies the value; it
 * never reshapes the value. This keeps it near-lossless and low-risk.
 *
 * The mapping table below targets the CANONICAL bundled addon
 * (`addons/StyleCustomizer/includes/configs/*`). The standalone plugin uses the same prop
 * shapes under per-element group keys — those aliases plug into {@see self::map()} the same
 * way (see STYLE-CUSTOMIZER-V2-PLAN.md §12).
 *
 * Every transform here is pure (no WP calls) so it is unit-testable in isolation; the
 * migrator orchestration is what talks to the option store (handled by the caller with
 * backup + temp-compile-verify + atomic commit, plan §5 Phase 5).
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Legacy → v2 record migrator.
 */
final class Migrator {

	/**
	 * Convert one legacy record into a v2 record.
	 *
	 * @param array $legacy Legacy `everest_forms_styles[form_id]` record.
	 * @return array v2 record ( schema_version + tokens + palette ).
	 */
	public static function migrate_record( $legacy ) {
		$legacy = is_array( $legacy ) ? $legacy : array();

		// Already a v2 record (has a token map) — return it unchanged. Running the v1→v2 mapping
		// over it would find no v1 group keys and emit a defaults-only record, silently wiping the
		// form's styling. This makes migrate_record() idempotent and safe to call defensively.
		if ( isset( $legacy['tokens'] ) ) {
			return $legacy;
		}

		// Otherwise normalize to the canonical v1 shape the mapping table reads (converts the old
		// v0 standalone-plugin shape; leaves canonical v1 untouched), so migration is faithful
		// regardless of how old the source data is.
		$legacy = self::normalize_legacy_shape( $legacy );

		$tokens = array();

		foreach ( self::map() as $rule ) {
			$group = $rule['group'];
			$prop  = $rule['prop'];
			if ( ! isset( $legacy[ $group ][ $prop ] ) ) {
				continue;
			}
			$value = $legacy[ $group ][ $prop ];
			// Legacy represents "never customized" two ways: the key is absent (handled above),
			// OR the key is present with an empty string (e.g. an unset colour/border-type —
			// confirmed on a real production record, `button.border_type => ''`). Migrating the
			// empty string verbatim breaks rendering: Compiler treats '' as a real value and
			// emits no declaration for it (Sanitizer/Compiler skip empty strings), and with no
			// CSS fallback the property is left unset/inherited instead of falling through to
			// the v2 schema default. Skip it here so the token stays unset and the compiler uses
			// its own default, exactly as if the legacy key had never been set at all.
			if ( '' === $value ) {
				continue;
			}
			$tokens[ $rule['token'] ] = self::apply_transform( $rule['transform'], $value );
		}

		// Colour palette: seed the palette-derived token colours from the saved palette (v2
		// deliberately derives input.focusBorder/choice.checked/file.icon/btn.bgHover from
		// button_background — plan §12 — a one-click "recolour everything coherently" UX
		// enhancement over legacy, where those were independent, separately-set properties).
		// Palette values go FIRST here so they're only a FALLBACK: an explicit legacy value for
		// one of these properties (already in $tokens from the loop above) must win, or a
		// customized focus-border/hover colour would be silently discarded on migration.
		$tokens = array_merge( self::migrate_palette( $legacy ), $tokens );

		// Bundled templates ship no color_palette; these typography keys carry their palette-driven
		// fills (EVF-2668). Fall back only when no palette set the token. Deliberately NOT extended to
		// form bg / label colours — some templates hold stray values there ("red" bg, #ffffff labels).
		foreach ( array(
			'input.bg'  => 'field_styles_background_color',
			'btn.bg'    => 'button_background_color',
			'btn.color' => 'button_font_color',
		) as $token => $legacy_key ) {
			if ( ! isset( $tokens[ $token ] ) && ! empty( $legacy['typography'][ $legacy_key ] ) ) {
				$tokens[ $token ] = self::apply_transform( 'pass', $legacy['typography'][ $legacy_key ] );
			}
		}

		// v1 NEVER coupled these three to the palette — each rendered its OWN independent setting
		// unconditionally (scss.php:172,187,460; no palette check anywhere), unlike button_background
		// itself which v1 always read from the palette. So when the legacy record never explicitly
		// set one, the palette merge above just invented a colour v1 never showed on that element
		// (EVF-2669: the file-upload cloud icon changing colour after migration is exactly this).
		// Re-assert each at its legacy-parity schema default unless explicitly customized in legacy.
		foreach ( array(
			'input.focusBorder' => 'field_styles_border_focus_color',
			'choice.checked'    => 'checkbox_radio_checked_color',
			'file.icon'         => 'file_upload_icon_color',
		) as $token => $legacy_key ) {
			if ( empty( $legacy['typography'][ $legacy_key ] ) ) {
				$default = Schema::get( $token );
				if ( $default ) {
					$tokens[ $token ] = self::apply_transform( 'pass', $default['default'] );
				}
			}
		}

		$record = array(
			'schema_version' => Schema::version(),
			'tokens'         => $tokens,
		);

		// Carry the v1 "selected template" (a WP-Customizer control, `template` — see
		// class-evf-style-customizer-api.php) across so the v2 Templates pane still shows it as
		// applied post-migration, instead of landing on "no template selected" despite the form's
		// styling having come from one.
		if ( ! empty( $legacy['template'] ) ) {
			$record['template'] = Templates::resolve_legacy_slug( $legacy['template'] );
		}

		/**
		 * Filter a freshly-migrated v2 record (e.g. to add standalone-engine keys).
		 *
		 * @param array $record V2 record.
		 * @param array $legacy Source legacy record.
		 */
		return apply_filters( 'evf_style_v2_migrated_record', $record, $legacy );
	}

	/* --------------------------------------------------------------------- *
	 * Shape normalization (v0 / v1 / v2)
	 * --------------------------------------------------------------------- */

	/**
	 * Normalize a legacy record to the canonical v1 shape that {@see self::map()} reads (the v2
	 * short-circuit is handled earlier, in {@see self::migrate_record()}):
	 *
	 *  - **v0** (the OLD standalone "Style Customizer" plugin shape: top-level `wrapper`,
	 *    `field_label`, `field_sublabel`, `checkbox_radio_styles`, flat `field_styles.*` typography,
	 *    …): converted to canonical v1 via {@see self::v0_to_v1()}. Real sites got this conversion
	 *    from the one-shot `evfsc_migration()`; a record that predates/skipped it would otherwise
	 *    migrate to all-defaults (every custom colour/size silently lost).
	 *  - **v1** (canonical bundled-addon shape): returned unchanged.
	 *
	 * @param array $legacy Source record (v0 or v1 shape).
	 * @return array Canonical v1 record.
	 */
	protected static function normalize_legacy_shape( $legacy ) {
		if ( self::is_v0_shape( $legacy ) ) {
			return self::v0_to_v1( $legacy );
		}
		return $legacy; // Canonical v1.
	}

	/**
	 * Detect the v0 (old standalone-plugin) shape by keys that ONLY exist there — never in the
	 * canonical v1 shape (which nests typography under `typography.*` and uses `font` /
	 * `form_container`, not a top-level `wrapper`).
	 *
	 * @param array $legacy Record.
	 * @return bool
	 */
	protected static function is_v0_shape( $legacy ) {
		foreach ( array( 'wrapper', 'field_label', 'field_sublabel', 'checkbox_radio_styles', 'file_upload', 'section_title' ) as $marker ) {
			if ( isset( $legacy[ $marker ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Convert one v0 (standalone-plugin) record to the canonical v1 shape — a pure port of the
	 * per-form body of `evfsc_migration()` (addons/StyleCustomizer/includes/functions.php), so a
	 * v0 record migrates identically whether or not that one-shot DB migration ever ran.
	 *
	 * Two documented bugs in `evfsc_migration()` are deliberately CORRECTED here so v0 data
	 * reaches v2 faithfully (the project rule is "v2 does what the setting says", not "replicate
	 * legacy bugs" — plan §11):
	 *   1. Trailing-space source keys (`'font_color '`) silently dropped the field/description/
	 *      section-title/file-upload/checkbox font colours; corrected to `'font_color'`.
	 *   2. The button-alignment typo target (`button_button_alignment`) never matched the v1 key
	 *      the compiler/migrator read (`button_alignment`); corrected.
	 *
	 * @param array $s v0 record (`$settings` in evfsc_migration).
	 * @return array Canonical v1 record.
	 */
	protected static function v0_to_v1( $s ) {
		$new = array();

		if ( isset( $s['template'] ) ) {
			$new['template'] = $s['template'];
		}

		// Font.
		if ( isset( $s['wrapper']['font_family'] ) ) {
			$new['font']['font_family'] = $s['wrapper']['font_family'];
		}

		// Form container.
		$wrapper_keys = array( 'width', 'border_type', 'border_width', 'border_radius', 'border_color', 'background_image', 'background_preset', 'opacity', 'background_position', 'background_size', 'margin', 'padding' );
		foreach ( $wrapper_keys as $k ) {
			if ( isset( $s['wrapper'][ $k ] ) ) {
				$new['form_container'][ $k ] = $s['wrapper'][ $k ];
			}
		}

		// Group-level borders (field / file-upload / button).
		foreach ( array( 'field_styles' => 'field_styles', 'file_upload' => 'file_upload_styles', 'button' => 'button' ) as $src => $dst ) {
			foreach ( array( 'width', 'border_type', 'border_width', 'border_radius' ) as $k ) {
				if ( isset( $s[ $src ][ $k ] ) ) {
					$new[ $dst ][ $k ] = $s[ $src ][ $k ];
				}
			}
		}

		// Typography — (v0 group, [ v0 prop => v1 typography prop ]).
		$typography = array(
			'field_label'          => array( 'font_size' => 'field_labels_font_size', 'font_style' => 'field_labels_font_style', 'text_alignment' => 'field_labels_text_alignment', 'line_height' => 'field_labels_line_height', 'margin' => 'field_labels_margin', 'padding' => 'field_labels_padding' ),
			'field_sublabel'       => array( 'font_size' => 'field_sublabels_font_size', 'font_style' => 'field_sublabels_font_style', 'text_alignment' => 'field_sublabels_text_alignment', 'line_height' => 'field_sublabels_line_height', 'margin' => 'field_sublabels_margin', 'padding' => 'field_sublabels_padding' ),
			'field_styles'         => array( 'font_size' => 'field_styles_font_size', 'font_color' => 'field_styles_font_color', 'placeholder_font_color' => 'field_styles_placeholder_font_color', 'font_style' => 'field_styles_font_style', 'alignment' => 'field_styles_alignment', 'border_color' => 'field_styles_border_color', 'border_focus_color' => 'field_styles_border_focus_color', 'margin' => 'field_styles_margin', 'padding' => 'field_styles_padding' ),
			'field_description'    => array( 'font_size' => 'field_description_font_size', 'font_color' => 'field_description_font_color', 'font_style' => 'field_description_font_style', 'text_alignment' => 'field_description_text_alignment', 'line_height' => 'field_description_line_height', 'margin' => 'field_description_margin', 'padding' => 'field_description_padding' ),
			'section_title'        => array( 'font_size' => 'section_title_font_size', 'font_color' => 'section_title_font_color', 'font_style' => 'section_title_font_style', 'text_alignment' => 'section_title_text_alignment', 'line_height' => 'section_title_line_height', 'margin' => 'section_title_margin', 'padding' => 'section_title_padding' ),
			'file_upload_styles'   => array( 'font_size' => 'file_upload_font_size', 'font_color' => 'file_upload_font_color', 'background_color' => 'file_upload_background_color', 'icon_background_color' => 'file_upload_icon_background_color', 'icon_color' => 'file_upload_icon_color', 'border_color' => 'file_upload_border_color', 'margin' => 'file_upload_margin', 'padding' => 'file_upload_padding' ),
			'checkbox_radio_styles' => array( 'font_size' => 'checkbox_radio_font_size', 'font_color' => 'checkbox_radio_font_color', 'font_style' => 'checkbox_radio_font_style', 'alignment' => 'checkbox_radio_alignment', 'style_variation' => 'checkbox_radio_style_variation', 'size' => 'checkbox_radio_size', 'color' => 'checkbox_radio_color', 'checked_color' => 'checkbox_radio_checked_color', 'margin' => 'checkbox_radio_margin' ),
			'button'               => array( 'font_size' => 'button_font_size', 'font_style' => 'button_font_style', 'hover_font_color' => 'button_hover_font_color', 'hover_background_color' => 'button_hover_background_color', 'border_color' => 'button_border_color', 'alignment' => 'button_alignment', 'border_hover_color' => 'button_border_hover_color', 'line_height' => 'button_line_height', 'margin' => 'button_margin', 'padding' => 'button_padding' ),
		);
		foreach ( $typography as $group => $props ) {
			foreach ( $props as $src => $dst ) {
				if ( isset( $s[ $group ][ $src ] ) ) {
					$new['typography'][ $dst ] = $s[ $group ][ $src ];
				}
			}
		}

		// Submission messages — v0 key structure is IDENTICAL to v1, so copy straight across.
		foreach ( array( 'success_message', 'validation_message', 'error_message' ) as $group ) {
			foreach ( array( 'show_submission_message', 'font_size', 'text_alignment', 'font_color', 'background_color', 'border_type', 'border_width', 'border_color', 'border_radius' ) as $k ) {
				if ( isset( $s[ $group ][ $k ] ) ) {
					$new[ $group ][ $k ] = $s[ $group ][ $k ];
				}
			}
		}

		// Palette colours — v0 stored each colour flat on its element; v1 packs the six into a
		// single `color_palette.color_12` slot (the shape {@see self::migrate_palette()} reads).
		$color_mappings = array(
			'wrapper'        => array( 'background_color' => 'form_background' ),
			'field_styles'   => array( 'background_color' => 'field_background' ),
			'field_label'    => array( 'font_color' => 'field_label' ),
			'field_sublabel' => array( 'font_color' => 'field_sublabel' ),
			'button'         => array( 'font_color' => 'button_text', 'background_color' => 'button_background' ),
		);
		foreach ( $color_mappings as $group => $fields ) {
			foreach ( $fields as $src => $slot ) {
				if ( isset( $s[ $group ][ $src ] ) ) {
					$new['color_palette']['color_12'][ $slot ] = $s[ $group ][ $src ];
				}
			}
		}

		return $new;
	}

	/* --------------------------------------------------------------------- *
	 * Mapping table
	 * --------------------------------------------------------------------- */

	/**
	 * The full legacy(group,prop) => v2 token map with transform per row.
	 *
	 * Transform keys: `pass` (scalar), `fontstyle`, `dim_resp` (responsive margin/padding),
	 * `dim` (single-value border width/radius), `opacity` (0–1 → 0–100).
	 *
	 * @return array
	 */
	public static function map() {
		$rows = array();

		// --- Font ---
		$rows[] = self::row( 'font', 'show_theme_font', 'fonts.theme', 'pass' );
		$rows[] = self::row( 'font', 'font_family', 'fonts.family', 'pass' );

		// --- Form container ---
		$rows[] = self::row( 'form_container', 'width', 'wrap.width', 'pass' );
		$rows[] = self::row( 'form_container', 'border_type', 'wrap.borderStyle', 'pass' );
		$rows[] = self::row( 'form_container', 'border_width', 'wrap.bw', 'dim' );
		$rows[] = self::row( 'form_container', 'border_color', 'wrap.borderC', 'pass' );
		$rows[] = self::row( 'form_container', 'border_radius', 'wrap.radius', 'dim' );
		$rows[] = self::row( 'form_container', 'background_image', 'wrap.bgImage', 'pass' );
		$rows[] = self::row( 'form_container', 'background_preset', 'wrap.bgPreset', 'pass' );
		$rows[] = self::row( 'form_container', 'background_size', 'wrap.bgSize', 'pass' );
		$rows[] = self::row( 'form_container', 'background_repeat', 'wrap.bgRepeat', 'pass' );
		$rows[] = self::row( 'form_container', 'background_attachment', 'wrap.bgAttachment', 'pass' );
		$rows[] = self::row( 'form_container', 'background_position_x', 'wrap.bgPosition', 'pass' ); // y is dropped (single-axis enum, plan §12 lossy edge).
		$rows[] = self::row( 'form_container', 'opacity', 'wrap.bgOpacity', 'pass' ); // v2 keeps the 0–1 scale (identity).
		$rows[] = self::row( 'form_container', 'margin', 'wrap.margin', 'dim_resp' );
		$rows[] = self::row( 'form_container', 'padding', 'wrap.pad', 'dim_resp' );

		// --- Typography: text roles (group `typography`, prefixed props) ---
		$roles = array(
			'field_labels'      => 'label',
			'field_sublabels'   => 'sub',
			'field_description' => 'desc',
			'section_title'     => 'title',
		);
		foreach ( $roles as $legacy_prefix => $vp ) {
			$rows[] = self::row( 'typography', "{$legacy_prefix}_font_size", "{$vp}.size", 'pass' );
			// LABEL and SUBLABEL colour are PALETTE-driven in v1: scss.php reads them from the
			// color_palette `field_label` / `field_sublabel` slots (views/scss.php:154,160), and
			// NEVER from `typography.field_labels_font_color` / `field_sublabels_font_color` — those
			// keys exist in saved/template data but are dead (v1 doesn't render them). Mapping them
			// here injected a colour v1 never showed (e.g. a template's stray #ffffff label), and it
			// overrode the correct palette-derived value from migrate_palette(). So skip the colour
			// row for label/sub; migrate_palette() (palette slots) + the schema default cover them.
			// desc/title colour IS typography-driven in v1 (field_description_font_color /
			// section_title_font_color, scss.php:190,196), so those keep the mapping.
			if ( 'label' !== $vp && 'sub' !== $vp ) {
				$rows[] = self::row( 'typography', "{$legacy_prefix}_font_color", "{$vp}.color", 'pass' );
			}
			$rows[] = self::row( 'typography', "{$legacy_prefix}_font_style", "{$vp}.fstyle", 'fontstyle' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_text_alignment", "{$vp}.align", 'pass' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_line_height", "{$vp}.line", 'pass' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_margin", "{$vp}.margin", 'dim_resp' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_padding", "{$vp}.pad", 'dim_resp' );
		}

		// --- Field styles (typography props + `field_styles` group borders) ---
		$rows[] = self::row( 'typography', 'field_styles_font_size', 'input.size', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_font_color', 'input.color', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_placeholder_font_color', 'input.ph', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_font_style', 'input.fstyle', 'fontstyle' );
		$rows[] = self::row( 'typography', 'field_styles_alignment', 'input.align', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_border_color', 'input.borderC', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_border_focus_color', 'input.focusBorder', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_margin', 'field.margin', 'dim_resp' );
		$rows[] = self::row( 'typography', 'field_styles_padding', 'input.pad', 'dim_resp' );
		$rows[] = self::row( 'field_styles', 'border_type', 'input.borderStyle', 'pass' );
		$rows[] = self::row( 'field_styles', 'border_width', 'input.bw', 'dim' );
		$rows[] = self::row( 'field_styles', 'border_radius', 'input.radius', 'dim' );

		// --- File upload (typography props + `file_upload_styles` group borders) ---
		$rows[] = self::row( 'typography', 'file_upload_font_size', 'file.size', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_font_color', 'file.color', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_background_color', 'file.bg', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_icon_background_color', 'file.iconBg', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_icon_color', 'file.icon', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_border_color', 'file.border', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_margin', 'file.margin', 'dim_resp' );
		$rows[] = self::row( 'typography', 'file_upload_padding', 'file.pad', 'dim_resp' );
		$rows[] = self::row( 'file_upload_styles', 'border_type', 'file.borderStyle', 'pass' );
		$rows[] = self::row( 'file_upload_styles', 'border_width', 'file.bw', 'dim' );
		$rows[] = self::row( 'file_upload_styles', 'border_radius', 'file.radius', 'dim' );

		// --- Radio / checkbox ---
		$rows[] = self::row( 'typography', 'checkbox_radio_font_size', 'choice.fsize', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_font_color', 'choice.color', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_font_style', 'choice.fstyle', 'fontstyle' );
		$rows[] = self::row( 'typography', 'checkbox_radio_alignment', 'choice.align', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_style_variation', 'choice.variation', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_size', 'choice.size', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_color', 'choice.border', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_checked_color', 'choice.checked', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_margin', 'choice.margin', 'dim_resp' );

		// --- Button (typography props + `button` group borders) ---
		$rows[] = self::row( 'typography', 'button_font_size', 'btn.size', 'pass' );
		$rows[] = self::row( 'typography', 'button_font_style', 'btn.fstyle', 'fontstyle' );
		$rows[] = self::row( 'typography', 'button_alignment', 'btn.align', 'pass' );
		$rows[] = self::row( 'typography', 'button_line_height', 'btn.line', 'pass' );
		$rows[] = self::row( 'typography', 'button_border_color', 'btn.borderC', 'pass' );
		$rows[] = self::row( 'typography', 'button_border_hover_color', 'btn.borderCHover', 'pass' );
		$rows[] = self::row( 'typography', 'button_hover_font_color', 'btn.colorHover', 'pass' );
		$rows[] = self::row( 'typography', 'button_hover_background_color', 'btn.bgHover', 'pass' );
		$rows[] = self::row( 'typography', 'button_margin', 'btn.margin', 'dim_resp' );
		$rows[] = self::row( 'typography', 'button_padding', 'btn.pad', 'dim_resp' );
		$rows[] = self::row( 'button', 'border_type', 'btn.borderStyle', 'pass' );
		$rows[] = self::row( 'button', 'border_width', 'btn.bw', 'dim' );
		$rows[] = self::row( 'button', 'border_radius', 'btn.radius', 'dim' );

		// --- Submission messages (success / error / validation) ---
		$msg_props = array(
			'font_size'        => 'size',
			'font_style'       => 'fstyle',
			'text_alignment'   => 'align',
			'font_color'       => 'color',
			'background_color' => 'bg',
			'border_type'      => 'borderStyle',
			'border_width'     => 'bw',
			'border_color'     => 'borderC',
			'border_radius'    => 'radius',
		);
		foreach ( array( 'success', 'error', 'validation' ) as $type ) {
			foreach ( $msg_props as $legacy_prop => $v2_prop ) {
				$transform = 'font_style' === $legacy_prop ? 'fontstyle' : ( in_array( $legacy_prop, array( 'border_width', 'border_radius' ), true ) ? 'dim' : 'pass' );
				$rows[]    = self::row( "{$type}_message", $legacy_prop, "msg.{$type}.{$v2_prop}", $transform );
			}
		}

		/**
		 * Filter the legacy→v2 mapping rows (e.g. to append standalone-engine group aliases).
		 *
		 * @param array $rows Mapping rows.
		 */
		return apply_filters( 'evf_style_v2_migration_map', $rows );
	}

	/* --------------------------------------------------------------------- *
	 * Transforms (pure)
	 * --------------------------------------------------------------------- */

	/**
	 * Apply a named transform, returning a per-device value bag.
	 *
	 * @param string $transform Transform key.
	 * @param mixed  $value     Legacy value.
	 * @return array Device bag ( at least `desktop` ).
	 */
	public static function apply_transform( $transform, $value ) {
		switch ( $transform ) {
			case 'dim_resp':
				return self::responsive_dimension( $value );
			case 'dim':
				return array( 'desktop' => self::normalize_dimension( $value ) );
			case 'fontstyle':
				return array( 'desktop' => self::fontstyle( $value ) );
			case 'pass':
			default:
				return array( 'desktop' => $value );
		}
	}

	/**
	 * Validate a legacy dimension, keeping its associative shape {top,right,bottom,left}
	 * (+ optional `unit`) — v2 stores the SAME shape, so this is a coerce-to-int, not a
	 * reshape (identity migration).
	 *
	 * @param mixed $dim Legacy dimension.
	 * @return array
	 */
	public static function normalize_dimension( $dim ) {
		if ( ! is_array( $dim ) ) {
			return array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
		}
		$out = array(
			'top'    => isset( $dim['top'] ) ? (int) $dim['top'] : 0,
			'right'  => isset( $dim['right'] ) ? (int) $dim['right'] : 0,
			'bottom' => isset( $dim['bottom'] ) ? (int) $dim['bottom'] : 0,
			'left'   => isset( $dim['left'] ) ? (int) $dim['left'] : 0,
		);
		if ( isset( $dim['unit'] ) && '' !== $dim['unit'] ) {
			$out['unit'] = $dim['unit'];
		}
		return $out;
	}

	/**
	 * Responsive dimension {desktop:{…}, tablet?:{…}, mobile?:{…}} → per-device bag with the
	 * same associative inner shape. Preserves every device that was set (lossless, plan §12).
	 *
	 * @param mixed $dim Legacy responsive dimension.
	 * @return array
	 */
	public static function responsive_dimension( $dim ) {
		if ( ! is_array( $dim ) ) {
			return array( 'desktop' => self::normalize_dimension( null ) );
		}
		// A legacy value may be wrapped ({desktop:{…}}) or bare ({top,right,…}).
		if ( ! isset( $dim['desktop'] ) && ( isset( $dim['top'] ) || isset( $dim['left'] ) ) ) {
			return array( 'desktop' => self::normalize_dimension( $dim ) );
		}
		$out = array();
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( isset( $dim[ $device ] ) ) {
				$out[ $device ] = self::normalize_dimension( $dim[ $device ] );
			}
		}
		if ( empty( $out ) ) {
			$out['desktop'] = self::normalize_dimension( null );
		}
		return $out;
	}

	/**
	 * Font-style flags — identical key set on both sides, just coerced to booleans.
	 *
	 * @param mixed $value Legacy value.
	 * @return array
	 */
	public static function fontstyle( $value ) {
		$value = is_array( $value ) ? $value : array();
		return array(
			'bold'      => ! empty( $value['bold'] ),
			'italic'    => ! empty( $value['italic'] ),
			'underline' => ! empty( $value['underline'] ),
			'uppercase' => ! empty( $value['uppercase'] ),
		);
	}

	/* --------------------------------------------------------------------- *
	 * Palette
	 * --------------------------------------------------------------------- */

	/**
	 * Seed the six palette-driven token colours from a saved `color_palette` selection, plus the
	 * derived `btn.bgHover` (button_background darkened 14% toward black) — mirrors
	 * `applyPalette()` in store.ts exactly, so a migrated form's button hover colour matches what
	 * re-applying the same palette through the panel would produce, instead of silently falling
	 * back to the schema default (`#eeeeee`).
	 *
	 * @param array $legacy Legacy record.
	 * @return array token => device bag.
	 */
	protected static function migrate_palette( $legacy ) {
		if ( empty( $legacy['color_palette'] ) || ! is_array( $legacy['color_palette'] ) ) {
			return array();
		}
		$colors = reset( $legacy['color_palette'] ); // color_N => { 6 colours }.
		if ( ! is_array( $colors ) ) {
			return array();
		}
		$out = array();
		foreach ( Schema::palette_map() as $slot => $keys ) {
			if ( ! isset( $colors[ $slot ] ) ) {
				continue;
			}
			foreach ( $keys as $token_key ) {
				$out[ $token_key ] = array( 'desktop' => $colors[ $slot ] );
			}
		}
		if ( ! empty( $colors['button_background'] ) ) {
			$out['btn.bgHover'] = array( 'desktop' => self::mix_hex( $colors['button_background'], '#000000', 0.14 ) );
		}
		return $out;
	}

	/**
	 * Linear-interpolate two hex colours — PHP port of `mixHex()` in constants.ts, kept
	 * byte-for-byte equivalent (same channel rounding) so a migrated `btn.bgHover` is identical
	 * to what the panel would compute if the same palette were re-applied there.
	 *
	 * @param string $a First hex colour (`#rgb` or `#rrggbb`).
	 * @param string $b Second hex colour.
	 * @param float  $t Mix factor, 0 = `$a`, 1 = `$b`.
	 * @return string `#rrggbb`.
	 */
	protected static function mix_hex( $a, $b, $t ) {
		$parse = static function ( $hex ) {
			$s = ltrim( (string) $hex, '#' );
			if ( 3 === strlen( $s ) ) {
				$s = $s[0] . $s[0] . $s[1] . $s[1] . $s[2] . $s[2];
			}
			return array( hexdec( substr( $s, 0, 2 ) ), hexdec( substr( $s, 2, 2 ) ), hexdec( substr( $s, 4, 2 ) ) );
		};
		$from = $parse( $a );
		$to   = $parse( $b );
		$out  = '#';
		foreach ( $from as $i => $x ) {
			$out .= str_pad( dechex( (int) round( $x + ( $to[ $i ] - $x ) * $t ) ), 2, '0', STR_PAD_LEFT );
		}
		return $out;
	}

	/* --------------------------------------------------------------------- *
	 * Helpers
	 * --------------------------------------------------------------------- */

	/**
	 * Build a mapping row.
	 *
	 * @param string $group     Legacy group.
	 * @param string $prop      Legacy prop.
	 * @param string $token     v2 token key.
	 * @param string $transform Transform key.
	 * @return array
	 */
	protected static function row( $group, $prop, $token, $transform ) {
		return array(
			'group'     => $group,
			'prop'      => $prop,
			'token'     => $token,
			'transform' => $transform,
		);
	}
}
