<?php
/**
 * EverestForms Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package EverestForms/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Include core functions (available in both admin and frontend).
include EVF_ABSPATH . 'includes/evf-conditional-functions.php';
require EVF_ABSPATH . 'includes/evf-deprecated-functions.php';
include EVF_ABSPATH . 'includes/evf-formatting-functions.php';
include EVF_ABSPATH . 'includes/evf-entry-functions.php';

/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.0
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function evf_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Get template part (for templates like the shop-loop).
 *
 * EVF_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @access public
 *
 * @param mixed  $slug
 * @param string $name (default: '')
 */
function evf_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/everest-forms/slug-name.php
	if ( $name && ! EVF_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}-{$name}.php", EVF()->template_path() . "{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php
	if ( ! $template && $name && file_exists( EVF()->plugin_path() . "/templates/{$slug}-{$name}.php" ) ) {
		$template = EVF()->plugin_path() . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/everest-forms/slug.php
	if ( ! $template && ! EVF_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}.php", EVF()->template_path() . "{$slug}.php" ) );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'evf_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates passing attributes and including the file.
 *
 * @access public
 *
 * @param string $template_name
 * @param array  $args          (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path  (default: '')
 */
function evf_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	$located = evf_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'everest-forms' ), '<code>' . $located . '</code>' ), '1.0.0' );

		return;
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'evf_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'everest_forms_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'everest_forms_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Like evf_get_template, but returns the HTML instead of outputting.
 *
 * @see   evf_get_template
 * @since      1.0.0
 *
 * @param string $template_name
 * @param array  $args
 * @param string $template_path
 * @param string $default_path
 *
 * @return string
 */
function evf_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	evf_get_template( $template_name, $args, $template_path, $default_path );

	return ob_get_clean();
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *        yourtheme        /    $template_path    /    $template_name
 *        yourtheme        /    $template_name
 *        $default_path    /    $template_name
 *
 * @access public
 *
 * @param string $template_name
 * @param string $template_path (default: '')
 * @param string $default_path  (default: '')
 *
 * @return string
 */
function evf_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = EVF()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = EVF()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template/
	if ( ! $template || EVF_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'everest_forms_locate_template', $template, $template_name, $template_path );
}

/**
 * Send HTML emails from EverestForms.
 *
 * @param mixed  $to
 * @param mixed  $subject
 * @param mixed  $message
 * @param string $headers     (default: "Content-Type: text/html\r\n")
 * @param string $attachments (default: "")
 */
function evf_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	$mailer = EVF()->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code
 */
function evf_enqueue_js( $code ) {
	global $evf_queued_js;

	if ( empty( $evf_queued_js ) ) {
		$evf_queued_js = '';
	}

	$evf_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function evf_print_js() {
	global $evf_queued_js;

	if ( ! empty( $evf_queued_js ) ) {
		// Sanitize.
		$evf_queued_js = wp_check_invalid_utf8( $evf_queued_js );
		$evf_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $evf_queued_js );
		$evf_queued_js = str_replace( "\r", '', $evf_queued_js );

		$js = "<!-- Everest Forms JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $evf_queued_js });\n</script>\n";

		/**
		 * Queued jsfilter.
		 *
		 * @since 1.0.0
		 * @param string $js JavaScript code.
		 */
		echo apply_filters( 'everest_forms_queued_js', $js );

		unset( $evf_queued_js );
	}
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @param  string  $name   Name of the cookie being set.
 * @param  string  $value  Value of the cookie.
 * @param  integer $expire Expiry of the cookie.
 * @param  bool    $secure Whether the cookie should be served only over https.
 */
function evf_setcookie( $name, $value, $expire = 0, $secure = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
	}
}

/**
 * Get a log file path.
 *
 * @since 1.0.0
 *
 * @param  string $handle name.
 * @return string the log file path.
 */
function evf_get_log_file_path( $handle ) {
	return EVF_Log_Handler_File::get_log_file_path( $handle );
}

/**
 * Get a csv file name.
 *
 * File names consist of the handle, followed by the date, followed by a hash, .csv.
 *
 * @since 1.3.0
 *
 * @param  string $handle Name.
 * @return bool|string The csv file name or false if cannot be determined.
 */
function evf_get_csv_file_name( $handle ) {
	if ( function_exists( 'wp_hash' ) ) {
		$date_suffix = date( 'Y-m-d', current_time( 'timestamp', true ) );
		$hash_suffix = wp_hash( $handle );
		return sanitize_file_name( implode( '-', array( 'evf-entry-export', $handle, $date_suffix, $hash_suffix ) ) . '.csv' );
	} else {
		evf_doing_it_wrong( __METHOD__, __( 'This method should not be called before plugins_loaded.', 'everest-forms' ), '1.3.0' );
		return false;
	}
}

/**
 * Recursively get page children.
 *
 * @param  int $page_id
 *
 * @return int[]
 */
function evf_get_page_children( $page_id ) {
	$page_ids = get_posts( array(
		'post_parent' => $page_id,
		'post_type'   => 'page',
		'numberposts' => - 1,
		'post_status' => 'any',
		'fields'      => 'ids',
	) );

	if ( ! empty( $page_ids ) ) {
		foreach ( $page_ids as $page_id ) {
			$page_ids = array_merge( $page_ids, evf_get_page_children( $page_id ) );
		}
	}

	return $page_ids;
}

/**
 * Get user agent string.
 * @since      1.0.0
 * @return string
 */
function evf_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
}

// This function can be removed when WP 3.9.2 or greater is required.
if ( ! function_exists( 'hash_equals' ) ) :
	/**
	 * Compare two strings in constant time.
	 *
	 * This function was added in PHP 5.6.
	 * It can leak the length of a string.
	 *
	 * @since      1.0.0
	 *
	 * @param string $a Expected string.
	 * @param string $b Actual string.
	 *
	 * @return bool Whether strings are equal.
	 */
	function hash_equals( $a, $b ) {
		$a_length = strlen( $a );
		if ( strlen( $b ) !== $a_length ) {
			return false;
		}
		$result = 0;

		// Do not attempt to "optimize" this.
		for ( $i = 0; $i < $a_length; $i ++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		return 0 === $result;
	}
endif;

/**
 * Generate a rand hash.
 *
 * @since      1.0.0
 * @return string
 */
function evf_rand_hash() {
	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return bin2hex( openssl_random_pseudo_bytes( 20 ) );
	} else {
		return sha1( wp_rand() );
	}
}

/**
 * Find all possible combinations of values from the input array and return in a logical order.
 * @since      1.0.0
 *
 * @param array $input
 *
 * @return array
 */
function evf_array_cartesian( $input ) {
	$input   = array_filter( $input );
	$results = array();
	$indexes = array();
	$index   = 0;

	// Generate indexes from keys and values so we have a logical sort order
	foreach ( $input as $key => $values ) {
		foreach ( $values as $value ) {
			$indexes[ $key ][ $value ] = $index ++;
		}
	}

	// Loop over the 2D array of indexes and generate all combinations
	foreach ( $indexes as $key => $values ) {
		// When result is empty, fill with the values of the first looped array
		if ( empty( $results ) ) {
			foreach ( $values as $value ) {
				$results[] = array( $key => $value );
			}

			// Second and subsequent input sub-array merging.
		} else {
			foreach ( $results as $result_key => $result ) {
				foreach ( $values as $value ) {
					// If the key is not set, we can set it
					if ( ! isset( $results[ $result_key ][ $key ] ) ) {
						$results[ $result_key ][ $key ] = $value;
						// If the key is set, we can add a new combination to the results array
					} else {
						$new_combination         = $results[ $result_key ];
						$new_combination[ $key ] = $value;
						$results[]               = $new_combination;
					}
				}
			}
		}
	}

	// Sort the indexes
	arsort( $results );

	// Convert indexes back to values
	foreach ( $results as $result_key => $result ) {
		$converted_values = array();

		// Sort the values
		arsort( $results[ $result_key ] );

		// Convert the values
		foreach ( $results[ $result_key ] as $key => $value ) {
			$converted_values[ $key ] = array_search( $value, $indexes[ $key ] );
		}

		$results[ $result_key ] = $converted_values;
	}

	return $results;
}

/**
 * Run a MySQL transaction query, if supported.
 *
 * @param string $type start (default), commit, rollback
 *
 * @since      1.0.0
 */
function evf_transaction_query( $type = 'start' ) {
	global $wpdb;

	$wpdb->hide_errors();

	evf_maybe_define_constant( 'EVF_USE_TRANSACTIONS', true );

	if ( EVF_USE_TRANSACTIONS ) {
		switch ( $type ) {
			case 'commit' :
				$wpdb->query( 'COMMIT' );
				break;
			case 'rollback' :
				$wpdb->query( 'ROLLBACK' );
				break;
			default :
				$wpdb->query( 'START TRANSACTION' );
				break;
		}
	}
}

/**
 * Outputs a "back" link so admin screens can easily jump back a page.
 *
 * @param string $label Title of the page to return to.
 * @param string $url   URL of the page to return to.
 */
function evf_back_link( $label, $url ) {
	echo '<small class="evf-admin-breadcrumb"><a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '">&#x2934;</a></small>';
}

/**
 * Display a EverestForms help tip.
 *
 * @since      1.0.0
 *
 * @param  string $tip        Help tip text
 * @param  bool   $allow_html Allow sanitized HTML if true or escape
 *
 * @return string
 */
function evf_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = evf_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="everest-forms-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 * @since      1.0.0
 *
 * @param int $limit
 */
function evf_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( $limit );
	}
}

/**
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since 1.2.0
 */
function evf_nocache_headers() {
	EVF_Cache_Helper::set_nocache_constants();
	nocache_headers();
}

/**
 * Get a shared logger instance.
 *
 * Use the everest_forms_logging_class filter to change the logging class. You may provide one of the following:
 *     - a class name which will be instantiated as `new $class` with no arguments
 *     - an instance which will be used directly as the logger
 * In either case, the class or instance *must* implement EVF_Logger_Interface.
 *
 * @see EVF_Logger_Interface
 *
 * @return EVF_Logger
 */
function evf_get_logger() {
	static $logger = null;

	$class = apply_filters( 'everest_forms_logging_class', 'EVF_Logger' );

	if ( null === $logger || ! is_a( $logger, $class ) ) {
		$implements = class_implements( $class );

		if ( is_array( $implements ) && in_array( 'EVF_Logger_Interface', $implements, true ) ) {
			if ( is_object( $class ) ) {
				$logger = $class;
			} else {
				$logger = new $class();
			}
		} else {
			evf_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: 1: class name 2: everest_forms_logging_class 3: EVF_Logger_Interface */
					__( 'The class %1$s provided by %2$s filter must implement %3$s.', 'everest-forms' ),
					'<code>' . esc_html( is_object( $class ) ? get_class( $class ) : $class ) . '</code>',
					'<code>everest_forms_logging_class</code>',
					'<code>EVF_Logger_Interface</code>'
				),
				'1.2'
			);
			$logger = is_a( $logger, 'EVF_Logger' ) ? $logger : new EVF_Logger();
		}
	}

	return $logger;
}

/**
 * Prints human-readable information about a variable.
 *
 * Some server environments blacklist some debugging functions. This function provides a safe way to
 * turn an expression into a printable, readable form without calling blacklisted functions.
 *
 * @since      1.0.0
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return     Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string|bool False if expression could not be printed. True if the expression was printed.
 *     If $return is true, a string representation will be returned.
 */
function evf_print_r( $expression, $return = false ) {
	$alternatives = array(
		array( 'func' => 'print_r', 'args' => array( $expression, true ) ),
		array( 'func' => 'var_export', 'args' => array( $expression, true ) ),
		array( 'func' => 'json_encode', 'args' => array( $expression ) ),
		array( 'func' => 'serialize', 'args' => array( $expression ) ),
	);

	$alternatives = apply_filters( 'everest_forms_print_r_alternatives', $alternatives, $expression );

	foreach ( $alternatives as $alternative ) {
		if ( function_exists( $alternative['func'] ) ) {
			$res = call_user_func_array( $alternative['func'], $alternative['args'] );
			if ( $return ) {
				return $res;
			} else {
				echo $res;

				return true;
			}
		}
	}

	return false;
}

/**
 * Registers the default log handler.
 *
 * @since      1.0.0
 *
 * @param array $handlers
 *
 * @return array
 */
function evf_register_default_log_handler( $handlers ) {

	if ( defined( 'EVF_LOG_HANDLER' ) && class_exists( EVF_LOG_HANDLER ) ) {
		$handler_class   = EVF_LOG_HANDLER;
		$default_handler = new $handler_class();
	} else {
		$default_handler = new EVF_Log_Handler_File();
	}

	array_push( $handlers, $default_handler );

	return $handlers;
}

add_filter( 'everest_forms_register_log_handlers', 'evf_register_default_log_handler' );

/**
 * Based on wp_list_pluck, this calls a method instead of returning a property.
 *
 * @since      1.0.0
 *
 * @param array      $list              List of objects or arrays
 * @param int|string $callback_or_field Callback method from the object to place instead of the entire object
 * @param int|string $index_key         Optional. Field from the object to use as keys for the new array.
 *                                      Default null.
 *
 * @return array Array of values.
 */
function evf_list_pluck( $list, $callback_or_field, $index_key = null ) {
	// Use wp_list_pluck if this isn't a callback
	$first_el = current( $list );
	if ( ! is_object( $first_el ) || ! is_callable( array( $first_el, $callback_or_field ) ) ) {
		return wp_list_pluck( $list, $callback_or_field, $index_key );
	}
	if ( ! $index_key ) {
		/*
		 * This is simple. Could at some point wrap array_column()
		 * if we knew we had an array of arrays.
		 */
		foreach ( $list as $key => $value ) {
			$list[ $key ] = $value->{$callback_or_field}();
		}

		return $list;
	}

	/*
	 * When index_key is not set for a particular item, push the value
	 * to the end of the stack. This is how array_column() behaves.
	 */
	$newlist = array();
	foreach ( $list as $value ) {
		// Get index. @since      1.0.0
		if ( is_callable( array( $value, $index_key ) ) ) {
			$newlist[ $value->{$index_key}() ] = $value->{$callback_or_field}();
		} elseif ( isset( $value->$index_key ) ) {
			$newlist[ $value->$index_key ] = $value->{$callback_or_field}();
		} else {
			$newlist[] = $value->{$callback_or_field}();
		}
	}

	return $newlist;
}

/**
 * Switch EverestForms to site language.
 *
 * @since      1.0.0
 */
function evf_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init EVF locale.
		EVF()->load_plugin_textdomain();
	}
}

/**
 * Switch EverestForms language to original.
 *
 * @since      1.0.0
 */
function evf_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init EVF locale.
		EVF()->load_plugin_textdomain();
	}
}

/**
 * Get an item of post data if set, otherwise return a default value.
 *
 * @since      1.0.0
 *
 * @param  string $key
 * @param  string $default
 *
 * @return mixed value sanitized by evf_clean
 */
function evf_get_post_data_by_key( $key, $default = '' ) {
	return evf_clean( evf_get_var( $_POST[ $key ], $default ) );
}

/**
 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
 *
 * @since      1.0.0
 *
 * @param  mixed  $var
 * @param  string $default
 *
 * @return mixed value sanitized by evf_clean
 */
function evf_get_var( &$var, $default = null ) {
	return isset( $var ) ? $var : $default;
}

/**
 * Read in EverestForms headers when reading plugin headers.
 *
 * @since  1.2.0
 * @param  array $headers Headers.
 * @return array
 */
function evf_enable_evf_plugin_headers( $headers ) {
	if ( ! class_exists( 'EVF_Plugin_Updates' ) ) {
		include_once dirname( __FILE__ ) . '/admin/plugin-updates/class-evf-plugin-updates.php';
	}

	$headers['EVFRequires'] = EVF_Plugin_Updates::VERSION_REQUIRED_HEADER;
	$headers['EVFTested']   = EVF_Plugin_Updates::VERSION_TESTED_HEADER;
	return $headers;
}
add_filter( 'extra_plugin_headers', 'evf_enable_evf_plugin_headers' );

/**
 * Delete expired transients.
 *
 * Deletes all expired transients. The multi-table delete syntax is used.
 * to delete the transient record from table a, and the corresponding.
 * transient_timeout record from table b.
 *
 * Based on code inside core's upgrade_network() function.
 *
 * @since  1.0.0
 * @return int Number of transients that were cleared.
 */
function evf_delete_expired_transients() {
	global $wpdb;

	$sql  = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
		AND b.option_value < %d";
	$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) ); // WPCS: unprepared SQL ok.

	$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
		AND b.option_value < %d";
	$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) ); // WPCS: unprepared SQL ok.

	return absint( $rows + $rows2 );
}
add_action( 'everest_forms_installed', 'evf_delete_expired_transients' );

/**
 * Make a URL relative, if possible.
 *
 * @since  1.0.0
 * @param  string $url URL to make relative.
 * @return string
 */
function evf_get_relative_url( $url ) {
	return evf_is_external_resource( $url ) ? $url : str_replace( array( 'http://', 'https://' ), '//', $url );
}

/**
 * See if a resource is remote.
 *
 * @since  1.0.0
 * @param  string $url URL to check.
 * @return bool
 */
function evf_is_external_resource( $url ) {
	$wp_base = str_replace( array( 'http://', 'https://' ), '//', get_home_url( null, '/', 'http' ) );
	return strstr( $url, '://' ) && strstr( $wp_base, $url );
}

/**
 * See if theme/s is activate or not.
 *
 * @since  1.0.0
 * @param  string|array $theme Theme name or array of theme names to check.
 * @return boolean
 */
function evf_is_active_theme( $theme ) {
	return is_array( $theme ) ? in_array( get_template(), $theme, true ) : get_template() === $theme;
}

/**
 * Cleans up session data - cron callback.
 *
 * @since 1.0.0
 */
function evf_cleanup_session_data() {
	$session_class = apply_filters( 'everest_forms_session_handler', 'EVF_Session_Handler' );
	$session       = new $session_class();

	if ( is_callable( array( $session, 'cleanup_sessions' ) ) ) {
		$session->cleanup_sessions();
	}
}
add_action( 'everest_forms_cleanup_sessions', 'evf_cleanup_session_data' );

/**
 * Return the html selected attribute if stringified $value is found in array of stringified $options
 * or if stringified $value is the same as scalar stringified $options.
 *
 * @param string|int       $value   Value to find within options.
 * @param string|int|array $options Options to go through when looking for value.
 * @return string
 */
function evf_selected( $value, $options ) {
	if ( is_array( $options ) ) {
		$options = array_map( 'strval', $options );
		return selected( in_array( (string) $value, $options, true ), true, false );
	}

	return selected( $value, $options, false );
}

/**
 * Retrieve actual fields from a form.
 *
 * Non-posting elements such as section divider, page break, and HTML are
 * automatically excluded. Optionally a white list can be provided.
 *
 * @since      1.0.0
 *
 * @param mixed $form
 * @param array $whitelist
 *
 * @return mixed boolean or array
 */
function evf_get_form_fields( $form = false, $whitelist = array() ) {

	// Accept form (post) object or form ID
	if ( is_object( $form ) ) {
		$form = json_decode( $form->post_content );
	} elseif ( is_numeric( $form ) ) {
		$form = EVF()->form->get(
			$form,
			array(
				'content_only' => true,
			)
		);
	}

	if ( ! is_array( $form ) || empty( $form['form_fields'] ) ) {
		return false;
	}

	// White list of field types to allow
	$allowed_form_fields = array(
		'first-name',
		'last-name',
		'text',
		'textarea',
		'select',
		'radio',
		'checkbox',
		'email',
		'address',
		'country',
		'url',
		'name',
		'hidden',
		'date',
		'phone',
		'number',
		'file-upload',
		'image-upload',
		'payment-single',
		'payment-multiple',
		'payment-checkbox',
		'payment-total',
	);
	$allowed_form_fields = apply_filters( 'everest_forms_allowed_form_fields', $allowed_form_fields );

	$whitelist = ! empty( $whitelist ) ? $whitelist : $allowed_form_fields;

	$form_fields = $form['form_fields'];

	foreach ( $form_fields as $id => $form_field ) {
		if ( ! in_array( $form_field['type'], $whitelist, true ) ) {
			unset( $form_fields[ $id ] );
		}
	}

	return $form_fields;
}

/**
 * @param $string
 *
 * @return string
 */
function evf_sanitize_textarea_field( $string ) {
	if ( empty( $string ) || ! is_string( $string ) ) {
		return $string;
	}

	if ( function_exists( 'sanitize_textarea_field' ) ) {
		$string = sanitize_textarea_field( $string );
	} else {
		$string = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $string ) ) );
	}

	return $string;
}

/**
 * Formats, sanitizes, and returns/echos HTML element ID, classes, attributes,
 * and data attributes.
 *
 * @param string $id
 * @param array  $class
 * @param array  $datas
 * @param array  $atts
 * @param bool   $echo
 *
 * @return string
 */
function evf_html_attributes( $id = '', $class = array(), $datas = array(), $atts = array(), $echo = false ) {
	$id    = trim( $id );
	$parts = array();

	if ( ! empty( $id ) ) {
		$id = sanitize_html_class( $id );
		if ( ! empty( $id ) ) {
			$parts[] = 'id="' . $id . '"';
		}
	}

	if ( ! empty( $class ) ) {
		$class = evf_sanitize_classes( $class, true );
		if ( ! empty( $class ) ) {
			$parts[] = 'class="' . $class . '"';
		}
	}

	if ( ! empty( $datas ) ) {
		foreach ( $datas as $data => $val ) {
			$parts[] = 'data-' . sanitize_html_class( $data ) . '="' . esc_attr( $val ) . '"';
		}
	}

	if ( ! empty( $atts ) ) {
		foreach ( $atts as $att => $val ) {
			if ( '0' == $val || ! empty( $val ) ) {
				$parts[] = sanitize_html_class( $att ) . '="' . esc_attr( $val ) . '"';
			}
		}
	}

	$output = implode( ' ', $parts );

	if ( $echo ) {
		echo trim( $output ); // phpcs:ignore
	} else {
		return trim( $output );
	}
}

/**
 * Sanitizes string of CSS classes.
 *
 * @param array|string $classes
 * @param bool         $convert True will convert strings to array and vice versa.
 *
 * @return string|array
 */
function evf_sanitize_classes( $classes, $convert = false ) {
	$css   = array();
	$array = is_array( $classes );

	if ( ! empty( $classes ) ) {
		if ( ! $array ) {
			$classes = explode( ' ', trim( $classes ) );
		}
		foreach ( $classes as $class ) {
			$css[] = sanitize_html_class( $class );
		}
	}

	if ( $array ) {
		return $convert ? implode( ' ', $css ) : $css;
	} else {
		return $convert ? $css : implode( ' ', $css );
	}
}

/**
 * Performs json_decode and unslash.
 *
 * @since 1.0.0
 *
 * @param string $data
 * @return array|bool
 */
function evf_decode( $data ) {
	if ( ! $data || empty( $data ) ) {
		return false;
	}

	return wp_unslash( json_decode( $data, true ) );
}

/**
 * Performs json_encode and wp_slash.
 *
 * @since      1.0.0
 *
 * @param mixed $data
 *
 * @return string
 */
function evf_encode( $data = false ) {
	if ( empty( $data ) ) {
		return false;
	}

	return wp_slash( wp_json_encode( $data ) );
}

/**
 * @param $min
 * @param $max
 *
 * @return mixed
 */
function evf_crypto_rand_secure( $min, $max ) {
	$range = $max - $min;
	if ( $range < 1 ) {
		return $min;
	} // not so random...
	$log    = ceil( log( $range, 2 ) );
	$bytes  = (int) ( $log / 8 ) + 1; // length in bytes
	$bits   = (int) $log + 1; // length in bits
	$filter = (int) ( 1 << $bits ) - 1; // set all lower bits to 1
	do {
		$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
		$rnd = $rnd & $filter; // discard irrelevant bits
	} while ( $rnd > $range );

	return $min + $rnd;
}

/**
 * @param int $length
 *
 * @return string
 */
function evf_get_random_string( $length = 10 ) {
	$string       = "";
	$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
	$codeAlphabet .= "0123456789";
	$max          = strlen( $codeAlphabet ); // edited
	for ( $i = 0; $i < $length; $i ++ ) {
		$string .= $codeAlphabet[ evf_crypto_rand_secure( 0, $max - 1 ) ];
	}

	return $string;
}

/**
 * Get all forms.
 *
 * @param  bool $skip_disabled_entries True to skip disabled entries.
 * @return array of form data.
 */
function evf_get_all_forms( $skip_disabled_entries = false ) {
	$forms     = array();
	$form_ids  = wp_parse_id_list(
		get_posts(
			array(
				'post_type'   => 'everest_form',
				'numberposts' => -1, // @codingStandardsIgnoreLine
				'status'      => 'publish',
				'fields'      => 'ids',
			)
		)
	);

	if ( ! empty( $form_ids ) ) {
		foreach ( $form_ids as $form_id ) {
			$form      = EVF()->form->get( $form_id );
			$form_data = ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : '';

			if ( $skip_disabled_entries && ( isset( $form_data['settings']['disabled_entries'] ) && '1' === $form_data['settings']['disabled_entries'] ) ) {
				continue;
			}

			$forms[ $form_id ] = $form->post_title;
		}
	}

	return $forms;
}

/**
 * Get random meta-key for field option.
 *
 * @param  array $field Field data array
 * @return string
 */
function evf_get_meta_key_field_option( $field ) {
	$random_number = rand( pow( 10, 3 ), pow( 10, 4 ) - 1 );
	return strtolower( str_replace( array( ' ', '/_' ), array( '_', '' ), $field['label'] ) ) . '_' . $random_number;
}

/**
 * Get current user IP Address.
 *
 * @return string
 */
function evf_get_ip_address() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { // WPCS: input var ok, CSRF ok.
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );  // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // WPCS: input var ok, CSRF ok.
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { // @codingStandardsIgnoreLine
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // @codingStandardsIgnoreLine
	}
	return '';
}

/**
 * Get User Agent browser and OS type
 *
 * @since  1.1.0
 * @return array
 */
function evf_get_browser() {
    $u_agent  = $_SERVER['HTTP_USER_AGENT'];
    $bname    = 'Unknown';
    $platform = 'Unknown';
    $version  = '';

    // First get the platform.
    if ( preg_match( '/linux/i', $u_agent ) ) {
        $platform = 'Linux';
    } elseif ( preg_match( '/macintosh|mac os x/i', $u_agent ) ) {
        $platform = 'MAC OS';
    } elseif ( preg_match( '/windows|win32/i', $u_agent ) ) {
        $platform = 'Windows';
    }

    // Next get the name of the useragent yes seperately and for good reason.
    if ( preg_match( '/MSIE/i',$u_agent ) && ! preg_match( '/Opera/i',$u_agent ) ) {
        $bname = 'Internet Explorer';
        $ub    = 'MSIE';
    } elseif ( preg_match( '/Trident/i',$u_agent ) ) {
        // this condition is for IE11
        $bname = 'Internet Explorer';
        $ub = 'rv';
    } elseif ( preg_match( '/Firefox/i',$u_agent ) ) {
        $bname = 'Mozilla Firefox';
        $ub = 'Firefox';
    } elseif ( preg_match( '/Chrome/i',$u_agent ) ) {
        $bname = 'Google Chrome';
        $ub = 'Chrome';
    } elseif ( preg_match( '/Safari/i',$u_agent ) ) {
        $bname = 'Apple Safari';
        $ub = 'Safari';
    } elseif ( preg_match( '/Opera/i',$u_agent ) ) {
        $bname = 'Opera';
        $ub = 'Opera';
    } elseif ( preg_match( '/Netscape/i',$u_agent ) ) {
        $bname = 'Netscape';
        $ub = 'Netscape';
    }

    // Finally get the correct version number.
    // Added "|:"
    $known = array( 'Version', $ub, 'other' );
    $pattern = '#(?<browser>' . join( '|', $known ) .
     ')[/|: ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if ( ! preg_match_all( $pattern, $u_agent, $matches ) ) {
        // We have no matching number just continue.
    }

    // See how many we have.
    $i = count( $matches['browser'] );

    if ( $i != 1 ) {
        // we will have two since we are not using 'other' argument yet.
        // see if version is before or after the name.
        if ( strripos( $u_agent,'Version' ) < strripos( $u_agent,$ub ) ) {
            $version = $matches['version'][0];
        } else {
            $version = $matches['version'][1];
        }
    } else {
        $version = $matches['version'][0];
    }

    // Check if we have a number.
    if ( $version == null || $version == '' ) {
        $version = '';
    }

    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'   => $pattern
    );
}

/**
 * Get the certain date of a specified day in a specified format.
 *
 * @since 1.1.0
 *
 * @param string $period Supported values: start, end.
 * @param string $timestamp Default is the current timestamp, if left empty.
 * @param string $format Default is a MySQL format.
 *
 * @return string
 */
function evf_get_day_period_date( $period, $timestamp = '', $format = 'Y-m-d H:i:s' ) {
	$date = '';

	if ( empty( $timestamp ) ) {
		$timestamp = time();
	}

	switch ( $period ) {
		case 'start_of_day':
			$date = date( $format, strtotime( 'today', $timestamp ) );
			break;

		case 'end_of_day':
			$date = date( $format, strtotime( 'tomorrow', $timestamp ) - 1 );
			break;

	}

	return $date;
}

/**
 * Get field label by meta key
 *
 * @param  $form_id  Form ID
 * @param  $meta_key Field's meta key
 * @return mixed
 */
function evf_get_form_data_by_meta_key( $form_id, $meta_key ) {
	$get_post     = get_post( $form_id );
	$post_content = json_decode( $get_post->post_content, true ) ;
	$form_fields  = isset( $post_content['form_fields'] ) ? $post_content['form_fields'] : array();

	if ( ! empty( $form_fields ) ) {
		foreach( $form_fields as $field ) {
			if ( isset( $field['meta-key'] ) && $meta_key == $field['meta-key'] ) {
				return $field['label'];
			}
		}
	}

	return false;
}

/**
 * Get all the email fields of a Form.
 *
 * @param int $form_id
 */
function evf_get_all_email_fields_by_form_id( $form_id ) {
	$user_emails = array();
	$form_obj    = EVF()->form->get( $form_id );
	$form_data   = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

	if ( ! empty( $form_data['form_fields'] ) ) {
		foreach ( $form_data['form_fields'] as $form_fields ) {
			if ( 'email' === $form_fields['type'] ) {
				$user_emails[ $form_fields['meta-key'] ] = $form_fields['label'];
			}
		}
	}

	return $user_emails;
}

/**
 * Get all the field's meta-key label pair.
 *
 * @param  int $form_id
 * @return array
 */
function evf_get_all_form_fields_by_form_id( $form_id ) {
	$data      = array();
	$form_obj  = EVF()->form->get( $form_id );
	$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

	if ( ! empty( $form_data['form_fields'] ) ) {
		foreach ( $form_data['form_fields'] as $form_fields ) {
			if ( isset( $form_fields['meta-key'], $form_fields['label'] ) ) {
				$data[ $form_fields['meta-key'] ] = $form_fields['label'];
			}
		}
	}

	return $data;
}

/**
 * Checks whether the content passed contains a specific short code.
 *
 * @since  1.1.4
 * @param  string $tag Shortcode tag to check.
 * @return bool
 */
function evf_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}

/**
 * Convert a file size provided, such as "2M", to bytes.
 *
 * @since 1.2.0
 * @link http://stackoverflow.com/a/22500394
 *
 * @param string $size
 *
 * @return int
 */
function evf_size_to_bytes( $size ) {

	if ( is_numeric( $size ) ) {
		return $size;
	}

	$suffix = substr( $size, - 1 );
	$value  = substr( $size, 0, - 1 );

	switch ( strtoupper( $suffix ) ) {
		case 'P':
			$value *= 1024;
		case 'T':
			$value *= 1024;
		case 'G':
			$value *= 1024;
		case 'M':
			$value *= 1024;
		case 'K':
			$value *= 1024;
			break;
	}

	return $value;
}

/**
 * Convert bytes to megabytes (or in some cases KB).
 *
 * @since 1.2.0
 *
 * @param int $bytes
 *
 * @return string
 */
function evf_size_to_megabytes( $bytes ) {

	if ( $bytes < 1048676 ) {
		return number_format( $bytes / 1024, 1 ) . ' KB';
	} else {
		return round( number_format( $bytes / 1048576, 1 ) ) . ' MB';
	}
}

/**
 * Convert a file size provided, such as "2M", to bytes.
 *
 * @since 1.2.0
 * @link http://stackoverflow.com/a/22500394
 *
 * @param  bool $bytes
 * @return mixed
 */
function evf_max_upload( $bytes = false ) {
	$max = wp_max_upload_size();

	if ( $bytes ) {
		return $max;
	} else {
		return evf_size_to_megabytes( $max );
	}
}

/**
 * Get the required label text, with a filter.
 *
 * @since  1.2.0
 * @return string
 */
function evf_get_required_label() {
	return apply_filters( 'everest_forms_required_label', esc_html__( 'This field is required.', 'everest-forms' ) );
}

/**
 * Get a PRO license plan.
 *
 * @since  1.2.0
 * @return bool|string Plan on success, false on failure.
 */
function evf_get_license_plan() {
	$license_key = get_option( 'everest-forms-pro_license_key' );

	if ( $license_key && is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' ) ) {
		$license_data = get_transient( 'evf_pro_license_plan' );

		if ( false === $license_data ) {
			$license_data = json_decode( EVF_Updater_Key_API::check( array(
				'license' => $license_key,
			) ) );

			if ( ! empty( $license_data->item_plan ) ) {
				set_transient( 'evf_pro_license_plan', $license_data, WEEK_IN_SECONDS );
			}
		}

		return isset( $license_data->item_plan ) ? $license_data->item_plan : false;
	}

	return false;
}

/**
 * Decode special characters, both alpha- (<) and numeric-based (').
 *
 * @since  1.2.0
 * @param  string $string
 * @return string
 */
function evf_decode_string( $string ) {
	if ( ! is_string( $string) ) {
		return $string;
	}

	return wp_kses_decode_entities( html_entity_decode( $string, ENT_QUOTES ) );
}
add_filter( 'everest_forms_email_message', 'evf_decode_string' );

/**
 * Get Countries.
 *
 * @since  1.2.0
 * @return array
 */
function evf_get_countries() {
	$countries = array(
		'AF' => esc_html__( 'Afghanistan', 'everest-forms' ),
		'AX' => esc_html__( 'Åland Islands', 'everest-forms' ),
		'AL' => esc_html__( 'Albania', 'everest-forms' ),
		'DZ' => esc_html__( 'Algeria', 'everest-forms' ),
		'AS' => esc_html__( 'American Samoa', 'everest-forms' ),
		'AD' => esc_html__( 'Andorra', 'everest-forms' ),
		'AO' => esc_html__( 'Angola', 'everest-forms' ),
		'AI' => esc_html__( 'Anguilla', 'everest-forms' ),
		'AQ' => esc_html__( 'Antarctica', 'everest-forms' ),
		'AG' => esc_html__( 'Antigua and Barbuda', 'everest-forms' ),
		'AR' => esc_html__( 'Argentina', 'everest-forms' ),
		'AM' => esc_html__( 'Armenia', 'everest-forms' ),
		'AW' => esc_html__( 'Aruba', 'everest-forms' ),
		'AU' => esc_html__( 'Australia', 'everest-forms' ),
		'AT' => esc_html__( 'Austria', 'everest-forms' ),
		'AZ' => esc_html__( 'Azerbaijan', 'everest-forms' ),
		'BS' => esc_html__( 'Bahamas', 'everest-forms' ),
		'BH' => esc_html__( 'Bahrain', 'everest-forms' ),
		'BD' => esc_html__( 'Bangladesh', 'everest-forms' ),
		'BB' => esc_html__( 'Barbados', 'everest-forms' ),
		'BY' => esc_html__( 'Belarus', 'everest-forms' ),
		'BE' => esc_html__( 'Belgium', 'everest-forms' ),
		'BZ' => esc_html__( 'Belize', 'everest-forms' ),
		'BJ' => esc_html__( 'Benin', 'everest-forms' ),
		'BM' => esc_html__( 'Bermuda', 'everest-forms' ),
		'BT' => esc_html__( 'Bhutan', 'everest-forms' ),
		'BO' => esc_html__( 'Bolivia (Plurinational State of)', 'everest-forms' ),
		'BA' => esc_html__( 'Bosnia and Herzegovina', 'everest-forms' ),
		'BW' => esc_html__( 'Botswana', 'everest-forms' ),
		'BV' => esc_html__( 'Bouvet Island', 'everest-forms' ),
		'BR' => esc_html__( 'Brazil', 'everest-forms' ),
		'IO' => esc_html__( 'British Indian Ocean Territory', 'everest-forms' ),
		'BN' => esc_html__( 'Brunei Darussalam', 'everest-forms' ),
		'BG' => esc_html__( 'Bulgaria', 'everest-forms' ),
		'BF' => esc_html__( 'Burkina Faso', 'everest-forms' ),
		'BI' => esc_html__( 'Burundi', 'everest-forms' ),
		'CV' => esc_html__( 'Cabo Verde', 'everest-forms' ),
		'KH' => esc_html__( 'Cambodia', 'everest-forms' ),
		'CM' => esc_html__( 'Cameroon', 'everest-forms' ),
		'CA' => esc_html__( 'Canada', 'everest-forms' ),
		'KY' => esc_html__( 'Cayman Islands', 'everest-forms' ),
		'CF' => esc_html__( 'Central African Republic', 'everest-forms' ),
		'TD' => esc_html__( 'Chad', 'everest-forms' ),
		'CL' => esc_html__( 'Chile', 'everest-forms' ),
		'CN' => esc_html__( 'China', 'everest-forms' ),
		'CX' => esc_html__( 'Christmas Island', 'everest-forms' ),
		'CC' => esc_html__( 'Cocos (Keeling) Islands', 'everest-forms' ),
		'CO' => esc_html__( 'Colombia', 'everest-forms' ),
		'KM' => esc_html__( 'Comoros', 'everest-forms' ),
		'CG' => esc_html__( 'Congo', 'everest-forms' ),
		'CD' => esc_html__( 'Congo (Democratic Republic of the)', 'everest-forms' ),
		'CK' => esc_html__( 'Cook Islands', 'everest-forms' ),
		'CR' => esc_html__( 'Costa Rica', 'everest-forms' ),
		'CI' => esc_html__( 'Côte d\'Ivoire', 'everest-forms' ),
		'HR' => esc_html__( 'Croatia', 'everest-forms' ),
		'CU' => esc_html__( 'Cuba', 'everest-forms' ),
		'CW' => esc_html__( 'Curaçao', 'everest-forms' ),
		'CY' => esc_html__( 'Cyprus', 'everest-forms' ),
		'CZ' => esc_html__( 'Czech Republic', 'everest-forms' ),
		'DK' => esc_html__( 'Denmark', 'everest-forms' ),
		'DJ' => esc_html__( 'Djibouti', 'everest-forms' ),
		'DM' => esc_html__( 'Dominica', 'everest-forms' ),
		'DO' => esc_html__( 'Dominican Republic', 'everest-forms' ),
		'EC' => esc_html__( 'Ecuador', 'everest-forms' ),
		'EG' => esc_html__( 'Egypt', 'everest-forms' ),
		'SV' => esc_html__( 'El Salvador', 'everest-forms' ),
		'GQ' => esc_html__( 'Equatorial Guinea', 'everest-forms' ),
		'ER' => esc_html__( 'Eritrea', 'everest-forms' ),
		'EE' => esc_html__( 'Estonia', 'everest-forms' ),
		'ET' => esc_html__( 'Ethiopia', 'everest-forms' ),
		'FK' => esc_html__( 'Falkland Islands (Malvinas)', 'everest-forms' ),
		'FO' => esc_html__( 'Faroe Islands', 'everest-forms' ),
		'FJ' => esc_html__( 'Fiji', 'everest-forms' ),
		'FI' => esc_html__( 'Finland', 'everest-forms' ),
		'FR' => esc_html__( 'France', 'everest-forms' ),
		'GF' => esc_html__( 'French Guiana', 'everest-forms' ),
		'PF' => esc_html__( 'French Polynesia', 'everest-forms' ),
		'TF' => esc_html__( 'French Southern Territories', 'everest-forms' ),
		'GA' => esc_html__( 'Gabon', 'everest-forms' ),
		'GM' => esc_html__( 'Gambia', 'everest-forms' ),
		'GE' => esc_html_x( 'Georgia', 'Country', 'everest-forms' ),
		'DE' => esc_html__( 'Germany', 'everest-forms' ),
		'GH' => esc_html__( 'Ghana', 'everest-forms' ),
		'GI' => esc_html__( 'Gibraltar', 'everest-forms' ),
		'GR' => esc_html__( 'Greece', 'everest-forms' ),
		'GL' => esc_html__( 'Greenland', 'everest-forms' ),
		'GD' => esc_html__( 'Grenada', 'everest-forms' ),
		'GP' => esc_html__( 'Guadeloupe', 'everest-forms' ),
		'GU' => esc_html__( 'Guam', 'everest-forms' ),
		'GT' => esc_html__( 'Guatemala', 'everest-forms' ),
		'GG' => esc_html__( 'Guernsey', 'everest-forms' ),
		'GN' => esc_html__( 'Guinea', 'everest-forms' ),
		'GW' => esc_html__( 'Guinea-Bissau', 'everest-forms' ),
		'GY' => esc_html__( 'Guyana', 'everest-forms' ),
		'HT' => esc_html__( 'Haiti', 'everest-forms' ),
		'HM' => esc_html__( 'Heard Island and McDonald Islands', 'everest-forms' ),
		'HN' => esc_html__( 'Honduras', 'everest-forms' ),
		'HK' => esc_html__( 'Hong Kong', 'everest-forms' ),
		'HU' => esc_html__( 'Hungary', 'everest-forms' ),
		'IS' => esc_html__( 'Iceland', 'everest-forms' ),
		'IN' => esc_html__( 'India', 'everest-forms' ),
		'ID' => esc_html__( 'Indonesia', 'everest-forms' ),
		'IR' => esc_html__( 'Iran (Islamic Republic of)', 'everest-forms' ),
		'IQ' => esc_html__( 'Iraq', 'everest-forms' ),
		'IE' => esc_html__( 'Ireland (Republic of)', 'everest-forms' ),
		'IM' => esc_html__( 'Isle of Man', 'everest-forms' ),
		'IL' => esc_html__( 'Israel', 'everest-forms' ),
		'IT' => esc_html__( 'Italy', 'everest-forms' ),
		'JM' => esc_html__( 'Jamaica', 'everest-forms' ),
		'JP' => esc_html__( 'Japan', 'everest-forms' ),
		'JE' => esc_html__( 'Jersey', 'everest-forms' ),
		'JO' => esc_html__( 'Jordan', 'everest-forms' ),
		'KZ' => esc_html__( 'Kazakhstan', 'everest-forms' ),
		'KE' => esc_html__( 'Kenya', 'everest-forms' ),
		'KI' => esc_html__( 'Kiribati', 'everest-forms' ),
		'KP' => esc_html__( 'Korea (Democratic People\'s Republic of)', 'everest-forms' ),
		'KR' => esc_html__( 'Korea (Republic of)', 'everest-forms' ),
		'KW' => esc_html__( 'Kuwait', 'everest-forms' ),
		'KG' => esc_html__( 'Kyrgyzstan', 'everest-forms' ),
		'LA' => esc_html__( 'Lao People\'s Democratic Republic', 'everest-forms' ),
		'LV' => esc_html__( 'Latvia', 'everest-forms' ),
		'LB' => esc_html__( 'Lebanon', 'everest-forms' ),
		'LS' => esc_html__( 'Lesotho', 'everest-forms' ),
		'LR' => esc_html__( 'Liberia', 'everest-forms' ),
		'LY' => esc_html__( 'Libya', 'everest-forms' ),
		'LI' => esc_html__( 'Liechtenstein', 'everest-forms' ),
		'LT' => esc_html__( 'Lithuania', 'everest-forms' ),
		'LU' => esc_html__( 'Luxembourg', 'everest-forms' ),
		'MO' => esc_html__( 'Macao', 'everest-forms' ),
		'MK' => esc_html__( 'Macedonia (Republic of)', 'everest-forms' ),
		'MG' => esc_html__( 'Madagascar', 'everest-forms' ),
		'MW' => esc_html__( 'Malawi', 'everest-forms' ),
		'MY' => esc_html__( 'Malaysia', 'everest-forms' ),
		'MV' => esc_html__( 'Maldives', 'everest-forms' ),
		'ML' => esc_html__( 'Mali', 'everest-forms' ),
		'MT' => esc_html__( 'Malta', 'everest-forms' ),
		'MH' => esc_html__( 'Marshall Islands', 'everest-forms' ),
		'MQ' => esc_html__( 'Martinique', 'everest-forms' ),
		'MR' => esc_html__( 'Mauritania', 'everest-forms' ),
		'MU' => esc_html__( 'Mauritius', 'everest-forms' ),
		'YT' => esc_html__( 'Mayotte', 'everest-forms' ),
		'MX' => esc_html__( 'Mexico', 'everest-forms' ),
		'FM' => esc_html__( 'Micronesia (Federated States of)', 'everest-forms' ),
		'MD' => esc_html__( 'Moldova (Republic of)', 'everest-forms' ),
		'MC' => esc_html__( 'Monaco', 'everest-forms' ),
		'MN' => esc_html__( 'Mongolia', 'everest-forms' ),
		'ME' => esc_html__( 'Montenegro', 'everest-forms' ),
		'MS' => esc_html__( 'Montserrat', 'everest-forms' ),
		'MA' => esc_html__( 'Morocco', 'everest-forms' ),
		'MZ' => esc_html__( 'Mozambique', 'everest-forms' ),
		'MM' => esc_html__( 'Myanmar', 'everest-forms' ),
		'NA' => esc_html__( 'Namibia', 'everest-forms' ),
		'NR' => esc_html__( 'Nauru', 'everest-forms' ),
		'NP' => esc_html__( 'Nepal', 'everest-forms' ),
		'NL' => esc_html__( 'Netherlands', 'everest-forms' ),
		'NC' => esc_html__( 'New Caledonia', 'everest-forms' ),
		'NZ' => esc_html__( 'New Zealand', 'everest-forms' ),
		'NI' => esc_html__( 'Nicaragua', 'everest-forms' ),
		'NE' => esc_html__( 'Niger', 'everest-forms' ),
		'NG' => esc_html__( 'Nigeria', 'everest-forms' ),
		'NU' => esc_html__( 'Niue', 'everest-forms' ),
		'NF' => esc_html__( 'Norfolk Island', 'everest-forms' ),
		'MP' => esc_html__( 'Northern Mariana Islands', 'everest-forms' ),
		'NO' => esc_html__( 'Norway', 'everest-forms' ),
		'OM' => esc_html__( 'Oman', 'everest-forms' ),
		'PK' => esc_html__( 'Pakistan', 'everest-forms' ),
		'PW' => esc_html__( 'Palau', 'everest-forms' ),
		'PS' => esc_html__( 'Palestine (State of)', 'everest-forms' ),
		'PA' => esc_html__( 'Panama', 'everest-forms' ),
		'PG' => esc_html__( 'Papua New Guinea', 'everest-forms' ),
		'PY' => esc_html__( 'Paraguay', 'everest-forms' ),
		'PE' => esc_html__( 'Peru', 'everest-forms' ),
		'PH' => esc_html__( 'Philippines', 'everest-forms' ),
		'PN' => esc_html__( 'Pitcairn', 'everest-forms' ),
		'PL' => esc_html__( 'Poland', 'everest-forms' ),
		'PT' => esc_html__( 'Portugal', 'everest-forms' ),
		'PR' => esc_html__( 'Puerto Rico', 'everest-forms' ),
		'QA' => esc_html__( 'Qatar', 'everest-forms' ),
		'RE' => esc_html__( 'Réunion', 'everest-forms' ),
		'RO' => esc_html__( 'Romania', 'everest-forms' ),
		'RU' => esc_html__( 'Russian Federation', 'everest-forms' ),
		'RW' => esc_html__( 'Rwanda', 'everest-forms' ),
		'BL' => esc_html__( 'Saint Barthélemy', 'everest-forms' ),
		'SH' => esc_html__( 'Saint Helena, Ascension and Tristan da Cunha', 'everest-forms' ),
		'KN' => esc_html__( 'Saint Kitts and Nevis', 'everest-forms' ),
		'LC' => esc_html__( 'Saint Lucia', 'everest-forms' ),
		'MF' => esc_html__( 'Saint Martin (French part)', 'everest-forms' ),
		'PM' => esc_html__( 'Saint Pierre and Miquelon', 'everest-forms' ),
		'VC' => esc_html__( 'Saint Vincent and the Grenadines', 'everest-forms' ),
		'WS' => esc_html__( 'Samoa', 'everest-forms' ),
		'SM' => esc_html__( 'San Marino', 'everest-forms' ),
		'ST' => esc_html__( 'Sao Tome and Principe', 'everest-forms' ),
		'SA' => esc_html__( 'Saudi Arabia', 'everest-forms' ),
		'SN' => esc_html__( 'Senegal', 'everest-forms' ),
		'RS' => esc_html__( 'Serbia', 'everest-forms' ),
		'SC' => esc_html__( 'Seychelles', 'everest-forms' ),
		'SL' => esc_html__( 'Sierra Leone', 'everest-forms' ),
		'SG' => esc_html__( 'Singapore', 'everest-forms' ),
		'SX' => esc_html__( 'Sint Maarten (Dutch part)', 'everest-forms' ),
		'SK' => esc_html__( 'Slovakia', 'everest-forms' ),
		'SI' => esc_html__( 'Slovenia', 'everest-forms' ),
		'SB' => esc_html__( 'Solomon Islands', 'everest-forms' ),
		'SO' => esc_html__( 'Somalia', 'everest-forms' ),
		'ZA' => esc_html__( 'South Africa', 'everest-forms' ),
		'GS' => esc_html__( 'South Georgia and the South Sandwich Islands', 'everest-forms' ),
		'SS' => esc_html__( 'South Sudan', 'everest-forms' ),
		'ES' => esc_html__( 'Spain', 'everest-forms' ),
		'LK' => esc_html__( 'Sri Lanka', 'everest-forms' ),
		'SD' => esc_html__( 'Sudan', 'everest-forms' ),
		'SR' => esc_html__( 'Suriname', 'everest-forms' ),
		'SJ' => esc_html__( 'Svalbard and Jan Mayen', 'everest-forms' ),
		'SZ' => esc_html__( 'Swaziland', 'everest-forms' ),
		'SE' => esc_html__( 'Sweden', 'everest-forms' ),
		'CH' => esc_html__( 'Switzerland', 'everest-forms' ),
		'SY' => esc_html__( 'Syrian Arab Republic', 'everest-forms' ),
		'TW' => esc_html__( 'Taiwan, Province of China', 'everest-forms' ),
		'TJ' => esc_html__( 'Tajikistan', 'everest-forms' ),
		'TZ' => esc_html__( 'Tanzania (United Republic of)', 'everest-forms' ),
		'TH' => esc_html__( 'Thailand', 'everest-forms' ),
		'TL' => esc_html__( 'Timor-Leste', 'everest-forms' ),
		'TG' => esc_html__( 'Togo', 'everest-forms' ),
		'TK' => esc_html__( 'Tokelau', 'everest-forms' ),
		'TO' => esc_html__( 'Tonga', 'everest-forms' ),
		'TT' => esc_html__( 'Trinidad and Tobago', 'everest-forms' ),
		'TN' => esc_html__( 'Tunisia', 'everest-forms' ),
		'TR' => esc_html__( 'Turkey', 'everest-forms' ),
		'TM' => esc_html__( 'Turkmenistan', 'everest-forms' ),
		'TC' => esc_html__( 'Turks and Caicos Islands', 'everest-forms' ),
		'TV' => esc_html__( 'Tuvalu', 'everest-forms' ),
		'UG' => esc_html__( 'Uganda', 'everest-forms' ),
		'UA' => esc_html__( 'Ukraine', 'everest-forms' ),
		'AE' => esc_html__( 'United Arab Emirates', 'everest-forms' ),
		'GB' => esc_html__( 'United Kingdom of Great Britain and Northern Ireland', 'everest-forms' ),
		'US' => esc_html__( 'United States of America', 'everest-forms' ),
		'UM' => esc_html__( 'United States Minor Outlying Islands', 'everest-forms' ),
		'UY' => esc_html__( 'Uruguay', 'everest-forms' ),
		'UZ' => esc_html__( 'Uzbekistan', 'everest-forms' ),
		'VU' => esc_html__( 'Vanuatu', 'everest-forms' ),
		'VA' => esc_html__( 'Vatican City State', 'everest-forms' ),
		'VE' => esc_html__( 'Venezuela (Bolivarian Republic of)', 'everest-forms' ),
		'VN' => esc_html__( 'Viet Nam', 'everest-forms' ),
		'VG' => esc_html__( 'Virgin Islands (British)', 'everest-forms' ),
		'VI' => esc_html__( 'Virgin Islands (U.S.)', 'everest-forms' ),
		'WF' => esc_html__( 'Wallis and Futuna', 'everest-forms' ),
		'EH' => esc_html__( 'Western Sahara', 'everest-forms' ),
		'YE' => esc_html__( 'Yemen', 'everest-forms' ),
		'ZM' => esc_html__( 'Zambia', 'everest-forms' ),
		'ZW' => esc_html__( 'Zimbabwe', 'everest-forms' ),
	);

	return (array) apply_filters( 'everest_forms_countries', $countries );
}

/**
 * Get builder fields groups.
 *
 * @return array
 */
function evf_get_fields_groups() {
	return (array) apply_filters(
		'everest_forms_builder_fields_groups', array(
			'general'  => __( 'General Fields', 'everest-forms' ),
			'advanced' => __( 'Advanced Fields', 'everest-forms' ),
			'payment'  => __( 'Payment Fields', 'everest-forms' ),
		)
	);
}

/**
 * Get a builder fields type's name.
 *
 * @param string $type Coupon type.
 * @return string
 */
function evf_get_fields_group( $type = '' ) {
	$types = evf_get_fields_groups();
	return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

/**
 * Get all fields settings.
 *
 * @return array Settings data.
 */
function evf_get_all_fields_settings() {
	$settings = array(
		'label' => array(
			'id'       => 'label',
			'title'    => __( 'Label', 'everest-forms' ),
			'desc'     => __( 'Enter text for the form field label.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'meta' => array(
			'id'       => 'meta-key',
			'title'    => __( 'Meta Key', 'everest-forms' ),
			'desc'     => __( 'Enter meta key to be stored in database.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'description'     => array(
			'id'          => 'description',
			'title'       => __( 'Description', 'everest-forms' ),
			'type'        => 'textarea',
			'desc' => __( 'Enter text for the form field description.', 'everest-forms' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'required' => array(
			'id'       => 'require',
			'title'    => __( 'Required', 'everest-forms' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Check this option to mark the field required.', 'everest-forms' ),
			'default'  => 'no',
			'desc_tip' => true,
		),
		'choices' => array(
			'id'       => 'choices',
			'title'    => __( 'Choices', 'everest-forms' ),
			'desc'     => __( 'Add choices for the form field.', 'everest-forms' ),
			'type'     => 'choices',
			'desc_tip' => true,
			'defaults' => array(
				1 => __( 'First Choice', 'everest-forms' ),
				2 => __( 'Second Choice', 'everest-forms' ),
				3 => __( 'Third Choice', 'everest-forms' ),
			),
		),
		'placeholder' => array(
			'id'       => 'placeholder',
			'title'    => __( 'Placeholder Text', 'everest-forms' ),
			'desc'     => __( 'Enter text for the form field placeholder.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'css' => array(
			'id'       => 'css',
			'title'    => __( 'CSS Classes', 'everest-forms' ),
			'desc'     => __( 'Enter CSS class for this field container. Class names should be separated with spaces.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'label_hide' => array(
			'id'      => 'label_hide',
			'title'   => __( 'Hide Label', 'everest-forms' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Check this option to hide the form field label.', 'everest-forms' ),
			'default' => 'no',
			'desc_tip'    => true,
		),
		'sublabel_hide' => array(
			'id'      => 'sublabel_hide',
			'title'   => __( 'Hide Sub-Labels', 'everest-forms' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Check this option to hide the form field sub-label.', 'everest-forms' ),
			'default' => 'no',
			'desc_tip'    => true,
		),
	);

	return apply_filters( 'everest_form_all_fields_settings', $settings );
}

/**
 * Checks if date field exists in the form.
 *
 * @since  1.4.2
 * @param  int $form_id Form ID.
 * @return bool
 */
function evf_has_date_field( $form_id ) {
	$form_obj  = EVF()->form->get( $form_id );
	$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

	if ( ! empty( $form_data['form_fields'] ) ) {
		foreach( $form_data['form_fields'] as $form_field ) {
			if ( 'date' === $form_field['type'] ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Helper function to display debug data.
 *
 * @since 1.3.2
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return     Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string
 */
function evf_debug_data( $expression, $return = false ) {
	if ( defined( 'EVF_DEBUG' ) && true === EVF_DEBUG ) {
		$output = '<textarea style="color:#666;background:#fff;margin: 20px 0;width:100%;height:500px;font-size:12px;font-family: Consolas,Monaco,Lucida Console,monospace;direction: ltr;unicode-bidi: embed;line-height: 1.4;padding: 4px 6px 1px;" readonly>';

		$output .= "==================== Everest Forms Debugging ====================\n\n";

		if ( is_array( $expression ) || is_object( $expression ) ) {
			$output .= evf_print_r( $expression, true );
		} else {
			$output .= $expression;
		}

		$output .= '</textarea>';

		if ( $return ) {
			return $output;
		} else {
			echo $output; // phpcs:ignore
		}
	}
}

