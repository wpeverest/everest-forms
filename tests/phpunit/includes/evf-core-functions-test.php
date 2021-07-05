<?php
/**
 * Test for the core functions.
 *
 * @since 1.0.0
 */
class Core_Functions_Tests extends WP_UnitTestCase {

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

	/**
	 * Test evf process hyperlink syntax function.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_evf_process_hyperlink_syntax() {
		$text = evf_process_hyperlink_syntax( 'Hello', false );
		$this->assertEquals( 'Hello', $text, 'Value shoud not change when hyperlink not exists.' );

		$text = evf_process_hyperlink_syntax( 'Hello', true );
		$this->assertEquals( 'Hello', $text, 'Value shoud not change when hyperlink not exists.' );
	}

	/**
	 * Test process syntaxes function.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_evf_process_syntaxes() {
		$text = evf_process_syntaxes( 'label', true, true );
		$this->assertEquals( 'label', $text, 'Value shoud not change.' );

		$text = evf_process_syntaxes( 'label', false, true );
		$this->assertEquals( 'label', $text, 'Value shoud not change.' );

		$text = evf_process_syntaxes( 'label', true, false );
		$this->assertEquals( 'label', $text, 'Value shoud not change.' );

		$text = evf_process_syntaxes( 'label', false, false );
		$this->assertEquals( 'label', $text, 'Value shoud not change.' );
	}

	/**
	 * Test extract page ids function.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_evf_extract_page_ids() {
		$page_id = evf_extract_page_ids('https://wpeverest.me/page_id=20' );
		$this->assertEquals( array(20), $page_id, 'Page id should extract from the text' );
	}

	/**
	 * Test process italic syntax function.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_evf_process_italic_syntax() {
		$text = evf_process_italic_syntax("Label" );
		$this->assertEquals("Label", $text, 'Value should change' );
	}

	/**
	 * Test date range function.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_evf_date_range() {

		$dates   = evf_date_range( '07/04/2021', '07/06/2021', '+1 day', 'Y/m/d' );
		$this->assertEquals( array('2021/07/04','2021/07/05','2021/07/06'), $dates, 'Date List should be shown from the first day to last with particular format step by 1 day' );

	}

	/**
	 * Test parse args function.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function test_evf_parse_args(){
		$args = array(
			'abc' => array( 123, 124 ),
			'xyz' => 245
		);
		$defaults  = array( 'pqr' => 526 );
		$result   = evf_parse_args( $args, $defaults );
		$this->assertEquals(
			array(
				'pqr' => 526,
			   	'abc' => array( 123, 124 ),
			    'xyz' => 245
			), $result, 'Value should change' );

	}
}
