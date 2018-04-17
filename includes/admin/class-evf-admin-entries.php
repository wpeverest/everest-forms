<?php
/**
 * EverestForms Admin Entries Class
 *
 * @package EverestForms\Admin
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Entries class.
 */
class EVF_Admin_Entries {

	/**
	 * Initialize the entries admin actions.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	/**
	 * Check if is entries page.
	 *
	 * @return bool
	 */
	private function is_entries_page() {
		return isset( $_GET['page'] ) && 'evf-entries' === $_GET['page']; // WPCS: input var okay, CSRF ok.
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		if ( isset( $_GET['view-entry'] ) ) {
			$form_id  = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // WPCS: input var okay, CSRF ok.
			$entry_id = isset( $_GET['view-entry'] ) ? absint( $_GET['view-entry'] ) : 0; // WPCS: input var okay, CSRF ok.
			$entry    = evf_get_entry( $entry_id );

			include 'views/html-admin-page-entries-view.php';
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {
		global $entries_table_list;

		// Get the entries count.
		$count = count( evf_get_entries_ids( $entries_table_list->form_id ) );

		$entries_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">
			<?php if ( 0 < $count ) : ?>
				<form id="entries-list" method="post">
					<input type="hidden" name="page" value="evf-entries" />
					<?php
						$entries_table_list->views();
						$entries_table_list->search_box( __( 'Search Entries', 'everest-forms' ), 'everest-forms' );
						$entries_table_list->display();
					?>
				</form>
			<?php else : ?>
				<div class="everest-forms-BlankState">
					<svg aria-hidden="true" class="octicon octicon-graph everest-forms-BlankState-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M16 14v1H0V0h1v14h15zM5 13H3V8h2v5zm4 0H7V3h2v10zm4 0h-2V6h2v7z"/></svg>
					<h2 class="everest-forms-BlankState-message"><?php esc_html_e( 'Whoops, it appears you do not have any form entries yet.', 'everest-forms' ); ?></h2>
					<?php if ( ! empty( $entries_table_list->forms ) ) : ?>
						<form id="entries-list" method="post"><?php
							ob_start();
							$entries_table_list->forms_dropdown();
							$output = ob_get_clean();

							if ( ! empty( $output ) ) {
								echo $output;
								submit_button( __( 'Filter', 'everest-forms' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
							}
						?></form>
					<?php else : ?>
						<a class="everest-forms-BlankState-cta button-primary button" target="_blank" href="https://docs.wpeverest.com/docs/everest-forms/entry-management/?utm_source=blankslate&utm_medium=entry&utm_content=entriesdoc&utm_campaign=everestformplugin"><?php esc_html_e( 'Learn more about entries', 'everest-forms' ); ?></a>
						<a class="everest-forms-BlankState-cta button" href="<?php echo esc_url( admin_url( 'admin.php?page=edit-evf-form&create-form=1' ) ); ?>"><?php esc_html_e( 'Create your first form!', 'everest-forms' ); ?></a>
					<?php endif; ?>
					<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub { display: none; }</style>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Entries admin actions.
	 */
	public function actions() {
		if ( $this->is_entries_page() ) {
			// Trash entry.
			if ( isset( $_GET['trash'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->trash_entry();
			}

			// Untrash entry.
			if ( isset( $_GET['untrash'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->untrash_entry();
			}

			// Delete entry.
			if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->delete_entry();
			}

			// Bulk actions.
			if ( isset( $_REQUEST['action'], $_REQUEST['action2'] ) && isset( $_REQUEST['entry'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->bulk_actions();
			}

			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->empty_trash();
			}
		}
	}

	/**
	 * Trash entry.
	 */
	private function trash_entry() {
		check_admin_referer( 'trash-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['trash'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['trash'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				$this->update_status( $entry_id, 'trash' );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'trashed' => 1 ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Trash entry.
	 */
	private function untrash_entry() {
		check_admin_referer( 'untrash-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['untrash'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['untrash'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				$this->update_status( $entry_id, 'publish' );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'untrashed' => 1 ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Delete entry.
	 */
	private function delete_entry() {
		check_admin_referer( 'delete-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['delete'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				$this->remove_entry( $entry_id );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'deleted' => 1 ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Empty Trash
	 */
	public function empty_trash() {
		global $wpdb;

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( __( 'You do not have permissions to delete Entries!', 'everest-forms' ) );
		}

		if ( isset( $_GET['form_id'] ) ) { // WPCS: input var okay, CSRF ok.
			$form_id = absint( $_GET['form_id'] ); // WPCS: input var okay, CSRF ok.

			if ( $form_id ) {
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}evf_entries WHERE `status` = 'trash' AND form_id = %d", $form_id ) ); // WPCS: cache ok, DB call ok.
				$entries = array_map( 'intval', wp_list_pluck( $results, 'entry_id' ) );

				foreach ( $entries as $entry_id ) {
					$this->remove_entry( $entry_id );
				}

				$qty = count( $entries );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'deleted' => $qty ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		check_admin_referer( 'bulk-entries' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit Entries', 'everest-forms' ) );
		}

		if ( isset( $_REQUEST['action'], $_REQUEST['action2'] ) ) { // WPCS: input var okay, CSRF ok.
			$action  = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // WPCS: input var okay, CSRF ok.
			$action2 = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) ); // WPCS: input var okay, CSRF ok.
			$entries = isset( $_REQUEST['entry'] ) ? array_map( 'absint', (array) $_REQUEST['entry'] ) : array(); // WPCS: input var okay, CSRF ok.

			if ( 'delete' === $action || 'delete' === $action2 ) {
				$this->bulk_delete_entry( $entries );
			} elseif ( 'trash' === $action || 'trash' === $action2 ) {
				$this->bulk_update_status( $entries, 'trash' );
			} elseif ( 'untrash' === $action || 'untrash' === $action2 ) {
				$this->bulk_update_status( $entries, 'publish' );
			}
		}
	}

	/**
	 * Bulk delete entry.
	 *
	 * @param array $entries Entries.
	 */
	private function bulk_delete_entry( $entries ) {
		foreach ( $entries as $entry_id ) {
			$this->remove_entry( $entry_id );
		}
	}

	/**
	 * Bulk update entry status.
	 *
	 * @param array  $entries Entries.
	 * @param string $status  Entry status.
	 */
	private function bulk_update_status( $entries, $status = '' ) {
		foreach ( $entries as $entry_id ) {
			$this->update_status( $entry_id, $status );
		}
	}

	/**
	 * Remove entry.
	 *
	 * @param  int $entry_id Entry ID.
	 * @return bool
	 */
	private function remove_entry( $entry_id ) {
		global $wpdb;

		$delete = $wpdb->delete( $wpdb->prefix . 'evf_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );

		if ( apply_filters( 'everest_forms_delete_entrymeta', true ) ) {
			$wpdb->delete( $wpdb->prefix . 'evf_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
		}

		return $delete;
	}

	/**
	 * Set entry status.
	 *
	 * @param  int    $entry_id Entry ID.
	 * @param  string $status   Entry status.
	 */
	private function update_status( $entry_id, $status = 'publish' ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'evf_entries',
			array( 'status' => $status ),
			array( 'entry_id' => $entry_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}

new EVF_Admin_Entries();
