<?php
/**
 * EverestForms Misc Settings
 *
 * @package EverestForms\Admin
 * @version 1.9.8
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Advanced', false ) ) {
	return new EVF_Settings_Advanced();
}

/**
 * EVF_Settings_Advanced.
 */
class EVF_Settings_Advanced extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'advanced';
		$this->label = esc_html__( 'Advanced', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'general'           => esc_html__( 'General', 'everest-forms' ),
			'entry_reports'     => esc_html__( 'Entry Reports & Summaries', 'everest-forms' ),
			'plugin_management' => esc_html__( 'Plugin Management', 'everest-forms' ),
		);

		$entries_settings = apply_filters( 'everest_forms_entries_management_settings', array() );
		if ( ! empty( $entries_settings ) ) {
			$sections = array(
				'general'            => esc_html__( 'General', 'everest-forms' ),
				'entries_management' => esc_html__( 'Entries Management', 'everest-forms' ),
				'entry_reports'      => esc_html__( 'Entry Reports & Summaries', 'everest-forms' ),
				'plugin_management'  => esc_html__( 'Plugin Management', 'everest-forms' ),
			);
		}

		return apply_filters( 'everest_forms_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output sections.
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
			case 'entries_management':
				$settings = $this->get_entries_management_settings();
				break;
			case 'entry_reports':
				$settings = $this->get_entry_reports_settings();
				break;
			case 'plugin_management':
				$settings = $this->get_plugin_management_settings();
				break;
			default:
				$settings = $this->get_advanced_default_settings();
				break;
		}

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Get entries management settings.
	 *
	 * @return array
	 */
	public function get_entries_management_settings() {
		$base_settings = array(
			array(
				'title' => esc_html__( 'Advanced Options', 'everest-forms' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'misc_options',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'misc_options',
			),
		);

		$settings = apply_filters( 'everest_forms_entries_management_settings', $base_settings );

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get advanced default settings.
	 *
	 * @return array
	 */
	public function get_advanced_default_settings() {
		$allow_usage_notice_msg = wp_kses(
			__( 'Help us improve the plugin\'s features by sharing <a href="https://docs.everestforms.net/docs/misc-settings-4/#2-toc-title" target="_blank">non-sensitive plugin data</a> with us.', 'everest-forms' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);

		return array(
			array(
				'title' => esc_html__( 'Advanced Options', 'everest-forms' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'misc_options',
			),
				array(
					'title'    => esc_html__( 'Enable Log', 'everest-forms' ),
					'desc'     => esc_html__( 'Enable storing the logs.', 'everest-forms' ),
					'id'       => 'everest_forms_enable_log',
					'default'  => 'no',
					'type'     => 'toggle',
					'desc_tip' => true,
				),
			array(
				'title'    => esc_html__( 'Enable RestApi', 'everest-forms' ),
				'desc'     => __( 'Allow the other to use the rest api.', 'everest-forms' ),
				'id'       => 'everest_forms_enable_restapi',
				'type'     => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			array(
				'title'    => esc_html__( 'RestApi Key', 'everest-forms' ),
				'desc'     => __( 'List of api key.These are used to authenticate the request.', 'everest-forms' ),
				'id'       => 'everest_forms_restapi_keys',
				'type'     => 'restapi_key',
				'default'  => '',
				'desc_tip' => true,
				'css'      => 'width=500px !important;',
				'class'    => 'evf-restapi-key',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'misc_options',
			),
		);
	}

	/**
	 * Get entry reports settings.
	 *
	 * @return array
	 */
	public function get_entry_reports_settings() {
		$evf_form_lists = evf_get_all_forms();
		$evf_test_email = get_option( 'everest_forms_routine_report_send_email_test_to', '' );

		$schedule_status = '';
		if ( class_exists( 'EVF_Report_Cron' ) ) {
			$cron            = new EVF_Report_Cron();
			$schedule_status = $cron->get_schedule_status();
		}

		return array(
			array(
				'title' => esc_html__( 'Forms Entries Statistics Reporting', 'everest-forms' ),
				'type'  => 'title',
				'desc'  => ! empty( $schedule_status['message'] ) ? esc_html( $schedule_status['message'] ) : '',
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
				'default'  => 'Weekly',
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
				'default'  => 'monday',
				'desc'     => esc_html__( 'What day of the week should the weekly report be sent?', 'everest-forms' ),
				'desc_tip' => true,
			),
			array(
				'title'    => esc_html__( 'Email To', 'everest-forms' ),
				'desc_tip' => esc_html__( 'Email address to send the routine report. Use {admin_email} for the site admin address.', 'everest-forms' ),
				'id'       => 'everest_forms_entries_reporting_email',
				'default'  => '{admin_email}',
				'type'     => 'text',
			),
			array(
				'title'    => esc_html__( 'Email Subject', 'everest-forms' ),
				'desc_tip' => esc_html__( 'Email subject while sending the routine report.', 'everest-forms' ),
				'id'       => 'everest_forms_entries_reporting_subject',
				'default'  => esc_html__( 'Everest Forms - Entries summary statistics', 'everest-forms' ),
				'type'     => 'text',
			),
			array(
				'title'       => esc_html__( 'Send Test Report', 'everest-forms' ),
				'desc'        => esc_html__( 'Enter the email address to receive the test email for the routine summary report.', 'everest-forms' ),
				'input_id'    => 'everest_forms_routine_report_send_email_test_to',
				'input_type'  => 'email',
				'input_css'   => 'margin-right:0.5rem',
				'placeholder' => 'eg. testemail@gmail.com',
				'value'       => ! empty( $evf_test_email ) ? esc_attr( $evf_test_email ) : esc_attr( get_bloginfo( 'admin_email' ) ),
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
				'default'  => array(),
				'desc'     => esc_html__( 'Select specific forms to include in the report. Leave empty to include all published forms.', 'everest-forms' ),
				'desc_tip' => true,
				'type'     => 'multiselect',
				'options'  => ! empty( $evf_form_lists ) ? $evf_form_lists : array(),
				'class'    => 'evf-enhanced-select',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'reporting_options',
			),
		);
	}

	/**
	 * Get plugin management settings.
	 *
	 * @return array
	 */
	public function get_plugin_management_settings() {
		$allow_usage_notice_msg = wp_kses(
			__( 'Help us improve the plugin\'s features by sharing <a href="https://docs.everestforms.net/docs/misc-settings-4/#2-toc-title" target="_blank">non-sensitive plugin data</a> with us.', 'everest-forms' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);

		$settings = apply_filters(
			'everest_forms_plugin_management_settings',
			array(
				array(
					'title' => esc_html__( 'Advanced Options', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'misc_options',
				),
				array(
					'title'    => esc_html__( 'Uninstall Everest Forms', 'everest-forms' ),
					'desc'     => __( '<strong>Heads Up!</strong> Check this if you would like to remove ALL Everest Forms data upon plugin deletion.', 'everest-forms' ),
					'id'       => 'everest_forms_uninstall_option',
					'default'  => 'no',
					'type'     => 'toggle',
					'desc_tip' => true,
				),
				array(
					'title'    => esc_html__( 'Allow Usage Tracking', 'everest-forms' ),
					'desc'     => $allow_usage_notice_msg,
					'id'       => 'everest_forms_allow_usage_tracking',
					'type'     => 'toggle',
					'default'  => 'no',
					'desc_tip' => true,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'misc_options',
				),
			)
		);

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'general';

		if ( 'entry_reports' === $current_section && empty( $_POST['everest_forms_reporting_form_lists'] ) ) {
			update_option( 'everest_forms_reporting_form_lists', array() );
		}

		EVF_Admin_Settings::save_fields( $this->get_settings() );

		if ( 'entry_reports' === $current_section && class_exists( 'EVF_Report_Cron' ) ) {
			( new EVF_Report_Cron() )->evf_reschedule();
		}
	}
}

return new EVF_Settings_Advanced();
