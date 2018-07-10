<?php
/**
 * EverestForms Integrations class
 *
 * Loads Integrations into EverestForms.
 *
 * @package EverestForms/Classes/Integrations
 * @version 1.2.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integrations class.
 */
class EVF_Integrations {

	/**
	 * Array of integrations.
	 *
	 * @var array
	 */
	public $integrations = array();

	/**
	 * Initialize integrations.
	 */
	public function __construct() {

		do_action( 'everest_forms_integrations_init' );

		$load_integrations = apply_filters( 'everest_forms_integrations', array() );

		// Load integration classes.
		foreach ( $load_integrations as $integration ) {

			$load_integration = new $integration();

			$this->integrations[ $load_integration->id ] = $load_integration;
		}
	}

	/**
	 * Return loaded integrations.
	 *
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}
}
