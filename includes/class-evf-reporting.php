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
		add_action( 'admin_init', array( $this, 'schedule_entries_report_email' ) );
	}

	/**
	 * Schedule the entries reporting routine email.
	 *
	 * @since 2.0.7
	 */
	public function schedule_entries_report_email() {
		// Clear from report schedule (we need to do this in case the scheduling is changed)
		$evf_report_cron = new EVF_Cron();
		// $evf_report_cron->schedule_events(); // here the code for clearing cron jobs must be included.

		// Check if the routine emailing for form entries is enabled or not.
		$everest_forms_enable_routine_entries_reporting = get_option( 'everest_forms_enable_entries_reporting' );
		$everest_forms_entries_reporting_frequency      = '';
		$everest_forms_entries_reporting_day            = '';
		$everest_forms_entries_reporting_email          = '';
		$everest_forms_entries_reporting_subject        = '';
		$everest_forms_email_send_to                    = '';
		$everest_forms_reporting_form_lists             = array();

	}

}
