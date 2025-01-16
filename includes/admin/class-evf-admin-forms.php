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
		add_action( 'trashed_post', array( $this, 'remove_post_from_import_tracker' ), 10, 2 );
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

			EVF_Admin_Form_Templates::load_template_view();
		} else {
			self::table_list_output();
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
			<div class="everest-forms-form-listing__header">
				<div class="everest-forms-form-listing__header-left">
					<div id="everest-forms-header__logo">
						<svg xmlns="http://www.w3.org/2000/svg" width="32" height="26" viewBox="0 0 32 26" fill="none">
							<path d="M25.8984 0H19.6016L21.5313 3.24999H27.8282L25.8984 0Z" fill="#5317AA"/>
							<path d="M29.8594 6.49988H23.5625L25.5938 9.74987H31.8906L29.8594 6.49988Z" fill="#5317AA"/>
							<path d="M29.7579 22.75H28.8438H26.0001H5.78907L15.8438 6.29686L20.0079 13H19.0938H15.8438L13.9141 16.25H15.8438H17.1641H25.7969L15.8438 0.203094L0 26H2.84375H28.8438H31.7891L29.7579 22.75Z" fill="#5317AA"/>
						</svg>
						<span id="everest-forms-logo__separator">|</span>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder' ) ); ?>" id="everest-forms-form-listing__heading"><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></a>
				</div>
				<button class="button" id="evf-form-listing__screen-options">
					<?php esc_html_e( 'Screen Options', 'everest-forms' ); ?>
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
						<path d="M6 8.75C5.85 8.75 5.75 8.7 5.65 8.6L1.15 4.1C0.95 3.9 0.95 3.6 1.15 3.4C1.35 3.2 1.65 3.2 1.85 3.4L6 7.55L10.15 3.4C10.35 3.2 10.65 3.2 10.85 3.4C11.05 3.6 11.05 3.9 10.85 4.1L6.35 8.6C6.25 8.7 6.15 8.75 6 8.75Z" fill="#383838"/>
					</svg>
				</button>
			</div>

			<div id="everest-forms-list-table__container">
				<div class="everest-forms-list-table-container__header">
					<h2 class="wp-heading-inline"><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></h2>
					<?php if ( current_user_can( 'everest_forms_create_forms' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&create-form=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'everest-forms' ); ?></a>
					<?php endif; ?>
				</div>
				<hr class="wp-header-end">

				<?php settings_errors(); ?>

				<form id="form-list" method="post">
					<input type="hidden" name="page" value="everest-forms"/>
					<?php
						echo '<div class="everest-forms-list-filters-row">';
						$forms_table_list->views();
					?>
						<div id="everest-forms-list-search-form">
							<label class="screen-reader-text" for="everest-forms-list-table-search-input">Search Forms</label>
							<input type="search" id="everest-forms-list-table-search-input" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_html_e( 'Search Forms ...', 'everest-forms' ); ?>" />
							<button type="submit" id="search-submit">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<path fill="#000" fill-rule="evenodd" d="M4 11a7 7 0 1 1 12.042 4.856 1.012 1.012 0 0 0-.186.186A7 7 0 0 1 4 11Zm12.618 7.032a9 9 0 1 1 1.414-1.414l3.675 3.675a1 1 0 0 1-1.414 1.414l-3.675-3.675Z" clip-rule="evenodd"/>
								</svg>
							</button>
						</div>
					<?php
						echo '</div>';
						$forms_table_list->display();

						wp_nonce_field( 'save', 'everest-forms_nonce' );
					?>
				</form>
			</div>
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
				++$count;
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
	/**
	 * Remove the post from form migrator import tracker.
	 *
	 * @param [int]    $form_id The form ID.
	 * @param [string] $previous_status The previous status.
	 * @since 2.0.8
	 */
	public function remove_post_from_import_tracker( $form_id, $previous_status = '' ) {
		$form = evf()->form->get(
			absint( $form_id ),
			array(
				'content_only' => true,
			)
		);

		if ( empty( $form ) ) {
			return;
		}
		$imported_from = get_post_meta( $form_id, 'evf_fm_imported_from' );

		if ( empty( $imported_from ) ) {
			return;
		}
		if ( ! isset( $imported_from[0]['form_from'] ) || ! isset( $imported_from[0]['form_from'] ) ) {
			return;
		}
		$form_slug             = $imported_from[0]['form_from'];
		$imported_from_form_id = $imported_from[0]['form_id'];
		$imported_form_list    = get_option( 'evf_fm_' . $form_slug . '_imported_form_list', array() );

		$is_form_imported = array_search( $imported_from_form_id, $imported_form_list );

		if ( ! $is_form_imported ) {
			return;
		}

		unset( $imported_form_list[ $is_form_imported ] );
		update_option( 'evf_fm_' . $form_slug . '_imported_form_list', $imported_form_list );
	}
}

new EVF_Admin_Forms();
