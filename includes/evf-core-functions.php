<?php
/**
 * EverestForms Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package EverestForms/Functions
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core functions (available in both admin and frontend).
include EVF_ABSPATH . 'includes/evf-user-functions.php';
require EVF_ABSPATH . 'includes/evf-deprecated-functions.php';
include EVF_ABSPATH . 'includes/evf-formatting-functions.php';
include EVF_ABSPATH . 'includes/evf-entry-functions.php';

/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.0
 * @param string $name  Constant name.
 * @param string $value Value.
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
 * @since      1.0.0
 *
 * @param string $handle name.
 *
 * @return string the log file path.
 */
function evf_get_log_file_path( $handle ) {
	return EVF_Log_Handler_File::get_log_file_path( $handle );
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

	return '<span class="evf-help-tip" data-tip="' . $tip . '"></span>';
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
					/* translators: 1: class name 2: woocommerce_logging_class 3: EVF_Logger_Interface */
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
		'text',
		'textarea',
		'select',
		'radio',
		'checkbox',
		'email',
		'address',
		'url',
		'name',
		'hidden',
		'date-time',
		'phone',
		'number',
		'file-upload',
		'payment-single',
		'payment-multiple',
		'payment-select',
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
function everest_forms_sanitize_textarea_field( $string ) {

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
 * @param string $id
 * @param array  $class
 * @param array  $datas
 * @param array  $atts
 * @param bool   $echo
 *
 * @return string
 */
function evf_html_attributes( $id = '', $class = array(), $datas = array(), $atts = array(), $echo = false ) {

	$output = '';
	$id     = trim( $id );

	if ( ! empty( $id ) ) {
		$output = 'id="' . sanitize_html_class( $id ) . '" ';
	}

	if ( ! empty( $class ) ) {
		$output .= 'class="' . evf_sanitize_classes( $class, true ) . '" ';
	}

	if ( ! empty( $datas ) ) {
		foreach ( $datas as $data => $val ) {
			$output .= 'data-' . sanitize_html_class( $data ) . '="' . esc_attr( $val ) . '" ';
		}
	}

	if ( ! empty( $atts ) ) {
		foreach ( $atts as $att => $val ) {
			if ( '0' == $val || ! empty( $val ) ) {
				$output .= sanitize_html_class( $att ) . '="' . esc_attr( $val ) . '" ';
			}
		}
	}

	if ( $echo ) {
		echo trim( $output );
	} else {
		return trim( $output );
	}
}

/**
 * @param      $classes
 * @param bool $convert
 *
 * @return array|string
 */
function evf_sanitize_classes( $classes, $convert = false ) {

	$array = is_array( $classes );
	$css   = array();

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
 * @since      1.0.0
 *
 * @param string $data
 *
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

function evf_get_all_forms( $skip_disabled_entries = false ) {
	$all_forms   = array();
	$posts_array = get_posts( array(
		'post_type' => 'everest_form',
		'status'    => 'publish',
	) );

	foreach ( $posts_array as $post ) {
		$form_obj  = EVF()->form->get( $post->ID );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( $skip_disabled_entries && ( isset( $form_data['settings']['disabled_entries'] ) && '1' === $form_data['settings']['disabled_entries'] ) ) {
			continue;
		}

		$all_forms[ $post->ID ] = $post->post_title;
	}

	return $all_forms;
}

function evf_get_meta_key_field_option( $field ) {

	$random_number = rand( pow(10, 3 ), pow( 10, 4 ) -1 );
	return strtolower( str_replace( " ", "_", $field['label'] ) ) . '_' . $random_number;
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

function get_form_data_by_meta_key( $form_id, $meta_key ) {
	$get_post     = get_post( $form_id );
	$post_content = json_decode( $get_post->post_content, true ) ;
	$form_fields  = $post_content['form_fields'];

	foreach( $form_fields as $field ) {
		if ( $meta_key == $field['meta-key'] ) {
			return $field['label'];
		}
	}

	return false;
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
