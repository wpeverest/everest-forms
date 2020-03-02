<?php
/**
 * Deprecated Functions
 *
 * Where functions come to die.
 *
 * @package EverestForms\Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs a deprecated action with notice only if used.
 *
 * @since 1.0.0
 * @param string $tag         The name of the action hook.
 * @param array  $args        Array of additional function arguments to be passed to do_action().
 * @param string $version     The version of EverestForms that deprecated the hook.
 * @param string $replacement The hook that should have been used.
 * @param string $message     A message regarding the change.
 */
function evf_do_deprecated_action( $tag, $args, $version, $replacement = null, $message = null ) {
	if ( ! has_action( $tag ) ) {
		return;
	}

	evf_deprecated_hook( $tag, $version, $replacement, $message );
	do_action_ref_array( $tag, $args );
}

/**
 * Wrapper for deprecated functions so we can apply some extra logic.
 *
 * @since 1.0.0
 * @param string $function    Function used.
 * @param string $version     Version the message was added in.
 * @param string $replacement Replacement for the called function.
 */
function evf_deprecated_function( $function, $version, $replacement = null ) {
	// @codingStandardsIgnoreStart
	if ( is_ajax() ) {
		do_action( 'deprecated_function_run', $function, $replacement, $version );
		$log_string  = "The {$function} function is deprecated since version {$version}.";
		$log_string .= $replacement ? " Replace with {$replacement}." : '';
		error_log( $log_string );
	} else {
		_deprecated_function( $function, $version, $replacement );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * Wrapper for deprecated hook so we can apply some extra logic.
 *
 * @since 1.0.0
 * @param string $hook        The hook that was used.
 * @param string $version     The version of WordPress that deprecated the hook.
 * @param string $replacement The hook that should have been used.
 * @param string $message     A message regarding the change.
 */
function evf_deprecated_hook( $hook, $version, $replacement = null, $message = null ) {
	// @codingStandardsIgnoreStart
	if ( is_ajax() ) {
		do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

		$message    = empty( $message ) ? '' : ' ' . $message;
		$log_string = "{$hook} is deprecated since version {$version}";
		$log_string .= $replacement ? "! Use {$replacement} instead." : ' with no alternative available.';

		error_log( $log_string . $message );
	} else {
		_deprecated_hook( $hook, $version, $replacement, $message );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * When catching an exception, this allows us to log it if unexpected.
 *
 * @since 1.0.0
 * @param Exception $exception_object The exception object.
 * @param string    $function The function which threw exception.
 * @param array     $args The args passed to the function.
 */
function evf_caught_exception( $exception_object, $function = '', $args = array() ) {
	// @codingStandardsIgnoreStart
	$message  = $exception_object->getMessage();
	$message .= '. Args: ' . print_r( $args, true ) . '.';

	do_action( 'everest_forms_caught_exception', $exception_object, $function, $args );
	error_log( "Exception caught in {$function}. {$message}." );
	// @codingStandardsIgnoreEnd
}

/**
 * Wrapper for evf_doing_it_wrong.
 *
 * @since 1.0.0
 * @param string $function Function used.
 * @param string $message  Message to log.
 * @param string $version  Version the message was added in.
 */
function evf_doing_it_wrong( $function, $message, $version ) {
	// @codingStandardsIgnoreStart
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * Wrapper for deprecated arguments so we can apply some extra logic.
 *
 * @since 1.0.0
 * @param string $argument Argument used.
 * @param string $version  Version the message was added in.
 * @param string $message  A message regarding the change.
 */
function evf_deprecated_argument( $argument, $version, $message = null ) {
	// @codingStandardsIgnoreStart
	if ( is_ajax() ) {
		do_action( 'deprecated_argument_run', $argument, $message, $version );
		error_log( "The {$argument} argument is deprecated since version {$version}. {$message}" );
	} else {
		_deprecated_argument( $argument, $version, $message );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * @deprecated 1.1.6
 */
function evf_sender_name() {
	evf_deprecated_function( 'evf_sender_name', '1.1.6' );
}

/**
 * @deprecated 1.1.6
 */
function evf_sender_address() {
	evf_deprecated_function( 'evf_sender_address', '1.1.6' );
}

/**
 * @deprecated 1.2.0
 */
function get_form_data_by_meta_key( $form_id, $meta_key ) {
	evf_deprecated_function( 'get_form_data_by_meta_key', '1.2.0', 'evf_get_form_data_by_meta_key' );
	return evf_get_form_data_by_meta_key( $form_id, $meta_key );
}

/**
 * @deprecated 1.2.0
 */
function evf_query_string_form_fields( $values = null, $exclude = array(), $current_key = '', $return = false ) {
	evf_deprecated_function( 'evf_sender_address', '1.2.0' );
}

/**
 * @deprecated 1.2.0
 */
function everest_forms_sanitize_textarea_field( $string ) {
	evf_deprecated_function( 'everest_forms_sanitize_textarea_field', '1.2.0', 'evf_sanitize_textarea_field' );
	return evf_sanitize_textarea_field( $string );
}

/**
 * @deprecated 1.3.0
 */
function evf_get_us_states() {
	evf_deprecated_function( 'evf_get_us_states', '1.3.0' );
}

/**
 * @deprecated 1.3.0
 */
function get_all_email_fields_by_form_id( $form_id ) {
	evf_deprecated_function( 'get_all_email_fields_by_form_id', '1.3.0', 'evf_get_all_email_fields_by_form_id' );
	return evf_get_all_email_fields_by_form_id( $form_id );
}

/**
 * @deprecated 1.3.0
 */
function get_all_form_fields_by_form_id( $form_id ) {
	evf_deprecated_function( 'get_all_form_fields_by_form_id', '1.3.0', 'evf_get_all_form_fields_by_form_id' );
	return evf_get_all_form_fields_by_form_id( $form_id );
}

/**
 * @deprecated 1.5.7
 */
function evf_has_date_field( $form_id ) {
	evf_deprecated_function( 'evf_has_date_field', '1.5.7', 'evf_is_field_exists' );
	return evf_is_field_exists( $form_id, 'date-time' );
}
