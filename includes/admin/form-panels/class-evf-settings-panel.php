<?php
/**
 * EverestForms Admin
 *
 * @class    EVF_Admin
 * @author   WPEverest
 * @category Admin
 * @package  EverestForms/Admin/FormPanel
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * EVF_Settings_Panel class.
 */
class EVF_Settings_Panel extends EVF_Admin_Form_Panel {

	/**
	 * All systems go.
	 *
	 * @since      1.0.0
	 */
	public function init() {

		// Define panel information
		$this->name    = __( 'Settings', 'everest-forms' );
		$this->slug    = 'settings';
		$this->icon    = 'dashicons dashicons-admin-tools';
		$this->order   = 10;
		$this->sidebar = true;
	}

	/**
	 * Enqueue assets for the Setting panel.
	 *
	 * @since      1.0.0
	 */
	public function enqueues() {


	}

	/**
	 * Outputs the Settings panel sidebar.
	 *
	 * @since      1.0.0
	 */
	public function panel_sidebar() {

		$sections = array(
			'general' => __( 'General', 'everest-forms' ),
			'email'   => __( 'Email', 'everest-forms' ),
		);
		$sections = apply_filters( 'everest_forms_builder_settings_section', $sections, $this->form_data );
		foreach ( $sections as $slug => $section ) {
			$this->panel_sidebar_section( $section, $slug );
		}
	}

	/**
	 * Outputs the Settings panel primary content.
	 *
	 * @since      1.0.0
	 */
	public function panel_content() {

		// --------------------------------------------------------------------//
		// General
		// --------------------------------------------------------------------//
		echo '<div class="evf-content-section evf-content-general-settings">';
		echo '<div class="evf-content-section-title">';
		_e( 'General', 'everest-forms' );
		echo '</div>';
		everest_forms_panel_field(
			'text',
			'settings',
			'form_title',
			$this->form_data,
			__( 'Form Name', 'everest-forms' ),
			array(
				'default' => $this->form->post_title,
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'successful_form_submission_message',
			$this->form_data,
			__( 'Successful form submission message', 'everest-forms' ),
			array(
				'default' => isset( $this->form->successful_form_submission_message ) ? $this->form->successful_form_submission_message : __( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'redirect_to',
			$this->form_data,
			__( 'Redirect To', 'everest-forms' ),
			array(
				'default' => '0',
				'options' => array(
					'0' => __( 'Same Page', 'everest-forms' ),
					'1' => __( 'Custom Page', 'everest-forms' ),
					'2' => __( 'External URL', 'everest-forms' ),
				),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'custom_page',
			$this->form_data,
			__( 'Custom Page', 'everest-forms' ),
			array(
				'default' => '0',
				'options' => $this->evf_get_all_pages(),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'external_url',
			$this->form_data,
			__( 'External URL', 'everest-forms' ),
			array(
				'default' => isset( $this->form->external_url ) ? $this->form->external_url : '',
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'layout_class',
			$this->form_data,
			__( 'Layout Design', 'everest-forms' ),
			array(
				'default' => '0',
				'options' => array(
					'default' => __( 'Default', 'everest-forms' ),
					'layout-two' => __( 'Classic Layout', 'everest-forms' ),
				),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'form_class',
			$this->form_data,
			__( 'Form Class', 'everest-forms' ),
			array(
				'default' => isset( $this->form->form_class ) ? $this->form->form_class : '',
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_text',
			$this->form_data,
			__( 'Submit button text', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['submit_button_text'] ) ? $this->form_setting['submit_button_text'] : __( 'Submit', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'recaptcha_support',
			$this->form_data,
			sprintf( __( 'Enable %1$s %2$s reCaptcha %3$s support', 'everest-forms' ), '<a title="', 'Please make sure the site key and secret are not empty in setting page." href="' . admin_url() . 'admin.php?page=evf-settings&tab=recaptcha" target="_blank">', '</a>' ),
			array(
				'default' => '0',
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'disabled_entries',
			$this->form_data,
			__( 'Disable storing entry information', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['disabled_entries'] ) ? $this->form_setting['disabled_entries'] : 0,
			)
		);

		do_action( 'everest_forms_general_settings', $this );

		echo '</div>';

		// --------------------------------------------------------------------//
		// Email
		// --------------------------------------------------------------------//
		echo '<div class="evf-content-section evf-content-email-settings">';
		echo '<div class="evf-content-section-title">';
		_e( 'Email', 'everest-forms' );
		echo '</div>';
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_to_email',
			$this->form_data,
			__( 'To Address', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_to_email'] ) ? $this->form_setting['email']['evf_to_email'] : get_option( 'admin_email' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_from_name',
			$this->form_data,
			__( 'From Name', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_from_name'] ) ? $this->form_setting['email']['evf_from_name'] : get_bloginfo( 'name', 'display' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_from_email',
			$this->form_data,
			__( 'From Address', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_from_email'] ) ? $this->form_setting['email']['evf_from_email'] : get_option( 'admin_email' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_email_subject',
			$this->form_data,
			__( 'Email Subject', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_email_subject'] ) ? $this->form_setting['email']['evf_email_subject'] : __( 'New Form Entry', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'tinymce',
			'settings[email]',
			'evf_email_message',
			$this->form_data,
			__( 'Email Message', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_email_message'] ) ? $this->form_setting['email']['evf_email_message'] : __( '{all_fields}', 'everest-forms' ),
			)
		);
		do_action( 'everest_forms_email_settings', $this );

		echo '</div>';

		do_action( 'everest_forms_settings_panel_content', $this );
	}

	public function evf_get_all_pages(){
		foreach(get_pages() as $page){
			$pages[$page->ID] = $page->post_title; ;
		}
		return $pages;
	}
}

new EVF_Settings_Panel;
