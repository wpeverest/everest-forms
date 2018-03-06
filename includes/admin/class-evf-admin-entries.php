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

		$entries_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">
			<form id="entries-list" method="post">
				<?php
					$entries_table_list->views();
					$entries_table_list->search_box( __( 'Search Entries', 'everest-forms' ), 'everest-forms' );
					$entries_table_list->display();

					wp_nonce_field( 'save', 'everest-forms_nonce' );
				?>
			</form>
		</div>
		<?php
	}

	public function actions() {
		global $wpdb;

		if ( ! $this->is_entries_page() ) {
			return;
		}

        if ( isset( $_POST['action'] ) && $_POST['action'] == 'trash' ) {
            $entries = isset( $_POST['entry'] ) ? $_POST['entry'] : array();
            foreach( $entries as $entry ) {
                $query = 'UPDATE `wp_evf_entries` SET status = "trash" WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query );
                wp_redirect( admin_url('admin.php?page=display-evf-entries') );
            }
        }

        if( isset($_POST['action'] ) && $_POST['action'] == 'untrash' ){
            $entries = isset( $_POST['entry'] ) ? $_POST['entry'] : array();
            foreach( $entries as $entry ) {
                $query = 'UPDATE `wp_evf_entries` SET status = "publish" WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query );
                wp_redirect( admin_url('admin.php?page=display-evf-entries') );
            }
        }

        if( isset( $_POST['action'] ) && $_POST['action'] == 'delete' ){
            $entries = isset( $_POST['entry'] ) ? $_POST['entry'] : array();
            foreach( $entries as $entry ) {
                $query = 'DELETE FROM wp_evf_entries WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query );
                $query = 'DELETE FROM wp_evf_entrymeta WHERE evf_entry_id = '. $entry .'';
                $wpdb->get_results( $query );
                wp_redirect( admin_url('admin.php?page=display-evf-entries') );
            }
        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'trash' ) {
            $query = 'UPDATE `wp_evf_entries` SET status = "trash" WHERE id = '. $_GET['id'] .'' ;
            $wpdb->get_results( $query );
            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ) {
            $query = 'DELETE FROM wp_evf_entries WHERE id = '. $_GET['id'] .'';
            $wpdb->get_results( $query );
            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'untrash' ) {
            $query = 'UPDATE `wp_evf_entries` SET status = "publish" WHERE id = '. $_GET['id'] .'' ;
            $wpdb->get_results( $query );
            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }

        if( isset( $_GET['status'] ) && $_GET['status'] == 'trash' && isset( $_GET['empty_trash'] ) && $_GET['empty_trash'] == 1 ) {
            $query = 'DELETE FROM wp_evf_entries';
            $wpdb->get_results( $query );

            $query = 'DELETE FROM wp_evf_entrymeta';
            $wpdb->get_results( $query );

            wp_redirect( admin_url('admin.php?page=display-evf-entries') );
        }
    }

    public function get_single_entry( $id ) {

    }
}

new EVF_Admin_Entries();
