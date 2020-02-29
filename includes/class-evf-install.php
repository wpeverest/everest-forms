<?php
/**
 * Installation related functions and actions.
 *
 * @package EverestForms\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Install Class.
 */
class EVF_Install {

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
	 */
	private static $db_updates = array(
		'1.0.0' => array(
			'evf_update_100_db_version',
		),
		'1.0.1' => array(
			'evf_update_101_db_version',
		),
		'1.0.2' => array(
			'evf_update_102_db_version',
		),
		'1.0.3' => array(
			'evf_update_103_db_version',
		),
		'1.1.0' => array(
			'evf_update_110_update_forms',
			'evf_update_110_db_version',
		),
		'1.1.6' => array(
			'evf_update_116_delete_options',
			'evf_update_116_db_version',
		),
		'1.2.0' => array(
			'evf_update_120_db_rename_options',
			'evf_update_120_db_version',
		),
		'1.3.0' => array(
			'evf_update_130_db_version',
		),
		'1.4.0' => array(
			'evf_update_140_db_multiple_email',
			'evf_update_140_db_version',
		),
		'1.4.4' => array(
			'evf_update_144_delete_options',
			'evf_update_144_db_version',
		),
		'1.4.9' => array(
			'evf_update_149_db_rename_options',
			'evf_update_149_no_payment_options',
			'evf_update_149_db_version',
		),
		'1.5.0' => array(
			'evf_update_150_field_datetime_type',
			'evf_update_150_db_version',
		),
		'1.6.0' => array(
			'evf_update_160_db_version',
		),
	);

	/**
	 * Background update class.
	 *
	 * @var object
	 */
	private static $background_updater;

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'plugin_action_links_' . EVF_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
	}

	/**
	 * Init background updates
	 */
	public static function init_background_updater() {
		include_once dirname( __FILE__ ) . '/class-evf-background-updater.php';
		self::$background_updater = new EVF_Background_Updater();
	}

	/**
	 * Check EverestForms version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'everest_forms_version' ), evf()->version, '<' ) ) {
			self::install();
			do_action( 'everest_forms_updated' );
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_everest_forms'] ) ) {
			check_admin_referer( 'evf_db_update', 'evf_db_update_nonce' );
			self::update();
			EVF_Admin_Notices::add_notice( 'update' );
		}
		if ( ! empty( $_GET['force_update_everest_forms'] ) ) {
			do_action( 'wp_' . get_current_blog_id() . '_evf_updater_cron' );
			wp_safe_redirect( admin_url( 'admin.php?page=evf-settings' ) );
			exit;
		}
	}

	/**
	 * Install EVF.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'evf_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'evf_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		evf_maybe_define_constant( 'EVF_INSTALLING', true );

		self::remove_admin_notices();
		self::create_options();
		self::create_tables();
		self::create_roles();
		self::setup_environment();
		self::create_cron_jobs();
		self::create_files();
		self::create_forms();
		self::maybe_enable_setup_wizard();
		self::update_evf_version();
		self::maybe_update_db_version();
		self::maybe_add_activated_date();

		delete_transient( 'evf_installing' );

		do_action( 'everest_forms_flush_rewrite_rules' );
		do_action( 'everest_forms_installed' );
	}

	/**
	 * Reset any notices added to admin.
	 */
	private static function remove_admin_notices() {
		include_once dirname( __FILE__ ) . '/admin/class-evf-admin-notices.php';
		EVF_Admin_Notices::remove_all_notices();
	}

	/**
	 * Setup EVF environment - post types, taxonomies, endpoints.
	 */
	private static function setup_environment() {
		EVF_Post_Types::register_post_types();
	}

	/**
	 * Is this a brand new EVF install?
	 *
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'everest_forms_version', null ) ) && is_null( get_option( 'everest_forms_db_version', null ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @return boolean
	 */
	public static function needs_db_update() {
		$current_db_version = get_option( 'everest_forms_db_version', null );
		$updates            = self::get_db_update_callbacks();
		$update_versions    = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}

	/**
	 * See if we need the wizard or not.
	 */
	private static function maybe_enable_setup_wizard() {
		if ( apply_filters( 'everest_forms_enable_setup_wizard', true ) ) {
			set_transient( '_evf_activation_redirect', 1, 30 );
		}
	}

	/**
	 * See if we need to show or run database updates during install.
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'everest_forms_enable_auto_update_db', false ) ) {
				self::init_background_updater();
				self::update();
			} else {
				EVF_Admin_Notices::add_notice( 'update' );
			}
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Store the initial plugin activation date during install.
	 */
	private static function maybe_add_activated_date() {
		$activated_date = get_option( 'everest_forms_activated', '' );

		if ( empty( $activated_date ) ) {
			update_option( 'everest_forms_activated', time() );
		}
	}

	/**
	 * Update EVF version to current.
	 */
	private static function update_evf_version() {
		delete_option( 'everest_forms_version' );
		add_option( 'everest_forms_version', evf()->version );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'everest_forms_db_version' );
		$logger             = evf_get_logger();
		$update_queued      = false;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$logger->info(
						sprintf( 'Queuing %s - %s', $version, $update_callback ),
						array( 'source' => 'evf_db_updates' )
					);
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New EverestForms DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'everest_forms_db_version' );
		add_option( 'everest_forms_db_version', is_null( $version ) ? evf()->version : $version );
	}

	/**
	 * Add more cron schedules.
	 *
	 * @param  array $schedules List of WP scheduled cron jobs.
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'everest-forms' ),
		);
		return $schedules;
	}

	/**
	 * Create cron jobs (clear them first).
	 */
	private static function create_cron_jobs() {
		wp_clear_scheduled_hook( 'everest_forms_cleanup_logs' );
		wp_clear_scheduled_hook( 'everest_forms_cleanup_sessions' );
		wp_schedule_event( time() + ( 3 * HOUR_IN_SECONDS ), 'daily', 'everest_forms_cleanup_logs' );
		wp_schedule_event( time() + ( 6 * HOUR_IN_SECONDS ), 'twicedaily', 'everest_forms_cleanup_sessions' );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults.
		include_once dirname( __FILE__ ) . '/admin/class-evf-admin-settings.php';

		$settings = EVF_Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			foreach ( $subsections as $subsection ) {
				foreach ( $section->get_settings( $subsection ) as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/**
		 * Before updating with DBDELTA, add fields column to entries table schema.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}evf_entries';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}evf_entries` LIKE 'fields';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}evf_entries ADD `fields` longtext NULL AFTER `referer`;" );
			}
		}

		/**
		 * Change wp_evf_sessions schema to use a bigint auto increment field
		 * instead of char(32) field as the primary key. Doing this change primarily
		 * as it should reduce the occurrence of deadlocks, but also because it is
		 * not a good practice to use a char(32) field as the primary key of a table.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}evf_sessions'" ) ) {
			if ( ! $wpdb->get_var( "SHOW KEYS FROM {$wpdb->prefix}evf_sessions WHERE Key_name = 'PRIMARY' AND Column_name = 'session_id'" ) ) {
				$wpdb->query(
					"ALTER TABLE `{$wpdb->prefix}evf_sessions` DROP PRIMARY KEY, DROP KEY `session_id`, ADD PRIMARY KEY(`session_id`), ADD UNIQUE KEY(`session_key`)"
				);
			}
		}

		dbDelta( self::get_schema() );
	}

	/**
	 * Get Table schema.
	 *
	 * When adding or removing a table, make sure to update the list of tables in EVF_Install::get_tables().
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}evf_entries (
  entry_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  user_device varchar(100) NOT NULL,
  user_ip_address VARCHAR(100) NULL DEFAULT '',
  referer text NOT NULL,
  fields longtext NULL,
  status varchar(20) NOT NULL,
  viewed tinyint(1) NOT NULL DEFAULT '0',
  starred tinyint(1) NOT NULL DEFAULT '0',
  date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (entry_id),
  KEY form_id (form_id)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}evf_entrymeta (
  meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY entry_id (entry_id),
  KEY meta_key (meta_key(32))
) $charset_collate;
CREATE TABLE {$wpdb->prefix}evf_sessions (
  session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_key char(32) NOT NULL,
  session_value longtext NOT NULL,
  session_expiry BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (session_id),
  UNIQUE KEY session_key (session_key)
) $charset_collate;
		";

		return $tables;
	}

	/**
	 * Return a list of EverestForms tables. Used to make sure all UM tables are dropped when uninstalling the plugin
	 * in a single site or multi site environment.
	 *
	 * @return array UM tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}evf_entries",
			"{$wpdb->prefix}evf_entrymeta",
			"{$wpdb->prefix}evf_sessions",
		);

		return $tables;
	}

	/**
	 * Drop EverestForms tables.
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param  array $tables List of tables that will be deleted by WP.
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		return array_merge( $tables, self::get_tables() );
	}

	/**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Get capabilities for EverestForms - these are assigned to admin during installation or reset.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_everest_forms',
		);

		$capability_types = array( 'everest_form' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type.
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms.
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
			);
		}

		return $capabilities;
	}

	/**
	 * Remove EverestForms roles.
	 */
	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Create default contact form.
	 */
	public static function create_forms() {
		$form_count = wp_count_posts( 'everest_form' );

		if ( empty( $form_count->publish ) ) {
			include_once dirname( __FILE__ ) . '/templates/contact.php';

			// Create a form.
			$form_id = wp_insert_post(
				array(
					'post_title'   => esc_html__( 'Contact Form', 'everest-forms' ),
					'post_status'  => 'publish',
					'post_type'    => 'everest_form',
					'post_content' => '{}',
				)
			);

			if ( $form_id ) {
				wp_update_post(
					array(
						'ID'           => $form_id,
						'post_content' => evf_encode( array_merge( array( 'id' => $form_id ), $form_template['contact'] ) ),
					)
				);
			}

			update_option( 'everest_forms_default_form_page_id', $form_id );
		}
	}

	/**
	 * Create files/directories.
	 */
	private static function create_files() {
		// Bypass if filesystem is read-only and/or non-standard upload system is used.
		if ( apply_filters( 'everest_forms_install_skip_create_files', false ) ) {
			return;
		}

		// Install files and folders for uploading files and prevent hotlinking.
		$files = array(
			array(
				'base'    => EVF_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => EVF_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
				}
			}
		}
	}

	/**
	 * Display action links in the Plugins list table.
	 *
	 * @param  array $actions Plugin Action links.
	 * @return array
	 */
	public static function plugin_action_links( $actions ) {
		$new_actions = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=evf-settings' ) . '" aria-label="' . esc_attr__( 'View Everest Forms Settings', 'everest-forms' ) . '">' . esc_html__( 'Settings', 'everest-forms' ) . '</a>',
		);

		return array_merge( $new_actions, $actions );
	}

	/**
	 * Display row meta in the Plugins list table.
	 *
	 * @param  array  $plugin_meta Plugin Row Meta.
	 * @param  string $plugin_file Plugin Row Meta.
	 * @return array
	 */
	public static function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( EVF_PLUGIN_BASENAME === $plugin_file ) {
			$new_plugin_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'everest_forms_docs_url', 'https://docs.wpeverest.com/documentation/plugins/everest-forms/' ) ) . '" aria-label="' . esc_attr__( 'View Everest Forms documentation', 'everest-forms' ) . '">' . esc_html__( 'Docs', 'everest-forms' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'everest_forms_support_url', 'https://wordpress.org/support/plugin/everest-forms/' ) ) . '" aria-label="' . esc_attr__( 'Visit free customer support', 'everest-forms' ) . '">' . esc_html__( 'Free support', 'everest-forms' ) . '</a>',
			);

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;
	}
}

EVF_Install::init();
