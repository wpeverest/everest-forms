<?php
/**
 * EVF Reporting
 *
 * Orchestrates hooks, unsubscribe handling, and delegates
 * all schedule/send work to EVF_Report_Cron.
 *
 * @package EverestForms\Classes
 * @since   2.0.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Reporting Class.
 */
class EVF_Reporting {

	/**
	 * @var EVF_Report_Cron
	 */
	private $cron;

	/**
	 * Constructor.
	 *
	 * @since 2.0.9
	 */
	public function __construct() {
		$this->cron = new EVF_Report_Cron();

		add_action( EVF_Report_Cron::HOOK, array( $this, 'run_scheduled_send' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ), 99 );
	}

	/**
	 * Ensure a schedule exists when reporting is enabled.
	 *
	 * @since 2.0.9
	 */
	public function maybe_schedule() {
		if ( 'yes' !== get_option( 'everest_forms_enable_entries_reporting', 'no' ) ) {
			return;
		}

		$this->cron->evf_schedule_add();
	}

	/**
	 * Clear and reschedule. Called explicitly from EVF_Settings_Advanced::save().
	 *
	 * @since 2.0.9
	 */
	public function reschedule() {
		$this->cron->evf_reschedule();
	}

	/**
	 * Execute the scheduled send.
	 *
	 * @since 2.0.9
	 */
	public function run_scheduled_send() {
		$this->cron->evf_report_form_statistics_send( false );
	}
}

new EVF_Reporting();
