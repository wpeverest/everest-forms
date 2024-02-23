<?php
/**
 * EverestForms Reporting Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Reporting', false ) ) {
	return new EVF_Settings_Reporting();
}

/**
 * EVF_Settings_Reporting.
 */
class EVF_Settings_Reporting extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'reporting';
		$this->label = esc_html__( 'Reporting', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'everest_forms_reporting_settings',
			array(
				array(
					'title' => esc_html__( 'Forms Entries Statistics Reporting', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'reporting_options',
				),
				array(
					'title'    => esc_html__( 'Enable Entries Statistics Reporting', 'everest-forms' ),
					'desc'     => esc_html__( 'Enable to send the entries statistics reporting email on routine basis.', 'everest-forms' ),
					'id'       => 'everest_forms_enable_entries_reporting',
					'default'  => 'yes',
					'type'     => 'toggle',
					'desc_tip' => true,
				),
				array(
					'title'    => esc_html__( 'Report Frequency', 'everest-forms' ),
					'type'     => 'select',
					'options'  => array(
						'Daily'   => esc_html__( 'Daily', 'everest-forms' ),
						'Weekly'  => esc_html__( 'Weekly', 'everest-forms' ),
						'Monthly' => esc_html__( 'Monthly', 'everest-forms' ),
						'Yearly'  => esc_html__( 'Yearly', 'everest-forms' ),
					),
					'id'       => 'everest_forms_entries_reporting_frequency',
					'default'  => esc_html__( 'Weekly', 'everest-forms' ),
					'desc'     => esc_html__( 'How often should the report be emailed?', 'everest-forms' ),
					'desc_tip' => true,
				),
				array(
					'title'    => esc_html__( 'Day To Send', 'everest-forms' ),
					'type'     => 'select',
					'options'  => array(
						'sunday'    => esc_html__( 'Sunday', 'everest-forms' ),
						'monday'    => esc_html__( 'Monday', 'everest-forms' ),
						'tuesday'   => esc_html__( 'Tuesday', 'everest-forms' ),
						'wednesday' => esc_html__( 'Wednesday', 'everest-forms' ),
						'thursday'  => esc_html__( 'Thursday', 'everest-forms' ),
						'friday'    => esc_html__( 'Friday', 'everest-forms' ),
						'saturday'  => esc_html__( 'Saturday', 'everest-forms' ),
					),
					'id'       => 'everest_forms_entries_reporting_day',
					'default'  => esc_html__( 'Monday', 'everest-forms' ),
					'desc'     => esc_html__( 'What day of the week should the weekly report be sent?', 'everest-forms' ),
					'desc_tip' => true,
				),
				array(
					'title'    => esc_html__( 'Email To', 'everest-forms' ),
					'desc_tip' => esc_html__( 'Email address to send the routine report' ),
					'id'       => 'everest_forms_entries_reporting_email',
					'default'  => '',
					'type'     => 'text',
				),
				array(
					'title'    => esc_html__( 'Email Subject', 'everest-forms' ),
					'desc_tip' => esc_html__( 'Email subject while sending the routine report' ),
					'id'       => 'everest_forms_entries_reporting_subject',
					'default'  => '',
					'type'     => 'text',
				),
				array(
					'title'       => esc_html__( 'Send Test Email To', 'everest-forms' ),
					'desc'        => esc_html__( 'Enter email address where test email will be sent.', 'everest-forms' ),
					'id'          => 'everest_forms_email_send_to',
					'type'        => 'email',
					'placeholder' => 'eg. testemail@gmail.com',
					'value'       => get_option( 'everest_forms_email_send_to', '' ) ? esc_attr( get_option( 'everest_forms_email_send_to', '' ) ) : esc_attr( get_bloginfo( 'admin_email' ) ),
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
					'title'    => esc_html__( 'Report Form Lists', 'everest-forms' ),
					'id'       => 'everest_forms_reporting_form_lists',
					'desc'     => esc_html__( 'Name of the forms to send the weekly report', 'everest-forms' ),
					'desc_tip' => true,
					'type'     => 'multiselect',
					'options'  => evf_get_all_forms( true, false ),
					'class'    => 'evf-enhanced-select',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'reporting_options',
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

return new EVF_Settings_Reporting();
