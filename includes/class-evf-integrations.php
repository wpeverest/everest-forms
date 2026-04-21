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

		foreach ( $load_integrations as $integration ) {
			$load_integration                            = new $integration();
			$this->integrations[ $load_integration->id ] = $load_integration;
		}
	}

	/**
	 * Return loaded integrations.
	 *
	 * @return array
	 */
	public function get_integrations() {
		$default_integrations = array();

		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			$default_integrations = array(

				'dropbox'       => (object) array(
					'id'                 => 'dropbox',
					'category'           => esc_html__( 'Cloud Storage', 'everest-forms' ),
					'method_title'       => 'Dropbox',
					'icon'               => plugins_url( 'assets/images/integration-image/dropbox.png', EVF_PLUGIN_FILE ),
					'method_description' => esc_html__( 'Automatically upload form attachments and entries straight to your Dropbox.', 'everest-forms' ),
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => '5Vt82fN0swo',
					'upgrade_url'        => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'           => 'https://docs.everestforms.net/docs/how-to-upload-files-to-google-drive-or-dropbox/#4-toc-title',
					'features'           => array(
						esc_html__( 'Store uploaded form files directly in Dropbox', 'everest-forms' ),
						esc_html__( 'Keep important files backed up in your usual storage tool', 'everest-forms' ),
						esc_html__( 'Avoid filling up your website server with uploads', 'everest-forms' ),
						esc_html__( 'Keep your file workflow simple and familiar', 'everest-forms' ),
					),
				),

				'google_drive'  => (object) array(
					'id'                 => 'google_drive',
					'category'           => esc_html__( 'Cloud Storage', 'everest-forms' ),
					'method_title'       => 'Google Drive',
					'icon'               => plugins_url( 'assets/images/integration-image/google-drive.png', EVF_PLUGIN_FILE ),
					'method_description' => esc_html__( 'Save form entries and uploaded files directly to Google Drive, organised your way.', 'everest-forms' ),
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => '0g-dfhMy1Yo',
					'upgrade_url'        => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'           => 'https://docs.everestforms.net/docs/how-to-upload-files-to-google-drive-or-dropbox/#0-toc-title',
					'features'           => array(
						esc_html__( 'Save uploaded files to Google Drive automatically', 'everest-forms' ),
						esc_html__( 'Reduce storage use on your website', 'everest-forms' ),
						esc_html__( 'Make files easier to access for your team', 'everest-forms' ),
						esc_html__( 'Stay organized without changing your workflow', 'everest-forms' ),
					),
				),

				'mailchimp'     => (object) array(
					'id'                 => 'mailchimp',
					'category'           => esc_html__( 'Email Marketing', 'everest-forms' ),
					'method_title'       => 'MailChimp',
					'icon'               => plugins_url( 'assets/images/integration-image/mailchimp.png', EVF_PLUGIN_FILE ),
					'method_description' => esc_html__( 'Grow your Mailchimp audience automatically from every form submission.', 'everest-forms' ),
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => 'FhFsHdAIXwE',
					'upgrade_url'        => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'           => 'https://docs.everestforms.net/docs/mailchimp/',
					'features'           => array(
						esc_html__( 'Add form subscribers to your Mailchimp list', 'everest-forms' ),
						esc_html__( 'Map form fields to Mailchimp list fields', 'everest-forms' ),
						esc_html__( 'Grow your email list without extra manual work', 'everest-forms' ),
						esc_html__( 'Keep your leads and subscribers organized ', 'everest-forms' ),
					),
				),

				'google_sheets' => (object) array(
					'id'                 => 'google_sheets',
					'category'           => esc_html__( 'Google Sheets', 'everest-forms' ),
					'method_title'       => 'Google Sheets',
					'icon'               => plugins_url( 'assets/images/integration-image/google-sheets.png', EVF_PLUGIN_FILE ),
					'method_description' => esc_html__( 'Stream form submissions into a Google Sheet in real time — no copy-paste needed.', 'everest-forms' ),
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => 'tvS6Y_rNBfs',
					'upgrade_url'        => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'           => 'https://docs.everestforms.net/docs/google-sheets/',
					'features'           => array(
						esc_html__( 'Save new submissions to Google Sheets', 'everest-forms' ),
						esc_html__( 'View and manage form data in a familiar spreadsheet', 'everest-forms' ),
						esc_html__( 'Share entries easily with your team', 'everest-forms' ),
						esc_html__( 'Keep records updated in real time ', 'everest-forms' ),
					),
				),

				'convertkit'    => (object) array(
					'id'                 => 'convertkit',
					'category'           => esc_html__( 'Email Marketing', 'everest-forms' ),
					'method_title'       => 'ConvertKit',
					'icon'               => plugins_url( 'assets/images/integration-image/convertkit.png', EVF_PLUGIN_FILE ),
					'method_description' => esc_html__( 'Add subscribers to ConvertKit sequences and tags straight from your forms.', 'everest-forms' ),
					'account_status'     => 'upgrade-modal',
					'upgrade'            => 'upgrade',
					'vedio_id'           => 'GvqPVCK7Ws8',
					'upgrade_url'        => 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/',
					'docs_url'           => 'https://docs.everestforms.net/docs/convertkit/',
					'features'           => array(
						esc_html__( 'Add new subscribers automatically from form submissions', 'everest-forms' ),
						esc_html__( 'Turn your forms into lead collection tools', 'everest-forms' ),
						esc_html__( 'Save time by reducing manual export and import work', 'everest-forms' ),
						esc_html__( 'Keep subscriber list growing in the background', 'everest-forms' ),
					),
				),

			);
		}

		$this->integrations = array_merge( $this->integrations, $default_integrations );

		return $this->integrations;
	}
}
