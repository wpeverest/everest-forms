<?php
/**
 * Everest Forms FilterTest Class
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */

// Load the filters class.
require_once dirname( __DIR__, 1 ) . '/includes/ClassEVF_Filters.php';

 /**
  * Unit test class FilterTest.
  */
final class FilterTest extends EVFTest {

	/**
	 * Run the test routines.
	 */
	public function test_filter_hooks() {
		try {
			$filters = EVF_Filter::evf_master_filters();

			// Iterate and check if all important filters are working.
			foreach ( $filters as $singleFilter ) {
				$filter_existence = has_filter( $singleFilter );
				if ( $filter_existence ) {
					$this->print_t( "Assert filter : {$singleFilter}" );
					$this->assertTrue( $filter_existence );
				} else {
					// Inform that a required filter was not found.
					throw new Exception(
						"Filter likely unregistered : {$singleFilter} . Please investigate."
					);
				}
			}
		} catch ( Exception $e ) {
			$this->setVerboseErrorHandler();
			$this->JITReporter();
			$this->assertOutput( $e->getMessage() );
		}
	}
}
