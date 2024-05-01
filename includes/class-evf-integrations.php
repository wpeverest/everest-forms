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
		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			$this->integrations = array(
				'dropbox'       => (object) array(
					'id'                 => 'dropbox',
					'method_title'       => 'Dropbox',
					'icon'               => plugins_url( 'assets/images/integration-image/dropbox.png', EVF_PLUGIN_FILE ),
					'method_description' => 'Dropbox Integration with Everest Forms',
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => '5Vt82fN0swo',
				),
				'google_drive'  => (object) array(
					'id'                 => 'google_drive',
					'method_title'       => 'Google Drive',
					'icon'               => plugins_url( 'assets/images/integration-image/google-drive.png', EVF_PLUGIN_FILE ),
					'method_description' => 'Google Drive Integration with Everest Forms',
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => '0g-dfhMy1Yo',
				),
				'mailchimp'     => (object) array(
					'id'                 => 'mailchimp',
					'method_title'       => 'MailChimp',
					'icon'               => plugins_url( 'assets/images/integration-image/mailchimp.png', EVF_PLUGIN_FILE ),
					'method_description' => 'MailChimp Integration with Everest Forms',
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => 'FhFsHdAIXwE',
				),
				'google_sheets' => (object) array(
					'id'                 => 'google_sheets',
					'method_title'       => 'Google Sheets',
					'icon'               => plugins_url( 'assets/images/integration-image/google-sheets.png', EVF_PLUGIN_FILE ),
					'method_description' => 'Google Sheets Integration with Everest Forms',
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => 'tvS6Y_rNBfs',
				),
				'convertkit'    => (object) array(
					'id'                 => 'convertkit',
					'method_title'       => 'ConvertKit',
					'icon'               => plugins_url( 'assets/images/integration-image/convertkit.png', EVF_PLUGIN_FILE ),
					'method_description' => 'Marketing automation can be hard to wrap your brain around, but with ConvertKit, it’s easy.',
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => 'GvqPVCK7Ws8',
				),
			);
		}

		return $this->integrations;
	}
}
