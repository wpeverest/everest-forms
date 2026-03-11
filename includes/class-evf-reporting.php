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
		// After settings are saved: clear old schedule and set new one.
		add_action( 'everest_forms_settings_saved', array( $this, 'reschedule_on_settings_save' ) );

		// When the cron fires: run the send.
		add_action( EVF_Report_Cron::HOOK, array( $this, 'run_scheduled_send' ) );

		// On every init: ensure schedule exists if enabled (covers fresh installs
		// and cases where the cron was cleared externally).
		// EVF_Report_Cron::evf_schedule_add() guards internally with wp_next_scheduled()
		// so this is a no-op when a schedule already exists.
		add_action( 'init', array( $this, 'maybe_schedule' ), 99 );

		// Unsubscribe handler — must run before any output.
		add_action( 'init', array( $this, 'handle_unsubscribe' ), 10 );
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

	/**
	 * Handle unsubscribe requests from the email footer link.
	 *
	 * Expects GET params: evf_disable_reports=1 and nonce=<token>.
	 * On success: disables reporting, clears cron, redirects to homepage
	 * with a query param that a notice handler can use to confirm to the user.
	 *
	 * @since  2.0.9
	 * @return void
	 */
	public function handle_unsubscribe() {
		if ( empty( $_GET['evf_disable_reports'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'evf_disable_reports' ) ) {
			wp_die(
				esc_html__( 'This unsubscribe link has expired. Please use the link from a recent report email.', 'everest-forms' ),
				esc_html__( 'Link Expired', 'everest-forms' ),
				array( 'response' => 400 )
			);
		}

		update_option( 'everest_forms_enable_entries_reporting', 'no' );

		$this->cron()->evf_schedule_clear_all();

		// Redirect to homepage with a param so a front-end notice can confirm to
		// the visitor that they have been unsubscribed. The param is intentionally
		// non-sensitive — no user data is exposed.
		wp_safe_redirect(
			add_query_arg( 'evf_reports', 'disabled', home_url( '/' ) )
		);
		exit;
	}
}
