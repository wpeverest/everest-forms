<?php
/**
 * Builder-scope payment amount helpers.
 *
 * Currency-aware fallbacks for evf_sanitize_amount() / evf_format_amount() so the
 * free plugin can render LOCKED payment fields' previews IDENTICALLY to Everest
 * Forms Pro (e.g. "$ 0.00"). The real helpers ship in Pro
 * (includes/payments/functions.php, unguarded); these are only ever loaded from
 * a free payment-field class file, which is itself only autoloaded when the Pro
 * class is absent — so there is never a redeclaration conflict.
 *
 * Logic mirrors Pro's evf_format_amount()/evf_sanitize_amount() and reads the
 * same `everest_forms_currency` option, so the rendered amount matches Pro.
 *
 * @package EverestForms\Fields
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'evf_get_currencies' ) ) {
	/**
	 * Currency definitions (subset mirroring Everest Forms Pro values). Falls back
	 * to USD for any currency not listed.
	 *
	 * @return array
	 */
	function evf_get_currencies() {
		return array(
			'USD' => array( 'symbol' => '&#36;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'GBP' => array( 'symbol' => '&pound;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'EUR' => array( 'symbol' => '&euro;', 'symbol_pos' => 'right', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'AUD' => array( 'symbol' => '&#36;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'BRL' => array( 'symbol' => 'R$', 'symbol_pos' => 'left', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'CAD' => array( 'symbol' => '&#36;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'CHF' => array( 'symbol' => 'CHF', 'symbol_pos' => 'left', 'thousands_separator' => "'", 'decimal_separator' => '.', 'decimals' => 2 ),
			'CZK' => array( 'symbol' => '&#75;&#269;', 'symbol_pos' => 'right', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'DKK' => array( 'symbol' => 'kr.', 'symbol_pos' => 'left', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'HKD' => array( 'symbol' => '&#36;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'HUF' => array( 'symbol' => '&#70;&#116;', 'symbol_pos' => 'right', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'ILS' => array( 'symbol' => '&#8362;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'INR' => array( 'symbol' => '&#8377;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'JPY' => array( 'symbol' => '&yen;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 0 ),
			'MXN' => array( 'symbol' => '&#36;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'MYR' => array( 'symbol' => '&#82;&#77;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'NOK' => array( 'symbol' => 'kr', 'symbol_pos' => 'left', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'NZD' => array( 'symbol' => '&#36;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'PHP' => array( 'symbol' => '&#8369;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'PLN' => array( 'symbol' => '&#122;&#322;', 'symbol_pos' => 'right', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'SEK' => array( 'symbol' => 'kr', 'symbol_pos' => 'left', 'thousands_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2 ),
			'SGD' => array( 'symbol' => '&#36;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'THB' => array( 'symbol' => '&#3647;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
			'ZAR' => array( 'symbol' => '&#82;', 'symbol_pos' => 'left', 'thousands_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2 ),
		);
	}
}

if ( ! function_exists( 'evf_sanitize_amount' ) ) {
	/**
	 * Sanitize a price amount (builder-scope mirror of the Pro helper).
	 *
	 * @param string $amount   Amount value.
	 * @param string $currency Currency code.
	 * @return string
	 */
	function evf_sanitize_amount( $amount, $currency = '' ) {
		if ( empty( $currency ) ) {
			$currency = get_option( 'everest_forms_currency', 'USD' );
		}

		$currency      = strtoupper( $currency );
		$currencies    = evf_get_currencies();
		$thousands_sep = isset( $currencies[ $currency ]['thousands_separator'] ) ? $currencies[ $currency ]['thousands_separator'] : ',';
		$decimal_sep   = isset( $currencies[ $currency ]['decimal_separator'] ) ? $currencies[ $currency ]['decimal_separator'] : '.';

		if ( is_string( $amount ) ) {
			if ( ',' === $decimal_sep && false !== strpos( $amount, $decimal_sep ) ) {
				if ( ( '.' === $thousands_sep || ' ' === $thousands_sep ) && false !== strpos( $amount, $thousands_sep ) ) {
					$amount = str_replace( $thousands_sep, '', $amount );
				} elseif ( empty( $thousands_sep ) && false !== strpos( $amount, '.' ) ) {
					$amount = str_replace( '.', '', $amount );
				}
				$amount = str_replace( $decimal_sep, '.', $amount );
			} elseif ( ',' === $thousands_sep && false !== strpos( $amount, $thousands_sep ) ) {
				$amount = str_replace( $thousands_sep, '', $amount );
			}
		}

		$amount   = preg_replace( '/[^0-9\.]/', '', (string) $amount );
		$decimals = apply_filters( 'evf_sanitize_amount_decimals', 2, $amount );

		return number_format( (float) $amount, $decimals, '.', '' );
	}
}

if ( ! function_exists( 'evf_format_amount' ) ) {
	/**
	 * Format a price amount (builder-scope mirror of the Pro helper).
	 *
	 * @param string  $amount   Amount.
	 * @param boolean $symbol   Whether to prepend/append the currency symbol.
	 * @param string  $currency Currency code.
	 * @return string
	 */
	function evf_format_amount( $amount, $symbol = false, $currency = '' ) {
		if ( empty( $currency ) ) {
			$currency = get_option( 'everest_forms_currency', 'USD' );
		}

		$currency      = strtoupper( $currency );
		$currencies    = evf_get_currencies();
		$thousands_sep = isset( $currencies[ $currency ]['thousands_separator'] ) ? $currencies[ $currency ]['thousands_separator'] : ',';
		$decimal_sep   = isset( $currencies[ $currency ]['decimal_separator'] ) ? $currencies[ $currency ]['decimal_separator'] : '.';
		$sep_found     = ! empty( $decimal_sep ) ? strpos( $amount, $decimal_sep ) : false;

		if ( ',' === $decimal_sep && false !== $sep_found ) {
			$whole  = substr( $amount, 0, $sep_found );
			$part   = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
			$amount = $whole . '.' . $part;
		}

		if ( ',' === $thousands_sep && false !== strpos( (string) $amount, $thousands_sep ) ) {
			$amount = (float) floatval( str_replace( ',', '', $amount ) );
		}

		if ( empty( $amount ) ) {
			$amount = 0;
		}

		$decimals = apply_filters( 'evf_sanitize_amount_decimals', 2, $amount );
		$number   = number_format( (float) $amount, $decimals, $decimal_sep, $thousands_sep );

		if ( $symbol && isset( $currencies[ $currency ]['symbol'], $currencies[ $currency ]['symbol_pos'] ) ) {
			$symbol_padding = apply_filters( 'evf_currency_symbol_padding', ' ' );
			if ( 'right' === $currencies[ $currency ]['symbol_pos'] ) {
				$number .= $symbol_padding . $currencies[ $currency ]['symbol'];
			} else {
				$number = $currencies[ $currency ]['symbol'] . $symbol_padding . $number;
			}
		}

		if ( 'EUR' === $currency ) {
			$number = str_replace( '.', '', $number );
		}

		return $number;
	}
}
