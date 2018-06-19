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
	 * Initialize the entries admin actions.
	 */
	public function __construct() {
		add_action( 'deleted_post', array( $this, 'delete_entries' ) );
	}

	/**
	 * Check if is entries page.
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

		if ( isset( $_GET['form_id'] ) && $current_tab ) {
			$form      = EVF()->form->get( absint( $_GET['form_id'] ) );
			$form_id   = $form ? absint( $form->ID ) : absint( $_GET['form_id'] );
			$form_data = $form ? evf_decode( $form->post_content ) : false;

			include 'views/html-admin-page-builder.php';
		} elseif ( isset( $_GET['create-form'] ) ) {
			include 'views/html-admin-page-setup-tmpl.php';
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	public static function table_list_output() {
		global $forms_table_list;

		$forms_table_list->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-setup' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'everest-forms' ); ?></a>
			<hr class="wp-header-end">
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
	 * Remove entry and its associated meta.
	 *
	 * When form is deleted then it also deletes its entries meta.
	 *
	 * @param int $post_id
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
