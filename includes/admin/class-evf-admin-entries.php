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
		$this->actions();
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

		$entries_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">
			<form id="entries-list" method="post">
				<input type="hidden" name="page" value="evf-entries" />
				<?php
					$entries_table_list->views();
					$entries_table_list->search_box( __( 'Search Entries', 'everest-forms' ), 'everest-forms' );
					$entries_table_list->display();

					wp_nonce_field( 'everest-forms-entries' );
				?>
			</form>
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

			// Empty trash.
			if ( isset( $_GET['empty_trash'] ) ) {
				$this->empty_trash();
			}

			// Bulk actions.
			if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['entry'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->bulk_actions();
			}
		}
	}

	/**
	 * Trash entry.
	 */
	private function trash_entry() {
		check_admin_referer( 'trash-entry' );

		if ( isset( $_GET['trash'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['trash'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				$this->update_status( $entry_id, 'trash' );
			}
		}
	}

	/**
	 * Trash entry.
	 */
	private function untrash_entry() {
		check_admin_referer( 'untrash-entry' );

		if ( isset( $_GET['untrash'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['untrash'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				$this->update_status( $entry_id, 'publish' );
			}
		}
	}

	/**
	 * Delete entry.
	 */
	private function delete_entry() {
		check_admin_referer( 'delete-entry' );

		if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
			$entry_id = absint( $_GET['delete'] ); // WPCS: input var okay, CSRF ok.

			if ( $entry_id ) {
				$this->remove_entry( $entry_id );
			}
		}
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
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		check_admin_referer( 'everest-forms-entries' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit Entries', 'everest-forms' ) );
		}

		if ( isset( $_REQUEST['action'] ) ) { // WPCS: input var okay, CSRF ok.
			$action  = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // WPCS: input var okay, CSRF ok.
			$entries = isset( $_REQUEST['entry'] ) ? array_map( 'absint', (array) $_REQUEST['entry'] ) : array(); // WPCS: input var okay, CSRF ok.

			if ( 'delete' === $action ) {
				$this->bulk_delete_entry( $entries );
			} elseif ( 'trash' === $action ) {
				$this->bulk_update_status( $entries, 'trash' );
			} elseif ( 'untrash' === $action ) {
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

		return $delete;
	}

	/**
	 * Set entry status.
	 *
	 * @param  int    $entry_id Entry ID.
	 * @param  string $status   Entry status.
	 * @return bool
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
