<?php
/**
 * EverestForms Builder Settings
 *
 * @package EverestForms\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Builder_Settings', false ) ) {
	return new EVF_Builder_Settings();
}

/**
 * EVF_Builder_Settings class.
 */
class EVF_Builder_Settings extends EVF_Admin_Form_Panel {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'settings';
		$this->icon  = 'evf-icon evf-icon-setting';
		$this->label = __( 'Settings', 'everest-forms' );

		parent::__construct();
	}

	/**
	 * All systems go.
	 *
	 * @since      1.0.0
	 */
	public function init() {

		// Define panel information
		$this->name    = __( 'Settings', 'everest-forms' );
		$this->slug    = 'settings';
		$this->icon    = 'evf-icon evf-icon-setting';
		$this->order   = 10;
		$this->sidebar = true;
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
		$form_id     = isset( $_GET['form_id'] ) ? $_GET['form_id'] : '';
		$user_emails = $this->get_all_email_fields_by_form_id( $form_id );

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

		if ( ! empty( $user_emails ) ) {
			everest_forms_panel_field(
				'checkbox',
				'settings[email]',
				'evf_send_confirmation_email',
				$this->form_data,
				sprintf( __( 'Send Confirmation Email To User', 'everest-forms' )),
				array(
					'default' =>  isset( $this->form_setting['email']['evf_send_confirmation_email'] ) ? $this->form_setting['email']['evf_send_confirmation_email'] : 1,
				)
			);
			everest_forms_panel_field(
				'select',
				'settings[email]',
				'evf_user_to_email',
				$this->form_data,
				__( 'Send Confirmation Email To', 'everest-forms' ),
				array(
					'default' => isset( $this->form_setting['email']['evf_user_to_email'] ) ? $this->form_setting['email']['evf_user_to_email'] : '',
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
					'default' => isset( $this->form_setting['email']['evf_user_email_message'] ) ? $this->form_setting['email']['evf_user_email_message'] :  __('Thanks for contacting us! We will be in touch with you shortly.','everest-forms'),
				)
			);
		}


		do_action( 'everest_forms_inline_email_settings', $this );

		echo '</div>';

		do_action( 'everest_forms_settings_panel_content', $this );
	}

	public function get_all_email_fields_by_form_id( $form_id ) {
		$user_emails = array();
		$form_obj    = EVF()->form->get( $form_id );
		$form_data   = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $form_fields ) {
				if ( 'email' === $form_fields['type'] ) {
					$user_emails[ $form_fields['meta-key'] ] = $form_fields['label'];
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

return new EVF_Panel_Settings();
