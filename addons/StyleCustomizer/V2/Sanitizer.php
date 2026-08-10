<?php
/**
 * Style Customizer v2 — Sanitizer.
 *
 * The single write-path guard: no value reaches the stored record (and therefore the
 * compiler) unless it is valid for its token's type. Driven entirely by {@see Schema}
 * so it can never drift from the control set.
 *
 * Record shape:
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
	 * @param array $record         Raw (untrusted) record.
	 * @param bool  $check_contrast Also run {@see ensure_text_contrast()}. Only the AI style
	 *                              launcher opts in; a normal save must never silently revert a
	 *                              colour a person deliberately chose.
	 * @return array Clean record, always stamped with the current schema version.
	 */
	public static function sanitize_record( $record, $check_contrast = false ) {
		$record = is_array( $record ) ? $record : array();
		$clean  = array( 'schema_version' => Schema::version() );

		$pro_active = Engine::pro_active();

		// Palette first: on a non-Pro site the palette-driven ("free") tokens below are DERIVED
		// from this id, never trusted raw from the client — see the loop below for why.
		$palette_id = isset( $record['palette'] ) ? self::sanitize_palette_id( $record['palette'] ) : '';
		if ( isset( $record['palette'] ) ) {
			$clean['palette'] = $palette_id;
		}
		$free_derived = ( ! $pro_active && '' !== $palette_id ) ? Schema::palette_token_values( $palette_id ) : array();

		$in_tokens  = isset( $record['tokens'] ) && is_array( $record['tokens'] ) ? $record['tokens'] : array();
		$out_tokens = array();
		foreach ( Schema::tokens() as $token ) {
			$key = $token['key'];

			if ( ! $pro_active && isset( $token['tier'] ) && 'free' === $token['tier'] ) {
				// Free tier: the ONLY sanctioned customisation is picking one of the 2 free
				// palettes — never trust a raw client-submitted value for these keys, or a
				// crafted request (an AI response, a hand-built REST call) could paint an
				// arbitrary custom colour scheme through keys that exist only to let the
				// palette picker render at all (see EVF-2708).
				if ( isset( $free_derived[ $key ] ) ) {
					$out_tokens[ $key ] = self::sanitize_token( $token, $free_derived[ $key ] );
				}
				continue;
			}

			if ( ! array_key_exists( $key, $in_tokens ) ) {
				continue; // Absent → the compiler falls back to the schema default.
			}
			// A pro-tier value can never be persisted on a site without Pro.
			if ( ! $pro_active && isset( $token['tier'] ) && 'pro' === $token['tier'] ) {
				continue;
			}
			$out_tokens[ $key ] = self::sanitize_token( $token, $in_tokens[ $key ] );
		}
		if ( $check_contrast ) {
			self::ensure_text_contrast( $out_tokens );
		}

		$clean['tokens'] = $out_tokens;

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
	 * Re-attach any token the OLD record already implied but this save's clean output doesn't,
	 * if Pro is currently inactive — so a save made while Pro is merely undetected doesn't
	 * silently erase pro-tier customisation the form already had. The old record may still be
	 * legacy shape, so it is re-derived through {@see Migrator::migrate_record()} when needed.
	 *
	 * This restores stale FREE-tier (palette-driven) tokens too, not just pro-tier ones: since
	 * {@see self::sanitize_record()} now only ever DERIVES those from a registered free palette id
	 * (never trusting a raw client value — see EVF-2708), a legacy form migrating to v2 with a
	 * bespoke v1 custom colour set that matches no registered palette would otherwise have that
	 * migrated colour dropped on its very first v2 save. Restoring it here (from the same
	 * migrated-legacy `$old_record`) keeps that one-time migration lossless without reopening the
	 * free/pro bypass, since this only ever re-attaches a value the form ALREADY had — it can
	 * never introduce one a fresh crafted request invented this turn.
	 *
	 * @param array $clean      Freshly sanitized record (from self::sanitize_record()).
	 * @param array $old_record The record as it was stored before this save (legacy or v2 shape).
	 * @return array $clean, with any stale token the new save doesn't mention restored.
	 */
	public static function preserve_stale_pro_tokens( array $clean, array $old_record ) {
		if ( Engine::pro_active() ) {
			return $clean;
		}

		if ( Engine::is_v2_record( $old_record ) ) {
			$old_tokens = isset( $old_record['tokens'] ) && is_array( $old_record['tokens'] ) ? $old_record['tokens'] : array();
		} elseif ( ! empty( $old_record ) ) {
			$migrated   = Migrator::migrate_record( $old_record );
			$old_tokens = isset( $migrated['tokens'] ) && is_array( $migrated['tokens'] ) ? $migrated['tokens'] : array();
		} else {
			$old_tokens = array();
		}

		foreach ( $old_tokens as $old_key => $old_value ) {
			if ( ! isset( $clean['tokens'][ $old_key ] ) ) {
				$clean['tokens'][ $old_key ] = $old_value;
			}
		}

		return $clean;
	}

	/**
	 * Drop an AI-generated text-colour override that would be unreadable against the form's own
	 * background (e.g. white label.color with no matching dark wrap.bg). Threshold is low (2:1)
	 * to catch only "vanishes entirely" pairings — a human's deliberate low-but-visible choice
	 * must never be reverted, so only {@see RestController::ai_style()} opts into this.
	 *
	 * @param array $out_tokens Sanitized token map, keyed by token key, modified in place.
	 */
	private static function ensure_text_contrast( array &$out_tokens ) {
		// A background image covers wrap.bg visually, so skip the check rather than judge a
		// colour against a background it isn't actually rendered against.
		if ( ! empty( $out_tokens['wrap.bgImage']['desktop'] ) ) {
			return;
		}

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
		// The font-family select is data-driven (Google Fonts, not baked into schema options),
		// so validate as a font-family string rather than against the fallback option list.
		if ( ! empty( $token['source'] ) && 'google_fonts' === $token['source'] ) {
			return self::sanitize_font_family( $value );
		}

		switch ( $token['type'] ) {
			case 'slider':
				return self::sanitize_number( $token, $value );

			case 'color':
				return self::sanitize_color( $value, $token['default'], ! empty( $token['gradientable'] ) );

			case 'box4':
				return self::sanitize_box4( $token, $value );

			case 'select':
			case 'align':
				return self::sanitize_choice( $token, $value );

			case 'fontstyle':
				return self::sanitize_fontstyle( $token, $value );

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
	 * the default; alpha is preserved so legacy `rgba()` borders survive. When `$gradientable`
	 * is true (only set on background tokens whose CSS rule uses the `background` shorthand,
	 * not `background-color` — see frontend.css), a `linear-gradient()`/`radial-gradient()`
	 * value built from solid colour stops is accepted too.
	 *
	 * @param mixed  $value        Raw value.
	 * @param string $default      Fallback.
	 * @param bool   $gradientable Whether a gradient value is acceptable for this token.
	 * @return string
	 */
	protected static function sanitize_color( $value, $default, $gradientable = false ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ) {
			return strtolower( $value );
		}
		if ( preg_match( '/^rgba?\(\s*[\d.,%\s\/]+\)$/i', $value ) ) {
			return $value;
		}
		if ( $gradientable && self::is_valid_gradient( $value ) ) {
			return $value;
		}
		return $default;
	}

	/**
	 * Whether `$value` is a `linear-gradient()`/`radial-gradient()` built entirely from solid
	 * colour stops (each stop optionally followed by a `<percentage>` position) — e.g.
	 * `linear-gradient(135deg, #3366cc, #7a5cff)`. Deliberately strict: this string is emitted
	 * verbatim into an inline `<style>` block ({@see Compiler::css_safe}), so anything short of
	 * "every part matches a known-safe shape" is rejected rather than guessed at.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	protected static function is_valid_gradient( $value ) {
		if ( ! preg_match( '/^(linear|radial)-gradient\((.*)\)$/is', $value, $m ) ) {
			return false;
		}
		$type  = strtolower( $m[1] );
		$parts = self::split_top_level_commas( $m[2] );
		if ( count( $parts ) < 3 ) { // header + at least 2 colour stops.
			return false;
		}
		$header = trim( $parts[0] );
		$is_header = ( 'linear' === $type && (
			preg_match( '/^-?\d+(\.\d+)?deg$/i', $header ) ||
			preg_match( '/^to\s+(top|bottom|left|right)(\s+(top|bottom|left|right))?$/i', $header )
		) ) || ( 'radial' === $type && preg_match( '/^(circle|ellipse)(\s+at\s+.+)?$/i', $header ) );
		$stops = $is_header ? array_slice( $parts, 1 ) : $parts;
		if ( count( $stops ) < 2 ) {
			return false;
		}
		foreach ( $stops as $stop ) {
			$stop = trim( $stop );
			if ( ! preg_match(
				'/^(#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})|rgba?\([\d.,%\s\/]+\))(\s+-?\d+(\.\d+)?%)?$/i',
				$stop
			) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Split on top-level commas only — skips commas nested inside an `rgba()`/`hsla()` stop
	 * (e.g. `rgba(0,0,0,.5)` inside a gradient's stop list must not be split apart).
	 *
	 * @param string $str String to split.
	 * @return string[]
	 */
	protected static function split_top_level_commas( $str ) {
		$parts = array();
		$depth = 0;
		$cur   = '';
		$len   = strlen( $str );
		for ( $i = 0; $i < $len; $i++ ) {
			$ch = $str[ $i ];
			if ( '(' === $ch ) {
				$depth++;
			} elseif ( ')' === $ch ) {
				$depth--;
			}
			if ( ',' === $ch && 0 === $depth ) {
				$parts[] = $cur;
				$cur     = '';
				continue;
			}
			$cur .= $ch;
		}
		$parts[] = $cur;
		return $parts;
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
	 * Sanitize a font-family value: the empty string (theme font) or a plain family name.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected static function sanitize_font_family( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = preg_replace( '/[^A-Za-z0-9 ,\-\'"]/', '', $value );
		return trim( $value );
	}

	/**
	 * Normalize a font-style flag set to four booleans (old customizer keys), plus an optional
	 * explicit `weight` — validated against the token's own `weight_options`; anything else
	 * (unknown value, or simply absent, true for every pre-existing stored value) becomes ''
	 * ("Auto"), which keeps deriving font-weight from `bold` exactly as before this was added.
	 *
	 * @param array $token Token definition (for weight_options).
	 * @param mixed $value Raw value.
	 * @return array
	 */
	protected static function sanitize_fontstyle( $token, $value ) {
		$value = is_array( $value ) ? $value : array();
		$out   = array();
		foreach ( array( 'bold', 'italic', 'underline', 'uppercase' ) as $flag ) {
			$out[ $flag ] = ! empty( $value[ $flag ] );
		}
		$allowed_weights = array();
		foreach ( ( isset( $token['weight_options'] ) ? $token['weight_options'] : array() ) as $opt ) {
			$allowed_weights[] = (string) $opt['value'];
		}
		$weight        = isset( $value['weight'] ) ? (string) $value['weight'] : '';
		$out['weight'] = in_array( $weight, $allowed_weights, true ) ? $weight : '';
		return $out;
	}

	/* --------------------------------------------------------------------- *
	 * Record-level helpers
	 * --------------------------------------------------------------------- */

	/**
	 * A palette id must match one of the registered palettes, else empty (custom/none). A Pro
	 * palette is rejected unless the Pro tier is active.
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
	 * Strip anything dangerous from user CSS ({@see Compiler::scope_custom_css()} scopes it).
	 *
	 * @param mixed $css Raw CSS.
	 * @return string
	 */
	protected static function sanitize_css( $css ) {
		$css = (string) $css;
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
