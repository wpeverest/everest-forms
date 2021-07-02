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
	public function test_evf_has_static_instance() {
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

	/**
	 * Test for everst_forms_loaded hook is called while initlaize the main EVF class.
	 *
	 * @return void
	 */
	public function test_everst_forms_loaded_hooks_is_called() {
		$this->assertSame( 1, did_action( 'everest_forms_loaded' ) );
	}

	/**
	 * Hooks are initialized.
	 *
	 * @return void
	 */
	public function test_hooks_are_initialized() {
		$this->assertEquals( 11, has_action( 'after_setup_theme', array( $this->evf, 'include_template_functions' ) ) );
		$this->assertEquals( 0, has_action( 'init', array( $this->evf, 'init' ) ) );
		$this->assertEquals( 0, has_action( 'init', array( $this->evf, 'form_fields' ) ) );
		$this->assertEquals( 0, has_action( 'init', array( 'EVF_Shortcodes', 'init' ) ) );
		$this->assertEquals( 0, has_action( 'switch_blog', array( $this->evf, 'wpdb_table_fix' ) ) );
	}

	/**
	 * Test the request is not admin.
	 *
	 * @return void
	 */
	public function test_request_is_not_admin() {
		$this->assertFalse( $this->invoke_method( $this->evf, 'is_request', array( 'admin' ) ) );
	}

	/**
	 * Test the request is not ajax.
	 *
	 * @return void
	 */
	public function test_request_is_not_ajax() {
		$this->assertFalse( $this->invoke_method( $this->evf, 'is_request', array( 'ajax' ) ) );
	}

	/**
	 * Test request is not cron.
	 *
	 * @return void
	 */
	public function test_request_is_not_cron() {
		$this->assertFalse( $this->invoke_method( $this->evf, 'is_request', array( 'cron' ) ) );
	}

	/**
	 * Test request is frontend.
	 *
	 * @return void
	 */
	public function test_request_is_frontend() {
		$this->assertTrue( $this->invoke_method( $this->evf, 'is_request', array( 'frontend' ) ) );
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $method_name Method name to call
	 * @param array  $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	public function invoke_method( &$object, $method_name, array $parameters = array() ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}
}
