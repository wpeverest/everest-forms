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
	}

	/**
	 * Check if is forms page.
	 *
	 * @return bool
	 */
	private function is_forms_page() {
		return isset( $_GET['page'] ) && 'evf-builder' === $_GET['page']; // WPCS: input var okay, CSRF ok.
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		global $current_tab;

		if ( isset( $_GET['form_id'] ) && $current_tab ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form      = EVF()->form->get( absint( $_GET['form_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$form_id   = is_object( $form ) ? absint( $form->ID ) : absint( $_GET['form_id'] );
			$form_data = is_object( $form ) ? evf_decode( $form->post_content ) : false;

			include 'views/html-admin-page-builder.php';
		} elseif ( isset( $_GET['create-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			include 'views/html-admin-page-builder-setup.php';
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
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&create-form=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'everest-forms' ); ?></a>
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
			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->empty_trash();
			}

			// Duplicate form.
			if ( isset( $_REQUEST['action'] ) && 'duplicate_form' === $_REQUEST['action'] ) { // WPCS: input var okay, CSRF ok.
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
}

new EVF_Admin_Forms();
