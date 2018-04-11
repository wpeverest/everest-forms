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
			'textarea',
			'settings',
			'successful_form_submission_message',
			$this->form_data,
			__( 'Successful form submission message', 'everest-forms' ),
			array(
				'default' => isset( $this->form->successful_form_submission_message ) ? $this->form->successful_form_submission_message : get_option( 'everest_forms_successful_form_submission_message', __('Thanks for contacting us! We will be in touch with you shortly','everest-forms')),
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

		$disable = get_option( 'everest_forms_disable_form_entries' );
		$disable = $disable === 'yes' ? 1 : 0;

		everest_forms_panel_field(
			'checkbox',
			'settings',
			'disabled_entries',
			$this->form_data,
			__( 'Disable Form Entries', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['disabled_entries'] ) ? $this->form_setting['disabled_entries'] : $disable,
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_text',
			$this->form_data,
			__( 'Submit button text', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['submit_button_text'] ) ? $this->form_setting['submit_button_text'] : get_option( 'everest_forms_form_submit_button_label', __( 'Submit', 'everest-forms' ) ),
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
				'default' => isset( $this->form_setting['email']['evf_to_email'] ) ? $this->form_setting['email']['evf_to_email'] : get_option( 'evf_to_email', get_option( 'admin_email' ) ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_from_name',
			$this->form_data,
			__( 'From Name', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_from_name'] ) ? $this->form_setting['email']['evf_from_name'] : get_option( 'evf_from_name', evf_sender_name() ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_from_email',
			$this->form_data,
			__( 'From Address', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_from_email'] ) ? $this->form_setting['email']['evf_from_email'] : get_option( 'evf_from_address', evf_sender_address() ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_email_subject',
			$this->form_data,
			__( 'Email Subject', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_email_subject'] ) ? $this->form_setting['email']['evf_email_subject'] : get_option( 'evf_email_subject',  __( 'New Form Entry', 'everest-forms' ) ),
			)
		);
		everest_forms_panel_field(
			'tinymce',
			'settings[email]',
			'evf_email_message',
			$this->form_data,
			__( 'Email Message', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_email_message'] ) ? $this->form_setting['email']['evf_email_message'] :  get_option( 'evf_email_message', __( '{all_fields}', 'everest-forms' ) ),
			)
		);

		everest_forms_panel_field(
			'checkbox',
			'settings[email]',
			'send_confirmation_email_to_user',
			$this->form_data,
			sprintf( __( 'Send Confirmation Email To User', 'everest-forms' )),
			array(
				'default' =>  isset( $this->form_setting['email']['send_confirmation_email_to_user'] ) ? $this->form_setting['email']['send_confirmation_email_to_user'] : 1,
			)
		);
		
		$form_id = isset( $_GET['form_id'] ) ? $_GET['form_id'] : '';
		$user_emails = $this->get_all_email_fields_by_form_id( $form_id );

		everest_forms_panel_field(
			'select',
			'settings[email]',
			'evf_to_user_email',
			$this->form_data,
			__( 'Send Email To', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_to_user_email'] ) ? $this->form_setting['email']['evf_to_user_email'] : '',
				'options' => $user_emails
			)
		);
		everest_forms_panel_field(
			'text',
			'settings[email]',
			'evf_user_email_subject',
			$this->form_data,
			__( 'Confirmation Email Subject', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_user_email_subject'] ) ? $this->form_setting['email']['evf_user_email_subject'] : __( 'Thank You!', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'tinymce',
			'settings[email]',
			'evf_user_email_message',
			$this->form_data,
			__( 'Confirmation Email Message', 'everest-forms' ),
			array(
				'default' => isset( $this->form_setting['email']['evf_user_email_message'] ) ? $this->form_setting['email']['evf_user_email_message'] :  __('Thanks for contacting us! We will be in touch with you shortly','everest-forms'),
			)
		);
		do_action( 'everest_forms_email_settings', $this );

		echo '</div>';

		do_action( 'everest_forms_settings_panel_content', $this );
	}

	public function get_all_email_fields_by_form_id( $form_id ) {
		
		$user_emails = array();

		$form_obj  = EVF()->form->get( $form_id );

		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $form_fields ) {
				if( $form_fields['type'] === 'email' ) {
					$user_emails[$form_fields['meta-key']] = $form_fields['label'];
				}
			}
		}

		return $user_emails;
	}

	public function evf_get_all_pages(){
		foreach(get_pages() as $page){
			$pages[$page->ID] = $page->post_title; ;
		}
		return $pages;
	}
}

new EVF_Settings_Panel;
