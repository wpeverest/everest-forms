<?php
/**
 * EverestForms Entries Table List
 *
 * @package EverestForms\Admin
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Entries table list class.
 */
class EVF_Admin_Entries_Table_List extends WP_List_Table {

	/**
	 * Initialize the log table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'entry',
			'plural'   => 'entries',
			'ajax'     => false,
		) );
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		esc_html_e( 'Whoops, it appears you do not have any form entries yet.', 'everest-forms' );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'name'    => __( 'Entry', 'everest-forms' ),
			'email'   => __( 'Email', 'everest-forms' ),
			'date'    => __( 'Entry Date', 'everest-forms' ),
			'actions' => __( 'Action', 'everest-forms' ),
		);
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'date' => array( 'date', false ),
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  object $entry Entry object.
	 * @return string
	 */
	public function column_cb( $entry ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $entry->entry_id );
	}

	/**
	 * Return id column.
	 *
	 * @param  object $item
	 *
	 * @return string
	 */
	public function column_name( $items ) {
		$actions = array(
			'view'      => sprintf( '<a href="?page=%s&action=%s&id=%s">View</a>', $_REQUEST['page'], 'view', $items->entry_id ),
			'trash'    => sprintf( '<a href="?page=%s&action=%s&id=%s">Trash</a>', $_REQUEST['page'],'trash', $items->entry_id ),
		);

		if( isset( $_GET['status'] ) && $_GET['status'] == 'trash' ) {
			$actions = array(
				'view'      => sprintf( '<a href="?page=%s&action=%s&id=%s">View</a>', $_REQUEST['page'], 'view', $items->entry_id ),
				'delete'    => sprintf( '<a href="?page=%s&action=%s&id=%s">Delete Permanently</a>', $_REQUEST['page'],'delete', $items->entry_id ),
				'untrash'    => sprintf( '<a href="?page=%s&action=%s&id=%s">Restore</a>', $_REQUEST['page'],'untrash', $items->entry_id ),
			);
		}

		return sprintf('%1$s %2$s', $items->entry_id, $this->row_actions($actions) );
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
		return isset( $items->date_created ) ? $items->date_created : '';
	}

	/**
	 * Return action column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_actions( $items ) {
		return '<a href=" '. admin_url('admin.php?page=display-evf-entries&action=edit&id='. $items->entry_id .' ') .'">Details</a>';
	}

	protected function get_views() {
		global $wpdb;
		$status_links = array();

		$selected_form = get_option( 'evf_selected_form_in_entries', 'All forms');

		$selected_form = (int) $selected_form;

		$form_id = ( isset( $_POST['select-form'] ) && isset( $_POST['form_id'] ) ) ? $_POST['form_id'] : $selected_form;

		if( ( isset( $form_id )  ) && is_numeric( $form_id )  && $form_id !== 0) {

			$query = 'SELECT entry_id FROM wp_evf_entries WHERE form_id = '. $form_id .' AND status = "publish" ';
			$query_1 = 'SELECT entry_id FROM wp_evf_entries WHERE form_id = '. $form_id .' AND status = "trash" ';

		} else {
			$query = 'SELECT entry_id FROM wp_evf_entries WHERE status = "publish" ';
			$query_1 = 'SELECT entry_id FROM wp_evf_entries WHERE status = "trash" ';
		}

		$results = $wpdb->get_results( $query );

		$total_items = count($results);

		$results = $wpdb->get_results( $query_1 );

		$total_trash_items = count($results);

		/* translators: %s: count */
		$status_links['all'] = "<a href='admin.php?page=evf-entries&status=all'>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_items, 'entries', 'everest-forms' ), number_format_i18n( $total_items ) ) . '</a>';

		$status_links['trash'] = "<a href='admin.php?page=evf-entries&status=trash'>" . sprintf( _nx( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $total_trash_items, 'entries', 'everest-forms' ), number_format_i18n( $total_trash_items ) ) . '</a>';

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
	 * Prepare table list items.
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items( $args = array() ) {
		global $wpdb;

		$per_page     = $this->get_items_per_page( 'evf_forms_per_page' );
		$current_page = $this->get_pagenum();

		$query = 'SELECT wp_evf_entries.entry_id, wp_evf_entrymeta.entry_id, form_id, date_created, meta_key, meta_value FROM wp_evf_entries INNER JOIN wp_evf_entrymeta WHERE wp_evf_entries.entry_id = wp_evf_entrymeta.entry_id AND status = "publish" ';

		$selected_form = get_option( 'evf_selected_form_in_entries', 'All forms');

		$selected_form = (int) $selected_form;

		$form_id = ( isset( $_POST['select-form'] ) && isset( $_POST['form_id'] ) ) ? $_POST['form_id'] : $selected_form;

		if( ( isset( $form_id )  ) && is_numeric( $form_id )  && $form_id !== 0) {
			$query = 'SELECT form_id, entry_id, date_created, meta_key, meta_value FROM wp_evf_entries INNER JOIN wp_evf_entrymeta WHERE form_id = '. $form_id .' AND wp_evf_entries.entry_id = wp_evf_entrymeta.entry_id AND status = "publish" ';
		}

		if( ( isset( $_GET['status'] ) && $_GET['status'] == 'trash') ) {
			$query = 'SELECT form_id, entry_id, date_created, meta_key, meta_value FROM wp_evf_entries INNER JOIN wp_evf_entrymeta WHERE wp_evf_entries.entry_id = wp_evf_entrymeta.entry_id AND status = "trash" ';
		}

		$results = $wpdb->get_results( $query );
		$array = [];

		foreach( $results as $val ) {
			$array[ $val->entry_id ]['entry_id'] = ( ! isset( $array[ $val->entry_id ]['entry_id'] ) ) ? $val->entry_id : $array[ $val->entry_id ]['entry_id'];
			$array[ $val->entry_id ]['date_created'] = (! isset( $array[ $val->entry_id ]['date_created'] ) ) ? $val->date_created : $array[ $val->entry_id ]['date_created'];
			$array[ $val->entry_id ]['form_id'] = (! isset( $array[ $val->entry_id ]['form_id'] ) ) ? $val->form_id : $array[ $val->entry_id ]['form_id'];
			$array[ $val->entry_id ][ $val->meta_key ] =  $val->meta_value;
		}

		$array = json_decode( json_encode( array_values( $array ) ) );

		$this->items = $array;

		$total_items = count( $this->items );

		$current_page = $this->get_pagenum();

		$this->items = array_slice( $this->items,( ( $current_page-1 ) * $per_page ),$per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );
	}
}
