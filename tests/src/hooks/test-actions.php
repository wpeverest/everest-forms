<?php
/**
 * Everest Forms ActionTest Class
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */

// Load the actions class.
require_once dirname( __DIR__, 1 ) . '/includes/ClassEVF_Actions.php';

 /**
  * Unit test class ActionTest.
  */
final class ActionTest extends EVFTest {

	/**
	 * Run the test routines.
	 */
	public function test_action_hooks() {
		try {
			$actions = EVF_Actions::evf_master_actions();

			// Iterate and check if all important actions are working.
			foreach ( $actions as $singleAction ) {
				$action_existence = has_action( $singleAction );
				if ( $action_existence ) {
					$this->print_t( "Assert action : {$singleAction}" );
					$this->assertTrue( $action_existence );
				} else {
					// Inform that a required action was not found.
					throw new Exception(
						"Action likely unregistered : {$singleAction} . Please investigate."
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
