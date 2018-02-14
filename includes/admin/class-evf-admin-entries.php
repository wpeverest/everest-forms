<?php

/**
 * The Entry Manager Class
 *
 * @since 1.1.0
 */
class EVF_Admin_Entries {

	/**
	 * Initialize the entries admin actions.
	 */
	function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
	}

	public static function page_output() {
		global $entries_table_list;

		$entries_table_list->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">
			<form id="entries-list" method="post">
				<?php
					$entries_table_list->views();
					$entries_table_list->search_box( __( 'Search Forms', 'everest-forms' ), 'everest-forms' );
					$entries_table_list->display();

					wp_nonce_field( 'save', 'everest-forms_nonce' );
				?>
			</form>
		</div>
		<?php

		do_action( 'everest_forms_get_all_entries' );
	}

    public function actions() {
        global $wpdb;

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
