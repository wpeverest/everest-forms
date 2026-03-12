<?php
/**
 * EVF Email Entries Report
 *
 * Handles building and rendering the entries report email.
 *
 * @package EverestForms\Classes
 * @since   2.0.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Email_Entries_Report Class.
 */
class EVF_Email_Entries_Report {

	/**
	 * Frequency of the report.
	 *
	 * @var string
	 */
	private $frequency;

	/**
	 * Period start date string.
	 *
	 * @var string
	 */
	private $period_start;

	/**
	 * Period end date string.
	 *
	 * @var string
	 */
	private $period_end;

	/**
	 * Form IDs to include in the report.
	 *
	 * @var array
	 */
	private $form_ids;

	/**
	 * Whether this is a test email.
	 *
	 * @var bool
	 */
	private $is_test;

	/**
	 * Constructor.
	 *
	 * @param string $frequency   Daily|Weekly|Monthly.
	 * @param array  $form_ids    Form IDs to include.
	 * @param bool   $is_test     Whether this is a test send.
	 */
	public function __construct( $frequency, $form_ids = array(), $is_test = false ) {
		$this->frequency = $frequency;
		$this->form_ids  = $form_ids;
		$this->is_test   = $is_test;
		$this->set_period_dates();
	}

	/**
	 * Calculate period start and end dates based on frequency.
	 *
	 * @since 2.0.9
	 */
	private function set_period_dates() {
		switch ( $this->frequency ) {
			case 'Daily':
				$this->period_start = gmdate( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );
				$this->period_end   = gmdate( 'Y-m-d 23:59:59', strtotime( '-1 day' ) );
				break;
			case 'Monthly':
				$this->period_start = gmdate( 'Y-m-01 00:00:00', strtotime( '-1 month' ) );
				$this->period_end   = gmdate( 'Y-m-t 23:59:59', strtotime( '-1 month' ) );
				break;
			case 'Weekly':
			default:
				$this->period_start = gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
				$this->period_end   = gmdate( 'Y-m-d 23:59:59', strtotime( '-1 day' ) );
				break;
		}
	}

	/**
	 * Get human-readable period label.
	 *
	 * @since 2.0.9
	 * @return string
	 */
	public function get_period_label() {
		$date_format = get_option( 'date_format', 'F j, Y' );

		switch ( $this->frequency ) {
			case 'Daily':
				return sprintf(
					/* translators: %s: date */
					__( 'Daily Report for %s', 'everest-forms' ),
					date_i18n( $date_format, strtotime( $this->period_start ) )
				);
			case 'Monthly':
				return sprintf(
					/* translators: %s: month and year */
					__( 'Monthly Report: %s', 'everest-forms' ),
					date_i18n( 'F Y', strtotime( $this->period_start ) )
				);
			case 'Weekly':
			default:
				return sprintf(
					/* translators: %1$s: start date, %2$s: end date */
					__( 'Weekly Report: %1$s – %2$s', 'everest-forms' ),
					date_i18n( $date_format, strtotime( $this->period_start ) ),
					date_i18n( $date_format, strtotime( $this->period_end ) )
				);
		}
	}

	/**
	 * Query entry counts for each form for current and previous period.
	 *
	 * @since 2.0.9
	 * @return array
	 */
	public function get_entries_data() {
		global $wpdb;

		$current_start_ts = strtotime( $this->period_start );
		$current_end_ts   = strtotime( $this->period_end );
		$period_length    = $current_end_ts - $current_start_ts;
		$prev_start       = gmdate( 'Y-m-d H:i:s', $current_start_ts - $period_length - 1 );
		$prev_end         = gmdate( 'Y-m-d H:i:s', $current_start_ts - 1 );

		$results = array();

		// If no specific forms selected, report on all published forms.
		$form_ids = $this->form_ids;
		if ( empty( $form_ids ) ) {
			return $results;
		}

		$table = $wpdb->prefix . 'evf_entries';

		foreach ( $form_ids as $form_id ) {
			$form_id = absint( $form_id );
			if ( ! $form_id ) {
				continue;
			}

			
			$current = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					 FROM {$table}
					 WHERE form_id      = %d
					 AND   status      != %s
					 AND   status      != %s
					 AND   date_created >= %s
					 AND   date_created <= %s",
					$form_id,
					'trash',
					'draft',
					$this->period_start,
					$this->period_end
				)
			);


			$previous = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					 FROM {$table}
					 WHERE form_id      = %d
					 AND   status      != %s
					 AND   status      != %s
					 AND   date_created >= %s
					 AND   date_created <= %s",
					$form_id,
					'trash',
					'draft',
					$prev_start,
					$prev_end
				)
			);

			$unread = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					 FROM {$table}
					 WHERE form_id      = %d
					 AND   status      != %s
					 AND   viewed       = 0
					 AND   date_created >= %s
					 AND   date_created <= %s",
					$form_id,
					'trash',
					$this->period_start,
					$this->period_end
				)
			);

			// Percentage change vs previous period.
			$change = null;
			if ( $previous > 0 ) {
				$change = round( ( ( $current - $previous ) / $previous ) * 100, 1 );
			}

			$results[ $form_id ] = array(
				'form_id'   => $form_id,
				'form_name' => get_the_title( $form_id ),
				'current'   => $current,
				'previous'  => $previous,
				'unread'    => $unread,
				'change'    => $change,
				'view_url'  => admin_url( 'admin.php?page=evf-entries&form_id=' . $form_id ),
			);
		}

		// Sort by current entry count descending.
		uasort(
			$results,
			function ( $a, $b ) {
				return $b['current'] <=> $a['current'];
			}
		);

		return $results;
	}

	/**
	 * Build summary bar totals from entries data.
	 *
	 * @since 2.0.9
	 * @param array $entries_data Result of get_entries_data().
	 * @return array
	 */
	public function get_summary( $entries_data ) {
		$total_entries = array_sum( array_column( $entries_data, 'current' ) );
		$total_prev    = array_sum( array_column( $entries_data, 'previous' ) );
		$total_unread  = array_sum( array_column( $entries_data, 'unread' ) );
		$total_forms   = count( $entries_data );
		$active_forms  = count(
			array_filter(
				$entries_data,
				function ( $f ) {
					return $f['current'] > 0;
				}
			)
		);

		$overall_change = null;
		if ( $total_prev > 0 ) {
			$overall_change = round( ( ( $total_entries - $total_prev ) / $total_prev ) * 100, 1 );
		}

		return array(
			'total_entries'  => $total_entries,
			'total_prev'     => $total_prev,
			'overall_change' => $overall_change,
			'total_unread'   => $total_unread,
			'total_forms'    => $total_forms,
			'active_forms'   => $active_forms,
		);
	}

	/**
	 * Build highlights from entries data.
	 *
	 * @since 2.0.9
	 * @param array $entries_data Result of get_entries_data().
	 * @return array
	 */
	public function get_highlights( $entries_data ) {
		$highlights = array();

		if ( empty( $entries_data ) ) {
			return $highlights;
		}

		// ── Top performing form ──────────────────────────────────────
		$top = null;
		foreach ( $entries_data as $form ) {
			if ( $form['current'] > 0 && ( null === $top || $form['current'] > $top['current'] ) ) {
				$top = $form;
			}
		}
		if ( $top ) {
			$highlights['top_form'] = sprintf(
				/* translators: %1$s: form name, %2$s: submission count label */
				__( '<strong>%1$s</strong> received the most entries this period with <strong>%2$s</strong>.', 'everest-forms' ),
				esc_html( $top['form_name'] ),
				sprintf(
					/* translators: %d: number of submissions */
					_n( '%d submission', '%d submissions', $top['current'], 'everest-forms' ),
					$top['current']
				)
			);
		}

		// ── Most improved form ───────────────────────────────────────
		$improved = null;
		foreach ( $entries_data as $form ) {
			if ( ! is_null( $form['change'] ) && $form['change'] > 0 ) {
				if ( null === $improved || $form['change'] > $improved['change'] ) {
					$improved = $form;
				}
			}
		}
		if ( $improved ) {
			$highlights['most_improved'] = sprintf(
				/* translators: %1$s: form name, %2$s: percentage change */
				__( '<strong>%1$s</strong> grew the most, up <strong>%2$s%%</strong> compared to last period.', 'everest-forms' ),
				esc_html( $improved['form_name'] ),
				$improved['change']
			);
		}

		// ── Unread entries alert ─────────────────────────────────────
		$total_unread = array_sum( array_column( $entries_data, 'unread' ) );
		if ( $total_unread > 0 ) {
			$highlights['unread_alert'] = sprintf(
				/* translators: %s: unread entry count label */
				__( 'You have <strong>%s</strong> across your forms that need attention.', 'everest-forms' ),
				sprintf(
					/* translators: %d: number of unread entries */
					_n( '%d unread entry', '%d unread entries', $total_unread, 'everest-forms' ),
					$total_unread
				)
			);
		}

		// ── Inactive forms alert ─────────────────────────────────────
		$inactive_forms = array_filter(
			$entries_data,
			function ( $f ) {
				return 0 === $f['current'];
			}
		);
		$inactive_count = count( $inactive_forms );
		if ( $inactive_count > 0 ) {
			$inactive_names               = implode(
				', ',
				array_map(
					function ( $f ) {
						return $f['form_name'];
					},
					$inactive_forms
				)
			);
			$highlights['inactive_alert'] = sprintf(
				/* translators: %1$s: form count label, %2$s: comma-separated form names */
				__( '<strong>%1$s</strong> received no entries this period: %2$s.', 'everest-forms' ),
				sprintf(
					/* translators: %d: number of inactive forms */
					_n( '%d form', '%d forms', $inactive_count, 'everest-forms' ),
					$inactive_count
				),
				esc_html( $inactive_names )
			);
		}

		return $highlights;
	}

	/**
	 * Build footer URLs.
	 *
	 * @since 2.0.9
	 * @return array
	 */
	public function get_footer_data() {
		$unsubscribe_nonce = wp_create_nonce( 'evf_disable_reports' );

		return array(
			'site_name'       => get_bloginfo( 'name' ),
			'site_url'        => home_url(),
			'settings_url'    => admin_url( 'admin.php?page=evf-settings&tab=advanced&section=entry_reports' ),
			'entries_url'     => admin_url( 'admin.php?page=evf-entries' ),
			'unsubscribe_url' => add_query_arg(
				array(
					'evf_disable_reports' => 1,
					'nonce'               => $unsubscribe_nonce,
				),
				home_url()
			),
			'generated_at'    => date_i18n(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
			),
			'plugin_version'  => defined( 'EVF_VERSION' ) ? EVF_VERSION : '',
		);
	}

	/**
	 * Render the full HTML email.
	 *
	 * @since 2.0.9
	 * @return string HTML email content.
	 */
	public function render_html() {
		$entries_data = $this->get_entries_data();
		$summary      = $this->get_summary( $entries_data );
		$highlights   = $this->get_highlights( $entries_data );
		$footer       = $this->get_footer_data();
		$period_label = $this->get_period_label();
		$is_test      = $this->is_test;

		ob_start();
		include EVF_ABSPATH . 'includes/templates/entries-report.php';
		return ob_get_clean();
	}

	/**
	 * Render plain text fallback email.
	 *
	 * @since 2.0.9
	 * @return string Plain text email content.
	 */
	public function render_plain_text() {
		$entries_data = $this->get_entries_data();
		$summary      = $this->get_summary( $entries_data );
		$highlights   = $this->get_highlights( $entries_data );
		$footer       = $this->get_footer_data();
		$period_label = $this->get_period_label();

		ob_start();
		include EVF_ABSPATH . 'includes/templates/entries-report-plain.php';
		return ob_get_clean();
	}
}
