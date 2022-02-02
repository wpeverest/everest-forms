<?php
/**
 * EverestForms Admin Forms Class
 *
 * @package EverestForms\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Forms class.
 */
class EVF_Admin_Forms {

	/**
	 * Initialize the forms admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'deleted_post', array( $this, 'delete_entries' ) );
		add_filter( 'wp_untrash_post_status', array( $this, 'untrash_form_status' ), 10, 2 );
	}

	/**
	 * Check if is forms page.
	 *
	 * @return bool
	 */
	private function is_forms_page() {
		return isset( $_GET['page'] ) && 'evf-builder' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		global $current_tab;

		if ( isset( $_GET['form_id'] ) && $current_tab ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form      = evf()->form->get( absint( $_GET['form_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$form_id   = is_object( $form ) ? absint( $form->ID ) : absint( $_GET['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$form_data = is_object( $form ) ? evf_decode( $form->post_content ) : false;

			include 'views/html-admin-page-builder.php';
		} elseif ( isset( $_GET['create-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$templates       = array();
			$refresh_url     = add_query_arg(
				array(
					'page'               => 'evf-builder&create-form=1',
					'action'             => 'evf-template-refresh',
					'evf-template-nonce' => wp_create_nonce( 'refresh' ),
				),
				admin_url( 'admin.php' )
			);
			$license_plan    = evf_get_license_plan();
			$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_all'; // phpcs:ignore WordPress.Security.NonceVerification

			if ( '_featured' !== $current_section ) {
				$category  = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'free'; // phpcs:ignore WordPress.Security.NonceVerification
				$templates = self::get_template_data( $category );
			}

			/**
			 * Addon page view.
			 *
			 * @uses $templates
			 * @uses $refresh_url
			 * @uses $current_section
			 */
			include 'views/html-admin-page-builder-setup.php';
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Get sections for the addons screen.
	 *
	 * @return array of objects
	 */
	public static function get_sections() {
		$template_sections = get_transient( 'evf_template_sections_list' );

		if ( false === $template_sections ) {
			$template_sections = evf_get_json_file_contents( 'assets/extensions-json/templates/template-sections.json' );

			if ( $template_sections ) {
				set_transient( 'evf_template_sections_list', $template_sections, WEEK_IN_SECONDS );
			}
		}

		return apply_filters( 'everest_forms_template_sections', $template_sections );
	}

	/**
	 * Get section content for the template screen.
	 *
	 * @return array
	 */
	public static function get_template_data() {
		$template_data = get_transient( 'evf_template_section_list' );

		if ( false === $template_data ) {
			$template_data     = evf_get_json_file_contents( 'assets/extensions-json/templates/all_templates.json' );
			// Removing directory so the templates can be reinitialized.
			$folder_path = untrailingslashit( plugin_dir_path( EVF_PLUGIN_FILE ) . '/assets/images/templates' );

			foreach ( $template_data->templates as $template_tuple ) {
				// We retrieve the image, then use them instead of the remote server.
				$image = wp_remote_get( $template_tuple->image );
				$type  = wp_remote_retrieve_header( $image, 'content-type' );

				// Remote file check failed, we'll fallback to remote image.
				if ( ! $type ) {
					continue;
				}

				$temp_name     = explode( '/', $template_tuple->image );
				$relative_path = $folder_path . '/' . end( $temp_name );
				$exists        = file_exists( $relative_path );

				// If it exists, utilize this file instead of remote file.
				if ( $exists ) {
					$template_tuple->image = plugin_dir_url( EVF_PLUGIN_FILE ) . 'assets/images/templates/' . end( $temp_name );
				}
			}

			if ( ! empty( $template_data->templates ) ) {
				set_transient( 'evf_template_section_list', $template_data, WEEK_IN_SECONDS );
			}
		}

		if ( ! empty( $template_data->templates ) ) {
			return apply_filters( 'everest_forms_template_section_data', $template_data->templates );
		}
	}

	/**
	 * Table list output.
	 */
	public static function table_list_output() {
		global $forms_table_list;

		$forms_table_list->process_bulk_action();
		$forms_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></h1>
			<?php if ( current_user_can( 'everest_forms_create_forms' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&create-form=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'everest-forms' ); ?></a>
			<?php endif; ?>
			<hr class="wp-header-end">

			<?php settings_errors(); ?>

			<form id="form-list" method="post">
				<input type="hidden" name="page" value="everest-forms"/>
				<?php
					$forms_table_list->views();
					$forms_table_list->search_box( __( 'Search Forms', 'everest-forms' ), 'everest-forms' );
					$forms_table_list->display();

					wp_nonce_field( 'save', 'everest-forms_nonce' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Forms admin actions.
	 */
	public function actions() {
		if ( $this->is_forms_page() ) {
			// Empty trash.
			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->empty_trash();
			}

			// Duplicate form.
			if ( isset( $_REQUEST['action'] ) && 'duplicate_form' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->duplicate_form();
			}
		}
	}

	/**
	 * Empty Trash.
	 */
	private function empty_trash() {
		check_admin_referer( 'bulk-forms' );

		$count    = 0;
		$form_ids = get_posts(
			array(
				'post_type'           => 'everest_form',
				'ignore_sticky_posts' => true,
				'nopaging'            => true,
				'post_status'         => 'trash',
				'fields'              => 'ids',
			)
		);

		foreach ( $form_ids as $form_id ) {
			if ( wp_delete_post( $form_id, true ) ) {
				$count ++;
			}
		}

		add_settings_error(
			'empty_trash',
			'empty_trash',
			/* translators: %d: number of forms */
			sprintf( _n( '%d form permanently deleted.', '%d forms permanently deleted.', $count, 'everest-forms' ), $count ),
			'updated'
		);
	}

	/**
	 * Duplicate form.
	 */
	private function duplicate_form() {
		if ( empty( $_REQUEST['form_id'] ) ) {
			wp_die( esc_html__( 'No form to duplicate has been supplied!', 'everest-forms' ) );
		}

		$form_id = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : '';

		check_admin_referer( 'everest-forms-duplicate-form_' . $form_id );

		$duplicate_id = evf()->form->duplicate( $form_id );

		// Redirect to the edit screen for the new form page.
		wp_safe_redirect( admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $duplicate_id ) );
		exit;
	}

	/**
	 * Remove entry and its associated meta.
	 *
	 * When form is deleted then it also deletes its entries meta.
	 *
	 * @param int $postid Post ID.
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
	 * Untrash form status.
	 *
	 * @since 1.7.5
	 *
	 * @param string $new_status The new status of the post being restored.
	 * @param int    $post_id    The ID of the post being restored.
	 * @return string
	 */
	public function untrash_form_status( $new_status, $post_id ) {
		return current_user_can( 'everest_forms_edit_forms', $post_id ) ? 'publish' : $new_status;
	}
}

new EVF_Admin_Forms();
