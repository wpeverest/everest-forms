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
	 * Raw data to export.
	 *
	 * @var array
	 */
	protected $row_data = array();

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

		$entries_table_list->process_bulk_action();
		$entries_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">

			<?php settings_errors(); ?>

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
						<form id="entries-list" method="get">
							<input type="hidden" name="page" value="evf-entries" />
							<?php
								ob_start();
								$entries_table_list->forms_dropdown();
								$output = ob_get_clean();

								if ( ! empty( $output ) ) {
									echo $output;
									submit_button( __( 'Filter', 'everest-forms' ), '', '', false, array( 'id' => 'post-query-submit' ) );
								}
							?>
						</form>
					<?php else : ?>
						<a class="everest-forms-BlankState-cta button-primary button" target="_blank" href="https://docs.wpeverest.com/docs/everest-forms/entry-management/?utm_source=blankslate&utm_medium=entry&utm_content=entriesdoc&utm_campaign=everestformplugin"><?php esc_html_e( 'Learn more about entries', 'everest-forms' ); ?></a>
						<a class="everest-forms-BlankState-cta button" href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&create-form=1' ) ); ?>"><?php esc_html_e( 'Create your first form!', 'everest-forms' ); ?></a>
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

			// Empty Trash.
			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->empty_trash();
			}

			// Export CSV.
			if ( isset( $_REQUEST['export_action'] ) ) { // WPCS: input var okay, CSRF ok.
				$this->export_csv();
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
				self::update_status( $entry_id, 'trash' );
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
				self::update_status( $entry_id, 'publish' );
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
				self::remove_entry( $entry_id );
			}
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'deleted' => 1 ), admin_url( 'admin.php?page=evf-entries' ) ) ) );
		exit();
	}

	/**
	 * Empty Trash.
	 */
	public function empty_trash() {
		global $wpdb;

		check_admin_referer( 'bulk-entries' );

		if ( isset( $_GET['form_id'] ) ) { // WPCS: input var okay, CSRF ok.
			$form_id = absint( $_GET['form_id'] ); // WPCS: input var okay, CSRF ok.

			if ( $form_id ) {
				$count     = 0;
				$results   = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}evf_entries WHERE `status` = 'trash' AND form_id = %d", $form_id ) ); // WPCS: cache ok, DB call ok.
				$entry_ids = array_map( 'intval', wp_list_pluck( $results, 'entry_id' ) );

				foreach ( $entry_ids as $entry_id ) {
					if ( self::remove_entry( $entry_id ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'empty_trash',
					'empty_trash',
					/* translators: %d: number of entries */
					sprintf( _n( '%d entry permanently deleted.', '%d entries permanently deleted.', $count, 'everest-forms' ), $count ),
					'updated'
				);
			}
		}
	}

	/**
	 * Remove entry.
	 *
	 * @param  int $entry_id Entry ID.
	 * @return bool
	 */
	public static function remove_entry( $entry_id ) {
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
	 * @param int    $entry_id Entry ID.
	 * @param string $status   Entry status.
	 */
	public static function update_status( $entry_id, $status = 'publish' ) {
		global $wpdb;

		$update = $wpdb->update(
			$wpdb->prefix . 'evf_entries',
			array( 'status' => $status ),
			array( 'entry_id' => $entry_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $update;
	}

	/*
	|--------------------------------------------------------------------------
	| CSV Exporter
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the entry object for CSV exporter.
	*/

	/**
	 * Return an array of supported column names and ids.
	 *
	 * @return array
	 */
	public function get_column_names() {
		$columns   = array();
		$form_id   = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0;
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		// Set Entry ID at first.
		$columns['entry_id'] = __( 'ID', 'everest-forms' );

		// Add whitelisted fields to export columns.
		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $field ) {
				if ( ! in_array( $field['type'], array( 'html', 'title' ), true ) ) {
					$columns[ evf_clean( $field['meta-key'] ) ] = evf_clean( $field['label'] );
				}
			}
		}

		// Set the default columns.
		$columns['status']           = __( 'Status', 'everest-forms' );
		$columns['date_created']     = __( 'Date Created', 'everest-forms' );
		$columns['date_created_gmt'] = __( 'Date Created GMT', 'everest-forms' );

		// If user details are disabled globally discard the IP and UA.
		if ( 'yes' !== get_option( 'everest_forms_disable_user_details' ) ) {
			$columns['user_device' ]    = __( 'User Device', 'everest-forms' );
			$columns['user_ip_address'] = __( 'User IP Address', 'everest-forms' );
		}

		return apply_filters( 'everest_forms_entry_export_column_names', $columns );
	}

	/**
	 * Get a csv file name.
	 *
	 * File names consist of the handle, followed by the date, followed by a hash, .csv.
	 *
	 * @param string $handle File name.
	 * @return bool|string The csv file name or false if cannot be determined.
	 */
	public static function get_csv_file_name( $handle ) {
		$handle = strtolower( 'evf-entry-export-' . str_replace( '.csv', '', $handle ) );

		if ( function_exists( 'wp_hash' ) ) {
			$date_suffix = date( 'Y-m-d', current_time( 'timestamp', true ) );
			$hash_suffix = wp_hash( $handle );
			return sanitize_file_name( implode( '-', array( $handle, $date_suffix, $hash_suffix ) ) . '.csv' );
		} else {
			evf_doing_it_wrong( __METHOD__, __( 'This method should not be called before plugins_loaded.', 'everest-forms' ), '1.3.0' );
			return false;
		}
	}

	/**
	 * Prepare data for export.
	 *
	 * @since 1.3.0
	 */
	public function prepare_data_to_export() {
		$form_id   = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0;
		$entry_ids = evf_search_entries( array(
			'limit'   => -1,
			'order'   => 'ASC',
			'form_id' => $form_id,
		) );

		// Get the entries.
		$entries        = array_map( 'evf_get_entry', $entry_ids );
		$this->row_data = array();

		foreach ( $entries as $entry ) {
			$this->row_data[] = $this->generate_row_data( $entry );
		}

		return $this->row_data;
	}

	/**
	 * Take a entry id and generate row data from it for export.
	 *
	 * @param  object $entry Entry object.
	 * @return array
	 */
	protected function generate_row_data( $entry ) {
		$columns = $this->get_column_names();
		$row     = array();

		foreach ( $columns as $column_id => $column_name ) {
			$column_id  = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
			$value     = '';

			if ( isset( $entry->meta[ $column_id] ) ) {
				// Filter for entry meta data.
				$value = apply_filters( 'everest_forms_html_field_value', $entry->meta[ $column_id ], $entry->meta[ $column_id ], $entry, 'export-csv' );

			} elseif ( in_array( $column_id, array( 'entry_id', 'status', 'date_created', 'user_device', 'user_ip_address' ), true ) ) {
				// Default and custom handling.
				$value = $entry->{$column_id};

				switch ( $column_id ) {
					case 'entry_id':
						$value = absint( $value );
					case 'status':
						$statuses = evf_get_entry_statuses();
						$value    = isset( $statuses[ $value ] ) ? $statuses[ $value ] : $value;
					break;
					case 'date_created':
						/* translators: 1: entry date 2: entry time */
						$value = sprintf( __( '%1$s %2$s', 'everest-forms' ), date_i18n( evf_date_format(), strtotime( $value ) ), date_i18n( evf_time_format(), strtotime( $value ) ) );
					break;
					default:
						$value = sanitize_text_field( $value );
					break;
				}

			} elseif ( is_callable( array( $this, "get_column_value_{$column_id}" ) ) ) {
				// Handle special columns which don't map 1:1 to entry data.
				$value = $this->{"get_column_value_{$column_id}"}( $entry );
			}

			$row[ $column_id ] = $value;
		}

		return apply_filters( 'everest_forms_entry_export_row_data', $row, $entry );
	}

	/**
	 * Get date in GMT format.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	protected function get_column_value_date_created_gmt( $entry ) {
		$timestamp = false;

		if ( isset( $entry->date_created ) ) {
			$timestamp = strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 );
		}

		/* translators: 1: entry date 2: entry time */
		return sprintf( __( '%1$s %2$s', 'everest-forms' ), date_i18n( evf_date_format(), $timestamp ), date_i18n( evf_time_format(), $timestamp ) );
	}

	/**
	 * Do the entries export.
	 *
	 * @since 1.3.0
	 */
	public function export_csv() {
		$form_id   = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0;
		$file_name = $this->get_csv_file_name( get_the_title( $form_id ) );

		if ( ! current_user_can( 'export' ) ) {
			return;
		}

		$this->prepare_data_to_export();
		$this->send_headers( $file_name );
		$this->send_content( chr( 239 ) . chr( 187 ) . chr( 191 ) . $this->export_column_headers() . $this->export_rows() );
		die();
	}

	/**
	 * Set the export content.
	 *
	 * @param string $csv_data All CSV content.
	 */
	public function send_content( $csv_data ) {
		echo $csv_data; // @codingStandardsIgnoreLine
	}

	/**
	 * Export column headers in CSV format.
	 *
	 * @return string
	 */
	protected function export_column_headers() {
		$columns    = $this->get_column_names();
		$export_row = array();
		$buffer     = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		ob_start();

		foreach ( $columns as $column_id => $column_name ) {
			$export_row[] = $this->format_data( $column_name );
		}

		$this->fputcsv( $buffer, $export_row );

		return ob_get_clean();
	}

	/**
	 * Export rows in CSV format.
	 *
	 * @return string
	 */
	protected function export_rows() {
		$data   = $this->row_data;
		$buffer = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		ob_start();

		array_walk( $data, array( $this, 'export_row' ), $buffer );

		return apply_filters( "everest_forms_entry_export_rows", ob_get_clean(), $this );
	}

	/**
	 * Export rows to an array ready for the CSV.
	 *
	 * @since 3.1.0
	 * @param array    $row_data Data to export.
	 * @param string   $key Column being exported.
	 * @param resource $buffer Output buffer.
	 */
	protected function export_row( $row_data, $key, $buffer ) {
		$columns    = $this->get_column_names();
		$export_row = array();

		foreach ( $columns as $column_id => $column_name ) {
			if ( isset( $row_data[ $column_id ] ) ) {
				$export_row[] = $this->format_data( $row_data[ $column_id ] );
			} else {
				$export_row[] = '';
			}
		}

		$this->fputcsv( $buffer, $export_row );
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * Malicious input can inject formulas into CSV files, opening up the possibility
	 * for phishing attacks and disclosure of sensitive information.
	 *
	 * Additionally, Excel exposes the ability to launch arbitrary commands through
	 * the DDE protocol.
	 *
	 * @see http://www.contextis.com/resources/blog/comma-separated-vulnerabilities/
	 * @see https://hackerone.com/reports/72785
	 *
	 * @param  string $data CSV field to escape.
	 * @return string
	 */
	public function escape_data( $data ) {
		$active_content_triggers = array( '=', '+', '-', '@' );

		if ( in_array( mb_substr( $data, 0, 1 ), $active_content_triggers, true ) ) {
			$data = "'" . $data . "'";
		}

		return $data;
	}

	/**
	 * Format and escape data ready for the CSV file.
	 *
	 * @param  string $data Data to format.
	 * @return string
	 */
	public function format_data( $data ) {
		if ( ! is_scalar( $data ) ) {
			$data = ''; // Not supported.
		} elseif ( is_bool( $data ) ) {
			$data = $data ? 1 : 0;
		}

		$use_mb = function_exists( 'mb_convert_encoding' );
		$data   = (string) urldecode( $data );

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			$data     = 'UTF-8' === $encoding ? $data : utf8_encode( $data );
		}

		return $this->escape_data( $data );
	}

	/**
	 * Set the export headers.
	 *
	 * @since 1.3.0
	 * @param string $file_name File name.
	 */
	private function send_headers( $file_name = '' ) {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable(); // phpcs:ignore PHPCompatibility.PHP.NewFunctions.gc_enableFound
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 ); // @codingStandardsIgnoreLine
		}
		@ini_set( 'zlib.output_compression', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_buffering', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_handler', '' ); // @codingStandardsIgnoreLine
		ignore_user_abort( true );
		evf_set_time_limit( 0 );
		evf_nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $file_name );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	/**
	 * Write to the CSV file, ensuring escaping works across versions of PHP.
	 *
	 * PHP 5.5.4 uses '\' as the default escape character. This is not RFC-4180 compliant.
	 * \0 disables the escape character.
	 *
	 * @see https://bugs.php.net/bug.php?id=43225
	 * @see https://bugs.php.net/bug.php?id=50686
	 * @see https://github.com/woocommerce/woocommerce/issues/19514
	 * @since 1.3.0
	 * @param resource $buffer Resource we are writing to.
	 * @param array    $export_row Row to export.
	 */
	protected function fputcsv( $buffer, $export_row ) {
		if ( version_compare( PHP_VERSION, '5.5.4', '<' ) ) {
			ob_start();
			$temp = fopen( 'php://output', 'w' ); // @codingStandardsIgnoreLine
			fputcsv( $temp, $export_row, ",", '"' ); // @codingStandardsIgnoreLine
			fclose( $temp ); // @codingStandardsIgnoreLine
			$row = ob_get_clean();
			$row = str_replace( '\\"', '\\""', $row );
			fwrite( $buffer, $row ); // @codingStandardsIgnoreLine
		} else {
			fputcsv( $buffer, $export_row, ",", '"', "\0" ); // @codingStandardsIgnoreLine
		}
	}
}

new EVF_Admin_Entries();
