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
		$this->forms = evf_get_all_forms( true );

		// Check that the user has created at least one form.
		if ( ! empty( $this->forms ) ) {
			$this->form_id   = ! empty( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : apply_filters( 'everest_forms_entry_list_default_form_id', key( $this->forms ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$this->form      = evf()->form->get( $this->form_id );
			$this->form_data = ! empty( $this->form->post_content ) ? evf_decode( $this->form->post_content ) : '';
		}

		parent::__construct(
			array(
				'singular' => 'entry',
				'plural'   => 'entries',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @since 1.5.3
	 *
	 * @return string|false The action name or False if no action was selected.
	 */
	public function current_action() {
		if ( isset( $_REQUEST['export_action'] ) && ! empty( $_REQUEST['export_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'delete_all';
		}

		return parent::current_action();
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
		$columns            = array();
		$columns['cb']      = '<input type="checkbox" />';
		$columns            = apply_filters( 'everest_forms_entries_table_form_fields_columns', $this->get_columns_form_fields( $columns ), $this->form_id, $this->form_data );
		$columns['date']    = esc_html__( 'Date Created', 'everest-forms' );
		$columns['actions'] = esc_html__( 'Actions', 'everest-forms' );

		return apply_filters( 'everest_forms_entries_table_columns', $columns, $this->form_data );
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array();

		if ( isset( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$sortable_columns = array(
				'date' => array( 'date_created', false ),
			);
		}

		return array_merge(
			array(
				'id' => array( 'title', false ),
			),
			$sortable_columns
		);
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 1.6.0
	 *
	 * @param object $entry Entry data.
	 */
	public function single_row( $entry ) {
		if ( empty( $_GET['status'] ) || ( isset( $_GET['status'] ) && 'trash' !== $_GET['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<tr class="' . ( $entry->viewed ? 'read' : 'unread' ) . '">';
			$this->single_row_columns( $entry );
			echo '</tr>';
		} else {
			parent::single_row( $entry );
		}
	}

	/**
	 * Get the list of fields, that are disallowed to be displayed as column in a table.
	 *
	 * @return array
	 */
	public static function get_columns_form_disallowed_fields() {
		return (array) apply_filters( 'everest_forms_entries_table_fields_disallow', array( 'html', 'title', 'captcha' ) );
	}

	/**
	 * Logic to determine which fields are displayed in the table columns.
	 *
	 * @param array $columns List of colums.
	 * @param int   $display Numbers of columns to display.
	 *
	 * @return array
	 */
	public function get_columns_form_fields( $columns = array(), $display = 3 ) {
		$entry_columns = evf()->form->get_meta( $this->form_id, 'entry_columns' );

		if ( ! $entry_columns && ! empty( $this->form_data['form_fields'] ) ) {
			$x = 0;
			foreach ( $this->form_data['form_fields'] as $id => $field ) {
				if ( ! in_array( $field['type'], self::get_columns_form_disallowed_fields(), true ) && $x < $display ) {
					$columns[ 'evf_field_' . $id ] = ! empty( $field['label'] ) ? wp_strip_all_tags( $field['label'] ) : esc_html__( 'Field', 'everest-forms' );
					$x++;
				}
			}
		} elseif ( ! empty( $entry_columns ) ) {
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
	 * @param  object $entry Entry object.
	 * @param  string $column_name Column Name.
	 * @return string
	 */
	public function column_form_field( $entry, $column_name ) {
		$field_id = str_replace( 'evf_field_', '', $column_name );
		$meta_key = isset( $this->form_data['form_fields'][ $field_id ]['meta-key'] ) ? strtolower( $this->form_data['form_fields'][ $field_id ]['meta-key'] ) : $field_id;

		if ( ! empty( $entry->meta[ $meta_key ] ) ) { // phpcs:ignore WordPress.Security.EscapeOutput
			$value = $entry->meta[ $meta_key ];

			if ( evf_is_json( $value ) ) {
				$field_value = json_decode( $value, true );
				$value       = $field_value['value'];
			}

			if ( is_serialized( $value ) ) {
				$field_html  = array();
				$field_value = maybe_unserialize( $value );
				$field_label = ! empty( $field_value['label'] ) ? evf_clean( $field_value['label'] ) : $field_value;

				if ( is_array( $field_label ) ) {
					foreach ( $field_label as $value ) {
						$field_html[] = esc_html( $value );
					}

					$value = implode( ' | ', $field_html );
				} else {
					$value = esc_html( $field_label );
				}
			}

			// Limit to 5 lines.
			if ( false === strpos( $value, 'http' ) ) {
				$lines = explode( "\n", $value );
				$value = array_slice( $lines, 0, 4 );
				$value = implode( "\n", $value );

				if ( count( $lines ) > 5 ) {
					$value .= '&hellip;';
				} elseif ( strlen( $value ) > 75 ) {
					$value = substr( $value, 0, 75 ) . '&hellip;';
				}

				$value = nl2br( wp_strip_all_tags( trim( $value ) ) );
			}

			return apply_filters( 'everest_forms_html_field_value', $value, $entry->meta[ $meta_key ], $entry, 'entry-table' );
		} else {
			return '<span class="na">&mdash;</span>';
		}
	}

	/**
	 * Renders the columns.
	 *
	 * @param  object $entry Entry object.
	 * @param  string $column_name Column Name.
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
				break;
		}

		return apply_filters( 'everest_forms_entry_table_column_value', $value, $entry, $column_name );
	}

	/**
	 * Render the actions column.
	 *
	 * @param  object $entry Entry object.
	 * @return string
	 */
	public function column_actions( $entry ) {
		if ( 'trash' !== $entry->status ) {
			$actions = array(
				'view'  => '<a href="' . esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $entry->form_id . '&amp;view-entry=' . $entry->entry_id ) ) . '">' . esc_html__( 'View', 'everest-forms' ) . '</a>',
				/* translators: %s: entry name */
				'trash' => '<a class="submitdelete" aria-label="' . esc_attr__( 'Trash form entry', 'everest-forms' ) . '" href="' . esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'trash'   => $entry->entry_id,
								'form_id' => $this->form_id,
							),
							admin_url( 'admin.php?page=evf-entries' )
						),
						'trash-entry'
					)
				) . '">' . esc_html__( 'Trash', 'everest-forms' ) . '</a>',
			);
		} else {
			$actions = array(
				'untrash' => '<a aria-label="' . esc_attr__( 'Restore form entry from trash', 'everest-forms' ) . '" href="' . esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'untrash' => $entry->entry_id,
								'form_id' => $this->form_id,
							),
							admin_url( 'admin.php?page=evf-entries' )
						),
						'untrash-entry'
					)
				) . '">' . esc_html__( 'Restore', 'everest-forms' ) . '</a>',
				'delete'  => '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete form entry permanently', 'everest-forms' ) . '" href="' . esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'delete'  => $entry->entry_id,
								'form_id' => $this->form_id,
							),
							admin_url( 'admin.php?page=evf-entries' )
						),
						'delete-entry'
					)
				) . '">' . esc_html__( 'Delete Permanently', 'everest-forms' ) . '</a>',
			);
		}

		return implode( ' <span class="sep">|</span> ', apply_filters( 'everest_forms_entry_table_actions', $actions, $entry ) );
	}

	/**
	 * Get the status label for entries.
	 *
	 * @param string $status_name Status name.
	 * @param int    $amount      Amount of entries.
	 * @return array
	 */
	private function get_status_label( $status_name, $amount ) {
		$statuses = evf_get_entry_statuses( $this->form_data );

		if ( isset( $statuses[ $status_name ] ) ) {
			return array(
				'singular' => sprintf( '%s <span class="count">(<span class="%s-count">%s</span>)</span>', esc_html( $statuses[ $status_name ] ), $status_name, $amount ),
				'plural'   => sprintf( '%s <span class="count">(<span class="%s-count">%s</span>)</span>', esc_html( $statuses[ $status_name ] ), $status_name, $amount ),
				'context'  => '',
				'domain'   => 'everest-forms',
			);
		}

		return array(
			'singular' => sprintf( '%s <span class="count">(<span class="%s-count">%s</span>)</span>', esc_html( $statuses[ $status_name ] ), $status_name, $amount ),
			'plural'   => sprintf( '%s <span class="count">(%s)</span>', esc_html( $status_name ), $amount ),
			'context'  => '',
			'domain'   => 'everest-forms',
		);
	}

	/**
	 * Table list views.
	 *
	 * @return array
	 */
	protected function get_views() {
		$status_links  = array();
		$num_entries   = evf_get_count_entries_by_status( $this->form_id );
		$total_entries = apply_filters( 'everest_forms_total_entries_count', (int) $num_entries['publish'], $num_entries, $this->form_id );
		$statuses      = array_keys( evf_get_entry_statuses( $this->form_data ) );
		$class         = empty( $_REQUEST['status'] ) ? ' class="current"' : ''; // phpcs:ignore WordPress.Security.NonceVerification

		/* translators: %s: count */
		$status_links['all'] = "<a href='admin.php?page=evf-entries&amp;form_id=$this->form_id'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_entries, 'entries', 'everest-forms' ), number_format_i18n( $total_entries ) ) . '</a>';

		foreach ( $statuses as $status_name ) {
			$class = '';

			if ( 'publish' === $status_name ) {
				continue;
			}

			if ( isset( $_REQUEST['status'] ) && sanitize_key( wp_unslash( $_REQUEST['status'] ) ) === $status_name ) { // phpcs:ignore WordPress.Security.NonceVerification
				$class = ' class="current"';
			}

			$label = $this->get_status_label( $status_name, $num_entries[ $status_name ] );

			$status_links[ $status_name ] = "<a href='admin.php?page=evf-entries&amp;form_id=$this->form_id&amp;status=$status_name'$class>" . sprintf( translate_nooped_plural( $label, $num_entries[ $status_name ] ), number_format_i18n( $num_entries[ $status_name ] ) ) . '</a>';
		}

		return apply_filters( 'everest_forms_entries_table_views', $status_links, $num_entries, $this->form_data );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$actions = array(
				'untrash' => __( 'Restore', 'everest-forms' ),
				'delete'  => __( 'Delete Permanently', 'everest-forms' ),
			);
		} else {
			$actions = array(
				'trash' => __( 'Move to Trash', 'everest-forms' ),
			);
		}

		return apply_filters( 'everest_forms_entry_bulk_actions', $actions );
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 1.2.0
	 */
	public function process_bulk_action() {
		$pagenum   = $this->get_pagenum();
		$doaction  = $this->current_action();
		$entry_ids = isset( $_REQUEST['entry'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['entry'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
		$count     = 0;

		if ( $doaction ) {
			check_admin_referer( 'bulk-entries' );

			$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted' ), wp_get_referer() );
			if ( ! $sendback ) {
				$sendback = admin_url( 'admin.php?page=evf-entries' );
			}
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			if ( ! isset( $entry_ids ) ) {
				wp_safe_redirect( $sendback );
				exit;
			}

			switch ( $doaction ) {
				case 'star':
				case 'unstar':
					foreach ( $entry_ids as $entry_id ) {
						if ( EVF_Admin_Entries::update_status( $entry_id, $doaction ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						/* translators: %d: number of entries, %s: entries status */
						sprintf( _n( '%1$d entry successfully %2$s.', '%1$d entries successfully %2$s.', $count, 'everest-forms' ), $count, 'star' === $doaction ? 'starred' : 'unstarred' ),
						'updated'
					);
					break;
				case 'read':
				case 'unread':
					foreach ( $entry_ids as $entry_id ) {
						if ( EVF_Admin_Entries::update_status( $entry_id, $doaction ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						/* translators: %d: number of entries, %s: entries status */
						sprintf( _n( '%1$d entry successfully marked as %2$s.', '%1$d entries successfully marked as %2$s.', $count, 'everest-forms' ), $count, $doaction ),
						'updated'
					);
					break;
				case 'trash':
					foreach ( $entry_ids as $entry_id ) {
						if ( EVF_Admin_Entries::update_status( $entry_id, 'trash' ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						/* translators: %d: number of entries */
						sprintf( _n( '%d entry moved to the Trash.', '%d entries moved to the Trash.', $count, 'everest-forms' ), $count ),
						'updated'
					);
					break;
				case 'untrash':
					foreach ( $entry_ids as $entry_id ) {
						if ( EVF_Admin_Entries::update_status( $entry_id, 'publish' ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						/* translators: %d: number of entries */
						sprintf( _n( '%d entry restored from the Trash.', '%d entries restored from the Trash.', $count, 'everest-forms' ), $count ),
						'updated'
					);
					break;
				case 'delete':
					foreach ( $entry_ids as $entry_id ) {
						if ( EVF_Admin_Entries::remove_entry( $entry_id ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						/* translators: %d: number of entries */
						sprintf( _n( '%d entry permanently deleted.', '%d entries permanently deleted.', $count, 'everest-forms' ), $count ),
						'updated'
					);
					break;
			}
			$sendback = remove_query_arg( array( 'action', 'action2' ), $sendback );

			wp_safe_redirect( $sendback );
			exit();
		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) && isset( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			exit();
		}
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which The location of the extra table nav markup.
	 */
	protected function extra_tablenav( $which ) {
		$num_entries = evf_get_count_entries_by_status( $this->form_id );
		$show_export = isset( $_GET['status'] ) && 'trash' === $_GET['status'] ? false : true; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="alignleft actions">
		<?php
		if ( ! empty( $this->forms ) && 'top' === $which ) {
			ob_start();
			$this->forms_dropdown();
			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				echo $output; // @codingStandardsIgnoreLine
				submit_button( __( 'Filter', 'everest-forms' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );

				// Export CSV submit button.
				if ( apply_filters( 'everest_forms_enable_csv_export', $show_export ) && current_user_can( 'export' ) ) {
					submit_button( __( 'Export CSV', 'everest-forms' ), '', 'export_action', false, array( 'id' => 'export-csv-submit' ) );
				}
			}
		}

		if ( $num_entries['trash'] && isset( $_GET['status'] ) && 'trash' === $_GET['status'] && current_user_can( 'manage_everest_forms' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			submit_button( __( 'Empty Trash', 'everest-forms' ), 'apply', 'delete_all', false );
		}
		?>
		</div>
		<?php
	}

	/**
	 * Display a form dropdown for filtering entries.
	 */
	public function forms_dropdown() {
		$forms   = evf_get_all_forms( true );
		$form_id = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : $this->form_id; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<label for="filter-by-form" class="screen-reader-text"><?php esc_html_e( 'Filter by form', 'everest-forms' ); ?></label>
		<select name="form_id" id="filter-by-form">
			<?php foreach ( $forms as $id => $form ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $form_id, $id ); ?>><?php echo esc_html( $form ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		$per_page     = $this->get_items_per_page( 'evf_entries_per_page' );
		$current_page = $this->get_pagenum();

		// Query args.
		$args = array(
			'status'  => 'publish',
			'form_id' => $this->form_id,
			'limit'   => $per_page,
			'offset'  => $per_page * ( $current_page - 1 ),
		);

		// Handle the status query.
		if ( ! empty( $_REQUEST['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['status'] = sanitize_key( wp_unslash( $_REQUEST['status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// Handle the search query.
		if ( ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['search'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['orderby'] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_REQUEST['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// Get the entries.
		$entries     = evf_search_entries( $args );
		$this->items = array_map( 'evf_get_entry', $entries );

		// Get total items.
		$args['limit']  = -1;
		$args['offset'] = 0;
		$total_items    = count( evf_search_entries( $args ) );

		// Set the pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
