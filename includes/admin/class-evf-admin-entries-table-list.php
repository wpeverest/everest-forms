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
	 * Form ID.
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * Forms object.
	 *
	 * @var EVF_Form_Handler
	 */
	public $form;

	/**
	 * Forms object.
	 *
	 * @var EVF_Form_Handler[]
	 */
	public $forms;

	/**
	 * Number of different entry types.
	 *
	 * @since 1.1.0
	 *
	 * @var int
	 */
	public $counts;

	/**
	 * Form data as an array.
	 *
	 * @var array
	 */
	public $form_data;

	/**
	 * Initialize the log table list.
	 */
	public function __construct() {
		// Fetch all forms.
		$this->forms = EVF()->form->get();

		// Check that the user has created at least one form.
		if ( ! empty( $this->forms ) ) {
			$this->form_id = ! empty( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : apply_filters( 'everest_forms_entry_list_default_form_id', absint( $this->forms[0]->ID ) );
			$this->form    = EVF()->form->get( $this->form_id );
			$this->form_data = ! empty( $this->form->post_content ) ? evf_decode( $this->form->post_content ) : '';
		}

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
	 * Get the entry counts for various types of entries.
	 *
	 * @since 1.1.0
	 */
	public function get_counts() {
		$this->counts          = array();
		$this->counts['total'] = EVF_Admin_Entries::get_entries(
			array(
				'form_id' => $this->form_id,
			),
			true
		);

		$this->counts = apply_filters( 'everest_forms_entries_table_counts', $this->counts, $this->form_data );
	}

	/**
	 * Retrieve the view types.
	 *
	 * @since 1.1.6
	 */
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
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns               = array();
		$columns['cb']         = '<input type="checkbox" />';
		$columns               = $this->get_columns_form_fields( $columns );
		$columns['date']       = esc_html__( 'Date', 'everest-forms' );
		$columns['actions']    = esc_html__( 'Actions', 'everest-forms' );

		return apply_filters( 'everest_forms_entries_table_columns', $columns, $this->form_data );
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'id'   => array( 'title', false ),
			'date' => array( 'date', false ),
		);
	}

	/**
	 * Get the list of fields, that are disallowed to be displayed as column in a table.
	 *
	 * @return array
	 */
	public static function get_columns_form_disallowed_fields() {
		return (array) apply_filters( 'everest_forms_entries_table_fields_disallow', array( 'divider', 'html', 'pagebreak', 'captcha' ) );
	}

	/**
	 * Logic to determine which fields are displayed in the table columns.
	 *
	 * @param array $columns
	 * @param int   $display
	 *
	 * @return array
	 */
	public function get_columns_form_fields( $columns = array(), $display = 3 ) {
		$entry_columns = EVF()->form->get_meta( $this->form_id, 'entry_columns' );

		if ( ! $entry_columns && ! empty( $this->form_data['form_fields'] ) ) {
			$x = 0;
			foreach ( $this->form_data['form_fields'] as $id => $field ) {
				if ( ! in_array( $field['type'], self::get_columns_form_disallowed_fields(), true ) && $x < $display ) {
					$columns[ 'evf_field_' . $id ] = ! empty( $field['label'] ) ? wp_strip_all_tags( $field['label'] ) : esc_html__( 'Field', 'everest-forms' );
					$x++;
				}
			}
		} else {
			foreach ( $entry_columns as $id ) {
				// Check to make sure the field as not been removed.
				if ( empty( $this->form_data['form_fields'][ $id ] ) ) {
					continue;
				}

				$columns[ 'evf_field_' . $id ] = ! empty( $this->form_data['form_fields'][ $id ]['label'] ) ? wp_strip_all_tags( $this->form_data['form_fields'][ $id ]['label'] ) : esc_html__( 'Field', 'everest-forms' );
			}
		}

		return $columns;
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
	 * Show specific form fields.
	 *
	 * @param  object $entry
	 * @param  string $column_name
	 * @return string
	 */
	public function column_form_field( $entry, $column_name ) {
		$field_id     = str_replace( 'evf_field_', '', $column_name );
		$entry_fields = $entry->fields;

		if ( ! empty( $entry_fields->{$field_id} ) ) {
			$value = $entry_fields->{$field_id};

			// Limit to 5 lines.
			$lines = explode( "\n", $value );
			$value = array_slice( $lines, 0, 4 );
			$value = implode( "\n", $value );

			if ( count( $lines ) > 5 ) {
				$value .= '&hellip;';
			} elseif ( strlen( $value ) > 75 ) {
				$value = substr( $value, 0, 75 ) . '&hellip;';
			}

			$value = nl2br( wp_strip_all_tags( trim( $value ) ) );

			return apply_filters( 'everest_forms_html_field_value', $value, $entry_fields->{$field_id}, $this->form_data, 'entry-table' );

		} else {
			return '-';
		}
	}

	/**
	 * Renders the columns.
	 *
	 * @param  object $entry
	 * @param  string $column_name
	 * @return string
	 */
	public function column_default( $entry, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				$value = absint( $entry->entry_id );
				break;

			case 'date':
				$value = date_i18n( get_option( 'date_format' ), strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 ) );
				break;

			default:
				if ( false !== strpos( $column_name, 'evf_field_' ) ) {
					$value = $this->column_form_field( $entry, $column_name );
				} else {
					$value = '';
				}
		}

		return apply_filters( 'everest_forms_entry_table_column_value', $value, $entry, $column_name );
	}

	/**
	 * Render the actions column.
	 *
	 * @param  object $entry
	 * @return string
	 */
	public function column_actions( $entry ) {
		$actions = array(
			'view'   => '<a href="' . esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $entry->form_id . '&amp;view-entry=' . $entry->entry_id ) ) . '">' . esc_html__( 'View', 'everest-forms' ) . '</a>',
			/* translators: %s: entry name */
			'delete' => '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete form entry', 'everest-forms' ) . '" href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'delete' => $entry->entry_id,
			), admin_url( 'admin.php?page=evf-entries' ) ), 'delete-entry' ) ) . '">' . esc_html__( 'Delete', 'everest-forms' ) . '</a>',
		);

		return implode( ' <span class="sep">|</span> ', apply_filters( 'everest_forms_entry_table_actions', $actions, $entry ) );
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
		if ( $which === 'bottom' ) {
			return;
		}

		$all_forms = evf_get_all_forms();
		$selected = isset( $_POST['form_id'] ) ? $_POST['form_id'] : get_option( 'evf_selected_form_in_entries', key( $all_forms ) );
        $selected_form = update_option( 'evf_selected_form_in_entries', $selected );

		?><select id = "form-select" name ="form_id">
                        <?php 
                            foreach( $all_forms as $key => $form ) {
                                echo '<option value="'. $key .'" '. selected( $selected, $key ) .'>'. $form .'</option>';
                            }
                        ?>

        </select>
        <button type="submit" class="button button-primary" name="select-form">Filter</button>
        <?php
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

		$this->get_counts();

		// Get entries.
		$order        = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
		$orderby      = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'entry_id';
		$per_page     = $this->get_items_per_page( 'evf_forms_per_page' );
		$current_page = $this->get_pagenum();
		$data_args    = array(
			'form_id' => $this->form_id,
			'number'  => $per_page,
			'offset'  => $per_page * ( $current_page - 1 ),
			'order'   => $order,
			'orderby' => $orderby,
		);

		if ( ! empty( $_GET['status'] ) ) {
			$data_args['status'] = sanitize_text_field( $_GET['status'] );
		}

		$selected_form = get_option( 'evf_selected_form_in_entries', key( evf_get_all_forms() ) );

		$selected_form = (int) $selected_form;

		$form_id = ( isset( $_POST['select-form'] ) && isset( $_POST['form_id'] ) ) ? $_POST['form_id'] : $selected_form;

		if( ( isset( $form_id )  ) && is_numeric( $form_id )  && $form_id !== 0) {
			$query = $wpdb->prepare( "SELECT {$wpdb->prefix}evf_entries.entry_id, {$wpdb->prefix}evf_entrymeta.entry_id, form_id, date_created, meta_key, meta_value FROM {$wpdb->prefix}evf_entries INNER JOIN {$wpdb->prefix}evf_entrymeta WHERE form_id = %d AND {$wpdb->prefix}evf_entries.entry_id = {$wpdb->prefix}evf_entrymeta.entry_id AND status = %s ", $form_id, "publish" );
		}

		if( ( isset( $_GET['status'] ) && $_GET['status'] == 'trash') ) {
			$query = $wpdb->prepare( "SELECT form_id, {$wpdb->prefix}evf_entries.entry_id, {$wpdb->prefix}evf_entrymeta.entry_id, date_created, meta_key, meta_value FROM {$wpdb->prefix}evf_entries INNER JOIN {$wpdb->prefix}evf_entrymeta WHERE {$wpdb->prefix}evf_entries.entry_id = {$wpdb->prefix}evf_entrymeta.entry_id AND status = %s ","trash");
		}

		$results = $wpdb->get_results( $query );
		$array = [];

		foreach( $results as $val ) {
			$array[ $val->entry_id ]['entry_id'] = ( ! isset( $array[ $val->entry_id ]['entry_id'] ) ) ? $val->entry_id : $array[ $val->entry_id ]['entry_id'];
			$array[ $val->entry_id ]['date_created'] = (! isset( $array[ $val->entry_id ]['date_created'] ) ) ? $val->date_created : $array[ $val->entry_id ]['date_created'];
			$array[ $val->entry_id ]['form_id'] = (! isset( $array[ $val->entry_id ]['form_id'] ) ) ? $val->form_id : $array[ $val->entry_id ]['form_id'];
			$array[ $val->entry_id ]['fields'][ $val->meta_key ] =  $val->meta_value;
		}

		$array = json_decode( json_encode( array_values( $array ) ) );

		$this->items = $array;

		$total_items = count( $this->items );

		$this->items = array_slice( $this->items,( ( $current_page-1 ) * $per_page ),$per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );
	}
}
