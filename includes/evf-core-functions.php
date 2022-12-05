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
require EVF_ABSPATH . 'includes/evf-conditional-functions.php';
require EVF_ABSPATH . 'includes/evf-deprecated-functions.php';
require EVF_ABSPATH . 'includes/evf-formatting-functions.php';
require EVF_ABSPATH . 'includes/evf-entry-functions.php';

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
 * Get template part.
 *
 * EVF_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function evf_get_template_part( $slug, $name = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template-part', $slug, $name, EVF_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'everest-forms' );

	if ( ! $template ) {
		if ( $name ) {
			$template = EVF_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}-{$name}.php",
					evf()->template_path() . "{$slug}-{$name}.php",
				)
			);

			if ( ! $template ) {
				$fallback = evf()->plugin_path() . "/templates/{$slug}-{$name}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		if ( ! $template ) {
			// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/everest-forms/slug.php.
			$template = EVF_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}.php",
					evf()->template_path() . "{$slug}.php",
				)
			);
		}

		wp_cache_set( $cache_key, $template, 'everest-forms' );
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
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function evf_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path, EVF_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'everest-forms' );

	if ( ! $template ) {
		$template = evf_locate_template( $template_name, $template_path, $default_path );
		wp_cache_set( $cache_key, $template, 'everest-forms' );
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$filter_template = apply_filters( 'evf_get_template', $template, $template_name, $args, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			/* translators: %s template */
			evf_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'everest-forms' ), '<code>' . $filter_template . '</code>' ), '1.0.0' );
			return;
		}
		$template = $filter_template;
	}

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		if ( isset( $args['action_args'] ) ) {
			evf_doing_it_wrong(
				__FUNCTION__,
				__( 'action_args should not be overwritten when calling evf_get_template.', 'everest-forms' ),
				'1.4.9'
			);
			unset( $args['action_args'] );
		}
		extract( $args ); // @codingStandardsIgnoreLine
	}

	do_action( 'everest_forms_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'everest_forms_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}

/**
 * Like evf_get_template, but returns the HTML instead of outputting.
 *
 * @see    evf_get_template
 * @since  1.0.0
 * @param  string $template_name Template name.
 * @param  array  $args          Arguments. (default: array).
 * @param  string $template_path Template path. (default: '').
 * @param  string $default_path  Default path. (default: '').
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
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param  string $template_name Template name.
 * @param  string $template_path Template path. (default: '').
 * @param  string $default_path  Default path. (default: '').
 * @return string
 */
function evf_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = evf()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = evf()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template/.
	if ( ! $template || EVF_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'everest_forms_locate_template', $template, $template_name, $template_path );
}

/**
 * Send HTML emails from EverestForms.
 *
 * @param mixed  $to          Receiver.
 * @param mixed  $subject     Subject.
 * @param mixed  $message     Message.
 * @param string $headers     Headers. (default: "Content-Type: text/html\r\n").
 * @param string $attachments Attachments. (default: "").
 */
function evf_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = '' ) {
	$mailer = evf()->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code Code.
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
		echo wp_kses( apply_filters( 'everest_forms_queued_js', $js ), array( 'script' => array( 'type' => true ) ) );
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
 * @param  bool    $httponly Whether the cookie is only accessible over HTTP, not scripting languages like JavaScript. @since 1.4.9.
 */
function evf_setcookie( $name, $value, $expire = 0, $secure = false, $httponly = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters( 'everest_forms_cookie_httponly', $httponly, $name, $value, $expire, $secure ) );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
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
 * @param  string $extension Extension Type.
 * @return bool|string The csv file name or false if cannot be determined.
 */
function evf_get_entry_export_file_name( $handle, $extension = 'csv' ) {
	if ( function_exists( 'wp_hash' ) ) {
		$date_suffix = date_i18n( 'Y-m-d', time() );
		$hash_suffix = wp_hash( $handle );
		return sanitize_file_name( implode( '-', array( 'evf-entry-export', $handle, $date_suffix, $hash_suffix ) ) . '.' . $extension );
	} else {
		evf_doing_it_wrong( __METHOD__, __( 'This method should not be called before plugins_loaded.', 'everest-forms' ), '1.3.0' );
		return false;
	}
}

/**
 * Recursively get page children.
 *
 * @param  int $page_id Page ID.
 * @return int[]
 */
function evf_get_page_children( $page_id ) {
	$page_ids = get_posts(
		array(
			'post_parent' => $page_id,
			'post_type'   => 'page',
			'numberposts' => - 1,
			'post_status' => 'any',
			'fields'      => 'ids',
		)
	);

	if ( ! empty( $page_ids ) ) {
		foreach ( $page_ids as $page_id ) {
			$page_ids = array_merge( $page_ids, evf_get_page_children( $page_id ) );
		}
	}

	return $page_ids;
}

/**
 * Get user agent string.
 *
 * @since  1.0.0
 * @return string
 */
function evf_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? evf_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // @codingStandardsIgnoreLine
}

// This function can be removed when WP 3.9.2 or greater is required.
if ( ! function_exists( 'hash_equals' ) ) :
	/**
	 * Compare two strings in constant time.
	 *
	 * This function was added in PHP 5.6.
	 * It can leak the length of a string.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $a Expected string.
	 * @param  string $b Actual string.
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
 * @since  1.0.0
 * @return string
 */
function evf_rand_hash() {
	if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return sha1( wp_rand() );
	}

	return bin2hex( openssl_random_pseudo_bytes( 20 ) ); // @codingStandardsIgnoreLine
}

/**
 * Find all possible combinations of values from the input array and return in a logical order.
 *
 * @since  1.0.0
 * @param  array $input Input.
 * @return array
 */
function evf_array_cartesian( $input ) {
	$input   = array_filter( $input );
	$results = array();
	$indexes = array();
	$index   = 0;

	// Generate indexes from keys and values so we have a logical sort order.
	foreach ( $input as $key => $values ) {
		foreach ( $values as $value ) {
			$indexes[ $key ][ $value ] = $index++;
		}
	}

	// Loop over the 2D array of indexes and generate all combinations.
	foreach ( $indexes as $key => $values ) {
		// When result is empty, fill with the values of the first looped array.
		if ( empty( $results ) ) {
			foreach ( $values as $value ) {
				$results[] = array( $key => $value );
			}
		} else {
			// Second and subsequent input sub-array merging.
			foreach ( $results as $result_key => $result ) {
				foreach ( $values as $value ) {
					// If the key is not set, we can set it.
					if ( ! isset( $results[ $result_key ][ $key ] ) ) {
						$results[ $result_key ][ $key ] = $value;
					} else {
						// If the key is set, we can add a new combination to the results array.
						$new_combination         = $results[ $result_key ];
						$new_combination[ $key ] = $value;
						$results[]               = $new_combination;
					}
				}
			}
		}
	}

	// Sort the indexes.
	arsort( $results );

	// Convert indexes back to values.
	foreach ( $results as $result_key => $result ) {
		$converted_values = array();

		// Sort the values.
		arsort( $results[ $result_key ] );

		// Convert the values.
		foreach ( $results[ $result_key ] as $key => $value ) {
			$converted_values[ $key ] = array_search( $value, $indexes[ $key ], true );
		}

		$results[ $result_key ] = $converted_values;
	}

	return $results;
}

/**
 * Run a MySQL transaction query, if supported.
 *
 * @since 1.0.0
 * @param string $type Types: start (default), commit, rollback.
 * @param bool   $force use of transactions.
 */
function evf_transaction_query( $type = 'start', $force = false ) {
	global $wpdb;

	$wpdb->hide_errors();

	evf_maybe_define_constant( 'EVF_USE_TRANSACTIONS', true );

	if ( EVF_USE_TRANSACTIONS || $force ) {
		switch ( $type ) {
			case 'commit':
				$wpdb->query( 'COMMIT' );
				break;
			case 'rollback':
				$wpdb->query( 'ROLLBACK' );
				break;
			default:
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
 * @since  1.0.0
 *
 * @param  string $tip        Help tip text.
 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
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
 *
 * @since 1.0.0
 * @param int $limit Time limit.
 */
function evf_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
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

	if ( null !== $logger && is_string( $class ) && is_a( $logger, $class ) ) {
		return $logger;
	}

	$implements = class_implements( $class );

	if ( is_array( $implements ) && in_array( 'EVF_Logger_Interface', $implements, true ) ) {
		$logger = is_object( $class ) ? $class : new $class();
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

	return $logger;
}

/**
 * Prints human-readable information about a variable.
 *
 * Some server environments blacklist some debugging functions. This function provides a safe way to
 * turn an expression into a printable, readable form without calling blacklisted functions.
 *
 * @since 1.0.0
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return     Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string|bool False if expression could not be printed. True if the expression was printed.
 *     If $return is true, a string representation will be returned.
 */
function evf_print_r( $expression, $return = false ) {
	$alternatives = array(
		array(
			'func' => 'print_r',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'var_export',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'json_encode',
			'args' => array( $expression ),
		),
		array(
			'func' => 'serialize',
			'args' => array( $expression ),
		),
	);

	$alternatives = apply_filters( 'everest_forms_print_r_alternatives', $alternatives, $expression );

	foreach ( $alternatives as $alternative ) {
		if ( function_exists( $alternative['func'] ) ) {
			$res = call_user_func_array( $alternative['func'], $alternative['args'] );
			if ( $return ) {
				return $res;
			}

			echo wp_kses_post( $res );
			return true;
		}
	}

	return false;
}

/**
 * Registers the default log handler.
 *
 * @since  1.0.0
 * @param  array $handlers Handlers.
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
 * @since 1.0.0
 * @param array      $list              List of objects or arrays.
 * @param int|string $callback_or_field Callback method from the object to place instead of the entire object.
 * @param int|string $index_key         Optional. Field from the object to use as keys for the new array.
 *                                      Default null.
 * @return array Array of values.
 */
function evf_list_pluck( $list, $callback_or_field, $index_key = null ) {
	// Use wp_list_pluck if this isn't a callback.
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
		// Get index.
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
 * @since 1.0.0
 */
function evf_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init EVF locale.
		evf()->load_plugin_textdomain();
	}
}

/**
 * Switch EverestForms language to original.
 *
 * @since 1.0.0
 */
function evf_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init EVF locale.
		evf()->load_plugin_textdomain();
	}
}

/**
 * Get an item of post data if set, otherwise return a default value.
 *
 * @since  1.0.0
 * @param  string $key     Key.
 * @param  string $default Default.
 * @return mixed value sanitized by evf_clean
 */
function evf_get_post_data_by_key( $key, $default = '' ) {
	return evf_clean( evf_get_var( $_POST[ $key ], $default ) ); // @codingStandardsIgnoreLine
}

/**
 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
 *
 * @since  1.0.0
 * @param  mixed  $var     Variable.
 * @param  string $default Default value.
 * @return mixed
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

	// EVF requires at least - allows developers to define which version of Everest Forms the plugin requires to run.
	$headers[] = EVF_Plugin_Updates::VERSION_REQUIRED_HEADER;

	// EVF tested up to - allows developers to define which version of Everest Forms they have tested up to.
	$headers[] = EVF_Plugin_Updates::VERSION_TESTED_HEADER;

	return $headers;
}
add_filter( 'extra_theme_headers', 'evf_enable_evf_plugin_headers' );
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
	$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
		AND b.option_value < %d";
	$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

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
 * @since 1.0.0
 *
 * @param mixed $form Form data.
 * @param array $whitelist Whitelist args.
 *
 * @return mixed boolean or array
 */
function evf_get_form_fields( $form = false, $whitelist = array() ) {
	// Accept form (post) object or form ID.
	if ( is_object( $form ) ) {
		$form = json_decode( $form->post_content );
	} elseif ( is_numeric( $form ) ) {
		$form = evf()->form->get(
			$form,
			array(
				'content_only' => true,
			)
		);
	}

	if ( ! is_array( $form ) || empty( $form['form_fields'] ) ) {
		return false;
	}

	// White list of field types to allow.
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
 * Sanitize a string, that can be a multiline.
 * If WP core `sanitize_textarea_field()` exists (after 4.7.0) - use it.
 * Otherwise - split onto separate lines, sanitize each one, merge again.
 *
 * @since 1.4.1
 *
 * @param string $string Raw string to sanitize.
 *
 * @return string If empty var is passed, or not a string - return unmodified. Otherwise - sanitize.
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
 * @param string $id    Element ID.
 * @param array  $class Class args.
 * @param array  $datas Data args.
 * @param array  $atts  Attributes.
 * @param bool   $echo  True to echo else return.
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
			if ( '0' === $val || ! empty( $val ) ) {
				if ( $att[0] === '[' ) { //phpcs:ignore
					// Handle special case for bound attributes in AMP.
					$escaped_att = '[' . sanitize_html_class( trim( $att, '[]' ) ) . ']';
				} else {
					$escaped_att = sanitize_html_class( $att );
				}
				$parts[] = $escaped_att . '="' . esc_attr( $val ) . '"';
			}
		}
	}

	$output = implode( ' ', $parts );

	if ( $echo ) {
		echo esc_html( trim( $output ) );
	} else {
		return trim( $output );
	}
}

/**
 * Sanitize string of CSS classes.
 *
 * @param array|string $classes Class names.
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
 * @param string $data Data to decode.
 *
 * @return array|bool
 */
function evf_decode( $data ) {
	if ( ! $data || empty( $data ) ) {
		return false;
	}

	return json_decode( $data, true );
}

/**
 * Performs json_encode and wp_slash.
 *
 * @since 1.0.0
 *
 * @param mixed $data Data to encode.
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
 * Crypto rand secure.
 *
 * @param int $min Min value.
 * @param int $max Max value.
 *
 * @return mixed
 */
function evf_crypto_rand_secure( $min, $max ) {
	$range = $max - $min;
	if ( $range < 1 ) {
		return $min;
	} // not so random...
	$log    = ceil( log( $range, 2 ) );
	$bytes  = (int) ( $log / 8 ) + 1; // Length in bytes.
	$bits   = (int) $log + 1; // Length in bits.
	$filter = (int) ( 1 << $bits ) - 1; // Set all lower bits to 1.
	do {
		$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
		$rnd = $rnd & $filter; // Discard irrelevant bits.
	} while ( $rnd > $range );

	return $min + $rnd;
}

/**
 * Generate random string.
 *
 * @param int $length Length of string.
 *
 * @return string
 */
function evf_get_random_string( $length = 10 ) {
	$string         = '';
	$code_alphabet  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$code_alphabet .= 'abcdefghijklmnopqrstuvwxyz';
	$code_alphabet .= '0123456789';
	$max            = strlen( $code_alphabet );
	for ( $i = 0; $i < $length; $i ++ ) {
		$string .= $code_alphabet[ evf_crypto_rand_secure( 0, $max - 1 ) ];
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
	$forms    = array();
	$form_ids = wp_parse_id_list(
		evf()->form->get_multiple(
			array(
				'fields'      => 'ids',
				'status'      => 'publish',
				'order'       => 'DESC',
				'numberposts' => -1, // @codingStandardsIgnoreLine
			)
		)
	);

	if ( ! empty( $form_ids ) ) {
		foreach ( $form_ids as $form_id ) {
			$form      = evf()->form->get( $form_id );
			$entries   = evf_get_entries_ids( $form_id );
			$form_data = ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : '';

			if ( ! $form || ( $skip_disabled_entries && count( $entries ) < 1 ) && ( isset( $form_data['settings']['disabled_entries'] ) && '1' === $form_data['settings']['disabled_entries'] ) ) {
				continue;
			}

			// Check permissions for forms with viewable.
			if ( current_user_can( 'everest_forms_view_form_entries', $form_id ) ) {
				$forms[ $form_id ] = $form->post_title;
			}
		}
	}

	return $forms;
}

/**
 * Get random meta-key for field option.
 *
 * @param  array $field Field data array.
 * @return string
 */
function evf_get_meta_key_field_option( $field ) {
	return str_replace( ' ', '_', preg_replace( '/[^a-zA-Z0-9\s`_]/', '', strtolower( $field['label'] ) ) ) . '_' . rand( pow( 10, 3 ), pow( 10, 4 ) - 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand.
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
	$u_agent  = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
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
	if ( preg_match( '/MSIE/i', $u_agent ) && ! preg_match( '/Opera/i', $u_agent ) ) {
		$bname = 'Internet Explorer';
		$ub    = 'MSIE';
	} elseif ( preg_match( '/Trident/i', $u_agent ) ) {
		// this condition is for IE11.
		$bname = 'Internet Explorer';
		$ub    = 'rv';
	} elseif ( preg_match( '/Firefox/i', $u_agent ) ) {
		$bname = 'Mozilla Firefox';
		$ub    = 'Firefox';
	} elseif ( preg_match( '/Chrome/i', $u_agent ) ) {
		$bname = 'Google Chrome';
		$ub    = 'Chrome';
	} elseif ( preg_match( '/Safari/i', $u_agent ) ) {
		$bname = 'Apple Safari';
		$ub    = 'Safari';
	} elseif ( preg_match( '/Opera/i', $u_agent ) ) {
		$bname = 'Opera';
		$ub    = 'Opera';
	} elseif ( preg_match( '/Netscape/i', $u_agent ) ) {
		$bname = 'Netscape';
		$ub    = 'Netscape';
	}

	// Finally get the correct version number.
	// Added "|:".
	$known   = array( 'Version', $ub, 'other' );
	$pattern = '#(?<browser>' . join( '|', $known ) . ')[/|: ]+(?<version>[0-9.|a-zA-Z.]*)#';
	if ( ! preg_match_all( $pattern, $u_agent, $matches ) ) { // @codingStandardsIgnoreLine
		// We have no matching number just continue.
	}

	// See how many we have.
	$i = count( $matches['browser'] );

	if ( 1 !== $i ) {
		// we will have two since we are not using 'other' argument yet.
		// see if version is before or after the name.
		if ( strripos( $u_agent, 'Version' ) < strripos( $u_agent, $ub ) ) {
			$version = $matches['version'][0];
		} else {
			$version = $matches['version'][1];
		}
	} else {
		$version = $matches['version'][0];
	}

	// Check if we have a number.
	if ( null === $version || '' === $version ) {
		$version = '';
	}

	return array(
		'userAgent' => $u_agent,
		'name'      => $bname,
		'version'   => $version,
		'platform'  => $platform,
		'pattern'   => $pattern,
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
			$date = date( $format, strtotime( 'today', $timestamp ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			break;

		case 'end_of_day':
			$date = date( $format, strtotime( 'tomorrow', $timestamp ) - 1 ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			break;

	}

	return $date;
}

/**
 * Get field label by meta key
 *
 * @param int    $form_id  Form ID.
 * @param string $meta_key Field's meta key.
 * @param array  $fields Entry Field Data.
 *
 * @return string|false True if field label exists in form.
 */
function evf_get_form_data_by_meta_key( $form_id, $meta_key, $fields = array() ) {
	$get_post     = get_post( $form_id );
	$post_content = json_decode( $get_post->post_content, true );
	$form_fields  = isset( $post_content['form_fields'] ) ? $post_content['form_fields'] : array();

	if ( ! empty( $form_fields ) ) {
		foreach ( $form_fields as $field ) {
			if ( isset( $field['meta-key'] ) && $meta_key === $field['meta-key'] ) {
				return $field['label'];
			}
		}
	}

	if ( ! empty( $fields ) ) {
		foreach ( $fields as $field ) {
			if ( isset( $field->meta_key ) && $meta_key === $field->meta_key ) {
				return isset( $field->name ) ? $field->name : $field->value->name;
			}
		}
	}

	return false;
}

/**
 * Get field type by meta key
 *
 * @param int    $form_id  Form ID.
 * @param string $meta_key Field's meta key.
 *
 * @return string|false True if field type exists in form.
 */
function evf_get_field_type_by_meta_key( $form_id, $meta_key ) {
	$get_post     = get_post( $form_id );
	$post_content = json_decode( $get_post->post_content, true );
	$form_fields  = isset( $post_content['form_fields'] ) ? $post_content['form_fields'] : array();

	if ( ! empty( $form_fields ) ) {
		foreach ( $form_fields as $field ) {
			if ( isset( $field['meta-key'] ) && $meta_key === $field['meta-key'] ) {
				return $field['type'];
			}
		}
	}

	return false;
}

/**
 * Get all the email fields of a Form.
 *
 * @param int $form_id  Form ID.
 */
function evf_get_all_email_fields_by_form_id( $form_id ) {
	$user_emails = array();
	$form_obj    = evf()->form->get( $form_id );
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
 * @param int $form_id  Form ID.
 * @return array
 */
function evf_get_all_form_fields_by_form_id( $form_id ) {
	$data      = array();
	$form_obj  = evf()->form->get( $form_id );
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
 * Check if the string JSON.
 *
 * @param string $string String to check.
 * @return bool
 */
function evf_isJson( $string ) {
	json_decode( $string );
	return ( json_last_error() == JSON_ERROR_NONE ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
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
 * @param string $size Size to convert to bytes.
 *
 * @return int
 */
function evf_size_to_bytes( $size ) {
	if ( is_numeric( $size ) ) {
		return $size;
	}

	$suffix = substr( $size, - 1 );
	$value  = substr( $size, 0, - 1 );

	// @codingStandardsIgnoreStart
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
	// @codingStandardsIgnoreEnd

	return $value;
}

/**
 * Convert bytes to megabytes (or in some cases KB).
 *
 * @since 1.2.0
 *
 * @param int $bytes Bytes to convert to a readable format.
 *
 * @return string
 */
function evf_size_to_megabytes( $bytes ) {
	if ( $bytes < 1048676 ) {
		return number_format( $bytes / 1024, 1 ) . ' KB';
	} else {
		return round( (float) number_format( $bytes / 1048576, 1 ) ) . ' MB';
	}
}

/**
 * Convert a file size provided, such as "2M", to bytes.
 *
 * @since 1.2.0
 * @link http://stackoverflow.com/a/22500394
 *
 * @param  bool $bytes Whether to convert Bytes to a readable format.
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

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( $license_key && is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' ) ) {
		$license_data = get_transient( 'evf_pro_license_plan' );

		if ( false === $license_data ) {
			$license_data = json_decode(
				EVF_Updater_Key_API::check(
					array(
						'license' => $license_key,
					)
				)
			);

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
 * @since 1.2.0
 *
 * @param string $string Raw string to decode.
 *
 * @return string
 */
function evf_decode_string( $string ) {
	if ( ! is_string( $string ) ) {
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
		'PW' => esc_html__( 'Belau', 'everest-forms' ),
		'BZ' => esc_html__( 'Belize', 'everest-forms' ),
		'BJ' => esc_html__( 'Benin', 'everest-forms' ),
		'BM' => esc_html__( 'Bermuda', 'everest-forms' ),
		'BT' => esc_html__( 'Bhutan', 'everest-forms' ),
		'BO' => esc_html__( 'Bolivia', 'everest-forms' ),
		'BQ' => esc_html__( 'Bonaire, Saint Eustatius and Saba', 'everest-forms' ),
		'BA' => esc_html__( 'Bosnia and Herzegovina', 'everest-forms' ),
		'BW' => esc_html__( 'Botswana', 'everest-forms' ),
		'BV' => esc_html__( 'Bouvet Island', 'everest-forms' ),
		'BR' => esc_html__( 'Brazil', 'everest-forms' ),
		'IO' => esc_html__( 'British Indian Ocean Territory', 'everest-forms' ),
		'BN' => esc_html__( 'Brunei', 'everest-forms' ),
		'BG' => esc_html__( 'Bulgaria', 'everest-forms' ),
		'BF' => esc_html__( 'Burkina Faso', 'everest-forms' ),
		'BI' => esc_html__( 'Burundi', 'everest-forms' ),
		'KH' => esc_html__( 'Cambodia', 'everest-forms' ),
		'CM' => esc_html__( 'Cameroon', 'everest-forms' ),
		'CA' => esc_html__( 'Canada', 'everest-forms' ),
		'CV' => esc_html__( 'Cape Verde', 'everest-forms' ),
		'KY' => esc_html__( 'Cayman Islands', 'everest-forms' ),
		'CF' => esc_html__( 'Central African Republic', 'everest-forms' ),
		'TD' => esc_html__( 'Chad', 'everest-forms' ),
		'CL' => esc_html__( 'Chile', 'everest-forms' ),
		'CN' => esc_html__( 'China', 'everest-forms' ),
		'CX' => esc_html__( 'Christmas Island', 'everest-forms' ),
		'CC' => esc_html__( 'Cocos (Keeling) Islands', 'everest-forms' ),
		'CO' => esc_html__( 'Colombia', 'everest-forms' ),
		'KM' => esc_html__( 'Comoros', 'everest-forms' ),
		'CG' => esc_html__( 'Congo (Brazzaville)', 'everest-forms' ),
		'CD' => esc_html__( 'Congo (Kinshasa)', 'everest-forms' ),
		'CK' => esc_html__( 'Cook Islands', 'everest-forms' ),
		'CR' => esc_html__( 'Costa Rica', 'everest-forms' ),
		'HR' => esc_html__( 'Croatia', 'everest-forms' ),
		'CU' => esc_html__( 'Cuba', 'everest-forms' ),
		'CW' => esc_html__( 'Cura&ccedil;ao', 'everest-forms' ),
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
		'FK' => esc_html__( 'Falkland Islands', 'everest-forms' ),
		'FO' => esc_html__( 'Faroe Islands', 'everest-forms' ),
		'FJ' => esc_html__( 'Fiji', 'everest-forms' ),
		'FI' => esc_html__( 'Finland', 'everest-forms' ),
		'FR' => esc_html__( 'France', 'everest-forms' ),
		'GF' => esc_html__( 'French Guiana', 'everest-forms' ),
		'PF' => esc_html__( 'French Polynesia', 'everest-forms' ),
		'TF' => esc_html__( 'French Southern Territories', 'everest-forms' ),
		'GA' => esc_html__( 'Gabon', 'everest-forms' ),
		'GM' => esc_html__( 'Gambia', 'everest-forms' ),
		'GE' => esc_html__( 'Georgia', 'everest-forms' ),
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
		'IR' => esc_html__( 'Iran', 'everest-forms' ),
		'IQ' => esc_html__( 'Iraq', 'everest-forms' ),
		'IE' => esc_html__( 'Ireland', 'everest-forms' ),
		'IM' => esc_html__( 'Isle of Man', 'everest-forms' ),
		'IL' => esc_html__( 'Israel', 'everest-forms' ),
		'IT' => esc_html__( 'Italy', 'everest-forms' ),
		'CI' => esc_html__( 'Ivory Coast', 'everest-forms' ),
		'JM' => esc_html__( 'Jamaica', 'everest-forms' ),
		'JP' => esc_html__( 'Japan', 'everest-forms' ),
		'JE' => esc_html__( 'Jersey', 'everest-forms' ),
		'JO' => esc_html__( 'Jordan', 'everest-forms' ),
		'KZ' => esc_html__( 'Kazakhstan', 'everest-forms' ),
		'KE' => esc_html__( 'Kenya', 'everest-forms' ),
		'KI' => esc_html__( 'Kiribati', 'everest-forms' ),
		'KW' => esc_html__( 'Kuwait', 'everest-forms' ),
		'XK' => esc_html__( 'Kosovo', 'everest-forms' ),
		'KG' => esc_html__( 'Kyrgyzstan', 'everest-forms' ),
		'LA' => esc_html__( 'Laos', 'everest-forms' ),
		'LV' => esc_html__( 'Latvia', 'everest-forms' ),
		'LB' => esc_html__( 'Lebanon', 'everest-forms' ),
		'LS' => esc_html__( 'Lesotho', 'everest-forms' ),
		'LR' => esc_html__( 'Liberia', 'everest-forms' ),
		'LY' => esc_html__( 'Libya', 'everest-forms' ),
		'LI' => esc_html__( 'Liechtenstein', 'everest-forms' ),
		'LT' => esc_html__( 'Lithuania', 'everest-forms' ),
		'LU' => esc_html__( 'Luxembourg', 'everest-forms' ),
		'MO' => esc_html__( 'Macao', 'everest-forms' ),
		'MK' => esc_html__( 'North Macedonia', 'everest-forms' ),
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
		'FM' => esc_html__( 'Micronesia', 'everest-forms' ),
		'MD' => esc_html__( 'Moldova', 'everest-forms' ),
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
		'KP' => esc_html__( 'North Korea', 'everest-forms' ),
		'NO' => esc_html__( 'Norway', 'everest-forms' ),
		'OM' => esc_html__( 'Oman', 'everest-forms' ),
		'PK' => esc_html__( 'Pakistan', 'everest-forms' ),
		'PS' => esc_html__( 'Palestinian Territory', 'everest-forms' ),
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
		'RE' => esc_html__( 'Reunion', 'everest-forms' ),
		'RO' => esc_html__( 'Romania', 'everest-forms' ),
		'RU' => esc_html__( 'Russia', 'everest-forms' ),
		'RW' => esc_html__( 'Rwanda', 'everest-forms' ),
		'BL' => esc_html__( 'Saint Barth&eacute;lemy', 'everest-forms' ),
		'SH' => esc_html__( 'Saint Helena', 'everest-forms' ),
		'KN' => esc_html__( 'Saint Kitts and Nevis', 'everest-forms' ),
		'LC' => esc_html__( 'Saint Lucia', 'everest-forms' ),
		'MF' => esc_html__( 'Saint Martin (French part)', 'everest-forms' ),
		'SX' => esc_html__( 'Saint Martin (Dutch part)', 'everest-forms' ),
		'PM' => esc_html__( 'Saint Pierre and Miquelon', 'everest-forms' ),
		'VC' => esc_html__( 'Saint Vincent and the Grenadines', 'everest-forms' ),
		'SM' => esc_html__( 'San Marino', 'everest-forms' ),
		'ST' => esc_html__( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'everest-forms' ),
		'SA' => esc_html__( 'Saudi Arabia', 'everest-forms' ),
		'SN' => esc_html__( 'Senegal', 'everest-forms' ),
		'RS' => esc_html__( 'Serbia', 'everest-forms' ),
		'SC' => esc_html__( 'Seychelles', 'everest-forms' ),
		'SL' => esc_html__( 'Sierra Leone', 'everest-forms' ),
		'SG' => esc_html__( 'Singapore', 'everest-forms' ),
		'SK' => esc_html__( 'Slovakia', 'everest-forms' ),
		'SI' => esc_html__( 'Slovenia', 'everest-forms' ),
		'SB' => esc_html__( 'Solomon Islands', 'everest-forms' ),
		'SO' => esc_html__( 'Somalia', 'everest-forms' ),
		'ZA' => esc_html__( 'South Africa', 'everest-forms' ),
		'GS' => esc_html__( 'South Georgia/Sandwich Islands', 'everest-forms' ),
		'KR' => esc_html__( 'South Korea', 'everest-forms' ),
		'SS' => esc_html__( 'South Sudan', 'everest-forms' ),
		'ES' => esc_html__( 'Spain', 'everest-forms' ),
		'LK' => esc_html__( 'Sri Lanka', 'everest-forms' ),
		'SD' => esc_html__( 'Sudan', 'everest-forms' ),
		'SR' => esc_html__( 'Suriname', 'everest-forms' ),
		'SJ' => esc_html__( 'Svalbard and Jan Mayen', 'everest-forms' ),
		'SZ' => esc_html__( 'Swaziland', 'everest-forms' ),
		'SE' => esc_html__( 'Sweden', 'everest-forms' ),
		'CH' => esc_html__( 'Switzerland', 'everest-forms' ),
		'SY' => esc_html__( 'Syria', 'everest-forms' ),
		'TW' => esc_html__( 'Taiwan', 'everest-forms' ),
		'TJ' => esc_html__( 'Tajikistan', 'everest-forms' ),
		'TZ' => esc_html__( 'Tanzania', 'everest-forms' ),
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
		'GB' => esc_html__( 'United Kingdom (UK)', 'everest-forms' ),
		'US' => esc_html__( 'United States (US)', 'everest-forms' ),
		'UM' => esc_html__( 'United States (US) Minor Outlying Islands', 'everest-forms' ),
		'UY' => esc_html__( 'Uruguay', 'everest-forms' ),
		'UZ' => esc_html__( 'Uzbekistan', 'everest-forms' ),
		'VU' => esc_html__( 'Vanuatu', 'everest-forms' ),
		'VA' => esc_html__( 'Vatican', 'everest-forms' ),
		'VE' => esc_html__( 'Venezuela', 'everest-forms' ),
		'VN' => esc_html__( 'Vietnam', 'everest-forms' ),
		'VG' => esc_html__( 'Virgin Islands (British)', 'everest-forms' ),
		'VI' => esc_html__( 'Virgin Islands (US)', 'everest-forms' ),
		'WF' => esc_html__( 'Wallis and Futuna', 'everest-forms' ),
		'EH' => esc_html__( 'Western Sahara', 'everest-forms' ),
		'WS' => esc_html__( 'Samoa', 'everest-forms' ),
		'YE' => esc_html__( 'Yemen', 'everest-forms' ),
		'ZM' => esc_html__( 'Zambia', 'everest-forms' ),
		'ZW' => esc_html__( 'Zimbabwe', 'everest-forms' ),
	);

	return (array) apply_filters( 'everest_forms_countries', $countries );
}

/**
 * Get U.S. States.
 *
 * @since  1.7.0
 * @return array
 */
function evf_get_states() {
	$states = array(
		'AF' => array(),
		'AL' => array( // Albanian states.
			'AL-01' => _x( 'Berat', 'AL state of Berat', 'everest-forms' ),
			'AL-09' => _x( 'Dibër', 'AL state of Dibër', 'everest-forms' ),
			'AL-02' => _x( 'Durrës', 'AL state of Durrës', 'everest-forms' ),
			'AL-03' => _x( 'Elbasan', 'AL state of Elbasan', 'everest-forms' ),
			'AL-04' => _x( 'Fier', 'AL state of Fier', 'everest-forms' ),
			'AL-05' => _x( 'Gjirokastër', 'AL state of Gjirokastër', 'everest-forms' ),
			'AL-06' => _x( 'Korçë', 'AL state of Korçë', 'everest-forms' ),
			'AL-07' => _x( 'Kukës', 'AL state of Kukës', 'everest-forms' ),
			'AL-08' => _x( 'Lezhë', 'AL state of Lezhë', 'everest-forms' ),
			'AL-10' => _x( 'Shkodër', 'AL state of Shkodër', 'everest-forms' ),
			'AL-11' => _x( 'Tirana', 'AL state of Tirana', 'everest-forms' ),
			'AL-12' => _x( 'Vlorë', 'AL state of Vlorë', 'everest-forms' ),
		),
		'AO' => array( // Angolan states.
			'BGO' => _x( 'Bengo', 'AO state of Bengo', 'everest-forms' ),
			'BLU' => _x( 'Benguela', 'AO state of Benguela', 'everest-forms' ),
			'BIE' => _x( 'Bié', 'AO state of Bié', 'everest-forms' ),
			'CAB' => _x( 'Cabinda', 'AO state of Cabinda', 'everest-forms' ),
			'CNN' => _x( 'Cunene', 'AO state of Cunene', 'everest-forms' ),
			'HUA' => _x( 'Huambo', 'AO state of Huambo', 'everest-forms' ),
			'HUI' => _x( 'Huíla', 'AO state of Huíla', 'everest-forms' ),
			'CCU' => _x( 'Kuando Kubango', 'AO state of Kuando Kubango', 'everest-forms' ),
			'CNO' => _x( 'Kwanza-Norte', 'AO state of Kwanza-Norte', 'everest-forms' ),
			'CUS' => _x( 'Kwanza-Sul', 'AO state of Kwanza-Sul', 'everest-forms' ),
			'LUA' => _x( 'Luanda', 'AO state of Luanda', 'everest-forms' ),
			'LNO' => _x( 'Lunda-Norte', 'AO state of Lunda-Norte', 'everest-forms' ),
			'LSU' => _x( 'Lunda-Sul', 'AO state of Lunda-Sul', 'everest-forms' ),
			'MAL' => _x( 'Malanje', 'AO state of Malanje', 'everest-forms' ),
			'MOX' => _x( 'Moxico', 'AO state of Moxico', 'everest-forms' ),
			'NAM' => _x( 'Namibe', 'AO state of Namibe', 'everest-forms' ),
			'UIG' => _x( 'Uíge', 'AO state of Uíge', 'everest-forms' ),
			'ZAI' => _x( 'Zaire', 'AO state of Zaire', 'everest-forms' ),
		),
		'AR' => array( // Argentinian provinces.
			'C' => _x( 'Ciudad Autónoma de Buenos Aires', 'AR state of Ciudad Autónoma de Buenos Aires', 'everest-forms' ),
			'B' => _x( 'Buenos Aires', 'AR state of Buenos Aires', 'everest-forms' ),
			'K' => _x( 'Catamarca', 'AR state of Catamarca', 'everest-forms' ),
			'H' => _x( 'Chaco', 'AR state of Chaco', 'everest-forms' ),
			'U' => _x( 'Chubut', 'AR state of Chubut', 'everest-forms' ),
			'X' => _x( 'Córdoba', 'AR state of Córdoba', 'everest-forms' ),
			'W' => _x( 'Corrientes', 'AR state of Corrientes', 'everest-forms' ),
			'E' => _x( 'Entre Ríos', 'AR state of Entre Ríos', 'everest-forms' ),
			'P' => _x( 'Formosa', 'AR state of Formosa', 'everest-forms' ),
			'Y' => _x( 'Jujuy', 'AR state of Jujuy', 'everest-forms' ),
			'L' => _x( 'La Pampa', 'AR state of La Pampa', 'everest-forms' ),
			'F' => _x( 'La Rioja', 'AR state of La Rioja', 'everest-forms' ),
			'M' => _x( 'Mendoza', 'AR state of Mendoza', 'everest-forms' ),
			'N' => _x( 'Misiones', 'AR state of Misiones', 'everest-forms' ),
			'Q' => _x( 'Neuquén', 'AR state of Neuquén', 'everest-forms' ),
			'R' => _x( 'Río Negro', 'AR state of Río Negro', 'everest-forms' ),
			'A' => _x( 'Salta', 'AR state of Salta', 'everest-forms' ),
			'J' => _x( 'San Juan', 'AR state of San Juan', 'everest-forms' ),
			'D' => _x( 'San Luis', 'AR state of San Luis', 'everest-forms' ),
			'Z' => _x( 'Santa Cruz', 'AR state of Santa Cruz', 'everest-forms' ),
			'S' => _x( 'Santa Fe', 'AR state of Santa Fe', 'everest-forms' ),
			'G' => _x( 'Santiago del Estero', 'AR state of Santiago del Estero', 'everest-forms' ),
			'V' => _x( 'Tierra del Fuego', 'AR state of Tierra del Fuego', 'everest-forms' ),
			'T' => _x( 'Tucumán', 'AR state of Tucumán', 'everest-forms' ),
		),
		'AT' => array(),
		'AU' => array( // Australian states.
			'ACT' => _x( 'Australian Capital Territory', 'AU state of Australian Capital Territory', 'everest-forms' ),
			'NSW' => _x( 'New South Wales', 'AU state of New South Wales', 'everest-forms' ),
			'NT'  => _x( 'Northern Territory', 'AU state of Northern Territory', 'everest-forms' ),
			'QLD' => _x( 'Queensland', 'AU state of Queensland', 'everest-forms' ),
			'SA'  => _x( 'South Australia', 'AU state of South Australia', 'everest-forms' ),
			'TAS' => _x( 'Tasmania', 'AU state of Tasmania', 'everest-forms' ),
			'VIC' => _x( 'Victoria', 'AU state of Victoria', 'everest-forms' ),
			'WA'  => _x( 'Western Australia', 'AU state of Western Australia', 'everest-forms' ),
		),
		'AX' => array(),
		'BD' => array( // Bangladeshi districts.
			'BD-05' => _x( 'Bagerhat', 'BD state of Bagerhat', 'everest-forms' ),
			'BD-01' => _x( 'Bandarban', 'BD state of Bandarban', 'everest-forms' ),
			'BD-02' => _x( 'Barguna', 'BD state of Barguna', 'everest-forms' ),
			'BD-06' => _x( 'Barishal', 'BD state of Barishal', 'everest-forms' ),
			'BD-07' => _x( 'Bhola', 'BD state of Bhola', 'everest-forms' ),
			'BD-03' => _x( 'Bogura', 'BD state of Bogura', 'everest-forms' ),
			'BD-04' => _x( 'Brahmanbaria', 'BD state of Brahmanbaria', 'everest-forms' ),
			'BD-09' => _x( 'Chandpur', 'BD state of Chandpur', 'everest-forms' ),
			'BD-10' => _x( 'Chattogram', 'BD state of Chattogram', 'everest-forms' ),
			'BD-12' => _x( 'Chuadanga', 'BD state of Chuadanga', 'everest-forms' ),
			'BD-11' => _x( "Cox's Bazar", "BD state of Cox's Bazar", 'everest-forms' ),
			'BD-08' => _x( 'Cumilla', 'BD state of Cumilla', 'everest-forms' ),
			'BD-13' => _x( 'Dhaka', 'BD state of Dhaka', 'everest-forms' ),
			'BD-14' => _x( 'Dinajpur', 'BD state of Dinajpur', 'everest-forms' ),
			'BD-15' => _x( 'Faridpur ', 'BD state of Faridpur ', 'everest-forms' ),
			'BD-16' => _x( 'Feni', 'BD state of Feni', 'everest-forms' ),
			'BD-19' => _x( 'Gaibandha', 'BD state of Gaibandha', 'everest-forms' ),
			'BD-18' => _x( 'Gazipur', 'BD state of Gazipur', 'everest-forms' ),
			'BD-17' => _x( 'Gopalganj', 'BD state of Gopalganj', 'everest-forms' ),
			'BD-20' => _x( 'Habiganj', 'BD state of Habiganj', 'everest-forms' ),
			'BD-21' => _x( 'Jamalpur', 'BD state of Jamalpur', 'everest-forms' ),
			'BD-22' => _x( 'Jashore', 'BD state of Jashore', 'everest-forms' ),
			'BD-25' => _x( 'Jhalokati', 'BD state of Jhalokati', 'everest-forms' ),
			'BD-23' => _x( 'Jhenaidah', 'BD state of Jhenaidah', 'everest-forms' ),
			'BD-24' => _x( 'Joypurhat', 'BD state of Joypurhat', 'everest-forms' ),
			'BD-29' => _x( 'Khagrachhari', 'BD state of Khagrachhari', 'everest-forms' ),
			'BD-27' => _x( 'Khulna', 'BD state of Khulna', 'everest-forms' ),
			'BD-26' => _x( 'Kishoreganj', 'BD state of Kishoreganj', 'everest-forms' ),
			'BD-28' => _x( 'Kurigram', 'BD state of Kurigram', 'everest-forms' ),
			'BD-30' => _x( 'Kushtia', 'BD state of Kushtia', 'everest-forms' ),
			'BD-31' => _x( 'Lakshmipur', 'BD state of Lakshmipur', 'everest-forms' ),
			'BD-32' => _x( 'Lalmonirhat', 'BD state of Lalmonirhat', 'everest-forms' ),
			'BD-36' => _x( 'Madaripur', 'BD state of Madaripur', 'everest-forms' ),
			'BD-37' => _x( 'Magura', 'BD state of Magura', 'everest-forms' ),
			'BD-33' => _x( 'Manikganj ', 'BD state of Manikganj ', 'everest-forms' ),
			'BD-39' => _x( 'Meherpur', 'BD state of Meherpur', 'everest-forms' ),
			'BD-38' => _x( 'Moulvibazar', 'BD state of Moulvibazar', 'everest-forms' ),
			'BD-35' => _x( 'Munshiganj', 'BD state of Munshiganj', 'everest-forms' ),
			'BD-34' => _x( 'Mymensingh', 'BD state of Mymensingh', 'everest-forms' ),
			'BD-48' => _x( 'Naogaon', 'BD state of Naogaon', 'everest-forms' ),
			'BD-43' => _x( 'Narail', 'BD state of Narail', 'everest-forms' ),
			'BD-40' => _x( 'Narayanganj', 'BD state of Narayanganj', 'everest-forms' ),
			'BD-42' => _x( 'Narsingdi', 'BD state of Narsingdi', 'everest-forms' ),
			'BD-44' => _x( 'Natore', 'BD state of Natore', 'everest-forms' ),
			'BD-45' => _x( 'Nawabganj', 'BD state of Nawabganj', 'everest-forms' ),
			'BD-41' => _x( 'Netrakona', 'BD state of Netrakona', 'everest-forms' ),
			'BD-46' => _x( 'Nilphamari', 'BD state of Nilphamari', 'everest-forms' ),
			'BD-47' => _x( 'Noakhali', 'BD state of Noakhali', 'everest-forms' ),
			'BD-49' => _x( 'Pabna', 'BD state of Pabna', 'everest-forms' ),
			'BD-52' => _x( 'Panchagarh', 'BD state of Panchagarh', 'everest-forms' ),
			'BD-51' => _x( 'Patuakhali', 'BD state of Patuakhali', 'everest-forms' ),
			'BD-50' => _x( 'Pirojpur', 'BD state of Pirojpur', 'everest-forms' ),
			'BD-53' => _x( 'Rajbari', 'BD state of Rajbari', 'everest-forms' ),
			'BD-54' => _x( 'Rajshahi', 'BD state of Rajshahi', 'everest-forms' ),
			'BD-56' => _x( 'Rangamati', 'BD state of Rangamati', 'everest-forms' ),
			'BD-55' => _x( 'Rangpur', 'BD state of Rangpur', 'everest-forms' ),
			'BD-58' => _x( 'Satkhira', 'BD state of Satkhira', 'everest-forms' ),
			'BD-62' => _x( 'Shariatpur', 'BD state of Shariatpur', 'everest-forms' ),
			'BD-57' => _x( 'Sherpur', 'BD state of Sherpur', 'everest-forms' ),
			'BD-59' => _x( 'Sirajganj', 'BD state of Sirajganj', 'everest-forms' ),
			'BD-61' => _x( 'Sunamganj', 'BD state of Sunamganj', 'everest-forms' ),
			'BD-60' => _x( 'Sylhet', 'BD state of Sylhet', 'everest-forms' ),
			'BD-63' => _x( 'Tangail', 'BD state of Tangail', 'everest-forms' ),
			'BD-64' => _x( 'Thakurgaon', 'BD state of Thakurgaon', 'everest-forms' ),
		),
		'BE' => array(),
		'BG' => array( // Bulgarian states.
			'BG-01' => _x( 'Blagoevgrad', 'BG state of Blagoevgrad', 'everest-forms' ),
			'BG-02' => _x( 'Burgas', 'BG state of Burgas', 'everest-forms' ),
			'BG-08' => _x( 'Dobrich', 'BG state of Dobrich', 'everest-forms' ),
			'BG-07' => _x( 'Gabrovo', 'BG state of Gabrovo', 'everest-forms' ),
			'BG-26' => _x( 'Haskovo', 'BG state of Haskovo', 'everest-forms' ),
			'BG-09' => _x( 'Kardzhali', 'BG state of Kardzhali', 'everest-forms' ),
			'BG-10' => _x( 'Kyustendil', 'BG state of Kyustendil', 'everest-forms' ),
			'BG-11' => _x( 'Lovech', 'BG state of Lovech', 'everest-forms' ),
			'BG-12' => _x( 'Montana', 'BG state of Montana', 'everest-forms' ),
			'BG-13' => _x( 'Pazardzhik', 'BG state of Pazardzhik', 'everest-forms' ),
			'BG-14' => _x( 'Pernik', 'BG state of Pernik', 'everest-forms' ),
			'BG-15' => _x( 'Pleven', 'BG state of Pleven', 'everest-forms' ),
			'BG-16' => _x( 'Plovdiv', 'BG state of Plovdiv', 'everest-forms' ),
			'BG-17' => _x( 'Razgrad', 'BG state of Razgrad', 'everest-forms' ),
			'BG-18' => _x( 'Ruse', 'BG state of Ruse', 'everest-forms' ),
			'BG-27' => _x( 'Shumen', 'BG state of Shumen', 'everest-forms' ),
			'BG-19' => _x( 'Silistra', 'BG state of Silistra', 'everest-forms' ),
			'BG-20' => _x( 'Sliven', 'BG state of Sliven', 'everest-forms' ),
			'BG-21' => _x( 'Smolyan', 'BG state of Smolyan', 'everest-forms' ),
			'BG-23' => _x( 'Sofia', 'BG state of Sofia', 'everest-forms' ),
			'BG-22' => _x( 'Sofia-Grad', 'BG state of Sofia-Grad', 'everest-forms' ),
			'BG-24' => _x( 'Stara Zagora', 'BG state of Stara Zagora', 'everest-forms' ),
			'BG-25' => _x( 'Targovishte', 'BG state of Targovishte', 'everest-forms' ),
			'BG-03' => _x( 'Varna', 'BG state of Varna', 'everest-forms' ),
			'BG-04' => _x( 'Veliko Tarnovo', 'BG state of Veliko Tarnovo', 'everest-forms' ),
			'BG-05' => _x( 'Vidin', 'BG state of Vidin', 'everest-forms' ),
			'BG-06' => _x( 'Vratsa', 'BG state of Vratsa', 'everest-forms' ),
			'BG-28' => _x( 'Yambol', 'BG state of Yambol', 'everest-forms' ),
		),
		'BH' => array(),
		'BI' => array(),
		'BJ' => array( // Beninese states.
			'AL' => _x( 'Alibori', 'BJ state of Alibori', 'everest-forms' ),
			'AK' => _x( 'Atakora', 'BJ state of Atakora', 'everest-forms' ),
			'AQ' => _x( 'Atlantique', 'BJ state of Atlantique', 'everest-forms' ),
			'BO' => _x( 'Borgou', 'BJ state of Borgou', 'everest-forms' ),
			'CO' => _x( 'Collines', 'BJ state of Collines', 'everest-forms' ),
			'KO' => _x( 'Kouffo', 'BJ state of Kouffo', 'everest-forms' ),
			'DO' => _x( 'Donga', 'BJ state of Donga', 'everest-forms' ),
			'LI' => _x( 'Littoral', 'BJ state of Littoral', 'everest-forms' ),
			'MO' => _x( 'Mono', 'BJ state of Mono', 'everest-forms' ),
			'OU' => _x( 'Ouémé', 'BJ state of Ouémé', 'everest-forms' ),
			'PL' => _x( 'Plateau', 'BJ state of Plateau', 'everest-forms' ),
			'ZO' => _x( 'Zou', 'BJ state of Zou', 'everest-forms' ),
		),
		'BO' => array( // Bolivian states.
			'BO-B' => _x( 'Beni', 'BO state of Beni', 'everest-forms' ),
			'BO-H' => _x( 'Chuquisaca', 'BO state of Chuquisaca', 'everest-forms' ),
			'BO-C' => _x( 'Cochabamba', 'BO state of Cochabamba', 'everest-forms' ),
			'BO-L' => _x( 'La Paz', 'BO state of La Paz', 'everest-forms' ),
			'BO-O' => _x( 'Oruro', 'BO state of Oruro', 'everest-forms' ),
			'BO-N' => _x( 'Pando', 'BO state of Pando', 'everest-forms' ),
			'BO-P' => _x( 'Potosí', 'BO state of Potosí', 'everest-forms' ),
			'BO-S' => _x( 'Santa Cruz', 'BO state of Santa Cruz', 'everest-forms' ),
			'BO-T' => _x( 'Tarija', 'BO state of Tarija', 'everest-forms' ),
		),
		'BR' => array( // Brazilian states.
			'AC' => _x( 'Acre', 'BR state of Acre', 'everest-forms' ),
			'AL' => _x( 'Alagoas', 'BR state of Alagoas', 'everest-forms' ),
			'AP' => _x( 'Amapá', 'BR state of Amapá', 'everest-forms' ),
			'AM' => _x( 'Amazonas', 'BR state of Amazonas', 'everest-forms' ),
			'BA' => _x( 'Bahia', 'BR state of Bahia', 'everest-forms' ),
			'CE' => _x( 'Ceará', 'BR state of Ceará', 'everest-forms' ),
			'DF' => _x( 'Distrito Federal', 'BR state of Distrito Federal', 'everest-forms' ),
			'ES' => _x( 'Espírito Santo', 'BR state of Espírito Santo', 'everest-forms' ),
			'GO' => _x( 'Goiás', 'BR state of Goiás', 'everest-forms' ),
			'MA' => _x( 'Maranhão', 'BR state of Maranhão', 'everest-forms' ),
			'MT' => _x( 'Mato Grosso', 'BR state of Mato Grosso', 'everest-forms' ),
			'MS' => _x( 'Mato Grosso do Sul', 'BR state of Mato Grosso do Sul', 'everest-forms' ),
			'MG' => _x( 'Minas Gerais', 'BR state of Minas Gerais', 'everest-forms' ),
			'PA' => _x( 'Pará', 'BR state of Pará', 'everest-forms' ),
			'PB' => _x( 'Paraíba', 'BR state of Paraíba', 'everest-forms' ),
			'PR' => _x( 'Paraná', 'BR state of Paraná', 'everest-forms' ),
			'PE' => _x( 'Pernambuco', 'BR state of Pernambuco', 'everest-forms' ),
			'PI' => _x( 'Piauí', 'BR state of Piauí', 'everest-forms' ),
			'RJ' => _x( 'Rio de Janeiro', 'BR state of Rio de Janeiro', 'everest-forms' ),
			'RN' => _x( 'Rio Grande do Norte', 'BR state of Rio Grande do Norte', 'everest-forms' ),
			'RS' => _x( 'Rio Grande do Sul', 'BR state of Rio Grande do Sul', 'everest-forms' ),
			'RO' => _x( 'Rondônia', 'BR state of Rondônia', 'everest-forms' ),
			'RR' => _x( 'Roraima', 'BR state of Roraima', 'everest-forms' ),
			'SC' => _x( 'Santa Catarina', 'BR state of Santa Catarina', 'everest-forms' ),
			'SP' => _x( 'São Paulo', 'BR state of São Paulo', 'everest-forms' ),
			'SE' => _x( 'Sergipe', 'BR state of Sergipe', 'everest-forms' ),
			'TO' => _x( 'Tocantins', 'BR state of Tocantins', 'everest-forms' ),

		),
		'CA' => array( // Canadian states.
			'AB' => _x( 'Alberta', 'CA state of Alberta', 'everest-forms' ),
			'BC' => _x( 'British Columbia', 'CA state of British Columbia', 'everest-forms' ),
			'MB' => _x( 'Manitoba', 'CA state of Manitoba', 'everest-forms' ),
			'NB' => _x( 'New Brunswick', 'CA state of New Brunswick', 'everest-forms' ),
			'NL' => _x( 'Newfoundland and Labrador', 'CA state of Newfoundland and Labrador', 'everest-forms' ),
			'NT' => _x( 'Northwest Territories', 'CA state of Northwest Territories', 'everest-forms' ),
			'NS' => _x( 'Nova Scotia', 'CA state of Nova Scotia', 'everest-forms' ),
			'NU' => _x( 'Nunavut', 'CA state of Nunavut', 'everest-forms' ),
			'ON' => _x( 'Ontario', 'CA state of Ontario', 'everest-forms' ),
			'PE' => _x( 'Prince Edward Island', 'CA state of Prince Edward Island', 'everest-forms' ),
			'QC' => _x( 'Quebec', 'CA state of Quebec', 'everest-forms' ),
			'SK' => _x( 'Saskatchewan', 'CA state of Saskatchewan', 'everest-forms' ),
			'YT' => _x( 'Yukon Territory', 'CA state of Yukon Territory', 'everest-forms' ),
		),
		'CH' => array( // Swiss cantons.
			'AG' => _x( 'Aargau', 'CH state of Aargau', 'everest-forms' ),
			'AR' => _x( 'Appenzell Ausserrhoden', 'CH state of Appenzell Ausserrhoden', 'everest-forms' ),
			'AI' => _x( 'Appenzell Innerrhoden', 'CH state of Appenzell Innerrhoden', 'everest-forms' ),
			'BL' => _x( 'Basel-Landschaft', 'CH state of Basel-Landschaft', 'everest-forms' ),
			'BS' => _x( 'Basel-Stadt', 'CH state of Basel-Stadt', 'everest-forms' ),
			'BE' => _x( 'Bern', 'CH state of Bern', 'everest-forms' ),
			'FR' => _x( 'Fribourg', 'CH state of Fribourg', 'everest-forms' ),
			'GE' => _x( 'Geneva', 'CH state of Geneva', 'everest-forms' ),
			'GL' => _x( 'Glarus', 'CH state of Glarus', 'everest-forms' ),
			'GR' => _x( 'Graubünden', 'CH state of Graubünden', 'everest-forms' ),
			'JU' => _x( 'Jura', 'CH state of Jura', 'everest-forms' ),
			'LU' => _x( 'Luzern', 'CH state of Luzern', 'everest-forms' ),
			'NE' => _x( 'Neuchâtel', 'CH state of Neuchâtel', 'everest-forms' ),
			'NW' => _x( 'Nidwalden', 'CH state of Nidwalden', 'everest-forms' ),
			'OW' => _x( 'Obwalden', 'CH state of Obwalden', 'everest-forms' ),
			'SH' => _x( 'Schaffhausen', 'CH state of Schaffhausen', 'everest-forms' ),
			'SZ' => _x( 'Schwyz', 'CH state of Schwyz', 'everest-forms' ),
			'SO' => _x( 'Solothurn', 'CH state of Solothurn', 'everest-forms' ),
			'SG' => _x( 'St. Gallen', 'CH state of St. Gallen', 'everest-forms' ),
			'TG' => _x( 'Thurgau', 'CH state of Thurgau', 'everest-forms' ),
			'TI' => _x( 'Ticino', 'CH state of Ticino', 'everest-forms' ),
			'UR' => _x( 'Uri', 'CH state of Uri', 'everest-forms' ),
			'VS' => _x( 'Valais', 'CH state of Valais', 'everest-forms' ),
			'VD' => _x( 'Vaud', 'CH state of Vaud', 'everest-forms' ),
			'ZG' => _x( 'Zug', 'CH state of Zug', 'everest-forms' ),
			'ZH' => _x( 'Zürich', 'CH state of Zürich', 'everest-forms' ),
		),
		'CL' => array( // Chilean states.
			'CL-AI' => _x( 'Aisén del General Carlos Ibañez del Campo', 'CL state of Aisén del General Carlos Ibañez del Campo', 'everest-forms' ),
			'CL-AN' => _x( 'Antofagasta', 'CL state of Antofagasta', 'everest-forms' ),
			'CL-AP' => _x( 'Arica y Parinacota', 'CL state of Arica y Parinacota', 'everest-forms' ),
			'CL-AR' => _x( 'La Araucanía', 'CL state of La Araucanía', 'everest-forms' ),
			'CL-AT' => _x( 'Atacama', 'CL state of Atacama', 'everest-forms' ),
			'CL-BI' => _x( 'Biobío', 'CL state of Biobío', 'everest-forms' ),
			'CL-CO' => _x( 'Coquimbo', 'CL state of Coquimbo', 'everest-forms' ),
			'CL-LI' => _x( "Libertador General Bernardo O'Higgins", "CL state of Libertador General Bernardo O'Higgins", 'everest-forms' ),
			'CL-LL' => _x( 'Los Lagos', 'CL state of Los Lagos', 'everest-forms' ),
			'CL-LR' => _x( 'Los Ríos', 'CL state of Los Ríos', 'everest-forms' ),
			'CL-MA' => _x( 'Magallanes', 'CL state of Magallanes', 'everest-forms' ),
			'CL-ML' => _x( 'Maule', 'CL state of Maule', 'everest-forms' ),
			'CL-NB' => _x( 'Ñuble', 'CL state of Ñuble', 'everest-forms' ),
			'CL-RM' => _x( 'Región Metropolitana de Santiago', 'CL state of Región Metropolitana de Santiago', 'everest-forms' ),
			'CL-TA' => _x( 'Tarapacá', 'CL state of Tarapacá', 'everest-forms' ),
			'CL-VS' => _x( 'Valparaíso', 'CL state of Valparaíso', 'everest-forms' ),
		),
		'CN' => array( // Chinese states.
			'CN1'  => _x( 'Yunnan / 云南', 'CN state of Yunnan / 云南', 'everest-forms' ),
			'CN2'  => _x( 'Beijing / 北京', 'CN state of Beijing / 北京', 'everest-forms' ),
			'CN3'  => _x( 'Tianjin / 天津', 'CN state of Tianjin / 天津', 'everest-forms' ),
			'CN4'  => _x( 'Hebei / 河北', 'CN state of Hebei / 河北', 'everest-forms' ),
			'CN5'  => _x( 'Shanxi / 山西', 'CN state of Shanxi / 山西', 'everest-forms' ),
			'CN6'  => _x( 'Inner Mongolia / 內蒙古', 'CN state of Inner Mongolia / 內蒙古', 'everest-forms' ),
			'CN7'  => _x( 'Liaoning / 辽宁', 'CN state of Liaoning / 辽宁', 'everest-forms' ),
			'CN8'  => _x( 'Jilin / 吉林', 'CN state of Jilin / 吉林', 'everest-forms' ),
			'CN9'  => _x( 'Heilongjiang / 黑龙江', 'CN state of Heilongjiang / 黑龙江', 'everest-forms' ),
			'CN10' => _x( 'Shanghai / 上海', 'CN state of Shanghai / 上海', 'everest-forms' ),
			'CN11' => _x( 'Jiangsu / 江苏', 'CN state of Jiangsu / 江苏', 'everest-forms' ),
			'CN12' => _x( 'Zhejiang / 浙江', 'CN state of Zhejiang / 浙江', 'everest-forms' ),
			'CN13' => _x( 'Anhui / 安徽', 'CN state of Anhui / 安徽', 'everest-forms' ),
			'CN14' => _x( 'Fujian / 福建', 'CN state of Fujian / 福建', 'everest-forms' ),
			'CN15' => _x( 'Jiangxi / 江西', 'CN state of Jiangxi / 江西', 'everest-forms' ),
			'CN16' => _x( 'Shandong / 山东', 'CN state of Shandong / 山东', 'everest-forms' ),
			'CN17' => _x( 'Henan / 河南', 'CN state of Henan / 河南', 'everest-forms' ),
			'CN18' => _x( 'Hubei / 湖北', 'CN state of Hubei / 湖北', 'everest-forms' ),
			'CN19' => _x( 'Hunan / 湖南', 'CN state of Hunan / 湖南', 'everest-forms' ),
			'CN20' => _x( 'Guangdong / 广东', 'CN state of Guangdong / 广东', 'everest-forms' ),
			'CN21' => _x( 'Guangxi Zhuang / 广西壮族', 'CN state of Guangxi Zhuang / 广西壮族', 'everest-forms' ),
			'CN22' => _x( 'Hainan / 海南', 'CN state of Hainan / 海南', 'everest-forms' ),
			'CN23' => _x( 'Chongqing / 重庆', 'CN state of Chongqing / 重庆', 'everest-forms' ),
			'CN24' => _x( 'Sichuan / 四川', 'CN state of Sichuan / 四川', 'everest-forms' ),
			'CN25' => _x( 'Guizhou / 贵州', 'CN state of Guizhou / 贵州', 'everest-forms' ),
			'CN26' => _x( 'Shaanxi / 陕西', 'CN state of Shaanxi / 陕西', 'everest-forms' ),
			'CN27' => _x( 'Gansu / 甘肃', 'CN state of Gansu / 甘肃', 'everest-forms' ),
			'CN28' => _x( 'Qinghai / 青海', 'CN state of Qinghai / 青海', 'everest-forms' ),
			'CN29' => _x( 'Ningxia Hui / 宁夏', 'CN state of Ningxia Hui / 宁夏', 'everest-forms' ),
			'CN30' => _x( 'Macao / 澳门', 'CN state of Macao / 澳门', 'everest-forms' ),
			'CN31' => _x( 'Tibet / 西藏', 'CN state of Tibet / 西藏', 'everest-forms' ),
			'CN32' => _x( 'Xinjiang / 新疆', 'CN state of Xinjiang / 新疆', 'everest-forms' ),
		),
		'CO' => array( // Colombian states.
			'CO-AMA' => _x( 'Amazonas', 'CO state of Amazonas', 'everest-forms' ),
			'CO-ANT' => _x( 'Antioquia', 'CO state of Antioquia', 'everest-forms' ),
			'CO-ARA' => _x( 'Arauca', 'CO state of Arauca', 'everest-forms' ),
			'CO-ATL' => _x( 'Atlántico', 'CO state of Atlántico', 'everest-forms' ),
			'CO-BOL' => _x( 'Bolívar', 'CO state of Bolívar', 'everest-forms' ),
			'CO-BOY' => _x( 'Boyacá', 'CO state of Boyacá', 'everest-forms' ),
			'CO-CAL' => _x( 'Caldas', 'CO state of Caldas', 'everest-forms' ),
			'CO-CAQ' => _x( 'Caquetá', 'CO state of Caquetá', 'everest-forms' ),
			'CO-CAS' => _x( 'Casanare', 'CO state of Casanare', 'everest-forms' ),
			'CO-CAU' => _x( 'Cauca', 'CO state of Cauca', 'everest-forms' ),
			'CO-CES' => _x( 'Cesar', 'CO state of Cesar', 'everest-forms' ),
			'CO-CHO' => _x( 'Chocó', 'CO state of Chocó', 'everest-forms' ),
			'CO-COR' => _x( 'Córdoba', 'CO state of Córdoba', 'everest-forms' ),
			'CO-CUN' => _x( 'Cundinamarca', 'CO state of Cundinamarca', 'everest-forms' ),
			'CO-DC'  => _x( 'Capital District', 'CO state of Capital District', 'everest-forms' ),
			'CO-GUA' => _x( 'Guainía', 'CO state of Guainía', 'everest-forms' ),
			'CO-GUV' => _x( 'Guaviare', 'CO state of Guaviare', 'everest-forms' ),
			'CO-HUI' => _x( 'Huila', 'CO state of Huila', 'everest-forms' ),
			'CO-LAG' => _x( 'La Guajira', 'CO state of La Guajira', 'everest-forms' ),
			'CO-MAG' => _x( 'Magdalena', 'CO state of Magdalena', 'everest-forms' ),
			'CO-MET' => _x( 'Meta', 'CO state of Meta', 'everest-forms' ),
			'CO-NAR' => _x( 'Nariño', 'CO state of Nariño', 'everest-forms' ),
			'CO-NSA' => _x( 'Norte de Santander', 'CO state of Norte de Santander', 'everest-forms' ),
			'CO-PUT' => _x( 'Putumayo', 'CO state of Putumayo', 'everest-forms' ),
			'CO-QUI' => _x( 'Quindío', 'CO state of Quindío', 'everest-forms' ),
			'CO-RIS' => _x( 'Risaralda', 'CO state of Risaralda', 'everest-forms' ),
			'CO-SAN' => _x( 'Santander', 'CO state of Santander', 'everest-forms' ),
			'CO-SAP' => _x( 'San Andrés & Providencia', 'CO state of San Andrés & Providencia', 'everest-forms' ),
			'CO-SUC' => _x( 'Sucre', 'CO state of Sucre', 'everest-forms' ),
			'CO-TOL' => _x( 'Tolima', 'CO state of Tolima', 'everest-forms' ),
			'CO-VAC' => _x( 'Valle del Cauca', 'CO state of Valle del Cauca', 'everest-forms' ),
			'CO-VAU' => _x( 'Vaupés', 'CO state of Vaupés', 'everest-forms' ),
			'CO-VID' => _x( 'Vichada', 'CO state of Vichada', 'everest-forms' ),
		),
		'CR' => array( // Costa Rican states.
			'CR-A'  => _x( 'Alajuela', 'CR state of Alajuela', 'everest-forms' ),
			'CR-C'  => _x( 'Cartago', 'CR state of Cartago', 'everest-forms' ),
			'CR-G'  => _x( 'Guanacaste', 'CR state of Guanacaste', 'everest-forms' ),
			'CR-H'  => _x( 'Heredia', 'CR state of Heredia', 'everest-forms' ),
			'CR-L'  => _x( 'Limón', 'CR state of Limón', 'everest-forms' ),
			'CR-P'  => _x( 'Puntarenas', 'CR state of Puntarenas', 'everest-forms' ),
			'CR-SJ' => _x( 'San José', 'CR state of San José', 'everest-forms' ),
		),
		'CZ' => array(),
		'DE' => array( // German states.
			'DE-BW' => _x( 'Baden-Württemberg', 'DE state of Baden-Württemberg', 'everest-forms' ),
			'DE-BY' => _x( 'Bavaria', 'DE state of Bavaria', 'everest-forms' ),
			'DE-BE' => _x( 'Berlin', 'DE state of Berlin', 'everest-forms' ),
			'DE-BB' => _x( 'Brandenburg', 'DE state of Brandenburg', 'everest-forms' ),
			'DE-HB' => _x( 'Bremen', 'DE state of Bremen', 'everest-forms' ),
			'DE-HH' => _x( 'Hamburg', 'DE state of Hamburg', 'everest-forms' ),
			'DE-HE' => _x( 'Hesse', 'DE state of Hesse', 'everest-forms' ),
			'DE-MV' => _x( 'Mecklenburg-Vorpommern', 'DE state of Mecklenburg-Vorpommern', 'everest-forms' ),
			'DE-NI' => _x( 'Lower Saxony', 'DE state of Lower Saxony', 'everest-forms' ),
			'DE-NW' => _x( 'North Rhine-Westphalia', 'DE state of North Rhine-Westphalia', 'everest-forms' ),
			'DE-RP' => _x( 'Rhineland-Palatinate', 'DE state of Rhineland-Palatinate', 'everest-forms' ),
			'DE-SL' => _x( 'Saarland', 'DE state of Saarland', 'everest-forms' ),
			'DE-SN' => _x( 'Saxony', 'DE state of Saxony', 'everest-forms' ),
			'DE-ST' => _x( 'Saxony-Anhalt', 'DE state of Saxony-Anhalt', 'everest-forms' ),
			'DE-SH' => _x( 'Schleswig-Holstein', 'DE state of Schleswig-Holstein', 'everest-forms' ),
			'DE-TH' => _x( 'Thuringia', 'DE state of Thuringia', 'everest-forms' ),
		),
		'DK' => array(),
		'DO' => array( // Dominican states.
			'DO-01' => _x( 'Distrito Nacional', 'DO state of Distrito Nacional', 'everest-forms' ),
			'DO-02' => _x( 'Azua', 'DO state of Azua', 'everest-forms' ),
			'DO-03' => _x( 'Baoruco', 'DO state of Baoruco', 'everest-forms' ),
			'DO-04' => _x( 'Barahona', 'DO state of Barahona', 'everest-forms' ),
			'DO-33' => _x( 'Cibao Nordeste', 'DO state of Cibao Nordeste', 'everest-forms' ),
			'DO-34' => _x( 'Cibao Noroeste', 'DO state of Cibao Noroeste', 'everest-forms' ),
			'DO-35' => _x( 'Cibao Norte', 'DO state of Cibao Norte', 'everest-forms' ),
			'DO-36' => _x( 'Cibao Sur', 'DO state of Cibao Sur', 'everest-forms' ),
			'DO-05' => _x( 'Dajabón', 'DO state of Dajabón', 'everest-forms' ),
			'DO-06' => _x( 'Duarte', 'DO state of Duarte', 'everest-forms' ),
			'DO-08' => _x( 'El Seibo', 'DO state of El Seibo', 'everest-forms' ),
			'DO-37' => _x( 'El Valle', 'DO state of El Valle', 'everest-forms' ),
			'DO-07' => _x( 'Elías Piña', 'DO state of Elías Piña', 'everest-forms' ),
			'DO-38' => _x( 'Enriquillo', 'DO state of Enriquillo', 'everest-forms' ),
			'DO-09' => _x( 'Espaillat', 'DO state of Espaillat', 'everest-forms' ),
			'DO-30' => _x( 'Hato Mayor', 'DO state of Hato Mayor', 'everest-forms' ),
			'DO-19' => _x( 'Hermanas Mirabal', 'DO state of Hermanas Mirabal', 'everest-forms' ),
			'DO-39' => _x( 'Higüamo', 'DO state of Higüamo', 'everest-forms' ),
			'DO-10' => _x( 'Independencia', 'DO state of Independencia', 'everest-forms' ),
			'DO-11' => _x( 'La Altagracia', 'DO state of La Altagracia', 'everest-forms' ),
			'DO-12' => _x( 'La Romana', 'DO state of La Romana', 'everest-forms' ),
			'DO-13' => _x( 'La Vega', 'DO state of La Vega', 'everest-forms' ),
			'DO-14' => _x( 'María Trinidad Sánchez', 'DO state of María Trinidad Sánchez', 'everest-forms' ),
			'DO-28' => _x( 'Monseñor Nouel', 'DO state of Monseñor Nouel', 'everest-forms' ),
			'DO-15' => _x( 'Monte Cristi', 'DO state of Monte Cristi', 'everest-forms' ),
			'DO-29' => _x( 'Monte Plata', 'DO state of Monte Plata', 'everest-forms' ),
			'DO-40' => _x( 'Ozama', 'DO state of Ozama', 'everest-forms' ),
			'DO-16' => _x( 'Pedernales', 'DO state of Pedernales', 'everest-forms' ),
			'DO-17' => _x( 'Peravia', 'DO state of Peravia', 'everest-forms' ),
			'DO-18' => _x( 'Puerto Plata', 'DO state of Puerto Plata', 'everest-forms' ),
			'DO-20' => _x( 'Samaná', 'DO state of Samaná', 'everest-forms' ),
			'DO-21' => _x( 'San Cristóbal', 'DO state of San Cristóbal', 'everest-forms' ),
			'DO-31' => _x( 'San José de Ocoa', 'DO state of San José de Ocoa', 'everest-forms' ),
			'DO-22' => _x( 'San Juan', 'DO state of San Juan', 'everest-forms' ),
			'DO-23' => _x( 'San Pedro de Macorís', 'DO state of San Pedro de Macorís', 'everest-forms' ),
			'DO-24' => _x( 'Sánchez Ramírez', 'DO state of Sánchez Ramírez', 'everest-forms' ),
			'DO-25' => _x( 'Santiago', 'DO state of Santiago', 'everest-forms' ),
			'DO-26' => _x( 'Santiago Rodríguez', 'DO state of Santiago Rodríguez', 'everest-forms' ),
			'DO-32' => _x( 'Santo Domingo', 'DO state of Santo Domingo', 'everest-forms' ),
			'DO-41' => _x( 'Valdesia', 'DO state of Valdesia', 'everest-forms' ),
			'DO-27' => _x( 'Valverde', 'DO state of Valverde', 'everest-forms' ),
			'DO-42' => _x( 'Yuma', 'DO state of Yuma', 'everest-forms' ),
		),
		'DZ' => array( // Algerian states.
			'DZ-01' => _x( 'Adrar', 'DZ state of Adrar', 'everest-forms' ),
			'DZ-02' => _x( 'Chlef', 'DZ state of Chlef', 'everest-forms' ),
			'DZ-03' => _x( 'Laghouat', 'DZ state of Laghouat', 'everest-forms' ),
			'DZ-04' => _x( 'Oum El Bouaghi', 'DZ state of Oum El Bouaghi', 'everest-forms' ),
			'DZ-05' => _x( 'Batna', 'DZ state of Batna', 'everest-forms' ),
			'DZ-06' => _x( 'Béjaïa', 'DZ state of Béjaïa', 'everest-forms' ),
			'DZ-07' => _x( 'Biskra', 'DZ state of Biskra', 'everest-forms' ),
			'DZ-08' => _x( 'Béchar', 'DZ state of Béchar', 'everest-forms' ),
			'DZ-09' => _x( 'Blida', 'DZ state of Blida', 'everest-forms' ),
			'DZ-10' => _x( 'Bouira', 'DZ state of Bouira', 'everest-forms' ),
			'DZ-11' => _x( 'Tamanghasset', 'DZ state of Tamanghasset', 'everest-forms' ),
			'DZ-12' => _x( 'Tébessa', 'DZ state of Tébessa', 'everest-forms' ),
			'DZ-13' => _x( 'Tlemcen', 'DZ state of Tlemcen', 'everest-forms' ),
			'DZ-14' => _x( 'Tiaret', 'DZ state of Tiaret', 'everest-forms' ),
			'DZ-15' => _x( 'Tizi Ouzou', 'DZ state of Tizi Ouzou', 'everest-forms' ),
			'DZ-16' => _x( 'Algiers', 'DZ state of Algiers', 'everest-forms' ),
			'DZ-17' => _x( 'Djelfa', 'DZ state of Djelfa', 'everest-forms' ),
			'DZ-18' => _x( 'Jijel', 'DZ state of Jijel', 'everest-forms' ),
			'DZ-19' => _x( 'Sétif', 'DZ state of Sétif', 'everest-forms' ),
			'DZ-20' => _x( 'Saïda', 'DZ state of Saïda', 'everest-forms' ),
			'DZ-21' => _x( 'Skikda', 'DZ state of Skikda', 'everest-forms' ),
			'DZ-22' => _x( 'Sidi Bel Abbès', 'DZ state of Sidi Bel Abbès', 'everest-forms' ),
			'DZ-23' => _x( 'Annaba', 'DZ state of Annaba', 'everest-forms' ),
			'DZ-24' => _x( 'Guelma', 'DZ state of Guelma', 'everest-forms' ),
			'DZ-25' => _x( 'Constantine', 'DZ state of Constantine', 'everest-forms' ),
			'DZ-26' => _x( 'Médéa', 'DZ state of Médéa', 'everest-forms' ),
			'DZ-27' => _x( 'Mostaganem', 'DZ state of Mostaganem', 'everest-forms' ),
			'DZ-28' => _x( 'M’Sila', 'DZ state of M’Sila', 'everest-forms' ),
			'DZ-29' => _x( 'Mascara', 'DZ state of Mascara', 'everest-forms' ),
			'DZ-30' => _x( 'Ouargla', 'DZ state of Ouargla', 'everest-forms' ),
			'DZ-31' => _x( 'Oran', 'DZ state of Oran', 'everest-forms' ),
			'DZ-32' => _x( 'El Bayadh', 'DZ state of El Bayadh', 'everest-forms' ),
			'DZ-33' => _x( 'Illizi', 'DZ state of Illizi', 'everest-forms' ),
			'DZ-34' => _x( 'Bordj Bou Arréridj', 'DZ state of Bordj Bou Arréridj', 'everest-forms' ),
			'DZ-35' => _x( 'Boumerdès', 'DZ state of Boumerdès', 'everest-forms' ),
			'DZ-36' => _x( 'El Tarf', 'DZ state of El Tarf', 'everest-forms' ),
			'DZ-37' => _x( 'Tindouf', 'DZ state of Tindouf', 'everest-forms' ),
			'DZ-38' => _x( 'Tissemsilt', 'DZ state of Tissemsilt', 'everest-forms' ),
			'DZ-39' => _x( 'El Oued', 'DZ state of El Oued', 'everest-forms' ),
			'DZ-40' => _x( 'Khenchela', 'DZ state of Khenchela', 'everest-forms' ),
			'DZ-41' => _x( 'Souk Ahras', 'DZ state of Souk Ahras', 'everest-forms' ),
			'DZ-42' => _x( 'Tipasa', 'DZ state of Tipasa', 'everest-forms' ),
			'DZ-43' => _x( 'Mila', 'DZ state of Mila', 'everest-forms' ),
			'DZ-44' => _x( 'Aïn Defla', 'DZ state of Aïn Defla', 'everest-forms' ),
			'DZ-45' => _x( 'Naama', 'DZ state of Naama', 'everest-forms' ),
			'DZ-46' => _x( 'Aïn Témouchent', 'DZ state of Aïn Témouchent', 'everest-forms' ),
			'DZ-47' => _x( 'Ghardaïa', 'DZ state of Ghardaïa', 'everest-forms' ),
			'DZ-48' => _x( 'Relizane', 'DZ state of Relizane', 'everest-forms' ),
		),
		'EE' => array(),
		'EC' => array( // Ecuadorian states.
			'EC-A'  => _x( 'Azuay', 'EC state of Azuay', 'everest-forms' ),
			'EC-B'  => _x( 'Bolívar', 'EC state of Bolívar', 'everest-forms' ),
			'EC-F'  => _x( 'Cañar', 'EC state of Cañar', 'everest-forms' ),
			'EC-C'  => _x( 'Carchi', 'EC state of Carchi', 'everest-forms' ),
			'EC-H'  => _x( 'Chimborazo', 'EC state of Chimborazo', 'everest-forms' ),
			'EC-X'  => _x( 'Cotopaxi', 'EC state of Cotopaxi', 'everest-forms' ),
			'EC-O'  => _x( 'El Oro', 'EC state of El Oro', 'everest-forms' ),
			'EC-E'  => _x( 'Esmeraldas', 'EC state of Esmeraldas', 'everest-forms' ),
			'EC-W'  => _x( 'Galápagos', 'EC state of Galápagos', 'everest-forms' ),
			'EC-G'  => _x( 'Guayas', 'EC state of Guayas', 'everest-forms' ),
			'EC-I'  => _x( 'Imbabura', 'EC state of Imbabura', 'everest-forms' ),
			'EC-L'  => _x( 'Loja', 'EC state of Loja', 'everest-forms' ),
			'EC-R'  => _x( 'Los Ríos', 'EC state of Los Ríos', 'everest-forms' ),
			'EC-M'  => _x( 'Manabí', 'EC state of Manabí', 'everest-forms' ),
			'EC-S'  => _x( 'Morona-Santiago', 'EC state of Morona-Santiago', 'everest-forms' ),
			'EC-N'  => _x( 'Napo', 'EC state of Napo', 'everest-forms' ),
			'EC-D'  => _x( 'Orellana', 'EC state of Orellana', 'everest-forms' ),
			'EC-Y'  => _x( 'Pastaza', 'EC state of Pastaza', 'everest-forms' ),
			'EC-P'  => _x( 'Pichincha', 'EC state of Pichincha', 'everest-forms' ),
			'EC-SE' => _x( 'Santa Elena', 'EC state of Santa Elena', 'everest-forms' ),
			'EC-SD' => _x( 'Santo Domingo de los Tsáchilas', 'EC state of Santo Domingo de los Tsáchilas', 'everest-forms' ),
			'EC-U'  => _x( 'Sucumbíos', 'EC state of Sucumbíos', 'everest-forms' ),
			'EC-T'  => _x( 'Tungurahua', 'EC state of Tungurahua', 'everest-forms' ),
			'EC-Z'  => _x( 'Zamora-Chinchipe', 'EC state of Zamora-Chinchipe', 'everest-forms' ),
		),
		'EG' => array( // Egyptian states.
			'EGALX' => _x( 'Alexandria', 'EG state of Alexandria', 'everest-forms' ),
			'EGASN' => _x( 'Aswan', 'EG state of Aswan', 'everest-forms' ),
			'EGAST' => _x( 'Asyut', 'EG state of Asyut', 'everest-forms' ),
			'EGBA'  => _x( 'Red Sea', 'EG state of Red Sea', 'everest-forms' ),
			'EGBH'  => _x( 'Beheira', 'EG state of Beheira', 'everest-forms' ),
			'EGBNS' => _x( 'Beni Suef', 'EG state of Beni Suef', 'everest-forms' ),
			'EGC'   => _x( 'Cairo', 'EG state of Cairo', 'everest-forms' ),
			'EGDK'  => _x( 'Dakahlia', 'EG state of Dakahlia', 'everest-forms' ),
			'EGDT'  => _x( 'Damietta', 'EG state of Damietta', 'everest-forms' ),
			'EGFYM' => _x( 'Faiyum', 'EG state of Faiyum', 'everest-forms' ),
			'EGGH'  => _x( 'Gharbia', 'EG state of Gharbia', 'everest-forms' ),
			'EGGZ'  => _x( 'Giza', 'EG state of Giza', 'everest-forms' ),
			'EGIS'  => _x( 'Ismailia', 'EG state of Ismailia', 'everest-forms' ),
			'EGJS'  => _x( 'South Sinai', 'EG state of South Sinai', 'everest-forms' ),
			'EGKB'  => _x( 'Qalyubia', 'EG state of Qalyubia', 'everest-forms' ),
			'EGKFS' => _x( 'Kafr el-Sheikh', 'EG state of Kafr el-Sheikh', 'everest-forms' ),
			'EGKN'  => _x( 'Qena', 'EG state of Qena', 'everest-forms' ),
			'EGLX'  => _x( 'Luxor', 'EG state of Luxor', 'everest-forms' ),
			'EGMN'  => _x( 'Minya', 'EG state of Minya', 'everest-forms' ),
			'EGMNF' => _x( 'Monufia', 'EG state of Monufia', 'everest-forms' ),
			'EGMT'  => _x( 'Matrouh', 'EG state of Matrouh', 'everest-forms' ),
			'EGPTS' => _x( 'Port Said', 'EG state of Port Said', 'everest-forms' ),
			'EGSHG' => _x( 'Sohag', 'EG state of Sohag', 'everest-forms' ),
			'EGSHR' => _x( 'Al Sharqia', 'EG state of Al Sharqia', 'everest-forms' ),
			'EGSIN' => _x( 'North Sinai', 'EG state of North Sinai', 'everest-forms' ),
			'EGSUZ' => _x( 'Suez', 'EG state of Suez', 'everest-forms' ),
			'EGWAD' => _x( 'New Valley', 'EG state of New Valley', 'everest-forms' ),
		),
		'ES' => array( // Spanish states.
			'C'  => _x( 'A Coruña', 'ES state of A Coruña', 'everest-forms' ),
			'VI' => _x( 'Araba/Álava', 'ES state of Araba/Álava', 'everest-forms' ),
			'AB' => _x( 'Albacete', 'ES state of Albacete', 'everest-forms' ),
			'A'  => _x( 'Alicante', 'ES state of Alicante', 'everest-forms' ),
			'AL' => _x( 'Almería', 'ES state of Almería', 'everest-forms' ),
			'O'  => _x( 'Asturias', 'ES state of Asturias', 'everest-forms' ),
			'AV' => _x( 'Ávila', 'ES state of Ávila', 'everest-forms' ),
			'BA' => _x( 'Badajoz', 'ES state of Badajoz', 'everest-forms' ),
			'PM' => _x( 'Baleares', 'ES state of Baleares', 'everest-forms' ),
			'B'  => _x( 'Barcelona', 'ES state of Barcelona', 'everest-forms' ),
			'BU' => _x( 'Burgos', 'ES state of Burgos', 'everest-forms' ),
			'CC' => _x( 'Cáceres', 'ES state of Cáceres', 'everest-forms' ),
			'CA' => _x( 'Cádiz', 'ES state of Cádiz', 'everest-forms' ),
			'S'  => _x( 'Cantabria', 'ES state of Cantabria', 'everest-forms' ),
			'CS' => _x( 'Castellón', 'ES state of Castellón', 'everest-forms' ),
			'CE' => _x( 'Ceuta', 'ES state of Ceuta', 'everest-forms' ),
			'CR' => _x( 'Ciudad Real', 'ES state of Ciudad Real', 'everest-forms' ),
			'CO' => _x( 'Córdoba', 'ES state of Córdoba', 'everest-forms' ),
			'CU' => _x( 'Cuenca', 'ES state of Cuenca', 'everest-forms' ),
			'GI' => _x( 'Girona', 'ES state of Girona', 'everest-forms' ),
			'GR' => _x( 'Granada', 'ES state of Granada', 'everest-forms' ),
			'GU' => _x( 'Guadalajara', 'ES state of Guadalajara', 'everest-forms' ),
			'SS' => _x( 'Gipuzkoa', 'ES state of Gipuzkoa', 'everest-forms' ),
			'H'  => _x( 'Huelva', 'ES state of Huelva', 'everest-forms' ),
			'HU' => _x( 'Huesca', 'ES state of Huesca', 'everest-forms' ),
			'J'  => _x( 'Jaén', 'ES state of Jaén', 'everest-forms' ),
			'LO' => _x( 'La Rioja', 'ES state of La Rioja', 'everest-forms' ),
			'GC' => _x( 'Las Palmas', 'ES state of Las Palmas', 'everest-forms' ),
			'LE' => _x( 'León', 'ES state of León', 'everest-forms' ),
			'L'  => _x( 'Lleida', 'ES state of Lleida', 'everest-forms' ),
			'LU' => _x( 'Lugo', 'ES state of Lugo', 'everest-forms' ),
			'M'  => _x( 'Madrid', 'ES state of Madrid', 'everest-forms' ),
			'MA' => _x( 'Málaga', 'ES state of Málaga', 'everest-forms' ),
			'ML' => _x( 'Melilla', 'ES state of Melilla', 'everest-forms' ),
			'MU' => _x( 'Murcia', 'ES state of Murcia', 'everest-forms' ),
			'NA' => _x( 'Navarra', 'ES state of Navarra', 'everest-forms' ),
			'OR' => _x( 'Ourense', 'ES state of Ourense', 'everest-forms' ),
			'P'  => _x( 'Palencia', 'ES state of Palencia', 'everest-forms' ),
			'PO' => _x( 'Pontevedra', 'ES state of Pontevedra', 'everest-forms' ),
			'SA' => _x( 'Salamanca', 'ES state of Salamanca', 'everest-forms' ),
			'TF' => _x( 'Santa Cruz de Tenerife', 'ES state of Santa Cruz de Tenerife', 'everest-forms' ),
			'SG' => _x( 'Segovia', 'ES state of Segovia', 'everest-forms' ),
			'SE' => _x( 'Sevilla', 'ES state of Sevilla', 'everest-forms' ),
			'SO' => _x( 'Soria', 'ES state of Soria', 'everest-forms' ),
			'T'  => _x( 'Tarragona', 'ES state of Tarragona', 'everest-forms' ),
			'TE' => _x( 'Teruel', 'ES state of Teruel', 'everest-forms' ),
			'TO' => _x( 'Toledo', 'ES state of Toledo', 'everest-forms' ),
			'V'  => _x( 'Valencia', 'ES state of Valencia', 'everest-forms' ),
			'VA' => _x( 'Valladolid', 'ES state of Valladolid', 'everest-forms' ),
			'BI' => _x( 'Biscay', 'ES state of Biscay', 'everest-forms' ),
			'ZA' => _x( 'Zamora', 'ES state of Zamora', 'everest-forms' ),
			'Z'  => _x( 'Zaragoza', 'ES state of Zaragoza', 'everest-forms' ),
		),
		'FI' => array(),
		'FR' => array(),
		'GF' => array(),
		'GH' => array( // Ghanaian regions.
			'AF' => _x( 'Ahafo', 'GH state of Ahafo', 'everest-forms' ),
			'AH' => _x( 'Ashanti', 'GH state of Ashanti', 'everest-forms' ),
			'BA' => _x( 'Brong-Ahafo', 'GH state of Brong-Ahafo', 'everest-forms' ),
			'BO' => _x( 'Bono', 'GH state of Bono', 'everest-forms' ),
			'BE' => _x( 'Bono East', 'GH state of Bono East', 'everest-forms' ),
			'CP' => _x( 'Central', 'GH state of Central', 'everest-forms' ),
			'EP' => _x( 'Eastern', 'GH state of Eastern', 'everest-forms' ),
			'AA' => _x( 'Greater Accra', 'GH state of Greater Accra', 'everest-forms' ),
			'NE' => _x( 'North East', 'GH state of North East', 'everest-forms' ),
			'NP' => _x( 'Northern', 'GH state of Northern', 'everest-forms' ),
			'OT' => _x( 'Oti', 'GH state of Oti', 'everest-forms' ),
			'SV' => _x( 'Savannah', 'GH state of Savannah', 'everest-forms' ),
			'UE' => _x( 'Upper East', 'GH state of Upper East', 'everest-forms' ),
			'UW' => _x( 'Upper West', 'GH state of Upper West', 'everest-forms' ),
			'TV' => _x( 'Volta', 'GH state of Volta', 'everest-forms' ),
			'WP' => _x( 'Western', 'GH state of Western', 'everest-forms' ),
			'WN' => _x( 'Western North', 'GH state of Western North', 'everest-forms' ),
		),
		'GP' => array(),
		'GR' => array( // Greek regions.
			'I' => _x( 'Attica', 'GR state of Attica', 'everest-forms' ),
			'A' => _x( 'East Macedonia and Thrace', 'GR state of East Macedonia and Thrace', 'everest-forms' ),
			'B' => _x( 'Central Macedonia', 'GR state of Central Macedonia', 'everest-forms' ),
			'C' => _x( 'West Macedonia', 'GR state of West Macedonia', 'everest-forms' ),
			'D' => _x( 'Epirus', 'GR state of Epirus', 'everest-forms' ),
			'E' => _x( 'Thessaly', 'GR state of Thessaly', 'everest-forms' ),
			'F' => _x( 'Ionian Islands', 'GR state of Ionian Islands', 'everest-forms' ),
			'G' => _x( 'West Greece', 'GR state of West Greece', 'everest-forms' ),
			'H' => _x( 'Central Greece', 'GR state of Central Greece', 'everest-forms' ),
			'J' => _x( 'Peloponnese', 'GR state of Peloponnese', 'everest-forms' ),
			'K' => _x( 'North Aegean', 'GR state of North Aegean', 'everest-forms' ),
			'L' => _x( 'South Aegean', 'GR state of South Aegean', 'everest-forms' ),
			'M' => _x( 'Crete', 'GR state of Crete', 'everest-forms' ),
		),
		'GT' => array( // Guatemalan states.
			'GT-AV' => _x( 'Alta Verapaz', 'GT state of Alta Verapaz', 'everest-forms' ),
			'GT-BV' => _x( 'Baja Verapaz', 'GT state of Baja Verapaz', 'everest-forms' ),
			'GT-CM' => _x( 'Chimaltenango', 'GT state of Chimaltenango', 'everest-forms' ),
			'GT-CQ' => _x( 'Chiquimula', 'GT state of Chiquimula', 'everest-forms' ),
			'GT-PR' => _x( 'El Progreso', 'GT state of El Progreso', 'everest-forms' ),
			'GT-ES' => _x( 'Escuintla', 'GT state of Escuintla', 'everest-forms' ),
			'GT-GU' => _x( 'Guatemala', 'GT state of Guatemala', 'everest-forms' ),
			'GT-HU' => _x( 'Huehuetenango', 'GT state of Huehuetenango', 'everest-forms' ),
			'GT-IZ' => _x( 'Izabal', 'GT state of Izabal', 'everest-forms' ),
			'GT-JA' => _x( 'Jalapa', 'GT state of Jalapa', 'everest-forms' ),
			'GT-JU' => _x( 'Jutiapa', 'GT state of Jutiapa', 'everest-forms' ),
			'GT-PE' => _x( 'Petén', 'GT state of Petén', 'everest-forms' ),
			'GT-QZ' => _x( 'Quetzaltenango', 'GT state of Quetzaltenango', 'everest-forms' ),
			'GT-QC' => _x( 'Quiché', 'GT state of Quiché', 'everest-forms' ),
			'GT-RE' => _x( 'Retalhuleu', 'GT state of Retalhuleu', 'everest-forms' ),
			'GT-SA' => _x( 'Sacatepéquez', 'GT state of Sacatepéquez', 'everest-forms' ),
			'GT-SM' => _x( 'San Marcos', 'GT state of San Marcos', 'everest-forms' ),
			'GT-SR' => _x( 'Santa Rosa', 'GT state of Santa Rosa', 'everest-forms' ),
			'GT-SO' => _x( 'Sololá', 'GT state of Sololá', 'everest-forms' ),
			'GT-SU' => _x( 'Suchitepéquez', 'GT state of Suchitepéquez', 'everest-forms' ),
			'GT-TO' => _x( 'Totonicapán', 'GT state of Totonicapán', 'everest-forms' ),
			'GT-ZA' => _x( 'Zacapa', 'GT state of Zacapa', 'everest-forms' ),
		),
		'HK' => array( // Hong Kong states.
			'HONG KONG'       => _x( 'Hong Kong Island', 'HK state of Hong Kong Island', 'everest-forms' ),
			'KOWLOON'         => _x( 'Kowloon', 'HK state of Kowloon', 'everest-forms' ),
			'NEW TERRITORIES' => _x( 'New Territories', 'HK state of New Territories', 'everest-forms' ),
		),
		'HN' => array( // Honduran states.
			'HN-AT' => _x( 'Atlántida', 'HN state of Atlántida', 'everest-forms' ),
			'HN-IB' => _x( 'Bay Islands', 'HN state of Bay Islands', 'everest-forms' ),
			'HN-CH' => _x( 'Choluteca', 'HN state of Choluteca', 'everest-forms' ),
			'HN-CL' => _x( 'Colón', 'HN state of Colón', 'everest-forms' ),
			'HN-CM' => _x( 'Comayagua', 'HN state of Comayagua', 'everest-forms' ),
			'HN-CP' => _x( 'Copán', 'HN state of Copán', 'everest-forms' ),
			'HN-CR' => _x( 'Cortés', 'HN state of Cortés', 'everest-forms' ),
			'HN-EP' => _x( 'El Paraíso', 'HN state of El Paraíso', 'everest-forms' ),
			'HN-FM' => _x( 'Francisco Morazán', 'HN state of Francisco Morazán', 'everest-forms' ),
			'HN-GD' => _x( 'Gracias a Dios', 'HN state of Gracias a Dios', 'everest-forms' ),
			'HN-IN' => _x( 'Intibucá', 'HN state of Intibucá', 'everest-forms' ),
			'HN-LE' => _x( 'Lempira', 'HN state of Lempira', 'everest-forms' ),
			'HN-LP' => _x( 'La Paz', 'HN state of La Paz', 'everest-forms' ),
			'HN-OC' => _x( 'Ocotepeque', 'HN state of Ocotepeque', 'everest-forms' ),
			'HN-OL' => _x( 'Olancho', 'HN state of Olancho', 'everest-forms' ),
			'HN-SB' => _x( 'Santa Bárbara', 'HN state of Santa Bárbara', 'everest-forms' ),
			'HN-VA' => _x( 'Valle', 'HN state of Valle', 'everest-forms' ),
			'HN-YO' => _x( 'Yoro', 'HN state of Yoro', 'everest-forms' ),
		),
		'HU' => array( // Hungarian states.
			'BK' => _x( 'Bács-Kiskun', 'HU state of Bács-Kiskun', 'everest-forms' ),
			'BE' => _x( 'Békés', 'HU state of Békés', 'everest-forms' ),
			'BA' => _x( 'Baranya', 'HU state of Baranya', 'everest-forms' ),
			'BZ' => _x( 'Borsod-Abaúj-Zemplén', 'HU state of Borsod-Abaúj-Zemplén', 'everest-forms' ),
			'BU' => _x( 'Budapest', 'HU state of Budapest', 'everest-forms' ),
			'CS' => _x( 'Csongrád-Csanád', 'HU state of Csongrád-Csanád', 'everest-forms' ),
			'FE' => _x( 'Fejér', 'HU state of Fejér', 'everest-forms' ),
			'GS' => _x( 'Győr-Moson-Sopron', 'HU state of Győr-Moson-Sopron', 'everest-forms' ),
			'HB' => _x( 'Hajdú-Bihar', 'HU state of Hajdú-Bihar', 'everest-forms' ),
			'HE' => _x( 'Heves', 'HU state of Heves', 'everest-forms' ),
			'JN' => _x( 'Jász-Nagykun-Szolnok', 'HU state of Jász-Nagykun-Szolnok', 'everest-forms' ),
			'KE' => _x( 'Komárom-Esztergom', 'HU state of Komárom-Esztergom', 'everest-forms' ),
			'NO' => _x( 'Nógrád', 'HU state of Nógrád', 'everest-forms' ),
			'PE' => _x( 'Pest', 'HU state of Pest', 'everest-forms' ),
			'SO' => _x( 'Somogy', 'HU state of Somogy', 'everest-forms' ),
			'SZ' => _x( 'Szabolcs-Szatmár-Bereg', 'HU state of Szabolcs-Szatmár-Bereg', 'everest-forms' ),
			'TO' => _x( 'Tolna', 'HU state of Tolna', 'everest-forms' ),
			'VA' => _x( 'Vas', 'HU state of Vas', 'everest-forms' ),
			'VE' => _x( 'Veszprém', 'HU state of Veszprém', 'everest-forms' ),
			'ZA' => _x( 'Zala', 'HU state of Zala', 'everest-forms' ),
		),
		'ID' => array( // Indonesian provinces.
			'AC' => _x( 'Daerah Istimewa Aceh', 'ID state of Daerah Istimewa Aceh', 'everest-forms' ),
			'SU' => _x( 'Sumatera Utara', 'ID state of Sumatera Utara', 'everest-forms' ),
			'SB' => _x( 'Sumatera Barat', 'ID state of Sumatera Barat', 'everest-forms' ),
			'RI' => _x( 'Riau', 'ID state of Riau', 'everest-forms' ),
			'KR' => _x( 'Kepulauan Riau', 'ID state of Kepulauan Riau', 'everest-forms' ),
			'JA' => _x( 'Jambi', 'ID state of Jambi', 'everest-forms' ),
			'SS' => _x( 'Sumatera Selatan', 'ID state of Sumatera Selatan', 'everest-forms' ),
			'BB' => _x( 'Bangka Belitung', 'ID state of Bangka Belitung', 'everest-forms' ),
			'BE' => _x( 'Bengkulu', 'ID state of Bengkulu', 'everest-forms' ),
			'LA' => _x( 'Lampung', 'ID state of Lampung', 'everest-forms' ),
			'JK' => _x( 'DKI Jakarta', 'ID state of DKI Jakarta', 'everest-forms' ),
			'JB' => _x( 'Jawa Barat', 'ID state of Jawa Barat', 'everest-forms' ),
			'BT' => _x( 'Banten', 'ID state of Banten', 'everest-forms' ),
			'JT' => _x( 'Jawa Tengah', 'ID state of Jawa Tengah', 'everest-forms' ),
			'JI' => _x( 'Jawa Timur', 'ID state of Jawa Timur', 'everest-forms' ),
			'YO' => _x( 'Daerah Istimewa Yogyakarta', 'ID state of Daerah Istimewa Yogyakarta', 'everest-forms' ),
			'BA' => _x( 'Bali', 'ID state of Bali', 'everest-forms' ),
			'NB' => _x( 'Nusa Tenggara Barat', 'ID state of Nusa Tenggara Barat', 'everest-forms' ),
			'NT' => _x( 'Nusa Tenggara Timur', 'ID state of Nusa Tenggara Timur', 'everest-forms' ),
			'KB' => _x( 'Kalimantan Barat', 'ID state of Kalimantan Barat', 'everest-forms' ),
			'KT' => _x( 'Kalimantan Tengah', 'ID state of Kalimantan Tengah', 'everest-forms' ),
			'KI' => _x( 'Kalimantan Timur', 'ID state of Kalimantan Timur', 'everest-forms' ),
			'KS' => _x( 'Kalimantan Selatan', 'ID state of Kalimantan Selatan', 'everest-forms' ),
			'KU' => _x( 'Kalimantan Utara', 'ID state of Kalimantan Utara', 'everest-forms' ),
			'SA' => _x( 'Sulawesi Utara', 'ID state of Sulawesi Utara', 'everest-forms' ),
			'ST' => _x( 'Sulawesi Tengah', 'ID state of Sulawesi Tengah', 'everest-forms' ),
			'SG' => _x( 'Sulawesi Tenggara', 'ID state of Sulawesi Tenggara', 'everest-forms' ),
			'SR' => _x( 'Sulawesi Barat', 'ID state of Sulawesi Barat', 'everest-forms' ),
			'SN' => _x( 'Sulawesi Selatan', 'ID state of Sulawesi Selatan', 'everest-forms' ),
			'GO' => _x( 'Gorontalo', 'ID state of Gorontalo', 'everest-forms' ),
			'MA' => _x( 'Maluku', 'ID state of Maluku', 'everest-forms' ),
			'MU' => _x( 'Maluku Utara', 'ID state of Maluku Utara', 'everest-forms' ),
			'PA' => _x( 'Papua', 'ID state of Papua', 'everest-forms' ),
			'PB' => _x( 'Papua Barat', 'ID state of Papua Barat', 'everest-forms' ),
		),
		'IE' => array( // Irish states.
			'CW' => _x( 'Carlow', 'IE state of Carlow', 'everest-forms' ),
			'CN' => _x( 'Cavan', 'IE state of Cavan', 'everest-forms' ),
			'CE' => _x( 'Clare', 'IE state of Clare', 'everest-forms' ),
			'CO' => _x( 'Cork', 'IE state of Cork', 'everest-forms' ),
			'DL' => _x( 'Donegal', 'IE state of Donegal', 'everest-forms' ),
			'D'  => _x( 'Dublin', 'IE state of Dublin', 'everest-forms' ),
			'G'  => _x( 'Galway', 'IE state of Galway', 'everest-forms' ),
			'KY' => _x( 'Kerry', 'IE state of Kerry', 'everest-forms' ),
			'KE' => _x( 'Kildare', 'IE state of Kildare', 'everest-forms' ),
			'KK' => _x( 'Kilkenny', 'IE state of Kilkenny', 'everest-forms' ),
			'LS' => _x( 'Laois', 'IE state of Laois', 'everest-forms' ),
			'LM' => _x( 'Leitrim', 'IE state of Leitrim', 'everest-forms' ),
			'LK' => _x( 'Limerick', 'IE state of Limerick', 'everest-forms' ),
			'LD' => _x( 'Longford', 'IE state of Longford', 'everest-forms' ),
			'LH' => _x( 'Louth', 'IE state of Louth', 'everest-forms' ),
			'MO' => _x( 'Mayo', 'IE state of Mayo', 'everest-forms' ),
			'MH' => _x( 'Meath', 'IE state of Meath', 'everest-forms' ),
			'MN' => _x( 'Monaghan', 'IE state of Monaghan', 'everest-forms' ),
			'OY' => _x( 'Offaly', 'IE state of Offaly', 'everest-forms' ),
			'RN' => _x( 'Roscommon', 'IE state of Roscommon', 'everest-forms' ),
			'SO' => _x( 'Sligo', 'IE state of Sligo', 'everest-forms' ),
			'TA' => _x( 'Tipperary', 'IE state of Tipperary', 'everest-forms' ),
			'WD' => _x( 'Waterford', 'IE state of Waterford', 'everest-forms' ),
			'WH' => _x( 'Westmeath', 'IE state of Westmeath', 'everest-forms' ),
			'WX' => _x( 'Wexford', 'IE state of Wexford', 'everest-forms' ),
			'WW' => _x( 'Wicklow', 'IE state of Wicklow', 'everest-forms' ),
		),
		'IN' => array( // Indian states.
			'AP' => _x( 'Andhra Pradesh', 'IN state of Andhra Pradesh', 'everest-forms' ),
			'AR' => _x( 'Arunachal Pradesh', 'IN state of Arunachal Pradesh', 'everest-forms' ),
			'AS' => _x( 'Assam', 'IN state of Assam', 'everest-forms' ),
			'BR' => _x( 'Bihar', 'IN state of Bihar', 'everest-forms' ),
			'CT' => _x( 'Chhattisgarh', 'IN state of Chhattisgarh', 'everest-forms' ),
			'GA' => _x( 'Goa', 'IN state of Goa', 'everest-forms' ),
			'GJ' => _x( 'Gujarat', 'IN state of Gujarat', 'everest-forms' ),
			'HR' => _x( 'Haryana', 'IN state of Haryana', 'everest-forms' ),
			'HP' => _x( 'Himachal Pradesh', 'IN state of Himachal Pradesh', 'everest-forms' ),
			'JK' => _x( 'Jammu and Kashmir', 'IN state of Jammu and Kashmir', 'everest-forms' ),
			'JH' => _x( 'Jharkhand', 'IN state of Jharkhand', 'everest-forms' ),
			'KA' => _x( 'Karnataka', 'IN state of Karnataka', 'everest-forms' ),
			'KL' => _x( 'Kerala', 'IN state of Kerala', 'everest-forms' ),
			'LA' => _x( 'Ladakh', 'IN state of Ladakh', 'everest-forms' ),
			'MP' => _x( 'Madhya Pradesh', 'IN state of Madhya Pradesh', 'everest-forms' ),
			'MH' => _x( 'Maharashtra', 'IN state of Maharashtra', 'everest-forms' ),
			'MN' => _x( 'Manipur', 'IN state of Manipur', 'everest-forms' ),
			'ML' => _x( 'Meghalaya', 'IN state of Meghalaya', 'everest-forms' ),
			'MZ' => _x( 'Mizoram', 'IN state of Mizoram', 'everest-forms' ),
			'NL' => _x( 'Nagaland', 'IN state of Nagaland', 'everest-forms' ),
			'OR' => _x( 'Odisha', 'IN state of Odisha', 'everest-forms' ),
			'PB' => _x( 'Punjab', 'IN state of Punjab', 'everest-forms' ),
			'RJ' => _x( 'Rajasthan', 'IN state of Rajasthan', 'everest-forms' ),
			'SK' => _x( 'Sikkim', 'IN state of Sikkim', 'everest-forms' ),
			'TN' => _x( 'Tamil Nadu', 'IN state of Tamil Nadu', 'everest-forms' ),
			'TS' => _x( 'Telangana', 'IN state of Telangana', 'everest-forms' ),
			'TR' => _x( 'Tripura', 'IN state of Tripura', 'everest-forms' ),
			'UK' => _x( 'Uttarakhand', 'IN state of Uttarakhand', 'everest-forms' ),
			'UP' => _x( 'Uttar Pradesh', 'IN state of Uttar Pradesh', 'everest-forms' ),
			'WB' => _x( 'West Bengal', 'IN state of West Bengal', 'everest-forms' ),
			'AN' => _x( 'Andaman and Nicobar Islands', 'IN state of Andaman and Nicobar Islands', 'everest-forms' ),
			'CH' => _x( 'Chandigarh', 'IN state of Chandigarh', 'everest-forms' ),
			'DN' => _x( 'Dadra and Nagar Haveli', 'IN state of Dadra and Nagar Haveli', 'everest-forms' ),
			'DD' => _x( 'Daman and Diu', 'IN state of Daman and Diu', 'everest-forms' ),
			'DL' => _x( 'Delhi', 'IN state of Delhi', 'everest-forms' ),
			'LD' => _x( 'Lakshadeep', 'IN state of Lakshadeep', 'everest-forms' ),
			'PY' => _x( 'Pondicherry (Puducherry)', 'IN state of Pondicherry (Puducherry)', 'everest-forms' ),
		),
		'IR' => array( // Irania states.
			'KHZ' => _x( 'Khuzestan (خوزستان)', 'IR state of Khuzestan (خوزستان)', 'everest-forms' ),
			'THR' => _x( 'Tehran (تهران)', 'IR state of Tehran (تهران)', 'everest-forms' ),
			'ILM' => _x( 'Ilaam (ایلام)', 'IR state of Ilaam (ایلام)', 'everest-forms' ),
			'BHR' => _x( 'Bushehr (بوشهر)', 'IR state of Bushehr (بوشهر)', 'everest-forms' ),
			'ADL' => _x( 'Ardabil (اردبیل)', 'IR state of Ardabil (اردبیل)', 'everest-forms' ),
			'ESF' => _x( 'Isfahan (اصفهان)', 'IR state of Isfahan (اصفهان)', 'everest-forms' ),
			'YZD' => _x( 'Yazd (یزد)', 'IR state of Yazd (یزد)', 'everest-forms' ),
			'KRH' => _x( 'Kermanshah (کرمانشاه)', 'IR state of Kermanshah (کرمانشاه)', 'everest-forms' ),
			'KRN' => _x( 'Kerman (کرمان)', 'IR state of Kerman (کرمان)', 'everest-forms' ),
			'HDN' => _x( 'Hamadan (همدان)', 'IR state of Hamadan (همدان)', 'everest-forms' ),
			'GZN' => _x( 'Ghazvin (قزوین)', 'IR state of Ghazvin (قزوین)', 'everest-forms' ),
			'ZJN' => _x( 'Zanjan (زنجان)', 'IR state of Zanjan (زنجان)', 'everest-forms' ),
			'LRS' => _x( 'Luristan (لرستان)', 'IR state of Luristan (لرستان)', 'everest-forms' ),
			'ABZ' => _x( 'Alborz (البرز)', 'IR state of Alborz (البرز)', 'everest-forms' ),
			'EAZ' => _x( 'East Azarbaijan (آذربایجان شرقی)', 'IR state of East Azarbaijan (آذربایجان شرقی)', 'everest-forms' ),
			'WAZ' => _x( 'West Azarbaijan (آذربایجان غربی)', 'IR state of West Azarbaijan (آذربایجان غربی)', 'everest-forms' ),
			'CHB' => _x( 'Chaharmahal and Bakhtiari (چهارمحال و بختیاری)', 'IR state of Chaharmahal and Bakhtiari (چهارمحال و بختیاری)', 'everest-forms' ),
			'SKH' => _x( 'South Khorasan (خراسان جنوبی)', 'IR state of South Khorasan (خراسان جنوبی)', 'everest-forms' ),
			'RKH' => _x( 'Razavi Khorasan (خراسان رضوی)', 'IR state of Razavi Khorasan (خراسان رضوی)', 'everest-forms' ),
			'NKH' => _x( 'North Khorasan (خراسان شمالی)', 'IR state of North Khorasan (خراسان شمالی)', 'everest-forms' ),
			'SMN' => _x( 'Semnan (سمنان)', 'IR state of Semnan (سمنان)', 'everest-forms' ),
			'FRS' => _x( 'Fars (فارس)', 'IR state of Fars (فارس)', 'everest-forms' ),
			'QHM' => _x( 'Qom (قم)', 'IR state of Qom (قم)', 'everest-forms' ),
			'KRD' => _x( 'Kurdistan / کردستان)', 'IR state of Kurdistan / کردستان)', 'everest-forms' ),
			'KBD' => _x( 'Kohgiluyeh and BoyerAhmad (کهگیلوییه و بویراحمد)', 'IR state of Kohgiluyeh and BoyerAhmad (کهگیلوییه و بویراحمد)', 'everest-forms' ),
			'GLS' => _x( 'Golestan (گلستان)', 'IR state of Golestan (گلستان)', 'everest-forms' ),
			'GIL' => _x( 'Gilan (گیلان)', 'IR state of Gilan (گیلان)', 'everest-forms' ),
			'MZN' => _x( 'Mazandaran (مازندران)', 'IR state of Mazandaran (مازندران)', 'everest-forms' ),
			'MKZ' => _x( 'Markazi (مرکزی)', 'IR state of Markazi (مرکزی)', 'everest-forms' ),
			'HRZ' => _x( 'Hormozgan (هرمزگان)', 'IR state of Hormozgan (هرمزگان)', 'everest-forms' ),
			'SBN' => _x( 'Sistan and Baluchestan (سیستان و بلوچستان)', 'IR state of Sistan and Baluchestan (سیستان و بلوچستان)', 'everest-forms' ),
		),
		'IS' => array(),
		'IT' => array( // Italian provinces.
			'AG' => _x( 'Agrigento', 'IT state of Agrigento', 'everest-forms' ),
			'AL' => _x( 'Alessandria', 'IT state of Alessandria', 'everest-forms' ),
			'AN' => _x( 'Ancona', 'IT state of Ancona', 'everest-forms' ),
			'AO' => _x( 'Aosta', 'IT state of Aosta', 'everest-forms' ),
			'AR' => _x( 'Arezzo', 'IT state of Arezzo', 'everest-forms' ),
			'AP' => _x( 'Ascoli Piceno', 'IT state of Ascoli Piceno', 'everest-forms' ),
			'AT' => _x( 'Asti', 'IT state of Asti', 'everest-forms' ),
			'AV' => _x( 'Avellino', 'IT state of Avellino', 'everest-forms' ),
			'BA' => _x( 'Bari', 'IT state of Bari', 'everest-forms' ),
			'BT' => _x( 'Barletta-Andria-Trani', 'IT state of Barletta-Andria-Trani', 'everest-forms' ),
			'BL' => _x( 'Belluno', 'IT state of Belluno', 'everest-forms' ),
			'BN' => _x( 'Benevento', 'IT state of Benevento', 'everest-forms' ),
			'BG' => _x( 'Bergamo', 'IT state of Bergamo', 'everest-forms' ),
			'BI' => _x( 'Biella', 'IT state of Biella', 'everest-forms' ),
			'BO' => _x( 'Bologna', 'IT state of Bologna', 'everest-forms' ),
			'BZ' => _x( 'Bolzano', 'IT state of Bolzano', 'everest-forms' ),
			'BS' => _x( 'Brescia', 'IT state of Brescia', 'everest-forms' ),
			'BR' => _x( 'Brindisi', 'IT state of Brindisi', 'everest-forms' ),
			'CA' => _x( 'Cagliari', 'IT state of Cagliari', 'everest-forms' ),
			'CL' => _x( 'Caltanissetta', 'IT state of Caltanissetta', 'everest-forms' ),
			'CB' => _x( 'Campobasso', 'IT state of Campobasso', 'everest-forms' ),
			'CE' => _x( 'Caserta', 'IT state of Caserta', 'everest-forms' ),
			'CT' => _x( 'Catania', 'IT state of Catania', 'everest-forms' ),
			'CZ' => _x( 'Catanzaro', 'IT state of Catanzaro', 'everest-forms' ),
			'CH' => _x( 'Chieti', 'IT state of Chieti', 'everest-forms' ),
			'CO' => _x( 'Como', 'IT state of Como', 'everest-forms' ),
			'CS' => _x( 'Cosenza', 'IT state of Cosenza', 'everest-forms' ),
			'CR' => _x( 'Cremona', 'IT state of Cremona', 'everest-forms' ),
			'KR' => _x( 'Crotone', 'IT state of Crotone', 'everest-forms' ),
			'CN' => _x( 'Cuneo', 'IT state of Cuneo', 'everest-forms' ),
			'EN' => _x( 'Enna', 'IT state of Enna', 'everest-forms' ),
			'FM' => _x( 'Fermo', 'IT state of Fermo', 'everest-forms' ),
			'FE' => _x( 'Ferrara', 'IT state of Ferrara', 'everest-forms' ),
			'FI' => _x( 'Firenze', 'IT state of Firenze', 'everest-forms' ),
			'FG' => _x( 'Foggia', 'IT state of Foggia', 'everest-forms' ),
			'FC' => _x( 'Forlì-Cesena', 'IT state of Forlì-Cesena', 'everest-forms' ),
			'FR' => _x( 'Frosinone', 'IT state of Frosinone', 'everest-forms' ),
			'GE' => _x( 'Genova', 'IT state of Genova', 'everest-forms' ),
			'GO' => _x( 'Gorizia', 'IT state of Gorizia', 'everest-forms' ),
			'GR' => _x( 'Grosseto', 'IT state of Grosseto', 'everest-forms' ),
			'IM' => _x( 'Imperia', 'IT state of Imperia', 'everest-forms' ),
			'IS' => _x( 'Isernia', 'IT state of Isernia', 'everest-forms' ),
			'SP' => _x( 'La Spezia', 'IT state of La Spezia', 'everest-forms' ),
			'AQ' => _x( "L'Aquila", "IT state of L'Aquila", 'everest-forms' ),
			'LT' => _x( 'Latina', 'IT state of Latina', 'everest-forms' ),
			'LE' => _x( 'Lecce', 'IT state of Lecce', 'everest-forms' ),
			'LC' => _x( 'Lecco', 'IT state of Lecco', 'everest-forms' ),
			'LI' => _x( 'Livorno', 'IT state of Livorno', 'everest-forms' ),
			'LO' => _x( 'Lodi', 'IT state of Lodi', 'everest-forms' ),
			'LU' => _x( 'Lucca', 'IT state of Lucca', 'everest-forms' ),
			'MC' => _x( 'Macerata', 'IT state of Macerata', 'everest-forms' ),
			'MN' => _x( 'Mantova', 'IT state of Mantova', 'everest-forms' ),
			'MS' => _x( 'Massa-Carrara', 'IT state of Massa-Carrara', 'everest-forms' ),
			'MT' => _x( 'Matera', 'IT state of Matera', 'everest-forms' ),
			'ME' => _x( 'Messina', 'IT state of Messina', 'everest-forms' ),
			'MI' => _x( 'Milano', 'IT state of Milano', 'everest-forms' ),
			'MO' => _x( 'Modena', 'IT state of Modena', 'everest-forms' ),
			'MB' => _x( 'Monza e della Brianza', 'IT state of Monza e della Brianza', 'everest-forms' ),
			'NA' => _x( 'Napoli', 'IT state of Napoli', 'everest-forms' ),
			'NO' => _x( 'Novara', 'IT state of Novara', 'everest-forms' ),
			'NU' => _x( 'Nuoro', 'IT state of Nuoro', 'everest-forms' ),
			'OR' => _x( 'Oristano', 'IT state of Oristano', 'everest-forms' ),
			'PD' => _x( 'Padova', 'IT state of Padova', 'everest-forms' ),
			'PA' => _x( 'Palermo', 'IT state of Palermo', 'everest-forms' ),
			'PR' => _x( 'Parma', 'IT state of Parma', 'everest-forms' ),
			'PV' => _x( 'Pavia', 'IT state of Pavia', 'everest-forms' ),
			'PG' => _x( 'Perugia', 'IT state of Perugia', 'everest-forms' ),
			'PU' => _x( 'Pesaro e Urbino', 'IT state of Pesaro e Urbino', 'everest-forms' ),
			'PE' => _x( 'Pescara', 'IT state of Pescara', 'everest-forms' ),
			'PC' => _x( 'Piacenza', 'IT state of Piacenza', 'everest-forms' ),
			'PI' => _x( 'Pisa', 'IT state of Pisa', 'everest-forms' ),
			'PT' => _x( 'Pistoia', 'IT state of Pistoia', 'everest-forms' ),
			'PN' => _x( 'Pordenone', 'IT state of Pordenone', 'everest-forms' ),
			'PZ' => _x( 'Potenza', 'IT state of Potenza', 'everest-forms' ),
			'PO' => _x( 'Prato', 'IT state of Prato', 'everest-forms' ),
			'RG' => _x( 'Ragusa', 'IT state of Ragusa', 'everest-forms' ),
			'RA' => _x( 'Ravenna', 'IT state of Ravenna', 'everest-forms' ),
			'RC' => _x( 'Reggio Calabria', 'IT state of Reggio Calabria', 'everest-forms' ),
			'RE' => _x( 'Reggio Emilia', 'IT state of Reggio Emilia', 'everest-forms' ),
			'RI' => _x( 'Rieti', 'IT state of Rieti', 'everest-forms' ),
			'RN' => _x( 'Rimini', 'IT state of Rimini', 'everest-forms' ),
			'RM' => _x( 'Roma', 'IT state of Roma', 'everest-forms' ),
			'RO' => _x( 'Rovigo', 'IT state of Rovigo', 'everest-forms' ),
			'SA' => _x( 'Salerno', 'IT state of Salerno', 'everest-forms' ),
			'SS' => _x( 'Sassari', 'IT state of Sassari', 'everest-forms' ),
			'SV' => _x( 'Savona', 'IT state of Savona', 'everest-forms' ),
			'SI' => _x( 'Siena', 'IT state of Siena', 'everest-forms' ),
			'SR' => _x( 'Siracusa', 'IT state of Siracusa', 'everest-forms' ),
			'SO' => _x( 'Sondrio', 'IT state of Sondrio', 'everest-forms' ),
			'SU' => _x( 'Sud Sardegna', 'IT state of Sud Sardegna', 'everest-forms' ),
			'TA' => _x( 'Taranto', 'IT state of Taranto', 'everest-forms' ),
			'TE' => _x( 'Teramo', 'IT state of Teramo', 'everest-forms' ),
			'TR' => _x( 'Terni', 'IT state of Terni', 'everest-forms' ),
			'TO' => _x( 'Torino', 'IT state of Torino', 'everest-forms' ),
			'TP' => _x( 'Trapani', 'IT state of Trapani', 'everest-forms' ),
			'TN' => _x( 'Trento', 'IT state of Trento', 'everest-forms' ),
			'TV' => _x( 'Treviso', 'IT state of Treviso', 'everest-forms' ),
			'TS' => _x( 'Trieste', 'IT state of Trieste', 'everest-forms' ),
			'UD' => _x( 'Udine', 'IT state of Udine', 'everest-forms' ),
			'VA' => _x( 'Varese', 'IT state of Varese', 'everest-forms' ),
			'VE' => _x( 'Venezia', 'IT state of Venezia', 'everest-forms' ),
			'VB' => _x( 'Verbano-Cusio-Ossola', 'IT state of Verbano-Cusio-Ossola', 'everest-forms' ),
			'VC' => _x( 'Vercelli', 'IT state of Vercelli', 'everest-forms' ),
			'VR' => _x( 'Verona', 'IT state of Verona', 'everest-forms' ),
			'VV' => _x( 'Vibo Valentia', 'IT state of Vibo Valentia', 'everest-forms' ),
			'VI' => _x( 'Vicenza', 'IT state of Vicenza', 'everest-forms' ),
			'VT' => _x( 'Viterbo', 'IT state of Viterbo', 'everest-forms' ),
		),
		'IL' => array(),
		'IM' => array(),
		'JM' => array( // Jamaican parishes.
			'JM-01' => _x( 'Kingston', 'JM state of Kingston', 'everest-forms' ),
			'JM-02' => _x( 'Saint Andrew', 'JM state of Saint Andrew', 'everest-forms' ),
			'JM-03' => _x( 'Saint Thomas', 'JM state of Saint Thomas', 'everest-forms' ),
			'JM-04' => _x( 'Portland', 'JM state of Portland', 'everest-forms' ),
			'JM-05' => _x( 'Saint Mary', 'JM state of Saint Mary', 'everest-forms' ),
			'JM-06' => _x( 'Saint Ann', 'JM state of Saint Ann', 'everest-forms' ),
			'JM-07' => _x( 'Trelawny', 'JM state of Trelawny', 'everest-forms' ),
			'JM-08' => _x( 'Saint James', 'JM state of Saint James', 'everest-forms' ),
			'JM-09' => _x( 'Hanover', 'JM state of Hanover', 'everest-forms' ),
			'JM-10' => _x( 'Westmoreland', 'JM state of Westmoreland', 'everest-forms' ),
			'JM-11' => _x( 'Saint Elizabeth', 'JM state of Saint Elizabeth', 'everest-forms' ),
			'JM-12' => _x( 'Manchester', 'JM state of Manchester', 'everest-forms' ),
			'JM-13' => _x( 'Clarendon', 'JM state of Clarendon', 'everest-forms' ),
			'JM-14' => _x( 'Saint Catherine', 'JM state of Saint Catherine', 'everest-forms' ),
		),

		'JP' => array(
			'JP01' => _x( 'Hokkaido', 'JP state of Hokkaido', 'everest-forms' ),
			'JP02' => _x( 'Aomori', 'JP state of Aomori', 'everest-forms' ),
			'JP03' => _x( 'Iwate', 'JP state of Iwate', 'everest-forms' ),
			'JP04' => _x( 'Miyagi', 'JP state of Miyagi', 'everest-forms' ),
			'JP05' => _x( 'Akita', 'JP state of Akita', 'everest-forms' ),
			'JP06' => _x( 'Yamagata', 'JP state of Yamagata', 'everest-forms' ),
			'JP07' => _x( 'Fukushima', 'JP state of Fukushima', 'everest-forms' ),
			'JP08' => _x( 'Ibaraki', 'JP state of Ibaraki', 'everest-forms' ),
			'JP09' => _x( 'Tochigi', 'JP state of Tochigi', 'everest-forms' ),
			'JP10' => _x( 'Gunma', 'JP state of Gunma', 'everest-forms' ),
			'JP11' => _x( 'Saitama', 'JP state of Saitama', 'everest-forms' ),
			'JP12' => _x( 'Chiba', 'JP state of Chiba', 'everest-forms' ),
			'JP13' => _x( 'Tokyo', 'JP state of Tokyo', 'everest-forms' ),
			'JP14' => _x( 'Kanagawa', 'JP state of Kanagawa', 'everest-forms' ),
			'JP15' => _x( 'Niigata', 'JP state of Niigata', 'everest-forms' ),
			'JP16' => _x( 'Toyama', 'JP state of Toyama', 'everest-forms' ),
			'JP17' => _x( 'Ishikawa', 'JP state of Ishikawa', 'everest-forms' ),
			'JP18' => _x( 'Fukui', 'JP state of Fukui', 'everest-forms' ),
			'JP19' => _x( 'Yamanashi', 'JP state of Yamanashi', 'everest-forms' ),
			'JP20' => _x( 'Nagano', 'JP state of Nagano', 'everest-forms' ),
			'JP21' => _x( 'Gifu', 'JP state of Gifu', 'everest-forms' ),
			'JP22' => _x( 'Shizuoka', 'JP state of Shizuoka', 'everest-forms' ),
			'JP23' => _x( 'Aichi', 'JP state of Aichi', 'everest-forms' ),
			'JP24' => _x( 'Mie', 'JP state of Mie', 'everest-forms' ),
			'JP25' => _x( 'Shiga', 'JP state of Shiga', 'everest-forms' ),
			'JP26' => _x( 'Kyoto', 'JP state of Kyoto', 'everest-forms' ),
			'JP27' => _x( 'Osaka', 'JP state of Osaka', 'everest-forms' ),
			'JP28' => _x( 'Hyogo', 'JP state of Hyogo', 'everest-forms' ),
			'JP29' => _x( 'Nara', 'JP state of Nara', 'everest-forms' ),
			'JP30' => _x( 'Wakayama', 'JP state of Wakayama', 'everest-forms' ),
			'JP31' => _x( 'Tottori', 'JP state of Tottori', 'everest-forms' ),
			'JP32' => _x( 'Shimane', 'JP state of Shimane', 'everest-forms' ),
			'JP33' => _x( 'Okayama', 'JP state of Okayama', 'everest-forms' ),
			'JP34' => _x( 'Hiroshima', 'JP state of Hiroshima', 'everest-forms' ),
			'JP35' => _x( 'Yamaguchi', 'JP state of Yamaguchi', 'everest-forms' ),
			'JP36' => _x( 'Tokushima', 'JP state of Tokushima', 'everest-forms' ),
			'JP37' => _x( 'Kagawa', 'JP state of Kagawa', 'everest-forms' ),
			'JP38' => _x( 'Ehime', 'JP state of Ehime', 'everest-forms' ),
			'JP39' => _x( 'Kochi', 'JP state of Kochi', 'everest-forms' ),
			'JP40' => _x( 'Fukuoka', 'JP state of Fukuoka', 'everest-forms' ),
			'JP41' => _x( 'Saga', 'JP state of Saga', 'everest-forms' ),
			'JP42' => _x( 'Nagasaki', 'JP state of Nagasaki', 'everest-forms' ),
			'JP43' => _x( 'Kumamoto', 'JP state of Kumamoto', 'everest-forms' ),
			'JP44' => _x( 'Oita', 'JP state of Oita', 'everest-forms' ),
			'JP45' => _x( 'Miyazaki', 'JP state of Miyazaki', 'everest-forms' ),
			'JP46' => _x( 'Kagoshima', 'JP state of Kagoshima', 'everest-forms' ),
			'JP47' => _x( 'Okinawa', 'JP state of Okinawa', 'everest-forms' ),
		),
		'KE' => array( // Kenyan counties.
			'KE01' => _x( 'Baringo', 'KE state of Baringo', 'everest-forms' ),
			'KE02' => _x( 'Bomet', 'KE state of Bomet', 'everest-forms' ),
			'KE03' => _x( 'Bungoma', 'KE state of Bungoma', 'everest-forms' ),
			'KE04' => _x( 'Busia', 'KE state of Busia', 'everest-forms' ),
			'KE05' => _x( 'Elgeyo-Marakwet', 'KE state of Elgeyo-Marakwet', 'everest-forms' ),
			'KE06' => _x( 'Embu', 'KE state of Embu', 'everest-forms' ),
			'KE07' => _x( 'Garissa', 'KE state of Garissa', 'everest-forms' ),
			'KE08' => _x( 'Homa Bay', 'KE state of Homa Bay', 'everest-forms' ),
			'KE09' => _x( 'Isiolo', 'KE state of Isiolo', 'everest-forms' ),
			'KE10' => _x( 'Kajiado', 'KE state of Kajiado', 'everest-forms' ),
			'KE11' => _x( 'Kakamega', 'KE state of Kakamega', 'everest-forms' ),
			'KE12' => _x( 'Kericho', 'KE state of Kericho', 'everest-forms' ),
			'KE13' => _x( 'Kiambu', 'KE state of Kiambu', 'everest-forms' ),
			'KE14' => _x( 'Kilifi', 'KE state of Kilifi', 'everest-forms' ),
			'KE15' => _x( 'Kirinyaga', 'KE state of Kirinyaga', 'everest-forms' ),
			'KE16' => _x( 'Kisii', 'KE state of Kisii', 'everest-forms' ),
			'KE17' => _x( 'Kisumu', 'KE state of Kisumu', 'everest-forms' ),
			'KE18' => _x( 'Kitui', 'KE state of Kitui', 'everest-forms' ),
			'KE19' => _x( 'Kwale', 'KE state of Kwale', 'everest-forms' ),
			'KE20' => _x( 'Laikipia', 'KE state of Laikipia', 'everest-forms' ),
			'KE21' => _x( 'Lamu', 'KE state of Lamu', 'everest-forms' ),
			'KE22' => _x( 'Machakos', 'KE state of Machakos', 'everest-forms' ),
			'KE23' => _x( 'Makueni', 'KE state of Makueni', 'everest-forms' ),
			'KE24' => _x( 'Mandera', 'KE state of Mandera', 'everest-forms' ),
			'KE25' => _x( 'Marsabit', 'KE state of Marsabit', 'everest-forms' ),
			'KE26' => _x( 'Meru', 'KE state of Meru', 'everest-forms' ),
			'KE27' => _x( 'Migori', 'KE state of Migori', 'everest-forms' ),
			'KE28' => _x( 'Mombasa', 'KE state of Mombasa', 'everest-forms' ),
			'KE29' => _x( 'Murang’a', 'KE state of Murang’a', 'everest-forms' ),
			'KE30' => _x( 'Nairobi County', 'KE state of Nairobi County', 'everest-forms' ),
			'KE31' => _x( 'Nakuru', 'KE state of Nakuru', 'everest-forms' ),
			'KE32' => _x( 'Nandi', 'KE state of Nandi', 'everest-forms' ),
			'KE33' => _x( 'Narok', 'KE state of Narok', 'everest-forms' ),
			'KE34' => _x( 'Nyamira', 'KE state of Nyamira', 'everest-forms' ),
			'KE35' => _x( 'Nyandarua', 'KE state of Nyandarua', 'everest-forms' ),
			'KE36' => _x( 'Nyeri', 'KE state of Nyeri', 'everest-forms' ),
			'KE37' => _x( 'Samburu', 'KE state of Samburu', 'everest-forms' ),
			'KE38' => _x( 'Siaya', 'KE state of Siaya', 'everest-forms' ),
			'KE39' => _x( 'Taita-Taveta', 'KE state of Taita-Taveta', 'everest-forms' ),
			'KE40' => _x( 'Tana River', 'KE state of Tana River', 'everest-forms' ),
			'KE41' => _x( 'Tharaka-Nithi', 'KE state of Tharaka-Nithi', 'everest-forms' ),
			'KE42' => _x( 'Trans Nzoia', 'KE state of Trans Nzoia', 'everest-forms' ),
			'KE43' => _x( 'Turkana', 'KE state of Turkana', 'everest-forms' ),
			'KE44' => _x( 'Uasin Gishu', 'KE state of Uasin Gishu', 'everest-forms' ),
			'KE45' => _x( 'Vihiga', 'KE state of Vihiga', 'everest-forms' ),
			'KE46' => _x( 'Wajir', 'KE state of Wajir', 'everest-forms' ),
			'KE47' => _x( 'West Pokot', 'KE state of West Pokot', 'everest-forms' ),
		),
		'KR' => array(),
		'KW' => array(),
		'LA' => array( // Laotian provinces.
			'AT' => _x( 'Attapeu', 'LA state of Attapeu', 'everest-forms' ),
			'BK' => _x( 'Bokeo', 'LA state of Bokeo', 'everest-forms' ),
			'BL' => _x( 'Bolikhamsai', 'LA state of Bolikhamsai', 'everest-forms' ),
			'CH' => _x( 'Champasak', 'LA state of Champasak', 'everest-forms' ),
			'HO' => _x( 'Houaphanh', 'LA state of Houaphanh', 'everest-forms' ),
			'KH' => _x( 'Khammouane', 'LA state of Khammouane', 'everest-forms' ),
			'LM' => _x( 'Luang Namtha', 'LA state of Luang Namtha', 'everest-forms' ),
			'LP' => _x( 'Luang Prabang', 'LA state of Luang Prabang', 'everest-forms' ),
			'OU' => _x( 'Oudomxay', 'LA state of Oudomxay', 'everest-forms' ),
			'PH' => _x( 'Phongsaly', 'LA state of Phongsaly', 'everest-forms' ),
			'SL' => _x( 'Salavan', 'LA state of Salavan', 'everest-forms' ),
			'SV' => _x( 'Savannakhet', 'LA state of Savannakhet', 'everest-forms' ),
			'VI' => _x( 'Vientiane Province', 'LA state of Vientiane Province', 'everest-forms' ),
			'VT' => _x( 'Vientiane', 'LA state of Vientiane', 'everest-forms' ),
			'XA' => _x( 'Sainyabuli', 'LA state of Sainyabuli', 'everest-forms' ),
			'XE' => _x( 'Sekong', 'LA state of Sekong', 'everest-forms' ),
			'XI' => _x( 'Xiangkhouang', 'LA state of Xiangkhouang', 'everest-forms' ),
			'XS' => _x( 'Xaisomboun', 'LA state of Xaisomboun', 'everest-forms' ),
		),
		'LB' => array(),
		'LR' => array( // Liberian provinces.
			'BM' => _x( 'Bomi', 'LR state of Bomi', 'everest-forms' ),
			'BN' => _x( 'Bong', 'LR state of Bong', 'everest-forms' ),
			'GA' => _x( 'Gbarpolu', 'LR state of Gbarpolu', 'everest-forms' ),
			'GB' => _x( 'Grand Bassa', 'LR state of Grand Bassa', 'everest-forms' ),
			'GC' => _x( 'Grand Cape Mount', 'LR state of Grand Cape Mount', 'everest-forms' ),
			'GG' => _x( 'Grand Gedeh', 'LR state of Grand Gedeh', 'everest-forms' ),
			'GK' => _x( 'Grand Kru', 'LR state of Grand Kru', 'everest-forms' ),
			'LO' => _x( 'Lofa', 'LR state of Lofa', 'everest-forms' ),
			'MA' => _x( 'Margibi', 'LR state of Margibi', 'everest-forms' ),
			'MY' => _x( 'Maryland', 'LR state of Maryland', 'everest-forms' ),
			'MO' => _x( 'Montserrado', 'LR state of Montserrado', 'everest-forms' ),
			'NM' => _x( 'Nimba', 'LR state of Nimba', 'everest-forms' ),
			'RV' => _x( 'Rivercess', 'LR state of Rivercess', 'everest-forms' ),
			'RG' => _x( 'River Gee', 'LR state of River Gee', 'everest-forms' ),
			'SN' => _x( 'Sinoe', 'LR state of Sinoe', 'everest-forms' ),
		),
		'LU' => array(),
		'MD' => array( // Moldovan states.
			'C'  => _x( 'Chișinău', 'MD state of Chișinău', 'everest-forms' ),
			'BL' => _x( 'Bălți', 'MD state of Bălți', 'everest-forms' ),
			'AN' => _x( 'Anenii Noi', 'MD state of Anenii Noi', 'everest-forms' ),
			'BS' => _x( 'Basarabeasca', 'MD state of Basarabeasca', 'everest-forms' ),
			'BR' => _x( 'Briceni', 'MD state of Briceni', 'everest-forms' ),
			'CH' => _x( 'Cahul', 'MD state of Cahul', 'everest-forms' ),
			'CT' => _x( 'Cantemir', 'MD state of Cantemir', 'everest-forms' ),
			'CL' => _x( 'Călărași', 'MD state of Călărași', 'everest-forms' ),
			'CS' => _x( 'Căușeni', 'MD state of Căușeni', 'everest-forms' ),
			'CM' => _x( 'Cimișlia', 'MD state of Cimișlia', 'everest-forms' ),
			'CR' => _x( 'Criuleni', 'MD state of Criuleni', 'everest-forms' ),
			'DN' => _x( 'Dondușeni', 'MD state of Dondușeni', 'everest-forms' ),
			'DR' => _x( 'Drochia', 'MD state of Drochia', 'everest-forms' ),
			'DB' => _x( 'Dubăsari', 'MD state of Dubăsari', 'everest-forms' ),
			'ED' => _x( 'Edineț', 'MD state of Edineț', 'everest-forms' ),
			'FL' => _x( 'Fălești', 'MD state of Fălești', 'everest-forms' ),
			'FR' => _x( 'Florești', 'MD state of Florești', 'everest-forms' ),
			'GE' => _x( 'UTA Găgăuzia', 'MD state of UTA Găgăuzia', 'everest-forms' ),
			'GL' => _x( 'Glodeni', 'MD state of Glodeni', 'everest-forms' ),
			'HN' => _x( 'Hîncești', 'MD state of Hîncești', 'everest-forms' ),
			'IL' => _x( 'Ialoveni', 'MD state of Ialoveni', 'everest-forms' ),
			'LV' => _x( 'Leova', 'MD state of Leova', 'everest-forms' ),
			'NS' => _x( 'Nisporeni', 'MD state of Nisporeni', 'everest-forms' ),
			'OC' => _x( 'Ocnița', 'MD state of Ocnița', 'everest-forms' ),
			'OR' => _x( 'Orhei', 'MD state of Orhei', 'everest-forms' ),
			'RZ' => _x( 'Rezina', 'MD state of Rezina', 'everest-forms' ),
			'RS' => _x( 'Rîșcani', 'MD state of Rîșcani', 'everest-forms' ),
			'SG' => _x( 'Sîngerei', 'MD state of Sîngerei', 'everest-forms' ),
			'SR' => _x( 'Soroca', 'MD state of Soroca', 'everest-forms' ),
			'ST' => _x( 'Strășeni', 'MD state of Strășeni', 'everest-forms' ),
			'SD' => _x( 'Șoldănești', 'MD state of Șoldănești', 'everest-forms' ),
			'SV' => _x( 'Ștefan Vodă', 'MD state of Ștefan Vodă', 'everest-forms' ),
			'TR' => _x( 'Taraclia', 'MD state of Taraclia', 'everest-forms' ),
			'TL' => _x( 'Telenești', 'MD state of Telenești', 'everest-forms' ),
			'UN' => _x( 'Ungheni', 'MD state of Ungheni', 'everest-forms' ),
		),
		'MQ' => array(),
		'MT' => array(),
		'MX' => array( // Mexican states.
			'DF' => _x( 'Ciudad de México', 'MX state of Ciudad de México', 'everest-forms' ),
			'JA' => _x( 'Jalisco', 'MX state of Jalisco', 'everest-forms' ),
			'NL' => _x( 'Nuevo León', 'MX state of Nuevo León', 'everest-forms' ),
			'AG' => _x( 'Aguascalientes', 'MX state of Aguascalientes', 'everest-forms' ),
			'BC' => _x( 'Baja California', 'MX state of Baja California', 'everest-forms' ),
			'BS' => _x( 'Baja California Sur', 'MX state of Baja California Sur', 'everest-forms' ),
			'CM' => _x( 'Campeche', 'MX state of Campeche', 'everest-forms' ),
			'CS' => _x( 'Chiapas', 'MX state of Chiapas', 'everest-forms' ),
			'CH' => _x( 'Chihuahua', 'MX state of Chihuahua', 'everest-forms' ),
			'CO' => _x( 'Coahuila', 'MX state of Coahuila', 'everest-forms' ),
			'CL' => _x( 'Colima', 'MX state of Colima', 'everest-forms' ),
			'DG' => _x( 'Durango', 'MX state of Durango', 'everest-forms' ),
			'GT' => _x( 'Guanajuato', 'MX state of Guanajuato', 'everest-forms' ),
			'GR' => _x( 'Guerrero', 'MX state of Guerrero', 'everest-forms' ),
			'HG' => _x( 'Hidalgo', 'MX state of Hidalgo', 'everest-forms' ),
			'MX' => _x( 'Estado de México', 'MX state of Estado de México', 'everest-forms' ),
			'MI' => _x( 'Michoacán', 'MX state of Michoacán', 'everest-forms' ),
			'MO' => _x( 'Morelos', 'MX state of Morelos', 'everest-forms' ),
			'NA' => _x( 'Nayarit', 'MX state of Nayarit', 'everest-forms' ),
			'OA' => _x( 'Oaxaca', 'MX state of Oaxaca', 'everest-forms' ),
			'PU' => _x( 'Puebla', 'MX state of Puebla', 'everest-forms' ),
			'QT' => _x( 'Querétaro', 'MX state of Querétaro', 'everest-forms' ),
			'QR' => _x( 'Quintana Roo', 'MX state of Quintana Roo', 'everest-forms' ),
			'SL' => _x( 'San Luis Potosí', 'MX state of San Luis Potosí', 'everest-forms' ),
			'SI' => _x( 'Sinaloa', 'MX state of Sinaloa', 'everest-forms' ),
			'SO' => _x( 'Sonora', 'MX state of Sonora', 'everest-forms' ),
			'TB' => _x( 'Tabasco', 'MX state of Tabasco', 'everest-forms' ),
			'TM' => _x( 'Tamaulipas', 'MX state of Tamaulipas', 'everest-forms' ),
			'TL' => _x( 'Tlaxcala', 'MX state of Tlaxcala', 'everest-forms' ),
			'VE' => _x( 'Veracruz', 'MX state of Veracruz', 'everest-forms' ),
			'YU' => _x( 'Yucatán', 'MX state of Yucatán', 'everest-forms' ),
			'ZA' => _x( 'Zacatecas', 'MX state of Zacatecas', 'everest-forms' ),
		),
		'MY' => array( // Malaysian states.
			'JHR' => _x( 'Johor', 'MY state of Johor', 'everest-forms' ),
			'KDH' => _x( 'Kedah', 'MY state of Kedah', 'everest-forms' ),
			'KTN' => _x( 'Kelantan', 'MY state of Kelantan', 'everest-forms' ),
			'LBN' => _x( 'Labuan', 'MY state of Labuan', 'everest-forms' ),
			'MLK' => _x( 'Malacca (Melaka)', 'MY state of Malacca (Melaka)', 'everest-forms' ),
			'NSN' => _x( 'Negeri Sembilan', 'MY state of Negeri Sembilan', 'everest-forms' ),
			'PHG' => _x( 'Pahang', 'MY state of Pahang', 'everest-forms' ),
			'PNG' => _x( 'Penang (Pulau Pinang)', 'MY state of Penang (Pulau Pinang)', 'everest-forms' ),
			'PRK' => _x( 'Perak', 'MY state of Perak', 'everest-forms' ),
			'PLS' => _x( 'Perlis', 'MY state of Perlis', 'everest-forms' ),
			'SBH' => _x( 'Sabah', 'MY state of Sabah', 'everest-forms' ),
			'SWK' => _x( 'Sarawak', 'MY state of Sarawak', 'everest-forms' ),
			'SGR' => _x( 'Selangor', 'MY state of Selangor', 'everest-forms' ),
			'TRG' => _x( 'Terengganu', 'MY state of Terengganu', 'everest-forms' ),
			'PJY' => _x( 'Putrajaya', 'MY state of Putrajaya', 'everest-forms' ),
			'KUL' => _x( 'Kuala Lumpur', 'MY state of Kuala Lumpur', 'everest-forms' ),
		),
		'MZ' => array( // Mozambican provinces.
			'MZP'   => _x( 'Cabo Delgado', 'MZ state of Cabo Delgado', 'everest-forms' ),
			'MZG'   => _x( 'Gaza', 'MZ state of Gaza', 'everest-forms' ),
			'MZI'   => _x( 'Inhambane', 'MZ state of Inhambane', 'everest-forms' ),
			'MZB'   => _x( 'Manica', 'MZ state of Manica', 'everest-forms' ),
			'MZL'   => _x( 'Maputo Province', 'MZ state of Maputo Province', 'everest-forms' ),
			'MZMPM' => _x( 'Maputo', 'MZ state of Maputo', 'everest-forms' ),
			'MZN'   => _x( 'Nampula', 'MZ state of Nampula', 'everest-forms' ),
			'MZA'   => _x( 'Niassa', 'MZ state of Niassa', 'everest-forms' ),
			'MZS'   => _x( 'Sofala', 'MZ state of Sofala', 'everest-forms' ),
			'MZT'   => _x( 'Tete', 'MZ state of Tete', 'everest-forms' ),
			'MZQ'   => _x( 'Zambézia', 'MZ state of Zambézia', 'everest-forms' ),
		),
		'NA' => array( // Namibian regions.
			'ER' => _x( 'Erongo', 'NA state of Erongo', 'everest-forms' ),
			'HA' => _x( 'Hardap', 'NA state of Hardap', 'everest-forms' ),
			'KA' => _x( 'Karas', 'NA state of Karas', 'everest-forms' ),
			'KE' => _x( 'Kavango East', 'NA state of Kavango East', 'everest-forms' ),
			'KW' => _x( 'Kavango West', 'NA state of Kavango West', 'everest-forms' ),
			'KH' => _x( 'Khomas', 'NA state of Khomas', 'everest-forms' ),
			'KU' => _x( 'Kunene', 'NA state of Kunene', 'everest-forms' ),
			'OW' => _x( 'Ohangwena', 'NA state of Ohangwena', 'everest-forms' ),
			'OH' => _x( 'Omaheke', 'NA state of Omaheke', 'everest-forms' ),
			'OS' => _x( 'Omusati', 'NA state of Omusati', 'everest-forms' ),
			'ON' => _x( 'Oshana', 'NA state of Oshana', 'everest-forms' ),
			'OT' => _x( 'Oshikoto', 'NA state of Oshikoto', 'everest-forms' ),
			'OD' => _x( 'Otjozondjupa', 'NA state of Otjozondjupa', 'everest-forms' ),
			'CA' => _x( 'Zambezi', 'NA state of Zambezi', 'everest-forms' ),
		),
		'NG' => array( // Nigerian provinces.
			'AB' => _x( 'Abia', 'NG state of Abia', 'everest-forms' ),
			'FC' => _x( 'Abuja', 'NG state of Abuja', 'everest-forms' ),
			'AD' => _x( 'Adamawa', 'NG state of Adamawa', 'everest-forms' ),
			'AK' => _x( 'Akwa Ibom', 'NG state of Akwa Ibom', 'everest-forms' ),
			'AN' => _x( 'Anambra', 'NG state of Anambra', 'everest-forms' ),
			'BA' => _x( 'Bauchi', 'NG state of Bauchi', 'everest-forms' ),
			'BY' => _x( 'Bayelsa', 'NG state of Bayelsa', 'everest-forms' ),
			'BE' => _x( 'Benue', 'NG state of Benue', 'everest-forms' ),
			'BO' => _x( 'Borno', 'NG state of Borno', 'everest-forms' ),
			'CR' => _x( 'Cross River', 'NG state of Cross River', 'everest-forms' ),
			'DE' => _x( 'Delta', 'NG state of Delta', 'everest-forms' ),
			'EB' => _x( 'Ebonyi', 'NG state of Ebonyi', 'everest-forms' ),
			'ED' => _x( 'Edo', 'NG state of Edo', 'everest-forms' ),
			'EK' => _x( 'Ekiti', 'NG state of Ekiti', 'everest-forms' ),
			'EN' => _x( 'Enugu', 'NG state of Enugu', 'everest-forms' ),
			'GO' => _x( 'Gombe', 'NG state of Gombe', 'everest-forms' ),
			'IM' => _x( 'Imo', 'NG state of Imo', 'everest-forms' ),
			'JI' => _x( 'Jigawa', 'NG state of Jigawa', 'everest-forms' ),
			'KD' => _x( 'Kaduna', 'NG state of Kaduna', 'everest-forms' ),
			'KN' => _x( 'Kano', 'NG state of Kano', 'everest-forms' ),
			'KT' => _x( 'Katsina', 'NG state of Katsina', 'everest-forms' ),
			'KE' => _x( 'Kebbi', 'NG state of Kebbi', 'everest-forms' ),
			'KO' => _x( 'Kogi', 'NG state of Kogi', 'everest-forms' ),
			'KW' => _x( 'Kwara', 'NG state of Kwara', 'everest-forms' ),
			'LA' => _x( 'Lagos', 'NG state of Lagos', 'everest-forms' ),
			'NA' => _x( 'Nasarawa', 'NG state of Nasarawa', 'everest-forms' ),
			'NI' => _x( 'Niger', 'NG state of Niger', 'everest-forms' ),
			'OG' => _x( 'Ogun', 'NG state of Ogun', 'everest-forms' ),
			'ON' => _x( 'Ondo', 'NG state of Ondo', 'everest-forms' ),
			'OS' => _x( 'Osun', 'NG state of Osun', 'everest-forms' ),
			'OY' => _x( 'Oyo', 'NG state of Oyo', 'everest-forms' ),
			'PL' => _x( 'Plateau', 'NG state of Plateau', 'everest-forms' ),
			'RI' => _x( 'Rivers', 'NG state of Rivers', 'everest-forms' ),
			'SO' => _x( 'Sokoto', 'NG state of Sokoto', 'everest-forms' ),
			'TA' => _x( 'Taraba', 'NG state of Taraba', 'everest-forms' ),
			'YO' => _x( 'Yobe', 'NG state of Yobe', 'everest-forms' ),
			'ZA' => _x( 'Zamfara', 'NG state of Zamfara', 'everest-forms' ),
		),
		'NL' => array(),
		'NO' => array(),
		'NP' => array( // Nepalese zones.
			'BAG' => _x( 'Bagmati', 'NP state of Bagmati', 'everest-forms' ),
			'BHE' => _x( 'Bheri', 'NP state of Bheri', 'everest-forms' ),
			'DHA' => _x( 'Dhaulagiri', 'NP state of Dhaulagiri', 'everest-forms' ),
			'GAN' => _x( 'Gandaki', 'NP state of Gandaki', 'everest-forms' ),
			'JAN' => _x( 'Janakpur', 'NP state of Janakpur', 'everest-forms' ),
			'KAR' => _x( 'Karnali', 'NP state of Karnali', 'everest-forms' ),
			'KOS' => _x( 'Koshi', 'NP state of Koshi', 'everest-forms' ),
			'LUM' => _x( 'Lumbini', 'NP state of Lumbini', 'everest-forms' ),
			'MAH' => _x( 'Mahakali', 'NP state of Mahakali', 'everest-forms' ),
			'MEC' => _x( 'Mechi', 'NP state of Mechi', 'everest-forms' ),
			'NAR' => _x( 'Narayani', 'NP state of Narayani', 'everest-forms' ),
			'RAP' => _x( 'Rapti', 'NP state of Rapti', 'everest-forms' ),
			'SAG' => _x( 'Sagarmatha', 'NP state of Sagarmatha', 'everest-forms' ),
			'SET' => _x( 'Seti', 'NP state of Seti', 'everest-forms' ),
		),
		'NI' => array( // Nicaraguan states.
			'NI-AN' => _x( 'Atlántico Norte', 'NI state of Atlántico Norte', 'everest-forms' ),
			'NI-AS' => _x( 'Atlántico Sur', 'NI state of Atlántico Sur', 'everest-forms' ),
			'NI-BO' => _x( 'Boaco', 'NI state of Boaco', 'everest-forms' ),
			'NI-CA' => _x( 'Carazo', 'NI state of Carazo', 'everest-forms' ),
			'NI-CI' => _x( 'Chinandega', 'NI state of Chinandega', 'everest-forms' ),
			'NI-CO' => _x( 'Chontales', 'NI state of Chontales', 'everest-forms' ),
			'NI-ES' => _x( 'Estelí', 'NI state of Estelí', 'everest-forms' ),
			'NI-GR' => _x( 'Granada', 'NI state of Granada', 'everest-forms' ),
			'NI-JI' => _x( 'Jinotega', 'NI state of Jinotega', 'everest-forms' ),
			'NI-LE' => _x( 'León', 'NI state of León', 'everest-forms' ),
			'NI-MD' => _x( 'Madriz', 'NI state of Madriz', 'everest-forms' ),
			'NI-MN' => _x( 'Managua', 'NI state of Managua', 'everest-forms' ),
			'NI-MS' => _x( 'Masaya', 'NI state of Masaya', 'everest-forms' ),
			'NI-MT' => _x( 'Matagalpa', 'NI state of Matagalpa', 'everest-forms' ),
			'NI-NS' => _x( 'Nueva Segovia', 'NI state of Nueva Segovia', 'everest-forms' ),
			'NI-RI' => _x( 'Rivas', 'NI state of Rivas', 'everest-forms' ),
			'NI-SJ' => _x( 'Río San Juan', 'NI state of Río San Juan', 'everest-forms' ),
		),
		'NZ' => array( // New Zealand states.
			'NL' => _x( 'Northland', 'NZ state of Northland', 'everest-forms' ),
			'AK' => _x( 'Auckland', 'NZ state of Auckland', 'everest-forms' ),
			'WA' => _x( 'Waikato', 'NZ state of Waikato', 'everest-forms' ),
			'BP' => _x( 'Bay of Plenty', 'NZ state of Bay of Plenty', 'everest-forms' ),
			'TK' => _x( 'Taranaki', 'NZ state of Taranaki', 'everest-forms' ),
			'GI' => _x( 'Gisborne', 'NZ state of Gisborne', 'everest-forms' ),
			'HB' => _x( 'Hawke’s Bay', 'NZ state of Hawke’s Bay', 'everest-forms' ),
			'MW' => _x( 'Manawatu-Wanganui', 'NZ state of Manawatu-Wanganui', 'everest-forms' ),
			'WE' => _x( 'Wellington', 'NZ state of Wellington', 'everest-forms' ),
			'NS' => _x( 'Nelson', 'NZ state of Nelson', 'everest-forms' ),
			'MB' => _x( 'Marlborough', 'NZ state of Marlborough', 'everest-forms' ),
			'TM' => _x( 'Tasman', 'NZ state of Tasman', 'everest-forms' ),
			'WC' => _x( 'West Coast', 'NZ state of West Coast', 'everest-forms' ),
			'CT' => _x( 'Canterbury', 'NZ state of Canterbury', 'everest-forms' ),
			'OT' => _x( 'Otago', 'NZ state of Otago', 'everest-forms' ),
			'SL' => _x( 'Southland', 'NZ state of Southland', 'everest-forms' ),
		),
		'PA' => array( // Panamanian states.
			'PA-1'  => _x( 'Bocas del Toro', 'PA state of Bocas del Toro', 'everest-forms' ),
			'PA-2'  => _x( 'Coclé', 'PA state of Coclé', 'everest-forms' ),
			'PA-3'  => _x( 'Colón', 'PA state of Colón', 'everest-forms' ),
			'PA-4'  => _x( 'Chiriquí', 'PA state of Chiriquí', 'everest-forms' ),
			'PA-5'  => _x( 'Darién', 'PA state of Darién', 'everest-forms' ),
			'PA-6'  => _x( 'Herrera', 'PA state of Herrera', 'everest-forms' ),
			'PA-7'  => _x( 'Los Santos', 'PA state of Los Santos', 'everest-forms' ),
			'PA-8'  => _x( 'Panamá', 'PA state of Panamá', 'everest-forms' ),
			'PA-9'  => _x( 'Veraguas', 'PA state of Veraguas', 'everest-forms' ),
			'PA-10' => _x( 'West Panamá', 'PA state of West Panamá', 'everest-forms' ),
			'PA-EM' => _x( 'Emberá', 'PA state of Emberá', 'everest-forms' ),
			'PA-KY' => _x( 'Guna Yala', 'PA state of Guna Yala', 'everest-forms' ),
			'PA-NB' => _x( 'Ngöbe-Buglé', 'PA state of Ngöbe-Buglé', 'everest-forms' ),
		),
		'PE' => array( // Peruvian states.
			'CAL' => _x( 'El Callao', 'PE state of El Callao', 'everest-forms' ),
			'LMA' => _x( 'Municipalidad Metropolitana de Lima', 'PE state of Municipalidad Metropolitana de Lima', 'everest-forms' ),
			'AMA' => _x( 'Amazonas', 'PE state of Amazonas', 'everest-forms' ),
			'ANC' => _x( 'Ancash', 'PE state of Ancash', 'everest-forms' ),
			'APU' => _x( 'Apurímac', 'PE state of Apurímac', 'everest-forms' ),
			'ARE' => _x( 'Arequipa', 'PE state of Arequipa', 'everest-forms' ),
			'AYA' => _x( 'Ayacucho', 'PE state of Ayacucho', 'everest-forms' ),
			'CAJ' => _x( 'Cajamarca', 'PE state of Cajamarca', 'everest-forms' ),
			'CUS' => _x( 'Cusco', 'PE state of Cusco', 'everest-forms' ),
			'HUV' => _x( 'Huancavelica', 'PE state of Huancavelica', 'everest-forms' ),
			'HUC' => _x( 'Huánuco', 'PE state of Huánuco', 'everest-forms' ),
			'ICA' => _x( 'Ica', 'PE state of Ica', 'everest-forms' ),
			'JUN' => _x( 'Junín', 'PE state of Junín', 'everest-forms' ),
			'LAL' => _x( 'La Libertad', 'PE state of La Libertad', 'everest-forms' ),
			'LAM' => _x( 'Lambayeque', 'PE state of Lambayeque', 'everest-forms' ),
			'LIM' => _x( 'Lima', 'PE state of Lima', 'everest-forms' ),
			'LOR' => _x( 'Loreto', 'PE state of Loreto', 'everest-forms' ),
			'MDD' => _x( 'Madre de Dios', 'PE state of Madre de Dios', 'everest-forms' ),
			'MOQ' => _x( 'Moquegua', 'PE state of Moquegua', 'everest-forms' ),
			'PAS' => _x( 'Pasco', 'PE state of Pasco', 'everest-forms' ),
			'PIU' => _x( 'Piura', 'PE state of Piura', 'everest-forms' ),
			'PUN' => _x( 'Puno', 'PE state of Puno', 'everest-forms' ),
			'SAM' => _x( 'San Martín', 'PE state of San Martín', 'everest-forms' ),
			'TAC' => _x( 'Tacna', 'PE state of Tacna', 'everest-forms' ),
			'TUM' => _x( 'Tumbes', 'PE state of Tumbes', 'everest-forms' ),
			'UCA' => _x( 'Ucayali', 'PE state of Ucayali', 'everest-forms' ),
		),
		'PH' => array( // Philippine provinces.
			'ABR' => _x( 'Abra', 'PH state of Abra', 'everest-forms' ),
			'AGN' => _x( 'Agusan del Norte', 'PH state of Agusan del Norte', 'everest-forms' ),
			'AGS' => _x( 'Agusan del Sur', 'PH state of Agusan del Sur', 'everest-forms' ),
			'AKL' => _x( 'Aklan', 'PH state of Aklan', 'everest-forms' ),
			'ALB' => _x( 'Albay', 'PH state of Albay', 'everest-forms' ),
			'ANT' => _x( 'Antique', 'PH state of Antique', 'everest-forms' ),
			'APA' => _x( 'Apayao', 'PH state of Apayao', 'everest-forms' ),
			'AUR' => _x( 'Aurora', 'PH state of Aurora', 'everest-forms' ),
			'BAS' => _x( 'Basilan', 'PH state of Basilan', 'everest-forms' ),
			'BAN' => _x( 'Bataan', 'PH state of Bataan', 'everest-forms' ),
			'BTN' => _x( 'Batanes', 'PH state of Batanes', 'everest-forms' ),
			'BTG' => _x( 'Batangas', 'PH state of Batangas', 'everest-forms' ),
			'BEN' => _x( 'Benguet', 'PH state of Benguet', 'everest-forms' ),
			'BIL' => _x( 'Biliran', 'PH state of Biliran', 'everest-forms' ),
			'BOH' => _x( 'Bohol', 'PH state of Bohol', 'everest-forms' ),
			'BUK' => _x( 'Bukidnon', 'PH state of Bukidnon', 'everest-forms' ),
			'BUL' => _x( 'Bulacan', 'PH state of Bulacan', 'everest-forms' ),
			'CAG' => _x( 'Cagayan', 'PH state of Cagayan', 'everest-forms' ),
			'CAN' => _x( 'Camarines Norte', 'PH state of Camarines Norte', 'everest-forms' ),
			'CAS' => _x( 'Camarines Sur', 'PH state of Camarines Sur', 'everest-forms' ),
			'CAM' => _x( 'Camiguin', 'PH state of Camiguin', 'everest-forms' ),
			'CAP' => _x( 'Capiz', 'PH state of Capiz', 'everest-forms' ),
			'CAT' => _x( 'Catanduanes', 'PH state of Catanduanes', 'everest-forms' ),
			'CAV' => _x( 'Cavite', 'PH state of Cavite', 'everest-forms' ),
			'CEB' => _x( 'Cebu', 'PH state of Cebu', 'everest-forms' ),
			'COM' => _x( 'Compostela Valley', 'PH state of Compostela Valley', 'everest-forms' ),
			'NCO' => _x( 'Cotabato', 'PH state of Cotabato', 'everest-forms' ),
			'DAV' => _x( 'Davao del Norte', 'PH state of Davao del Norte', 'everest-forms' ),
			'DAS' => _x( 'Davao del Sur', 'PH state of Davao del Sur', 'everest-forms' ),
			'DAC' => _x( 'Davao Occidental', 'PH state of Davao Occidental', 'everest-forms' ),
			'DAO' => _x( 'Davao Oriental', 'PH state of Davao Oriental', 'everest-forms' ),
			'DIN' => _x( 'Dinagat Islands', 'PH state of Dinagat Islands', 'everest-forms' ),
			'EAS' => _x( 'Eastern Samar', 'PH state of Eastern Samar', 'everest-forms' ),
			'GUI' => _x( 'Guimaras', 'PH state of Guimaras', 'everest-forms' ),
			'IFU' => _x( 'Ifugao', 'PH state of Ifugao', 'everest-forms' ),
			'ILN' => _x( 'Ilocos Norte', 'PH state of Ilocos Norte', 'everest-forms' ),
			'ILS' => _x( 'Ilocos Sur', 'PH state of Ilocos Sur', 'everest-forms' ),
			'ILI' => _x( 'Iloilo', 'PH state of Iloilo', 'everest-forms' ),
			'ISA' => _x( 'Isabela', 'PH state of Isabela', 'everest-forms' ),
			'KAL' => _x( 'Kalinga', 'PH state of Kalinga', 'everest-forms' ),
			'LUN' => _x( 'La Union', 'PH state of La Union', 'everest-forms' ),
			'LAG' => _x( 'Laguna', 'PH state of Laguna', 'everest-forms' ),
			'LAN' => _x( 'Lanao del Norte', 'PH state of Lanao del Norte', 'everest-forms' ),
			'LAS' => _x( 'Lanao del Sur', 'PH state of Lanao del Sur', 'everest-forms' ),
			'LEY' => _x( 'Leyte', 'PH state of Leyte', 'everest-forms' ),
			'MAG' => _x( 'Maguindanao', 'PH state of Maguindanao', 'everest-forms' ),
			'MAD' => _x( 'Marinduque', 'PH state of Marinduque', 'everest-forms' ),
			'MAS' => _x( 'Masbate', 'PH state of Masbate', 'everest-forms' ),
			'MSC' => _x( 'Misamis Occidental', 'PH state of Misamis Occidental', 'everest-forms' ),
			'MSR' => _x( 'Misamis Oriental', 'PH state of Misamis Oriental', 'everest-forms' ),
			'MOU' => _x( 'Mountain Province', 'PH state of Mountain Province', 'everest-forms' ),
			'NEC' => _x( 'Negros Occidental', 'PH state of Negros Occidental', 'everest-forms' ),
			'NER' => _x( 'Negros Oriental', 'PH state of Negros Oriental', 'everest-forms' ),
			'NSA' => _x( 'Northern Samar', 'PH state of Northern Samar', 'everest-forms' ),
			'NUE' => _x( 'Nueva Ecija', 'PH state of Nueva Ecija', 'everest-forms' ),
			'NUV' => _x( 'Nueva Vizcaya', 'PH state of Nueva Vizcaya', 'everest-forms' ),
			'MDC' => _x( 'Occidental Mindoro', 'PH state of Occidental Mindoro', 'everest-forms' ),
			'MDR' => _x( 'Oriental Mindoro', 'PH state of Oriental Mindoro', 'everest-forms' ),
			'PLW' => _x( 'Palawan', 'PH state of Palawan', 'everest-forms' ),
			'PAM' => _x( 'Pampanga', 'PH state of Pampanga', 'everest-forms' ),
			'PAN' => _x( 'Pangasinan', 'PH state of Pangasinan', 'everest-forms' ),
			'QUE' => _x( 'Quezon', 'PH state of Quezon', 'everest-forms' ),
			'QUI' => _x( 'Quirino', 'PH state of Quirino', 'everest-forms' ),
			'RIZ' => _x( 'Rizal', 'PH state of Rizal', 'everest-forms' ),
			'ROM' => _x( 'Romblon', 'PH state of Romblon', 'everest-forms' ),
			'WSA' => _x( 'Samar', 'PH state of Samar', 'everest-forms' ),
			'SAR' => _x( 'Sarangani', 'PH state of Sarangani', 'everest-forms' ),
			'SIQ' => _x( 'Siquijor', 'PH state of Siquijor', 'everest-forms' ),
			'SOR' => _x( 'Sorsogon', 'PH state of Sorsogon', 'everest-forms' ),
			'SCO' => _x( 'South Cotabato', 'PH state of South Cotabato', 'everest-forms' ),
			'SLE' => _x( 'Southern Leyte', 'PH state of Southern Leyte', 'everest-forms' ),
			'SUK' => _x( 'Sultan Kudarat', 'PH state of Sultan Kudarat', 'everest-forms' ),
			'SLU' => _x( 'Sulu', 'PH state of Sulu', 'everest-forms' ),
			'SUN' => _x( 'Surigao del Norte', 'PH state of Surigao del Norte', 'everest-forms' ),
			'SUR' => _x( 'Surigao del Sur', 'PH state of Surigao del Sur', 'everest-forms' ),
			'TAR' => _x( 'Tarlac', 'PH state of Tarlac', 'everest-forms' ),
			'TAW' => _x( 'Tawi-Tawi', 'PH state of Tawi-Tawi', 'everest-forms' ),
			'ZMB' => _x( 'Zambales', 'PH state of Zambales', 'everest-forms' ),
			'ZAN' => _x( 'Zamboanga del Norte', 'PH state of Zamboanga del Norte', 'everest-forms' ),
			'ZAS' => _x( 'Zamboanga del Sur', 'PH state of Zamboanga del Sur', 'everest-forms' ),
			'ZSI' => _x( 'Zamboanga Sibugay', 'PH state of Zamboanga Sibugay', 'everest-forms' ),
			'00'  => _x( 'Metro Manila', 'PH state of Metro Manila', 'everest-forms' ),
		),
		'PK' => array( // Pakistani states.
			'JK' => _x( 'Azad Kashmir', 'PK state of Azad Kashmir', 'everest-forms' ),
			'BA' => _x( 'Balochistan', 'PK state of Balochistan', 'everest-forms' ),
			'TA' => _x( 'FATA', 'PK state of FATA', 'everest-forms' ),
			'GB' => _x( 'Gilgit Baltistan', 'PK state of Gilgit Baltistan', 'everest-forms' ),
			'IS' => _x( 'Islamabad Capital Territory', 'PK state of Islamabad Capital Territory', 'everest-forms' ),
			'KP' => _x( 'Khyber Pakhtunkhwa', 'PK state of Khyber Pakhtunkhwa', 'everest-forms' ),
			'PB' => _x( 'Punjab', 'PK state of Punjab', 'everest-forms' ),
			'SD' => _x( 'Sindh', 'PK state of Sindh', 'everest-forms' ),
		),
		'PL' => array(),
		'PR' => array(),
		'PT' => array(),
		'PY' => array( // Paraguayan states.
			'PY-ASU' => _x( 'Asunción', 'PY state of Asunción', 'everest-forms' ),
			'PY-1'   => _x( 'Concepción', 'PY state of Concepción', 'everest-forms' ),
			'PY-2'   => _x( 'San Pedro', 'PY state of San Pedro', 'everest-forms' ),
			'PY-3'   => _x( 'Cordillera', 'PY state of Cordillera', 'everest-forms' ),
			'PY-4'   => _x( 'Guairá', 'PY state of Guairá', 'everest-forms' ),
			'PY-5'   => _x( 'Caaguazú', 'PY state of Caaguazú', 'everest-forms' ),
			'PY-6'   => _x( 'Caazapá', 'PY state of Caazapá', 'everest-forms' ),
			'PY-7'   => _x( 'Itapúa', 'PY state of Itapúa', 'everest-forms' ),
			'PY-8'   => _x( 'Misiones', 'PY state of Misiones', 'everest-forms' ),
			'PY-9'   => _x( 'Paraguarí', 'PY state of Paraguarí', 'everest-forms' ),
			'PY-10'  => _x( 'Alto Paraná', 'PY state of Alto Paraná', 'everest-forms' ),
			'PY-11'  => _x( 'Central', 'PY state of Central', 'everest-forms' ),
			'PY-12'  => _x( 'Ñeembucú', 'PY state of Ñeembucú', 'everest-forms' ),
			'PY-13'  => _x( 'Amambay', 'PY state of Amambay', 'everest-forms' ),
			'PY-14'  => _x( 'Canindeyú', 'PY state of Canindeyú', 'everest-forms' ),
			'PY-15'  => _x( 'Presidente Hayes', 'PY state of Presidente Hayes', 'everest-forms' ),
			'PY-16'  => _x( 'Alto Paraguay', 'PY state of Alto Paraguay', 'everest-forms' ),
			'PY-17'  => _x( 'Boquerón', 'PY state of Boquerón', 'everest-forms' ),
		),
		'RE' => array(),
		'RO' => array( // Romanian states.
			'AB' => _x( 'Alba', 'RO state of Alba', 'everest-forms' ),
			'AR' => _x( 'Arad', 'RO state of Arad', 'everest-forms' ),
			'AG' => _x( 'Argeș', 'RO state of Argeș', 'everest-forms' ),
			'BC' => _x( 'Bacău', 'RO state of Bacău', 'everest-forms' ),
			'BH' => _x( 'Bihor', 'RO state of Bihor', 'everest-forms' ),
			'BN' => _x( 'Bistrița-Năsăud', 'RO state of Bistrița-Năsăud', 'everest-forms' ),
			'BT' => _x( 'Botoșani', 'RO state of Botoșani', 'everest-forms' ),
			'BR' => _x( 'Brăila', 'RO state of Brăila', 'everest-forms' ),
			'BV' => _x( 'Brașov', 'RO state of Brașov', 'everest-forms' ),
			'B'  => _x( 'București', 'RO state of București', 'everest-forms' ),
			'BZ' => _x( 'Buzău', 'RO state of Buzău', 'everest-forms' ),
			'CL' => _x( 'Călărași', 'RO state of Călărași', 'everest-forms' ),
			'CS' => _x( 'Caraș-Severin', 'RO state of Caraș-Severin', 'everest-forms' ),
			'CJ' => _x( 'Cluj', 'RO state of Cluj', 'everest-forms' ),
			'CT' => _x( 'Constanța', 'RO state of Constanța', 'everest-forms' ),
			'CV' => _x( 'Covasna', 'RO state of Covasna', 'everest-forms' ),
			'DB' => _x( 'Dâmbovița', 'RO state of Dâmbovița', 'everest-forms' ),
			'DJ' => _x( 'Dolj', 'RO state of Dolj', 'everest-forms' ),
			'GL' => _x( 'Galați', 'RO state of Galați', 'everest-forms' ),
			'GR' => _x( 'Giurgiu', 'RO state of Giurgiu', 'everest-forms' ),
			'GJ' => _x( 'Gorj', 'RO state of Gorj', 'everest-forms' ),
			'HR' => _x( 'Harghita', 'RO state of Harghita', 'everest-forms' ),
			'HD' => _x( 'Hunedoara', 'RO state of Hunedoara', 'everest-forms' ),
			'IL' => _x( 'Ialomița', 'RO state of Ialomița', 'everest-forms' ),
			'IS' => _x( 'Iași', 'RO state of Iași', 'everest-forms' ),
			'IF' => _x( 'Ilfov', 'RO state of Ilfov', 'everest-forms' ),
			'MM' => _x( 'Maramureș', 'RO state of Maramureș', 'everest-forms' ),
			'MH' => _x( 'Mehedinți', 'RO state of Mehedinți', 'everest-forms' ),
			'MS' => _x( 'Mureș', 'RO state of Mureș', 'everest-forms' ),
			'NT' => _x( 'Neamț', 'RO state of Neamț', 'everest-forms' ),
			'OT' => _x( 'Olt', 'RO state of Olt', 'everest-forms' ),
			'PH' => _x( 'Prahova', 'RO state of Prahova', 'everest-forms' ),
			'SJ' => _x( 'Sălaj', 'RO state of Sălaj', 'everest-forms' ),
			'SM' => _x( 'Satu Mare', 'RO state of Satu Mare', 'everest-forms' ),
			'SB' => _x( 'Sibiu', 'RO state of Sibiu', 'everest-forms' ),
			'SV' => _x( 'Suceava', 'RO state of Suceava', 'everest-forms' ),
			'TR' => _x( 'Teleorman', 'RO state of Teleorman', 'everest-forms' ),
			'TM' => _x( 'Timiș', 'RO state of Timiș', 'everest-forms' ),
			'TL' => _x( 'Tulcea', 'RO state of Tulcea', 'everest-forms' ),
			'VL' => _x( 'Vâlcea', 'RO state of Vâlcea', 'everest-forms' ),
			'VS' => _x( 'Vaslui', 'RO state of Vaslui', 'everest-forms' ),
			'VN' => _x( 'Vrancea', 'RO state of Vrancea', 'everest-forms' ),
		),
		'SG' => array(),
		'SK' => array(),
		'SI' => array(),
		'SV' => array( // Salvadoran states.
			'SV-AH' => _x( 'Ahuachapán', 'SV state of Ahuachapán', 'everest-forms' ),
			'SV-CA' => _x( 'Cabañas', 'SV state of Cabañas', 'everest-forms' ),
			'SV-CH' => _x( 'Chalatenango', 'SV state of Chalatenango', 'everest-forms' ),
			'SV-CU' => _x( 'Cuscatlán', 'SV state of Cuscatlán', 'everest-forms' ),
			'SV-LI' => _x( 'La Libertad', 'SV state of La Libertad', 'everest-forms' ),
			'SV-MO' => _x( 'Morazán', 'SV state of Morazán', 'everest-forms' ),
			'SV-PA' => _x( 'La Paz', 'SV state of La Paz', 'everest-forms' ),
			'SV-SA' => _x( 'Santa Ana', 'SV state of Santa Ana', 'everest-forms' ),
			'SV-SM' => _x( 'San Miguel', 'SV state of San Miguel', 'everest-forms' ),
			'SV-SO' => _x( 'Sonsonate', 'SV state of Sonsonate', 'everest-forms' ),
			'SV-SS' => _x( 'San Salvador', 'SV state of San Salvador', 'everest-forms' ),
			'SV-SV' => _x( 'San Vicente', 'SV state of San Vicente', 'everest-forms' ),
			'SV-UN' => _x( 'La Unión', 'SV state of La Unión', 'everest-forms' ),
			'SV-US' => _x( 'Usulután', 'SV state of Usulután', 'everest-forms' ),
		),
		'TH' => array( // Thai states.
			'TH-37' => _x( 'Amnat Charoen', 'TH state of Amnat Charoen', 'everest-forms' ),
			'TH-15' => _x( 'Ang Thong', 'TH state of Ang Thong', 'everest-forms' ),
			'TH-14' => _x( 'Ayutthaya', 'TH state of Ayutthaya', 'everest-forms' ),
			'TH-10' => _x( 'Bangkok', 'TH state of Bangkok', 'everest-forms' ),
			'TH-38' => _x( 'Bueng Kan', 'TH state of Bueng Kan', 'everest-forms' ),
			'TH-31' => _x( 'Buri Ram', 'TH state of Buri Ram', 'everest-forms' ),
			'TH-24' => _x( 'Chachoengsao', 'TH state of Chachoengsao', 'everest-forms' ),
			'TH-18' => _x( 'Chai Nat', 'TH state of Chai Nat', 'everest-forms' ),
			'TH-36' => _x( 'Chaiyaphum', 'TH state of Chaiyaphum', 'everest-forms' ),
			'TH-22' => _x( 'Chanthaburi', 'TH state of Chanthaburi', 'everest-forms' ),
			'TH-50' => _x( 'Chiang Mai', 'TH state of Chiang Mai', 'everest-forms' ),
			'TH-57' => _x( 'Chiang Rai', 'TH state of Chiang Rai', 'everest-forms' ),
			'TH-20' => _x( 'Chonburi', 'TH state of Chonburi', 'everest-forms' ),
			'TH-86' => _x( 'Chumphon', 'TH state of Chumphon', 'everest-forms' ),
			'TH-46' => _x( 'Kalasin', 'TH state of Kalasin', 'everest-forms' ),
			'TH-62' => _x( 'Kamphaeng Phet', 'TH state of Kamphaeng Phet', 'everest-forms' ),
			'TH-71' => _x( 'Kanchanaburi', 'TH state of Kanchanaburi', 'everest-forms' ),
			'TH-40' => _x( 'Khon Kaen', 'TH state of Khon Kaen', 'everest-forms' ),
			'TH-81' => _x( 'Krabi', 'TH state of Krabi', 'everest-forms' ),
			'TH-52' => _x( 'Lampang', 'TH state of Lampang', 'everest-forms' ),
			'TH-51' => _x( 'Lamphun', 'TH state of Lamphun', 'everest-forms' ),
			'TH-42' => _x( 'Loei', 'TH state of Loei', 'everest-forms' ),
			'TH-16' => _x( 'Lopburi', 'TH state of Lopburi', 'everest-forms' ),
			'TH-58' => _x( 'Mae Hong Son', 'TH state of Mae Hong Son', 'everest-forms' ),
			'TH-44' => _x( 'Maha Sarakham', 'TH state of Maha Sarakham', 'everest-forms' ),
			'TH-49' => _x( 'Mukdahan', 'TH state of Mukdahan', 'everest-forms' ),
			'TH-26' => _x( 'Nakhon Nayok', 'TH state of Nakhon Nayok', 'everest-forms' ),
			'TH-73' => _x( 'Nakhon Pathom', 'TH state of Nakhon Pathom', 'everest-forms' ),
			'TH-48' => _x( 'Nakhon Phanom', 'TH state of Nakhon Phanom', 'everest-forms' ),
			'TH-30' => _x( 'Nakhon Ratchasima', 'TH state of Nakhon Ratchasima', 'everest-forms' ),
			'TH-60' => _x( 'Nakhon Sawan', 'TH state of Nakhon Sawan', 'everest-forms' ),
			'TH-80' => _x( 'Nakhon Si Thammarat', 'TH state of Nakhon Si Thammarat', 'everest-forms' ),
			'TH-55' => _x( 'Nan', 'TH state of Nan', 'everest-forms' ),
			'TH-96' => _x( 'Narathiwat', 'TH state of Narathiwat', 'everest-forms' ),
			'TH-39' => _x( 'Nong Bua Lam Phu', 'TH state of Nong Bua Lam Phu', 'everest-forms' ),
			'TH-43' => _x( 'Nong Khai', 'TH state of Nong Khai', 'everest-forms' ),
			'TH-12' => _x( 'Nonthaburi', 'TH state of Nonthaburi', 'everest-forms' ),
			'TH-13' => _x( 'Pathum Thani', 'TH state of Pathum Thani', 'everest-forms' ),
			'TH-94' => _x( 'Pattani', 'TH state of Pattani', 'everest-forms' ),
			'TH-82' => _x( 'Phang Nga', 'TH state of Phang Nga', 'everest-forms' ),
			'TH-93' => _x( 'Phatthalung', 'TH state of Phatthalung', 'everest-forms' ),
			'TH-56' => _x( 'Phayao', 'TH state of Phayao', 'everest-forms' ),
			'TH-67' => _x( 'Phetchabun', 'TH state of Phetchabun', 'everest-forms' ),
			'TH-76' => _x( 'Phetchaburi', 'TH state of Phetchaburi', 'everest-forms' ),
			'TH-66' => _x( 'Phichit', 'TH state of Phichit', 'everest-forms' ),
			'TH-65' => _x( 'Phitsanulok', 'TH state of Phitsanulok', 'everest-forms' ),
			'TH-54' => _x( 'Phrae', 'TH state of Phrae', 'everest-forms' ),
			'TH-83' => _x( 'Phuket', 'TH state of Phuket', 'everest-forms' ),
			'TH-25' => _x( 'Prachin Buri', 'TH state of Prachin Buri', 'everest-forms' ),
			'TH-77' => _x( 'Prachuap Khiri Khan', 'TH state of Prachuap Khiri Khan', 'everest-forms' ),
			'TH-85' => _x( 'Ranong', 'TH state of Ranong', 'everest-forms' ),
			'TH-70' => _x( 'Ratchaburi', 'TH state of Ratchaburi', 'everest-forms' ),
			'TH-21' => _x( 'Rayong', 'TH state of Rayong', 'everest-forms' ),
			'TH-45' => _x( 'Roi Et', 'TH state of Roi Et', 'everest-forms' ),
			'TH-27' => _x( 'Sa Kaeo', 'TH state of Sa Kaeo', 'everest-forms' ),
			'TH-47' => _x( 'Sakon Nakhon', 'TH state of Sakon Nakhon', 'everest-forms' ),
			'TH-11' => _x( 'Samut Prakan', 'TH state of Samut Prakan', 'everest-forms' ),
			'TH-74' => _x( 'Samut Sakhon', 'TH state of Samut Sakhon', 'everest-forms' ),
			'TH-75' => _x( 'Samut Songkhram', 'TH state of Samut Songkhram', 'everest-forms' ),
			'TH-19' => _x( 'Saraburi', 'TH state of Saraburi', 'everest-forms' ),
			'TH-91' => _x( 'Satun', 'TH state of Satun', 'everest-forms' ),
			'TH-17' => _x( 'Sing Buri', 'TH state of Sing Buri', 'everest-forms' ),
			'TH-33' => _x( 'Sisaket', 'TH state of Sisaket', 'everest-forms' ),
			'TH-90' => _x( 'Songkhla', 'TH state of Songkhla', 'everest-forms' ),
			'TH-64' => _x( 'Sukhothai', 'TH state of Sukhothai', 'everest-forms' ),
			'TH-72' => _x( 'Suphan Buri', 'TH state of Suphan Buri', 'everest-forms' ),
			'TH-84' => _x( 'Surat Thani', 'TH state of Surat Thani', 'everest-forms' ),
			'TH-32' => _x( 'Surin', 'TH state of Surin', 'everest-forms' ),
			'TH-63' => _x( 'Tak', 'TH state of Tak', 'everest-forms' ),
			'TH-92' => _x( 'Trang', 'TH state of Trang', 'everest-forms' ),
			'TH-23' => _x( 'Trat', 'TH state of Trat', 'everest-forms' ),
			'TH-34' => _x( 'Ubon Ratchathani', 'TH state of Ubon Ratchathani', 'everest-forms' ),
			'TH-41' => _x( 'Udon Thani', 'TH state of Udon Thani', 'everest-forms' ),
			'TH-61' => _x( 'Uthai Thani', 'TH state of Uthai Thani', 'everest-forms' ),
			'TH-53' => _x( 'Uttaradit', 'TH state of Uttaradit', 'everest-forms' ),
			'TH-95' => _x( 'Yala', 'TH state of Yala', 'everest-forms' ),
			'TH-35' => _x( 'Yasothon', 'TH state of Yasothon', 'everest-forms' ),
		),
		'TR' => array( // Turkish states.
			'TR01' => _x( 'Adana', 'TR state of Adana', 'everest-forms' ),
			'TR02' => _x( 'Adıyaman', 'TR state of Adıyaman', 'everest-forms' ),
			'TR03' => _x( 'Afyon', 'TR state of Afyon', 'everest-forms' ),
			'TR04' => _x( 'Ağrı', 'TR state of Ağrı', 'everest-forms' ),
			'TR05' => _x( 'Amasya', 'TR state of Amasya', 'everest-forms' ),
			'TR06' => _x( 'Ankara', 'TR state of Ankara', 'everest-forms' ),
			'TR07' => _x( 'Antalya', 'TR state of Antalya', 'everest-forms' ),
			'TR08' => _x( 'Artvin', 'TR state of Artvin', 'everest-forms' ),
			'TR09' => _x( 'Aydın', 'TR state of Aydın', 'everest-forms' ),
			'TR10' => _x( 'Balıkesir', 'TR state of Balıkesir', 'everest-forms' ),
			'TR11' => _x( 'Bilecik', 'TR state of Bilecik', 'everest-forms' ),
			'TR12' => _x( 'Bingöl', 'TR state of Bingöl', 'everest-forms' ),
			'TR13' => _x( 'Bitlis', 'TR state of Bitlis', 'everest-forms' ),
			'TR14' => _x( 'Bolu', 'TR state of Bolu', 'everest-forms' ),
			'TR15' => _x( 'Burdur', 'TR state of Burdur', 'everest-forms' ),
			'TR16' => _x( 'Bursa', 'TR state of Bursa', 'everest-forms' ),
			'TR17' => _x( 'Çanakkale', 'TR state of Çanakkale', 'everest-forms' ),
			'TR18' => _x( 'Çankırı', 'TR state of Çankırı', 'everest-forms' ),
			'TR19' => _x( 'Çorum', 'TR state of Çorum', 'everest-forms' ),
			'TR20' => _x( 'Denizli', 'TR state of Denizli', 'everest-forms' ),
			'TR21' => _x( 'Diyarbakır', 'TR state of Diyarbakır', 'everest-forms' ),
			'TR22' => _x( 'Edirne', 'TR state of Edirne', 'everest-forms' ),
			'TR23' => _x( 'Elazığ', 'TR state of Elazığ', 'everest-forms' ),
			'TR24' => _x( 'Erzincan', 'TR state of Erzincan', 'everest-forms' ),
			'TR25' => _x( 'Erzurum', 'TR state of Erzurum', 'everest-forms' ),
			'TR26' => _x( 'Eskişehir', 'TR state of Eskişehir', 'everest-forms' ),
			'TR27' => _x( 'Gaziantep', 'TR state of Gaziantep', 'everest-forms' ),
			'TR28' => _x( 'Giresun', 'TR state of Giresun', 'everest-forms' ),
			'TR29' => _x( 'Gümüşhane', 'TR state of Gümüşhane', 'everest-forms' ),
			'TR30' => _x( 'Hakkari', 'TR state of Hakkari', 'everest-forms' ),
			'TR31' => _x( 'Hatay', 'TR state of Hatay', 'everest-forms' ),
			'TR32' => _x( 'Isparta', 'TR state of Isparta', 'everest-forms' ),
			'TR33' => _x( 'İçel', 'TR state of İçel', 'everest-forms' ),
			'TR34' => _x( 'İstanbul', 'TR state of İstanbul', 'everest-forms' ),
			'TR35' => _x( 'İzmir', 'TR state of İzmir', 'everest-forms' ),
			'TR36' => _x( 'Kars', 'TR state of Kars', 'everest-forms' ),
			'TR37' => _x( 'Kastamonu', 'TR state of Kastamonu', 'everest-forms' ),
			'TR38' => _x( 'Kayseri', 'TR state of Kayseri', 'everest-forms' ),
			'TR39' => _x( 'Kırklareli', 'TR state of Kırklareli', 'everest-forms' ),
			'TR40' => _x( 'Kırşehir', 'TR state of Kırşehir', 'everest-forms' ),
			'TR41' => _x( 'Kocaeli', 'TR state of Kocaeli', 'everest-forms' ),
			'TR42' => _x( 'Konya', 'TR state of Konya', 'everest-forms' ),
			'TR43' => _x( 'Kütahya', 'TR state of Kütahya', 'everest-forms' ),
			'TR44' => _x( 'Malatya', 'TR state of Malatya', 'everest-forms' ),
			'TR45' => _x( 'Manisa', 'TR state of Manisa', 'everest-forms' ),
			'TR46' => _x( 'Kahramanmaraş', 'TR state of Kahramanmaraş', 'everest-forms' ),
			'TR47' => _x( 'Mardin', 'TR state of Mardin', 'everest-forms' ),
			'TR48' => _x( 'Muğla', 'TR state of Muğla', 'everest-forms' ),
			'TR49' => _x( 'Muş', 'TR state of Muş', 'everest-forms' ),
			'TR50' => _x( 'Nevşehir', 'TR state of Nevşehir', 'everest-forms' ),
			'TR51' => _x( 'Niğde', 'TR state of Niğde', 'everest-forms' ),
			'TR52' => _x( 'Ordu', 'TR state of Ordu', 'everest-forms' ),
			'TR53' => _x( 'Rize', 'TR state of Rize', 'everest-forms' ),
			'TR54' => _x( 'Sakarya', 'TR state of Sakarya', 'everest-forms' ),
			'TR55' => _x( 'Samsun', 'TR state of Samsun', 'everest-forms' ),
			'TR56' => _x( 'Siirt', 'TR state of Siirt', 'everest-forms' ),
			'TR57' => _x( 'Sinop', 'TR state of Sinop', 'everest-forms' ),
			'TR58' => _x( 'Sivas', 'TR state of Sivas', 'everest-forms' ),
			'TR59' => _x( 'Tekirdağ', 'TR state of Tekirdağ', 'everest-forms' ),
			'TR60' => _x( 'Tokat', 'TR state of Tokat', 'everest-forms' ),
			'TR61' => _x( 'Trabzon', 'TR state of Trabzon', 'everest-forms' ),
			'TR62' => _x( 'Tunceli', 'TR state of Tunceli', 'everest-forms' ),
			'TR63' => _x( 'Şanlıurfa', 'TR state of Şanlıurfa', 'everest-forms' ),
			'TR64' => _x( 'Uşak', 'TR state of Uşak', 'everest-forms' ),
			'TR65' => _x( 'Van', 'TR state of Van', 'everest-forms' ),
			'TR66' => _x( 'Yozgat', 'TR state of Yozgat', 'everest-forms' ),
			'TR67' => _x( 'Zonguldak', 'TR state of Zonguldak', 'everest-forms' ),
			'TR68' => _x( 'Aksaray', 'TR state of Aksaray', 'everest-forms' ),
			'TR69' => _x( 'Bayburt', 'TR state of Bayburt', 'everest-forms' ),
			'TR70' => _x( 'Karaman', 'TR state of Karaman', 'everest-forms' ),
			'TR71' => _x( 'Kırıkkale', 'TR state of Kırıkkale', 'everest-forms' ),
			'TR72' => _x( 'Batman', 'TR state of Batman', 'everest-forms' ),
			'TR73' => _x( 'Şırnak', 'TR state of Şırnak', 'everest-forms' ),
			'TR74' => _x( 'Bartın', 'TR state of Bartın', 'everest-forms' ),
			'TR75' => _x( 'Ardahan', 'TR state of Ardahan', 'everest-forms' ),
			'TR76' => _x( 'Iğdır', 'TR state of Iğdır', 'everest-forms' ),
			'TR77' => _x( 'Yalova', 'TR state of Yalova', 'everest-forms' ),
			'TR78' => _x( 'Karabük', 'TR state of Karabük', 'everest-forms' ),
			'TR79' => _x( 'Kilis', 'TR state of Kilis', 'everest-forms' ),
			'TR80' => _x( 'Osmaniye', 'TR state of Osmaniye', 'everest-forms' ),
			'TR81' => _x( 'Düzce', 'TR state of Düzce', 'everest-forms' ),
		),
		'TZ' => array( // Tanzanian states.
			'TZ01' => _x( 'Arusha', 'TZ state of Arusha', 'everest-forms' ),
			'TZ02' => _x( 'Dar es Salaam', 'TZ state of Dar es Salaam', 'everest-forms' ),
			'TZ03' => _x( 'Dodoma', 'TZ state of Dodoma', 'everest-forms' ),
			'TZ04' => _x( 'Iringa', 'TZ state of Iringa', 'everest-forms' ),
			'TZ05' => _x( 'Kagera', 'TZ state of Kagera', 'everest-forms' ),
			'TZ06' => _x( 'Pemba North', 'TZ state of Pemba North', 'everest-forms' ),
			'TZ07' => _x( 'Zanzibar North', 'TZ state of Zanzibar North', 'everest-forms' ),
			'TZ08' => _x( 'Kigoma', 'TZ state of Kigoma', 'everest-forms' ),
			'TZ09' => _x( 'Kilimanjaro', 'TZ state of Kilimanjaro', 'everest-forms' ),
			'TZ10' => _x( 'Pemba South', 'TZ state of Pemba South', 'everest-forms' ),
			'TZ11' => _x( 'Zanzibar South', 'TZ state of Zanzibar South', 'everest-forms' ),
			'TZ12' => _x( 'Lindi', 'TZ state of Lindi', 'everest-forms' ),
			'TZ13' => _x( 'Mara', 'TZ state of Mara', 'everest-forms' ),
			'TZ14' => _x( 'Mbeya', 'TZ state of Mbeya', 'everest-forms' ),
			'TZ15' => _x( 'Zanzibar West', 'TZ state of Zanzibar West', 'everest-forms' ),
			'TZ16' => _x( 'Morogoro', 'TZ state of Morogoro', 'everest-forms' ),
			'TZ17' => _x( 'Mtwara', 'TZ state of Mtwara', 'everest-forms' ),
			'TZ18' => _x( 'Mwanza', 'TZ state of Mwanza', 'everest-forms' ),
			'TZ19' => _x( 'Coast', 'TZ state of Coast', 'everest-forms' ),
			'TZ20' => _x( 'Rukwa', 'TZ state of Rukwa', 'everest-forms' ),
			'TZ21' => _x( 'Ruvuma', 'TZ state of Ruvuma', 'everest-forms' ),
			'TZ22' => _x( 'Shinyanga', 'TZ state of Shinyanga', 'everest-forms' ),
			'TZ23' => _x( 'Singida', 'TZ state of Singida', 'everest-forms' ),
			'TZ24' => _x( 'Tabora', 'TZ state of Tabora', 'everest-forms' ),
			'TZ25' => _x( 'Tanga', 'TZ state of Tanga', 'everest-forms' ),
			'TZ26' => _x( 'Manyara', 'TZ state of Manyara', 'everest-forms' ),
			'TZ27' => _x( 'Geita', 'TZ state of Geita', 'everest-forms' ),
			'TZ28' => _x( 'Katavi', 'TZ state of Katavi', 'everest-forms' ),
			'TZ29' => _x( 'Njombe', 'TZ state of Njombe', 'everest-forms' ),
			'TZ30' => _x( 'Simiyu', 'TZ state of Simiyu', 'everest-forms' ),
		),
		'LK' => array(),
		'RS' => array( // Serbian districts.
			'RS00' => _x( 'Belgrade', 'district', 'everest-forms' ),
			'RS14' => _x( 'Bor', 'district', 'everest-forms' ),
			'RS11' => _x( 'Braničevo', 'district', 'everest-forms' ),
			'RS02' => _x( 'Central Banat', 'district', 'everest-forms' ),
			'RS10' => _x( 'Danube', 'district', 'everest-forms' ),
			'RS23' => _x( 'Jablanica', 'district', 'everest-forms' ),
			'RS09' => _x( 'Kolubara', 'district', 'everest-forms' ),
			'RS08' => _x( 'Mačva', 'district', 'everest-forms' ),
			'RS17' => _x( 'Morava', 'district', 'everest-forms' ),
			'RS20' => _x( 'Nišava', 'district', 'everest-forms' ),
			'RS01' => _x( 'North Bačka', 'district', 'everest-forms' ),
			'RS03' => _x( 'North Banat', 'district', 'everest-forms' ),
			'RS24' => _x( 'Pčinja', 'district', 'everest-forms' ),
			'RS22' => _x( 'Pirot', 'district', 'everest-forms' ),
			'RS13' => _x( 'Pomoravlje', 'district', 'everest-forms' ),
			'RS19' => _x( 'Rasina', 'district', 'everest-forms' ),
			'RS18' => _x( 'Raška', 'district', 'everest-forms' ),
			'RS06' => _x( 'South Bačka', 'district', 'everest-forms' ),
			'RS04' => _x( 'South Banat', 'district', 'everest-forms' ),
			'RS07' => _x( 'Srem', 'district', 'everest-forms' ),
			'RS12' => _x( 'Šumadija', 'district', 'everest-forms' ),
			'RS21' => _x( 'Toplica', 'district', 'everest-forms' ),
			'RS05' => _x( 'West Bačka', 'district', 'everest-forms' ),
			'RS15' => _x( 'Zaječar', 'district', 'everest-forms' ),
			'RS16' => _x( 'Zlatibor', 'district', 'everest-forms' ),
			'RS25' => _x( 'Kosovo', 'district', 'everest-forms' ),
			'RS26' => _x( 'Peć', 'district', 'everest-forms' ),
			'RS27' => _x( 'Prizren', 'district', 'everest-forms' ),
			'RS28' => _x( 'Kosovska Mitrovica', 'district', 'everest-forms' ),
			'RS29' => _x( 'Kosovo-Pomoravlje', 'district', 'everest-forms' ),
			'RSKM' => _x( 'Kosovo-Metohija', 'district', 'everest-forms' ),
			'RSVO' => _x( 'Vojvodina', 'district', 'everest-forms' ),
		),
		'SE' => array(),
		'UA' => array( // Ukrainian oblasts.
			'VN' => _x( 'Vinnytsia Oblast', 'UA state of Vinnytsia Oblast', 'everest-forms' ),
			'VL' => _x( 'Volyn Oblast', 'UA state of Volyn Oblast', 'everest-forms' ),
			'DP' => _x( 'Dnipropetrovsk Oblast', 'UA state of Dnipropetrovsk Oblast', 'everest-forms' ),
			'DT' => _x( 'Donetsk Oblast', 'UA state of Donetsk Oblast', 'everest-forms' ),
			'ZT' => _x( 'Zhytomyr Oblast', 'UA state of Zhytomyr Oblast', 'everest-forms' ),
			'ZK' => _x( 'Zakarpattia Oblast', 'UA state of Zakarpattia Oblast', 'everest-forms' ),
			'ZP' => _x( 'Zaporizhzhia Oblast', 'UA state of Zaporizhzhia Oblast', 'everest-forms' ),
			'IF' => _x( 'Ivano-Frankivsk Oblast', 'UA state of Ivano-Frankivsk Oblast', 'everest-forms' ),
			'KV' => _x( 'Kyiv Oblast', 'UA state of Kyiv Oblast', 'everest-forms' ),
			'KH' => _x( 'Kirovohrad Oblast', 'UA state of Kirovohrad Oblast', 'everest-forms' ),
			'LH' => _x( 'Luhansk Oblast', 'UA state of Luhansk Oblast', 'everest-forms' ),
			'LV' => _x( 'Lviv Oblast', 'UA state of Lviv Oblast', 'everest-forms' ),
			'MY' => _x( 'Mykolaiv Oblast', 'UA state of Mykolaiv Oblast', 'everest-forms' ),
			'OD' => _x( 'Odessa Oblast', 'UA state of Odessa Oblast', 'everest-forms' ),
			'PL' => _x( 'Poltava Oblast', 'UA state of Poltava Oblast', 'everest-forms' ),
			'RV' => _x( 'Rivne Oblast', 'UA state of Rivne Oblast', 'everest-forms' ),
			'SM' => _x( 'Sumy Oblast', 'UA state of Sumy Oblast', 'everest-forms' ),
			'TP' => _x( 'Ternopil Oblast', 'UA state of Ternopil Oblast', 'everest-forms' ),
			'KK' => _x( 'Kharkiv Oblast', 'UA state of Kharkiv Oblast', 'everest-forms' ),
			'KS' => _x( 'Kherson Oblast', 'UA state of Kherson Oblast', 'everest-forms' ),
			'KM' => _x( 'Khmelnytskyi Oblast', 'UA state of Khmelnytskyi Oblast', 'everest-forms' ),
			'CK' => _x( 'Cherkasy Oblast', 'UA state of Cherkasy Oblast', 'everest-forms' ),
			'CH' => _x( 'Chernihiv Oblast', 'UA state of Chernihiv Oblast', 'everest-forms' ),
			'CV' => _x( 'Chernivtsi Oblast', 'UA state of Chernivtsi Oblast', 'everest-forms' ),
		),
		'UG' => array( // Ugandan districts.
			'UG314' => _x( 'Abim', 'UG state of Abim', 'everest-forms' ),
			'UG301' => _x( 'Adjumani', 'UG state of Adjumani', 'everest-forms' ),
			'UG322' => _x( 'Agago', 'UG state of Agago', 'everest-forms' ),
			'UG323' => _x( 'Alebtong', 'UG state of Alebtong', 'everest-forms' ),
			'UG315' => _x( 'Amolatar', 'UG state of Amolatar', 'everest-forms' ),
			'UG324' => _x( 'Amudat', 'UG state of Amudat', 'everest-forms' ),
			'UG216' => _x( 'Amuria', 'UG state of Amuria', 'everest-forms' ),
			'UG316' => _x( 'Amuru', 'UG state of Amuru', 'everest-forms' ),
			'UG302' => _x( 'Apac', 'UG state of Apac', 'everest-forms' ),
			'UG303' => _x( 'Arua', 'UG state of Arua', 'everest-forms' ),
			'UG217' => _x( 'Budaka', 'UG state of Budaka', 'everest-forms' ),
			'UG218' => _x( 'Bududa', 'UG state of Bududa', 'everest-forms' ),
			'UG201' => _x( 'Bugiri', 'UG state of Bugiri', 'everest-forms' ),
			'UG235' => _x( 'Bugweri', 'UG state of Bugweri', 'everest-forms' ),
			'UG420' => _x( 'Buhweju', 'UG state of Buhweju', 'everest-forms' ),
			'UG117' => _x( 'Buikwe', 'UG state of Buikwe', 'everest-forms' ),
			'UG219' => _x( 'Bukedea', 'UG state of Bukedea', 'everest-forms' ),
			'UG118' => _x( 'Bukomansimbi', 'UG state of Bukomansimbi', 'everest-forms' ),
			'UG220' => _x( 'Bukwa', 'UG state of Bukwa', 'everest-forms' ),
			'UG225' => _x( 'Bulambuli', 'UG state of Bulambuli', 'everest-forms' ),
			'UG416' => _x( 'Buliisa', 'UG state of Buliisa', 'everest-forms' ),
			'UG401' => _x( 'Bundibugyo', 'UG state of Bundibugyo', 'everest-forms' ),
			'UG430' => _x( 'Bunyangabu', 'UG state of Bunyangabu', 'everest-forms' ),
			'UG402' => _x( 'Bushenyi', 'UG state of Bushenyi', 'everest-forms' ),
			'UG202' => _x( 'Busia', 'UG state of Busia', 'everest-forms' ),
			'UG221' => _x( 'Butaleja', 'UG state of Butaleja', 'everest-forms' ),
			'UG119' => _x( 'Butambala', 'UG state of Butambala', 'everest-forms' ),
			'UG233' => _x( 'Butebo', 'UG state of Butebo', 'everest-forms' ),
			'UG120' => _x( 'Buvuma', 'UG state of Buvuma', 'everest-forms' ),
			'UG226' => _x( 'Buyende', 'UG state of Buyende', 'everest-forms' ),
			'UG317' => _x( 'Dokolo', 'UG state of Dokolo', 'everest-forms' ),
			'UG121' => _x( 'Gomba', 'UG state of Gomba', 'everest-forms' ),
			'UG304' => _x( 'Gulu', 'UG state of Gulu', 'everest-forms' ),
			'UG403' => _x( 'Hoima', 'UG state of Hoima', 'everest-forms' ),
			'UG417' => _x( 'Ibanda', 'UG state of Ibanda', 'everest-forms' ),
			'UG203' => _x( 'Iganga', 'UG state of Iganga', 'everest-forms' ),
			'UG418' => _x( 'Isingiro', 'UG state of Isingiro', 'everest-forms' ),
			'UG204' => _x( 'Jinja', 'UG state of Jinja', 'everest-forms' ),
			'UG318' => _x( 'Kaabong', 'UG state of Kaabong', 'everest-forms' ),
			'UG404' => _x( 'Kabale', 'UG state of Kabale', 'everest-forms' ),
			'UG405' => _x( 'Kabarole', 'UG state of Kabarole', 'everest-forms' ),
			'UG213' => _x( 'Kaberamaido', 'UG state of Kaberamaido', 'everest-forms' ),
			'UG427' => _x( 'Kagadi', 'UG state of Kagadi', 'everest-forms' ),
			'UG428' => _x( 'Kakumiro', 'UG state of Kakumiro', 'everest-forms' ),
			'UG101' => _x( 'Kalangala', 'UG state of Kalangala', 'everest-forms' ),
			'UG222' => _x( 'Kaliro', 'UG state of Kaliro', 'everest-forms' ),
			'UG122' => _x( 'Kalungu', 'UG state of Kalungu', 'everest-forms' ),
			'UG102' => _x( 'Kampala', 'UG state of Kampala', 'everest-forms' ),
			'UG205' => _x( 'Kamuli', 'UG state of Kamuli', 'everest-forms' ),
			'UG413' => _x( 'Kamwenge', 'UG state of Kamwenge', 'everest-forms' ),
			'UG414' => _x( 'Kanungu', 'UG state of Kanungu', 'everest-forms' ),
			'UG206' => _x( 'Kapchorwa', 'UG state of Kapchorwa', 'everest-forms' ),
			'UG236' => _x( 'Kapelebyong', 'UG state of Kapelebyong', 'everest-forms' ),
			'UG126' => _x( 'Kasanda', 'UG state of Kasanda', 'everest-forms' ),
			'UG406' => _x( 'Kasese', 'UG state of Kasese', 'everest-forms' ),
			'UG207' => _x( 'Katakwi', 'UG state of Katakwi', 'everest-forms' ),
			'UG112' => _x( 'Kayunga', 'UG state of Kayunga', 'everest-forms' ),
			'UG407' => _x( 'Kibaale', 'UG state of Kibaale', 'everest-forms' ),
			'UG103' => _x( 'Kiboga', 'UG state of Kiboga', 'everest-forms' ),
			'UG227' => _x( 'Kibuku', 'UG state of Kibuku', 'everest-forms' ),
			'UG432' => _x( 'Kikuube', 'UG state of Kikuube', 'everest-forms' ),
			'UG419' => _x( 'Kiruhura', 'UG state of Kiruhura', 'everest-forms' ),
			'UG421' => _x( 'Kiryandongo', 'UG state of Kiryandongo', 'everest-forms' ),
			'UG408' => _x( 'Kisoro', 'UG state of Kisoro', 'everest-forms' ),
			'UG305' => _x( 'Kitgum', 'UG state of Kitgum', 'everest-forms' ),
			'UG319' => _x( 'Koboko', 'UG state of Koboko', 'everest-forms' ),
			'UG325' => _x( 'Kole', 'UG state of Kole', 'everest-forms' ),
			'UG306' => _x( 'Kotido', 'UG state of Kotido', 'everest-forms' ),
			'UG208' => _x( 'Kumi', 'UG state of Kumi', 'everest-forms' ),
			'UG333' => _x( 'Kwania', 'UG state of Kwania', 'everest-forms' ),
			'UG228' => _x( 'Kween', 'UG state of Kween', 'everest-forms' ),
			'UG123' => _x( 'Kyankwanzi', 'UG state of Kyankwanzi', 'everest-forms' ),
			'UG422' => _x( 'Kyegegwa', 'UG state of Kyegegwa', 'everest-forms' ),
			'UG415' => _x( 'Kyenjojo', 'UG state of Kyenjojo', 'everest-forms' ),
			'UG125' => _x( 'Kyotera', 'UG state of Kyotera', 'everest-forms' ),
			'UG326' => _x( 'Lamwo', 'UG state of Lamwo', 'everest-forms' ),
			'UG307' => _x( 'Lira', 'UG state of Lira', 'everest-forms' ),
			'UG229' => _x( 'Luuka', 'UG state of Luuka', 'everest-forms' ),
			'UG104' => _x( 'Luwero', 'UG state of Luwero', 'everest-forms' ),
			'UG124' => _x( 'Lwengo', 'UG state of Lwengo', 'everest-forms' ),
			'UG114' => _x( 'Lyantonde', 'UG state of Lyantonde', 'everest-forms' ),
			'UG223' => _x( 'Manafwa', 'UG state of Manafwa', 'everest-forms' ),
			'UG320' => _x( 'Maracha', 'UG state of Maracha', 'everest-forms' ),
			'UG105' => _x( 'Masaka', 'UG state of Masaka', 'everest-forms' ),
			'UG409' => _x( 'Masindi', 'UG state of Masindi', 'everest-forms' ),
			'UG214' => _x( 'Mayuge', 'UG state of Mayuge', 'everest-forms' ),
			'UG209' => _x( 'Mbale', 'UG state of Mbale', 'everest-forms' ),
			'UG410' => _x( 'Mbarara', 'UG state of Mbarara', 'everest-forms' ),
			'UG423' => _x( 'Mitooma', 'UG state of Mitooma', 'everest-forms' ),
			'UG115' => _x( 'Mityana', 'UG state of Mityana', 'everest-forms' ),
			'UG308' => _x( 'Moroto', 'UG state of Moroto', 'everest-forms' ),
			'UG309' => _x( 'Moyo', 'UG state of Moyo', 'everest-forms' ),
			'UG106' => _x( 'Mpigi', 'UG state of Mpigi', 'everest-forms' ),
			'UG107' => _x( 'Mubende', 'UG state of Mubende', 'everest-forms' ),
			'UG108' => _x( 'Mukono', 'UG state of Mukono', 'everest-forms' ),
			'UG334' => _x( 'Nabilatuk', 'UG state of Nabilatuk', 'everest-forms' ),
			'UG311' => _x( 'Nakapiripirit', 'UG state of Nakapiripirit', 'everest-forms' ),
			'UG116' => _x( 'Nakaseke', 'UG state of Nakaseke', 'everest-forms' ),
			'UG109' => _x( 'Nakasongola', 'UG state of Nakasongola', 'everest-forms' ),
			'UG230' => _x( 'Namayingo', 'UG state of Namayingo', 'everest-forms' ),
			'UG234' => _x( 'Namisindwa', 'UG state of Namisindwa', 'everest-forms' ),
			'UG224' => _x( 'Namutumba', 'UG state of Namutumba', 'everest-forms' ),
			'UG327' => _x( 'Napak', 'UG state of Napak', 'everest-forms' ),
			'UG310' => _x( 'Nebbi', 'UG state of Nebbi', 'everest-forms' ),
			'UG231' => _x( 'Ngora', 'UG state of Ngora', 'everest-forms' ),
			'UG424' => _x( 'Ntoroko', 'UG state of Ntoroko', 'everest-forms' ),
			'UG411' => _x( 'Ntungamo', 'UG state of Ntungamo', 'everest-forms' ),
			'UG328' => _x( 'Nwoya', 'UG state of Nwoya', 'everest-forms' ),
			'UG331' => _x( 'Omoro', 'UG state of Omoro', 'everest-forms' ),
			'UG329' => _x( 'Otuke', 'UG state of Otuke', 'everest-forms' ),
			'UG321' => _x( 'Oyam', 'UG state of Oyam', 'everest-forms' ),
			'UG312' => _x( 'Pader', 'UG state of Pader', 'everest-forms' ),
			'UG332' => _x( 'Pakwach', 'UG state of Pakwach', 'everest-forms' ),
			'UG210' => _x( 'Pallisa', 'UG state of Pallisa', 'everest-forms' ),
			'UG110' => _x( 'Rakai', 'UG state of Rakai', 'everest-forms' ),
			'UG429' => _x( 'Rubanda', 'UG state of Rubanda', 'everest-forms' ),
			'UG425' => _x( 'Rubirizi', 'UG state of Rubirizi', 'everest-forms' ),
			'UG431' => _x( 'Rukiga', 'UG state of Rukiga', 'everest-forms' ),
			'UG412' => _x( 'Rukungiri', 'UG state of Rukungiri', 'everest-forms' ),
			'UG111' => _x( 'Sembabule', 'UG state of Sembabule', 'everest-forms' ),
			'UG232' => _x( 'Serere', 'UG state of Serere', 'everest-forms' ),
			'UG426' => _x( 'Sheema', 'UG state of Sheema', 'everest-forms' ),
			'UG215' => _x( 'Sironko', 'UG state of Sironko', 'everest-forms' ),
			'UG211' => _x( 'Soroti', 'UG state of Soroti', 'everest-forms' ),
			'UG212' => _x( 'Tororo', 'UG state of Tororo', 'everest-forms' ),
			'UG113' => _x( 'Wakiso', 'UG state of Wakiso', 'everest-forms' ),
			'UG313' => _x( 'Yumbe', 'UG state of Yumbe', 'everest-forms' ),
			'UG330' => _x( 'Zombo', 'UG state of Zombo', 'everest-forms' ),
		),
		'UM' => array(
			'81' => _x( 'Baker Island', 'UM state of Baker Island', 'everest-forms' ),
			'84' => _x( 'Howland Island', 'UM state of Howland Island', 'everest-forms' ),
			'86' => _x( 'Jarvis Island', 'UM state of Jarvis Island', 'everest-forms' ),
			'67' => _x( 'Johnston Atoll', 'UM state of Johnston Atoll', 'everest-forms' ),
			'89' => _x( 'Kingman Reef', 'UM state of Kingman Reef', 'everest-forms' ),
			'71' => _x( 'Midway Atoll', 'UM state of Midway Atoll', 'everest-forms' ),
			'76' => _x( 'Navassa Island', 'UM state of Navassa Island', 'everest-forms' ),
			'95' => _x( 'Palmyra Atoll', 'UM state of Palmyra Atoll', 'everest-forms' ),
			'79' => _x( 'Wake Island', 'UM state of Wake Island', 'everest-forms' ),
		),
		'US' => array( // U.S. states.
			'AL' => _x( 'Alabama', 'US state of Alabama', 'everest-forms' ),
			'AK' => _x( 'Alaska', 'US state of Alaska', 'everest-forms' ),
			'AZ' => _x( 'Arizona', 'US state of Arizona', 'everest-forms' ),
			'AR' => _x( 'Arkansas', 'US state of Arkansas', 'everest-forms' ),
			'CA' => _x( 'California', 'US state of California', 'everest-forms' ),
			'CO' => _x( 'Colorado', 'US state of Colorado', 'everest-forms' ),
			'CT' => _x( 'Connecticut', 'US state of Connecticut', 'everest-forms' ),
			'DE' => _x( 'Delaware', 'US state of Delaware', 'everest-forms' ),
			'DC' => _x( 'District Of Columbia', 'US state of District Of Columbia', 'everest-forms' ),
			'FL' => _x( 'Florida', 'US state of Florida', 'everest-forms' ),
			'GA' => _x( 'Georgia', 'US state of Georgia', 'everest-forms' ),
			'HI' => _x( 'Hawaii', 'US state of Hawaii', 'everest-forms' ),
			'ID' => _x( 'Idaho', 'US state of Idaho', 'everest-forms' ),
			'IL' => _x( 'Illinois', 'US state of Illinois', 'everest-forms' ),
			'IN' => _x( 'Indiana', 'US state of Indiana', 'everest-forms' ),
			'IA' => _x( 'Iowa', 'US state of Iowa', 'everest-forms' ),
			'KS' => _x( 'Kansas', 'US state of Kansas', 'everest-forms' ),
			'KY' => _x( 'Kentucky', 'US state of Kentucky', 'everest-forms' ),
			'LA' => _x( 'Louisiana', 'US state of Louisiana', 'everest-forms' ),
			'ME' => _x( 'Maine', 'US state of Maine', 'everest-forms' ),
			'MD' => _x( 'Maryland', 'US state of Maryland', 'everest-forms' ),
			'MA' => _x( 'Massachusetts', 'US state of Massachusetts', 'everest-forms' ),
			'MI' => _x( 'Michigan', 'US state of Michigan', 'everest-forms' ),
			'MN' => _x( 'Minnesota', 'US state of Minnesota', 'everest-forms' ),
			'MS' => _x( 'Mississippi', 'US state of Mississippi', 'everest-forms' ),
			'MO' => _x( 'Missouri', 'US state of Missouri', 'everest-forms' ),
			'MT' => _x( 'Montana', 'US state of Montana', 'everest-forms' ),
			'NE' => _x( 'Nebraska', 'US state of Nebraska', 'everest-forms' ),
			'NV' => _x( 'Nevada', 'US state of Nevada', 'everest-forms' ),
			'NH' => _x( 'New Hampshire', 'US state of New Hampshire', 'everest-forms' ),
			'NJ' => _x( 'New Jersey', 'US state of New Jersey', 'everest-forms' ),
			'NM' => _x( 'New Mexico', 'US state of New Mexico', 'everest-forms' ),
			'NY' => _x( 'New York', 'US state of New York', 'everest-forms' ),
			'NC' => _x( 'North Carolina', 'US state of North Carolina', 'everest-forms' ),
			'ND' => _x( 'North Dakota', 'US state of North Dakota', 'everest-forms' ),
			'OH' => _x( 'Ohio', 'US state of Ohio', 'everest-forms' ),
			'OK' => _x( 'Oklahoma', 'US state of Oklahoma', 'everest-forms' ),
			'OR' => _x( 'Oregon', 'US state of Oregon', 'everest-forms' ),
			'PA' => _x( 'Pennsylvania', 'US state of Pennsylvania', 'everest-forms' ),
			'RI' => _x( 'Rhode Island', 'US state of Rhode Island', 'everest-forms' ),
			'SC' => _x( 'South Carolina', 'US state of South Carolina', 'everest-forms' ),
			'SD' => _x( 'South Dakota', 'US state of South Dakota', 'everest-forms' ),
			'TN' => _x( 'Tennessee', 'US state of Tennessee', 'everest-forms' ),
			'TX' => _x( 'Texas', 'US state of Texas', 'everest-forms' ),
			'UT' => _x( 'Utah', 'US state of Utah', 'everest-forms' ),
			'VT' => _x( 'Vermont', 'US state of Vermont', 'everest-forms' ),
			'VA' => _x( 'Virginia', 'US state of Virginia', 'everest-forms' ),
			'WA' => _x( 'Washington', 'US state of Washington', 'everest-forms' ),
			'WV' => _x( 'West Virginia', 'US state of West Virginia', 'everest-forms' ),
			'WI' => _x( 'Wisconsin', 'US state of Wisconsin', 'everest-forms' ),
			'WY' => _x( 'Wyoming', 'US state of Wyoming', 'everest-forms' ),
			'AA' => _x( 'Armed Forces (AA)', 'US state of Armed Forces (AA)', 'everest-forms' ),
			'AE' => _x( 'Armed Forces (AE)', 'US state of Armed Forces (AE)', 'everest-forms' ),
			'AP' => _x( 'Armed Forces (AP)', 'US state of Armed Forces (AP)', 'everest-forms' ),
		),
		'UY' => array( // Uruguayan states.
			'UY-AR' => _x( 'Artigas', 'UY state of Artigas', 'everest-forms' ),
			'UY-CA' => _x( 'Canelones', 'UY state of Canelones', 'everest-forms' ),
			'UY-CL' => _x( 'Cerro Largo', 'UY state of Cerro Largo', 'everest-forms' ),
			'UY-CO' => _x( 'Colonia', 'UY state of Colonia', 'everest-forms' ),
			'UY-DU' => _x( 'Durazno', 'UY state of Durazno', 'everest-forms' ),
			'UY-FS' => _x( 'Flores', 'UY state of Flores', 'everest-forms' ),
			'UY-FD' => _x( 'Florida', 'UY state of Florida', 'everest-forms' ),
			'UY-LA' => _x( 'Lavalleja', 'UY state of Lavalleja', 'everest-forms' ),
			'UY-MA' => _x( 'Maldonado', 'UY state of Maldonado', 'everest-forms' ),
			'UY-MO' => _x( 'Montevideo', 'UY state of Montevideo', 'everest-forms' ),
			'UY-PA' => _x( 'Paysandú', 'UY state of Paysandú', 'everest-forms' ),
			'UY-RN' => _x( 'Río Negro', 'UY state of Río Negro', 'everest-forms' ),
			'UY-RV' => _x( 'Rivera', 'UY state of Rivera', 'everest-forms' ),
			'UY-RO' => _x( 'Rocha', 'UY state of Rocha', 'everest-forms' ),
			'UY-SA' => _x( 'Salto', 'UY state of Salto', 'everest-forms' ),
			'UY-SJ' => _x( 'San José', 'UY state of San José', 'everest-forms' ),
			'UY-SO' => _x( 'Soriano', 'UY state of Soriano', 'everest-forms' ),
			'UY-TA' => _x( 'Tacuarembó', 'UY state of Tacuarembó', 'everest-forms' ),
			'UY-TT' => _x( 'Treinta y Tres', 'UY state of Treinta y Tres', 'everest-forms' ),
		),
		'VE' => array( // Venezuelan states.
			'VE-A' => _x( 'Capital', 'VE state of Capital', 'everest-forms' ),
			'VE-B' => _x( 'Anzoátegui', 'VE state of Anzoátegui', 'everest-forms' ),
			'VE-C' => _x( 'Apure', 'VE state of Apure', 'everest-forms' ),
			'VE-D' => _x( 'Aragua', 'VE state of Aragua', 'everest-forms' ),
			'VE-E' => _x( 'Barinas', 'VE state of Barinas', 'everest-forms' ),
			'VE-F' => _x( 'Bolívar', 'VE state of Bolívar', 'everest-forms' ),
			'VE-G' => _x( 'Carabobo', 'VE state of Carabobo', 'everest-forms' ),
			'VE-H' => _x( 'Cojedes', 'VE state of Cojedes', 'everest-forms' ),
			'VE-I' => _x( 'Falcón', 'VE state of Falcón', 'everest-forms' ),
			'VE-J' => _x( 'Guárico', 'VE state of Guárico', 'everest-forms' ),
			'VE-K' => _x( 'Lara', 'VE state of Lara', 'everest-forms' ),
			'VE-L' => _x( 'Mérida', 'VE state of Mérida', 'everest-forms' ),
			'VE-M' => _x( 'Miranda', 'VE state of Miranda', 'everest-forms' ),
			'VE-N' => _x( 'Monagas', 'VE state of Monagas', 'everest-forms' ),
			'VE-O' => _x( 'Nueva Esparta', 'VE state of Nueva Esparta', 'everest-forms' ),
			'VE-P' => _x( 'Portuguesa', 'VE state of Portuguesa', 'everest-forms' ),
			'VE-R' => _x( 'Sucre', 'VE state of Sucre', 'everest-forms' ),
			'VE-S' => _x( 'Táchira', 'VE state of Táchira', 'everest-forms' ),
			'VE-T' => _x( 'Trujillo', 'VE state of Trujillo', 'everest-forms' ),
			'VE-U' => _x( 'Yaracuy', 'VE state of Yaracuy', 'everest-forms' ),
			'VE-V' => _x( 'Zulia', 'VE state of Zulia', 'everest-forms' ),
			'VE-W' => _x( 'Federal Dependencies', 'VE state of Federal Dependencies', 'everest-forms' ),
			'VE-X' => _x( 'La Guaira (Vargas)', 'VE state of La Guaira (Vargas)', 'everest-forms' ),
			'VE-Y' => _x( 'Delta Amacuro', 'VE state of Delta Amacuro', 'everest-forms' ),
			'VE-Z' => _x( 'Amazonas', 'VE state of Amazonas', 'everest-forms' ),
		),
		'VN' => array(),
		'YT' => array(),
		'ZA' => array( // South African states.
			'EC'  => _x( 'Eastern Cape', 'ZA state of Eastern Cape', 'everest-forms' ),
			'FS'  => _x( 'Free State', 'ZA state of Free State', 'everest-forms' ),
			'GP'  => _x( 'Gauteng', 'ZA state of Gauteng', 'everest-forms' ),
			'KZN' => _x( 'KwaZulu-Natal', 'ZA state of KwaZulu-Natal', 'everest-forms' ),
			'LP'  => _x( 'Limpopo', 'ZA state of Limpopo', 'everest-forms' ),
			'MP'  => _x( 'Mpumalanga', 'ZA state of Mpumalanga', 'everest-forms' ),
			'NC'  => _x( 'Northern Cape', 'ZA state of Northern Cape', 'everest-forms' ),
			'NW'  => _x( 'North West', 'ZA state of North West', 'everest-forms' ),
			'WC'  => _x( 'Western Cape', 'ZA state of Western Cape', 'everest-forms' ),
		),
		'ZM' => array( // Zambian provinces.
			'ZM-01' => _x( 'Western', 'ZM state of Western', 'everest-forms' ),
			'ZM-02' => _x( 'Central', 'ZM state of Central', 'everest-forms' ),
			'ZM-03' => _x( 'Eastern', 'ZM state of Eastern', 'everest-forms' ),
			'ZM-04' => _x( 'Luapula', 'ZM state of Luapula', 'everest-forms' ),
			'ZM-05' => _x( 'Northern', 'ZM state of Northern', 'everest-forms' ),
			'ZM-06' => _x( 'North-Western', 'ZM state of North-Western', 'everest-forms' ),
			'ZM-07' => _x( 'Southern', 'ZM state of Southern', 'everest-forms' ),
			'ZM-08' => _x( 'Copperbelt', 'ZM state of Copperbelt', 'everest-forms' ),
			'ZM-09' => _x( 'Lusaka', 'ZM state of Lusaka', 'everest-forms' ),
			'ZM-10' => _x( 'Muchinga', 'ZM state of Muchinga', 'everest-forms' ),
		),
	);

	return (array) apply_filters( 'everest_forms_states', $states );
}

/**
 * Get builder fields groups.
 *
 * @return array
 */
function evf_get_fields_groups() {
	return (array) apply_filters(
		'everest_forms_builder_fields_groups',
		array(
			'general'  => __( 'General Fields', 'everest-forms' ),
			'advanced' => __( 'Advanced Fields', 'everest-forms' ),
			'payment'  => __( 'Payment Fields', 'everest-forms' ),
			'survey'   => __( 'Survey Fields', 'everest-forms' ),
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
		'label'         => array(
			'id'       => 'label',
			'title'    => __( 'Label', 'everest-forms' ),
			'desc'     => __( 'Enter text for the form field label. This is recommended and can be hidden in the Advanced Settings.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'meta'          => array(
			'id'       => 'meta-key',
			'title'    => __( 'Meta Key', 'everest-forms' ),
			'desc'     => __( 'Enter meta key to be stored in database.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'description'   => array(
			'id'       => 'description',
			'title'    => __( 'Description', 'everest-forms' ),
			'type'     => 'textarea',
			'desc'     => __( 'Enter text for the form field description.', 'everest-forms' ),
			'default'  => '',
			'desc_tip' => true,
		),
		'required'      => array(
			'id'       => 'require',
			'title'    => __( 'Required', 'everest-forms' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Check this option to mark the field required.', 'everest-forms' ),
			'default'  => 'no',
			'desc_tip' => true,
		),
		'choices'       => array(
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
		'placeholder'   => array(
			'id'       => 'placeholder',
			'title'    => __( 'Placeholder Text', 'everest-forms' ),
			'desc'     => __( 'Enter text for the form field placeholder.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'css'           => array(
			'id'       => 'css',
			'title'    => __( 'CSS Classes', 'everest-forms' ),
			'desc'     => __( 'Enter CSS class for this field container. Class names should be separated with spaces.', 'everest-forms' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'label_hide'    => array(
			'id'       => 'label_hide',
			'title'    => __( 'Hide Label', 'everest-forms' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Check this option to hide the form field label.', 'everest-forms' ),
			'default'  => 'no',
			'desc_tip' => true,
		),
		'sublabel_hide' => array(
			'id'       => 'sublabel_hide',
			'title'    => __( 'Hide Sub-Labels', 'everest-forms' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Check this option to hide the form field sub-label.', 'everest-forms' ),
			'default'  => 'no',
			'desc_tip' => true,
		),
	);

	return apply_filters( 'everest_form_all_fields_settings', $settings );
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

		if ( ! $return ) {
			echo '<textarea style="color:#666;background:#fff;margin: 20px 0;width:100%;height:500px;font-size:12px;font-family: Consolas,Monaco,Lucida Console,monospace;direction: ltr;unicode-bidi: embed;line-height: 1.4;padding: 4px 6px 1px;" readonly>';

			echo "==================== Everest Forms Debugging ====================\n\n";

			if ( is_array( $expression ) || is_object( $expression ) ) {
				echo esc_html( evf_print_r( $expression, true ) );
			} else {
				echo esc_html( $expression );
			}
			echo '</textarea>';

		} else {
			$output = '<textarea style="color:#666;background:#fff;margin: 20px 0;width:100%;height:500px;font-size:12px;font-family: Consolas,Monaco,Lucida Console,monospace;direction: ltr;unicode-bidi: embed;line-height: 1.4;padding: 4px 6px 1px;" readonly>';

			$output .= "==================== Everest Forms Debugging ====================\n\n";

			if ( is_array( $expression ) || is_object( $expression ) ) {
				$output .= evf_print_r( $expression, true );
			} else {
				$output .= $expression;
			}

			$output .= '</textarea>';

			return $output;
		}
	}
}

/**
 * String translation function.
 *
 * @since 1.4.9
 *
 * @param int    $form_id Form ID.
 * @param string $field_id Field ID.
 * @param mixed  $value The string that needs to be translated.
 * @param string $suffix The suffix to make the field have unique naem.
 *
 * @return mixed The translated string.
 */
function evf_string_translation( $form_id, $field_id, $value, $suffix = '' ) {
	$context = isset( $form_id ) ? 'everest_forms_' . absint( $form_id ) : 0;
	$name    = isset( $field_id ) ? evf_clean( $field_id . $suffix ) : '';

	if ( function_exists( 'icl_register_string' ) ) {
		icl_register_string( $context, $name, $value );
	}

	if ( function_exists( 'icl_t' ) ) {
		$value = icl_t( $context, $name, $value );
	}

	return $value;
}

/**
 * Trigger logging cleanup using the logging class.
 *
 * @since 1.6.2
 */
function evf_cleanup_logs() {
	$logger = evf_get_logger();

	if ( is_callable( array( $logger, 'clear_expired_logs' ) ) ) {
		$logger->clear_expired_logs();
	}
}
add_action( 'everest_forms_cleanup_logs', 'evf_cleanup_logs' );


/**
 * Check whether it device is table or not from HTTP user agent
 *
 * @since 1.7.0
 *
 * @return bool
 */
function evf_is_tablet() {
	return false !== stripos( evf_get_user_agent(), 'tablet' ) || false !== stripos( evf_get_user_agent(), 'tab' );
}

/**
 * Get user device from user agent from HTTP user agent.
 *
 * @since 1.7.0
 *
 * @return string
 */
function evf_get_user_device() {
	if ( evf_is_tablet() ) {
		return esc_html__( 'Tablet', 'everest-forms' );
	} elseif ( wp_is_mobile() ) {
		return esc_html__( 'Mobile', 'everest-forms' );
	} else {
		return esc_html__( 'Desktop', 'everest-forms' );
	}
}


/**
 * A wp_parse_args() for multi-dimensional array.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_parse_args/
 *
 * @since 1.7.0
 *
 * @param array $args       Value to merge with $defaults.
 * @param array $defaults   Array that serves as the defaults.
 *
 * @return array    Merged user defined values with defaults.
 */
function evf_parse_args( &$args, $defaults ) {
	$args     = (array) $args;
	$defaults = (array) $defaults;
	$result   = $defaults;
	foreach ( $args as $k => &$v ) {
		if ( is_array( $v ) && isset( $result[ $k ] ) ) {
			$result[ $k ] = evf_parse_args( $v, $result[ $k ] );
		} else {
			$result[ $k ] = $v;
		}
	}
	return $result;
}

/**
 * Get date of ranges.
 *
 * @since 1.7.0
 *
 * @param string $first Starting date.
 * @param string $last  End date.
 * @param string $step Date step.
 * @param string $format Date format.
 *
 * @return array Range dates.
 */
function evf_date_range( $first, $last = '', $step = '+1 day', $format = 'Y/m/d' ) {
	$dates   = array();
	$current = strtotime( $first );
	$last    = strtotime( $last );

	while ( $current <= $last ) {
		$dates[] = date_i18n( $format, $current );
		$current = strtotime( $step, $current );
	}

	return $dates;
}

/**
 * Process syntaxes in a text.
 *
 * @since 1.7.0
 *
 * @param string $text Text to be processed.
 * @param bool   $escape_html Whether to escape all the htmls before processing or not.
 * @param bool   $trim_trailing_spaces Whether to trim trailing spaces or not.
 *
 * @return string Processed text.
 */
function evf_process_syntaxes( $text, $escape_html = true, $trim_trailing_spaces = true ) {

	if ( true === $trim_trailing_spaces ) {
		$text = trim( $text );
	}
	if ( true === $escape_html ) {
		$text = esc_html( $text );
	}
	$text = evf_process_hyperlink_syntax( $text );
	$text = evf_process_italic_syntax( $text );
	$text = evf_process_bold_syntax( $text );
	$text = evf_process_underline_syntax( $text );
	$text = evf_process_line_breaks( $text );
	return $text;
}

/**
 * Extract page ids from a text.
 *
 * @since 1.7.0
 *
 * @param string $text Text to extract page ids from.
 *
 * @return mixed
 */
function evf_extract_page_ids( $text ) {
	$page_id_syntax_matches = array();
	$page_ids               = array();

	while ( preg_match( '/page_id=([0-9]+)/', $text, $page_id_syntax_matches ) ) {
		$page_id    = $page_id_syntax_matches[1];
		$page_ids[] = $page_id;
		$text       = str_replace( 'page_id=' . $page_id, '', $text );
	}

	if ( count( $page_ids ) > 0 ) {
		return $page_ids;
	}
	return false;
}

/**
 * Process hyperlink syntaxes in a text.
 * The syntax used for hyperlink is: [Link Label](Link URL)
 * Example: [Google Search Page](https://google.com)
 *
 * @since 1.7.0
 *
 * @param string $text         Text to process.
 * @param string $use_no_a_tag If set to `true` only the link will be used and no `a` tag. Particularly useful for exporting CSV,
 *                             as the html tags are escaped in a CSV file.
 *
 * @return string Processed text.
 */
function evf_process_hyperlink_syntax( $text, $use_no_a_tag = false ) {
	$matches = array();
	$regex   = '/(\[[^\[\]]*\])(\([^\(\)]*\))/';

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = $matches[1];
		$link           = $matches[2];
		$class          = '';
		$page_id        = '';

		// Trim brackets.
		$label = trim( substr( $label, 1, -1 ) );
		$link  = trim( substr( $link, 1, -1 ) );

		// Proceed only if label or link is not empty.
		if ( ! empty( $label ) || ! empty( $link ) ) {

			// Use hash(#) if the link is empty.
			if ( empty( $link ) ) {
				$link = '#';
			}

			// Use link as label if it's empty.
			if ( empty( $label ) ) {
				$label = $link;
			}

			// See if it's a link to a local page.
			if ( strpos( $link, '?' ) === 0 ) {
				$class .= ' evf-privacy-policy-local-page-link';

				// Extract page id.
				$page_ids = evf_extract_page_ids( $link );

				if ( false !== $page_ids ) {
					$page_id = $page_ids[0];
					$link    = get_page_link( $page_id );

					if ( empty( $link ) ) {
						$link = '#';
					}
				}
			}

			// Insert hyperlink html.
			if ( true === $use_no_a_tag ) {
				$html = $link;
			} else {
				$html = sprintf( '<a data-page-id="%s" target="_blank" rel="noopener noreferrer nofollow" href="%s" class="%s">%s</a>', $page_id, $link, $class, $label );
			}
			$text = str_replace( $matched_string, $html, $text );
		} else {
			// If both label and link are empty then replace it with empty string.
			$text = str_replace( $matched_string, '', $text );
		}
	}

	return $text;
}

/**
 * Process italic syntaxes in a text.
 * The syntax used for italic text is: `text`
 * Just wrap the text with back tick characters. To escape a backtick insert a backslash(\) before the character like "\`".
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function evf_process_italic_syntax( $text ) {
	$matches = array();
	$regex   = '/`[^`]+`/';
	$text    = str_replace( '\`', '<&&&&&>', $text ); // To preserve an escaped special character '`'.

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = substr( trim( $matched_string ), 1, -1 );
		$html           = sprintf( '<i>%s</i>', $label );
		$text           = str_replace( $matched_string, $html, $text );
	}

	return str_replace( '<&&&&&>', '`', $text );
}

/**
 * Process bold syntaxes in a text.
 * The syntax used for bold text is: *text*
 * Just wrap the text with asterisk characters. To escape an asterisk insert a backslash(\) before the character like "\*".
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function evf_process_bold_syntax( $text ) {
	$matches = array();
	$regex   = '/\*[^*]+\*/';
	$text    = str_replace( '\*', '<&&&&&>', $text ); // To preserve an escaped special character '*'.

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = substr( trim( $matched_string ), 1, -1 );
		$html           = sprintf( '<b>%s</b>', $label );
		$text           = str_replace( $matched_string, $html, $text );
	}

	return str_replace( '<&&&&&>', '*', $text );
}

/**
 * Process underline syntaxes in a text.
 * The syntax used for bold text is: __text__
 * Wrap the text with double underscore characters. To escape an underscore insert a backslash(\) before the character like "\_".
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function evf_process_underline_syntax( $text ) {
	$matches = array();
	$regex   = '/__[^_]+__/';
	$text    = str_replace( '\_', '<&&&&&>', $text ); // To preserve an escaped special character '_'.

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = substr( trim( $matched_string ), 2, -2 );
		$html           = sprintf( '<u>%s</u>', $label );
		$text           = str_replace( $matched_string, $html, $text );
	}

	$text = str_replace( '<&&&&&>', '_', $text );
	return $text;
}

/**
 * It replaces `\n` characters with `<br/>` tag because new line `\n` character is not supported in html.
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function evf_process_line_breaks( $text ) {
	return str_replace( "\n", '<br/>', $text );
}

/**
 * Check whether the current page is in AMP mode or not.
 * We need to check for specific functions, as there is no special AMP header.
 *
 * @since 1.8.4
 *
 * @param bool $check_theme_support Whether theme support should be checked. Defaults to true.
 *
 * @return bool
 */
function evf_is_amp( $check_theme_support = true ) {

	$is_amp = false;

	if (
	   // AMP by Automattic.
	   ( function_exists( 'amp_is_request' ) && amp_is_request() ) ||
	   // Better AMP.
	   ( function_exists( 'is_better_amp' ) && is_better_amp() )
	) {
		$is_amp = true;
	}

	if ( $is_amp && $check_theme_support ) {
		$is_amp = current_theme_supports( 'amp' );
	}

	return apply_filters( 'evf_is_amp', $is_amp );
}

/**
 * EVF KSES.
 *
 * @since 1.8.2.1
 *
 * @param string $context Context.
 */
function evf_get_allowed_html_tags( $context = '' ) {

	$post_tags = wp_kses_allowed_html( 'post' );
	if ( 'builder' === $context ) {
		$builder_tags = get_transient( 'evf-builder-tags-list' );
		if ( ! empty( $builder_tags ) ) {
			return $builder_tags;
		}
		$allowed_tags = evf_get_json_file_contents( 'assets/allowed_tags/allowed_tags.json', true );
		if ( ! empty( $allowed_tags ) ) {
			foreach ( $allowed_tags as $tag => $args ) {
				if ( array_key_exists( $tag, $post_tags ) ) {
					foreach ( $args as $arg => $value ) {
						if ( ! array_key_exists( $arg, $post_tags[ $tag ] ) ) {
							$post_tags[ $tag ][ $arg ] = true;
						}
					}
				} else {
					$post_tags[ $tag ] = $args;
				}
			}
			set_transient( 'evf-builder-tags-list', $post_tags, DAY_IN_SECONDS );
		}
		return $post_tags;
	}

	return wp_parse_args(
		$post_tags,
		array(
			'input'    => array(
				'type'  => true,
				'name'  => true,
				'value' => true,
			),
			'select'   => array(
				'name' => true,
				'id'   => true,
			),
			'option'   => array(
				'value'    => true,
				'selected' => true,
			),
			'textarea' => array(
				'style' => true,
			),
		)
	);
}

/**
 * Parse Builder Post Data.
 *
 * @param mixed $post_data Post Data.
 *
 * @since 1.8.2.2
 */
function evf_sanitize_builder( $post_data = array() ) {

	if ( empty( $post_data ) || ! is_array( $post_data ) ) {
		return array();
	}

	$form_data = array();
	foreach ( $post_data as $data_key => $data ) {
		$name = sanitize_text_field( $data->name );
		if ( preg_match( '/\<.*\>/', $data->value ) ) {
			$value = wp_kses_post( $data->value );
		} elseif ( 'settings[external_url]' === $data->name ) {
			$value = esc_url_raw( $data->value );
		} elseif ( 'settings[email][connection_1][evf_email_message]' === $data->name ) {
			$value = wp_kses_post( $data->value );
		} else {
			$value = sanitize_text_field( $data->value );
		}

		$form_data[ sanitize_text_field( $data_key ) ] = (object) array(
			'name'  => $name,
			'value' => $value,
		);
	}
	return $form_data;
}

/**
 * Entry Post Data.
 *
 * @param mixed $entry Post Data.
 *
 * @since 1.8.2.2
 */
function evf_sanitize_entry( $entry = array() ) {
	if ( empty( $entry ) || ! is_array( $entry ) || empty( $entry['form_fields'] ) ) {
		return $entry;
	}

	$form_id   = absint( $entry['id'] );
	$form_data = evf()->form->get( $form_id, array( 'contents_only' => true ) );

	if ( ! $form_data ) {
		return array();
	}

	$form_data = evf_decode( $form_data->post_content );

	$form_fields = $form_data['form_fields'];

	if ( empty( $form_fields ) ) {
		return array();
	}

	foreach ( $form_fields as $key => $field ) {
		$key = sanitize_text_field( $key );
		if ( array_key_exists( $key, $entry['form_fields'] ) ) {
			switch ( $field['type'] ) {
				case 'email':
					if ( isset( $entry['form_fields'][ $key ]['primary'] ) ) {
						$entry['form_fields'][ $key ]['primary']   = sanitize_email( $entry['form_fields'][ $key ]['primary'] );
						$entry['form_fields'][ $key ]['secondary'] = sanitize_email( $entry['form_fields'][ $key ]['secondary'] );
					} else {
						$entry['form_fields'][ $key ] = sanitize_email( $entry['form_fields'][ $key ] );
					}
					break;
				case 'file-upload':
				case 'signature':
				case 'image-upload':
					$entry['form_fields'][ $key ] = is_array( $entry['form_fields'][ $key ] ) ? $entry['form_fields'][ $key ] : esc_url_raw( $entry['form_fields'][ $key ] );
					break;
				case 'textarea':
				case 'html':
				case 'privacy-policy':
				case 'wysiwug':
					$entry['form_fields'][ $key ] = wp_kses_post( $entry['form_fields'][ $key ] );
					break;
				case 'repeater-fields':
					$entry['form_fields'][ $key ] = $entry['form_fields'][ $key ];
					break;
				default:
					if ( is_array( $entry['form_fields'][ $key ] ) ) {
						foreach ( $entry['form_fields'][ $key ] as $field_key => $value ) {
							$field_key                                  = sanitize_text_field( $field_key );
							$entry['form_fields'][ $key ][ $field_key ] = sanitize_text_field( $value );
						}
					} else {
						$entry['form_fields'][ $key ] = sanitize_text_field( $entry['form_fields'][ $key ] );
					}
			}
		}
		return $entry;
	}
}

/**
 * EVF Get json file contents.
 *
 * @param mixed $file File path.
 * @param mixed $to_array Returned data in array.
 */
function evf_get_json_file_contents( $file, $to_array = false ) {
	if ( $to_array ) {
		return json_decode( evf_file_get_contents( $file ), true );
	}
	return json_decode( evf_file_get_contents( $file ) );
}

/**
 * EVF file get contents.
 *
 * @param mixed $file File path.
 */
function evf_file_get_contents( $file ) {
	if ( $file ) {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		$local_file = preg_replace( '/\\\\|\/\//', '/', plugin_dir_path( EVF_PLUGIN_FILE ) . $file );
		if ( $wp_filesystem->exists( $local_file ) ) {
			$response = $wp_filesystem->get_contents( $local_file );
			return $response;
		}
	}
	return;
}
