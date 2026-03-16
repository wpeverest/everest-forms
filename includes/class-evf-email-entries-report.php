<?php
/**
 * EVF Email Entries Report
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
	 * @var string
	 */
	private $frequency;

	/**
	 * @var string
	 */
	private $period_start;

	/**
	 * @var string
	 */
	private $period_end;

	/**
	 * @var string
	 */
	private $prev_period_start;

	/**
	 * @var string
	 */
	private $prev_period_end;

	/**
	 * @var string
	 */
	private $prev_prev_period_start;

	/**
	 * @var string
	 */
	private $prev_prev_period_end;

	/**
	 * @var array
	 */
	private $form_ids;

	/**
	 * @var bool
	 */
	private $is_test;

	/**
	 * Cached entries data to avoid repeated DB queries.
	 *
	 * @var array|null
	 */
	private $entries_cache = null;

	/**
	 * Constructor.
	 *
	 * @param string     $frequency  Daily|Weekly|Monthly.
	 * @param array|null $form_ids   Specific form IDs, or null to read from saved settings.
	 * @param bool       $is_test    Whether this is a test send.
	 */
	public function __construct( $frequency, $form_ids = null, $is_test = false ) {
		$this->frequency = $frequency;
		$this->is_test   = $is_test;

		if ( is_null( $form_ids ) ) {
			$saved          = get_option( 'everest_forms_reporting_form_lists', array() );
			$this->form_ids = is_array( $saved ) ? array_filter( array_map( 'absint', $saved ) ) : array();
		} else {
			$this->form_ids = is_array( $form_ids ) ? array_filter( array_map( 'absint', $form_ids ) ) : array();
		}

		$this->set_period_dates();
	}

	/**
	 * Set current and previous period date boundaries.
	 *
	 * @since 2.0.9
	 */
	private function set_period_dates() {
		switch ( $this->frequency ) {
			case 'Daily':
				$current_start_ts = strtotime( 'yesterday midnight' );
				$current_end_ts   = strtotime( 'today midnight' ) - 1;
				$prev_start_ts    = $current_start_ts - DAY_IN_SECONDS;
				$prev_end_ts      = $current_start_ts - 1;
				break;

			case 'Monthly':
				$current_start_ts = strtotime( 'first day of last month midnight' );
				$current_end_ts   = $current_start_ts + (int) gmdate( 't', $current_start_ts ) * DAY_IN_SECONDS - 1;
				$prev_start_ts    = strtotime( '-1 month', $current_start_ts );
				$prev_end_ts      = $current_start_ts - 1;
				break;

			case 'Weekly':
			default:
				$current_end_ts   = strtotime( 'today midnight' ) - 1;
				$current_start_ts = $current_end_ts - ( 7 * DAY_IN_SECONDS ) + 1;
				$prev_end_ts      = $current_start_ts - 1;
				$prev_start_ts    = $prev_end_ts - ( 7 * DAY_IN_SECONDS ) + 1;
				break;
		}

		$period_length = $prev_end_ts - $prev_start_ts;

		$this->period_start           = gmdate( 'Y-m-d H:i:s', $current_start_ts );
		$this->period_end             = gmdate( 'Y-m-d H:i:s', $current_end_ts );
		$this->prev_period_start      = gmdate( 'Y-m-d H:i:s', $prev_start_ts );
		$this->prev_period_end        = gmdate( 'Y-m-d H:i:s', $prev_end_ts );
		$this->prev_prev_period_start = gmdate( 'Y-m-d H:i:s', $prev_start_ts - $period_length - 1 );
		$this->prev_prev_period_end   = gmdate( 'Y-m-d H:i:s', $prev_start_ts - 1 );
	}

	/**
	 * Period label.
	 *
	 * @since 2.0.9
	 * @return string
	 */
	public function get_period_label() {
		$date_format = get_option( 'date_format', 'F j, Y' );

		switch ( $this->frequency ) {
			case 'Daily':
				return sprintf(
					__( ' %s', 'everest-forms' ),
					date_i18n( $date_format, strtotime( $this->period_start ) )
				);

			case 'Monthly':
				return sprintf(
					__( ' %s', 'everest-forms' ),
					date_i18n( 'F Y', strtotime( $this->period_start ) )
				);

			case 'Weekly':
			default:
				return sprintf(
					/* translators: 1: Start date, 2: End date */
					__( '%1$s – %2$s', 'everest-forms' ),
					date_i18n( $date_format, strtotime( $this->period_start ) ),
					date_i18n( $date_format, strtotime( $this->period_end ) )
				);
		}
	}

	/**
	 * Resolve the form IDs to include in the report.
	 *
	 * @since 2.0.9
	 * @return int[]
	 */
	private function resolve_form_ids() {
		$all_published = array_map(
			'intval',
			(array) get_posts(
				array(
					'post_type'      => 'everest_form',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			)
		);

		if ( empty( $this->form_ids ) ) {
			return $all_published;
		}

		return array_values(
			array_intersect( $this->form_ids, $all_published )
		);
	}

	/**
	 * Count entries for a form within a date range, excluding trash and draft.
	 *
	 * @since 2.0.9
	 *
	 * @param int    $form_id
	 * @param string $date_start
	 * @param string $date_end
	 * @return int
	 */
	private function count_entries( $form_id, $date_start, $date_end ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				 FROM {$wpdb->prefix}evf_entries
				 WHERE form_id      = %d
				   AND status      != %s
				   AND status      != %s
				   AND date_created >= %s
				   AND date_created <= %s",
				$form_id,
				'trash',
				'draft',
				$date_start,
				$date_end
			)
		);
	}

	/**
	 * Count unread entries for a form within a date range.
	 *
	 * @since 2.0.9
	 *
	 * @param int    $form_id
	 * @param string $date_start
	 * @param string $date_end
	 * @return int
	 */
	private function count_unread_entries( $form_id, $date_start, $date_end ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				 FROM {$wpdb->prefix}evf_entries
				 WHERE form_id      = %d
				   AND status      != %s
				   AND viewed       = 0
				   AND date_created >= %s
				   AND date_created <= %s",
				$form_id,
				'trash',
				$date_start,
				$date_end
			)
		);
	}

	/**
	 * Get entry counts per form. Results are cached within the request.
	 *
	 * @since 2.0.9
	 * @return array
	 */
	public function get_entries_data() {
		if ( ! is_null( $this->entries_cache ) ) {
			return $this->entries_cache;
		}

		$results = array();

		foreach ( $this->resolve_form_ids() as $form_id ) {
			if ( ! $form_id ) {
				continue;
			}

			$current       = $this->count_entries( $form_id, $this->period_start, $this->period_end );
			$previous      = $this->count_entries( $form_id, $this->prev_period_start, $this->prev_period_end );
			$previous_prev = $this->count_entries( $form_id, $this->prev_prev_period_start, $this->prev_prev_period_end );
			$unread        = $this->count_unread_entries( $form_id, $this->period_start, $this->period_end );

			$change = null;
			if ( $previous > 0 ) {
				$change = round( ( ( $current - $previous ) / $previous ) * 100, 1 );
			}

			$results[ $form_id ] = array(
				'form_id'       => $form_id,
				'form_name'     => get_the_title( $form_id ),
				'current'       => $current,
				'previous'      => $previous,
				'previous_prev' => $previous_prev,
				'unread'        => $unread,
				'change'        => $change,
				'view_url'      => admin_url( 'admin.php?page=evf-entries&form_id=' . $form_id ),
			);
		}

		uasort(
			$results,
			function ( $a, $b ) {
				return $b['current'] <=> $a['current'];
			}
		);

		$this->entries_cache = $results;

		return $this->entries_cache;
	}

	/**
	 * Build summary totals from entries data.
	 *
	 * @since 2.0.9
	 * @param array $entries_data
	 * @return array
	 */
	public function get_summary( $entries_data ) {
		$total_entries = array_sum( array_column( $entries_data, 'current' ) );
		$total_prev    = array_sum( array_column( $entries_data, 'previous' ) );

		return array(
			'total_entries'  => $total_entries,
			'total_prev'     => $total_prev,
			'overall_change' => $total_prev > 0 ? round( ( ( $total_entries - $total_prev ) / $total_prev ) * 100, 1 ) : null,
			'total_unread'   => array_sum( array_column( $entries_data, 'unread' ) ),
			'total_forms'    => count( $entries_data ),
			'active_forms'   => count(
				array_filter(
					$entries_data,
					function ( $f ) {
						return $f['current'] > 0;
					}
				)
			),
		);
	}

	/**
	 * Build highlight messages from entries data.
	 *
	 * @since 2.0.9
	 * @param array $entries_data
	 * @return array
	 */
	public function get_highlights( $entries_data ) {
		$highlights = array();

		if ( empty( $entries_data ) ) {
			return $highlights;
		}

		$top = null;
		foreach ( $entries_data as $form ) {
			if ( $form['current'] > 0 && ( null === $top || $form['current'] > $top['current'] ) ) {
				$top = $form;
			}
		}
		if ( $top ) {
			$highlights['top_form'] = sprintf(
				__( '<strong>%1$s</strong> received the most entries this period with <strong>%2$s</strong>.', 'everest-forms' ),
				esc_html( $top['form_name'] ),
				sprintf( _n( '%d submission', '%d submissions', $top['current'], 'everest-forms' ), $top['current'] )
			);
		}

		$improved = null;
		foreach ( $entries_data as $form ) {
			if ( ! is_null( $form['change'] ) && $form['change'] > 0 && ( null === $improved || $form['change'] > $improved['change'] ) ) {
				$improved = $form;
			}
		}
		if ( $improved ) {
			$highlights['most_improved'] = sprintf(
				__( '<strong>%1$s</strong> grew the most, up <strong>%2$s%%</strong> compared to last period.', 'everest-forms' ),
				esc_html( $improved['form_name'] ),
				$improved['change']
			);
		}

		$total_unread = array_sum( array_column( $entries_data, 'unread' ) );
		if ( $total_unread > 0 ) {
			$highlights['unread_alert'] = sprintf(
				__( 'You have <strong>%s</strong> across your forms that need attention.', 'everest-forms' ),
				sprintf( _n( '%d unread entry', '%d unread entries', $total_unread, 'everest-forms' ), $total_unread )
			);
		}

		$inactive_forms = array_filter(
			$entries_data,
			function ( $f ) {
				return 0 === $f['current'];
			}
		);
		$inactive_count = count( $inactive_forms );
		if ( $inactive_count > 0 ) {
			$highlights['inactive_alert'] = sprintf(
				__( '<strong>%1$s</strong> received no entries this period: %2$s.', 'everest-forms' ),
				sprintf( _n( '%d form', '%d forms', $inactive_count, 'everest-forms' ), $inactive_count ),
				esc_html(
					implode(
						', ',
						array_map(
							function ( $f ) {
								return $f['form_name'];
							},
							$inactive_forms
						)
					)
				)
			);
		}

		return $highlights;
	}

	/**
	 * Build footer data.
	 *
	 * @since 2.0.9
	 * @return array
	 */
	public function get_footer_data() {
		return array(
			'site_name'       => get_bloginfo( 'name' ),
			'site_url'        => home_url(),
			'settings_url'    => admin_url( 'admin.php?page=evf-settings&tab=advanced&section=entry_reports' ),
			'entries_url'     => admin_url( 'admin.php?page=evf-entries' ),
			'unsubscribe_url' => add_query_arg(
				array(
					'evf_disable_reports' => 1,
					'nonce'               => wp_create_nonce( 'evf_disable_reports' ),
				),
				home_url()
			),
			'generated_at'    => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'plugin_version'  => defined( 'EVF_VERSION' ) ? EVF_VERSION : '',
		);
	}

	/**
	 * Render the HTML email.
	 *
	 * @since 2.0.9
	 * @return string
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
	 * Render the plain-text fallback email.
	 *
	 * @since 2.0.9
	 * @return string
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
