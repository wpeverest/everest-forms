<?php
/**
 * Test for the core functions.
 *
 * @since 1.0.0
 */

class Core_Functions_Tests extends \WP_UnitTestCase {

	public function setUp() {
		\WP_Mock::setUp();
	}

	public function tearDown() {
		\WP_Mock::tearDown();
	}

	public function test_wordpress_and_plugin_are_loaded() {
		$this->assertTrue( function_exists( 'do_action' ) );
		$this->assertTrue( function_exists( 'evf' ) );
		$this->assertTrue( class_exists( 'EverestForms' ) );
	}

	/**
	 * Test evf string translation function.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_evf_string_translation() {
		$value = evf_string_translation( 10, 'first_name', 'john', 'home' );
		$this->assertEquals( 'john', $value, 'Value should not change when WPML doens\'t exit' );

		\WP_Mock::userFunction(
			'icl_t',
			array(
				'args'   => array( 'everest_forms_10', 'first_name', 'john' ),
				'times'  => 1,
				'return' => 'doe',
			)
		);
		$value = evf_string_translation( 10, 'first_name', 'john' );
		$this->assertEquals( 'doe', $value, 'Value shoud change when WPML exists' );
	}
}
