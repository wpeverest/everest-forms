<?php
/**
 * Contains Validation functions
 *
 * @version  1.0.0
 * @package  EverestForms/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Validation Class.
 */
class EVF_Validation {

	/**
	 * Validates an email using WordPress native is_email function.
	 *
	 * @param  string $email Email address to validate.
	 * @return bool
	 */
	public static function is_email( $email ) {
		return is_email( $email );
	}

	/**
	 * Validates a phone number using a regular expression.
	 *
	 * @param  string $phone Phone number to validate.
	 * @return bool
	 */
	public static function is_phone( $phone ) {
		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)]/', '', $phone ) ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Format the phone number.
	 *
	 * @param  mixed $tel Phone number to format.
	 * @return string
	 */
	public static function format_phone( $tel ) {
		return evf_format_phone_number( $tel );
	}
}
