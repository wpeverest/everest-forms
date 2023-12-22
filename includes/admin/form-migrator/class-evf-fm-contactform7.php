<?php
/**
 * EverestForms Form Migrator ContactForm7 Class
 *
 * @package EverestForms\Admin
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Contactform7 class.
 */
class EVF_Fm_Contactform7 extends EVF_Admin_Form_Migrator {
	/**
	 * Define required properties.
	 *
	 * @since 2.0.6
	 */
	public function init() {

		$this->name = 'Contact Form 7';
		$this->slug = 'contact-form-7';
		$this->path = 'contact-form-7/wp-contact-form-7.php';
	}

	/**
	 * Get all the forms.
	 *
	 * @since 2.0.6
	 */
	public function get_forms() {

		$forms_final = array();

		if ( ! $this->is_active() ) {
			return $forms_final;
		}

		$forms = \WPCF7_ContactForm::find( array( 'posts_per_page' => - 1 ) );

		if ( empty( $forms ) ) {
			return $forms_final;
		}

		foreach ( $forms as $form ) {
			if ( ! empty( $form ) && ( $form instanceof \WPCF7_ContactForm ) ) {
				$forms_final[ $form->id() ] = $form->title();
			}
		}

		return $forms_final;
	}

	/**
	 * Get a single form.
	 *
	 * @since 2.0.6
	 *
	 * @param int $id Form ID.
	 *
	 * @return \WPCF7_ContactForm|bool
	 */
	public function get_form( $id ) {

		$form = \WPCF7_ContactForm::find(
			array(
				'posts_per_page' => 1,
				'p'              => $id,
			)
		);

		if ( ! empty( $form[0] ) && ( $form[0] instanceof \WPCF7_ContactForm ) ) {
			return $form[0];
		}

		return false;
	}

	/**
	 * Sync the form datas.
	 *
	 * @since 2.0.6
	 * @param [array] $cf7_form_ids
	 */
	public function get_form_sync_data( $cf7_form_ids ) {
		$cf7_forms_data = array();
		foreach ( $cf7_form_ids as $cf7_form_id ) {
			$cf7_form = $this->get_form( $cf7_form_id );

			if ( ! $cf7_form ) {
				$cf7_forms_data[ $cf7_form_id ] = $cf7_form;
				continue;
			}

			$cf7_form_name  = $cf7_form->title();
			$cf7_fields     = $cf7_form->scan_form_tags();
			$cf7_properties = $cf7_form->get_properties();

			$form = array(
				'id'                                 => '',
				'form_enabled'                       => '',
				'form_field_id'                      => '',
				'form_fields'                        => '',
				'settings'                           => '',
				'form_title'                         => $cf7_form_name,
				'form_description'                   => '',
				'form_disable_message'               => esc_html__( 'This form is disabled.', 'everest-forms' ),
				'successful_form_submission_message' => esc_html__( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms' ),
				'submission_message_scroll'          => '1',
				'redirect_to'                        => 'same',
				'custom_page'                        => '',
				'external_url'                       => '',
				'enable_redirect_query_string'       => 0,
				'query_string'                       => '',
				'layout_class'                       => 'default',
				'form_class'                         => '',
				'submit_button_text'                 => esc_html__( 'Submit', 'everest-forms' ),
				'submit_button_processing_text'      => esc_html__( 'Processing\u2026', 'everest-forms' ),
				'submit_button_class'                => '',
				'ajax_form_submission'               => '0',
				'disabled_entries'                   => '0',
				'honeypot'                           => '1',
				'akismet'                            => '0',
				'akismet_protection_type'            => 'validation_failed',
				'recaptcha_support'                  => '0',
				'evf-enable-custom-css'              => '0',
				'evf-custom-css'                     => '',
				'evf-enable-custom-js'               => '0',
				'evf-custom-js'                      => '',
				'structure'                          => '',
			);

			// Settings.
			$form['settings'] = array(
				'email' => array(
					'connection_1' => array(
						'enable_email_notification' => '1',
						'connection_name'           => esc_html__( 'Admin Notification', 'everest-forms' ),
						'evf_to_email'              => '{admin_email}',
						'evf_from_name'             => esc_html__( 'Everest Forms', 'everest-forms' ),
						'evf_from_email'            => '{admin_email}',
						'evf_reply_to'              => '',
						'evf_email_subject'         => sprintf( '%s - %s', esc_html__( 'New Form Entry', 'everest-forms' ), esc_attr( $cf7_form_name ) ),
						'evf_email_message'         => '{all_fields}',
					),
				),
			);
		}
		return $cf7_forms_data;
	}
}

new EVF_Fm_Contactform7();
