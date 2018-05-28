<?php
/**
 * EverestForms setup
 *
 * @package EverestForms
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main EverestForms Class.
 *
 * @class   EverestForms
 * @version 1.0.0
 */
final class EverestForms {

	/**
	 * EverestForms version.
	 *
	 * @var string
	 */
	public $version = '1.1.7';

	/**
	 * The form data handler instance.
	 *
	 * @since 1.0.0
	 * @var object everest_forms_Form_Handler
	 */
	public $form;

	/**
	 * The entry data handler instance.
	 *
	 * @since 1.1.0
	 *
	 * @var EVF_Entry_Handler
	 */
	public $entry;

	/**
	 * The entry meta data handler instance.
	 *
	 * @since 1.1.0
	 *
	 * @var EVF_Entry_Meta_Handler
	 */
	public $entry_meta;

	/*
	 * Number of grid in form
	 */
	public $form_grid = 2;

	/**
	 * The front-end instance.
	 *
	 * @since      1.0.0
	 *
	 * @var object everest_forms_Frontend
	 */
	public $frontend;

	/**
	 * The process instance.
	 *
	 * @since      1.0.0
	 *
	 * @var object everest_forms_Process
	 */
	public $process;

	/**
	 * The smart tags instance.
	 *
	 * @since      1.0.0
	 *
	 * @var object everest_forms_Smart_Tags
	 */
	public $smart_tags;
	/**
	 * The single instance of the class.
	 *
	 * @var EverestForms
	 * @since      1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Session instance.
	 *
	 * @var EVF_Session|EVF_Session_Handler
	 */
	public $session = null;

	/**
	 * Main EverestForms Instance.
	 *
	 * Ensures only one instance of EverestForms is loaded or can be loaded.
	 *
	 * @since      1.0.0
	 * @static
	 * @see   EVF()
	 * @return EverestForms - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since      1.0.0
	 */
	public function __clone() {
		evf_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'everest-forms' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since      1.0.0
	 */
	public function __wakeup() {
		evf_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'everest-forms' ), '1.0.0' );
	}

	/**
	 * EverestForms Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
		add_action( 'plugins_loaded', array( $this, 'objects' ), 10 );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since      1.0.0
	 */
	private function init_hooks() {
		register_activation_hook( EVF_PLUGIN_FILE, array( 'EVF_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'EVF_Shortcodes', 'init' ) );
		add_action( 'init', array( 'EVF_Template_Loader', 'init' ) );
		add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 1.0.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( E_ERROR === $error['type'] ) {
			$logger = evf_get_logger();
			$logger->critical(
				$error['message'] . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
		}
	}

	/**
	 * Define EVF Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );
		$this->define( 'EVF_ABSPATH', dirname( EVF_PLUGIN_FILE ) . '/' );
		$this->define( 'EVF_PLUGIN_BASENAME', plugin_basename( EVF_PLUGIN_FILE ) );
		$this->define( 'EVF_VERSION', $this->version );
		$this->define( 'EVF_LOG_DIR', $upload_dir['basedir'] . '/evf-logs/' );
		$this->define( 'EVF_SESSION_CACHE_GROUP', 'evf_session_id' );
		$this->define( 'EVF_TEMPLATE_DEBUG_MODE', false );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		include_once EVF_ABSPATH . 'includes/class-evf-autoloader.php';

		/**
		 * Interfaces.
		 */
		include_once EVF_ABSPATH . 'includes/interfaces/class-evf-logger-interface.php';
		include_once EVF_ABSPATH . 'includes/interfaces/class-evf-log-handler-interface.php';

		/**
		 * Abstract classes.
		 */
		include_once EVF_ABSPATH . 'includes/abstracts/abstract-evf-log-handler.php';
		include_once EVF_ABSPATH . 'includes/abstracts/abstract-evf-session.php';

		/**
		 * Core classes.
		 */
		include_once EVF_ABSPATH . 'includes/evf-core-functions.php';
		include_once EVF_ABSPATH . 'includes/class-evf-post-types.php';
		include_once EVF_ABSPATH . 'includes/class-evf-install.php';
		include_once EVF_ABSPATH . 'includes/class-evf-ajax.php';
		include_once EVF_ABSPATH . 'includes/class-evf-emails.php';
		include_once EVF_ABSPATH . 'includes/class-evf-cache-helper.php';
		include_once EVF_ABSPATH . 'includes/class-evf-field-item.php';

		if ( $this->is_request( 'admin' ) ) {
			include_once EVF_ABSPATH . 'includes/admin/class-evf-admin.php';
		}

		/**
		 * Forms feature.
		 */
		require_once EVF_ABSPATH . 'includes/class-evf-forms-feature.php';

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once EVF_ABSPATH . 'includes/evf-notice-functions.php';
		include_once EVF_ABSPATH . 'includes/class-evf-template-loader.php';  // Template Loader.
		include_once EVF_ABSPATH . 'includes/class-evf-frontend-scripts.php'; // Frontend Scripts.
		include_once EVF_ABSPATH . 'includes/class-evf-shortcodes.php';       // Shortcodes class.
		include_once EVF_ABSPATH . 'includes/class-evf-session-handler.php';  // Session handler class.
	}

	/**
	 * Function used to Init EverestForms Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once EVF_ABSPATH . 'includes/evf-template-functions.php';
	}

	/**
	 * Init EverestForms when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_everest_forms_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Classes/actions loaded for the frontend and for ajax requests.
		if ( $this->is_request( 'frontend' ) ) {
			// Session class, handles session data for users - can be overwritten if custom handler is needed.
			$session_class = apply_filters( 'everest_forms_session_handler', 'EVF_Session_Handler' );
			$this->session = new $session_class();
			$this->session->init();
		}

		// Init action.
		do_action( 'everest_forms_init' );
	}

	/**
	 * Setup objects.
	 *
	 * @since      1.0.0
	 */
	public function objects() {

		// Global objects.
		$this->form = new EVF_Form_Handler;

		//$this->frontend   = new EVF_Forms_Frontend;
		$this->task = new EVF_Form_Task;
		//$this->smart_tags = new EVF_Forms_Smart_Tags;

		// Hook now that all of the EverestForms stuff is loaded.
		do_action( 'everest_forms_loaded' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/everest-forms/everest-forms-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/everest-forms-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'everest_forms' );

		unload_textdomain( 'everest-forms' );
		load_textdomain( 'everest-forms', WP_LANG_DIR . '/everest-forms/everest-forms-' . $locale . '.mo' );
		load_plugin_textdomain( 'everest-forms', false, plugin_basename( dirname( EVF_PLUGIN_FILE ) ) . '/i18n/languages' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', EVF_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( EVF_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'everest_forms_template_path', 'everest-forms/' );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Everest Forms Entry Meta - set table names.
	 */
	public function wpdb_table_fix() {
		global $wpdb;
		$wpdb->form_entrymeta = $wpdb->prefix . 'evf_entrymeta';
		$wpdb->tables[]       = 'evf_entrymeta';
	}
}
