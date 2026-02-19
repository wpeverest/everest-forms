<?php
/**
 * EverestForms Admin Entries Class - All Forms Support
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
		add_action( 'everest_forms_after_delete_entries', array( $this, 'evf_delete_booked_slot' ), 10, 2 );
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
		global $entries_table_list, $wpdb;

		$form_id = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$current_status = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : 'publish'; // phpcs:ignore WordPress.Security.NonceVerification


		if ( $form_id > 0 ) {


    $has_publish = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT 1 FROM {$wpdb->prefix}evf_entries
             WHERE form_id = %d AND status = 'publish'
             LIMIT 1",
            $form_id
        )
    );

    if ( $has_publish ) {
        $current_status = 'publish';
    } else {

        $has_trash = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->prefix}evf_entries
                 WHERE form_id = %d AND status = 'trash'
                 LIMIT 1",
                $form_id
            )
        );

        $current_status = $has_trash ? 'trash' : '';
    }

} else {

    $has_publish = $wpdb->get_var(
        "SELECT 1 FROM {$wpdb->prefix}evf_entries
         WHERE status = 'publish'
         LIMIT 1"
    );

    if ( $has_publish ) {
        $current_status = 'publish';
    } else {

        $has_trash = $wpdb->get_var(
            "SELECT 1 FROM {$wpdb->prefix}evf_entries
             WHERE status = 'trash'
             LIMIT 1"
        );

        $current_status = $has_trash ? 'trash' : '';
    }
}

		$current_status = $current_status ? $current_status : 'publish';

		$entries_table_list->process_bulk_action();
		$entries_table_list->prepare_items();

		$use_react_header = apply_filters( 'everest_forms_use_react_header', true, 'entries' );
		if ( $use_react_header ) {
			?>
			<div id="evf-react-header-root" data-active-menu="entries"></div>
			<?php
		}
		?>

		<div id="everest-forms-entries-list" class="wrap">
			<?php settings_errors(); ?>
			<div class="evf-entries-tab-wrapper">
				<div class="evf-entries-tab-header">
					<div class="evf-entries-tab-header-title">
						<span class="evf-entries-tab-header-title__text">
							<?php esc_html_e( 'Entries', 'everest-forms' ); ?>
						</span>
					</div>
					<div class="evf-entries-form-selector">
						<label for="filter-by-form" class="screen-reader-text"><?php esc_html_e( 'Filter by form', 'everest-forms' ); ?></label>
						<select
							name="form_id"
							id="filter-by-form"
							class="evf-enhanced-normal-select evf-auto-filter"
							data-placeholder="<?php esc_attr_e( 'Search form...', 'everest-forms' ); ?>"
						>
							<option value="0" <?php selected( $form_id, 0 ); ?>><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></option>
							<?php
							$forms = evf_get_all_forms( true );
							foreach ( $forms as $id => $form ) :
								?>
								<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $form_id, $id ); ?>><?php echo esc_html( $form ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php
					$style     = '';
					$entry_ids = evf_get_entries_ids( $entries_table_list->form_id );

					if ( ( defined( 'EFP_VERSION' ) && absint( $form_id ) === 0 ) || 0 === count( $entry_ids ) ) {
						$style = 'style="display:none;"';
					}
					?>
					<!-- Tab Navigation -->
					<ul class="evf-tab-nav" role="tablist" <?php echo $style; ?>>
						<li role="presentation">
							<button
								class="evf-tab-nav__btn"
								data-tab="evf-panel-entries"
								role="tab"
								aria-controls="evf-panel-entries"
								aria-selected="false"
								type="button"
							>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<rect x="3" y="3" width="18" height="18" rx="2"/>
									<line x1="3"  y1="9"  x2="21" y2="9"/>
									<line x1="3"  y1="15" x2="21" y2="15"/>
									<line x1="9"  y1="3"  x2="9"  y2="21"/>
								</svg>
								<?php esc_html_e( 'Table', 'everest-forms' ); ?>
							</button>
						</li>
						<?php if ( defined( 'EFP_VERSION' ) && count( $entry_ids ) > 0 && 'trash' !== $current_status ) : ?>
						<li role="presentation">
							<button
								class="evf-tab-nav__btn"
								data-tab="evf-panel-analytics"
								role="tab"
								aria-controls="evf-panel-analytics"
								aria-selected="false"
								type="button"
							>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<line x1="18" y1="20" x2="18" y2="10"/>
									<line x1="12" y1="20" x2="12" y2="4"/>
									<line x1="6"  y1="20" x2="6"  y2="14"/>
								</svg>
								<?php esc_html_e( 'Report', 'everest-forms' ); ?>
							</button>
						</li>
						<?php endif; ?>
					</ul>

				</div><!-- /.evf-entries-tab-header -->

				<div
					id="evf-panel-analytics"
					class="evf-tab-panel"
					role="tabpanel"
					hidden
				>
					<?php do_action( 'everest_forms_before_entry_list', $entries_table_list ); ?>
				</div>


				<div
					id="evf-panel-entries"
					class="evf-tab-panel"
					role="tabpanel"
					hidden
				>
					<form
						id="entries-list"
						method="get"
						data-form-id="<?php echo absint( $entries_table_list->form_id ); ?>"
						data-last-entry-id="<?php echo ! empty( $entry_ids ) ? absint( end( $entry_ids ) ) : 0; ?>"
					>
						<input type="hidden" name="page" value="evf-entries" />

						<?php if ( ! empty( $_REQUEST['form_id'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
							<input type="hidden" name="form_id" value="<?php echo absint( $_REQUEST['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
						<?php endif; ?>

						<?php if ( ! empty( $_REQUEST['status'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
							<input type="hidden" name="status" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
						<?php endif; ?>

						<div class="everest-forms-base-list-table-heading">
							<div style="display: flex; align-items: center; gap: 16px; flex: 0 0 auto;">
								<span class="evf-forms-title">
									<?php // esc_html_e( 'Entries', 'everest-forms' ); ?>
								</span>
							</div>
						</div>

						<?php $entries_table_list->views(); ?>
						<?php $entries_table_list->display(); ?>

					</form>
				</div><!-- /#evf-panel-entries -->

			</div>
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

			// Export CSV.
			if ( isset( $_REQUEST['export_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->export_csv();
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
	 * Untrash entry.
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
				self::remove_entry( $entry_id, $form_id );
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
					if ( self::remove_entry( $entry_id, $form_id ) ) {
						++$count;
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
	public function export_csv() {
		check_admin_referer( 'bulk-entries' );

		if ( isset( $_REQUEST['form_id'] ) && current_user_can( 'export' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			include_once EVF_ABSPATH . 'includes/export/class-evf-entry-csv-exporter.php';
			$form_id   = absint( $_REQUEST['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$form_name = strtolower( get_the_title( $form_id ) );

			if ( $form_name ) {
				$exporter = new EVF_Entry_CSV_Exporter( $form_id );
				$exporter->set_filename( evf_get_entry_export_file_name( $form_name ) );
			}

			$exporter->export();
		}
	}

	/**
	 * Remove entry.
	 *
	 * @param  int $entry_id Entry ID.
	 * @param  int $form_id  Form ID.
	 * @return bool
	 */
	public static function remove_entry( $entry_id, $form_id = 0 ) {
		global $wpdb;

		do_action( 'everest_forms_before_delete_entries', $entry_id );

		$delete = $wpdb->delete( $wpdb->prefix . 'evf_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );

		if ( apply_filters( 'everest_forms_delete_entrymeta', true ) ) {
			$wpdb->delete( $wpdb->prefix . 'evf_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
		}

		do_action( 'everest_forms_after_delete_entries', $form_id, $entry_id );

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

		$is_bulk_action = isset( $_GET['bulk_action'] ) && 'Apply' == $_GET['bulk_action'] ? true : false; // phpcs:ignore WordPress.Security.NonceVerification
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
		} elseif ( 'approved' === $status ) {
			$update = $wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array(
					'status' => 'publish',
				),
				array( 'entry_id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);

			$entry      = evf_get_entry( $entry_id );
			$entry_meta = $entry->meta;
			$entry_date = $entry->date_created;
			$first_name = '';
			$last_name  = '';
			$email      = '';
			$site_name  = get_option( 'blogname', '' );
			$subject    = '';
			$message    = '';
			$name       = '';

			foreach ( $entry_meta as $key => $value ) {
				if ( preg_match( '/^first_name_/', $key ) ) {
					$first_name = $value;
				}

				if ( preg_match( '/^last_name_/', $key ) ) {
					$last_name = $value;
				}

				if ( preg_match( '/^email/', $key ) ) {
					$email = $value;
				}

				if ( '' === $name ) {
					if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
						$name = $first_name . ' ' . $last_name;
					} elseif ( ! empty( $first_name ) ) {
						$name = $first_name;
					} else {
						$name = $last_name;
					}
				}

				$subject = apply_filters( 'everest_forms_entry_submission_approval_subject', esc_html__( 'Form Entry Approved', 'everest-forms' ) );
				/* translators:%s: User name of form entry */
				$message = sprintf( __( 'Hey, %s', 'everest-forms' ), $name ) . '<br/>';
				/* translators:%s: Form Entry Date */
				$message .= '<br/>' . sprintf( __( 'We\'re pleased to inform you that your form entry submitted on %s has been successfully approved.', 'everest-forms' ), $entry_date ) . '<br/>';
				$message .= '<br/>' . __( 'Thank you for giving us your precious time.', 'everest-forms' ) . '<br/>';
				/* translators:%s: Site Name*/
				$message .= '<br/>' . sprintf( __( 'From %s', 'everest-forms' ), $site_name );
				$message  = apply_filters( 'everest_forms_entry_approval_message', $message, $name, $entry_date, $site_name );
			}

			if ( ! $is_bulk_action ) {
				$email_obj = new EVF_Emails();
				$email_obj->send( $email, $subject, $message );
			}
		} elseif ( 'denied' === $status ) {
			$update = $wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array(
					'status' => $status,
				),
				array( 'entry_id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);

			$entry      = evf_get_entry( $entry_id );
			$entry_meta = $entry->meta;
			$entry_date = $entry->date_created;
			$first_name = '';
			$last_name  = '';
			$email      = '';
			$site_name  = get_option( 'blogname', '' );
			$name       = '';

			foreach ( $entry_meta as $key => $value ) {
				if ( preg_match( '/^first_name/', $key ) ) {
					$first_name = $value;
				}

				if ( preg_match( '/^last_name/', $key ) ) {
					$last_name = $value;
				}

				if ( preg_match( '/^email/', $key ) ) {
					$email = $value;
				}

				if ( '' === $name ) {
					if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
						$name = $first_name . ' ' . $last_name;
					} elseif ( ! empty( $first_name ) ) {
						$name = $first_name;
					} else {
						$name = $last_name;
					}
				}

				$subject = apply_filters( 'everest_forms_entry_submission_denial_subject', esc_html__( 'Entry Submission Denied', 'everest-forms' ) );
				/* translators:%s: User name of form entry */
				$message = sprintf( __( 'Hey, %s', 'everest-forms' ), $name ) . '<br/>';
				/* translators:%s: Form Entry Date */
				$message .= '<br/>' . sprintf( __( 'We regret to inform you that your form entry submitted on %s has been denied.', 'everest-forms' ), $entry_date ) . '<br/>';
				$message .= '<br/>' . __( 'Thank you for giving us your precious time.', 'everest-forms' ) . '<br/>';
				/* translator: %s: Site Name */
				$message .= '<br/>' . sprintf( __( 'From %s', 'everest-forms' ), $site_name );
				$message  = apply_filters( 'everest_forms_entry_denial_message', $message, $name, $entry_date, $site_name );
			}

			if ( ! $is_bulk_action ) {
				$email_obj = new EVF_Emails();
				$email_obj->send( $email, $subject, $message );
			}
		} elseif ( 'unspam' === $status ) {
			$update = $wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array(
					'status' => 'publish',
				),
				array( 'entry_id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);
		} else {
			$entry = evf_get_entry( $entry_id );

			// Preserve entry status.
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
	 * Delete booked slot after deleting the entries.
	 *
	 * @param int $form_id  Form ID.
	 * @param int $entry_id Entry ID.
	 */
	public function evf_delete_booked_slot( $form_id, $entry_id ) {
		$form_data    = get_post( $form_id );
		$form_content = json_decode( $form_data->post_content, true );
		$form_fields  = $form_content['form_fields'];
		foreach ( $form_fields as $field_name => $field ) {
			if ( 'date-time' === $field['type'] && isset( $field['slot_booking_advanced'] ) && evf_string_to_bool( $field['slot_booking_advanced'] ) ) {
				$booked_slot = evf_maybe_unserialize( get_option( 'evf_booked_slot', '' ) );
				if ( ! empty( $booked_slot ) && array_key_exists( $form_id, $booked_slot ) ) {
					$form_booked_slot = $booked_slot[ $form_id ];
					if ( array_key_exists( $entry_id, $form_booked_slot ) ) {
						unset( $form_booked_slot[ $entry_id ] );
						$booked_slot[ $form_id ] = $form_booked_slot;

						$booked_slot = maybe_serialize( $booked_slot );
						update_option( 'evf_booked_slot', $booked_slot );
					}
				}
			}
		}
	}
}

new EVF_Admin_Entries();
