<?php
/**
 * EverestForms Admin
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin class.
 */
class EVF_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'addon_actions' ) );
		add_action( 'admin_init', array( $this, 'template_actions' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'evf_print_js', 25 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Output buffering allows admin screens to make redirects later on.
	 */
	public function buffer() {
		ob_start();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once dirname( __FILE__ ) . '/evf-admin-functions.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-menus.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-notices.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-assets.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-editor.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-forms.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-entries.php';
		include_once dirname( __FILE__ ) . '/class-evf-admin-import-export.php';

		// Setup/welcome.
		if ( ! empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			switch ( $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				case 'evf-welcome':
					include_once dirname( __FILE__ ) . '/class-evf-admin-welcome.php';
					break;
			}
		}
	}

	/**
	 * Handle redirects after addon activate/deactivate.
	 */
	public function addon_actions() {
		if ( isset( $_GET['page'], $_REQUEST['action'] ) && 'evf-addons' === $_GET['page'] ) {
			$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : false;

			if ( 'evf-addons-refresh' === $action ) {
				if ( empty( $_GET['evf-addons-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['evf-addons-nonce'] ), 'refresh' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					wp_die( esc_html_e( 'Could not verify nonce', 'everest-forms' ) );
				}

				foreach ( array( 'evf_pro_license_plan', 'evf_addons_sections', 'evf_extensions_section' ) as $transient ) {
					delete_transient( $transient );
				}
			}

			if ( $plugin && in_array( $action, array( 'activate', 'deactivate' ), true ) ) {

				if ( 'activate' === $action ) {
					if ( ! current_user_can( 'activate_plugin', $plugin ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to activate this plugin.', 'everest-forms' ) );
					}

					check_admin_referer( 'activate-plugin_' . $plugin );

					activate_plugin( $plugin );
				} elseif ( 'deactivate' === $action ) {
					if ( ! current_user_can( 'deactivate_plugins' ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to deactivate plugins for this site.', 'everest-forms' ) );
					}

					check_admin_referer( 'deactivate-plugin_' . $plugin );

					deactivate_plugins( $plugin );
				}
			}

			// Redirect to the add-ons page.
			wp_safe_redirect( admin_url( 'admin.php?page=evf-addons' ) );
			exit;
		}
	}

	/**
	 * Handle redirects after template refresh.
	 */
	public function template_actions() {
		if ( isset( $_GET['page'], $_REQUEST['action'] ) && 'evf-builder' === $_GET['page'] ) {
			$action        = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			$raw_templates = wp_safe_remote_get( 'https://raw.githubusercontent.com/wpeverest/extensions-json/master/everest-forms/templates/all_templates.json' );

			if ( 'evf-template-refresh' === $action && ! is_wp_error( $raw_templates ) ) {
				if ( empty( $_GET['evf-template-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['evf-template-nonce'] ), 'refresh' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					wp_die( esc_html_e( 'Could not verify nonce', 'everest-forms' ) );
				}

				foreach ( array( 'evf_pro_license_plan', 'evf_template_sections', 'evf_template_section' ) as $transient ) {
					delete_transient( $transient );
				}

				// Redirect to the builder page normally.
				wp_safe_redirect( admin_url( 'admin.php?page=evf-builder&create-form=1' ) );
				exit;
			}
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted).
		if ( ! empty( $_GET['evf-install-plugin-redirect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$plugin_slug = evf_clean( wp_unslash( $_GET['evf-install-plugin-redirect'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );
			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect.
		if ( get_transient( '_evf_activation_redirect' ) && apply_filters( 'everest_forms_show_welcome_page', true ) ) {
			$do_redirect  = true;
			$current_page = isset( $_GET['page'] ) ? evf_clean( wp_unslash( $_GET['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// On these pages, or during these events, postpone the redirect.
			if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_everest_forms' ) ) {
				$do_redirect = false;
			}

			// On these pages, or during these events, disable the redirect.
			if ( 'evf-welcome' === $current_page || EVF_Admin_Notices::has_notice( 'install' ) || apply_filters( 'everest_forms_prevent_automatic_wizard_redirect', false ) || isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				delete_transient( '_evf_activation_redirect' );
				$do_redirect = false;
			}

			if ( $do_redirect ) {
				delete_transient( '_evf_activation_redirect' );
				wp_safe_redirect( admin_url( 'index.php?page=evf-welcome' ) );
				exit;
			}
		}
	}

	/**
	 * Change the admin footer text on EverestForms admin pages.
	 *
	 * @since  1.0.0
	 * @param  string $footer_text Footer text.
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_everest_forms' ) || ! function_exists( 'evf_get_screen_ids' ) ) {
			return $footer_text;
		}
		$current_screen = get_current_screen();
		$evf_pages      = evf_get_screen_ids();

		// Check to make sure we're on a EverestForms admin page.
		if ( isset( $current_screen->id ) && apply_filters( 'everest_forms_display_admin_footer_text', in_array( $current_screen->id, $evf_pages, true ) ) ) {
			// Change the footer text.
			if ( ! get_option( 'everest_forms_admin_footer_text_rated' ) ) {
				$footer_text = sprintf(
					/* translators: 1: EverestForms 2:: five stars */
					esc_html__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'everest-forms' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'Everest Forms', 'everest-forms' ) ),
					'<a href="https://wordpress.org/support/plugin/everest-forms/reviews?rate=5#new-post" target="_blank" class="evf-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'everest-forms' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
				evf_enqueue_js(
					"
					jQuery( 'a.evf-rating-link' ).click( function() {
						jQuery.post( '" . evf()->ajax_url() . "', { action: 'everest_forms_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
					"
				);
			} else {
				$footer_text = esc_html__( 'Thank you for creating with Everest Forms.', 'everest-forms' );
			}
		}

		return $footer_text;
	}

	/**
	 * Add body classes for Everest builder.
	 *
	 * @param  array $classes Admin body classes.
	 * @return array
	 */
	public function admin_body_class( $classes ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Check to make sure we're on a EverestForms builder page.
		if ( ( isset( $_GET['form_id'] ) || isset( $_GET['create-form'] ) ) && in_array( $screen_id, array( 'everest-forms_page_evf-builder' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$classes = isset( $_GET['form_id'] ) ? 'everest-forms-builder' : 'everest-forms-builder-setup'; // phpcs:ignore WordPress.Security.NonceVerification
		}

		return $classes;
	}
}

return new EVF_Admin();
