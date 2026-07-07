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

		$in_tokens  = isset( $record['tokens'] ) && is_array( $record['tokens'] ) ? $record['tokens'] : array();
		$out_tokens = array();
		foreach ( Schema::tokens() as $token ) {
			$key = $token['key'];
			if ( ! array_key_exists( $key, $in_tokens ) ) {
				continue; // Absent → the compiler falls back to the schema default.
			}
			$out_tokens[ $key ] = self::sanitize_token( $token, $in_tokens[ $key ] );
		}
		$clean['tokens'] = $out_tokens;

		if ( isset( $record['palette'] ) ) {
			$clean['palette'] = self::sanitize_palette_id( $record['palette'] );
		}
		if ( isset( $record['template'] ) ) {
			$clean['template'] = sanitize_key( $record['template'] );
		}
		if ( isset( $record['custom_css'] ) ) {
			$clean['custom_css'] = self::sanitize_css( $record['custom_css'] );
		}

		$clean['_updated_at'] = time();

		return $clean;
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
		$floor     = $allow_neg ? -1000 : 0;
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
	 * A palette id must match one of the registered palettes, else empty (custom/none).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected static function sanitize_palette_id( $value ) {
		$value = sanitize_key( $value );
		foreach ( Schema::palettes() as $palette ) {
			if ( $palette['id'] === $value ) {
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
