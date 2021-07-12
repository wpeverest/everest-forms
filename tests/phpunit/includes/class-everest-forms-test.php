<?php
/**
 * Test for the core class.
 *
 * @since 1.8.0
 */
class Core_Class_Tests extends WP_UnitTestCase {

	/**
	 * EverestForms instance.
	 *
	 * @var \EverestForms instance
	 */
	protected $evf;

	/**
	 * Setup test.
	 *
	 * @since 1.8
	 */
	public function setUp() {
		$this->evf = evf();
	}

	/**
	 * Test EVF has static instance.
	 *
	 * @since 1.8
	 */
	public function test_evf_instance() {
		$this->assertClassHasStaticAttribute( 'instance', 'EverestForms' );
	}

	/**
	 * Test that all EVF constants are set.
	 *
	 * @since 1.8
	 */
	public function test_constants() {
		// $this->assertEquals( plugin_dir_path( __FILE__ ) . 'everest-forms.php', EVF_PLUGIN_FILE );
		// $this->assertEquals( plugin_basename( EVF_PLUGIN_FILE ), EVF_PLUGIN_FILE );
		$this->assertEquals( $this->evf->version, EVF_VERSION );
		// $this->assertNotEquals( EVF_LOG_DIR, '' );
	}

	/**
	 * Test class instance.
	 *
	 * @since 1.8
	 */
	public function test_evf_class_instances() {
		$this->assertInstanceOf( 'EverestForms', $this->evf );
		$this->assertInstanceOf( 'EVF_Integrations', $this->evf->integrations );
		$this->assertInstanceOf( 'EVF_Deprecated_Action_Hooks', $this->evf->deprecated_hook_handlers['actions'] );
		$this->assertInstanceOf( 'EVF_Deprecated_Filter_Hooks', $this->evf->deprecated_hook_handlers['filters'] );
	}
}
