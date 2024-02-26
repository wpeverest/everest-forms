<?php
/**
 * Routine entries reporting functionality.
 *
 * @package EverestForms\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reporting Class.
 */
class EVF_Reporting {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'evf_schedule_entries_report_email' ) );
	}


	/**
	 * Schedule the entries reporting routine email.
	 *
	 * @since 2.0.7
	 */
	public function evf_schedule_entries_report_email() {

		// Clearing the existing statistics routine email this needs to be done in case the scheduling is changed)
		$evf_report_cron = new EVF_Report_Cron();
		$evf_report_cron->evf_schedule_clear_all();

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
			$evf_report_cron->evf_schedule_add( $evf_recurrence, $evf_report_next_run );
		}
	}

}
