<?php
/**
 * Setup menus in WP admin.
 *
 * @package  EverestForms/Admin
 * @version  1.0.0
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
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'deleted_post', array( $this, 'delete_entries' ) );

		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'forms_menu' ), 20 );
		add_action( 'admin_menu', array( $this, 'add_new_form' ), 30 );
		add_action( 'admin_menu', array( $this, 'entries_menu' ), 40 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'status_menu' ), 60 );
		add_filter( 'admin_footer', array( $this, 'admin_footer' ), 1 );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 11, 3 );

		// Admin bar menus.
		if ( apply_filters( 'everest_forms_show_admin_bar', true ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 99 );
		}
	}

	/**
	 * Returns a base64 URL for the SVG for use in the menu.
	 *
	 * @param  bool $base64 Whether or not to return base64-encoded SVG.
	 * @return string
	 */
	private function get_icon_svg( $base64 = true ) {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><g><path fill="#82878c" d="M4.5 0v3H0v17h20V0H4.5zM9 19H1V4h8v15zm10 0h-9V3H5.5V1H19v18zM6.5 6h-4V5h4v1zm1 2v1h-5V8h5zm-5 3h3v1h-3v-1z"/></g></svg>';

		if ( $base64 ) {
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return $svg;
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_menu_page( __( 'Everest Forms', 'everest-forms' ), __( 'Everest Forms', 'everest-forms' ), 'manage_everest_forms', 'everest-forms', null, $this->get_icon_svg(), '55.5' );
	}

	/**
	 * Add menu items.
	 */
	public function forms_menu() {
		$forms_page = add_submenu_page( 'everest-forms', __( 'All Forms', 'everest-forms' ), __( 'All Forms', 'everest-forms' ), 'manage_everest_forms', 'everest-forms', array( $this, 'everest_forms_page' ) );

		add_action( 'load-' . $forms_page, array( $this, 'forms_page_init' ) );
	}

	/**
	 * Loads forms into memory.
	 */
	public function forms_page_init() {
		global $forms_table_list;

		if ( ! isset( $_GET['edit-evf-form'] ) ) { // WPCS: input var okay, CSRF ok.
			$forms_table_list = new EVF_Admin_Forms_Table_List();

			// Add screen option.
			add_screen_option( 'per_page', array(
				'default' => 20,
				'option'  => 'evf_forms_per_page'
			) );
		}

		do_action( 'everest_forms_page_init' );
	}

	/**
	 * Add menu items.
	 */
	public function add_new_form() {
		add_submenu_page( 'everest-forms', __( 'Add New', 'everest-forms' ), __( 'Add New', 'everest-forms' ), 'manage_everest_forms', 'edit-evf-form', array( $this, 'add_everest_forms' ) );
	}

	/**
	 * Add menu item.
	 */
	public function entries_menu() {
		$entries_page = add_submenu_page( 'everest-forms', __( 'Entries', 'everest-forms' ), __( 'Entries', 'everest-forms' ), 'manage_everest_forms', 'evf-entries', array( $this, 'entries_page' ) );

		add_action( 'load-' . $entries_page, array( $this, 'entries_page_init' ) );
	}

	/**
	 * Loads entries into memory.
	 */
	public function entries_page_init() {
		global $entries_table_list;

		if ( ! isset( $_GET['view-entry'] ) ) { // WPCS: input var okay, CSRF ok.
			$entries_table_list = new EVF_Admin_Entries_Table_List();

			// Add screen option.
			add_screen_option( 'per_page', array(
				'default' => 20,
				'option'  => 'evf_entries_per_page'
			) );
		}

		do_action( 'everest_forms_entries_page_init' );
	}

	/**
	 * Everest forms admin actions.
	 */
	public function actions() {
		if ( isset( $_GET['page'] ) && 'everest-forms' === $_GET['page'] ) {
			// Bulk actions
			if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['form'] ) ) {
				$this->bulk_actions();
			}

			// Empty trash
			if ( isset( $_GET['empty_trash'] ) ) {
				$this->empty_trash();
			}

			$action  = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
			$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : '';
			$form_id = isset( $_GET['form'] ) && is_numeric( $_GET['form'] ) ? $_GET['form'] : '';

			if ( ! empty( $action ) && ! empty( $nonce ) && ! empty( $form_id ) ) {
				$flag = wp_verify_nonce( $nonce, 'everest_forms_form_duplicate' . $form_id );

				if ( $flag == true && ! is_wp_error( $flag ) ) {

					if ( 'duplicate' === $action ) {
						$this->duplicate( $form_id );
					}
				}
			}
		}
	}

	/**
	 * Remove entry and its associated meta.
	 */
	public function delete_entries( $postid ) {
		global $wpdb;

		$entries = evf_get_entries_ids( $postid );

		// Delete entry.
		if ( ! empty( $entries ) ) {
			foreach ( $entries as $entry_id ) {
				$wpdb->delete( $wpdb->prefix . 'evf_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );
				$wpdb->delete( $wpdb->prefix . 'evf_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
			}
		}
	}

	/**
	 * Bulk trash.
	 *
	 * @param array   $forms
	 * @param boolean $delete
	 */
	private function bulk_trash( $forms, $delete = false ) {
		foreach ( $forms as $form_id ) {
			if ( $delete ) {
				wp_delete_post( $form_id, true );
			} else {
				wp_trash_post( $form_id );
			}
		}

		$type   = ! EMPTY_TRASH_DAYS || $delete ? 'deleted' : 'trashed';
		$qty    = count( $forms );
		$status = isset( $_GET['status'] ) ? '&status=' . sanitize_text_field( $_GET['status'] ) : '';

		// Redirect to registrations page
		wp_redirect( admin_url( 'admin.php?page=everest-forms' . $status . '&' . $type . '=' . $qty ) );
		exit();
	}

	/**
	 * Bulk untrash.
	 *
	 * @param array $forms
	 */
	private function bulk_untrash( $forms ) {
		foreach ( $forms as $form_id ) {
			wp_untrash_post( $form_id );
		}

		$qty = count( $forms );

		// Redirect to registrations page
		wp_redirect( admin_url( 'admin.php?page=everest-forms&status=trash&untrashed=' . $qty ) );
		exit();
	}

	/**
	 * Empty Trash.
	 */
	private function empty_trash() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'empty_trash' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'everest-forms' ) );
		}

		if ( ! current_user_can( 'delete_everest_forms' ) ) {
			wp_die( __( 'You do not have permissions to delete forms!', 'everest-forms' ) );
		}

		$registration = get_posts( array(
			'post_type'           => 'everest_form',
			'ignore_sticky_posts' => true,
			'nopaging'            => true,
			'post_status'         => 'trash',
			'fields'              => 'ids',
		) );

		foreach ( $registration as $registration_id ) {
			wp_delete_post( $registration_id, true );
		}

		$qty = count( $registration );

		// Redirect to registrations page
		wp_redirect( admin_url( 'admin.php?page=everest-forms&deleted=' . $qty ) );
		exit();
	}

	/**
	 * Duplicate form
	 */
	private function duplicate( $form_id ) {

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'everest_forms_form_duplicate' . $form_id ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'everest-forms' ) );
		}

		if ( ! current_user_can( 'duplicate_everest_form' ) ) {
			wp_die( __( 'You do not have permissions to delete forms!', 'everest-forms' ) );
		}
		$post            = get_post( $form_id );
		$current_user    = wp_get_current_user();
		$new_post_author = $current_user->ID;

		/*
		 * if post data exists, create the post duplicate
		 */
		if ( isset( $post ) && $post != null ) {

			if ( 'publish' !== $post->post_status ) {

				return false;
			}

			/*
			 * new post data array
			 */
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => $post->post_status,
				'post_title'     => __( 'Copy of ', 'everest-forms' ) . $post->post_title,
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order
			);

			/*
			 * insert the post by wp_insert_post() function
			 */
			$new_post_id = wp_insert_post( $args );

			/*
			 * duplicate all post meta just in two SQL queries
			 */
			global $wpdb;
			$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $form_id ) );

			if ( count( $post_meta_infos ) != 0 ) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach ( $post_meta_infos as $meta_info ) {
					$meta_key = $meta_info->meta_key;
					if ( $meta_key == '_wp_old_slug' ) {
						continue;
					}
					$meta_value      = addslashes( $meta_info->meta_value );
					$sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}
				if ( count( $sql_query_sel ) > 0 ) {
					$sql_query .= implode( " UNION ALL ", $sql_query_sel );
				}
				$wpdb->query( $sql_query );
			}

			/*
			 * duplicate all post meta just in two SQL queries
			 */
			global $wpdb;
			$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $form_id ) );

			if ( count( $post_meta_infos ) != 0 ) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach ( $post_meta_infos as $meta_info ) {
					$meta_key = $meta_info->meta_key;
					if ( $meta_key == '_wp_old_slug' ) {
						continue;
					}
					$meta_value      = addslashes( $meta_info->meta_value );
					$sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}
				$sql_query .= implode( " UNION ALL ", $sql_query_sel );
				$wpdb->query( $sql_query );
			}

			/*
			 * finally, redirect to the edit post screen for the new draft
			 */
			//wp_redirect( admin_url( 'admin.php?page=everest-forms&edit-registration=' . $new_post_id ) );
			wp_redirect( admin_url( 'admin.php?page=everest-forms' ) );
			exit;
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( ! current_user_can( 'edit_everest_forms' ) ) {
			wp_die( __( 'You do not have permissions to edit forms!', 'everest-forms' ) );
		}

		$forms = array_map( 'absint', (array) $_REQUEST['form'] );

		switch ( $_REQUEST['action'] ) {
			case 'trash' :
				$this->bulk_trash( $forms );
				break;
			case 'untrash' :
				$this->bulk_untrash( $forms );
				break;
			case 'delete' :
				$this->bulk_trash( $forms, true );
				break;
			default :
				break;
		}
	}

	/**
	 * Add menu item.
	 */
	public function settings_menu() {
		$settings_page = add_submenu_page( 'everest-forms', __( 'Everest Forms settings', 'everest-forms' ), __( 'Settings', 'everest-forms' ), 'manage_everest_forms', 'evf-settings', array( $this, 'settings_page' ) );

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
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // WPCS: input var okay, CSRF ok.
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // WPCS: input var okay, CSRF ok.

		// Save settings if data has been posted.
		if ( apply_filters( '' !== $current_section ? "everest_forms_save_settings_{$current_tab}_{$current_section}" : "everest_forms_save_settings_{$current_tab}", ! empty( $_POST ) ) ) { // WPCS: input var okay, CSRF ok.
			EVF_Admin_Settings::save();
		}

		// Add any posted messages.
		if ( ! empty( $_GET['evf_error'] ) ) { // WPCS: input var okay, CSRF ok.
			EVF_Admin_Settings::add_error( wp_kses_post( wp_unslash( $_GET['um_error'] ) ) ); // WPCS: input var okay, CSRF ok.
		}

		if ( ! empty( $_GET['evf_message'] ) ) { // WPCS: input var okay, CSRF ok.
			EVF_Admin_Settings::add_message( wp_kses_post( wp_unslash( $_GET['um_message'] ) ) ); // WPCS: input var okay, CSRF ok.
		}

		do_action( 'everest_forms_settings_page_init' );
	}

	/**
	 * Add menu item.
	 */
	public function status_menu() {
		add_submenu_page( 'everest-forms', __( 'Everest Forms status', 'everest-forms' ), __( 'Status', 'everest-forms' ), 'manage_everest_forms', 'evf-status', array( $this, 'status_page' ) );
	}

	/**
	 * Init the settings page.
	 */
	public function everest_forms_page() {
		EVF_Admin_Forms::output();
	}

	/**
	 * Init the add registration page.
	 */
	public function add_everest_forms() {
		if ( isset( $_GET['tab'], $_GET['form_id'] ) ) {
			do_action( 'everest_form_admin_form_builder_page' );
		} else {
			do_action( 'everest_form_admin_form_template_page' );
		}
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
	public function status_page() {
		EVF_Admin_Status::output();
	}

	public function admin_footer() {
		$screen = get_current_screen();

		do_action( 'everest_form_list_admin_footer', $screen->id );
	}

	/**
	 * Validate screen options on update.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'evf_forms_per_page', 'evf_entries_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Add "Everest Forms" link in admin bar main menu.
	 *
	 * @since 1.0.0
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function admin_bar_menus( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() || ! current_user_can( apply_filters( 'everest_forms_manage_cap', 'manage_options' ) ) ) {
			return;
		}

		$args = array(
			'id'     => 'everest_forms',
			'title'  => 'Everest Forms',
			'href'   => admin_url( 'admin.php?page=edit-evf-form' ),
			'parent' => 'new-content',
		);
		$wp_admin_bar->add_node( $args );
	}
}

return new EVF_Admin_Menus();
