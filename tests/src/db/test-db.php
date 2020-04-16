<?php
/**
 * Everest Forms DBTest Class
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */

 /**
  * Unit test class DBTest.
  */
final class DBTest extends EVFTest {

	/**
	 * Run the test routines.
	 */
	public function test_db() {
		global $wpdb;

		// List of tables that are currently present in the test sandbox.
		$list_tables = array();
		foreach ( $wpdb->get_results( 'SHOW TABLES', ARRAY_N ) as $singleTable ) {
			$list_tables[] = $singleTable[0];
		}

		// List of expected db tables.
		$expected_db = array(
			'evf_entries',
			'evf_entrymeta',
			'evf_sessions',
		);

		try {
			foreach ( $expected_db as $singleTable ) {

				// Check if the db exists, if not, quit immediately and inform.
				if ( in_array( "{$wpdb->prefix}{$singleTable}", array_values( $list_tables ), true ) ) {
					$this->print_t( "Assert Database table : {$singleTable}" );
					$this->assertTrue( true );
				} else {
					throw new Exception(
						"Couldn't find the required table : {$wpdb->prefix}{$singleTable} . Please investigate."
					);
				}
			}
		} catch ( Exception $e ) {
			$this->setVerboseErrorHandler();
			$this->JITReporter();
			$this->assertOutput( $e->getMessage() );
		}
	}

	/**
	 * Run the options test routines.
	 */
	public function test_db_options() {

		// Everest form instance.
		$instance = EVF_Tests::instance();

		// List of expected db options.
		// Since these are already cross checked with stable version in readme.
		$expected_options = array(
			'everest_forms_version'    => $instance->evf->version,
			'everest_forms_db_version' => $instance->evf->version,
		);

		try {
			foreach ( $expected_options as $single_option => $single_value ) {
				$db_option = get_option( $single_option, false );

				// Basically, find out if the consistency with version's there.
				if ( $single_value === $db_option ) {
					$this->print_t( "Assert Database option : {$single_option}" . PHP_EOL . "{$single_option} - Reported version : {$single_value}" );
					$this->assertTrue( true );
				} else {
					throw new Exception( "Could not assert {$single_option} option. Please investigate." );
				}
			}
		} catch ( Exception $e ) {
			$this->setVerboseErrorHandler();
			$this->JITReporter();
			$this->assertOutput( $e->getMessage() );
		}
	}
}
