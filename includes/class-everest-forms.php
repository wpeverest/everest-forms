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
	public $version = '1.5.6';

	/**
	 * The single instance of the class.
	 *
	 * @var   EverestForms
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Session instance.
	 *
	 * @var EVF_Session|EVF_Session_Handler
	 */
	public $session = null;

	/**
	 * The form data handler instance.
	 *
	 * @var object everest_forms_Form_Handler
	 */
	public $form;

	/**
	 * The entry data handler instance.
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

	/**
	 * Integrations instance.
	 *
	 * @var EVF_Integrations
	 */
	public $integrations = null;

	/**
	 * Array of deprecated hook handlers.
	 *
	 * @var array of EVF_Deprecated_Hooks
	 */
	public $deprecated_hook_handlers = array();

	/**
	 * Main EverestForms Instance.
	 *
	 * Ensures only one instance of EverestForms is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see    EVF()
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
	 * @since 1.0.0
	 */
	public function __clone() {
		evf_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'everest-forms' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		evf_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'everest-forms' ), '1.0.0' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'form_fields' ), true ) ) {
			return $this->$key();
		}
	}

	/**
	 * EverestForms Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->define_tables();
		$this->includes();
		$this->init_hooks();
		add_action( 'plugins_loaded', array( $this, 'objects' ), 1 );

		do_action( 'everest_forms_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_activation_hook( EVF_PLUGIN_FILE, array( 'EVF_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'form_fields' ), 0 );
		add_action( 'init', array( 'EVF_Shortcodes', 'init' ), 0 );
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
	 * Register custom tables within $wpdb object.
	 */
	private function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'form_entrymeta' => 'evf_entrymeta',
		);
		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
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
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
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
		include_once EVF_ABSPATH . 'includes/abstracts/class-evf-settings-api.php';
		include_once EVF_ABSPATH . 'includes/abstracts/class-evf-integration.php';
		include_once EVF_ABSPATH . 'includes/abstracts/class-evf-log-handler.php';
		include_once EVF_ABSPATH . 'includes/abstracts/class-evf-deprecated-hooks.php';
		include_once EVF_ABSPATH . 'includes/abstracts/class-evf-session.php';
		include_once EVF_ABSPATH . 'includes/abstracts/class-evf-form-fields.php';

		/**
		 * Core classes.
		 */
		include_once EVF_ABSPATH . 'includes/evf-core-functions.php';
		include_once EVF_ABSPATH . 'includes/class-evf-post-types.php';
		include_once EVF_ABSPATH . 'includes/class-evf-install.php';
		include_once EVF_ABSPATH . 'includes/class-evf-ajax.php';
		include_once EVF_ABSPATH . 'includes/class-evf-emails.php';
		include_once EVF_ABSPATH . 'includes/class-evf-form-block.php';
		include_once EVF_ABSPATH . 'includes/class-evf-integrations.php';
		include_once EVF_ABSPATH . 'includes/class-evf-cache-helper.php';
		include_once EVF_ABSPATH . 'includes/class-evf-deprecated-action-hooks.php';
		include_once EVF_ABSPATH . 'includes/class-evf-deprecated-filter-hooks.php';
		include_once EVF_ABSPATH . 'includes/class-evf-forms-features.php';
		include_once EVF_ABSPATH . 'includes/class-evf-privacy.php';

		if ( $this->is_request( 'admin' ) ) {
			include_once EVF_ABSPATH . 'includes/admin/class-evf-admin.php';
		}

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once EVF_ABSPATH . 'includes/evf-notice-functions.php';
		include_once EVF_ABSPATH . 'includes/evf-template-hooks.php';
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

		// Load class instances.
		$this->integrations                        = new EVF_Integrations();
		$this->deprecated_hook_handlers['actions'] = new EVF_Deprecated_Action_Hooks();
		$this->deprecated_hook_handlers['filters'] = new EVF_Deprecated_Filter_Hooks();

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
		$this->form       = new EVF_Form_Handler();
		$this->task       = new EVF_Form_Task();
		$this->smart_tags = new EVF_Smart_Tags();
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
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// @todo Remove when start supporting WP 5.0 or later.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'everest_forms' );

		unload_textdomain( 'everest-forms' );
		load_textdomain( 'everest-forms', WP_LANG_DIR . '/everest-forms/everest-forms-' . $locale . '.mo' );
		load_plugin_textdomain( 'everest-forms', false, plugin_basename( dirname( EVF_PLUGIN_FILE ) ) . '/languages' );
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
		$this->define_tables();
	}

	/**
	 * Get form fields Class.
	 *
	 * @return EVF_Form_Fields
	 */
	public function form_fields() {
		return EVF_Fields::instance();
	}
}
