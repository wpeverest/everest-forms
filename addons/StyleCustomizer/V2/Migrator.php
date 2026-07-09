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

		$record = array(
			'schema_version' => Schema::version(),
			'tokens'         => $tokens,
		);

		/**
		 * Filter a freshly-migrated v2 record (e.g. to add standalone-engine keys).
		 *
		 * @param array $record V2 record.
		 * @param array $legacy Source legacy record.
		 */
		return apply_filters( 'evf_style_v2_migrated_record', $record, $legacy );
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
			$rows[] = self::row( 'typography', "{$legacy_prefix}_font_color", "{$vp}.color", 'pass' );
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
	 * Seed the six palette-driven token colours from a saved `color_palette` selection.
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
