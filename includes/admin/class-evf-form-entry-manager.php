<?php

/**
 * The Entry Manager Class
 *
 * @since 1.1.0
 */
class EVF_Form_Entry_Manager {

    /**
     * The constructor
     *
     */

    function __construct() {
        add_action( 'everest_form_display_entries', array( $this, 'page_output' ) );
    }

    public function page_output() { 
        global $entries_table_list;

        $entries_table_list->prepare_items();
        
        $this->all_actions();

        ?>        
        <div class="wrap">
            <h2 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?><h2>
            <hr class="wp-header-end">
           
            <?php    
                $all_forms = evf_get_all_forms();
                $selected = isset( $_POST['form_id'] ) ? $_POST['form_id'] : get_option( 'evf_selected_form_in_entries', 'All forms' );
                $selected_form = update_option( 'evf_selected_form_in_entries', $selected );

            ?>
                <form id="entries-select" method="POST">
                    <select id = "form-select" name ="form_id">
                        <option>All Forms</option>
                        <?php 
                            foreach( $all_forms as $key => $form ) {
                                echo '<option value="'. $key .'" '. selected( $selected, $key ) .'>'. $form .'</option>';
                            }
                        ?>
                    </select>
                    <?php wp_nonce_field( 'save', 'everest-forms_nonce' );?>
                    <button type="submit" name ="select-form">Filter</button>
                </form>

            <form id="entries-filter" method="post">
                <input type="hidden" name="page" value="display-evf-entries">
                
                <?php
                    $entries_table_list->views();
                    $entries_table_list->display();
                    wp_nonce_field( 'save', 'everest-forms_nonce' );
                ?>

            </form>
        </div>
        <?php

        do_action( 'everest_forms_get_all_entries' );
    }

    public function all_actions() {

        global $wpdb;

        if( isset( $_POST['action'] ) && $_POST['action'] == 'trash' ) {
            $entries = isset( $_POST['everest_form'] ) ? $_POST['everest_form'] : array();
            foreach( $entries as $entry ) {
                $query = 'UPDATE `wp_evf_entries` SET status = "trash" WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query ); 
                wp_redirect( admin_url('admin.php?page=display-evf-entries') ); 
            }
        }

        if( isset($_POST['action'] ) && $_POST['action'] == 'untrash' ){
            $entries = isset( $_POST['everest_form'] ) ? $_POST['everest_form'] : array();
            foreach( $entries as $entry ) {
                $query = 'UPDATE `wp_evf_entries` SET status = "publish" WHERE id = '. $entry .'' ;
                $wpdb->get_results( $query ); 
                wp_redirect( admin_url('admin.php?page=display-evf-entries') ); 
            }
        }

        if( isset( $_POST['action'] ) && $_POST['action'] == 'delete' ){
            $entries = isset( $_POST['everest_form'] ) ? $_POST['everest_form'] : array();
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
new EVF_Form_Entry_Manager;