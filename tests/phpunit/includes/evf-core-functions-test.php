<?php
/**
 * Test for the core functions.
 *
 * @since 1.0.0
 */
class Core_Functions_Tests extends WP_UnitTestCase {

	function test_wordpress_and_plugin_are_loaded() {
		$this->assertTrue( function_exists( 'do_action' ) );
		$this->assertTrue( function_exists( 'evf' ) );
		$this->assertTrue( class_exists( 'EverestForms' ) );
	}
}
