<?php
/**
 * Entries Summary report
 *
 * Sends the entries summary report the routine basis.
 *
 * @package EverestForms\Classes
 * @version 2.0.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EverestForms Entries Summary Class.
 */
class EVF_Report_Cron {
	/**
	 * EVF_Report_Cron Constructor
	 *
	 * @since 2.0.9
	 */
	public function __construct() {
	}

	/**
	 * Function for implementing cron job for reporting.
	 *
	 * @since 2.0.9
	 */


	/**
	 * Function to add email schedule task.
	 *
	 * @since 2.0.7
	 *
	 * @param  string $evf_recurrence The recurrence time frame for the events.
	 * @param  bool   $evf_report_next_run The time for running event.
	 * @return void.
	 */
	public function evf_schedule_add( $evf_recurrence, $evf_report_next_run = false ) {
		if ( false === $evf_report_next_run ) {
			$evf_report_next_run = time();
		}
		$evf_weekly_day = get_option( 'everest_forms_entries_reporting_day' );
		if ( ! wp_next_scheduled( 'everest_forms_stats_report_schedule' ) ) {
			switch ( $evf_recurrence ) {
				case 'daily':
					$next_run_time = strtotime( '+1 day', $evf_report_next_run );
					break;
				case 'weekly':
					// Schedule event weekly .
					switch ( $evf_weekly_day ) {
						case 'sunday':
							$next_run_time = strtotime( 'next Sunday', $evf_report_next_run );
							break;
						case 'monday':
							$next_run_time = strtotime( 'next Monday', $evf_report_next_run );
							break;
						case 'tuesday':
							$next_run_time = strtotime( 'next Tuesday', $evf_report_next_run );
							break;
						case 'wednesday':
							$next_run_time = strtotime( 'next Wednesday', $evf_report_next_run );
							break;
						case 'thursday':
							$next_run_time = strtotime( 'next Thursday', $evf_report_next_run );
							break;
						case 'friday':
							$next_run_time = strtotime( 'next Friday', $evf_report_next_run );
							break;
						case 'saturday':
							$next_run_time = strtotime( 'next Saturday', $evf_report_next_run );
							break;
						default:
							return;
					}
					break;
				case 'monthly':
					$next_run_time = strtotime( '+30 days', $evf_report_next_run );
					break;
				default:
					return;
			}
			wp_schedule_event( $next_run_time, $evf_recurrence, 'everest_forms_stats_report_schedule' );
		}

	}


	/**
	 * Clears all the scheduled task need to be done when plugin is deactivated and the setting is changed.
	 *
	 * @since 2.0.7
	 * @return void.
	 */
	public function evf_schedule_clear_all() {

		$evf_scheduled_events = _get_cron_array();

		// If there are no scheduled events, return.
		if ( empty( $evf_scheduled_events ) ) {
			return; }

		// Run through each scheduled event.
		foreach ( $evf_scheduled_events as $timestamp => $cron ) {

			// Check the cron foe everest forms report scheduling. Skips it if not of Everest forms.
			if ( ! isset( $cron['everest_forms_stats_report_schedule'] ) ) {
				continue;
			}

			// Delete this scheduled event.
			unset( $evf_scheduled_events[ $timestamp ]['everest_forms_stats_report_schedule'] );

			// If this time stamp is now empty, delete it in its entirety.
			if ( empty( $evf_scheduled_events[ $timestamp ] ) ) {

				unset( $evf_scheduled_events[ $timestamp ] );
			}
		}

		// Save the scheduled events back.
		_set_cron_array( $evf_scheduled_events );
	}

	/**
	 * Handles the schedule on deactivation.
	 *
	 * @since 2.0.7
	 */
	public function deactivate() {
		self::evf_schedule_clear_all();
	}

	/**
	 * Schedule form statistics report email
	 *
	 * @since 2.0.7
	 * @return void.
	 */
	public function evf_report_form_statistics_schedule() {

		// Clear from report schedule (we need to do this in case the scheduling is changed).
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
					$evf_recurrence                    = 'daily';
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

					$evf_recurrence = 'weekly';
					break;

				case 'Monthly':
					$evf_entries_report_summary_offset = 'first day of next month';
					$evf_recurrence                    = 'monthly';
					break;
			}

			// Get midnight time of offset.
			$evf_midnight_time_offset = gmdate( 'Y-m-d 00:00:00', strtotime( $evf_entries_report_summary_offset ) );

			// Get UTC time of offset.
			$evf_midnight_time_offset_utc = get_gmt_from_date( $evf_midnight_time_offset );

			// Get next run.
			$evf_report_next_run = strtotime( $evf_midnight_time_offset_utc . ' +6 hours' );

			// Add to report schedule.
			$this->evf_schedule_add( $evf_recurrence, $evf_report_next_run );
		}
	}

	/**
	 * Report stat data.
	 *
	 * @since 2.0.9
	 */
	public function evf_report_form_statistics_send() {
		$evf_headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$evf_stat_subject = get_option( 'everest_forms_entries_reporting_subject' );

		if ( '' === $evf_stat_subject ) {
			$evf_stat_subject = esc_html__( 'Everest Forms - Entries summary statistics', 'everest-forms' );
		}

		$evf_stat_email = get_option( 'everest_forms_entries_reporting_email' );
		if ( '{admin_email}' === $evf_stat_email ) {
			$evf_stat_email = str_replace( $evf_stat_email, '{admin_email}', get_bloginfo( 'admin_email' ) );
		}
		if ( '' === $evf_stat_email && empty( $evf_stat_email ) ) {
			$evf_stat_email = get_bloginfo( 'admin_email' );
		}

		// Sending the stat email.
		$evf_stat_message    = '';
		$evf_send_stat_email = new EVF_Emails();
		$test                = EVF_Reporting::evf_schedule_entries_report_email();
		$evf_send_stat_email->send( $evf_stat_email, $evf_stat_subject, $evf_stat_message, '', '' );
	}
}

	new EVF_Report_Cron();
