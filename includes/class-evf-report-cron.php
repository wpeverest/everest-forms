<?php

class EVF_Report_Cron {

	public function __construct() {

		// Report cron processes
		add_action( 'everest_forms_stats_report_schedule', array( $this, 'evf_schedule_run' ), 10, 2 );
	}

	/**
	 * Function for implementing cron job for reporting.
	 *
	 * @since 2.0.7
	 *
	 * @return void.
	 */
	public function evf_schedule_run() {
				// Send
				self::evf_report_form_statistics_send();

				// If frequency is monthly, then we'll reschedue to ensure we hit monthly exactly (wp_cron doesn't support 1 month)
				$evf_report_frequency = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );

		if ( $evf_report_frequency === 'Monthly' ) {

			// Clear schedule
			$this->evf_schedule_clear_all();

			// Reschedule
			$this->evf_report_form_statistics_schedule();
		}

	}

	// Schedule - Add
	public function evf_schedule_add( $evf_recurrence, $evf_report_next_run = false ) {

		if ( $evf_report_next_run === false ) {
			$evf_report_next_run = time(); }

		// Only add if recurrence valid
		$evf_schedules = wp_get_schedules();
		if ( ! isset( $evf_schedules[ $evf_recurrence ] ) ) {
			return; }

		// Schedule event for data source
		wp_schedule_event( $evf_report_next_run, $evf_recurrence, 'everest_forms_stats_report_schedule' );
	}

	// Schedule - Clear all for report
	public function evf_schedule_clear_all() {

		$evf_scheduled_events = _get_cron_array();

		// If there are no scheduled events, return
		if ( empty( $evf_scheduled_events ) ) {
			return; }

		// Run through each scheduled event
		foreach ( $evf_scheduled_events as $timestamp => $cron ) {

			// If this is not a WS Form data source hook, skip it
			if ( ! isset( $cron['evf_report_schedule'] ) ) {
				continue; }

				// Check the contents of the scheduled event
			// foreach ( $cron['evf_report_schedule'] as $cron_element_id => $cron_element ) {

			// if ( ! isset( $cron_element['args'] ) ) {
			// continue 2; }
			// if ( ! isset( $cron_element['args']['evf_report_id'] ) ) {
			// continue 2; }
			// if ( $cron_element['args']['evf_report_id'] != $evf_report_id ) {
			// continue 2; }
			// }

			// Delete this scheduled event
			unset( $evf_scheduled_events[ $timestamp ]['evf_report_schedule'] );

			// If this time stamp is now empty, delete it in its entirety
			if ( empty( $evf_scheduled_events[ $timestamp ] ) ) {

				unset( $evf_scheduled_events[ $timestamp ] );
			}
		}

		// Save the scheduled events back
		_set_cron_array( $evf_scheduled_events );
	}

	// Deactivate
	public function deactivate() {
		self::evf_schedule_clear_all();
	}

			// Get UTC time from
	public function evf_get_utc_time_from( $evf_entries_report_summary_offset, $evf_format = 'Y-m-d H:i:s', $evf_display = false ) {

		// Get local time midnight today
		$evf_time_from_local = wp_date( 'Y-m-d 00:00:00' );

		// Get local time from
		$evf_time_from_offset = strtotime( $evf_entries_report_summary_offset, strtotime( $evf_time_from_local ) );

		if ( $evf_display ) {

			return date( $evf_format, $evf_time_from_offset );

		} else {

			return strtotime( get_gmt_from_date( date( $evf_format, $evf_time_from_offset ) ) );
		}
	}

	// Get GMT time to
	public function evf_get_utc_time_to( $evf_entries_report_summary_offset, $evf_format = 'Y-m-d H:i:s', $evf_display = false ) {

		// Get local time 23:59:59 today
		$evf_time_to_local = wp_date( 'Y-m-d 23:59:59' );

		// Get local time to
		$evf_time_to_offset = strtotime( $evf_entries_report_summary_offset, strtotime( $evf_time_to_local ) );

		if ( $evf_display ) {

			return date( $evf_format, $evf_time_to_offset );

		} else {

			return strtotime( get_gmt_from_date( date( $evf_format, $evf_time_to_offset ) ) );
		}
	}


	// Schedule form statistics report email
	public function evf_report_form_statistics_schedule() {

		// Clear from report schedule (we need to do this in case the scheduling is changed)
		$this->evf_schedule_clear_all( $evf_report_id_form_statistics );

		// Get enabled
		$evf_routine_report_enabled = get_option( 'everest_forms_enable_entries_reporting', '' );

		if ( $evf_routine_report_enabled ) {

			// Get frequency
			$evf_routine_report_frequency = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );

			switch ( $evf_routine_report_frequency ) {

				case 'daily':
					$evf_entries_report_summary_offset = '+1 day';

					$evf_entries_report_summary_recurrence = 'evf_daily';

					break;

				case 'weekly':
					$evf_routine_report_day_of_week = get_option( 'everest_forms_entries_reporting_day', 'monday' );

					switch ( $evf_routine_report_day_of_week ) {

						case 'tuesday':
							$evf_entries_report_summary_offset = 'next tuesday';
							break;
						case 'wednesday':
							$evf_entries_report_summary_offset = 'next wednesday';
							break;
						case 'thursday':
							$evf_entries_report_summary_offset = 'next thursday';
							break;
						case 'friday':
							$evf_entries_report_summary_offset = 'next friday';
							break;
						case 'saturday':
							$evf_entries_report_summary_offset = 'next saturday';
							break;
						case 'sunday':
							$evf_entries_report_summary_offset = 'next sunday';
							break;
						default:
							$evf_entries_report_summary_offset = 'next monday';
					}

					$evf_entries_report_summary_recurrence = 'evf_weekly';

					break;

				case 'monthly':
					$evf_entries_report_summary_offset = 'first day of next month';

					$evf_entries_report_summary_recurrence = 'wsf_monthly';

					break;
			}

			// Get midnight time of offset
			$evf_midnight_time_offset = date( 'Y-m-d 00:00:00', strtotime( $offset ) );

			// Get UTC time of offset
			$evf_midnight_time_offset_utc = get_gmt_from_date( $midnight_time_offset );

			// Get next run
			$evf_report_next_run = strtotime( $evf_midnight_time_offset_utc . ' +6 hours' );

			// Add to report schedule
			$this->evf_schedule_add( $evf_report_id_form_statistics, $evf_entries_report_summary_recurrence, $evf_report_next_run );
		}
	}

	// Get counts for form statistics report
	public function evf_report_form_statistics_get_data( $evf_report_offset_from, $evf_report_offset_to ) {

		global $wpdb;

		$evf_return_data = array(
			'forms' => array(),
		);

		$evf_report_form_lists = get_option( 'everest_forms_reporting_form_lists', '' );
		foreach ( $evf_report_form_lists as $evf_report_forms => $evf_report_id ) {
			$evf_report_id_count = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}evf_entries WHERE form_id = %d ", $evf_report_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			error_log( print_r( $evf_report_id_count, true ) );
		}

		// Process each form
		foreach ( $forms as $form ) {

			// Set for mID
			$this->form_id = $form['id'];

			// Get data
			$data = self::report_form_statistics_get_data_process(
				self::get_utc_time_from( $evf_report_offset_from ),
				self::get_utc_time_to( $evf_report_offset_to )
			);

			// Check for saves (used for formatting the table)
			if ( $data['count_save_total'] > 0 ) {
				$return_data['saves'] = true; }

			// Build return data
			$return_data['forms'][] = array(

				'id'                 => $form['id'],
				'label'              => $form['label'],
				'count_view_total'   => $data['count_view_total'],
				'count_save_total'   => $data['count_save_total'],
				'count_submit_total' => $data['count_submit_total'],
			);
		}

		return $return_data;
	}

	/**
	 * Function to retrieve the form entries according to the offset start and offset end duration.
	 *
	 * @param int $evf_time_from_utc Start time in UTC format.
	 * @param int $evf_time_to_utc End Time in UTC format.
	 */
	public function report_form_statistics_get_data_process( $evf_time_from_utc = false, $evf_time_to_utc = false ) {

		global $wpdb;

		$where_sql   = '';
		$where_array = array();

		// Form ID
		if ( $this->form_id > 0 ) {
			$where_array[] = sprintf( 'form_id = %u', $this->form_id ); }

		// Time from
		if ( $time_from_utc !== false ) {
			$where_array[] = sprintf( 'date_created >= \'%s\'', date( 'Y-m-d H:i:s', $evf_time_from_utc ) ); }

		// Time to
		if ( $time_to_utc !== false ) {
			$where_array[] = sprintf( 'date_created < \'%s\'', date( 'Y-m-d H:i:s', $time_to_utc ) ); }

		// Build WHERE SQL
		if ( count( $where_array ) > 0 ) {

			$where_sql = ' WHERE ' . implode( ' AND ', $where_array );
		}

		// Get form stat data
		$sql = sprintf(
			"SELECT * FROM {$this->table_name}%s;",
			$where_sql
		);

		$evf_form_stats = $wpdb->get_results( $sql );

		// Build form stat array
		$count_submit_total = 0;

		if ( ! is_null( $evf_form_stats ) ) {

			foreach ( $evf_form_stats as $evf_form_stat ) {

				// Totals
				$evf_count_submit_total += $evf_form_stat->count_submit;
			}
		}

		return array(
			'evf_count_submit_total' => $evf_count_submit_total,
		);
	}


	// Report stat data
	public function evf_report_form_statistics_send() {

		// Get options
		$evf_report_frequency     = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );
		$evf_report_email_to      = get_option( 'everest_forms_email_send_to', get_bloginfo( 'admin_email' ) );
		$evf_report_email_subject = get_option( 'everest_forms_entries_reporting_subject', esc_html__( 'Everest Forms - Entries summary statistics', 'everest-forms' ) );

		// Set everest forms reporting frequency specific variables
		switch ( $evf_report_frequency ) {

			case 'daily':
				$evf_report_email_title = __( 'Everest Forms Daily Entries Statistics Report', 'everest-froms' );
				$evf_report_offset_from = '-1 days';
				$evf_report_offset_to   = '-1 day';
				break;

			case 'weekly':
				$evf_report_email_title = __( 'Everest Forms Weekly Entries Statistics Report', 'everest-forms' );
				$evf_report_offset_from = '-8 days';
				$evf_report_offset_to   = '-1 day';
				break;

			case 'monthly':
				$evf_report_email_title = __( 'Everest Forms Monthly Entries Statistics Report', 'everest-forms' );
				$evf_report_offset_from = '-1 month -1 day';
				$evf_report_offset_to   = '-1 day';
				break;
		}

		// Build email message
		$evf_report_email_message = sprintf(
			'<p><strong>%s:</strong> <a href="%s" target="_blank">%s</a>',
			__( 'Website', 'ws-form' ),
			get_site_url(),
			esc_html( get_bloginfo( 'name' ) )
		);

		// Build date range
		$date_format = get_option( 'date_format' );

		$evf_report_email_message .= sprintf(
			'<p><strong>%s:</strong> %s to %s</a>',
			__( 'Date Range', 'ws-form' ),
			self::get_utc_time_from( $evf_report_offset_from, $evf_date_format, true ),
			self::get_utc_time_to( $evf_report_offset_to, $evf_date_format, true )
		);

		// Get data
		$evf_report_form_statistics_data = self::evf_report_form_statistics_get_data( $evf_report_offset_from, $evf_report_offset_to );

		// Check if any saves exist, we'll simplify the table if no saves exists
		$email_message .= '<table class="table-report">';

		// Table heading
		$email_message .= '<thead>';
		$email_message .= sprintf(
			$form_saves ? '<tr><th>%1$s</th><th class="center">%2$s</th><th class="center">%3$s</th><th class="center">%4$s</th><th class="center">%5$s</th></tr>' : '<tr><th>%1$s</th><th class="center">%2$s</th><th class="center">%4$s</th><th class="center">%5$s</th></tr>',
			__( 'Form', 'ws-form' ),
			__( 'Submits', 'ws-form' ),
		);
		$email_message .= '</thead>';

		// Totals
		$count_submit_total = 0;

		// Table body
		$email_message .= '<tbody>';
		foreach ( $evf_report_form_statistics_data['forms'] as $evf_form_data ) {

			$count_view       = $form_data['count_view_total'];
			$count_save       = $form_data['count_save_total'];
			$evf_count_submit = $evf_form_data['count_submit_total'];

			$count_view_total   += $count_view;
			$count_save_total   += $count_save;
			$count_submit_total += $count_submit;

			// Calculate conversion rate
			$conversion_rate = ( $count_view > 0 ) ? ( ( $count_submit / $count_view ) * 100 ) : 0;
			if ( $conversion_rate > 100 ) {
				$conversion_rate = 100; }

			$email_message .= sprintf(
				$form_saves ? '<tr><td>%1$s</td><td class="right">%2$s</td><td class="right">%3$s</td><td class="right">%4$s</td><td class="right">%5$s</td></tr>' : '<tr><td>%1$s</td><td class="right">%2$s</td><td class="right">%4$s</td><td class="right">%5$s</td></tr>',
				esc_html( $form_data['label'] ),
				self::report_format_cell( $count_submit )
			);
		}
		$email_message .= '</tbody>';

		// Table footer
		$email_message .= '<tfoot>';
		$email_message .= sprintf(
			$form_saves ? '<tr><th class="right">%1$s</th><th class="right">%2$s</th><th class="right">%3$s</th><th class="right">%4$s</th><th class="right">%5$s</th></tr>' : '<tr><th class="right">%1$s</th><th class="right">%2$s</th><th class="right">%4$s</th><th class="right">%5$s</th></tr>',
			__( 'Totals', 'ws-form' ),
			self::report_format_cell( $count_view_total ),
			self::report_format_cell( $count_save_total ),
			self::report_format_cell( $count_submit_total ),
			sprintf( '%.1f%%', $conversion_rate )
		);
		$email_message .= '</tfoot>';

		$email_message .= '</table>';

		// Build email footer
		// $email_footer            = sprintf(
		// '%s <a href="https://wsform.com/knowledgebase/report-form-statistics/">%s</a>',
		// * translators: % 1$s = WS Form * /
		// sprintf(
		// __( 'This email was sent from %s.', 'ws-form' ),
		// WS_FORM_NAME_PRESENTABLE
		// ),
		// __( 'Learn more', 'ws-form' )
		// );

		// Get email template
		// $email_template = file_get_contents( sprintf( '%sincludes/templates/email/html/report.html', WS_FORM_PLUGIN_DIR_PATH ) );

		// Parse email template
		// $mask_values = array(

		// 'email_subject' => htmlentities( $email_subject ),
		// 'email_title'   => $email_title,
		// 'email_message' => $email_message,
		// 'email_footer'  => $email_footer,
		// );

		// Build headers
		// $headers = array(

		// 'Content-Type: text/html',
		// );

		// // Send email
		// wp_mail( $email_to_array, $email_subject, $message, $headers
	}
}

	new EVF_Report_Cron();
