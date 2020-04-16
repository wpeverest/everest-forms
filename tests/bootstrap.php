<?php
/**
 * Everest Forms PHPUnit Bootstrapper Class.
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */

// Load the Everest Forms Class and Class Params.
require_once __DIR__ . '/src/includes/ClassEVFLoader.php';

// Load abstract Unit Test class.
require_once __DIR__ . '/src/includes/ClassEVFUnitTest.php';

/**
 * Class EVF_Tests Bootstrap Class for Unit Tests.
 */
final class EVF_Tests {

	/** @var EVF_Tests  instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/** @var EverestForms instance of Everest Forms */
	public $evf;

	/**
	 * Constructor function for initializing the instance.
	 */
	public function __construct() {

		ini_set( 'display_errors', 'on' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted
		error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) { // phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
			$_SERVER['SERVER_NAME'] = 'localhost'; // phpcs:enable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		}

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir, 1 );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';

		// load test function so tests_add_filter() is available.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load Everest Forms.
		tests_add_filter( 'muplugins_loaded', array( $this, 'evf_load' ) );

		// install Everest Forms.
		tests_add_filter( 'setup_theme', array( $this, 'evf_install' ) );

		// load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';
	}

	/**
	 * Load Everest Forms.
	 */
	public function evf_load() {
		define( 'EVF_LOADED_TEST', true );
		require_once $this->plugin_dir . '/everest-forms.php';
		$this->evf = EverestForms::instance();
	}

	/**
	 * Install Everest Forms and initialize all install routines.
	 */
	public function evf_install() {

		echo esc_html( 'Installing Everest Forms ...' . PHP_EOL );

		// Clean existing install first.
		define( 'WP_UNINSTALL_PLUGIN', true );
		require_once $this->plugin_dir . '/includes/class-evf-install.php';

		// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			$GLOBALS['wp_roles']->reinit();
		} else {
			$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wp_roles();
		}
	}

	/**
	 * Load test class files.
	 */
	public function includes() {

		// Load the Everest Forms Class and Class Params.
		require_once __DIR__ . '/src/includes/ClassEVFLoader.php';

		// Load abstract Unit Test class.
		require_once __DIR__ . '/src/includes/ClassEVFUnitTest.php';

	}

	/**
	 * Get the singleton class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

// Initialize bootstrapped instance.
// It can be called by any test functions.
EVF_Tests::instance();
