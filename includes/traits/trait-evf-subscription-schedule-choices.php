<?php
/**
 * Shared trial/expiry parsing and proration helpers for gateway subscription schedules.
 *
 * Used by Stripe, PayPal Standard, Authorize.Net, Mollie, and Square schedule builders.
 *
 * @package EverestForms\Traits
 * @since   3.4.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Subscription plan choice settings shared across payment gateways.
 */
trait EVF_Subscription_Schedule_Choices {

	/**
	 * Parse trial/expiry settings from a plan choice row.
	 *
	 * @since 3.4.8
	 *
	 * @param array $choice Plan choice from form_fields.
	 * @return array
	 */
	public static function parse_choice_settings( $choice ) {
		$choice = is_array( $choice ) ? $choice : array();

		$trial_enabled = isset( $choice['trail_period_enable'] ) && evf_string_to_bool( $choice['trail_period_enable'] );

		$expiry_enabled = isset( $choice['subscription_expiry_enable'] ) && evf_string_to_bool( $choice['subscription_expiry_enable'] );
		if ( ! $expiry_enabled && ! empty( $choice['subscription_expiry_date'] ) ) {
			$expiry_enabled = true;
		}

		$settings = array(
			'plan_amount'    => isset( $choice['value'] ) ? (float) evf_sanitize_amount( $choice['value'] ) : 0,
			'period'         => isset( $choice['recurring_period'] ) ? sanitize_key( (string) $choice['recurring_period'] ) : 'month',
			'interval_count' => isset( $choice['interval_count'] ) ? max( 1, absint( $choice['interval_count'] ) ) : 1,
			'trial_enabled'  => $trial_enabled,
			'expiry_enabled' => $expiry_enabled,
			'expiry_date'    => isset( $choice['subscription_expiry_date'] ) ? sanitize_text_field( $choice['subscription_expiry_date'] ) : '',
		);

		if ( $trial_enabled ) {
			$settings['trial_period']         = isset( $choice['trail_recurring_period'] ) ? sanitize_text_field( $choice['trail_recurring_period'] ) : 'week';
			$settings['trial_interval_count'] = isset( $choice['trail_interval_count'] ) ? max( 1, absint( $choice['trail_interval_count'] ) ) : 1;
		}

		return $settings;
	}

	/**
	 * Normalize EVF recurring period.
	 *
	 * @since 3.4.8
	 *
	 * @param string $period Raw period.
	 * @return string day|week|month|year
	 */
	public static function normalize_period( $period ) {
		$period  = sanitize_key( (string) $period );
		$aliases = array(
			'daily'   => 'day',
			'weekly'  => 'week',
			'monthly' => 'month',
			'yearly'  => 'year',
			'days'    => 'day',
			'weeks'   => 'week',
			'months'  => 'month',
			'years'   => 'year',
		);

		if ( isset( $aliases[ $period ] ) ) {
			return $aliases[ $period ];
		}

		if ( in_array( $period, array( 'day', 'week', 'month', 'year' ), true ) ) {
			return $period;
		}

		return 'month';
	}

	/**
	 * End of expiry date as unix timestamp.
	 *
	 * @since 3.4.8
	 *
	 * @param string $expiry_date Y-m-d.
	 * @return int|null
	 */
	public static function expiry_end_timestamp( $expiry_date ) {
		if ( empty( $expiry_date ) ) {
			return null;
		}

		$timestamp = strtotime( $expiry_date . ' 23:59:59' );

		return $timestamp ? $timestamp : null;
	}

	/**
	 * Billing period length in seconds.
	 *
	 * @since 3.4.8
	 *
	 * @param string $period         day|week|month|year.
	 * @param int    $interval_count Interval multiplier.
	 * @return int
	 */
	public static function billing_period_seconds( $period, $interval_count ) {
		$interval_count = max( 1, absint( $interval_count ) );
		$period         = self::normalize_period( $period );

		switch ( $period ) {
			case 'day':
				return DAY_IN_SECONDS * $interval_count;
			case 'week':
				return WEEK_IN_SECONDS * $interval_count;
			case 'year':
				return YEAR_IN_SECONDS * $interval_count;
			case 'month':
			default:
				return MONTH_IN_SECONDS * $interval_count;
		}
	}

	/**
	 * Trial end unix timestamp.
	 *
	 * @since 3.4.8
	 *
	 * @param array $settings Parsed settings.
	 * @return int|null
	 */
	public static function trial_end_timestamp( $settings ) {
		if ( empty( $settings['trial_enabled'] ) ) {
			return null;
		}

		$period = self::normalize_period( isset( $settings['trial_period'] ) ? $settings['trial_period'] : 'week' );
		$count  = isset( $settings['trial_interval_count'] ) ? max( 1, absint( $settings['trial_interval_count'] ) ) : 1;
		$ts     = strtotime( '+' . $count . ' ' . $period );

		if ( ! $ts || $ts <= time() + 60 ) {
			$ts = time() + DAY_IN_SECONDS;
		}

		return $ts;
	}

	/**
	 * Unix timestamp when paid billing begins (after trial when applicable).
	 *
	 * @since 3.4.8
	 *
	 * @param array $settings Parsed settings.
	 * @return int
	 */
	public static function billing_start_timestamp( $settings ) {
		if ( ! empty( $settings['trial_enabled'] ) ) {
			$trial_end = self::trial_end_timestamp( $settings );

			return $trial_end ? $trial_end : time();
		}

		return time();
	}

	/**
	 * Whether the paid billing window through expiry fits within one plan period.
	 *
	 * @since 3.4.8
	 *
	 * @param int    $billing_start_ts Billing start (after trial when applicable).
	 * @param int    $expiry_end_ts    Expiry end of day.
	 * @param string $period           day|week|month|year.
	 * @param int    $interval_count   Interval count.
	 * @return bool
	 */
	public static function is_short_billing_window( $billing_start_ts, $expiry_end_ts, $period, $interval_count ) {
		if ( $expiry_end_ts <= $billing_start_ts ) {
			return false;
		}

		$period_seconds = self::billing_period_seconds( $period, $interval_count );
		$window         = $expiry_end_ts - $billing_start_ts;

		return $window <= $period_seconds;
	}

	/**
	 * Prorated amount for billing window through expiry (after trial when applicable).
	 *
	 * @since 3.4.8
	 *
	 * @param float  $plan_amount      Plan amount per period.
	 * @param int    $billing_start_ts Billing start.
	 * @param int    $expiry_end_ts    Expiry end.
	 * @param string $period           day|week|month|year.
	 * @param int    $interval_count   Interval count.
	 * @return float|null
	 */
	public static function calculate_prorated_amount( $plan_amount, $billing_start_ts, $expiry_end_ts, $period, $interval_count ) {
		if ( $plan_amount <= 0 || $expiry_end_ts <= $billing_start_ts ) {
			return null;
		}

		$period_seconds = self::billing_period_seconds( $period, $interval_count );
		$window         = $expiry_end_ts - $billing_start_ts;

		return ( $window / $period_seconds ) * $plan_amount;
	}
}
