<?php
/**
 * Setup menus in WP admin.
 *
 * @package EverestForms\Admin
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Admin_Menus', false ) ) {
	return new EVF_Admin_Menus();
}

/**
 * EVF_Admin_Menus Class.
 */
class EVF_Admin_Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'builder_menu' ), 20 );
		add_action( 'admin_menu', array( $this, 'entries_menu' ), 30 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'tools_menu' ), 60 );

		if ( apply_filters( 'everest_forms_show_addons_page', true ) ) {
			add_action( 'admin_menu', array( $this, 'addons_menu' ), 70 );
		}

		add_action( 'admin_head', array( $this, 'menu_highlight' ) );
		add_action( 'admin_head', array( $this, 'custom_menu_count' ) );
		add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 11, 3 );
	}

	/**
	 * Returns a base64 URL for the SVG for use in the menu.
	 *
	 * @param  bool $base64 Whether or not to return base64-encoded SVG.
	 * @return string
	 */
	private function get_icon_svg( $base64 = true ) {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path fill="#82878c" d="M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z"/></g></svg>';

		if ( $base64 ) {
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return $svg;
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_menu_page( esc_html__( 'Everest Forms', 'everest-forms' ), esc_html__( 'Everest Forms', 'everest-forms' ), 'manage_everest_forms', 'everest-forms', null, $this->get_icon_svg(), '55.5' );

		// Backward compatibility for builder page redirects.
		if ( ! empty( $_GET['page'] ) && in_array( wp_unslash( $_GET['page'] ), array( 'everest-forms', 'edit-evf-form', 'evf-status' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( 'edit-evf-form' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$redirect_url = admin_url( 'admin.php?page=evf-builder&create-form=1' );

				if ( isset( $_GET['tab'], $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$redirect_url = add_query_arg(
						array(
							'tab'     => evf_clean( wp_unslash( $_GET['tab'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
							'form_id' => absint( wp_unslash( $_GET['form_id'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
						),
						admin_url( 'admin.php?page=evf-builder' )
					);
				}
			} elseif ( 'evf-status' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$redirect_url = str_replace( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'evf-tools', wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			} else {
				$redirect_url = str_replace( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'evf-builder', wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Add menu items.
	 */
	public function builder_menu() {
		$builder_page = add_submenu_page( 'everest-forms', esc_html__( 'Everest Forms Builder', 'everest-forms' ), esc_html__( 'All Forms', 'everest-forms' ), 'manage_everest_forms', 'evf-builder', array( $this, 'builder_page' ) );

		add_submenu_page( 'everest-forms', esc_html__( 'Everest Forms Setup', 'everest-forms' ), esc_html__( 'Add New', 'everest-forms' ), 'manage_everest_forms', 'evf-builder&create-form=1', array( $this, 'builder_page' ) );

		add_action( 'load-' . $builder_page, array( $this, 'builder_page_init' ) );
	}

	/**
	 * Loads builder page.
	 */
	public function builder_page_init() {
		global $current_tab, $forms_table_list;

		evf()->form_fields();

		// Include builder pages.
		EVF_Admin_Builder::get_builder_pages();

		// Get current tab/section.
		$current_tab = empty( $_GET['tab'] ) ? 'fields' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! isset( $_GET['tab'], $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$forms_table_list = new EVF_Admin_Forms_Table_List();

			// Add screen option.
			add_screen_option(
				'per_page',
				array(
					'default' => 20,
					'option'  => 'evf_forms_per_page',
				)
			);
		}

		do_action( 'everest_forms_builder_page_init' );
	}

	/**
	 * Add menu item.
	 */
	public function entries_menu() {
		$entries_page = add_submenu_page( 'everest-forms', esc_html__( 'Everest Forms Entries', 'everest-forms' ), esc_html__( 'Entries', 'everest-forms' ), 'manage_everest_forms', 'evf-entries', array( $this, 'entries_page' ) );

		add_action( 'load-' . $entries_page, array( $this, 'entries_page_init' ) );
	}

	/**
	 * Loads entries into memory.
	 */
	public function entries_page_init() {
		global $entries_table_list;

		if ( ! isset( $_GET['view-entry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$entries_table_list = new EVF_Admin_Entries_Table_List();

			// Add screen option.
			add_screen_option(
				'per_page',
				array(
					'default' => 20,
					'option'  => 'evf_entries_per_page',
				)
			);
		}

		do_action( 'everest_forms_entries_page_init' );
	}

	/**
	 * Add menu item.
	 */
	public function settings_menu() {
		$settings_page = add_submenu_page( 'everest-forms', esc_html__( 'Everest Forms settings', 'everest-forms' ), esc_html__( 'Settings', 'everest-forms' ), 'manage_everest_forms', 'evf-settings', array( $this, 'settings_page' ) );

		add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
	}

	/**
	 * Loads settings page.
	 */
	public function settings_page_init() {
		global $current_tab, $current_section;

		// Include settings pages.
		EVF_Admin_Settings::get_settings_pages();

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// Save settings if data has been posted.
		if ( apply_filters( '' !== $current_section ? "everest_forms_save_settings_{$current_tab}_{$current_section}" : "everest_forms_save_settings_{$current_tab}", ! empty( $_POST ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			EVF_Admin_Settings::save();
		}

		// Add any posted messages.
		if ( ! empty( $_GET['evf_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			EVF_Admin_Settings::add_error( wp_kses_post( wp_unslash( $_GET['evf_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_GET['evf_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			EVF_Admin_Settings::add_message( wp_kses_post( wp_unslash( $_GET['evf_message'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		do_action( 'everest_forms_settings_page_init' );
	}

	/**
	 * Add menu item.
	 */
	public function tools_menu() {
		add_submenu_page( 'everest-forms', esc_html__( 'Everest Forms tools', 'everest-forms' ), esc_html__( 'Tools', 'everest-forms' ), 'manage_everest_forms', 'evf-tools', array( $this, 'tools_page' ) );
	}

	/**
	 * Addons menu item.
	 */
	public function addons_menu() {
		add_submenu_page( 'everest-forms', esc_html__( 'Everest Forms Add-ons', 'everest-forms' ), esc_html__( 'Add-ons', 'everest-forms' ), 'manage_everest_forms', 'evf-addons', array( $this, 'addons_page' ) );
	}

	/**
	 * Highlights the correct top level admin menu item.
	 */
	public function menu_highlight() {
		global $parent_file, $submenu_file;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Check to make sure we're on a EverestForms builder setup page.
		if ( isset( $_GET['create-form'] ) && in_array( $screen_id, array( 'everest-forms_page_evf-builder' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$parent_file  = 'everest-forms'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$submenu_file = 'evf-builder&create-form=1'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}

	/**
	 * Adds the custom count to the menu.
	 */
	public function custom_menu_count() {
		global $submenu;

		if ( isset( $submenu['everest-forms'] ) ) {
			// Remove 'Everest Forms' sub menu item.
			unset( $submenu['everest-forms'][0] );

			// Add count if user has access.
			if ( apply_filters( 'everest_forms_include_count_in_menu', true ) && current_user_can( 'manage_everest_forms' ) ) {
				do_action( 'everest_forms_custom_menu_count', $submenu );
			}
		}
	}

	/**
	 * Custom menu order.
	 *
	 * @param  bool $enabled Whether custom menu ordering is already enabled.
	 * @return bool
	 */
	public function custom_menu_order( $enabled ) {
		return $enabled || current_user_can( 'manage_everest_forms' );
	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'evf_forms_per_page', 'evf_entries_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Init the settings page.
	 */
	public function builder_page() {
		EVF_Admin_Forms::page_output();
	}

	/**
	 * Init the entries page.
	 */
	public function entries_page() {
		EVF_Admin_Entries::page_output();
	}

	/**
	 * Init the settings page.
	 */
	public function settings_page() {
		EVF_Admin_Settings::output();
	}

	/**
	 * Init the status page.
	 */
	public function tools_page() {
		EVF_Admin_Tools::output();
	}

	/**
	 * Init the addons page.
	 */
	public function addons_page() {
		EVF_Admin_Addons::output();
	}
}

return new EVF_Admin_Menus();
