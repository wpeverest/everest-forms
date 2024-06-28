<?php
/**
 * EverestForms Reporting Settings
 *
 * @package EverestForms\Admin
 * @version 2.0.9
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
	 *
	 * @since 2.0.9
	 */
	public function __construct() {
		$this->id    = 'reporting';
		$this->label = esc_html__( 'Reporting', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @since 2.0.9
	 * @return array
	 */
	public function get_settings() {
		$evf_form_lists    = evf_get_all_forms();
		$evf_summary_email = get_option( 'everest_forms_email_send_to', '' );
		$settings          = apply_filters(
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
					'default'  => 'no',
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
					'desc_tip' => esc_html__( 'Email address to send the routine report', 'everest-forms' ),
					'id'       => 'everest_forms_entries_reporting_email',
					'default'  => '{admin_email}',
					'type'     => 'text',
				),
				array(
					'title'    => esc_html__( 'Email Subject', 'everest-forms' ),
					'desc_tip' => esc_html__( 'Email subject while sending the routine report', 'everest-forms' ),
					'id'       => 'everest_forms_entries_reporting_subject',
					'default'  => esc_html__( 'Everest Forms - Entries summary statistics', 'everest-forms' ),
					'type'     => 'text',
				),
				array(
					'title'       => esc_html__( 'Send Test Report', 'everest-forms' ),
					'desc'        => esc_html__( 'Enter the email address to receive the test email for the routine summary report.', 'everest-forms' ),
					'input_id'    => 'everest_forms_email_send_to',
					'input_type'  => 'email',
					'input_css'   => 'margin-right:0.5rem',
					'placeholder' => 'eg. testemail@gmail.com',
					'value'       => ! empty( $evf_summary_email ) ? esc_attr( $evf_summary_email ) : esc_attr( get_bloginfo( 'admin_email' ) ),
					'button_id'   => 'everest_forms_send_routine_report_test_email',
					'type'        => 'input_test_button',
					'buttons'     => array(
						array(
							'title' => __( 'Send Test Email', 'everest-forms' ),
							'href'  => 'javascript:;',
							'class' => 'everest_forms_send_routine_report_test_email',
						),
					),
					'desc_tip'    => true,
				),
				array(
					'title'    => esc_html__( 'Report Form Lists', 'everest-forms' ),
					'id'       => 'everest_forms_reporting_form_lists',
					'desc'     => esc_html__( 'Name of the forms to send the weekly report', 'everest-forms' ),
					'desc_tip' => true,
					'type'     => 'multiselect',
					'options'  => ! empty( $evf_form_lists ) ? $evf_form_lists : array(),
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
	 *
	 * @since 2.0.9
	 */
	public function save() {
		$settings = $this->get_settings();

		EVF_Admin_Settings::save_fields( $settings );
	}
}

return new EVF_Settings_Reporting();
