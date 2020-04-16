<?php
/**
 * Everest Forms Unit Test Class.
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */

use PHPUnit\Framework\TestCase;

// Initializing autoload for assertations.
require_once dirname( __FILE__, 4 ) . '/vendor/autoload.php';

/**
 * Define global path.
 */
if ( ! defined( 'EVF_WP_DIR' ) ) {
	$path = explode( 'wp-content', __DIR__ );
	if ( 2 === count( $path ) ) {
		define( 'EVF_WP_DIR', $path[0] );
	}
}

/**
 * @class EVFTest @extends PHPUnit\Framework\TestCase
 */
abstract class EVFTest extends TestCase {

	/**
	 * Instance of the test.
	 *
	 * @var Initializer
	 */
	private static $instance;

	/**
	 * Message bag for assertations.
	 *
	 * @var array
	 */
	private $messages = array();

	/**
	 * Make the stacktrace verbose if the test fails.
	 */
	protected function setVerboseErrorHandler() {
		$handler = function( $errorNumber, $errorString, $errorFile, $errorLine ) {
			print "{$errorNumber} - {$errorString} in: \n{$errorFile}:{$errorLine}\n";
		};
		set_error_handler( $handler );
	}

	/**
	 * Just in Time stack printer
	 */
	protected function JITReporter() {

		/**
		 * In case the PHP silently fails, force it to spit out trace.
		 */
		register_shutdown_function(
			function() {
				foreach ( $GLOBALS['lastStack'] as $i => $frame ) {
					print "{$i}. {$frame['file']}:{$frame['line']} in function {$frame['function']}()\n";
				}
			}
		);

		/**
		 * Register the tick for stacktrace limit.
		 */
		register_tick_function(
			function() {
				$GLOBALS['lastStack'] = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 25 );
			}
		);

		declare(ticks=1);
	}

	// Asserts both to the same one.
	protected static function assertValues( $var1, $var2, $message = '' ) {
		// assertSame() throws an exception if not true, so the following
		// won't occur unless the messages actually are the same
		$success        = print_r( $var1, true ) . ' is the same as ' . print_r( $var2, true );
		self::$messages = array_merge( self::$messages, array( $success ) );
	}

	/**
	 * Function assertOutput for verbose messaging.
	 */
	public function assertOutput( $message ) {
		$this->expectOutputString( $message );
		$this->fail( PHP_EOL . $message );
	}

	/**
	 * Function assertOutput for verbose messaging.
	 */
	public function print_t( $message, $level = 'log' ) {
		$composed_message = '';

		// Check if given foreground color found
		if ( 'log' === $level ) {
			$composed_message .= "\033[1;33m";
		} elseif ( 'error' === $level ) {
			$composed_message .= "\0331;31m";
		} else {
			return print_r(
				PHP_EOL . $message
			);
		}
		return print_r(
			PHP_EOL . "{$composed_message}{$message}\033[0m"
		);
	}
}
