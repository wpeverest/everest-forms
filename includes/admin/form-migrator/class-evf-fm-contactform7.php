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
	 * If the importer source is available.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	protected function is_active() {

		return is_plugin_active( $this->path );
	}
	/**
	 *  Check is the plugin installed or not.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	protected function is_installed() {

		return file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path );
	}


	/**
	 * Get all the forms.
	 *
	 * @since 2.0.6
	 */
	public function get_forms() {

		$required_form_arr = array();

		if ( ! $this->is_active() ) {
			return $required_form_arr;
		}

		$forms = \WPCF7_ContactForm::find( array( 'posts_per_page' => - 1 ) );

		if ( empty( $forms ) ) {
			return $required_form_arr;
		}

		foreach ( $forms as $form ) {
			if ( ! empty( $form ) && ( $form instanceof \WPCF7_ContactForm ) ) {
				$required_form_arr[ $form->id() ] = $form->title();
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
	 * Get the field label.
	 *
	 * @since 2.0.6
	 *
	 * @param string $form Form data and settings.
	 * @param string $type Field type.
	 * @param string $name Field name.
	 *
	 * @return string
	 */
	private function get_field_label( $form, $type, $name = '' ) {

		preg_match_all( '/<label>([ \w\S\r\n\t]+?)<\/label>/', $form, $matches );

		foreach ( $matches[1] as $match ) {
			$match = trim( str_replace( "\n", '', $match ) );

			preg_match( '/\[(?:' . preg_quote( $type ) . ') ' . $name . '(?:[ ](.*?))?(?:[\r\n\t ](\/))?\]/', $match, $input_match );

			if ( ! empty( $input_match[0] ) ) {
				return strip_shortcodes( sanitize_text_field( str_replace( $input_match[0], '', $match ) ) );
			}
		}

		$label = sprintf( /* translators: %1$s - field type, %2$s - field name if available. */
			esc_html__( '%1$s Field %2$s', 'everest-forms' ),
			ucfirst( $type ),
			! empty( $name ) ? "($name)" : ''
		);

		return trim( $label );
	}
	/**
	 * Get the field acceptance label.
	 *
	 * @since 2.0.6
	 *
	 * @param string $form Form data and settings.
	 * @param string $name Field name.
	 *
	 * @return string
	 */
	private function get_field_acceptance_label( $form, $name ) {
		$pattern = '/\[acceptance(?:[^]]* ' . preg_quote( $name ) . '[^]]*)?\](.*?)\[\/acceptance\]/s';

		preg_match_all( $pattern, $form, $matches );

		foreach ( $matches[1] as $match ) {
			return strip_shortcodes( sanitize_text_field( $match ) );
		}

		return '';
	}

	/**
	 * Extracts question-answer pairs from a [form] based on a specified name attribute.
	 *
	 * @since 2.0.6
	 *
	 * @param string $form The input string containing the [form].
	 * @param string $name The name attribute to match within the [form].
	 *
	 * @return array An array containing question-answer pairs.
	 */
	private function get_quiz_questions_and_answers( $form, $name = '' ) {
		$pattern = '/\[quiz ' . preg_quote( $name ) . '(.*?)\]/s';

		preg_match_all( $pattern, $form, $matches );

		$qa_pairs = array();

		// If there is a match, extract question-answer pairs
		if ( ! empty( $matches[1] ) ) {
			preg_match_all( '/"([^"]+?)\|([^"]+?)"/', $matches[1][0], $pairs, PREG_SET_ORDER );

			foreach ( $pairs as $pair ) {
				$question = strip_shortcodes( sanitize_text_field( $pair[1] ) );
				$answer   = strip_shortcodes( sanitize_text_field( $pair[2] ) );

				$qa_pairs[] = array(
					'question' => $question,
					'answer'   => $answer,
				);
			}
		}

		return $qa_pairs;
	}

	/**
	 * Lookup and return the placeholder or default value.
	 *
	 * @since 2.0.6
	 *
	 * @param object $field Field object.
	 * @param string $type  Type of the field.
	 *
	 * @return string
	 */
	private function get_field_placeholder_default( $field, $type = 'placeholder' ) {

		$placeholder   = '';
		$default_value = (string) reset( $field->values );

		if ( $field->has_option( 'placeholder' ) || $field->has_option( 'watermark' ) ) {
			$placeholder   = $default_value;
			$default_value = '';
		}

		if ( $type === 'placeholder' ) {
			return $placeholder;
		}

		return $default_value;
	}

	/**
	 * Replace 3rd-party form provider tags/shortcodes with our own Smart Tags.
	 *
	 * @since 2.0.6
	 *
	 * @param string $string Text to look for Smart Tags in.
	 * @param array  $fields List of fields to process Smart Tags in.
	 *
	 * @return string
	 */
	private function get_smarttags( $string, $fields ) {

		preg_match_all( '/\[(.+?)\]/', $string, $tags );

		if ( empty( $tags[1] ) ) {
			return $string;
		}

		// Process form-tags and mail-tags.
		foreach ( $tags[1] as $tag ) {
			foreach ( $fields as $field ) {
				if ( ! empty( $field['cf7_name'] ) && $field['cf7_name'] === $tag ) {
					$field_id = $this->get_field_id_for_smarttags( $field );
					$string   = str_replace( '[' . $tag . ']', '{field_id="' . $field_id . '"}', $string );
				}
			}
		}

		// Process CF7 tags that we can map with EVF alternatives.
		$string = str_replace(
			array(
				'[_remote_ip]',
				'[_date]',
				'[time]',
				'[_post_title]',
				'[_post_url]',
				'[_url]',
				'[_post_author]',
				'[_post_author_email]',
				'[_site_admin_email]',
				'[_user_email]',
				'[_user_login]',
				'[_user_first_name]',
				'[_user_last_name]',
				'[_user_display_name]',
				'[_site_title]',
				'[_post_name]',
			),
			array(
				'{user_ip_address}',
				'{current_date}',
				'{current_time}',
				'{post_title}',
				'{page_url}',
				'{page_url}',
				'{author_name}',
				'{author_email}',
				'{admin_email}',
				'{user_email}',
				'{user_name}',
				'{first_name}',
				'{last_name}',
				'{display_name}',
				'{site_name}',
				'{form_name}',
			),
			$string
		);

		// Replace those CF7 that are used in Notifications by default and that we can't leave empty.
		$string = str_replace(
			array(
				'[_site_description]',
				'[_site_url]',
			),
			array(
				get_bloginfo( 'description' ),
				get_bloginfo( 'url' ),
			),
			$string
		);

		/*
		 * We are not replacing certain special CF7 tags: [_user_url], [_user_agent], [_invalid_fields], [_serial_number]
		 * [_post_id].
		 * Without them some logic may be broken and for user it will be harder to stop missing strings.
		 * With them - they can see strange text and will be able to understand, based on the tag name, which value is expected there.
		 */

		return $string;
	}
	/**
	 * Find Reply-To in headers if provided.
	 *
	 * @since 2.0.6
	 *
	 * @param string $headers CF7 email headers.
	 * @param array  $fields  List of fields.
	 *
	 * @return string
	 */
	private function get_replyto( $headers, $fields ) {

		if ( strpos( $headers, 'Reply-To:' ) !== false ) {
			preg_match( '/Reply-To: \[(.+?)\]/', $headers, $tag );

			if ( ! empty( $tag[1] ) ) {
				foreach ( $fields as $field ) {
					if ( ! empty( $field['cf7_name'] ) && $field['cf7_name'] === $tag[1] ) {
						$field_id = $this->get_field_id_for_smarttags( $field );
						return '{field_id="' . $field_id . '"}';
					}
				}
			}
		}

		return '';
	}

	/**
	 * Sender information.
	 *
	 * @since 2.0.6
	 *
	 * @param string $sender Sender strings in "Name <email@example.com>" format.
	 * @param array  $fields List of fields.
	 *
	 * @return bool|array
	 */
	private function get_sender_details( $sender, $fields ) {

		preg_match( '/(.+?)\<(.+?)\>/', $sender, $tag );

		if ( ! empty( $tag[1] ) && ! empty( $tag[2] ) ) {
			return array(
				'name'    => $this->get_smarttags( $tag[1], $fields ),
				'address' => $this->get_smarttags( $tag[2], $fields ),
			);
		}

		return false;
	}

	/**
	 * Email notification settings.
	 *
	 * @since 2.0.6
	 * @param [array]  $form The form.
	 * @param [object] $cf7_form The contact form 7 form.
	 */
	private function get_email_notification_settings( $form, $cf7_form ) {
		$cf7_form_name         = $cf7_form->title();
		$notification_settings = array(
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
		);

		return $notification_settings;
	}

	/**
	 * Mapping the form setting.
	 *
	 * @since 2.0.6
	 * @param [array]  $form The form data.
	 * @param [object] $cf7_form The wpforms form settings.
	 * @param [int]    $cf7_form_id The wpforms ID.
	 */
	private function get_form_settings( $form, $cf7_form, $cf7_form_id ) {
		$cf7_form_name    = $cf7_form->title();
		$form['settings'] = array(
			'email'                              => apply_filters( 'evf_fm_' . $this->slug . 'email_notification_settings', $this->get_email_notification_settings( $form, $cf7_form ), $form, $cf7_form ),
			'form_title'                         => sanitize_text_field( $cf7_form_name ),
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
			'submit_button_processing_text'      => esc_html__( 'Processing', 'everest-forms' ),
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
			'structure'                          => array(),
			'imported_from'                      => array(
				'form_id'   => absint( $cf7_form_id ),
				'form_from' => $this->slug,
			),
		);

		return $form;
	}
	/**
	 * Mapped the form datas.
	 *
	 * @since 2.0.6
	 * @param [array] $cf7_form_ids
	 */
	public function get_fm_mapped_form_data( $cf7_form_ids ) {
		$cf7_forms_data = array();
		foreach ( $cf7_form_ids as $cf7_form_id ) {
			$cf7_form = $this->get_form( $cf7_form_id );

			if ( ! $cf7_form ) {
				$cf7_forms_data[ $cf7_form_id ] = $cf7_form;
				continue;
			}

			$cf7_form_name      = $cf7_form->title();
			$cf7_fields         = $cf7_form->scan_form_tags();
			$cf7_properties     = $cf7_form->get_properties();
			$cf7_recaptcha      = false;
			$fields_pro_plan    = array( 'tel', 'file', 'acceptance', 'quiz' );
			$fields_pro_omit    = array();
			$fields_unsupported = array( 'hidden' );
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
			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_settings_mapping', $this->get_form_settings( $form, $cf7_form, $cf7_form_id ), $cf7_form_id, $cf7_form );

			// Mapping Fields.
			if ( empty( $cf7_fields ) ) {
				// If form does not contain fields, bail.
				wp_send_json_error(
					array(
						'form_name' => sanitize_text_field( $cf7_form_name ),
						'message'   => esc_html__( 'No form fields found.', 'everest-forms' ),
					)
				);
			}
			// Convert fields.
			foreach ( $cf7_fields as $cf7_field ) {
				if ( ! $cf7_field instanceof \WPCF7_FormTag ) {
					continue;
				}

				// Try to determine field label to use.
				$label = $this->get_field_label( $cf7_properties['form'], $cf7_field->type, $cf7_field->name );

				// Next, check if field is unsupported. If supported make note and
				// then continue to the next field.
				if ( in_array( $cf7_field->basetype, $fields_unsupported, true ) ) {
					$unsupported[] = $label;

					continue;
				}
				if ( ! defined( 'EFP_VERSION' ) && '1.7.1' <= 'EFP_VERSION' && in_array( $cf7_field->basetype, $fields_pro_plan, true ) ) {
					$upgrade_plan[] = $label;
					continue;
				}
				if ( ! defined( 'EFP_VERSION' ) && '1.7.1' <= 'EFP_VERSION' && in_array( $cf7_field->basetype, $fields_pro_omit, true ) ) {
					$upgrade_omit[] = $label;

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

				$cf7_field_classes = '';
				if ( $cf7_field->has_option( 'class' ) ) {
					$cf7_field_classes = implode( ' ', $cf7_field->get_option( 'class', '', false ) );
				}

				$field_id = evf_get_random_string() . '-' . $form_field_id;
				// Mapping the field type and formtting the fields settings.
				switch ( $cf7_field->basetype ) {
					case 'text':
					case 'textarea':
						$type                                   = $cf7_field->basetype;
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'description'            => '',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $this->get_field_placeholder_default( $cf7_field ),
							'limit_count'            => '1',
							'limit_mode'             => 'characters',
							'min_length_count'       => '1',
							'min_length_mode'        => 'characters',
							'default_value'          => $this->get_field_placeholder_default( $cf7_field, 'default' ),
							'css'                    => $cf7_field_classes,
							'input_mask'             => '',
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'cf7_name'               => $cf7_field->name,
						);
						break;
					case 'email':
						$type                                   = $cf7_field->basetype;
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'description'            => '',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $this->get_field_placeholder_default( $cf7_field ),
							'default_value'          => $this->get_field_placeholder_default( $cf7_field, 'default' ),
							'css'                    => $cf7_field_classes,
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'cf7_name'               => $cf7_field->name,
						);
						break;
					case 'url':
						$type                                   = $cf7_field->basetype;
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'description'            => '',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $this->get_field_placeholder_default( $cf7_field ),
							'default_value'          => $this->get_field_placeholder_default( $cf7_field, 'default' ),
							'css'                    => $cf7_field_classes,
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'cf7_name'               => $cf7_field->name,
						);
						break;
					case 'date':
						$type                                   = 'date-time';
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'format'                 => 'date',
							'datetime_style'         => 'picker',
							'description'            => '',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $this->get_field_placeholder_default( $cf7_field ),
							'date_format'            => 'Y-m-d',
							'disable_dates'          => '',
							'date_localization'      => 'en',
							'date_timezone'          => 'Default',
							'date_mode'              => 'single',
							'min_date'               => '',
							'max_date'               => '',
							'min_date_range'         => '',
							'max_date_range'         => '',
							'time_interval'          => '15',
							'time_format'            => 'g:i A',
							'min_time_hour'          => '9',
							'min_time_minute'        => '30',
							'max_time_hour'          => '18',
							'max_time_minute'        => '30',
							'css'                    => $cf7_field_classes,
						);
						break;
					// Select, radio, and checkbox fields.
					case 'select':
					case 'radio':
					case 'checkbox':
						$type                                   = $cf7_field->basetype;
						$form['structure']['row_1']['grid_1'][] = $field_id;

						$choices = array();
						$options = (array) $cf7_field->labels;
						if ( $cf7_field->has_option( 'default' ) ) {
							$default = $cf7_field->get_option( 'default', '', true );
						} else {
							$default = '';
						}
						foreach ( $options as $key => $option ) {
							$choice = array(
								'label' => $option,
								'value' => '',
								'image' => '',
							);
							if ( in_array( (string) ( $key + 1 ), explode( '_', $default ), true ) ) {
								$choice['default'] = '1';
							}
							$choices[] = $choice;
						}

						$form['form_fields'][ $field_id ] = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'choices'                => $choices,
							'description'            => '',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'placeholder'            => $this->get_field_placeholder_default( $cf7_field ),
							'css'                    => $cf7_field_classes,
							'cf7_name'               => $cf7_field->name,
						);

						if ( 'select' === $cf7_field->basetype ) {
							if ( $cf7_field->has_option( 'multiple' ) ) {
								$form['form_fields'][ $field_id ]['multiple_choices'] = '1';
							}
						}
						if ( 'radio' === $cf7_field->basetype || 'checkbox' === $cf7_field->basetype ) {
							$form['form_fields'][ $field_id ]['input_columns'] = '';
						}

						if ( 'checkbox' === $cf7_field->basetype ) {
							$form['form_fields'][ $field_id ]['choice_limit'] = '';
						}
						break;
					case 'number':
						$type                                   = $cf7_field->basetype;
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'description'            => '',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'step'                   => $cf7_field->get_option( 'step', 'int', true ),
							'min_value'              => $cf7_field->get_option( 'min', 'signed_int', true ),
							'max_value'              => $cf7_field->get_option( 'max', 'signed_int', true ),
							'placeholder'            => $this->get_field_placeholder_default( $cf7_field ),
							'default_value'          => $this->get_field_placeholder_default( $cf7_field, 'default' ),
							'css'                    => $cf7_field_classes,
							'regex_value'            => '',
							'regex_message'          => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
							'cf7_name'               => $cf7_field->name,
						);
						break;
					case 'tel':
						$type                                   = 'phone';
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'phone_format'           => 'smart',
							'description'            => '',
							'input_mask'             => '(999) 999-9999',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'tooltip_description'    => '',
							'validate_message'       => esc_html__( 'This field value needs to be unique.', 'everst-forms' ),
							'placeholder'            => '',
							'default_value'          => '',
							'css'                    => $cf7_field_classes,
						);
						break;
					case 'file':
						$allowed_size       = 1024; // default size 1 MB
						$allowed_file_types = array();

						if ( $file_size_a = $cf7_field->get_option( 'limit' ) ) {
							$limit_pattern = '/^([1-9][0-9]*)([kKmM]?[bB])?$/';

							foreach ( $file_size_a as $file_size ) {
								if ( preg_match( $limit_pattern, $file_size, $matches ) ) {
									$allowed_size = (int) $matches[1];

									if ( ! empty( $matches[2] ) ) {
										$kbmb = strtolower( $matches[2] );

										if ( 'kb' == $kbmb ) {
											$allowed_size *= 1;
										} elseif ( 'mb' == $kbmb ) {
											$allowed_size *= 1024;
										}
									}

									break;
								}
							}
						}

						if ( $file_types_a = $cf7_field->get_option( 'filetypes' ) ) {
							foreach ( $file_types_a as $file_types ) {
								$file_types = explode( '|', $file_types );

								foreach ( $file_types as $file_type ) {
									$file_type = trim( $file_type, '.' );
									$file_type = str_replace( array( '.', '+', '*', '?' ), array( '\.', '\+', '\*', '\?' ), $file_type );

									if ( ! in_array( $file_type, $allowed_file_types ) ) {
										$allowed_file_types[] = $file_type;
									}
								}
							}
						}

						$type                                   = 'file-upload';
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'description'            => '',
							'upload_message'         => esc_html__( 'Drop your file here or click here to upload', 'everest-forms' ),
							'limit_message'          => esc_html__( 'You can upload up to 1 files.', 'everest-forms' ),
							'extensions'             => implode( ',', $allowed_file_types ),
							'max_size'               => $allowed_size,
							'max_file_number'        => '1',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'tooltip_description'    => '',
							'media_library'          => '',
							'css'                    => $cf7_field_classes,
						);
						break;
					case 'acceptance':
						$type                                   = 'privacy-policy';
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'                     => $field_id,
							'type'                   => $type,
							'label'                  => $label,
							'meta-key'               => $cf7_field->name,
							'description'            => '',
							'consent_message'        => $this->get_field_acceptance_label( $cf7_properties['form'], $cf7_field->name ),
							'add_local_page'         => '',
							'add_custom_link_label'  => '',
							'add_custom_link_url'    => '',
							'required'               => $cf7_field->is_required() ? '1' : '',
							'required_field_message_setting' => 'global',
							'required-field-message' => '',
							'tooltip_description'    => '',
							'css'                    => $cf7_field_classes,
						);
						break;
					case 'quiz':
						$type                                   = 'captcha';
						$form['structure']['row_1']['grid_1'][] = $field_id;
						$form['form_fields'][ $field_id ]       = array(
							'id'          => $field_id,
							'type'        => $type,
							'label'       => $label,
							'description' => '',
							'required'    => '1',
							'format'      => 'question',
							'questions'   => $this->get_quiz_questions_and_answers( $cf7_properties['form'], $cf7_field->name ),
							'placeholder' => '',
							'css'         => $cf7_field_classes,
						);
						break;
					// ReCAPTCHA field.
					case 'recaptcha':
						$cf7_recaptcha = true;

				}
			}

			// ReCAPTCHA.
			if ( $cf7_recaptcha ) {
				// If the user has already defined v2 reCAPTCHA keys in the EVFForms
				// settings, use those.
				$type = get_option( 'everest_forms_recaptcha_type', 'v2' );
				if ( 'v2' === $type ) {
					$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key', '' );
					$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key', '' );
					// Try to abstract keys from CF7.
					if ( empty( $site_key ) || empty( $secret_key ) ) {
						$cf7_settings = get_option( 'wpcf7' );

						if (
						! empty( $cf7_settings['recaptcha'] ) &&
						is_array( $cf7_settings['recaptcha'] )
						) {
							foreach ( $cf7_settings['recaptcha'] as $key => $val ) {
								if ( ! empty( $key ) && ! empty( $val ) ) {
									$site_key   = $key;
									$secret_key = $val;
								}
							}
							update_option( 'everest_forms_recaptcha_v2_site_key', $site_key );
							update_option( 'everest_forms_recaptcha_v2_secret_key', $secret_key );
						}
					}
				}

				// Don't enable reCAPTCHA if user had configured invisible reCAPTCHA.
				if (
				$type === 'v2' &&
				! empty( $site_key ) &&
				! empty( $secret_key )
				) {
					$form['settings']['recaptcha_support'] = '1';
				}
			}

			// Setup email notifications.
			if ( ! empty( $cf7_properties['mail']['subject'] ) ) {
				$form['settings']['email']['connection_1']['evf_email_subject'] = $this->get_smarttags( $cf7_properties['mail']['subject'], $form['form_fields'] );
			}

			if ( ! empty( $cf7_properties['mail']['recipient'] ) ) {
				$form['settings']['email']['connection_1']['evf_to_email'] = $this->get_smarttags( $cf7_properties['mail']['recipient'], $form['form_fields'] );
			}

			if ( ! empty( $cf7_properties['mail']['body'] ) ) {
				$form['settings']['email']['connection_1']['evf_email_message'] = $this->get_smarttags( $cf7_properties['mail']['body'], $form['form_fields'] );
			}

			if ( ! empty( $cf7_properties['mail']['additional_headers'] ) ) {
				$form['settings']['email']['connection_1']['evf_reply_to'] = $this->get_replyto( $cf7_properties['mail']['additional_headers'], $form['form_fields'] );
			}

			if ( ! empty( $cf7_properties['mail']['sender'] ) ) {
				$sender = $this->get_sender_details( $cf7_properties['mail']['sender'], $form['form_fields'] );

				if ( $sender ) {
					$form['settings']['email']['connection_1']['evf_from_name']  = $sender['name'];
					$form['settings']['email']['connection_1']['evf_from_email'] = $sender['address'];
				}
			}
			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_fields_mapping', $form, $cf7_form_id, $cf7_form );

			$response = $this->import_form( $form, $unsupported, $upgrade_plan, $upgrade_omit );

			$cf7_forms_data[ $cf7_form_id ] = $response;
		}
		return $cf7_forms_data;
	}
}

new EVF_Fm_Contactform7();
