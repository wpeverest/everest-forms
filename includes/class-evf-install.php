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
		add_action( 'everest_forms_plugin_background_installer', array( __CLASS__, 'background_installer' ), 10, 2 );
		add_action( 'everest_forms_theme_background_installer', array( __CLASS__, 'background_installer' ), 10, 2 );
	}

	/**
	 * Init background updates
	 */
	public static function init_background_updater() {
		include_once( dirname( __FILE__ ) . '/class-evf-background-updater.php' );
		self::$background_updater = new EVF_Background_Updater();
	}

	/**
	 * Check EverestForms version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'everest_forms_version' ) !== EVF()->version ) {
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
		self::create_forms();
		self::maybe_enable_setup_wizard();
		self::update_evf_version();
		self::maybe_update_db_version();

		delete_transient( 'evf_installing' );

		do_action( 'everest_forms_flush_rewrite_rules' );
		do_action( 'everest_forms_installed' );
	}

	/**
	 * Reset any notices added to admin.
	 */
	private static function remove_admin_notices() {
		include_once( dirname( __FILE__ ) . '/admin/class-evf-admin-notices.php' );
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
	private static function needs_db_update() {
		$current_db_version = get_option( 'everest_forms_db_version', null );
		$updates            = self::get_db_update_callbacks();

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

	/**
	 * See if we need the wizard or not.
	 */
	private static function maybe_enable_setup_wizard() {
		if ( apply_filters( 'everest_forms_enable_setup_wizard', self::is_new_install() ) ) {
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
	 * Update EVF version to current.
	 */
	private static function update_evf_version() {
		delete_option( 'everest_forms_version' );
		add_option( 'everest_forms_version', EVF()->version );
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
		add_option( 'everest_forms_db_version', is_null( $version ) ? EVF()->version : $version );
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
		wp_clear_scheduled_hook( 'everest_forms_cleanup_sessions' );
		wp_schedule_event( time(), 'twicedaily', 'everest_forms_cleanup_sessions' );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults.l
		include_once( dirname( __FILE__ ) . '/admin/class-evf-admin-settings.php' );

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

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( self::get_schema() );
	}

	/**
	 * Get Table schema.
	 *
	 * https://github.com/everest-forms/everest-forms/wiki/Database-Description/
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
	 * indexes first causes too much load on some servers/larger DB.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}evf_sessions (
  session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_key char(32) NOT NULL,
  session_value longtext NOT NULL,
  session_expiry BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (session_key),
  UNIQUE KEY session_id (session_id)
) $collate;
		";

		return $tables;
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
			'manage_everest_forms'
		);

		$capability_types = array( 'everest_form' );

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"duplicate_{$capability_type}",
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

				// Terms
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
			$form_id = wp_insert_post( array(
				'post_title'   => esc_html( 'Contact Form', 'everest-forms' ),
				'post_status'  => 'publish',
				'post_type'    => 'everest_form',
				'post_content' => '{}',
			) );

			if ( $form_id ) {
				wp_update_post( array(
					'ID'           => $form_id,
					'post_content' => evf_encode( array_merge( array( 'id' => $form_id ), $form_template['contact'] ) ),
				) );
			}

			update_option( 'evf_default_form_page_id', $form_id );
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
		if ( EVF_PLUGIN_BASENAME == $plugin_file ) {
			$new_plugin_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'everest_forms_docs_url', 'https://docs.wpeverest.com/documentation/plugins/everest-forms/' ) ) . '" aria-label="' . esc_attr__( 'View Everest Forms documentation', 'everest-forms' ) . '">' . esc_html__( 'Docs', 'everest-forms' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'everest_forms_support_url', 'https://wpeverest.com/support-forum/' ) ) . '" aria-label="' . esc_attr__( 'Visit premium customer support', 'everest-forms' ) . '">' . esc_html__( 'Premium support', 'everest-forms' ) . '</a>',
			);

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param  array $tables List of tables that will be deleted by WP.
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		global $wpdb;

		$tables[] = $wpdb->prefix . 'evf_sessions';

		return $tables;
	}

	/**
	 * Get slug from path
	 *
	 * @param  string $key Plugin relative path. Example: woocommerce/woocommerce.php.
	 * @return string
	 */
	private static function format_plugin_slug( $key ) {
		$slug = explode( '/', $key );
		$slug = explode( '.', end( $slug ) );
		return $slug[0];
	}

	/**
	 * Install a plugin from .org in the background via a cron job (used by
	 * installer - opt in).
	 *
	 * @param  string $plugin_to_install_id Plugin ID.
	 * @param  array  $plugin_to_install Plugin information.
	 * @throws Exception If unable to proceed with plugin installation.
	 */
	public static function background_installer( $plugin_to_install_id, $plugin_to_install ) {
		// Explicitly clear the event.
		wp_clear_scheduled_hook( 'everest_forms_plugin_background_installer', func_get_args() );

		if ( ! empty( $plugin_to_install['repo-slug'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			WP_Filesystem();

			$skin              = new Automatic_Upgrader_Skin();
			$upgrader          = new WP_Upgrader( $skin );
			$installed_plugins = array_map( array( __CLASS__, 'format_plugin_slug' ), array_keys( get_plugins() ) );
			$plugin_slug       = $plugin_to_install['repo-slug'];
			$plugin            = $plugin_slug . '/' . $plugin_slug . '.php';
			$installed         = false;
			$activate          = false;

			// See if the plugin is installed already.
			if ( in_array( $plugin_to_install['repo-slug'], $installed_plugins ) ) {
				$installed = true;
				$activate  = ! is_plugin_active( $plugin );
			}

			// Install this thing!
			if ( ! $installed ) {
				// Suppress feedback.
				ob_start();

				try {
					$plugin_information = plugins_api(
						'plugin_information',
						array(
							'slug'   => $plugin_to_install['repo-slug'],
							'fields' => array(
								'short_description' => false,
								'sections'          => false,
								'requires'          => false,
								'rating'            => false,
								'ratings'           => false,
								'downloaded'        => false,
								'last_updated'      => false,
								'added'             => false,
								'tags'              => false,
								'homepage'          => false,
								'donate_link'       => false,
								'author_profile'    => false,
								'author'            => false,
							),
						)
					);

					if ( is_wp_error( $plugin_information ) ) {
						throw new Exception( $plugin_information->get_error_message() );
					}

					$package  = $plugin_information->download_link;
					$download = $upgrader->download_package( $package );

					if ( is_wp_error( $download ) ) {
						throw new Exception( $download->get_error_message() );
					}

					$working_dir = $upgrader->unpack_package( $download, true );

					if ( is_wp_error( $working_dir ) ) {
						throw new Exception( $working_dir->get_error_message() );
					}

					$result = $upgrader->install_package(
						array(
							'source'                      => $working_dir,
							'destination'                 => WP_PLUGIN_DIR,
							'clear_destination'           => false,
							'abort_if_destination_exists' => false,
							'clear_working'               => true,
							'hook_extra'                  => array(
								'type'   => 'plugin',
								'action' => 'install',
							),
						)
					);

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}

					$activate = true;

				} catch ( Exception $e ) {
					EVF_Admin_Notices::add_custom_notice(
						$plugin_to_install_id . '_install_error',
						sprintf(
							// translators: 1: plugin name, 2: error message, 3: URL to install plugin manually.
							__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', 'everest-forms' ),
							$plugin_to_install['name'],
							$e->getMessage(),
							esc_url( admin_url( 'index.php?wc-install-plugin-redirect=' . $plugin_to_install['repo-slug'] ) )
						)
					);
				}

				// Discard feedback.
				ob_end_clean();
			}

			wp_clean_plugins_cache();

			// Activate this thing.
			if ( $activate ) {
				try {
					$result = activate_plugin( $plugin );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
				} catch ( Exception $e ) {
					EVF_Admin_Notices::add_custom_notice(
						$plugin_to_install_id . '_install_error',
						sprintf(
							// translators: 1: plugin name, 2: URL to WP plugin page.
							__( '%1$s was installed but could not be activated. <a href="%2$s">Please activate it manually by clicking here.</a>', 'everest-forms' ),
							$plugin_to_install['name'],
							admin_url( 'plugins.php' )
						)
					);
				}
			}
		}
	}

	/**
	 * Install a theme from .org in the background via a cron job (used by installer - opt in).
	 *
	 * @param  string $theme_slug Theme slug.
	 * @throws Exception If unable to proceed with theme installation.
	 */
	public static function theme_background_installer( $theme_slug ) {
		// Explicitly clear the event.
		wp_clear_scheduled_hook( 'everest_forms_theme_background_installer', func_get_args() );

		if ( ! empty( $theme_slug ) ) {
			// Suppress feedback.
			ob_start();

			try {
				$theme = wp_get_theme( $theme_slug );

				if ( ! $theme->exists() ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
					include_once ABSPATH . 'wp-admin/includes/theme.php';

					WP_Filesystem();

					$skin     = new Automatic_Upgrader_Skin();
					$upgrader = new Theme_Upgrader( $skin );
					$api      = themes_api(
						'theme_information', array(
							'slug'   => $theme_slug,
							'fields' => array( 'sections' => false ),
						)
					);
					$result   = $upgrader->install( $api->download_link );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					} elseif ( is_wp_error( $skin->result ) ) {
						throw new Exception( $skin->result->get_error_message() );
					} elseif ( is_null( $result ) ) {
						throw new Exception( 'Unable to connect to the filesystem. Please confirm your credentials.' );
					}
				}

				switch_theme( $theme_slug );
			} catch ( Exception $e ) {
				EVF_Admin_Notices::add_custom_notice(
					$theme_slug . '_install_error',
					sprintf(
						// translators: 1: theme slug, 2: error message, 3: URL to install theme manually.
						__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', 'everest-forms' ),
						$theme_slug,
						$e->getMessage(),
						esc_url( admin_url( 'update.php?action=install-theme&theme=' . $theme_slug . '&_wpnonce=' . wp_create_nonce( 'install-theme_' . $theme_slug ) ) )
					)
				);
			}

			// Discard feedback.
			ob_end_clean();
		}
	}
}

EVF_Install::init();
