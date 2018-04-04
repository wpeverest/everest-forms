<?php
/**
 * EverestForms Formatting
 *
 * Functions for formatting data.
 *
 * @author 		WPEverest
 * @category 	Core
 * @package 	WPEverest/Functions
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Converts a string (e.g. yes or no) to a bool.
 * @since      1.0.0
 * @param string $string
 * @return bool
 */
function evf_string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
}

/**
 * Converts a bool to a string.
 * @since      1.0.0
 * @param bool $bool
 * @return string yes or no
 */
function evf_bool_to_string( $bool ) {
	if ( ! is_bool( $bool ) ) {
		$bool = evf_string_to_bool( $bool );
	}
	return true === $bool ? 'yes' : 'no';
}

/**
 * Explode a string into an array by $delimiter and remove empty values.
 * @since      1.0.0
 * @param string $string
 * @param string $delimiter
 * @return array
 */
function evf_string_to_array( $string, $delimiter = ',' ) {
	return is_array( $string ) ? $string : array_filter( explode( $delimiter, $string ) );
}

/**
 * Sanitize taxonomy names. Slug format (no spaces, lowercase).
 *
 * urldecode is used to reverse munging of UTF8 characters.
 *
 * @param mixed $taxonomy
 * @return string
 */
function evf_sanitize_taxonomy_name( $taxonomy ) {
	return apply_filters( 'sanitize_taxonomy_name', urldecode( sanitize_title( urldecode( $taxonomy ) ) ), $taxonomy );
}

/**
 * Sanitize permalink values before insertion into DB.
 *
 * Cannot use evf_clean because it sometimes strips % chars and breaks the user's setting.
 *
 * @since      1.0.0
 * @param  string $value
 * @return string
 */
function evf_sanitize_permalink( $value ) {
	global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

	if ( is_wp_error( $value ) ) {
		$value = '';
	}

	$value = esc_url_raw( $value );
	$value = str_replace( 'http://', '', $value );
	return untrailingslashit( $value );
}

/**
 * Gets the filename part of a download URL.
 *
 * @param string $file_url
 * @return string
 */
function evf_get_filename_from_url( $file_url ) {
	$parts = parse_url( $file_url );
	if ( isset( $parts['path'] ) ) {
		return basename( $parts['path'] );
	}
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 * @param string|array $var
 * @return string|array
 */
function evf_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'evf_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Run evf_clean over posted textarea but maintain line breaks.
 * @since      1.0.0
 * @param string $var
 * @return string
 */
function evf_sanitize_textarea( $var ) {
	return implode( "\n", array_map( 'evf_clean', explode( "\n", $var ) ) );
}

/**
 * Sanitize a string destined to be a tooltip.
 *
 * @param string $var
 * @return string
 */
function evf_sanitize_tooltip( $var ) {
	return htmlspecialchars( wp_kses( html_entity_decode( $var ), array(
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
		'small'  => array(),
		'span'   => array(),
		'ul'     => array(),
		'li'     => array(),
		'ol'     => array(),
		'p'      => array(),
	) ) );
}

function evf_sanitize_array_combine( $array ) {
	if ( empty( $array ) || ! is_array( $array ) ) {
		return $array;
	}

	return array_map( 'sanitize_text_field', $array );
}

/**
 * Merge two arrays.
 *
 * @param array $a1
 * @param array $a2
 * @return array
 */
function evf_array_overlay( $a1, $a2 ) {
	foreach ( $a1 as $k => $v ) {
		if ( ! array_key_exists( $k, $a2 ) ) {
			continue;
		}
		if ( is_array( $v ) && is_array( $a2[ $k ] ) ) {
			$a1[ $k ] = evf_array_overlay( $v, $a2[ $k ] );
		} else {
			$a1[ $k ] = $a2[ $k ];
		}
	}
	return $a1;
}

/**
 * let_to_num function.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @param $size
 * @return int
 */
function evf_let_to_num( $size ) {
	$l   = substr( $size, -1 );
	$ret = substr( $size, 0, -1 );
	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024;
		case 'T':
			$ret *= 1024;
		case 'G':
			$ret *= 1024;
		case 'M':
			$ret *= 1024;
		case 'K':
			$ret *= 1024;
	}
	return $ret;
}

/**
 * EverestForms Date Format - Allows to change date format for everything EverestForms.
 *
 * @return string
 */
function evf_date_format() {
	return apply_filters( 'everest_forms_date_format', get_option( 'date_format' ) );
}

/**
 * EverestForms Time Format - Allows to change time format for everything EverestForms.
 *
 * @return string
 */
function evf_time_format() {
	return apply_filters( 'everest_forms_time_format', get_option( 'time_format' ) );
}

/**
 * Convert mysql datetime to PHP timestamp, forcing UTC. Wrapper for strtotime.
 *
 * @since      1.0.0
 *
 * @param string $time_string
 * @param int|null $from_timestamp
 *
 * @return int
 */
function evf_string_to_timestamp( $time_string, $from_timestamp = null ) {
	$original_timezone = date_default_timezone_get();

	// @codingStandardsIgnoreStart
	date_default_timezone_set( 'UTC' );

	if ( null === $from_timestamp ) {
		$next_timestamp = strtotime( $time_string );
	} else {
		$next_timestamp = strtotime( $time_string, $from_timestamp );
	}

	date_default_timezone_set( $original_timezone );
	// @codingStandardsIgnoreEnd

	return $next_timestamp;
}

/**
 * Callback which can flatten post meta (gets the first value if it's an array).
 *
 * @since      1.0.0
 * @param  array $value
 * @return mixed
 */
function evf_flatten_meta_callback( $value ) {
	return is_array( $value ) ? current( $value ) : $value;
}

if ( ! function_exists( 'evf_rgb_from_hex' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colors.
	 *
	 * @param mixed $color
	 *
	 * @return array
	 */
	function evf_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb      = array();
		$rgb['R'] = hexdec( $color{0} . $color{1} );
		$rgb['G'] = hexdec( $color{2} . $color{3} );
		$rgb['B'] = hexdec( $color{4} . $color{5} );

		return $rgb;
	}
}

if ( ! function_exists( 'evf_hex_darker' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colors.
	 *
	 * @param mixed $color
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function evf_hex_darker( $color, $factor = 30 ) {
		$base  = evf_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = $v / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = "0" . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'evf_hex_lighter' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colors.
	 *
	 * @param mixed $color
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function evf_hex_lighter( $color, $factor = 30 ) {
		$base  = evf_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = "0" . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'evf_light_or_dark' ) ) {

	/**
	 * Detect if we should use a light or dark color on a background color.
	 *
	 * @param mixed $color
	 * @param string $dark (default: '#000000')
	 * @param string $light (default: '#FFFFFF')
	 * @return string
	 */
	function evf_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {

		$hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );

		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155 ? $dark : $light;
	}
}

if ( ! function_exists( 'evf_format_hex' ) ) {

	/**
	 * Format string as hex.
	 *
	 * @param string $hex
	 * @return string
	 */
	function evf_format_hex( $hex ) {

		$hex = trim( str_replace( '#', '', $hex ) );

		if ( strlen( $hex ) == 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return $hex ? '#' . $hex : null;
	}
}

/**
 * Format phone numbers.
 *
 * @param  string $phone Phone number.
 * @return string
 */
function evf_format_phone_number( $phone ) {
	return str_replace( '.', '-', $phone );
}

/**
 * Wrapper for mb_strtoupper which see's if supported first.
 *
 * @since      1.0.0
 * @param  string $string
 * @return string
 */
function evf_strtoupper( $string ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $string ) : strtoupper( $string );
}

/**
 * Make a string lowercase.
 * Try to use mb_strtolower() when available.
 *
 * @since      1.0.0
 * @param  string $string
 * @return string
 */
function evf_strtolower( $string ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string ) : strtolower( $string );
}

/**
 * Trim a string and append a suffix.
 * @param  string  $string
 * @param  integer $chars
 * @param  string  $suffix
 * @return string
 */
function evf_trim_string( $string, $chars = 200, $suffix = '...' ) {
	if ( strlen( $string ) > $chars ) {
		if ( function_exists( 'mb_substr' ) ) {
			$string = mb_substr( $string, 0, ( $chars - mb_strlen( $suffix ) ) ) . $suffix;
		} else {
			$string = substr( $string, 0, ( $chars - strlen( $suffix ) ) ) . $suffix;
		}
	}
	return $string;
}

/**
 * Format content to display shortcodes.
 *
 * @since      1.0.0
 * @param  string $raw_string
 * @return string
 */
function evf_format_content( $raw_string ) {
	return apply_filters( 'everest_forms_format_content', apply_filters( 'everest_forms_short_description', $raw_string ), $raw_string );
}

/**
 * Process oEmbeds.
 *
 * @since      1.0.0
 * @param string $content
 * @return string
 */
function evf_do_oembeds( $content ) {
	global $wp_embed;

	$content = $wp_embed->autoembed( $content );

	return $content;
}

/**
 * Array merge and sum function.
 *
 * Source:  https://gist.github.com/Nickology/f700e319cbafab5eaedc
 *
 * @since      1.0.0
 * @return array
 */
function evf_array_merge_recursive_numeric() {
	$arrays = func_get_args();

	// If there's only one array, it's already merged.
	if ( 1 === count( $arrays ) ) {
		return $arrays[0];
	}

	// Remove any items in $arrays that are NOT arrays.
	foreach ( $arrays as $key => $array ) {
		if ( ! is_array( $array ) ) {
			unset( $arrays[ $key ] );
		}
	}

	// We start by setting the first array as our final array.
	// We will merge all other arrays with this one.
	$final = array_shift( $arrays );

	foreach ( $arrays as $b ) {
		foreach ( $final as $key => $value ) {
			// If $key does not exist in $b, then it is unique and can be safely merged.
			if ( ! isset( $b[ $key ] ) ) {
				$final[ $key ] = $value;
			} else {
				// If $key is present in $b, then we need to merge and sum numeric values in both.
				if ( is_numeric( $value ) && is_numeric( $b[ $key ] ) ) {
					// If both values for these keys are numeric, we sum them.
					$final[ $key ] = $value + $b[ $key ];
				} elseif ( is_array( $value ) && is_array( $b[ $key ] ) ) {
					// If both values are arrays, we recursively call ourself.
					$final[ $key ] = evf_array_merge_recursive_numeric( $value, $b[ $key ] );
				} else {
					// If both keys exist but differ in type, then we cannot merge them.
					// In this scenario, we will $b's value for $key is used.
					$final[ $key ] = $b[ $key ];
				}
			}
		}

		// Finally, we need to merge any keys that exist only in $b.
		foreach ( $b as $key => $value ) {
			if ( ! isset( $final[ $key ] ) ) {
				$final[ $key ] = $value;
			}
		}
	}

	return $final;
}
