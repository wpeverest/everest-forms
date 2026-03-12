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
	 * Constructor.
	 *
	 * @since 2.0.9
	 */
	public function __construct() {
		add_action( 'everest_forms_settings_saved', array( $this, 'reschedule_on_settings_save' ) );
		add_action( EVF_Report_Cron::HOOK, array( $this, 'run_scheduled_send' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ), 99 );
	}

	/**
	 * Returns a shared EVF_Report_Cron instance for this request.
	 *
	 * Avoids constructing four separate instances across the methods
	 * and makes it straightforward to swap the implementation later.
	 *
	 * @since  2.0.9
	 * @return EVF_Report_Cron
	 */
	private function cron() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new EVF_Report_Cron();
		}
		return $instance;
	}

	/**
	 * Ensure a schedule exists when reporting is enabled.
	 *
	 * Runs on init at priority 99. Safe to call on every request because
	 * EVF_Report_Cron::evf_schedule_add() is a no-op when a schedule is
	 * already registered (guarded by wp_next_scheduled() internally).
	 *
	 * @since  2.0.9
	 * @return void
	 */
	public function maybe_schedule() {
		if ( 'yes' !== get_option( 'everest_forms_enable_entries_reporting', 'no' ) ) {
			return;
		}

		$this->cron()->evf_schedule_add();
	}

	/**
	 * Clear and reschedule after settings are saved.
	 *
	 * @since  2.0.9
	 * @return void
	 */
	public function reschedule_on_settings_save() {
		$this->cron()->evf_reschedule();
	}

	/**
	 * Execute the scheduled send.
	 *
	 * @since  2.0.9
	 * @return void
	 */
	public function run_scheduled_send() {
		$this->cron()->evf_report_form_statistics_send( false );
	}

}
