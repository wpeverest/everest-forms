<?php
/**
 * EverestForms Form Migrator Ninja Forms Class
 *
 * @package EverestForms\Admin
 * @since   3.4.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Ninjaforms class.
 */
class EVF_Fm_Ninjaforms extends EVF_Admin_Form_Migrator {

	/**
	 * Define required properties.
	 */
	public function init() {
		$this->name = 'Ninja Forms';
		$this->slug = 'ninja-forms';
		$this->path = 'ninja-forms/ninja-forms.php';
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

		$required_form_arr = array();

		if ( ! $this->is_active() ) {
			return $required_form_arr;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$forms = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}nf3_forms ORDER BY id DESC" );

		if ( empty( $forms ) ) {
			return $required_form_arr;
		}

		foreach ( $forms as $form ) {
			$required_form_arr[ $form->id ] = $form->title;
		}

		return $required_form_arr;
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
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}nf3_forms WHERE id = %d", $id ) );
	}

	/**
	 * Get all fields for a form, keyed by field ID with their meta grouped.
	 *
	 * Returns structure: [ field_id => [ 'id'=>, 'label'=>, 'key'=>, 'type'=>, 'required'=>,
	 *                                     'default_value'=>, 'order'=>, 'meta'=> [...] ] ]
	 *
	 * @param int $form_id Ninja Forms form ID.
	 * @return array
	 */
	private function get_form_fields( $form_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.id, f.label, f.`key`, f.type, f.required, f.default_value, f.`order`,
				        m.`key` AS meta_key, m.value AS meta_value
				 FROM {$wpdb->prefix}nf3_fields AS f
				 LEFT JOIN {$wpdb->prefix}nf3_field_meta AS m ON f.id = m.parent_id
				 WHERE f.parent_id = %d
				 ORDER BY f.`order` ASC",
				$form_id
			)
		);

		if ( empty( $rows ) ) {
			return array();
		}

		$fields = array();
		foreach ( $rows as $row ) {
			$fid = (int) $row->id;
			if ( ! isset( $fields[ $fid ] ) ) {
				$fields[ $fid ] = array(
					'id'            => $fid,
					'label'         => $row->label,
					'key'           => $row->key,
					'type'          => $row->type,
					'required'      => (bool) $row->required,
					'default_value' => $row->default_value,
					'order'         => (int) $row->order,
					'meta'          => array(),
				);
			}
			if ( ! empty( $row->meta_key ) ) {
				$fields[ $fid ]['meta'][ $row->meta_key ] = $row->meta_value;
			}
		}

		return $fields;
	}

	/**
	 * Get all actions for a form, keyed by action ID with their meta grouped.
	 *
	 * @param int $form_id Ninja Forms form ID.
	 * @return array
	 */
	private function get_form_actions( $form_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.id, a.type, a.active, a.title,
				        m.`key` AS meta_key, m.value AS meta_value
				 FROM {$wpdb->prefix}nf3_actions AS a
				 LEFT JOIN {$wpdb->prefix}nf3_action_meta AS m ON a.id = m.parent_id
				 WHERE a.parent_id = %d
				 ORDER BY a.id ASC",
				$form_id
			)
		);

		if ( empty( $rows ) ) {
			return array();
		}

		$actions = array();
		foreach ( $rows as $row ) {
			$aid = (int) $row->id;
			if ( ! isset( $actions[ $aid ] ) ) {
				$actions[ $aid ] = array(
					'id'     => $aid,
					'type'   => $row->type,
					'active' => $row->active,
					'title'  => $row->title,
					'meta'   => array(),
				);
			}
			if ( ! empty( $row->meta_key ) ) {
				$actions[ $aid ]['meta'][ $row->meta_key ] = $row->meta_value;
			}
		}

		return $actions;
	}

	/**
	 * Convert Ninja Forms merge tags to EVF smart tags.
	 *
	 * NF field merge tags: {field_admin_label:fieldKey}
	 * NF system tags: {wp:admin_email}, {all_fields_table}, etc.
	 *
	 * @param string $string The string containing merge tags.
	 * @param array  $fields Mapped EVF form fields (for field-specific tags).
	 * @return string
	 */
	private function get_smarttags( $string, $fields = array() ) {
		if ( empty( $string ) ) {
			return $string;
		}

		// Replace {field_admin_label:fieldKey} → {field_id="evf-field-id"}.
		preg_match_all( '/\{field_admin_label:([^}]+)\}/', $string, $tags );
		if ( ! empty( $tags[1] ) ) {
			foreach ( $tags[1] as $nf_key ) {
				foreach ( $fields as $evf_field ) {
					if ( isset( $evf_field['nf_key'] ) && $evf_field['nf_key'] === $nf_key ) {
						$evf_field_id = $this->get_field_id_for_smarttags( $evf_field );
						$string       = str_replace(
							'{field_admin_label:' . $nf_key . '}',
							'{field_id="' . $evf_field_id . '"}',
							$string
						);
						break;
					}
				}
			}
		}

		// Replace system-level merge tags.
		$string = str_replace(
			array(
				'{all_fields_table}',
				'{wp:admin_email}',
				'{wp:user_email}',
				'{wp:post_title}',
				'{wp:site_title}',
				'{wp:user_first_name}',
				'{wp:user_last_name}',
			),
			array(
				'{all_fields}',
				'{admin_email}',
				'{user_email}',
				'{post_title}',
				'{site_title}',
				'{first_name}',
				'{last_name}',
			),
			$string
		);

		return $string;
	}

	/**
	 * Build EVF email notification settings from Ninja Forms email actions.
	 *
	 * @param array $form       Partially built EVF form array (form_fields must be populated).
	 * @param array $actions    Ninja Forms actions array from get_form_actions().
	 * @return array
	 */
	private function get_email_notification_settings( $form, $actions ) {
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

		$evf_notifications = array();
		$connection_num    = 1;

		foreach ( $actions as $action ) {
			if ( 'email' !== $action['type'] ) {
				continue;
			}
			if ( empty( $action['active'] ) ) {
				continue;
			}

			$meta = $action['meta'];

			$connection_key = 'connection_' . $connection_num;

			$evf_notifications[ $connection_key ] = array(
				'enable_email_notification' => '1',
				'connection_name'           => ! empty( $action['title'] ) ? sanitize_text_field( $action['title'] ) : esc_html__( 'Admin Notification', 'everest-forms' ),
				'evf_to_email'              => $this->get_smarttags( isset( $meta['to'] ) ? $meta['to'] : '{admin_email}', $form['form_fields'] ),
				'evf_from_name'             => $this->get_smarttags( isset( $meta['from_name'] ) ? $meta['from_name'] : '', $form['form_fields'] ),
				'evf_from_email'            => $this->get_smarttags( isset( $meta['from_address'] ) ? $meta['from_address'] : '', $form['form_fields'] ),
				'evf_reply_to'              => $this->get_smarttags( isset( $meta['reply_to'] ) ? $meta['reply_to'] : '', $form['form_fields'] ),
				'evf_email_subject'         => $this->get_smarttags( isset( $meta['email_subject'] ) ? $meta['email_subject'] : '', $form['form_fields'] ),
				'evf_email_message'         => $this->get_smarttags( isset( $meta['email_message'] ) ? wp_strip_all_tags( $meta['email_message'] ) : '{all_fields}', $form['form_fields'] ),
			);

			$connection_num++;
		}

		return ! empty( $evf_notifications ) ? $evf_notifications : $default;
	}

	/**
	 * Map form-level settings (confirmation, title, redirect, etc.).
	 *
	 * @param array $form       Partially built EVF form array.
	 * @param int   $nf_form_id Ninja Forms form ID.
	 * @param array $actions    Ninja Forms actions array.
	 * @return array
	 */
	private function get_form_settings( $form, $nf_form_id, $actions ) {
		$redirect_to     = 'same';
		$custom_page     = '';
		$external_url    = '';
		$success_message = esc_html__( 'Thanks for contacting us! We will get in touch with you shortly.', 'everest-forms' );

		// Parse success message and redirect from actions.
		foreach ( $actions as $action ) {
			if ( 'successmessage' === $action['type'] && ! empty( $action['active'] ) ) {
				$msg = isset( $action['meta']['success_msg'] ) ? $action['meta']['success_msg'] : '';
				if ( $msg ) {
					$success_message = wp_strip_all_tags( $msg );
				}
			}
			if ( 'redirect' === $action['type'] && ! empty( $action['active'] ) ) {
				$url = isset( $action['meta']['redirect_url'] ) ? $action['meta']['redirect_url'] : '';
				if ( $url ) {
					$redirect_to  = 'external_url';
					$external_url = esc_url_raw( $url );
				}
			}
		}

		$form['settings'] = array(
			'email'                              => apply_filters(
				'evf_fm_' . $this->slug . '_email_notification_settings',
				$this->get_email_notification_settings( $form, $actions ),
				$form,
				$nf_form_id
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
				'form_id'   => absint( $nf_form_id ),
				'form_from' => $this->slug,
			),
		);

		return $form;
	}

	/**
	 * Get mapped form data for the given Ninja Forms IDs.
	 *
	 * @param array $nf_form_ids Array of Ninja Forms IDs to import.
	 * @return array
	 */
	public function get_fm_mapped_form_data( $nf_form_ids ) {
		$nf_forms_data = array();

		foreach ( $nf_form_ids as $nf_form_id ) {
			$nf_form = $this->get_form( $nf_form_id );

			if ( ! $nf_form ) {
				$nf_forms_data[ $nf_form_id ] = false;
				continue;
			}

			$nf_form_name = $nf_form->title;
			$nf_fields    = $this->get_form_fields( $nf_form_id );
			$nf_actions   = $this->get_form_actions( $nf_form_id );

			$unsupported  = array();
			$upgrade_plan = array();
			$upgrade_omit = array();

			$form = array(
				'id'            => '',
				'form_enabled'  => '1',
				'form_field_id' => '',
				'form_fields'   => array(),
				'settings'      => array(
					'form_title' => $nf_form_name,
				),
			);

			if ( empty( $nf_fields ) ) {
				wp_send_json_error(
					array(
						'form_name' => sanitize_text_field( $nf_form_name ),
						'message'   => esc_html__( 'No form fields found.', 'everest-forms' ),
					)
				);
			}

			foreach ( $nf_fields as $nf_field ) {
				$this->map_field( $form, $nf_field, $unsupported, $upgrade_plan, $upgrade_omit );
			}

			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_fields_mapping', $form, $nf_form_id, $nf_form );
			$form = apply_filters(
				'evf_fm_' . $this->slug . '_form_after_settings_mapping',
				$this->get_form_settings( $form, $nf_form_id, $nf_actions ),
				$nf_form_id,
				$nf_form
			);

			$response = $this->import_form( $form, $unsupported, $upgrade_plan, $upgrade_omit );

			$nf_forms_data[ $nf_form_id ] = $response;
		}

		return $nf_forms_data;
	}

	/**
	 * Map a single Ninja Forms field to an EVF field and add it to $form.
	 *
	 * @param array $form         EVF form array (passed by reference).
	 * @param array $nf_field     Ninja Forms field data (id, label, key, type, required, default_value, meta).
	 * @param array $unsupported  Unsupported field labels (passed by reference).
	 * @param array $upgrade_plan Pro-only field labels (passed by reference).
	 * @param array $upgrade_omit No-equivalent field labels (passed by reference).
	 */
	private function map_field( &$form, $nf_field, &$unsupported, &$upgrade_plan, &$upgrade_omit ) {
		$nf_type     = isset( $nf_field['type'] ) ? $nf_field['type'] : '';
		$meta        = isset( $nf_field['meta'] ) ? $nf_field['meta'] : array();
		$label       = isset( $nf_field['label'] ) ? $nf_field['label'] : '';
		$nf_key      = isset( $nf_field['key'] ) ? $nf_field['key'] : '';
		$required    = ! empty( $nf_field['required'] ) ? '1' : '0';
		$default_val = isset( $nf_field['default_value'] ) ? $nf_field['default_value'] : '';
		$placeholder = isset( $meta['placeholder'] ) ? $meta['placeholder'] : '';
		$description = isset( $meta['desc_text'] ) ? $meta['desc_text'] : '';
		$css_class   = isset( $meta['custom_class'] ) ? $meta['custom_class'] : '';

		// Skip non-input structural elements.
		if ( in_array( $nf_type, array( 'submit', 'button', 'html', 'note', 'hr', 'recaptcha', 'recaptchav3', 'hcaptcha', 'turnstile', 'spam' ), true ) ) {
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

		switch ( $nf_type ) {

			// ── Single-line text ──────────────────────────────────────────
			case 'textbox':
			case 'password':
				$type      = 'text';
				$maxlength = isset( $meta['max_length'] ) && '' !== $meta['max_length'] ? absint( $meta['max_length'] ) : 0;
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => $type,
					'label'                          => $label,
					'meta-key'                       => $type . '-' . $nf_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'limit_enabled'                  => $maxlength > 0 ? '1' : '0',
					'limit_count'                    => $maxlength > 0 ? (string) $maxlength : '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'input_mask'                     => isset( $meta['input_mask'] ) ? $meta['input_mask'] : '',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Textarea ──────────────────────────────────────────────────
			case 'textarea':
				$maxlength = isset( $meta['max_length'] ) && '' !== $meta['max_length'] ? absint( $meta['max_length'] ) : 0;
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'textarea',
					'label'                          => $label,
					'meta-key'                       => 'textarea-' . $nf_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => '0',
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Email ─────────────────────────────────────────────────────
			case 'email':
			case 'confirm':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'email',
					'label'                          => $label,
					'meta-key'                       => 'email-' . $nf_key,
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Number ────────────────────────────────────────────────────
			case 'number':
				$min_value = isset( $meta['num_min'] ) && '' !== $meta['num_min'] ? $meta['num_min'] : '';
				$max_value = isset( $meta['num_max'] ) && '' !== $meta['num_max'] ? $meta['num_max'] : '';
				$step      = isset( $meta['num_step'] ) && '' !== $meta['num_step'] ? $meta['num_step'] : '0';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'number',
					'label'                          => $label,
					'meta-key'                       => 'number-' . $nf_key,
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── URL ───────────────────────────────────────────────────────
			case 'url':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'url',
					'label'                          => $label,
					'meta-key'                       => 'url-' . $nf_key,
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Phone ─────────────────────────────────────────────────────
			case 'phone':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'phone-us',
					'label'                          => $label,
					'meta-key'                       => 'phone-us-' . $nf_key,
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Date ──────────────────────────────────────────────────────
			case 'date':
				$date_format = isset( $meta['date_format'] ) ? $meta['date_format'] : 'm/d/Y';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'date-time',
					'label'                          => $label,
					'meta-key'                       => 'date-time-' . $nf_key,
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── First Name ────────────────────────────────────────────────
			case 'firstname':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'first-name',
					'label'                          => $label,
					'meta-key'                       => 'first-name-' . $nf_key,
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Last Name ─────────────────────────────────────────────────
			case 'lastname':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'last-name',
					'label'                          => $label,
					'meta-key'                       => 'last-name-' . $nf_key,
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
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Select / Dropdown ─────────────────────────────────────────
			case 'listselect':
			case 'listmultiselect':
				$is_multiple = ( 'listmultiselect' === $nf_type );
				$evf_choices = $this->build_choices( $meta );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'select',
					'label'                          => $label,
					'meta-key'                       => 'dropdown_-' . $nf_key,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => '0',
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'css'                            => $css_class,
					'placeholder'                    => $placeholder,
					'multiple_choices'               => $is_multiple ? '1' : '0',
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Radio ─────────────────────────────────────────────────────
			case 'listradio':
				$evf_choices = $this->build_choices( $meta );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'radio',
					'label'                          => $label,
					'meta-key'                       => 'radio-' . $nf_key,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => '0',
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'input_columns'                  => '',
					'css'                            => $css_class,
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Checkbox ──────────────────────────────────────────────────
			case 'listcheckbox':
			case 'listimage':
				$evf_choices = $this->build_choices( $meta );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'checkbox',
					'label'                          => $label,
					'meta-key'                       => 'checkbox-' . $nf_key,
					'choices'                        => $evf_choices,
					'description'                    => $description,
					'label_hide'                     => '0',
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'input_columns'                  => '',
					'choice_limit'                   => '',
					'css'                            => $css_class,
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Terms & Conditions ────────────────────────────────────────
			case 'terms':
				$privacy_text = isset( $meta['description'] ) ? wp_strip_all_tags( $meta['description'] ) : '';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ] = array(
					'id'                             => $field_id,
					'type'                           => 'privacy-policy',
					'label'                          => $label ? $label : esc_html__( 'Privacy Policy', 'everest-forms' ),
					'meta-key'                       => 'privacy-policy-' . $nf_key,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => '0',
					'css'                            => $css_class,
					'privacy_text'                   => $privacy_text,
					'nf_key'                         => $nf_key,
				);
				break;

			// ── Unsupported ───────────────────────────────────────────────
			case 'address':
			case 'address2':
			case 'city':
			case 'zip':
			case 'listcountry':
			case 'liststate':
			case 'starrating':
			case 'color':
			case 'repeater':
			case 'signature':
				$unsupported[] = $label ?: $nf_type;
				break;

			// Skip hidden and misc non-input fields silently.
			case 'hidden':
			default:
				break;
		}
	}

	/**
	 * Parse Ninja Forms field options into EVF choices array.
	 *
	 * NF stores options as a JSON string in field_meta with key 'options'.
	 * Each option object: { label, value, selected, calc_value, order }.
	 *
	 * @param array $meta Field meta array.
	 * @return array
	 */
	private function build_choices( $meta ) {
		$evf_choices = array();

		if ( empty( $meta['options'] ) ) {
			return $evf_choices;
		}

		$options = $meta['options'];
		if ( is_string( $options ) ) {
			$options = json_decode( $options, true );
		}

		if ( ! is_array( $options ) ) {
			return $evf_choices;
		}

		foreach ( $options as $option ) {
			$option_label = isset( $option['label'] ) ? $option['label'] : '';
			$option_value = isset( $option['value'] ) ? $option['value'] : $option_label;
			if ( '' === $option_label && '' === $option_value ) {
				continue;
			}
			$choice = array(
				'label' => $option_label,
				'value' => $option_value,
				'image' => '',
			);
			if ( ! empty( $option['selected'] ) ) {
				$choice['default'] = '1';
			}
			$evf_choices[] = $choice;
		}

		return $evf_choices;
	}

	/**
	 * Map a Ninja Forms date format string to the closest EVF equivalent.
	 *
	 * @param string $nf_format NF date format.
	 * @return string
	 */
	private function map_date_format( $nf_format ) {
		$map = array(
			'm/d/Y' => 'm/d/Y',
			'd/m/Y' => 'd/m/Y',
			'Y-m-d' => 'Y-m-d',
			'd-m-Y' => 'd-m-Y',
			'Y/m/d' => 'Y/m/d',
		);
		return isset( $map[ $nf_format ] ) ? $map[ $nf_format ] : 'm/d/Y';
	}

	/**
	 * Migrate form entries from Ninja Forms to EVF.
	 *
	 * Ninja Forms stores entries as the 'nf_sub' CPT; field values are postmeta
	 * with key `_field_{field_id}`.
	 *
	 * @param int $evf_form_id The Everest Forms form ID (destination).
	 * @param int $form_id     The Ninja Forms form ID (source).
	 * @return array Map of NF submission post ID → EVF entry ID.
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

		// Fetch all published submissions for this Ninja Forms form.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$submissions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_author, p.post_date
				 FROM {$wpdb->prefix}posts AS p
				 INNER JOIN {$wpdb->prefix}postmeta AS m
				    ON p.ID = m.post_id AND m.meta_key = '_form_id' AND m.meta_value = %s
				 WHERE p.post_type = 'nf_sub'
				   AND p.post_status = 'publish'
				 ORDER BY p.post_date ASC",
				(string) $form_id
			)
		);

		if ( ! $submissions || ! is_array( $submissions ) ) {
			return $evf_form_entries;
		}

		foreach ( $submissions as $submission ) {
			$sub_id = (int) $submission->ID;

			// Fetch all field values for this submission.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$raw_meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta
					 WHERE post_id = %d AND meta_key LIKE %s",
					$sub_id,
					$wpdb->esc_like( '_field_' ) . '%'
				),
				ARRAY_A
			);

			// Build a map of NF field ID → value.
			$submission_values = array();
			foreach ( $raw_meta as $row ) {
				$nf_field_id                       = str_replace( '_field_', '', $row['meta_key'] );
				$submission_values[ $nf_field_id ] = $row['meta_value'];
			}

			$entry_list = array();

			foreach ( $evf_form_fields as $field_key => $form_field ) {
				$field_type     = $form_field['type'];
				$field_meta_key = $form_field['meta-key'];
				$field_name     = $form_field['label'];
				$nf_key         = isset( $form_field['nf_key'] ) ? $form_field['nf_key'] : '';

				if ( empty( $nf_key ) ) {
					continue;
				}

				// Resolve the NF field ID from the key via DB lookup.
				$nf_field_id = $this->get_nf_field_id_by_key( $nf_key, $form_id );
				if ( ! $nf_field_id || ! isset( $submission_values[ $nf_field_id ] ) ) {
					continue;
				}

				$raw_value = $submission_values[ $nf_field_id ];

				// Some values may be serialized arrays (checkboxes).
				if ( is_string( $raw_value ) && is_serialized( $raw_value ) ) {
					$raw_value = maybe_unserialize( $raw_value );
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
						$entry = array(
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

			$user_id  = (int) $submission->post_author;
			$sub_date = $submission->post_date;

			$entries = array(
				'user_id'         => $user_id,
				'user_device'     => '',
				'user_ip_address' => '',
				'form_id'         => $evf_form_id,
				'referer'         => '',
				'fields'          => wp_json_encode( $entry_list ),
				'status'          => 'publish',
				'viewed'          => '0',
				'starred'         => '0',
				'date_created'    => $sub_date,
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
	 * Look up the numeric Ninja Forms field ID by its key string.
	 *
	 * Results are cached in a static array per (form_id, key) pair.
	 *
	 * @param string $nf_key  The field `key` column value.
	 * @param int    $form_id Ninja Forms form ID.
	 * @return string|false Field ID or false if not found.
	 */
	private function get_nf_field_id_by_key( $nf_key, $form_id ) {
		global $wpdb;

		static $cache = array();

		$cache_key = $form_id . ':' . $nf_key;
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}nf3_fields WHERE parent_id = %d AND `key` = %s LIMIT 1",
				$form_id,
				$nf_key
			)
		);

		$cache[ $cache_key ] = $id ? (string) $id : false;

		return $cache[ $cache_key ];
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

new EVF_Fm_Ninjaforms();
