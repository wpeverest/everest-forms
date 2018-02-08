<?php
/**
 * EverestForms Entries Table List
 *
 * @author   WPEverest
 * @category Admin
 * @package  EverestForms/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class EVF_Admin_Entries_Table_List extends WP_List_Table {

	public $post_type_config = array(
		'singular' => 'everest_form',
		'plural'   => 'everest_forms',
		'type'     => 'everest_form'
	);
	
	/**
	 * Initialize the log table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => $this->post_type_config['singular'],
			'plural'   => $this->post_type_config['plural'],
			'ajax'     => false,
		) );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'id'     => __( 'ID', 'everest-forms' ),
			'name' => __( 'Name', 'everest-forms' ),
			'email_address'    => __( 'Email Address', 'everest-forms' ),
			'date'      => __( 'Entry Date', 'everest-forms' ),
			'actions'      => __( 'Action', 'everest-forms' ),				
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  array $item
	 *
	 * @return string
	 */
	public function column_cb( $items ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $items->evf_entry_id );
	}


	/**
	 * Return id column.
	 *
	 * @param  object $item
	 *
	 * @return string
	 */
	public function column_id( $items ) {
		
		$actions = array(
            'view'      => sprintf( '<a href="?page=%s&action=%s&id=%s">View</a>', $_REQUEST['page'], 'view', $items->evf_entry_id ),
            'trash'    => sprintf( '<a href="?page=%s&action=%s&id=%s">Trash</a>', $_REQUEST['page'],'trash', $items->evf_entry_id ),
        );

        if( isset( $_GET['status'] ) && $_GET['status'] == 'trash' ) {
        	$actions = array(
            	'view'      => sprintf( '<a href="?page=%s&action=%s&id=%s">View</a>', $_REQUEST['page'], 'view', $items->evf_entry_id ),
   	            'delete'    => sprintf( '<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'],'delete', $items->evf_entry_id ),
 	            'untrash'    => sprintf( '<a href="?page=%s&action=%s&id=%s">Restore</a>', $_REQUEST['page'],'untrash', $items->evf_entry_id ),
    	    );
        }

  		return sprintf('%1$s %2$s', $items->evf_entry_id, $this->row_actions($actions) );
	}

	/**
	 * Return name column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_name( $items ) {
		return isset( $items->Name ) ? $items->Name : '' ;
	}

	/**
	 * Return email column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_email_address( $items ) {
		return isset( $items->Email ) ? $items->Email : '';
	}

	/**
	 * Return date column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_date( $items ) {
		return isset( $items->created_at ) ? $items->created_at : '';
	}

	/**
	 * Return action column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_actions( $items ) {
		return '<a href=" '. admin_url('admin.php?page=display-evf-entries&action=edit&id='. $items->evf_entry_id .' ') .'">Details</a>';
	}

	protected function get_views() {

		$status_links = array();
		$total_items  = count( $this->items );
		
		/* translators: %s: count */
		$status_links['all'] = "<a href='admin.php?page=display-evf-entries&status=all'>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_items, 'entries', 'everest-forms' ), number_format_i18n( $total_items ) ) . '</a>';

		$status_links['trash'] = "<a href='admin.php?page=display-evf-entries&status=trash'>" . sprintf( _nx( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $total_items, 'entries', 'everest-forms' ), number_format_i18n( $total_items ) ) . '</a>';

		return $status_links;
	}

	/**
	 * Get the status label for licenses.
	 *
	 * @param  string   $status_name
	 * @param  stdClass $status
	 *
	 * @return array
	 */
	private function get_status_label( $status_name, $status ) {
		switch ( $status_name ) {
			case 'publish' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Published <span class="count">(%s)</span>', 'everest-forms' ),
					'plural'   => __( 'Published <span class="count">(%s)</span>', 'everest-forms' ),
					'context'  => '',
					'domain'   => 'everest-forms',
				);
				break;
			case 'draft' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Draft <span class="count">(%s)</span>', 'everest-forms' ),
					'plural'   => __( 'Draft <span class="count">(%s)</span>', 'everest-forms' ),
					'context'  => '',
					'domain'   => 'everest-forms',
				);
				break;
			case 'pending' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Pending <span class="count">(%s)</span>', 'everest-forms' ),
					'plural'   => __( 'Pending <span class="count">(%s)</span>', 'everest-forms' ),
					'context'  => '',
					'domain'   => 'everest-forms',
				);
				break;

			default:
				$label = $status->label_count;
				break;
		}

		return $label;
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'id'  => array( 'id', false ),
			'form_id' => array('form_id', false ),
			'name' => array( 'name', false ),
			'email_address'   => array( 'email_address', false ),
			'date'   => array( 'date', false ),
			'actions' => array( 'actions', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		if ( isset( $_GET['status'] ) && 'trash' == $_GET['status'] ) {
			return array(
				'untrash' => __( 'Restore', 'everest-forms' ),
				'delete'  => __( 'Delete permanently', 'everest-forms' ),
			);
		}
		return array(
			'trash' => __( 'Move to trash', 'everest-forms' ),
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) { 				        
		
		if ( 'top' == $which && isset( $_GET['status'] ) && 'trash' == $_GET['status'] && current_user_can( 'delete_posts' ) ) {

			echo '<div class="alignleft actions"><a id="delete_all" class="button apply" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=display-evf-entries&status=trash&empty_trash=1' ), 'empty_trash' ) ) . '">' . __( 'Empty trash', 'everest-forms' ) . '</a></div>';
		}
	}

	/**
	 * Get a list of hidden columns.
	 *
	 * @return array
	 */
	protected function get_hidden_columns() {
		return get_hidden_columns( $this->screen );
	}

	/**
	 * Prepare table list items.
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items( $args = array() ) {
		global $wpdb;

		$per_page = $this->get_items_per_page( 'form_entries_per_page', 20 );
		$columns = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$current_page = $this->get_pagenum();

	    $query = 'SELECT form_id, evf_entry_id, created_at, meta_key, meta_value FROM wp_evf_entries INNER JOIN wp_evf_entrymeta WHERE wp_evf_entries.id = wp_evf_entrymeta.evf_entry_id AND status = "publish" ';
	    
	    $selected_form = get_option( 'evf_selected_form_in_entries', 'All forms');
	    
	    $selected_form = (int) $selected_form;

	    $form_id = ( isset( $_POST['select-form'] ) && isset( $_POST['form_id'] ) ) ? $_POST['form_id'] : $selected_form;

	    if( ( isset( $form_id )  ) && is_numeric( $form_id )  && $form_id !== 0) {
	    	$query = 'SELECT form_id, evf_entry_id, created_at, meta_key, meta_value FROM wp_evf_entries INNER JOIN wp_evf_entrymeta WHERE form_id = '. $form_id .' AND wp_evf_entries.id = wp_evf_entrymeta.evf_entry_id AND status = "publish" ';
	    }

	    // if( isset( $_GET['status'] ) && $_GET['status'] =='trash' ) {
	    // 	$query = 'SELECT form_id, evf_entry_id, created_at, meta_key, meta_value FROM wp_evf_entries INNER JOIN wp_evf_entrymeta WHERE form_id = '. $form_id .' AND wp_evf_entries.id = wp_evf_entrymeta.evf_entry_id AND status = "publish" ';	    	
	    // }

	   	$results = $wpdb->get_results( $query );
	   
	   	$array = [];

		foreach( $results as $val ) {
		    $array[ $val->evf_entry_id ]['evf_entry_id'] = ( ! isset( $array[ $val->evf_entry_id ]['evf_entry_id'] ) ) ? $val->evf_entry_id : $array[ $val->evf_entry_id ]['evf_entry_id'];
		    $array[ $val->evf_entry_id ]['created_at'] = (! isset( $array[ $val->evf_entry_id ]['created_at'] ) ) ? $val->created_at : $array[ $val->evf_entry_id ]['created_at'];
		    $array[ $val->evf_entry_id ]['form_id'] = (! isset( $array[ $val->evf_entry_id ]['form_id'] ) ) ? $val->form_id : $array[ $val->evf_entry_id ]['form_id'];
		    $array[ $val->evf_entry_id ][ $val->meta_key ] =  $val->meta_value;
		}

		$array = json_decode( json_encode( array_values( $array ) ) );
		
		$this->items = $array;	 
				
		$total_items = count( $this->items );

		$this->set_pagination_args( array(
    		'total_items' => $total_items,                 
    		'per_page'    => $per_page                     
 		) );
    	
	}

	/**
	 * Set _column_headers property for table list
	 */
	protected function prepare_column_headers() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}
}
