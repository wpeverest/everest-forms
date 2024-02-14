<?php
/**
 * EverestForms Form Migrator WPforms Class
 *
 * @package EverestForms\Admin
 * @since   2.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Wpforms class.
 */
class EVF_Fm_Wpforms extends EVF_Admin_Form_Migrator {
	/**
	 * Define required properties.
	 *
	 * @since 2.0.6
	 */
	public function init() {

		$this->name = 'WPForms';
		$this->slug = 'wpforms';
		$this->path = 'wpforms-lite/wpforms.php';
	}

	/**
	 * Get all the forms.
	 *
	 * @since 2.0.6
	 */
	public function get_forms() {

		$required_form_arr = array();
		if ( function_exists( 'wpforms' ) ) {
			$forms = wpforms()->form->get( '' );
			if ( empty( $forms ) ) {
				return $required_form_arr;
			}
			foreach ( $forms as $form ) {
				if ( empty( $form ) ) {
					continue;
				}
				$required_form_arr[ $form->ID ] = $form->post_title;
			}
		}
		return $required_form_arr;
	}

	/**
	 * Get a single form.
	 *
	 * @since 2.0.6
	 *
	 * @param int $id Form ID.
	 *
	 * @return object|bool
	 */
	public function get_form( $id ) {
		$forms = wpforms()->form->get( $id );

		return $forms;
	}
	/**
	 * Mapped the form datas.
	 *
	 * @since 2.0.6
	 * @param [array] $wpf_form_ids
	 */
	public function get_fm_mapped_form_data( $wpf_form_ids ) {
		$wpf_forms_data = array();
		foreach ( $wpf_form_ids as $wpf_form_id ) {
			$wpf_form = $this->get_form( $wpf_form_id );
			if ( ! $wpf_form ) {
				$wpf_forms_data[ $wpf_form_id ] = $wpf_form;
				continue;
			}
			$wpf_form_name         = $wpf_form->post_title;
			$wpf_form_post_content = json_decode( $wpf_form->post_content, true );
			error_log( print_r( $wpf_form_post_content, true ) );
			exit;
			$wpf_fields         = isset( $wpf_form_post_content['fields'] ) ? $wpf_form_post_content['fields'] : '';
			$wpf_settings       = isset( $wpf_form_post_content['settings'] ) ? $wpf_form_post_content['settings'] : '';
			$fields_pro_plan    = array( 'number-slider' );
			$fields_pro_omit    = array();
			$fields_unsupported = array();
			$upgrade_plan       = array();
			$upgrade_omit       = array();
			$unsupported        = array();

			$form = array(
				'id'            => '',
				'form_enabled'  => '1',
				'form_field_id' => '',
				'form_fields'   => array(),
				'settings'      => array(),
			);
			// Settings.
			$form['settings'] = array(
				'email'                              => array(
					'connection_1' => array(
						'enable_email_notification' => $wpf_settings['notification_enable'],
						'connection_name'           => esc_html__( 'Admin Notification', 'everest-forms' ),
						'evf_to_email'              => $wpf_settings['notifications'][1]['email'],
						'evf_from_name'             => $wpf_settings['notifications'][1]['sender_name'],
						'evf_from_email'            => $wpf_settings['notifications'][1]['sender_address'],
						'evf_reply_to'              => $wpf_settings['notifications'][1]['replyto'],
						'evf_email_subject'         => $wpf_settings['notifications'][1]['subject'],
						'evf_email_message'         => $wpf_settings['notifications'][1]['message'],
					),
				),
				'form_title'                         => $wpf_settings['form_title'],
				'form_description'                   => $wpf_settings['form_desc'],
				'form_disable_message'               => esc_html__( 'This form is disabled.', 'everest-forms' ),
				'successful_form_submission_message' => $wpf_settings['confirmations'][1]['message'],
				'submission_message_scroll'          => $wpf_settings['confirmations'][1]['message_scroll'],
				'redirect_to'                        => 'message' === $wpf_settings['confirmations'][1]['type'] ? 'same' : $wpf_settings['confirmations'][1]['type'],
				'custom_page'                        => $wpf_settings['confirmations'][1]['page'],
				'external_url'                       => $wpf_settings['confirmations'][1]['redirect'],
				'enable_redirect_query_string'       => 0,
				'query_string'                       => '',
				'layout_class'                       => 'default',
				'form_class'                         => $wpf_settings['form_class'],
				'submit_button_text'                 => $wpf_settings['submit_text'],
				'submit_button_processing_text'      => $wpf_settings['submit_text_processing'],
				'submit_button_class'                => $wpf_settings['submit_class'],
				'ajax_form_submission'               => $wpf_settings['ajax_submit'],
				'disabled_entries'                   => isset( $wpf_settings['store_spam_entries'] ) ? $wpf_settings['store_spam_entries'] : '0',
				'honeypot'                           => '1',
				'akismet'                            => isset( $wpf_settings['akismet'] ) ? $wpf_settings['akismet'] : '0',
				'akismet_protection_type'            => 'validation_failed',
				'recaptcha_support'                  => isset( $wpf_settings['recaptcha'] ) ? $wpf_settings['recaptcha'] : '0',
				'evf-enable-custom-css'              => '0',
				'evf-custom-css'                     => '',
				'evf-enable-custom-js'               => '0',
				'evf-custom-js'                      => '',
				'structure'                          => array(),
				'imported_from'                      => array(
					'form_id'   => absint( $wpf_form_id ),
					'form_from' => $this->slug,
				),
			);

			// Mapping Fields.
			if ( empty( $wpf_fields ) ) {
				// If form does not contain fields, bail.
				wp_send_json_error(
					array(
						'form_name' => sanitize_text_field( $wpf_form_name ),
						'message'   => esc_html__( 'No form fields found.', 'everest-forms' ),
					)
				);
			}
			// Convert fields.
			foreach ( $wpf_fields as $wpf_field ) {

				// Next, check if field is unsupported. If supported make note and
				// then continue to the next field.
				if ( in_array( $wpf_field['type'], $fields_unsupported, true ) ) {
					$unsupported[] = $wpf_field['label'];

					continue;
				}
				if ( ! defined( 'EFP_VERSION' ) && '1.7.1' <= 'EFP_VERSION' && in_array( $wpf_field['type'], $fields_pro_plan, true ) ) {
					$upgrade_plan[] = $wpf_field['label'];
				}
				if ( ! defined( 'EFP_VERSION' ) && '1.7.1' <= 'EFP_VERSION' && in_array( $wpf_field['type'], $fields_pro_omit, true ) ) {
					$upgrade_omit[] = $wpf_field['label'];

					continue;
				}

				// Calculating the field ids and storing next field id.
				if ( ! empty( $form['form_field_id'] ) ) {
					$form_field_id = absint( $form['form_field_id'] );
					++$form['form_field_id'];
				} else {
					$form_field_id         = '0';
					$form['form_field_id'] = '1';
				}

				$field_id = evf_get_random_string() . '-' . $form_field_id;
				// Mapping the field type and formtting the fields settings.
				switch ( $wpf_field['type'] ) {
					case 'text':
					case 'textarea':
						$type                                   = $wpf_field['type'];
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $wpf_field['label'],
							'meta-key'               => $field_id,
							'description'            => $wpf_field['description'],
							'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $wpf_field['placeholder'],
							'limit_count'            => '1',
							'limit_mode'             => 'characters',
							'min_length_count'       => $wpf_field['limit_count'],
							'min_length_mode'        => $wpf_field['limit_mode'],
							'default_value'          => $wpf_field['default_value'],
							'css'                    => $wpf_field['css'],
							'input_mask'             => isset( $wpf_field['input_mask'] ) ? $wpf_field['input_mask'] : '',
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'cf7_name'               => $wpf_field['id'],
						);
						break;
				}
			}

			$response = $this->import_form( $form, $unsupported, $upgrade_plan, $upgrade_omit );

			$wpf_forms_data[ $wpf_form_id ] = $response;
		}
		return $wpf_forms_data;
	}
}

new EVF_Fm_Wpforms();
