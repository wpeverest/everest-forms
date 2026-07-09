<?php
/**
 * Style Customizer v2 — Compiler.
 *
 * Turns a (sanitized) v2 record into the per-form CSS custom-property block, scoped to the
 * form wrapper. This is the v2 half of the "exactly one stylesheet per form" rule: the
 * shared rule template (the selectors that *read* `var(--evf-*)`) is authored once as a
 * static asset; this class only emits the per-form variable values.
 *
 * Live editing in the panel writes the same custom properties straight onto the iframe
 * wrapper (no recompile); save runs this compiler and persists the result.
 *
 * Responsive tokens (margin/padding only) emit tablet/mobile overrides inside media
 * queries; the CSS cascade handles inheritance, so a device only appears when overridden.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Record → CSS-variable compiler.
 */
final class Compiler {

	/**
	 * Compile a form's record into scoped CSS custom properties.
	 *
	 * @param array      $record  Sanitized v2 record (`tokens` map).
	 * @param int|string $form_id Form id.
	 * @return string CSS (empty string if nothing to emit).
	 */
	public static function compile( $record, $form_id ) {
		$selector = self::wrapper_selector( $form_id );
		$tokens     = ( isset( $record['tokens'] ) && is_array( $record['tokens'] ) ) ? $record['tokens'] : array();
		$theme_font = ! empty( $tokens['fonts.theme']['desktop'] );
		$pro_active = Engine::pro_active();

		// Base (desktop) declarations for every token that maps to a variable. Pro-tier tokens
		// only emit when the Pro tier is active — so a form previously styled with Pro renders
		// its pro styling neutrally (defaults) if Pro is later deactivated, without destroying the
		// stored values. (Pro tokens are also physically absent from a free schema.)
		$base = array();
		foreach ( Schema::tokens() as $token ) {
			if ( ! $pro_active && isset( $token['tier'] ) && 'pro' === $token['tier'] ) {
				continue;
			}
			$value = self::resolve( $tokens, $token, 'desktop' );
			$base  = array_merge( $base, self::declarations( $token, $value, $theme_font ) );
		}

		$css = '';
		if ( $base ) {
			$css .= $selector . '{' . self::join( $base ) . '}';
		}

		// Responsive (spacing) overrides per breakpoint.
		foreach ( self::breakpoints() as $device => $max_width ) {
			$decls = array();
			foreach ( Schema::tokens() as $token ) {
				if ( empty( $token['responsive'] ) ) {
					continue;
				}
				if ( ! $pro_active && isset( $token['tier'] ) && 'pro' === $token['tier'] ) {
					continue;
				}
				if ( ! isset( $tokens[ $token['key'] ][ $device ] ) ) {
					continue; // No override → inherits desktop.
				}
				$value = $tokens[ $token['key'] ][ $device ];
				$decls = array_merge( $decls, self::declarations( $token, $value, $theme_font ) );
			}
			if ( $decls ) {
				$css .= '@media (max-width:' . (int) $max_width . 'px){' . $selector . '{' . self::join( $decls ) . '}}';
			}
		}

		return $css;
	}

	/**
	 * Scope a user's custom CSS to the form wrapper so it can never leak site-wide (plan §9.4).
	 * Every top-level rule's selector list is prefixed with the wrapper selector; `@media` /
	 * `@supports` / `@container` blocks are recursed into; other at-rules (`@keyframes`,
	 * `@font-face`) pass through untouched. Input is already tag/@import/expression-stripped by
	 * {@see Sanitizer::sanitize_css()}.
	 *
	 * @param string     $css     Sanitized custom CSS.
	 * @param int|string $form_id Form id.
	 * @return string Scoped CSS (empty if nothing to emit).
	 */
	public static function scope_custom_css( $css, $form_id ) {
		$css = trim( (string) $css );
		if ( '' === $css ) {
			return '';
		}
		return self::scope_block( $css, self::wrapper_selector( $form_id ) );
	}

	/**
	 * Recursively prefix every rule selector in a CSS block with a scope selector.
	 *
	 * @param string $css   CSS block body.
	 * @param string $scope Scope selector (e.g. `#evf-12`).
	 * @return string
	 */
	protected static function scope_block( $css, $scope ) {
		$out = '';
		$len = strlen( $css );
		$i   = 0;

		while ( $i < $len ) {
			$brace = strpos( $css, '{', $i );
			if ( false === $brace ) {
				break; // Trailing junk with no block — drop it.
			}
			$prelude = trim( substr( $css, $i, $brace - $i ) );

			// Find the matching close brace (track nesting).
			$depth = 1;
			$j     = $brace + 1;
			while ( $j < $len && $depth > 0 ) {
				if ( '{' === $css[ $j ] ) {
					++$depth;
				} elseif ( '}' === $css[ $j ] ) {
					--$depth;
				}
				++$j;
			}
			$body = substr( $css, $brace + 1, $j - $brace - 2 );

			if ( '' !== $prelude && '@' === $prelude[0] ) {
				$lower = strtolower( $prelude );
				if ( 0 === strpos( $lower, '@media' ) || 0 === strpos( $lower, '@supports' ) || 0 === strpos( $lower, '@container' ) ) {
					$out .= $prelude . '{' . self::scope_block( $body, $scope ) . '}';
				} else {
					// @keyframes / @font-face / @page — not selector-based, keep as-is.
					$out .= $prelude . '{' . $body . '}';
				}
			} elseif ( '' !== $prelude ) {
				$selectors = array_filter( array_map( 'trim', explode( ',', $prelude ) ) );
				$scoped    = array();
				foreach ( $selectors as $selector ) {
					$scoped[] = $scope . ' ' . $selector;
				}
				$out .= implode( ',', $scoped ) . '{' . $body . '}';
			}

			$i = $j;
		}

		return $out;
	}

	/**
	 * The wrapper selector for a form. Matches the existing engine's `#evf-{id}` (inside
	 * `.everest-forms`); custom properties inherit from it to every field.
	 *
	 * @param int|string $form_id Form id.
	 * @return string
	 */
	public static function wrapper_selector( $form_id ) {
		$selector = '#evf-' . $form_id;
		/**
		 * Filter the wrapper selector the v2 variables are scoped to.
		 *
		 * @param string     $selector Selector.
		 * @param int|string $form_id  Form id.
		 */
		return apply_filters( 'evf_style_v2_wrapper_selector', $selector, $form_id );
	}

	/**
	 * Responsive breakpoints (device => max-width px). Shared with the preview widths so
	 * the panel and the live site always agree.
	 *
	 * @return array
	 */
	public static function breakpoints() {
		/**
		 * Filter the compiled responsive breakpoints.
		 *
		 * @param array $breakpoints device => max-width.
		 */
		return apply_filters(
			'evf_style_v2_breakpoints',
			array(
				'tablet' => 768,
				'mobile' => 480,
			)
		);
	}

	/* --------------------------------------------------------------------- *
	 * Emission
	 * --------------------------------------------------------------------- */

	/**
	 * The CSS declarations (`--var: value`) a token contributes for one value. A font-style
	 * token expands to four; a var-less token (opacity, choice variation…) contributes none.
	 *
	 * @param array $token      Token definition.
	 * @param mixed $value      Resolved value.
	 * @param bool  $theme_font Whether "use theme fonts" is on.
	 * @return array List of `--var:value` strings.
	 */
	protected static function declarations( $token, $value, $theme_font ) {
		if ( null === $value ) {
			return array();
		}

		if ( 'fontstyle' === $token['type'] ) {
			$v = is_array( $value ) ? $value : array();
			return array(
				$token['vars']['weight'] . ':' . ( ! empty( $v['bold'] ) ? '700' : $token['neutral_weight'] ),
				$token['vars']['style'] . ':' . ( ! empty( $v['italic'] ) ? 'italic' : 'normal' ),
				$token['vars']['decoration'] . ':' . ( ! empty( $v['underline'] ) ? 'underline' : 'none' ),
				$token['vars']['transform'] . ':' . ( ! empty( $v['uppercase'] ) ? 'uppercase' : 'none' ),
			);
		}

		if ( empty( $token['var'] ) ) {
			return array(); // Meta/data-attribute tokens (opacity, bg preset, choice variation…).
		}

		// "Use theme fonts" overrides the family with `inherit`.
		if ( 'fonts.family' === $token['key'] && $theme_font ) {
			return array( $token['var'] . ':inherit' );
		}

		$formatted = self::format( $token, $value );
		if ( '' === $formatted ) {
			return array();
		}
		return array( $token['var'] . ':' . $formatted );
	}

	/**
	 * Format a single value into its CSS representation.
	 *
	 * @param array $token Token definition.
	 * @param mixed $value Value.
	 * @return string
	 */
	protected static function format( $token, $value ) {
		switch ( $token['type'] ) {
			case 'slider':
				if ( ! is_numeric( $value ) ) {
					return '';
				}
				$unit = isset( $token['unit'] ) ? $token['unit'] : 'px';
				return $value . $unit;

			case 'box4':
				return self::format_box4( $token, $value );

			case 'media':
				return $value ? 'url("' . esc_url_raw( $value ) . '")' : 'none';

			case 'color':
			case 'select':
			default:
				return ( '' === $value || null === $value ) ? '' : self::css_safe( (string) $value );
		}
	}

	/**
	 * Defense-in-depth: strip anything that could break out of a CSS declaration/rule.
	 * Values are already schema-sanitized (colours validated, selects whitelisted), so this
	 * is belt-and-suspenders — a stray value can never inject a new selector or comment.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	protected static function css_safe( $value ) {
		return preg_replace( '#[<>{};\\\\]|/\*|\*/#', '', $value );
	}

	/**
	 * Format a 4-side box ({top,right,bottom,left}[,unit]) into a CSS shorthand `t r b l`.
	 *
	 * @param array $token Token definition.
	 * @param mixed $value Associative box.
	 * @return string
	 */
	protected static function format_box4( $token, $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		$unit = 'px';
		if ( ! empty( $token['units'] ) ) {
			$unit = ( isset( $value['unit'] ) && in_array( $value['unit'], $token['units'], true ) ) ? $value['unit'] : $token['units'][0];
		}
		$out = array();
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( ! isset( $value[ $side ] ) ) {
				return '';
			}
			$out[] = ( (int) $value[ $side ] ) . $unit;
		}
		return implode( ' ', $out );
	}

	/**
	 * Resolve a token's value for a device: the device value, else the desktop value, else
	 * the schema default (desktop only).
	 *
	 * @param array  $tokens Record tokens map.
	 * @param array  $token  Token definition.
	 * @param string $device Device.
	 * @return mixed
	 */
	protected static function resolve( $tokens, $token, $device ) {
		$key = $token['key'];
		if ( isset( $tokens[ $key ][ $device ] ) ) {
			return $tokens[ $key ][ $device ];
		}
		if ( 'desktop' === $device ) {
			return isset( $tokens[ $key ]['desktop'] ) ? $tokens[ $key ]['desktop'] : $token['default'];
		}
		return null;
	}

	/**
	 * Join declarations into a rule body.
	 *
	 * @param array $decls Declarations.
	 * @return string
	 */
	protected static function join( $decls ) {
		return implode( ';', $decls ) . ';';
	}
}
