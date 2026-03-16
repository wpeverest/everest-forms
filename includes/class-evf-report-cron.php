<?php
/**
 * Entries Summary Report Cron
 *
 * @package EverestForms\Classes
 * @since   2.0.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Report_Cron Class.
 */
class EVF_Report_Cron {

	/**
	 * Hook name for the scheduled event.
	 *
	 * @var string
	 */
	const HOOK = 'everest_forms_stats_report_schedule';

	/**
	 * Constructor.
	 *
	 * @since 2.0.9
	 */
	public function __construct() {}

	/**
	 * Schedule the report cron event.
	 *
	 * @since 2.0.9
	 * @return bool
	 */
	public function evf_schedule_add() {
		if ( wp_next_scheduled( self::HOOK ) ) {
			return false;
		}

		$frequency = get_option( 'everest_forms_entries_reporting_frequency', '' );
		$send_hour = (int) get_option( 'everest_forms_reporting_send_hour', 8 );

		switch ( $frequency ) {
			case 'Daily':
				$offset     = '+1 day';
				$recurrence = 'daily';
				break;

			case 'Weekly':
				$day        = get_option( 'everest_forms_entries_reporting_day', 'monday' );
				$offset     = 'next ' . $day;
				$recurrence = 'weekly';
				break;

			case 'Monthly':
				$offset     = 'first day of next month';
				$recurrence = 'monthly';
				break;

			default:
				evf_get_logger()->warning(
					sprintf(
						/* translators: %s: frequency value */
						__( 'EVF Report: unknown frequency "%s", cannot schedule.', 'everest-forms' ),
						$frequency
					),
					array( 'source' => 'evf-reporting' )
				);
				return false;
		}

		$midnight_local = gmdate( 'Y-m-d 00:00:00', strtotime( $offset ) );
		$midnight_utc   = get_gmt_from_date( $midnight_local );
		$next_run       = strtotime( $midnight_utc ) + ( $send_hour * HOUR_IN_SECONDS );

		wp_schedule_event( $next_run, $recurrence, self::HOOK );

		return true;
	}

	/**
	 * Clear all scheduled report events.
	 *
	 * @since 2.0.7
	 */
	public function evf_schedule_clear_all() {
		$timestamp = wp_next_scheduled( self::HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
			$timestamp = wp_next_scheduled( self::HOOK );
		}
	}

	/**
	 * Clear and reschedule. Called after settings are saved.
	 *
	 * @since 2.0.9
	 */
	public function evf_reschedule() {
		$this->evf_schedule_clear_all();

		if ( 'yes' === get_option( 'everest_forms_enable_entries_reporting', 'no' ) ) {
			$this->evf_schedule_add();
		}
	}

	/**
	 * Handle schedule cleanup on plugin deactivation.
	 *
	 * @since 2.0.7
	 */
	public function deactivate() {
		$this->evf_schedule_clear_all();
	}

	/**
	 * Build and send the report email.
	 *
	 * @since 2.0.9
	 * @param bool $is_test Whether this is a manual test send.
	 * @return bool
	 */
	public function evf_report_form_statistics_send( $is_test = false ) {
		$recipient = get_option( 'everest_forms_entries_reporting_email', '{admin_email}' );
		$recipient = sanitize_email( str_replace( '{admin_email}', get_bloginfo( 'admin_email' ), $recipient ) );

		if ( empty( $recipient ) ) {
			$recipient = sanitize_email( get_bloginfo( 'admin_email' ) );
		}

		$subject = get_option( 'everest_forms_entries_reporting_subject', __( 'Everest Forms - Entries summary statistics', 'everest-forms' ) );
		if ( empty( trim( $subject ) ) ) {
			$subject = __( 'Everest Forms - Entries summary statistics', 'everest-forms' );
		}

		$frequency = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );
		if ( ! in_array( $frequency, array( 'Daily', 'Weekly', 'Monthly' ), true ) ) {
			evf_get_logger()->warning(
				sprintf(
					/* translators: %s: frequency value */
					__( 'EVF Report: invalid frequency "%s" at send time.', 'everest-forms' ),
					$frequency
				),
				array( 'source' => 'evf-reporting' )
			);
			return false;
		}

		$email_builder = new EVF_Email_Entries_Report( $frequency, null, $is_test );
		$entries_data  = $email_builder->get_entries_data();
		$html_message  = $email_builder->render_html();

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . wp_specialchars_decode( get_bloginfo( 'name' ) ) . ' <' . sanitize_email( get_option( 'admin_email' ) ) . '>',
		);

		$sent = wp_mail( $recipient, $subject, $html_message, $headers );

		if ( $sent && ! $is_test ) {
			$this->log_report_sent(
				array(
					'frequency' => $frequency,
					'email'     => $recipient,
					'entries'   => $entries_data,
					'type'      => 'scheduled',
				)
			);
		}

		return $sent;
	}

	/**
	 * Log a report send to the history option. Keeps the last 30 records.
	 *
	 * @since 2.0.9
	 * @param array $data Keys: frequency, email, entries, type.
	 */
	public function log_report_sent( $data ) {
		$history = get_option( 'everest_forms_report_history', array() );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$entry_count = ( ! empty( $data['entries'] ) && is_array( $data['entries'] ) )
			? array_sum( array_column( $data['entries'], 'current' ) )
			: 0;

		$history[] = array(
			'sent_at'       => current_time( 'mysql' ),
			'frequency'     => isset( $data['frequency'] ) ? $data['frequency'] : '',
			'recipient'     => isset( $data['email'] ) ? $data['email'] : '',
			'form_count'    => ! empty( $data['entries'] ) ? count( $data['entries'] ) : 0,
			'total_entries' => $entry_count,
			'type'          => isset( $data['type'] ) ? $data['type'] : 'scheduled',
		);

		update_option( 'everest_forms_report_history', array_slice( $history, -30 ) );
	}

	/**
	 * Get the schedule health status for display in settings.
	 *
	 * @since 2.0.9
	 * @return array { status: string, message: string }
	 */
	public function get_schedule_status() {
		$enabled = get_option( 'everest_forms_enable_entries_reporting', 'no' );

		if ( 'yes' !== $enabled ) {
			return array(
				'status'  => 'disabled',
				'message' => __( 'Entry reporting is currently disabled.', 'everest-forms' ),
			);
		}

		$next = wp_next_scheduled( self::HOOK );

		if ( ! $next ) {
			return array(
				'status'  => 'not_scheduled',
				'message' => __( 'Reporting is enabled but no report is scheduled. Try saving settings again.', 'everest-forms' ),
			);
		}

		return array(
			'status'  => 'active',
			'message' => sprintf(
				/* translators: %s: next send date/time */
				__( 'Next report scheduled for %s.', 'everest-forms' ),
				get_date_from_gmt(
					gmdate( 'Y-m-d H:i:s', $next ),
					get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
				)
			),
		);
	}
}
