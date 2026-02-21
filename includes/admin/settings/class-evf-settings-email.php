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
		$this->label = esc_html__( 'Email & Reports', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * Get sections for email and reports tab.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'general'       => esc_html__( 'General', 'everest-forms' ),
			'entry_reports' => esc_html__( 'Entry Reports & Summaries', 'everest-forms' ),
		);

		return apply_filters( 'everest_forms_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	// public function get_settings() {
	// $settings = apply_filters(
	// 'everest_forms_email_settings',
	// array(
	// array(
	// 'title' => esc_html__( 'Template Settings', 'everest-forms' ),
	// 'type'  => 'title',
	// 'desc'  => '',
	// 'id'    => 'email_template_options',
	// ),
	// array(
	// 'title'   => esc_html__( 'Template', 'everest-forms' ),
	// 'type'    => 'radio-image',
	// 'id'      => 'everest_forms_email_template',
	// 'desc'    => esc_html__( 'Determine which format of email to send. HTML Template is default.', 'everest-forms' ),
	// 'default' => 'default',
	// 'options' => array(
	// 'default' => array(
	// 'name'  => esc_html__( 'HTML Template', 'everest-forms' ),
	// 'image' => plugins_url( 'assets/images/email-template-html.png', EVF_PLUGIN_FILE ),
	// ),
	// 'none'    => array(
	// 'name'  => esc_html__( 'Plain text', 'everest-forms' ),
	// 'image' => plugins_url( 'assets/images/email-template-plain.png', EVF_PLUGIN_FILE ),
	// ),
	// ),
	// ),
	// array(
	// 'title'    => esc_html__( 'Enable copies', 'everest-forms' ),
	// 'desc'     => esc_html__( 'Email addresses for Cc and Bcc can be applied from the form notification settings.', 'everest-forms' ),
	// 'id'       => 'everest_forms_enable_email_copies',
	// 'default'  => 'no',
	// 'type'     => 'toggle',
	// 'desc_tip' => true,
	// ),
	// array(
	// 'id'          => 'everest_forms_email_send_to',
	// 'title'       => esc_html__( 'Send Test Email To', 'everest-forms' ),
	// 'desc'        => esc_html__( 'Enter the email address where test email will be sent.', 'everest-forms' ),
	// 'input_id'    => 'everest_forms_email_send_to',
	// 'placeholder' => 'eg. testemail@gmail.com',
	// 'input_type'  => 'email',
	// 'default'     => get_option( 'everest_forms_email_send_to', '' ) ? esc_attr( get_option( 'everest_forms_email_send_to', '' ) ) : esc_attr( get_bloginfo( 'admin_email' ) ),
	// 'button_id'   => 'everest_forms_email_test',
	// 'type'        => 'input_test_button',
	// 'input_css'   => 'margin-right:0.5rem',
	// 'buttons'     => array(
	// array(
	// 'title' => __( 'Send Test Email', 'everest-forms' ),
	// 'href'  => 'javascript:;',
	// 'class' => 'everest_forms_send_email_test',
	// ),
	// ),
	// 'desc_tip'    => true,
	// ),
	// array(
	// 'type' => 'sectionend',
	// 'id'   => 'email_template_options',
	// ),
	// )
	// );

	// return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	// }


	/**
	 * Output sections in navigation sidebar.
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}

		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'general';

		echo '<ul class="evf-subsections">';

		foreach ( $sections as $id => $label ) {
			$url = add_query_arg(
				array(
					'page'    => 'evf-settings',
					'tab'     => $this->id,
					'section' => sanitize_title( $id ),
				),
				admin_url( 'admin.php' )
			);

			echo '<li><a href="' . esc_url( $url ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a></li>';
		}

		echo '</ul>';
	}

	/**
	 * Get settings array based on current section.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'general';

		switch ( $current_section ) {
			case 'entry_reports':
				$settings = $this->get_entry_reports_settings();
				break;
			default:
				$settings = $this->get_email_default_settings();
				break;
		}

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings, $current_section );
	}


	/**
	 * Get email default settings.
	 *
	 * @return array
	 */
	public function get_email_default_settings() {
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
						'desc'     => esc_html__( 'Email addresses for Cc and Bcc can be applied from the form notification settings.', 'everest-forms' ),
						'id'       => 'everest_forms_enable_email_copies',
						'default'  => 'no',
						'type'     => 'toggle',
						'desc_tip' => true,
					),
					array(
						'id'          => 'everest_forms_email_send_to',
						'title'       => esc_html__( 'Send Test Email To', 'everest-forms' ),
						'desc'        => esc_html__( 'Enter the email address where test email will be sent.', 'everest-forms' ),
						'input_id'    => 'everest_forms_email_send_to',
						'placeholder' => 'eg. testemail@gmail.com',
						'input_type'  => 'email',
						'default'     => get_option( 'everest_forms_email_send_to', '' ) ? esc_attr( get_option( 'everest_forms_email_send_to', '' ) ) : esc_attr( get_bloginfo( 'admin_email' ) ),
						'button_id'   => 'everest_forms_email_test',
						'type'        => 'input_test_button',
						'input_css'   => 'margin-right:0.5rem',
						'buttons'     => array(
							array(
								'title' => __( 'Send Test Email', 'everest-forms' ),
								'href'  => 'javascript:;',
								'class' => 'everest_forms_send_email_test',
							),
						),
						'desc_tip'    => true,
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
	 * Get entry reports settings.
	 *
	 * @return array
	 */
	public function get_entry_reports_settings() {
			$evf_form_lists = evf_get_all_forms();
		$evf_summary_email  = get_option( 'everest_forms_email_send_to', '' );
		$settings           = apply_filters(
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
	 */
	public function save() {
		$settings = $this->get_settings();

		EVF_Admin_Settings::save_fields( $settings );
	}
}

return new EVF_Settings_Email();
