<?php
/**
 * Everest Forms LogEntries Class
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */

 /**
  * Unit test class LogEntries.
  */
final class LogEntries extends EVFTest {

	/**
	 * Run the test routines.
	 */
	public function test_debug_log() {

		// Everest form instance.
		$instance = EVF_Tests::instance();

		// Debug.log path for the current testing instance.
		$errorlog_path = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress/wp-content/debug.log';

		try {
			// Test to see if any of the prior tests caused a warning or notice in error.log.
			if ( $log = fopen( $errorlog_path, 'r' ) ) {
				$contents = filesize( $errorlog_path ) ? fread( $log, filesize( $errorlog_path ) ) : '';
				fclose( $log );

				// Assert only if version's right, else quit and inform.
				if ( ! $contents ) {
					$this->print_t( PHP_EOL . 'No errors were lodged in debug.log.' );
					$this->assertEquals( $contents, '' );
				} else {
					throw new Exception( $contents );
				}
			} else {
				$this->print_t( PHP_EOL . 'No errors were lodged in debug.log.' );
				$this->assertTrue( true );
			}
		} catch ( Exception $e ) {
			$this->setVerboseErrorHandler();
			$this->JITReporter();
			$this->assertOutput( $e->getMessage() );
		}
	}
}
