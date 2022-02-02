<?php
/**
 * EverestForms Email Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Email', false ) ) {
	return new EVF_Settings_Email();
}

/**
 * EVF_Settings_Email.
 */
class EVF_Settings_Email extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'email';
		$this->label = esc_html__( 'Email', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'everest_forms_email_settings',
			array(
				array(
					'title' => esc_html__( 'Template Settings', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'email_template_options',
				),
				array(
					'title'   => esc_html__( 'Template', 'everest-forms' ),
					'type'    => 'radio-image',
					'id'      => 'everest_forms_email_template',
					'desc'    => esc_html__( 'Determine which format of email to send. HTML Template is default.', 'everest-forms' ),
					'default' => 'default',
					'options' => array(
						'default' => array(
							'name'  => esc_html__( 'HTML Template', 'everest-forms' ),
							'image' => plugins_url( 'assets/images/email-template-html.png', EVF_PLUGIN_FILE ),
						),
						'none'    => array(
							'name'  => esc_html__( 'Plain text', 'everest-forms' ),
							'image' => plugins_url( 'assets/images/email-template-plain.png', EVF_PLUGIN_FILE ),
						),
					),
				),
				array(
					'title'    => esc_html__( 'Enable copies', 'everest-forms' ),
					'desc'     => esc_html__( 'Enable the use of Cc and Bcc email addresses', 'everest-forms' ),
					'desc_tip' => esc_html__( 'Email addresses for Cc and Bcc can be applied from the form notification settings.', 'everest-forms' ),
					'id'       => 'everest_forms_enable_email_copies',
					'default'  => 'no',
					'type'     => 'checkbox',
				),
				array(
					'title'       => esc_html__( 'Send Test Email To', 'everest-forms' ),
					'desc'        => esc_html__( 'Enter email address where test email will be sent.', 'everest-forms' ),
					'id'          => 'everest_forms_email_send_to',
					'type'        => 'email',
					'placeholder' => 'eg. testemail@gmail.com',
					'value'       => esc_attr( get_bloginfo( 'admin_email' ) ),
					'desc_tip'    => true,
				),
				array(
					'title'    => __( 'Send Test Email', 'everest-forms' ),
					'desc'     => __( 'Click to send test email.', 'everest-forms' ),
					'id'       => 'everest_forms_email_test',
					'type'     => 'link',
					'buttons'  => array(
						array(
							'title' => __( 'Send Test Email', 'everest-forms' ),
							'href'  => 'javascript:;',
							'class' => 'everest_forms_send_email_test',
						),
					),
					'desc_tip' => true,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'email_template_options',
				),
			)
		);

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		EVF_Admin_Settings::save_fields( $settings );
	}
}

return new EVF_Settings_Email();
