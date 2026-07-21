<?php
/**
 * Style Customizer v2 — Sanitizer.
 *
 * The single write-path guard: no value reaches the stored record (and therefore the
 * compiler) unless it is valid for its token's type. Driven entirely by {@see Schema}
 * so it can never drift from the control set.
 *
 * Record shape (see STYLE-CUSTOMIZER-V2-PLAN.md §3):
 *   array(
 *     'schema_version' => 1,
 *     'tokens'         => array( '<key>' => array( 'desktop' => v, 'tablet' => v, 'mobile' => v ) ),
 *     'palette'        => '<palette-id>',
 *     'template'       => '<template-id>',
 *     'custom_css'     => '<css>',
 *     '_updated_at'    => <timestamp>,
 *   )
 * Only `desktop` is kept for non-responsive tokens; `tablet`/`mobile` survive only on the
 * responsive (spacing) tokens.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Schema-driven sanitizer.
 */
final class Sanitizer {

	/**
	 * Sanitize a full v2 record.
	 *
	 * @param array $record Raw (untrusted) record.
	 * @return array Clean record, always stamped with the current schema version.
	 */
	public static function sanitize_record( $record ) {
		$record = is_array( $record ) ? $record : array();
		$clean  = array( 'schema_version' => Schema::version() );

		$pro_active = Engine::pro_active();

		$in_tokens  = isset( $record['tokens'] ) && is_array( $record['tokens'] ) ? $record['tokens'] : array();
		$out_tokens = array();
		foreach ( Schema::tokens() as $token ) {
			$key = $token['key'];
			if ( ! array_key_exists( $key, $in_tokens ) ) {
				continue; // Absent → the compiler falls back to the schema default.
			}
			// Authoritative pro-tier gate: a pro-tier value can never be persisted on a site
			// without Pro. Pro tokens are already physically absent from a free schema (they are
			// defined in the Pro plugin), so this is the belt-and-suspenders backstop for the case
			// where a pro token is present in the schema but the Pro tier is not active.
			if ( ! $pro_active && isset( $token['tier'] ) && 'pro' === $token['tier'] ) {
				continue;
			}
			$out_tokens[ $key ] = self::sanitize_token( $token, $in_tokens[ $key ] );
		}
		self::ensure_text_contrast( $out_tokens );

		$clean['tokens'] = $out_tokens;

		if ( isset( $record['palette'] ) ) {
			$clean['palette'] = self::sanitize_palette_id( $record['palette'] );
		}
		if ( isset( $record['template'] ) ) {
			$template = sanitize_key( $record['template'] );
			// A Pro template (or a user/custom template) can't be selected without Pro.
			if ( ! $pro_active && ! Templates::is_free_template_id( $template ) ) {
				$template = '';
			}
			$clean['template'] = $template;
		}
		if ( isset( $record['custom_css'] ) ) {
			$clean['custom_css'] = self::sanitize_css( $record['custom_css'] );
		}

		$clean['_updated_at'] = time();

		return $clean;
	}

	/**
	 * Drop a text-colour override that would be unreadable against the form's own
	 * background — belt-and-suspenders against a caller (chiefly the AI style launcher,
	 * see RestController::ai_style()) sending a token combination that is internally
	 * inconsistent, e.g. an explicit `label.color` override to white with no matching dark
	 * `wrap.bg`. label.color/sub.color/desc.color/title.color are ALL hidden, palette-driven-
	 * by-convention tokens (Schema::text_role_tokens()) with dark schema defaults (#333333-
	 * #666666) that are already coherent with the white wrap.bg default — so dropping the
	 * override here is a safe fallback, not a loss of an otherwise-reachable look.
	 *
	 * Threshold is deliberately low (2:1, well under WCAG AA's 4.5:1) — this exists to catch
	 * "vanishes entirely" pairings like white-on-white, not to enforce accessible contrast in
	 * general; a human deliberately choosing a low-but-visible combination in the Design tab
	 * should never have their explicit choice silently reverted.
	 *
	 * @param array $out_tokens Sanitized token map, keyed by token key, modified in place.
	 */
	private static function ensure_text_contrast( array &$out_tokens ) {
		$bg = isset( $out_tokens['wrap.bg']['desktop'] ) ? $out_tokens['wrap.bg']['desktop'] : '#ffffff';

		foreach ( array( 'label.color', 'sub.color', 'desc.color', 'title.color' ) as $key ) {
			if ( ! isset( $out_tokens[ $key ]['desktop'] ) ) {
				continue;
			}
			$fg = $out_tokens[ $key ]['desktop'];
			if ( ! is_string( $fg ) || ! is_string( $bg ) ) {
				continue;
			}
			if ( self::contrast_ratio( $fg, $bg ) < 2.0 ) {
				unset( $out_tokens[ $key ] ); // Falls back to the token's own (coherent) schema default.
			}
		}
	}

	/**
	 * WCAG relative-luminance contrast ratio between two hex colours (1 = identical, 21 = max).
	 *
	 * @param string $hex1 First colour, e.g. "#ffffff".
	 * @param string $hex2 Second colour.
	 * @return float
	 */
	private static function contrast_ratio( $hex1, $hex2 ) {
		$l1 = self::relative_luminance( $hex1 );
		$l2 = self::relative_luminance( $hex2 );
		if ( null === $l1 || null === $l2 ) {
			return 21.0; // Unparseable colour (e.g. a CSS var/keyword) — don't second-guess it.
		}
		$lighter = max( $l1, $l2 );
		$darker  = min( $l1, $l2 );
		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	/**
	 * WCAG relative luminance of a hex colour, or null if it isn't a parseable #rrggbb/#rgb value.
	 *
	 * @param string $hex Colour, e.g. "#1a1a2e".
	 * @return float|null
	 */
	private static function relative_luminance( $hex ) {
		$hex = ltrim( trim( (string) $hex ), '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return null;
		}
		$channels = array();
		foreach ( array( 0, 2, 4 ) as $i ) {
			$c = hexdec( substr( $hex, $i, 2 ) ) / 255;
			$channels[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}
		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	/**
	 * Sanitize one token's per-device value bag.
	 *
	 * @param array $token  Token definition.
	 * @param mixed $stored Stored value (device bag, or a bare value we wrap into desktop).
	 * @return array Device bag with at least `desktop`.
	 */
	public static function sanitize_token( $token, $stored ) {
		// Accept a bare value (e.g. legacy import) by treating it as the desktop base.
		if ( ! is_array( $stored ) || ! self::is_device_bag( $stored ) ) {
			$stored = array( 'desktop' => $stored );
		}

		$devices = ! empty( $token['responsive'] ) ? array( 'desktop', 'tablet', 'mobile' ) : array( 'desktop' );
		$out     = array();
		foreach ( $devices as $device ) {
			if ( ! array_key_exists( $device, $stored ) ) {
				continue; // Missing device → inherits desktop via the CSS cascade.
			}
			$out[ $device ] = self::sanitize_scalar( $token, $stored[ $device ] );
		}

		if ( ! isset( $out['desktop'] ) ) {
			$out['desktop'] = $token['default'];
		}

		return $out;
	}

	/**
	 * Sanitize a single value according to the token type.
	 *
	 * @param array $token Token definition.
	 * @param mixed $value Raw value.
	 * @return mixed Clean value (token default on anything invalid).
	 */
	public static function sanitize_scalar( $token, $value ) {
		// The font-family select is data-driven (the full Google Fonts list is supplied at
		// runtime, not baked into the schema options), so validate it as a font-family string
		// rather than against the tiny fallback option list — otherwise every real choice would
		// be rejected. Migrated v1 records store the bare family name too, so this keeps them.
		if ( ! empty( $token['source'] ) && 'google_fonts' === $token['source'] ) {
			return self::sanitize_font_family( $value );
		}

		switch ( $token['type'] ) {
			case 'slider':
				return self::sanitize_number( $token, $value );

			case 'color':
				return self::sanitize_color( $value, $token['default'] );

			case 'box4':
				return self::sanitize_box4( $token, $value );

			case 'select':
			case 'align':
				return self::sanitize_choice( $token, $value );

			case 'fontstyle':
				return self::sanitize_fontstyle( $value );

			case 'toggle':
				return (bool) $value;

			case 'media':
				return $value ? esc_url_raw( (string) $value ) : '';

			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/* --------------------------------------------------------------------- *
	 * Type handlers
	 * --------------------------------------------------------------------- */

	/**
	 * Clamp a slider value to [min,max]; integer unless the step is fractional.
	 *
	 * @param array $token Token definition.
	 * @param mixed $value Raw value.
	 * @return int|float
	 */
	protected static function sanitize_number( $token, $value ) {
		if ( ! is_numeric( $value ) ) {
			return $token['default'];
		}
		$min  = isset( $token['min'] ) ? $token['min'] : 0;
		$max  = isset( $token['max'] ) ? $token['max'] : 9999;
		$step = isset( $token['step'] ) ? $token['step'] : 1;
		$n    = ( $step < 1 ) ? (float) $value : (int) round( $value );
		return max( $min, min( $max, $n ) );
	}

	/**
	 * Public façade over {@see self::sanitize_color()} for other engine classes (e.g. {@see Palettes}).
	 *
	 * @param mixed  $value   Raw value.
	 * @param string $default Fallback.
	 * @return string
	 */
	public static function color( $value, $default = '#000000' ) {
		return self::sanitize_color( $value, $default );
	}

	/**
	 * Validate a colour: #rgb / #rrggbb (+ 8-digit alpha) or rgb()/rgba(). Falls back to
	 * the default — alpha is preserved so legacy `rgba()` borders survive (plan §12).
	 *
	 * @param mixed  $value   Raw value.
	 * @param string $default Fallback.
	 * @return string
	 */
	protected static function sanitize_color( $value, $default ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ) {
			return strtolower( $value );
		}
		if ( preg_match( '/^rgba?\(\s*[\d.,%\s\/]+\)$/i', $value ) ) {
			return $value;
		}
		return $default;
	}

	/**
	 * Clean a 4-side box, keeping the legacy associative shape {top,right,bottom,left}
	 * (+ `unit` for a radius). Margins may be negative; everything else is clamped at 0.
	 *
	 * @param array $token Token definition.
	 * @param mixed $value Raw value.
	 * @return array
	 */
	protected static function sanitize_box4( $token, $value ) {
		if ( ! is_array( $value ) ) {
			return $token['default'];
		}
		$allow_neg = false !== strpos( $token['key'], 'margin' );
		$floor     = isset( $token['min'] ) ? (int) $token['min'] : ( $allow_neg ? -1000 : 0 );
		$max       = isset( $token['max'] ) ? $token['max'] : 1000;

		$out = array();
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$n            = isset( $value[ $side ] ) && is_numeric( $value[ $side ] ) ? (int) $value[ $side ] : 0;
			$out[ $side ] = max( $floor, min( $max, $n ) );
		}

		if ( ! empty( $token['units'] ) ) {
			$out['unit'] = ( isset( $value['unit'] ) && in_array( $value['unit'], $token['units'], true ) ) ? $value['unit'] : $token['units'][0];
		}
		return $out;
	}

	/**
	 * Ensure a select/align value is one of the allowed options.
	 *
	 * @param array $token Token definition.
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected static function sanitize_choice( $token, $value ) {
		$value = (string) $value;
		if ( 'align' === $token['type'] ) {
			return in_array( $value, array( 'left', 'center', 'right' ), true ) ? $value : $token['default'];
		}
		$allowed = array();
		foreach ( ( isset( $token['options'] ) ? $token['options'] : array() ) as $opt ) {
			$allowed[] = (string) $opt['value'];
		}
		return in_array( $value, $allowed, true ) ? $value : $token['default'];
	}

	/**
	 * Sanitize a font-family value: the empty string (theme font) or a plain family name such
	 * as `Open Sans` / `Roboto`. Strips characters that could break out of the compiled
	 * `font-family:` declaration (the compiler additionally css_safe()-guards the value).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected static function sanitize_font_family( $value ) {
		$value = sanitize_text_field( (string) $value );
		// Only letters, numbers, spaces, hyphens, commas, apostrophes and quotes are valid in a
		// font-family list; anything else (braces, semicolons, angle brackets…) is dropped.
		$value = preg_replace( '/[^A-Za-z0-9 ,\-\'"]/', '', $value );
		return trim( $value );
	}

	/**
	 * Normalize a font-style flag set to four booleans (old customizer keys).
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	protected static function sanitize_fontstyle( $value ) {
		$value = is_array( $value ) ? $value : array();
		$out   = array();
		foreach ( array( 'bold', 'italic', 'underline', 'uppercase' ) as $flag ) {
			$out[ $flag ] = ! empty( $value[ $flag ] );
		}
		return $out;
	}

	/* --------------------------------------------------------------------- *
	 * Record-level helpers
	 * --------------------------------------------------------------------- */

	/**
	 * A palette id must match one of the registered palettes, else empty (custom/none). A Pro
	 * (`is_pro`) palette is rejected unless the Pro tier is active — so a free site can never
	 * persist a Pro palette selection even if one is somehow present in the palette list.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected static function sanitize_palette_id( $value ) {
		$value      = sanitize_key( $value );
		$pro_active = Engine::pro_active();
		foreach ( Schema::palettes() as $palette ) {
			if ( $palette['id'] === $value ) {
				if ( ! empty( $palette['is_pro'] ) && ! $pro_active ) {
					return '';
				}
				return $value;
			}
		}
		return '';
	}

	/**
	 * Strip anything dangerous from user CSS. The compiler additionally scopes it to the
	 * form wrapper on save so it cannot leak site-wide (plan §9.4).
	 *
	 * @param mixed $css Raw CSS.
	 * @return string
	 */
	protected static function sanitize_css( $css ) {
		$css = (string) $css;
		// Drop tags, @import and any url(javascript:) / expression() vectors.
		$css = wp_strip_all_tags( $css );
		$css = preg_replace( '/@import\b[^;]+;?/i', '', $css );
		$css = preg_replace( '/expression\s*\(/i', '', $css );
		$css = preg_replace( '/url\(\s*[\'"]?\s*javascript:/i', 'url(', $css );
		return trim( $css );
	}

	/**
	 * Is this array a per-device bag (has desktop/tablet/mobile keys) rather than a value
	 * that just happens to be an array (like a box4)?
	 *
	 * @param array $value Value.
	 * @return bool
	 */
	protected static function is_device_bag( $value ) {
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( array_key_exists( $device, $value ) ) {
				return true;
			}
		}
		return false;
	}
}
