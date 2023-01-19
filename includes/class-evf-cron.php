<?php
/**
 * Everest Forms Cron
 *
 * @package EverestForms
 * @since   1.9.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Cron Class
 *
 * This class handles scheduled events
 */
class EVF_Cron {


	/**
	 * Init WordPress hook
	 *
	 * @see EVF_Cron::weekly_events()
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( 'init', array( $this, 'schedule_events' ) );
	}

	/**
	 * Registers new cron schedules
	 *
	 * @param array $schedules Schedules.
	 */
	public function add_schedules( $schedules = array() ) {
		// Adds once in biweekly to the existing schedules.
		$schedules['biweekly'] = array(
			'interval' => ( DAY_IN_SECONDS * 15 ),
			'display'  => __( 'Every 15 days', 'everest-forms' ),
		);

		return $schedules;
	}

	/**
	 * Schedules our events
	 *
	 * @return void
	 */
	public function schedule_events() {
		$this->biweekly_events();
	}

	/**
	 * Schedule biweekly events
	 *
	 * @return void
	 */
	private function biweekly_events() {
		if ( ! wp_next_scheduled( 'everest_forms_biweekly_scheduled_events' ) ) {
			wp_schedule_event( time(), 'biweekly', 'everest_forms_biweekly_scheduled_events' );
		}
	}

}

$everest_forms_cron = new EVF_Cron();
