<?php

class EVF_Report_Cron {

	public function __construct() {
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
		// $evf_report_frequency = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );

		// if ( $evf_report_frequency === 'Monthly' ) {

		// Clear schedule
		// $this->evf_schedule_clear_all();

		// Reschedule
		// $this->evf_report_form_statistics_schedule();
		// }
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
		if ( ! wp_next_scheduled( 'everest_forms_stats_report_schedule' ) ) {
			wp_schedule_event( $evf_report_next_run, $evf_recurrence, 'everest_forms_stats_report_schedule' );
		}
	}

	// Schedule - Clear all for report
	public function evf_schedule_clear_all() {

		$evf_scheduled_events = _get_cron_array();

		// If there are no scheduled events, return
		if ( empty( $evf_scheduled_events ) ) {
			return; }

		// Run through each scheduled event
		foreach ( $evf_scheduled_events as $timestamp => $cron ) {

			// Check the cron foe everest forms report scheduling. Skips it if not of Everest forms
			if ( ! isset( $cron['everest_forms_stats_report_schedule'] ) ) {
				continue;
			}

			// Delete this scheduled event
			unset( $evf_scheduled_events[ $timestamp ]['everest_forms_stats_report_schedule'] );

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

	// Formats the cell while sending the email in table format
	public function evf_stat_format_cell( $evf_stat_input ) {

		if ( $evf_stat_input === 0 ) {

			return '-';

		} else {

			return absint( $evf_stat_input );
		}
	}

			// Get UTC time from
	public function evf_get_utc_time_from( $evf_entries_report_summary_offset, $evf_format = 'Y-m-d H:i:s', $evf_display = false ) {

		// Get local time midnight today
		$evf_time_from_local = wp_date( 'Y-m-d 00:00:00' );
		$evf_format          = 'Y-m-d H:i:s';

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
		$evf_format        = 'Y-m-d H:i:s';

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
		$this->evf_schedule_clear_all( 'everest_forms_stats_report_schedule' );

		// Check if the routine emailing for form entries is enabled or not.
		$everest_forms_enable_routine_entries_reporting = get_option( 'everest_forms_enable_entries_reporting' );
		$everest_forms_entries_reporting_frequency      = '';
		$everest_forms_entries_reporting_day            = '';

		if ( isset( $everest_forms_enable_routine_entries_reporting ) && 'yes' === $everest_forms_enable_routine_entries_reporting ) {

			// Get the frequency of sending the summary of routine basis.
			$everest_forms_entries_reporting_frequency = get_option( 'everest_forms_entries_reporting_frequency' );

			switch ( $everest_forms_entries_reporting_frequency ) {

				case 'Daily':
					$evf_entries_report_summary_offset = '+1 day';
					$evf_recurrence                    = 'evf_daily';
					break;

				case 'Weekly':
					$everest_forms_entries_reporting_day = get_option( 'everest_forms_entries_reporting_day' );

					switch ( $everest_forms_entries_reporting_day ) {

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

					$evf_recurrence = 'evf_weekly';
					break;

				case 'Monthly':
					$evf_entries_report_summary_offset = 'first day of next month';
					$evf_recurrence                    = 'evf_monthly';
					break;
			}

			// Get midnight time of offset
			$evf_midnight_time_offset = date( 'Y-m-d 00:00:00', strtotime( $evf_entries_report_summary_offset ) );

			// Get UTC time of offset
			$evf_midnight_time_offset_utc = get_gmt_from_date( $evf_midnight_time_offset );

			// Get next run
			$evf_report_next_run = strtotime( $evf_midnight_time_offset_utc . ' +6 hours' );

			// Add to report schedule
			$this->evf_schedule_add( $evf_recurrence, $evf_report_next_run );
		}
	}
	// Get counts for form statistics report
	public function evf_report_form_statistics_get_data( $offset_from, $offset_to ) {

		global $wpdb;

		$evf_date_format         = 'Y-m-d H:i:s';
		$evf_stat_start          = self::evf_get_utc_time_from( $offset_from, $evf_date_format, true );
		$evf_stat_end            = self::evf_get_utc_time_to( $offset_to, $evf_date_format, true );
		$evf_stat_selected_forms = get_option( 'everest_forms_reporting_form_lists', array() );
		foreach ( $evf_stat_selected_forms as $evf_stat_selected_form ) {
			$sql = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
					wp_posts.post_title,
					wp_evf_entries.form_id,
					SUM(wp_evf_entries.viewed) AS Viewed,
					SUM(wp_evf_entries.starred) AS Starred
				FROM
					wp_posts
				INNER JOIN
					wp_evf_entries ON wp_posts.id = wp_evf_entries.form_id
				WHERE
					wp_evf_entries.date_created BETWEEN '$evf_stat_start' AND '$evf_stat_end'
				GROUP BY
				 wp_evf_entries.form_id"
				)
			);
			// $evf_report_display_forms[] = $sql;
			break;
		}
		// error_log(print_r($sql, true));
		// die();
		return $sql;
	}

	// Get report form statistics data - By time
	public function evf_report_form_statistics_get_data_process( $time_from_utc = false, $time_to_utc = false ) {

		global $wpdb;

		$where_sql   = '';
		$where_array = array();

		// Form ID
		if ( $this->form_id > 0 ) {
			$where_array[] = sprintf( 'form_id = %u', $this->form_id ); }

		// Time from
		if ( $time_from_utc !== false ) {
			$where_array[] = sprintf( 'date_added >= \'%s\'', date( 'Y-m-d H:i:s', $time_from_utc ) ); }

		// Time to
		if ( $time_to_utc !== false ) {
			$where_array[] = sprintf( 'date_added < \'%s\'', date( 'Y-m-d H:i:s', $time_to_utc ) ); }

		// Build WHERE SQL
		if ( count( $where_array ) > 0 ) {

			$where_sql = ' WHERE ' . implode( ' AND ', $where_array );
		}

		// Get form stat data
		$sql = sprintf(
			"SELECT date_added, count_view, count_save, count_submit FROM {$this->table_name}%s;",
			$where_sql
		);

		$form_stats = $wpdb->get_results( $sql );

		// Build form stat array
		$count_view_total   = 0;
		$count_save_total   = 0;
		$count_submit_total = 0;

		if ( ! is_null( $form_stats ) ) {

			foreach ( $form_stats as $form_stat ) {

				// Totals
				$count_view_total   += $form_stat->count_view;
				$count_save_total   += $form_stat->count_save;
				$count_submit_total += $form_stat->count_submit;
			}
		}

		return array(

			'count_view_total'   => $count_view_total,
			'count_save_total'   => $count_save_total,
			'count_submit_total' => $count_submit_total,
		);
	}


	// Report stat data
	public function evf_report_form_statistics_send() {

		// Get options
		$evf_report_frequency   = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );
		$evf_report_offset_from = '';
		$evf_report_email_to    = '';
		$evf_date_format        = '';
		$evf_report_offset_to   = '';

		// Set everest forms reporting frequency specific variables
		switch ( $evf_report_frequency ) {

			case 'Daily':
				$evf_report_email_title = __( 'Everest Forms Daily Entries Statistics Report', 'everest-froms' );
				$evf_report_offset_from = '-1 days';
				$evf_report_offset_to   = '-1 day';
				break;

			case 'Weekly':
				$evf_report_email_title = __( 'Everest Forms Weekly Entries Statistics Report', 'everest-forms' );
				$evf_report_offset_from = '-8 days';
				$evf_report_offset_to   = '-1 day';
				break;

			case 'Monthly':
				$evf_report_email_title = __( 'Everest Forms Monthly Entries Statistics Report', 'everest-forms' );
				$evf_report_offset_from = '-1 month -1 day';
				$evf_report_offset_to   = '-1 day';
				break;
		}

		// Build date range
		$date_format = get_option( 'date_format' );

		// Get Stat data
		$evf_report_form_statistics_data = self::evf_report_form_statistics_get_data( $evf_report_offset_from, $evf_report_offset_to );

		// $evf_form_stat_count = array_sum( array_map( 'count', $evf_report_form_statistics_data ) );
		$evf_form_stat_count = count( $evf_report_form_statistics_data );

		$evf_headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$evf_stat_subject = get_option( 'everest_forms_entries_reporting_subject' );

		if ( '' === $evf_stat_subject ) {
			$evf_stat_subject = esc_html__( 'Everest Forms - Entries summary statistics', 'everest-forms' );
		}

		$evf_stat_message = sprintf(
			'<div class="evf_stat_body"><p><strong>%s:</strong> <a href="%s" target="_blank">%s</a>',
			__( 'Routine Statistics Report for ', 'everest-forms' ),
			get_site_url(),
			esc_html( get_bloginfo( 'name' ) )
		);

		if ( $evf_form_stat_count < 0 ) {
			$evf_stat_message .= sprintf(
				'<p>%s</p>',
				esc_html__( 'No forms are selected for the stat report', 'everest-forms' )
			);
		} else {
			$evf_stat_message .= sprintf(
				'<p>%s<strong>%s</strong> to <strong>%s</strong></p>',
				__( 'Your scheduled report for form entries from ', 'everest-forms' ),
				self::evf_get_utc_time_from( $evf_report_offset_from, $date_format, true ),
				self::evf_get_utc_time_to( $evf_report_offset_to, $date_format, true )
			);

			$evf_stat_message .= '<table class="table-evf-report">';

			// Table head
			$evf_stat_message .= '<thead>';
			$evf_stat_message .= sprintf(
				'<tr><th>%1$s</th><th class="evf_center">%2$s</th><th class="evf_center">%3$s</th></tr>',
				esc_html__( 'Form', 'everest-forms' ),
				esc_html__( 'Views', 'everest-forms' ),
				esc_html__( 'Starred', 'everest-forms' ),
			);
			$evf_stat_message .= '</thead>';

			$evf_stat_form_name = '';
			$evf_viewed_total   = 0;
			$evf_starred_total  = 0;

			// Table body
			$evf_stat_message  .= '<tbody>';
			$evf_stat_form_name = wp_list_pluck( $evf_report_form_statistics_data, 'post_title' );
			$evf_viewed_total   = wp_list_pluck( $evf_report_form_statistics_data, 'Viewed' );
			$evf_starred_total  = wp_list_pluck( $evf_report_form_statistics_data, 'Starred' );

			error_log( count( $evf_viewed_total ) );

			$evf_stat_message .= sprintf(
				'<tr><td>%1$s</td><td class="right">%2$s</td><td class="right">%3$s</td></tr>',
				esc_html__( $evf_stat_form_name, 'everest-forms' ),
				self::evf_stat_format_cell( $evf_viewed_total ),
				self::evf_stat_format_cell( $evf_starred_total ),
			);
			$evf_stat_message .= '</tbody>';

			// EFV Stat Report Table footer
			$evf_stat_message .= '<tfoot>';
			$evf_stat_message .= sprintf(
				'<tr><th class="evf_report_right">%1$s</th><th class="evf_report_right">%2$s</th><th class="evf__report_right">%3$s</th></tr>',
				esc_html__( 'Totals Counts', 'everest-forms' ),
				self::evf_stat_format_cell( count( $evf_viewed_total ) ),
				self::evf_stat_format_cell( count( $evf_starred_total ) ),
			);
			$evf_stat_message .= '</tfoot>';

			$evf_stat_message .= '</table>';
			$evf_stat_message .= '</div>';
		}

		// Build email footer
		$evf_stat_message .= sprintf(
			'%s <a href="https://everestforms.net/">%s</a>',
			/* translators: %1$s = Everest Forms */
			sprintf(
				esc_html__( 'The stat report email was sent by %s.', 'everest-forms' ),
				get_bloginfo( 'name' )
			),
			__( 'Learn more', 'everest-forms' )
		);

		error_log( 'Message' );
		error_log( print_r( $evf_stat_message, true ) );

		$evf_stat_email = get_option( 'everest_forms_entries_reporting_email' );
		if ( '' === $evf_stat_email && empty( $evf_stat_email ) ) {
			$evf_stat_email = get_bloginfo( 'admin_email' );
		}

		wp_mail( $evf_stat_email, $evf_stat_subject, $evf_stat_message, $evf_headers );
	}
}

	new EVF_Report_Cron();
