<?php
/**
 * EverestForms Form Migrator Fluent Forms Class
 *
 * @package EverestForms\Admin
 * @since   3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Fluentforms class.
 */
class EVF_Fm_Fluentforms extends EVF_Admin_Form_Migrator {

	/**
	 * Importer plugin pro path.
	 *
	 * @var string
	 */
	public $pro_path;

	/**
	 * Define required properties.
	 */
	public function init() {
		$this->name     = 'Fluent Forms';
		$this->slug     = 'fluentforms';
		$this->path     = 'fluentform/fluentform.php';
		$this->pro_path = 'fluentformpro/fluentformpro.php';
	}

	/**
	 * If the importer source is available.
	 *
	 * @return bool
	 */
	protected function is_active() {
		return is_plugin_active( $this->path ) || is_plugin_active( $this->pro_path );
	}

	/**
	 * Check if the plugin is installed.
	 *
	 * @return bool
	 */
	protected function is_installed() {
		return file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path )
			|| file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->pro_path );
	}

	/**
	 * Get all the forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		global $wpdb;

		$required_form_arr = array();

		if ( ! $this->is_active() ) {
			return $required_form_arr;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$forms = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}fluentform_forms ORDER BY id DESC" );

		if ( empty( $forms ) ) {
			return $required_form_arr;
		}

		foreach ( $forms as $form ) {
			$required_form_arr[ $form->id ] = $form->title;
		}

		return $required_form_arr;
	}

	/**
	 * Get a single form.
	 *
	 * @param int $id Form ID.
	 * @return object|bool
	 */
	public function get_form( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fluentform_forms WHERE id = %d", $id ) );
	}

	/**
	 * Get form meta value by key.
	 *
	 * @param int    $form_id  Fluent Forms form ID.
	 * @param string $meta_key Meta key.
	 * @return mixed|null
	 */
	private function get_form_meta( $form_id, $meta_key ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT value FROM {$wpdb->prefix}fluentform_form_meta WHERE form_id = %d AND meta_key = %s",
				$form_id,
				$meta_key
			)
		);

		if ( null === $value ) {
			return null;
		}

		$decoded = json_decode( $value, true );
		return ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : $value;
	}

	/**
	 * Convert Fluent Forms smart tags to EVF smart tags.
	 *
	 * @param string $string The string containing smart tags.
	 * @param array  $fields Mapped EVF form fields (for field-specific tags).
	 * @return string
	 */
	private function get_smarttags( $string, $fields = array() ) {
		if ( empty( $string ) ) {
			return $string;
		}

		// Replace {inputs.field_name} → {field_id="evf-field-id"}
		preg_match_all( '/\{inputs\.([^}]+)\}/', $string, $tags );
		if ( ! empty( $tags[1] ) ) {
			foreach ( $tags[1] as $ff_name ) {
				foreach ( $fields as $field ) {
					if ( isset( $field['ff_name'] ) && $field['ff_name'] === $ff_name ) {
						$field_id = $this->get_field_id_for_smarttags( $field );
						$string   = str_replace( '{inputs.' . $ff_name . '}', '{field_id="' . $field_id . '"}', $string );
						break;
					}
				}
			}
		}

		// Replace known system smart tags.
		$string = str_replace(
			array(
				'{all_data}',
				'{wp.admin_email}',
				'{embed_post.permalink}',
				'{embed_post.title}',
				'{user.first_name}',
				'{user.last_name}',
				'{user.email}',
				'{ip}',
			),
			array(
				'{all_fields}',
				'{admin_email}',
				'{page_url}',
				'{post_title}',
				'{first_name}',
				'{last_name}',
				'{user_email}',
				'{user_ip_address}',
			),
			$string
		);

		return $string;
	}

	/**
	 * Build EVF email notification settings from Fluent Forms form meta.
	 *
	 * @param array $form       Partially built EVF form array (form_fields must be populated).
	 * @param int   $ff_form_id Fluent Forms form ID.
	 * @return array
	 */
	private function get_email_notification_settings( $form, $ff_form_id ) {
		$notifications = $this->get_form_meta( $ff_form_id, 'notifications' );

		$default = array(
			'connection_1' => array(
				'enable_email_notification' => '1',
				'connection_name'           => esc_html__( 'Admin Notification', 'everest-forms' ),
				'evf_to_email'              => '{admin_email}',
				'evf_from_name'             => get_bloginfo( 'name' ),
				'evf_from_email'            => get_option( 'admin_email' ),
				'evf_reply_to'              => '',
				'evf_email_subject'         => esc_html__( 'New Form Submission', 'everest-forms' ),
				'evf_email_message'         => '{all_fields}',
			),
		);

		if ( empty( $notifications ) || ! is_array( $notifications ) ) {
			return $default;
		}

		// Fluent Forms stores a single notification object OR an array of objects.
		if ( isset( $notifications['name'] ) ) {
			$notifications = array( $notifications );
		}

		$evf_notifications = array();
		$connection_num    = 1;

		foreach ( $notifications as $notification ) {
			if ( empty( $notification ) || ! is_array( $notification ) ) {
				continue;
			}

			// Resolve the To email address.
			$to_email = '{admin_email}';
			if ( isset( $notification['sendTo']['type'] ) ) {
				if ( 'email' === $notification['sendTo']['type'] ) {
					$raw      = isset( $notification['sendTo']['email'] ) ? $notification['sendTo']['email'] : '';
					$to_email = $this->get_smarttags( $raw, $form['form_fields'] );
				} elseif ( 'field' === $notification['sendTo']['type'] && isset( $notification['sendTo']['field'] ) ) {
					$ff_field_name = $notification['sendTo']['field'];
					foreach ( $form['form_fields'] as $evf_field ) {
						if ( isset( $evf_field['ff_name'] ) && $evf_field['ff_name'] === $ff_field_name ) {
							$to_email = '{field_id="' . $evf_field['id'] . '"}';
							break;
						}
					}
				}
			}

			$connection_key = 'connection_' . $connection_num;

			$evf_notifications[ $connection_key ] = array(
				'enable_email_notification' => '1',
				'connection_name'           => isset( $notification['name'] ) ? sanitize_text_field( $notification['name'] ) : esc_html__( 'Admin Notification', 'everest-forms' ),
				'evf_to_email'              => $to_email,
				'evf_from_name'             => $this->get_smarttags( isset( $notification['fromName'] ) ? $notification['fromName'] : '', $form['form_fields'] ),
				'evf_from_email'            => $this->get_smarttags( isset( $notification['fromEmail'] ) ? $notification['fromEmail'] : '', $form['form_fields'] ),
				'evf_reply_to'              => $this->get_smarttags( isset( $notification['replyTo'] ) ? $notification['replyTo'] : '', $form['form_fields'] ),
				'evf_email_subject'         => $this->get_smarttags( isset( $notification['subject'] ) ? $notification['subject'] : '', $form['form_fields'] ),
				'evf_email_message'         => $this->get_smarttags( isset( $notification['message'] ) ? $notification['message'] : '{all_fields}', $form['form_fields'] ),
			);

			$connection_num++;
		}

		return ! empty( $evf_notifications ) ? $evf_notifications : $default;
	}

	/**
	 * Map form-level settings (confirmation, title, etc.).
	 *
	 * @param array $form       Partially built EVF form array.
	 * @param int   $ff_form_id Fluent Forms form ID.
	 * @return array
	 */
	private function get_form_settings( $form, $ff_form_id ) {
		$form_settings = $this->get_form_meta( $ff_form_id, 'formSettings' );
		$confirmation  = isset( $form_settings['confirmation'] ) ? $form_settings['confirmation'] : array();

		$redirect_to     = 'same';
		$custom_page     = '';
		$external_url    = '';
		$success_message = esc_html__( 'Thanks for contacting us! We will get in touch with you shortly.', 'everest-forms' );

		if ( ! empty( $confirmation ) ) {
			$redirect_type = isset( $confirmation['redirectTo'] ) ? $confirmation['redirectTo'] : 'samePage';

			if ( 'customPage' === $redirect_type ) {
				$redirect_to = 'custom_page';
				$custom_page = isset( $confirmation['customPage'] ) ? absint( $confirmation['customPage'] ) : '';
			} elseif ( 'customUrl' === $redirect_type ) {
				$redirect_to  = 'external_url';
				$external_url = isset( $confirmation['customUrl'] ) ? esc_url_raw( $confirmation['customUrl'] ) : '';
			} else {
				$redirect_to     = 'same';
				$success_message = isset( $confirmation['messageToShow'] ) ? wp_strip_all_tags( $confirmation['messageToShow'] ) : $success_message;
			}
		}

		$form['settings'] = array(
			'email'                              => apply_filters(
				'evf_fm_' . $this->slug . '_email_notification_settings',
				$this->get_email_notification_settings( $form, $ff_form_id ),
				$form,
				$ff_form_id
			),
			'form_title'                         => $form['settings']['form_title'],
			'form_description'                   => '',
			'form_disable_message'               => esc_html__( 'This form is disabled.', 'everest-forms' ),
			'successful_form_submission_message' => $success_message,
			'submission_message_scroll'          => '1',
			'redirect_to'                        => $redirect_to,
			'custom_page'                        => $custom_page,
			'external_url'                       => $external_url,
			'enable_redirect_query_string'       => 0,
			'query_string'                       => '',
			'layout_class'                       => 'default',
			'form_class'                         => '',
			'submit_button_text'                 => esc_html__( 'Submit', 'everest-forms' ),
			'submit_button_processing_text'      => esc_html__( 'Processing...', 'everest-forms' ),
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
				'form_id'   => absint( $ff_form_id ),
				'form_from' => $this->slug,
			),
		);

		return $form;
	}

	/**
	 * Get mapped form data for the given Fluent Forms IDs.
	 *
	 * @param array $ff_form_ids Array of Fluent Forms form IDs to import.
	 * @return array
	 */
	public function get_fm_mapped_form_data( $ff_form_ids ) {
		$ff_forms_data = array();

		foreach ( $ff_form_ids as $ff_form_id ) {
			$ff_form = $this->get_form( $ff_form_id );

			if ( ! $ff_form ) {
				$ff_forms_data[ $ff_form_id ] = $ff_form;
				continue;
			}

			$ff_form_name   = $ff_form->title;
			$ff_form_fields = json_decode( $ff_form->form_fields, true );
			$ff_fields      = isset( $ff_form_fields['fields'] ) ? $ff_form_fields['fields'] : array();

			$unsupported = array();
			$upgrade_plan = array();
			$upgrade_omit = array();

			$form = array(
				'id'            => '',
				'form_enabled'  => '1',
				'form_field_id' => '',
				'form_fields'   => array(),
				'settings'      => array(
					'form_title' => $ff_form_name,
				),
			);

			if ( empty( $ff_fields ) ) {
				wp_send_json_error(
					array(
						'form_name' => sanitize_text_field( $ff_form_name ),
						'message'   => esc_html__( 'No form fields found.', 'everest-forms' ),
					)
				);
			}

			$this->process_fields( $form, $ff_fields, $unsupported, $upgrade_plan, $upgrade_omit );

			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_fields_mapping', $form, $ff_form_id, $ff_form );
			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_settings_mapping', $this->get_form_settings( $form, $ff_form_id ), $ff_form_id, $ff_form );

			$response = $this->import_form( $form, $unsupported, $upgrade_plan, $upgrade_omit );

			$ff_forms_data[ $ff_form_id ] = $response;
		}

		return $ff_forms_data;
	}

	/**
	 * Recursively process Fluent Forms fields (handles container/column wrappers).
	 *
	 * @param array $form          EVF form array (passed by reference).
	 * @param array $ff_fields     Fluent Forms fields array.
	 * @param array $unsupported   Unsupported field labels (passed by reference).
	 * @param array $upgrade_plan  Pro-only field labels (passed by reference).
	 * @param array $upgrade_omit  No-equivalent field labels (passed by reference).
	 */
	private function process_fields( &$form, $ff_fields, &$unsupported, &$upgrade_plan, &$upgrade_omit ) {
		foreach ( $ff_fields as $ff_field ) {
			$element = isset( $ff_field['element'] ) ? $ff_field['element'] : '';

			// Container fields wrap columns which wrap actual fields.
			if ( 'container' === $element ) {
				$columns = isset( $ff_field['columns'] ) ? $ff_field['columns'] : array();
				foreach ( $columns as $column ) {
					if ( ! empty( $column['fields'] ) ) {
						$this->process_fields( $form, $column['fields'], $unsupported, $upgrade_plan, $upgrade_omit );
					}
				}
				continue;
			}

			// Skip non-input / structural elements.
			if ( in_array(
				$element,
				array( 'button', 'section_break', 'custom_html', 'recaptcha', 'hcaptcha', 'turnstile', 'form_step', 'action_hook', 'shortcode', 'save_progress_button' ),
				true
			) ) {
				continue;
			}

			$this->map_field( $form, $ff_field, $unsupported, $upgrade_plan, $upgrade_omit );
		}
	}

	/**
	 * Map a single Fluent Forms field to an EVF field and add it to $form.
	 *
	 * @param array $form         EVF form array (passed by reference).
	 * @param array $ff_field     Fluent Forms field data.
	 * @param array $unsupported  Unsupported field labels (passed by reference).
	 * @param array $upgrade_plan Pro-only field labels (passed by reference).
	 * @param array $upgrade_omit No-equivalent field labels (passed by reference).
	 */
	private function map_field( &$form, $ff_field, &$unsupported, &$upgrade_plan, &$upgrade_omit ) {
		$element     = isset( $ff_field['element'] ) ? $ff_field['element'] : '';
		$attributes  = isset( $ff_field['attributes'] ) ? $ff_field['attributes'] : array();
		$settings    = isset( $ff_field['settings'] ) ? $ff_field['settings'] : array();
		$ff_name     = isset( $attributes['name'] ) ? $attributes['name'] : '';
		$label       = isset( $settings['label'] ) ? $settings['label'] : ( isset( $settings['admin_field_label'] ) ? $settings['admin_field_label'] : '' );
		$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
		$description = isset( $settings['help_message'] ) ? $settings['help_message'] : '';
		$required    = ( isset( $settings['validation_rules']['required']['value'] ) && $settings['validation_rules']['required']['value'] ) ? '1' : '0';
		$css_class   = isset( $settings['container_class'] ) ? $settings['container_class'] : '';
		$default_val = isset( $attributes['value'] ) && ! is_array( $attributes['value'] ) ? $attributes['value'] : '';

		// Allocate a new field ID slot.
		if ( ! empty( $form['form_field_id'] ) ) {
			$form_field_id = absint( $form['form_field_id'] );
			++$form['form_field_id'];
		} else {
			$form_field_id         = 0;
			$form['form_field_id'] = '1';
		}

		$field_id = evf_get_random_string() . '-' . $form_field_id;

		switch ( $element ) {

			// ── Text / Textarea ────────────────────────────────────────────
			case 'input_text':
			case 'textarea':
				$type          = ( 'textarea' === $element ) ? 'textarea' : 'text';
				$maxlength     = isset( $attributes['maxlength'] ) && '' !== $attributes['maxlength'] ? absint( $attributes['maxlength'] ) : 0;
				$limit_enabled = $maxlength > 0 ? '1' : '0';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => $type,
					'label'                          => $label,
					'meta-key'                       => $type . '-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'limit_enabled'                  => $limit_enabled,
					'limit_count'                    => $maxlength > 0 ? (string) $maxlength : '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'input_mask'                     => isset( $settings['temp_mask'] ) ? $settings['temp_mask'] : '',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Email ──────────────────────────────────────────────────────
			case 'input_email':
				$is_unique = isset( $settings['is_unique'] ) && 'yes' === $settings['is_unique'] ? '1' : '0';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'email',
					'label'                          => $label,
					'meta-key'                       => 'email-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'limit_enabled'                  => '0',
					'limit_count'                    => '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'input_mask'                     => '',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'is_unique'                      => $is_unique,
					'ff_name'                        => $ff_name,
				);
				break;

			// ── URL ────────────────────────────────────────────────────────
			case 'input_url':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'url',
					'label'                          => $label,
					'meta-key'                       => 'url-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'limit_enabled'                  => '0',
					'limit_count'                    => '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'input_mask'                     => '',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Number ────────────────────────────────────────────────────
			case 'input_number':
				$min_value = isset( $settings['validation_rules']['min']['value'] ) && '' !== $settings['validation_rules']['min']['value']
					? $settings['validation_rules']['min']['value']
					: '';
				$max_value = isset( $settings['validation_rules']['max']['value'] ) && '' !== $settings['validation_rules']['max']['value']
					? $settings['validation_rules']['max']['value']
					: '';
				$step      = isset( $settings['number_step'] ) && '' !== $settings['number_step']
					? $settings['number_step']
					: '0';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'number',
					'label'                          => $label,
					'meta-key'                       => 'number-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'step'                           => $step,
					'min_value'                      => $min_value,
					'max_value'                      => $max_value,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Phone ──────────────────────────────────────────────────────
			case 'phone':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'phone-us',
					'label'                          => $label,
					'meta-key'                       => 'phone-us-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Name field (has first_name / middle_name / last_name sub-fields) ──
			case 'input_name':
				$name_sub_fields = isset( $ff_field['fields'] ) ? $ff_field['fields'] : array();
				$row_num         = $form['form_field_id'];
				$grid_cols       = array( 'grid_1', 'grid_2', 'grid_3' );

				// Collect only visible sub-fields in display order.
				$visible_subs = array();
				foreach ( array( 'first_name', 'middle_name', 'last_name' ) as $sub_key ) {
					if ( isset( $name_sub_fields[ $sub_key ] ) ) {
						$sub_visible = isset( $name_sub_fields[ $sub_key ]['settings']['visible'] )
							? $name_sub_fields[ $sub_key ]['settings']['visible']
							: true;
						if ( $sub_visible ) {
							$visible_subs[] = $sub_key;
						}
					}
				}

				foreach ( $visible_subs as $idx => $sub_key ) {
					$sub_field    = $name_sub_fields[ $sub_key ];
					$sub_attrs    = isset( $sub_field['attributes'] ) ? $sub_field['attributes'] : array();
					$sub_settings = isset( $sub_field['settings'] ) ? $sub_field['settings'] : array();
					$sub_label    = isset( $sub_settings['label'] ) ? $sub_settings['label'] : ucfirst( str_replace( '_', ' ', $sub_key ) );
					$sub_ph       = isset( $sub_attrs['placeholder'] ) ? $sub_attrs['placeholder'] : '';
					$sub_required = ( isset( $sub_settings['validation_rules']['required']['value'] ) && $sub_settings['validation_rules']['required']['value'] ) ? '1' : '0';
					$sub_type     = ( 'last_name' === $sub_key ) ? 'last-name' : 'first-name';
					$sub_grid     = isset( $grid_cols[ $idx ] ) ? $grid_cols[ $idx ] : 'grid_1';

					// Allocate a new slot for each sub-field after the first.
					if ( $idx > 0 ) {
						$form_field_id = absint( $form['form_field_id'] );
						++$form['form_field_id'];
						$field_id = evf_get_random_string() . '-' . $form_field_id;
					}

					$form['structure'][ 'row_' . $row_num ][ $sub_grid ][] = $field_id;

					$form['form_fields'][ $field_id ] = array(
						'id'                             => $field_id,
						'type'                           => $sub_type,
						'label'                          => $sub_label,
						'meta-key'                       => $sub_key . '_' . $ff_name,
						'description'                    => '',
						'required'                       => $sub_required,
						'required_field_message_setting' => 'global',
						'required-field-message'         => '',
						'placeholder'                    => $sub_ph,
						'label_hide'                     => '0',
						'default_value'                  => '',
						'css'                            => '',
						'regex_value'                    => '',
						'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
						'ff_name'                        => $ff_name . '[' . $sub_key . ']',
					);
				}
				break;

			// ── Select / Dropdown ──────────────────────────────────────────
			case 'select':
				$is_multiple      = isset( $attributes['multiple'] ) && $attributes['multiple'];
				$advanced_options = isset( $settings['advanced_options'] ) ? $settings['advanced_options'] : array();
				$evf_choices      = array();
				foreach ( $advanced_options as $option ) {
					$evf_choices[] = array(
						'label' => isset( $option['label'] ) ? $option['label'] : '',
						'value' => isset( $option['value'] ) ? $option['value'] : '',
						'image' => '',
					);
				}
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'select',
					'label'                          => $label,
					'meta-key'                       => 'dropdown_-' . $ff_name,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => '0',
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'css'                            => $css_class,
					'placeholder'                    => isset( $settings['placeholder'] ) ? $settings['placeholder'] : '',
					'multiple_choices'               => $is_multiple ? '1' : '0',
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Radio ──────────────────────────────────────────────────────
			case 'input_radio':
				$advanced_options = isset( $settings['advanced_options'] ) ? $settings['advanced_options'] : array();
				$evf_choices      = array();
				foreach ( $advanced_options as $option ) {
					$evf_choices[] = array(
						'label' => isset( $option['label'] ) ? $option['label'] : '',
						'value' => isset( $option['value'] ) ? $option['value'] : '',
						'image' => '',
					);
				}
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'radio',
					'label'                          => $label,
					'meta-key'                       => 'radio-' . $ff_name,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => '0',
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'input_columns'                  => '',
					'css'                            => $css_class,
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Checkbox ───────────────────────────────────────────────────
			case 'input_checkbox':
				$advanced_options = isset( $settings['advanced_options'] ) ? $settings['advanced_options'] : array();
				$evf_choices      = array();
				foreach ( $advanced_options as $option ) {
					$evf_choices[] = array(
						'label' => isset( $option['label'] ) ? $option['label'] : '',
						'value' => isset( $option['value'] ) ? $option['value'] : '',
						'image' => '',
					);
				}
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'checkbox',
					'label'                          => $label,
					'meta-key'                       => 'checkbox-' . $ff_name,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => '0',
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'input_columns'                  => '',
					'choice_limit'                   => '',
					'css'                            => $css_class,
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Date / Time ────────────────────────────────────────────────
			case 'input_date':
				$date_format = isset( $settings['date_format'] ) ? $settings['date_format'] : 'm/d/Y';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'date-time',
					'label'                          => $label,
					'meta-key'                       => 'date-time-' . $ff_name,
					'datetime_format'                => 'm/d/Y',
					'datetime_style'                 => 'picker',
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
					'css'                            => $css_class,
					'date_format'                    => $this->map_date_format( $date_format ),
					'disable_dates'                  => '',
					'date_localization'              => 'en',
					'date_timezone'                  => 'Default',
					'date_mode'                      => 'single',
					'min_date'                       => '',
					'max_date'                       => '',
					'min_date_range'                 => '',
					'max_date_range'                 => '',
					'time_interval'                  => '30',
					'time_format'                    => 'g:i A',
					'min_time_hour'                  => '',
					'min_time_minute'                => '',
					'max_time_hour'                  => '',
					'max_time_minute'                => '',
					'ff_name'                        => $ff_name,
				);
				break;

			// ── File / Image upload ────────────────────────────────────────
			case 'input_file':
			case 'input_image':
				$type = ( 'input_image' === $element ) ? 'image-upload' : 'file-upload';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => $type,
					'label'                          => $label,
					'meta-key'                       => $type . '-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => '0',
					'css'                            => $css_class,
					'max_file_size'                  => '2',
					'max_file_number'                => '1',
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Terms & Conditions / GDPR ──────────────────────────────────
			case 'terms_and_condition':
			case 'gdpr_agreement':
				$privacy_text = '';
				if ( isset( $settings['tnc_html'] ) ) {
					$privacy_text = wp_strip_all_tags( $settings['tnc_html'] );
				} elseif ( isset( $settings['message'] ) ) {
					$privacy_text = wp_strip_all_tags( $settings['message'] );
				}
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'privacy-policy',
					'label'                          => $label ? $label : esc_html__( 'Privacy Policy', 'everest-forms' ),
					'meta-key'                       => 'privacy-policy-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => '0',
					'css'                            => $css_class,
					'privacy_text'                   => $privacy_text,
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Range Slider (Pro) ─────────────────────────────────────────
			case 'rangeslider':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'range-slider',
					'label'                          => $label,
					'meta-key'                       => 'range-slider-' . $ff_name,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'step'                           => isset( $settings['step'] ) ? $settings['step'] : '1',
					'min_value'                      => isset( $settings['min'] ) ? $settings['min'] : '0',
					'max_value'                      => isset( $settings['max'] ) ? $settings['max'] : '100',
					'placeholder'                    => '',
					'label_hide'                     => '0',
					'default_value'                  => isset( $settings['default_value'] ) ? $settings['default_value'] : '0',
					'css'                            => $css_class,
					'skin'                           => '',
					'handle_color'                   => '',
					'highlight_color'                => '',
					'track_color'                    => '',
					'prefix_text'                    => '',
					'show_slider_input'              => '1',
					'ff_name'                        => $ff_name,
				);
				break;

			// ── Unsupported fields ─────────────────────────────────────────
			case 'ratings':
			case 'net_promoter_score':
			case 'tabular_grid':
			case 'chained_select':
			case 'repeater_field':
			case 'quiz_score':
			case 'address':
				$unsupported[] = $label ?: $element;
				break;

			default:
				break;
		}
	}

	/**
	 * Convert a Fluent Forms date format string to the closest EVF equivalent.
	 *
	 * @param string $ff_format Fluent Forms date format (PHP date() syntax).
	 * @return string
	 */
	private function map_date_format( $ff_format ) {
		$map = array(
			'd/m/Y' => 'd/m/Y',
			'm/d/Y' => 'm/d/Y',
			'Y-m-d' => 'Y-m-d',
			'd-m-Y' => 'd-m-Y',
			'Y/m/d' => 'Y/m/d',
		);

		return isset( $map[ $ff_format ] ) ? $map[ $ff_format ] : 'm/d/Y';
	}

	/**
	 * Migrate form entries from Fluent Forms to EVF.
	 *
	 * @param int $evf_form_id The Everest Forms form ID (destination).
	 * @param int $form_id     The Fluent Forms form ID (source).
	 * @return array Map of FF submission ID → EVF entry ID.
	 */
	public function migrate_entry( $evf_form_id, $form_id ) {
		global $wpdb;

		$form_data = evf()->form->get(
			absint( $evf_form_id ),
			array( 'content_only' => true )
		);

		$evf_form_fields  = $form_data['form_fields'];
		$evf_form_entries = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$submissions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}fluentform_submissions WHERE form_id = %d AND status != 'trashed' ORDER BY id ASC",
				$form_id
			)
		);

		if ( ! $submissions || ! is_array( $submissions ) ) {
			return $evf_form_entries;
		}

		foreach ( $submissions as $submission ) {
			$response = json_decode( $submission->response, true );

			if ( ! $response ) {
				continue;
			}

			$entry_list = array();

			foreach ( $evf_form_fields as $field_key => $form_field ) {
				$field_type     = $form_field['type'];
				$field_meta_key = $form_field['meta-key'];
				$field_name     = $form_field['label'];
				$ff_name        = isset( $form_field['ff_name'] ) ? $form_field['ff_name'] : '';

				if ( empty( $ff_name ) ) {
					continue;
				}

				$value = null;

				// Name sub-fields use "parentName[sub_key]" notation.
				if ( preg_match( '/^(.+)\[(.+)\]$/', $ff_name, $m ) ) {
					$parent_key = $m[1];
					$sub_key    = $m[2];
					if ( isset( $response[ $parent_key ][ $sub_key ] ) ) {
						$value = $response[ $parent_key ][ $sub_key ];
					}
				} else {
					if ( isset( $response[ $ff_name ] ) ) {
						$value = $response[ $ff_name ];
					}
				}

				if ( null === $value || '' === $value ) {
					continue;
				}

				$entry = array();

				switch ( $field_type ) {
					case 'first-name':
					case 'last-name':
						$entry['id']       = $field_key;
						$entry['type']     = $field_type;
						$entry['meta_key'] = $field_meta_key;
						$entry['value']    = $value;
						$entry['name']     = $field_name;
						break;

					case 'radio':
						$entry['id']        = $field_key;
						$entry['type']      = $field_type;
						$entry['meta_key']  = $field_meta_key;
						$entry['value']     = array(
							'name'  => $field_name,
							'type'  => $field_type,
							'label' => is_array( $value ) ? implode( ', ', $value ) : $value,
						);
						$entry['value_raw'] = wp_json_encode( $value );
						break;

					case 'checkbox':
						$entry['id']        = $field_key;
						$entry['type']      = $field_type;
						$entry['meta_key']  = $field_meta_key;
						$entry['value']     = array(
							'name'  => $field_name,
							'type'  => $field_type,
							'label' => is_array( $value ) ? $value : array( $value ),
						);
						$entry['value_raw'] = wp_json_encode( $value );
						break;

					case 'select':
						$entry['id']        = $field_key;
						$entry['type']      = $field_type;
						$entry['meta_key']  = $field_meta_key;
						$entry['name']      = $field_name;
						$entry['value']     = is_array( $value ) ? $value : array( $value );
						$entry['value_raw'] = is_array( $value ) ? $value : array( $value );
						break;

					default:
						$entry['id']       = $field_key;
						$entry['type']     = $field_type;
						$entry['meta_key'] = $field_meta_key;
						$entry['name']     = $field_name;
						$entry['value']    = is_array( $value ) ? implode( ', ', $value ) : $value;
						break;
				}

				if ( ! empty( $entry ) ) {
					$entry_list[ $field_key ] = $entry;
				}
			}

			$entries = array(
				'user_id'         => $submission->user_id,
				'user_device'     => isset( $submission->browser ) ? $submission->browser : '',
				'user_ip_address' => isset( $submission->ip ) ? $submission->ip : '',
				'form_id'         => $evf_form_id,
				'referer'         => isset( $submission->source_url ) ? $submission->source_url : '',
				'fields'          => wp_json_encode( $entry_list ),
				'status'          => 'publish',
				'viewed'          => ( isset( $submission->status ) && 'read' === $submission->status ) ? '1' : '0',
				'starred'         => isset( $submission->is_favourite ) ? $submission->is_favourite : '0',
				'date_created'    => $submission->created_at,
			);

			if ( $this->check_token_column() ) {
				$entries['token'] = null;
			}

			$entry_id = $this->save_migrated_entry( $entries, $entry_list, $form_data );

			$evf_form_entries[ $submission->id ] = $entry_id;
		}

		return $evf_form_entries;
	}

	/**
	 * Check if the EVF token column exists (Save & Continue add-on).
	 *
	 * @return bool
	 */
	public function check_token_column() {
		return defined( 'EVF_SAVE_AND_CONTINUE_VERSION' );
	}
}

new EVF_Fm_Fluentforms();
