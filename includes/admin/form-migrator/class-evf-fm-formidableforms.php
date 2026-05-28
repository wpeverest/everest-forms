<?php
/**
 * EverestForms Form Migrator Formidable Forms Class
 *
 * @package EverestForms\Admin
 * @since   3.4.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Formidableforms class.
 */
class EVF_Fm_Formidableforms extends EVF_Admin_Form_Migrator {

	/**
	 * Define required properties.
	 */
	public function init() {
		$this->name = 'Formidable Forms';
		$this->slug = 'formidable-forms';
		$this->path = 'formidable/formidable.php';
	}

	/**
	 * If the importer source is available.
	 *
	 * @return bool
	 */
	protected function is_active() {
		return is_plugin_active( $this->path );
	}

	/**
	 * Check if the plugin is installed.
	 *
	 * @return bool
	 */
	protected function is_installed() {
		return file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path );
	}

	/**
	 * Get all forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		global $wpdb;

		if ( ! $this->is_active() ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$forms = $wpdb->get_results(
			"SELECT id, name FROM {$wpdb->prefix}frm_forms WHERE is_template = 0 AND status != 'draft' ORDER BY id DESC"
		);

		$result = array();
		foreach ( (array) $forms as $form ) {
			$result[ $form->id ] = $form->name;
		}

		return $result;
	}

	/**
	 * Get a single form row.
	 *
	 * @param int $id Form ID.
	 * @return object|null
	 */
	public function get_form( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}frm_forms WHERE id = %d", $id )
		);
	}

	/**
	 * Get all fields for a Formidable Forms form.
	 *
	 * Returns array of field data with unserialized field_options and options.
	 *
	 * @param int $form_id Formidable Forms form ID.
	 * @return array
	 */
	private function get_form_fields( $form_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, field_key, name, description, type, default_value, options, field_order, required, field_options
				 FROM {$wpdb->prefix}frm_fields
				 WHERE form_id = %d
				 ORDER BY field_order ASC",
				$form_id
			)
		);

		$fields = array();
		foreach ( (array) $rows as $row ) {
			$field_opts = $row->field_options;
			if ( is_string( $field_opts ) ) {
				$field_opts = maybe_unserialize( $field_opts );
			}
			if ( ! is_array( $field_opts ) ) {
				$field_opts = array();
			}

			$options = $row->options;
			if ( is_string( $options ) ) {
				$options = maybe_unserialize( $options );
			}

			$fields[] = array(
				'id'            => (int) $row->id,
				'field_key'     => $row->field_key,
				'label'         => $row->name,
				'description'   => $row->description,
				'type'          => $row->type,
				'default_value' => $row->default_value,
				'options'       => is_array( $options ) ? $options : array(),
				'required'      => (bool) $row->required,
				'field_opts'    => $field_opts,
			);
		}

		return $fields;
	}

	/**
	 * Get email notification actions for a form.
	 *
	 * Formidable Forms stores actions as the 'frm_actions' CPT with the action
	 * type in post_excerpt and JSON-encoded settings in post_content.
	 *
	 * @param int $form_id Formidable Forms form ID.
	 * @return array
	 */
	private function get_email_actions( $form_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_content, post_excerpt
				 FROM {$wpdb->posts}
				 WHERE post_type = 'frm_actions'
				   AND menu_order = %d
				   AND post_status = 'publish'",
				$form_id
			)
		);

		$actions = array();
		foreach ( (array) $rows as $row ) {
			if ( 'email' !== $row->post_excerpt ) {
				continue;
			}
			$settings = json_decode( $row->post_content, true );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
			$actions[] = array(
				'id'       => $row->ID,
				'title'    => $row->post_title,
				'settings' => $settings,
			);
		}

		return $actions;
	}

	/**
	 * Convert Formidable Forms merge tags to EVF smart tags.
	 *
	 * Formidable field tags: [field_key] or [field_id]
	 * System tags: [admin_email], [sitename], [default-message], [siteurl], etc.
	 *
	 * @param string $string The string containing merge tags.
	 * @param array  $fields Mapped EVF form fields.
	 * @return string
	 */
	private function get_smarttags( $string, $fields = array() ) {
		if ( empty( $string ) ) {
			return $string;
		}

		// Replace [field_key] → {field_id="evf-field-id"}.
		preg_match_all( '/\[([a-zA-Z0-9_\-]+)\]/', $string, $matches );
		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $frm_key ) {
				foreach ( $fields as $evf_field ) {
					if ( isset( $evf_field['frm_field_key'] ) && $evf_field['frm_field_key'] === $frm_key ) {
						$evf_field_id = $this->get_field_id_for_smarttags( $evf_field );
						$string       = str_replace(
							'[' . $frm_key . ']',
							'{field_id="' . $evf_field_id . '"}',
							$string
						);
						break;
					}
				}
			}
		}

		// System-level tag replacements.
		$string = str_replace(
			array(
				'[default-message]',
				'[admin_email]',
				'[sitename]',
				'[siteurl]',
				'[user_login]',
				'[first_name]',
				'[last_name]',
				'[ip]',
			),
			array(
				'{all_fields}',
				'{admin_email}',
				'{site_title}',
				'{page_url}',
				'{user_email}',
				'{first_name}',
				'{last_name}',
				'{user_ip_address}',
			),
			$string
		);

		return $string;
	}

	/**
	 * Parse a Formidable "from" field (format: "Name <email>" or "email") into
	 * separate name and email components.
	 *
	 * @param string $from       Raw from value.
	 * @param string $from_name  Output: sender name.
	 * @param string $from_email Output: sender email.
	 */
	private function parse_from_field( $from, &$from_name, &$from_email ) {
		$from = trim( $from );
		if ( preg_match( '/^(.+?)\s*<(.+?)>$/', $from, $m ) ) {
			$from_name  = trim( $m[1] );
			$from_email = trim( $m[2] );
		} elseif ( is_email( $from ) ) {
			$from_name  = '';
			$from_email = $from;
		} else {
			$from_name  = $from;
			$from_email = '';
		}
	}

	/**
	 * Build EVF email notification settings from Formidable email actions.
	 *
	 * @param array $form         Partially built EVF form (form_fields populated).
	 * @param array $email_actions Email actions from get_email_actions().
	 * @return array
	 */
	private function get_email_notification_settings( $form, $email_actions ) {
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

		if ( empty( $email_actions ) ) {
			return $default;
		}

		$evf_notifications = array();
		$connection_num    = 1;

		foreach ( $email_actions as $action ) {
			$settings    = $action['settings'];
			$from_raw    = isset( $settings['from'] ) ? $settings['from'] : '';
			$from_name   = get_bloginfo( 'name' );
			$from_email  = get_option( 'admin_email' );

			if ( ! empty( $from_raw ) ) {
				$this->parse_from_field( $from_raw, $from_name, $from_email );
			}

			$connection_key                        = 'connection_' . $connection_num;
			$evf_notifications[ $connection_key ] = array(
				'enable_email_notification' => '1',
				'connection_name'           => ! empty( $action['title'] ) ? sanitize_text_field( $action['title'] ) : esc_html__( 'Admin Notification', 'everest-forms' ),
				'evf_to_email'              => $this->get_smarttags( isset( $settings['email_to'] ) ? $settings['email_to'] : '{admin_email}', $form['form_fields'] ),
				'evf_from_name'             => $this->get_smarttags( $from_name, $form['form_fields'] ),
				'evf_from_email'            => $this->get_smarttags( $from_email, $form['form_fields'] ),
				'evf_reply_to'              => $this->get_smarttags( isset( $settings['reply_to'] ) ? $settings['reply_to'] : '', $form['form_fields'] ),
				'evf_email_subject'         => $this->get_smarttags( isset( $settings['email_subject'] ) ? $settings['email_subject'] : '', $form['form_fields'] ),
				'evf_email_message'         => $this->get_smarttags( isset( $settings['email_message'] ) ? wp_strip_all_tags( $settings['email_message'] ) : '{all_fields}', $form['form_fields'] ),
			);

			++$connection_num;
		}

		return ! empty( $evf_notifications ) ? $evf_notifications : $default;
	}

	/**
	 * Map form-level settings (confirmation, title, redirect, etc.).
	 *
	 * @param array  $form          Partially built EVF form array.
	 * @param int    $frm_form_id   Formidable Forms form ID.
	 * @param array  $frm_options   Unserialized form options from frm_forms.options.
	 * @param array  $email_actions Email actions from get_email_actions().
	 * @return array
	 */
	private function get_form_settings( $form, $frm_form_id, $frm_options, $email_actions ) {
		$redirect_to     = 'same';
		$external_url    = '';
		$success_message = esc_html__( 'Thanks for contacting us! We will get in touch with you shortly.', 'everest-forms' );
		$submit_text     = esc_html__( 'Submit', 'everest-forms' );

		if ( ! empty( $frm_options['success_msg'] ) ) {
			$success_message = wp_strip_all_tags( $frm_options['success_msg'] );
		}

		if ( ! empty( $frm_options['submit_value'] ) ) {
			$submit_text = sanitize_text_field( $frm_options['submit_value'] );
		}

		if ( isset( $frm_options['success_action'] ) && 'redirect' === $frm_options['success_action']
			&& ! empty( $frm_options['success_url'] ) ) {
			$redirect_to  = 'external_url';
			$external_url = esc_url_raw( $frm_options['success_url'] );
		}

		$form['settings'] = array(
			'email'                              => apply_filters(
				'evf_fm_' . $this->slug . '_email_notification_settings',
				$this->get_email_notification_settings( $form, $email_actions ),
				$form,
				$frm_form_id
			),
			'form_title'                         => $form['settings']['form_title'],
			'form_description'                   => '',
			'form_disable_message'               => esc_html__( 'This form is disabled.', 'everest-forms' ),
			'successful_form_submission_message' => $success_message,
			'submission_message_scroll'          => '1',
			'redirect_to'                        => $redirect_to,
			'custom_page'                        => '',
			'external_url'                       => $external_url,
			'enable_redirect_query_string'       => 0,
			'query_string'                       => '',
			'layout_class'                       => 'default',
			'form_class'                         => isset( $frm_options['form_class'] ) ? sanitize_text_field( $frm_options['form_class'] ) : '',
			'submit_button_text'                 => $submit_text,
			'submit_button_processing_text'      => esc_html__( 'Processing...', 'everest-forms' ),
			'submit_button_class'                => '',
			'ajax_form_submission'               => ! empty( $frm_options['ajax_submit'] ) ? '1' : '0',
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
				'form_id'   => absint( $frm_form_id ),
				'form_from' => $this->slug,
			),
		);

		return $form;
	}

	/**
	 * Get mapped form data for the given Formidable Forms IDs.
	 *
	 * @param array $frm_form_ids Array of Formidable Forms IDs to import.
	 * @return array
	 */
	public function get_fm_mapped_form_data( $frm_form_ids ) {
		$frm_forms_data = array();

		foreach ( $frm_form_ids as $frm_form_id ) {
			$frm_form = $this->get_form( $frm_form_id );

			if ( ! $frm_form ) {
				$frm_forms_data[ $frm_form_id ] = false;
				continue;
			}

			$frm_options = maybe_unserialize( $frm_form->options );
			if ( ! is_array( $frm_options ) ) {
				$frm_options = array();
			}

			$form_name     = $frm_form->name;
			$frm_fields    = $this->get_form_fields( $frm_form_id );
			$email_actions = $this->get_email_actions( $frm_form_id );

			$unsupported  = array();
			$upgrade_plan = array();
			$upgrade_omit = array();

			$form = array(
				'id'            => '',
				'form_enabled'  => '1',
				'form_field_id' => '',
				'form_fields'   => array(),
				'settings'      => array(
					'form_title' => $form_name,
				),
			);

			if ( empty( $frm_fields ) ) {
				wp_send_json_error(
					array(
						'form_name' => sanitize_text_field( $form_name ),
						'message'   => esc_html__( 'No form fields found.', 'everest-forms' ),
					)
				);
			}

			foreach ( $frm_fields as $frm_field ) {
				$this->map_field( $form, $frm_field, $unsupported, $upgrade_plan, $upgrade_omit );
			}

			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_fields_mapping', $form, $frm_form_id, $frm_form );
			$form = apply_filters(
				'evf_fm_' . $this->slug . '_form_after_settings_mapping',
				$this->get_form_settings( $form, $frm_form_id, $frm_options, $email_actions ),
				$frm_form_id,
				$frm_form
			);

			$response = $this->import_form( $form, $unsupported, $upgrade_plan, $upgrade_omit );

			$frm_forms_data[ $frm_form_id ] = $response;
		}

		return $frm_forms_data;
	}

	/**
	 * Map a single Formidable Forms field to an EVF field and add it to $form.
	 *
	 * @param array $form         EVF form array (passed by reference).
	 * @param array $frm_field    Formidable field data array.
	 * @param array $unsupported  Unsupported field labels (passed by reference).
	 * @param array $upgrade_plan Pro-only field labels (passed by reference).
	 * @param array $upgrade_omit No-equivalent field labels (passed by reference).
	 */
	private function map_field( &$form, $frm_field, &$unsupported, &$upgrade_plan, &$upgrade_omit ) {
		$frm_type    = isset( $frm_field['type'] ) ? $frm_field['type'] : '';
		$field_opts  = isset( $frm_field['field_opts'] ) ? $frm_field['field_opts'] : array();
		$label       = isset( $frm_field['label'] ) ? $frm_field['label'] : '';
		$frm_key     = isset( $frm_field['field_key'] ) ? $frm_field['field_key'] : '';
		$frm_id      = isset( $frm_field['id'] ) ? (int) $frm_field['id'] : 0;
		$required    = ! empty( $frm_field['required'] ) ? '1' : '0';
		$default_val = isset( $frm_field['default_value'] ) ? $frm_field['default_value'] : '';
		$placeholder = isset( $field_opts['placeholder'] ) ? $field_opts['placeholder'] : '';
		$description = isset( $frm_field['description'] ) ? wp_kses_post( $frm_field['description'] ) : '';
		$css_class   = isset( $field_opts['classes'] ) ? sanitize_text_field( $field_opts['classes'] ) : '';
		$label_hide  = ( isset( $field_opts['label'] ) && 'none' === $field_opts['label'] ) ? '1' : '0';

		// Skip non-input structural / utility elements silently.
		if ( in_array( $frm_type, array( 'html', 'hidden', 'user_id', 'captcha', 'divider', 'end_divider', 'break', 'submit', 'product', 'quantity', 'total', 'summary', 'form' ), true ) ) {
			return;
		}

		// Allocate a new field ID slot.
		if ( ! empty( $form['form_field_id'] ) ) {
			$form_field_id = absint( $form['form_field_id'] );
			++$form['form_field_id'];
		} else {
			$form_field_id         = 0;
			$form['form_field_id'] = '1';
		}

		$field_id = evf_get_random_string() . '-' . $form_field_id;

		switch ( $frm_type ) {

			// ── Text / Password / Rich Text ───────────────────────────────
			case 'text':
			case 'password':
			case 'rte':
				$maxlength = isset( $field_opts['max'] ) && '' !== $field_opts['max'] ? absint( $field_opts['max'] ) : 0;
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'text',
					'label'                          => $label,
					'meta-key'                       => 'text-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => is_string( $placeholder ) ? $placeholder : '',
					'label_hide'                     => $label_hide,
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'limit_enabled'                  => $maxlength > 0 ? '1' : '0',
					'limit_count'                    => $maxlength > 0 ? (string) $maxlength : '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'input_mask'                     => '',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Textarea ──────────────────────────────────────────────────
			case 'textarea':
				$maxlength = isset( $field_opts['max'] ) && '' !== $field_opts['max'] ? absint( $field_opts['max'] ) : 0;
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'textarea',
					'label'                          => $label,
					'meta-key'                       => 'textarea-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => is_string( $placeholder ) ? $placeholder : '',
					'label_hide'                     => $label_hide,
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'limit_enabled'                  => $maxlength > 0 ? '1' : '0',
					'limit_count'                    => $maxlength > 0 ? (string) $maxlength : '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Email ─────────────────────────────────────────────────────
			case 'email':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'email',
					'label'                          => $label,
					'meta-key'                       => 'email-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => is_string( $placeholder ) ? $placeholder : '',
					'label_hide'                     => $label_hide,
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
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── URL ───────────────────────────────────────────────────────
			case 'url':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'url',
					'label'                          => $label,
					'meta-key'                       => 'url-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => is_string( $placeholder ) ? $placeholder : '',
					'label_hide'                     => $label_hide,
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Number ────────────────────────────────────────────────────
			case 'number':
				// Formidable defaults: minnum=1, maxnum=10 — treat defaults as "not set".
				$min_value = ( isset( $field_opts['minnum'] ) && '1' !== (string) $field_opts['minnum'] ) ? $field_opts['minnum'] : '';
				$max_value = ( isset( $field_opts['maxnum'] ) && '10' !== (string) $field_opts['maxnum'] ) ? $field_opts['maxnum'] : '';
				$step      = isset( $field_opts['step'] ) ? $field_opts['step'] : '0';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'number',
					'label'                          => $label,
					'meta-key'                       => 'number-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => is_string( $placeholder ) ? $placeholder : '',
					'label_hide'                     => $label_hide,
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'step'                           => (string) $step,
					'min_value'                      => (string) $min_value,
					'max_value'                      => (string) $max_value,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Phone ─────────────────────────────────────────────────────
			case 'phone':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'phone-us',
					'label'                          => $label,
					'meta-key'                       => 'phone-us-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => is_string( $placeholder ) ? $placeholder : '',
					'label_hide'                     => $label_hide,
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Date ──────────────────────────────────────────────────────
			case 'date':
				$date_format = ( isset( $field_opts['format'] ) && $field_opts['format'] ) ? $field_opts['format'] : 'm/d/Y';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'date-time',
					'label'                          => $label,
					'meta-key'                       => 'date-time-' . $frm_key,
					'datetime_format'                => 'm/d/Y',
					'datetime_style'                 => 'picker',
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => '',
					'label_hide'                     => $label_hide,
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
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Name (split into first-name + last-name in a 2-column row) ─
			case 'name':
				$row_num = $form['form_field_id'];

				// First name (uses the already-allocated $field_id slot).
				$first_ph = is_array( $placeholder ) ? ( isset( $placeholder['first'] ) ? $placeholder['first'] : '' ) : '';
				$form['structure'][ 'row_' . $row_num ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                   = array(
					'id'                             => $field_id,
					'type'                           => 'first-name',
					'label'                          => $label ? $label . ' (' . esc_html__( 'First', 'everest-forms' ) . ')' : esc_html__( 'First Name', 'everest-forms' ),
					'meta-key'                       => 'first-name-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $first_ph,
					'label_hide'                     => $label_hide,
					'default_value'                  => '',
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
					'frm_sub_key'                    => 'first',
				);

				// Last name (allocate a second slot).
				$last_form_field_id = absint( $form['form_field_id'] );
				++$form['form_field_id'];
				$last_field_id = evf_get_random_string() . '-' . $last_form_field_id;
				$last_ph       = is_array( $placeholder ) ? ( isset( $placeholder['last'] ) ? $placeholder['last'] : '' ) : '';

				$form['structure'][ 'row_' . $row_num ]['grid_2'][] = $last_field_id;
				$form['form_fields'][ $last_field_id ]              = array(
					'id'                             => $last_field_id,
					'type'                           => 'last-name',
					'label'                          => $label ? $label . ' (' . esc_html__( 'Last', 'everest-forms' ) . ')' : esc_html__( 'Last Name', 'everest-forms' ),
					'meta-key'                       => 'last-name-' . $frm_key,
					'description'                    => '',
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $last_ph,
					'label_hide'                     => $label_hide,
					'default_value'                  => '',
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
					'frm_sub_key'                    => 'last',
				);
				break;

			// ── Select / Dropdown ─────────────────────────────────────────
			case 'select':
				$evf_choices = $this->build_choices( $frm_field['options'], $field_opts );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'select',
					'label'                          => $label,
					'meta-key'                       => 'dropdown_-' . $frm_key,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => $label_hide,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'css'                            => $css_class,
					'placeholder'                    => is_string( $placeholder ) ? $placeholder : '',
					'multiple_choices'               => '0',
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Radio ─────────────────────────────────────────────────────
			case 'radio':
				$evf_choices = $this->build_choices( $frm_field['options'], $field_opts );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'radio',
					'label'                          => $label,
					'meta-key'                       => 'radio-' . $frm_key,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => $label_hide,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'input_columns'                  => '',
					'css'                            => $css_class,
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Checkbox ──────────────────────────────────────────────────
			case 'checkbox':
				$evf_choices = $this->build_choices( $frm_field['options'], $field_opts );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'checkbox',
					'label'                          => $label,
					'meta-key'                       => 'checkbox-' . $frm_key,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => $label_hide,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'input_columns'                  => '',
					'choice_limit'                   => '',
					'css'                            => $css_class,
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── File Upload ───────────────────────────────────────────────
			case 'file':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'file-upload',
					'label'                          => $label,
					'meta-key'                       => 'file-upload-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── GDPR / Privacy Policy ─────────────────────────────────────
			case 'gdpr':
				$privacy_text = wp_strip_all_tags( $description );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'privacy-policy',
					'label'                          => $label ? $label : esc_html__( 'Privacy Policy', 'everest-forms' ),
					'meta-key'                       => 'privacy-policy-' . $frm_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'privacy_text'                   => $privacy_text,
					'frm_field_key'                  => $frm_key,
					'frm_field_id'                   => $frm_id,
				);
				break;

			// ── Unsupported ───────────────────────────────────────────────
			case 'address':
			case 'time':
			case 'signature':
			case 'star':
			case 'scale':
			case 'range':
			case 'tag':
			case 'repeater':
			case 'data':
			case 'lookup':
			case 'likert':
			case 'nps':
			case 'ranking':
				$unsupported[] = $label ?: $frm_type;
				break;

			default:
				break;
		}
	}

	/**
	 * Parse Formidable Forms field options into an EVF choices array.
	 *
	 * Formidable stores options as a serialized PHP array in frm_fields.options.
	 * Simple format: [0 => '', 1 => 'Option 1', 2 => 'Option 2']
	 * Separate-value format: ['val1' => 'Label 1', 'val2' => 'Label 2']
	 *
	 * @param array $options    Unserialized options array.
	 * @param array $field_opts Unserialized field_options array.
	 * @return array
	 */
	private function build_choices( $options, $field_opts = array() ) {
		$evf_choices    = array();
		$separate_value = ! empty( $field_opts['separate_value'] );

		if ( empty( $options ) || ! is_array( $options ) ) {
			return $evf_choices;
		}

		foreach ( $options as $key => $option ) {
			$option = (string) $option;
			if ( '' === $option ) {
				continue;
			}

			if ( $separate_value && is_string( $key ) && ! is_numeric( $key ) ) {
				$option_label = $option;
				$option_value = $key;
			} else {
				$option_label = $option;
				$option_value = $option;
			}

			$evf_choices[] = array(
				'label' => $option_label,
				'value' => $option_value,
				'image' => '',
			);
		}

		return $evf_choices;
	}

	/**
	 * Map a Formidable Forms date format string to the closest EVF equivalent.
	 *
	 * @param string $frm_format Formidable date format.
	 * @return string
	 */
	private function map_date_format( $frm_format ) {
		$map = array(
			'm/d/Y'  => 'm/d/Y',
			'd/m/Y'  => 'd/m/Y',
			'Y-m-d'  => 'Y-m-d',
			'd-m-Y'  => 'd-m-Y',
			'Y/m/d'  => 'Y/m/d',
			'm-d-Y'  => 'm/d/Y',
			'F j, Y' => 'm/d/Y',
		);
		return isset( $map[ $frm_format ] ) ? $map[ $frm_format ] : 'm/d/Y';
	}

	/**
	 * Migrate form entries from Formidable Forms to EVF.
	 *
	 * Formidable stores entries in frm_items with field values in frm_item_metas.
	 * Array values (checkboxes, name) are PHP-serialized in meta_value.
	 *
	 * @param int $evf_form_id The Everest Forms form ID (destination).
	 * @param int $form_id     The Formidable Forms form ID (source).
	 * @return array Map of Formidable item ID → EVF entry ID.
	 */
	public function migrate_entry( $evf_form_id, $form_id ) {
		global $wpdb;

		if ( ! $this->is_active() ) {
			return array();
		}

		$form_data = evf()->form->get(
			absint( $evf_form_id ),
			array( 'content_only' => true )
		);

		$evf_form_fields  = $form_data['form_fields'];
		$evf_form_entries = array();

		// Fetch all published (non-draft) entries for this form.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$submissions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_id, created_at FROM {$wpdb->prefix}frm_items
				 WHERE form_id = %d AND is_draft = 0
				 ORDER BY created_at ASC",
				$form_id
			)
		);

		if ( ! $submissions || ! is_array( $submissions ) ) {
			return $evf_form_entries;
		}

		foreach ( $submissions as $submission ) {
			$sub_id = (int) $submission->id;

			// Fetch all field-value rows for this entry.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$raw_metas = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT field_id, meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id = %d",
					$sub_id
				),
				ARRAY_A
			);

			// Build map: frm_field_id (int) → unserialized value.
			$submission_values = array();
			foreach ( (array) $raw_metas as $meta ) {
				$fid = (int) $meta['field_id'];
				$val = $meta['meta_value'];
				if ( is_string( $val ) && is_serialized( $val ) ) {
					$val = maybe_unserialize( $val );
				}
				$submission_values[ $fid ] = $val;
			}

			$entry_list = array();

			foreach ( $evf_form_fields as $field_key => $form_field ) {
				$field_type     = $form_field['type'];
				$field_meta_key = $form_field['meta-key'];
				$field_name     = $form_field['label'];
				$frm_id         = isset( $form_field['frm_field_id'] ) ? (int) $form_field['frm_field_id'] : 0;
				$frm_sub_key    = isset( $form_field['frm_sub_key'] ) ? $form_field['frm_sub_key'] : '';

				if ( ! $frm_id || ! isset( $submission_values[ $frm_id ] ) ) {
					continue;
				}

				$raw_value = $submission_values[ $frm_id ];

				// For name sub-fields (first-name/last-name), extract the relevant key.
				if ( $frm_sub_key && is_array( $raw_value ) ) {
					$raw_value = isset( $raw_value[ $frm_sub_key ] ) ? $raw_value[ $frm_sub_key ] : '';
				}

				if ( null === $raw_value || '' === $raw_value || array() === $raw_value ) {
					continue;
				}

				$entry = array();

				switch ( $field_type ) {
					case 'first-name':
					case 'last-name':
						$entry = array(
							'id'       => $field_key,
							'type'     => $field_type,
							'meta_key' => $field_meta_key,
							'value'    => $raw_value,
							'name'     => $field_name,
						);
						break;

					case 'radio':
						$entry = array(
							'id'        => $field_key,
							'type'      => $field_type,
							'meta_key'  => $field_meta_key,
							'value'     => array(
								'name'  => $field_name,
								'type'  => $field_type,
								'label' => is_array( $raw_value ) ? implode( ', ', $raw_value ) : $raw_value,
							),
							'value_raw' => wp_json_encode( $raw_value ),
						);
						break;

					case 'checkbox':
						$choice_labels = is_array( $raw_value ) ? $raw_value : array( $raw_value );
						$entry         = array(
							'id'        => $field_key,
							'type'      => $field_type,
							'meta_key'  => $field_meta_key,
							'value'     => array(
								'name'  => $field_name,
								'type'  => $field_type,
								'label' => $choice_labels,
							),
							'value_raw' => wp_json_encode( $raw_value ),
						);
						break;

					case 'select':
						$entry = array(
							'id'        => $field_key,
							'type'      => $field_type,
							'meta_key'  => $field_meta_key,
							'name'      => $field_name,
							'value'     => is_array( $raw_value ) ? $raw_value : array( $raw_value ),
							'value_raw' => is_array( $raw_value ) ? $raw_value : array( $raw_value ),
						);
						break;

					default:
						$entry = array(
							'id'       => $field_key,
							'type'     => $field_type,
							'meta_key' => $field_meta_key,
							'name'     => $field_name,
							'value'    => is_array( $raw_value ) ? implode( ', ', $raw_value ) : $raw_value,
						);
						break;
				}

				if ( ! empty( $entry ) ) {
					$entry_list[ $field_key ] = $entry;
				}
			}

			$entries = array(
				'user_id'         => (int) $submission->user_id,
				'user_device'     => '',
				'user_ip_address' => '',
				'form_id'         => $evf_form_id,
				'referer'         => '',
				'fields'          => wp_json_encode( $entry_list ),
				'status'          => 'publish',
				'viewed'          => '0',
				'starred'         => '0',
				'date_created'    => $submission->created_at,
			);

			if ( $this->check_token_column() ) {
				$entries['token'] = null;
			}

			$entry_id = $this->save_migrated_entry( $entries, $entry_list, $form_data );

			$evf_form_entries[ $sub_id ] = $entry_id;
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

new EVF_Fm_Formidableforms();
