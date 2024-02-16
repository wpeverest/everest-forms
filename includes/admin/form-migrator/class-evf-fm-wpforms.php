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
	 * Importer plugin pro path.
	 *
	 * @since 2.0.6
	 *
	 * @var string
	 */
	public $pro_path;

	/**
	 * Primary class constructor.
	 *
	 * @since 2.0.6
	 */
	/**
	 * Define required properties.
	 *
	 * @since 2.0.6
	 */
	public function init() {

		$this->name     = 'WPForms';
		$this->slug     = 'wpforms';
		$this->path     = 'wpforms-lite/wpforms.php';
		$this->pro_path = 'wpforms/wpforms.php';
	}

	/**
	 * If the importer source is available.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	protected function is_active() {
		return is_plugin_active( $this->path ) || is_plugin_active( $this->pro_path );
	}

	/**
	 *  Check is the plugin installed or not.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	protected function is_installed() {
		return file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path ) || file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->pro_path );
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
		if ( ! function_exists( 'wpforms' ) ) {
			return false;
		}

		$forms = wpforms()->form->get( $id );

		return $forms;
	}
	/**
	 * Mapping the form setting.
	 *
	 * @since 2.0.6
	 * @param [array] $form The form data.
	 * @param [aray]  $wpf_settings The wpforms form settings.
	 * @param [int]   $wpf_form_id The wpforms ID.
	 */
	private function get_form_settings( $form, $wpf_settings, $wpf_form_id ) {
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

		return $form;
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
			// error_log( print_r( $wpf_form_post_content, true ) );
			// exit;
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
			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_settings_mapping', $this->get_form_settings( $form, $wpf_settings, $wpf_form_id ), $wpf_form_id, $wpf_form );

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
						$type = $wpf_field['type'];
						$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]                                 = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $wpf_field['label'],
							'meta-key'               => $wpf_field['id'],
							'description'            => $wpf_field['description'],
							'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $wpf_field['placeholder'],
							'label_hide'             => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
							'limit_enabled'          => isset( $wpf_field['limit_enabled'] ) ? $wpf_field['limit_enabled'] : '0',
							'limit_count'            => isset( $wpf_field['limit_count'] ) ? $wpf_field['limit_count'] : '1',
							'limit_mode'             => $wpf_field['limit_mode'],
							'min_length_count'       => '1',
							'min_length_mode'        => 'characters',
							'default_value'          => $wpf_field['default_value'],
							'css'                    => $wpf_field['css'],
							'input_mask'             => isset( $wpf_field['input_mask'] ) ? $wpf_field['input_mask'] : '',
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'wpf_name'               => $wpf_field['id'],
						);
						break;
					case 'name':
						$name_format       = array_map( 'trim', explode( '-', $wpf_field['format'] ) );
						$name_format_count = count( $name_format );
						$row_num           = $form['form_field_id'];

						foreach ( $name_format as $index => $format ) {
							if ( 'simple' === $format || 'first' === $format || 'middle' === $format ) {
								$type  = 'first-name';
								$label = ucfirst( $format ) . ' ' . $wpf_field['label'];
								if ( 'middle' === $format ) {
									$form['structure'][ 'row_' . $row_num ]['grid_2'][] = $field_id;
								} else {
									$form['structure'][ 'row_' . $row_num ]['grid_1'][] = $field_id;
								}
							} elseif ( 'last' === $format ) {
								$type  = 'last-name';
								$label = ucfirst( $format ) . ' ' . $wpf_field['label'];
								if ( in_array( 'middle', $name_format, true ) ) {
									$form['structure'][ 'row_' . $row_num ]['grid_3'][] = $field_id;
								} else {
									$form['structure'][ 'row_' . $row_num ]['grid_2'][] = $field_id;
								}
							}

							$form['form_fields'][ $field_id ] = array(
								'id'                     => $field_id,
								'type'                   => $type,
								'label'                  => $label,
								'meta-key'               => $format . '_' . $wpf_field['id'],
								'description'            => $wpf_field['description'],
								'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
								'required_field_message_setting' => 'global',
								'required-field-message' => '',
								'placeholder'            => isset( $wpf_field[ $format . '_placeholder' ] ) ? $wpf_field[ $format . '_placeholder' ] : '',
								'label_hide'             => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
								'default_value'          => isset( $wpf_field[ $format . '_default' ] ) ? $wpf_field[ $format . '_default' ] : '',
								'css'                    => $wpf_field['css'],
								'regex_value'            => '',
								'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
								'wpf_name'               => $wpf_field['id'],
							);

							if ( ( $index + 1 ) < $name_format_count ) {
								// Calculating field id.
								$form_field_id = absint( $form['form_field_id'] );
								++$form['form_field_id'];
								$field_id = evf_get_random_string() . '-' . $form_field_id;
							}
						}
						break;
					case 'email':
						$type = $wpf_field['type'];
						$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]                                 = array(
							'id'                       => $field_id,
							'type'                     => $type,
							'label'                    => $wpf_field['label'],
							'meta-key'                 => $wpf_field['id'],
							'description'              => $wpf_field['description'],
							'required'                 => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
							'required_field_message_setting' => 'global',
							'required-field-message'   => '',
							'placeholder'              => $wpf_field['placeholder'],
							'confirmation_placeholder' => $wpf_field['confirmation_placeholder'],
							'label_hide'               => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
							'default_value'            => $wpf_field['default_value'],
							'css'                      => $wpf_field['css'],
							'regex_value'              => '',
							'regex_message'            => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'wpf_name'                 => $wpf_field['id'],
						);
						break;
					case 'select':
					case 'radio':
					case 'checkbox':
						$type = $wpf_field['type'];
						$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
						$evf_choices = array();
						if ( isset( $wpf_field['choices'] ) && ! empty( $wpf_field['choices'] ) ) {
							foreach ( $wpf_field['choices'] as $choice ) {
								$evf_choice = array(
									'label' => $choice['label'],
									'value' => $choice['value'],
									'image' => $choice['image'],
								);
								if ( isset( $choice['default'] ) ) {
									$evf_choice['default'] = $choice['default'];
								}
								$evf_choices[] = $evf_choice;
							}
						}

						$form['form_fields'][ $field_id ] = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $wpf_field['label'],
							'meta-key'               => $wpf_field['id'],
							'choices'                => $evf_choices,
							'description'            => $wpf_field['description'],
							'label_hide'             => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
							'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'css'                    => $wpf_field['css'],
							'wpf_name'               => $wpf_field['id'],
						);

						if ( 'select' === $type ) {
							if ( isset( $wpf_field['multiple'] ) ) {
								$form['form_fields'][ $field_id ]['multiple_choices'] = $wpf_field['multiple'];
							}
							$form['form_fields'][ $field_id ]['placeholder'] = $wpf_field['placeholder'];
						}
						if ( 'radio' === $type || 'checkbox' === $type ) {
							$form['form_fields'][ $field_id ]['input_columns'] = '';
							if ( isset( $wpf_field['choices_images'] ) ) {
								$form['form_fields'][ $field_id ]['choices_images'] = $wpf_field['choices_images'];
							}
						}

						if ( 'checkbox' === $type ) {
							$form['form_fields'][ $field_id ]['choice_limit'] = '';
						}
						break;
					case 'number':
						$type = $wpf_field['type'];
						$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]                                 = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $wpf_field['label'],
							'meta-key'               => $wpf_field['id'],
							'description'            => $wpf_field['description'],
							'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'step'                   => '0',
							'min_value'              => '',
							'max_value'              => '',
							'placeholder'            => $wpf_field['placeholder'],
							'label_hide'             => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
							'default_value'          => $wpf_field['default_value'],
							'css'                    => $wpf_field['css'],
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'wpf_name'               => $wpf_field['id'],
						);
						break;
					case 'number-slider':
						$type = 'range-slider';
						$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]                                 = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $wpf_field['label'],
							'meta-key'               => $wpf_field['id'],
							'description'            => $wpf_field['description'],
							'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'step'                   => $wpf_field['step'],
							'min_value'              => $wpf_field['min'],
							'max_value'              => $wpf_field['max'],
							'placeholder'            => '',
							'label_hide'             => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
							'default_value'          => $wpf_field['default_value'],
							'css'                    => $wpf_field['css'],
							'skin'                   => '',
							'handle_color'           => '',
							'highlight_color'        => '',
							'track_color'            => '',
							'prefix_text'            => '',
							'show_slider_input'      => '1',
							'wpf_name'               => $wpf_field['id'],
						);
						break;
					case 'date-time':
						$type = $wpf_field['type'];
						$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]                                 = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $wpf_field['label'],
							'meta-key'               => $wpf_field['id'],
							'datetime_format'        => $wpf_field['format'],
							'datetime_style'         => 'datepicker' === $wpf_field['date_type'] ? 'picker' : $wpf_field['date_type'],
							'description'            => $wpf_field['description'],
							'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $wpf_field['date_placeholder'],
							'label_hide'             => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
							'css'                    => $wpf_field['css'],
							'date_format'            => $wpf_field['date_format'],
							'disable_dates'          => isset( $wpf_field['date_disable_past_dates'] ) ? $wpf_field['date_disable_past_dates'] : '',
							'date_localization'      => 'en',
							'date_timezone'          => 'Default',
							'date_mode'              => 'single',
							'min_date'               => '',
							'max_date'               => '',
							'min_date_range'         => '',
							'max_date_range'         => '',
							'time_interval'          => $wpf_field['time_interval'],
							'time_format'            => $wpf_field['time_format'],
							'min_time_hour'          => $wpf_field['time_limit_hours_start_hour'],
							'min_time_minute'        => $wpf_field['time_limit_hours_start_min'],
							'max_time_hour'          => $wpf_field['time_limit_hours_end_hour'],
							'max_time_minute'        => $wpf_field['time_limit_hours_end_min'],
							'wpf_name'               => $wpf_field['id'],
						);
						break;
					case 'url':
						$type = $wpf_field['type'];
						$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]                                 = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $wpf_field['label'],
							'meta-key'               => $wpf_field['id'],
							'description'            => $wpf_field['description'],
							'required'               => isset( $wpf_field['required'] ) ? $wpf_field['required'] : '0',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $wpf_field['placeholder'],
							'label_hide'             => isset( $wpf_field['label_hide'] ) ? $wpf_field['label_hide'] : '0',
							'default_value'          => $wpf_field['default_value'],
							'css'                    => $wpf_field['css'],
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'wpf_name'               => $wpf_field['id'],
						);
						break;
					default:
						break;
				}
			}
			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_fields_mapping', $form, $wpf_form_id, $wpf_form );

			$response = $this->import_form( $form, $unsupported, $upgrade_plan, $upgrade_omit );

			$wpf_forms_data[ $wpf_form_id ] = $response;
		}
		return $wpf_forms_data;
	}
}

new EVF_Fm_Wpforms();
