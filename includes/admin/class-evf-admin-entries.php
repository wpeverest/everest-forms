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
		add_filter( 'heartbeat_received', array( $this, 'check_new_entries' ), 10, 3 );
	}

	/**
	 * Check if is entries page.
	 *
	 * @return bool
	 */
	private function is_entries_page() {
		return isset( $_GET['page'] ) && 'evf-entries' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Page output.
	 */
	public static function page_output() {
		if ( apply_filters( 'everest_forms_entries_list_actions', false ) ) {
			do_action( 'everest_forms_entries_list_actions_execute' );
		} elseif ( isset( $_GET['view-entry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
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

		// Get the entries IDs.
		$entry_ids = evf_get_entries_ids( $entries_table_list->form_id );

		$entries_table_list->process_bulk_action();
		$entries_table_list->prepare_items();
		?>
		<div id="everest-forms-entries-list" class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></h1>
			<hr class="wp-header-end">

			<?php settings_errors(); ?>
			<?php do_action( 'everest_forms_before_entry_list', $entries_table_list ); ?>

			<?php if ( 0 < count( $entry_ids ) ) : ?>
				<?php $entries_table_list->views(); ?>
				<form id="entries-list" method="get" data-form-id="<?php echo absint( $entries_table_list->form_id ); ?>" data-last-entry-id="<?php echo absint( end( $entry_ids ) ); ?>">
					<input type="hidden" name="page" value="evf-entries" />
					<?php if ( ! empty( $_REQUEST['form_id'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
						<input type="hidden" name="form_id" value="<?php echo absint( $_REQUEST['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
					<?php endif; ?>
					<?php if ( ! empty( $_REQUEST['status'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
						<input type="hidden" name="status" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
					<?php endif; ?>
					<?php
						$entries_table_list->search_box( esc_html__( 'Search Entries', 'everest-forms' ), 'everest-forms' );
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
								echo wp_kses(
									$output,
									evf_get_allowed_html_tags( 'form_dropdown' )
								);
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
			if ( isset( $_GET['trash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->trash_entry();
			}

			// Untrash entry.
			if ( isset( $_GET['untrash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->untrash_entry();
			}

			// Delete entry.
			if ( isset( $_GET['delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->delete_entry();
			}

			// Export Entry.
			if ( isset( $_REQUEST['export_entry_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->export_entry();
			}

			// Empty Trash.
			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
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

		if ( isset( $_GET['trash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$entry_id = absint( $_GET['trash'] ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( $entry_id ) {
				self::update_status( $entry_id, 'trash' );
			}
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'form_id' => $form_id,
						'trashed' => 1,
					),
					admin_url( 'admin.php?page=evf-entries' )
				)
			)
		);
		exit();
	}

	/**
	 * Trash entry.
	 */
	private function untrash_entry() {
		check_admin_referer( 'untrash-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['untrash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$entry_id = absint( $_GET['untrash'] ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( $entry_id ) {
				self::update_status( $entry_id, 'publish' );
			}
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'form_id'   => $form_id,
						'untrashed' => 1,
					),
					admin_url( 'admin.php?page=evf-entries' )
				)
			)
		);
		exit();
	}

	/**
	 * Delete entry.
	 */
	private function delete_entry() {
		check_admin_referer( 'delete-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$entry_id = absint( $_GET['delete'] ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( $entry_id ) {
				self::remove_entry( $entry_id );
			}
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'form_id' => $form_id,
						'deleted' => 1,
					),
					admin_url( 'admin.php?page=evf-entries' )
				)
			)
		);
		exit();
	}

	/**
	 * Empty Trash.
	 */
	public function empty_trash() {
		global $wpdb;

		check_admin_referer( 'bulk-entries' );

		if ( isset( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_id = absint( $_GET['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification

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
	 * Do the entries export.
	 *
	 * @since 1.3.0
	 */
	public function export_entry() {
		check_admin_referer( 'bulk-entries' );

		if ( isset( $_REQUEST['form_id'] ) && current_user_can( 'export' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			include_once EVF_ABSPATH . 'includes/export/class-evf-entry-spreadsheet-exporter.php';
			$form_id   = absint( $_REQUEST['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$form_name = strtolower( get_the_title( $form_id ) );
			$format    = isset( $_REQUEST['format_id'] ) ? $_REQUEST['format_id'] : ''; // phpcs:ignore

			if ( $form_name ) {

				switch ( $format ) {
					case 'csv':
						$exporter = new EVF_Entry_Spreadsheet_Exporter( $form_id );
						$exporter->set_filename( evf_get_export_entry_file_name( $form_name ), '.csv' );
						$exporter->export( 'text/csv' );
						break;
					case 'json':
						$this->export_as_json( $form_id, $form_name );
						break;
					case 'ods':
						$exporter = new EVF_Entry_Spreadsheet_Exporter( $form_id );
						$exporter->set_filename( evf_get_export_entry_file_name( $form_name ), '.ods' );
						$exporter->export( 'application/vnd.oasis.opendocument.spreadsheet' );
						break;
					case 'xlsx':
						$exporter = new EVF_Entry_Spreadsheet_Exporter( $form_id );
						$exporter->set_filename( evf_get_export_entry_file_name( $form_name ), '.xlsx' );
						$exporter->export( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
						break;
					default:
						new Exception( 'Invalid Requested Format ' . $format );
						break;
				}
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

		do_action( 'everest_forms_before_delete_entries', $entry_id );

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

		if ( in_array( $status, array( 'star', 'unstar' ), true ) ) {
			$update = $wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array(
					'starred' => 'star' === $status ? 1 : 0,
				),
				array( 'entry_id' => $entry_id ),
				array( '%d' ),
				array( '%d' )
			);
		} elseif ( in_array( $status, array( 'read', 'unread' ), true ) ) {
			$update = $wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array(
					'viewed' => 'read' === $status ? 1 : 0,
				),
				array( 'entry_id' => $entry_id ),
				array( '%d' ),
				array( '%d' )
			);
		} else {
			$entry = evf_get_entry( $entry_id );

			// Preseve entry status.
			if ( 'trash' === $status ) {
				$wpdb->insert(
					$wpdb->prefix . 'evf_entrymeta',
					array(
						'entry_id'   => $entry_id,
						'meta_key'   => '_evf_trash_entry_status',
						'meta_value' => sanitize_text_field( $entry->status ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					)
				);
			} elseif ( 'publish' === $status ) {
				$status = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}evf_entrymeta WHERE entry_id = %d AND meta_key = '_evf_trash_entry_status'", $entry_id ) );
				$wpdb->delete(
					$wpdb->prefix . 'evf_entrymeta',
					array(
						'entry_id' => $entry_id,
						'meta_key' => '_evf_trash_entry_status',
					)
				);
			}

			$update = $wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array( 'status' => $status ),
				array( 'entry_id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return $update;
	}

	/**
	 * Check new entries with heartbeat API.
	 *
	 * @since 1.5.0
	 *
	 * @param  array  $response  The Heartbeat response.
	 * @param  array  $data      The $_POST data sent.
	 * @param  string $screen_id The screen id.
	 * @return array The Heartbeat response.
	 */
	public function check_new_entries( $response, $data, $screen_id ) {
		if ( 'everest-forms_page_evf-entries' === $screen_id ) {
			$form_id       = ! empty( $data['evf_new_entries_form_id'] ) ? absint( $data['evf_new_entries_form_id'] ) : 0;
			$last_entry_id = ! empty( $data['evf_new_entries_last_entry_id'] ) ? absint( $data['evf_new_entries_last_entry_id'] ) : 0;

			// Count new entries.
			$entries_count = evf_get_count_entries_by_last_entry( $form_id, $last_entry_id );

			if ( ! empty( $entries_count ) ) {
				/* translators: %d - New form entries count. */
				$response['evf_new_entries_notification'] = esc_html( sprintf( _n( '%d new entry since you last checked.', '%d new entries since you last checked.', $entries_count, 'everest-forms' ), $entries_count ) );
			}
		}

		return $response;
	}

	/**
	 * Export entries to JSON file.
	 *
	 * @since 1.8.6
	 *
	 * @param  int    $form_id Form ID.
	 * @param  string $form_name Form Name.
	 */
	public function export_as_json( $form_id, $form_name ) {
		$entries     = evf_get_entries_ids( $form_id );
		$export_data = array();

		if ( count( $entries ) ) {
			foreach ( $entries as $entry_id ) {
				$export_entry = array();
				$entry        = evf_get_entry( $entry_id );
				$fields       = json_decode( $entry->fields, true );

				foreach ( $fields as $field ) {
					if ( ! in_array( $field['type'], array( 'html', 'title', 'captcha' ), true ) ) {
						if ( isset( $field['name'] ) ) {
							$export_entry[ $field['name'] ] = $field['value'];
						}
					}
				}

				// Status.
				$export_entry['Status'] = $this->get_column_value_status( $entry );

				// Date Created.
				$export_entry['Date Created'] = $this->get_column_value_date_created( $entry );

				// Date Created GMT.
				$export_entry['Date Created GMT'] = $this->get_column_value_date_created_gmt( $entry );

				// If user details are disabled globally discard the IP and UA.
				if ( 'yes' !== get_option( 'everest_forms_disable_user_details' ) ) {
					$export_entry['User Device']     = sanitize_text_field( $entry->user_device );
					$export_entry['User IP Address'] = sanitize_text_field( $entry->user_ip_address );
				}

				$export_data [] = $export_entry;
			}
		}

		$file_name = evf_get_export_entry_file_name( $form_name );

		if ( false !== $file_name ) {

			header( 'Content-disposition: attachment; filename=' . $file_name . '.json' );
			header( 'Content-type: application/json' );
			echo wp_json_encode( $export_data );
		}
		exit();
	}

	/**
	 * Get entry status value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	private function get_column_value_status( $entry ) {
		$statuses = evf_get_entry_statuses();

		if ( isset( $statuses[ $entry->status ] ) ) {
			return $statuses[ $entry->status ];
		}

		return $entry->status;
	}

	/**
	 * Get date created value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	private function get_column_value_date_created( $entry ) {
		$timestamp = false;

		if ( isset( $entry->date_created ) ) {
			$timestamp = strtotime( $entry->date_created );
		}

		/* translators: 1: entry date 2: entry time */
		return sprintf( esc_html__( '%1$s %2$s', 'everest-forms' ), date_i18n( evf_date_format(), $timestamp ), date_i18n( evf_time_format(), $timestamp ) );
	}

	/**
	 * Get GMT date created value.
	 *
	 * @param  object $entry Entry being exported.
	 * @return string
	 */
	private function get_column_value_date_created_gmt( $entry ) {
		$timestamp = false;

		if ( isset( $entry->date_created ) ) {
			$timestamp = strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 );
		}

		/* translators: 1: entry date 2: entry time */
		return sprintf( esc_html__( '%1$s %2$s', 'everest-forms' ), date_i18n( evf_date_format(), $timestamp ), date_i18n( evf_time_format(), $timestamp ) );
	}

}

new EVF_Admin_Entries();
