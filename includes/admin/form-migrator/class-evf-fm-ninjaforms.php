<?php
/**
 * EverestForms Form Migrator Ninja Forms Class
 *
 * @package EverestForms\Admin
 * @since   3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Fm_Ninjaforms class.
 */
class EVF_Fm_Ninjaforms extends EVF_Admin_Form_Migrator {

	/**
	 * Define required properties.
	 *
	 * @since 3.3.0
	 */
	public function init() {
		$this->name = 'Ninja Forms';
		$this->slug = 'ninjaforms';
		$this->path = 'ninja-forms/ninja-forms.php';
	}

	/**
	 * If the importer source is available.
	 *
	 * @since 3.3.0
	 *
	 * @return bool
	 */
	protected function is_active() {
		return is_plugin_active( $this->path );
	}

	/**
	 * Check is the plugin installed or not.
	 *
	 * @since 3.3.0
	 *
	 * @return bool
	 */
	protected function is_installed() {
		return file_exists( trailingslashit( WP_PLUGIN_DIR ) . $this->path );
	}

	/**
	 * Get all Ninja Forms forms.
	 *
	 * @since 3.3.0
	 *
	 * @return array Map of form_id => form_title.
	 */
	public function get_forms() {
		global $wpdb;

		$forms  = $wpdb->get_results( "SELECT `id`, `title` FROM `{$wpdb->prefix}nf3_forms` ORDER BY `title`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = array();

		foreach ( $forms as $form ) {
			$result[ $form->id ] = $form->title;
		}

		return $result;
	}

	/**
	 * Get a single Ninja Forms form row.
	 *
	 * @since 3.3.0
	 *
	 * @param int $id NF form ID.
	 * @return object|null
	 */
	public function get_form( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}nf3_forms` WHERE `id` = %d", absint( $id ) )
		);
	}

	/**
	 * Fetch all fields for a form, merged with their meta settings.
	 *
	 * @since 3.3.0
	 *
	 * @param int $form_id NF form ID.
	 * @return array Keyed by NF field ID.
	 */
	private function get_nf_fields( $form_id ) {
		global $wpdb;

		$fields = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}nf3_fields` WHERE `parent_id` = %d ORDER BY `order` ASC",
				absint( $form_id )
			)
		);

		if ( empty( $fields ) ) {
			return array();
		}

		$field_ids    = array_map( 'intval', wp_list_pluck( $fields, 'id' ) );
		$placeholders = implode( ',', array_fill( 0, count( $field_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `parent_id`, `key`, `value` FROM `{$wpdb->prefix}nf3_field_meta` WHERE `parent_id` IN ($placeholders)",
				...$field_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$meta = array();
		foreach ( $meta_rows as $row ) {
			$meta[ $row->parent_id ][ $row->key ] = $row->value;
		}

		$result = array();
		foreach ( $fields as $field ) {
			$field_meta           = isset( $meta[ $field->id ] ) ? $meta[ $field->id ] : array();
			$result[ $field->id ] = array(
				'id'            => $field->id,
				'type'          => $field->type,
				'label'         => $field->label,
				'key'           => $field->key,
				'order'         => $field->order,
				'required'      => $field->required,
				'default_value' => isset( $field->default_value ) ? $field->default_value : '',
				'label_pos'     => isset( $field->label_pos ) ? $field->label_pos : '',
				'meta'          => $field_meta,
			);
		}

		return $result;
	}

	/**
	 * Fetch the admin notification email action meta for a form.
	 *
	 * NF forms can have multiple email actions: one for admin notification
	 * (to = static address) and one for user confirmation (to = {field:key}).
	 * We prefer the action whose "to" meta is a static email address.
	 *
	 * @since 3.3.0
	 *
	 * @param int $form_id NF form ID.
	 * @return array Meta key => value pairs.
	 */
	private function get_nf_email_action( $form_id ) {
		global $wpdb;

		// Fetch all active email actions for this form.
		$actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `id` FROM `{$wpdb->prefix}nf3_actions`
				WHERE `parent_id` = %d AND `type` = 'email' AND `active` = 1
				ORDER BY `id` ASC",
				absint( $form_id )
			)
		);

		if ( empty( $actions ) ) {
			return array();
		}

		$all_meta     = array();
		$notification = array();

		foreach ( $actions as $action ) {
			$meta_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `key`, `value` FROM `{$wpdb->prefix}nf3_action_meta` WHERE `parent_id` = %d",
					absint( $action->id )
				)
			);

			$meta = array();
			foreach ( $meta_rows as $row ) {
				$meta[ $row->key ] = $row->value;
			}

			$all_meta[] = $meta;

			// Prefer the action whose "to" is a static address (not a field reference).
			// A field reference looks like {field:key} — that is the user confirmation email.
			$to = isset( $meta['to'] ) ? $meta['to'] : '';
			if ( empty( $notification ) && ! preg_match( '/\{[a-z_]+:[^}]+\}/', $to ) ) {
				$notification = $meta;
			}
		}

		// Fall back to the first action if every "to" was a field reference.
		return ! empty( $notification ) ? $notification : $all_meta[0];
	}

	/**
	 * Convert Ninja Forms merge tags to EVF smart tags.
	 *
	 * @since 3.3.0
	 *
	 * @param string $string Text containing NF merge tags.
	 * @return string
	 */
	private function get_smarttags( $string ) {
		if ( empty( $string ) ) {
			return $string;
		}

		// Map known NF system tags to EVF equivalents.
		$string = str_replace(
			array(
				'{wp:user_ip}',
				'{wp:page_title}',
				'{wp:page_url}',
				'{wp:user_first_name}',
				'{wp:user_last_name}',
				'{wp:user_email}',
				'{wp:admin_email}',
				'{wp:site_name}',
				'{wp:site_url}',
				'{all_fields_table}',
				'{all_fields}',
			),
			array(
				'{user_ip_address}',
				'{post_title}',
				'{page_url}',
				'{first_name}',
				'{last_name}',
				'{user_email}',
				'{admin_email}',
				'{site_title}',
				'{site_url}',
				'{all_fields}',
				'{all_fields}',
			),
			$string
		);

		// Strip any remaining NF field-reference tags ({field:key}, {calc:key}, etc.)
		// that cannot be converted without a full field-key → EVF-ID lookup.
		$string = preg_replace( '/\{[a-z_]+:[^}]+\}/', '', $string );

		return $string;
	}

	/**
	 * Build EVF email notification settings from NF email action meta.
	 *
	 * @since 3.3.0
	 *
	 * @param int $form_id NF form ID.
	 * @return array
	 */
	private function get_email_notification_settings( $form_id ) {
		$meta = $this->get_nf_email_action( $form_id );

		// NF stores the "to" address as a field-reference tag (e.g. {field:email}).
		// That is not a valid static address in EVF, so fall back to the site admin email.
		$raw_to  = isset( $meta['to'] ) ? $meta['to'] : '';
		$to_email = ( empty( $raw_to ) || preg_match( '/\{[a-z_]+:[^}]+\}/', $raw_to ) )
			? get_option( 'admin_email' )
			: $this->get_smarttags( $raw_to );

		return array(
			'connection_1' => array(
				'enable_email_notification' => '1',
				'connection_name'           => esc_html__( 'Admin Notification', 'everest-forms' ),
				'evf_to_email'              => $to_email,
				'evf_from_name'             => $this->get_smarttags( isset( $meta['from_name'] ) ? $meta['from_name'] : get_option( 'blogname' ) ),
				'evf_from_email'            => $this->get_smarttags( isset( $meta['from_address'] ) ? $meta['from_address'] : get_option( 'admin_email' ) ),
				'evf_reply_to'              => $this->get_smarttags( isset( $meta['reply_to'] ) ? $meta['reply_to'] : '' ),
				'evf_email_subject'         => $this->get_smarttags( isset( $meta['email_subject'] ) ? $meta['email_subject'] : esc_html__( 'New Form Entry', 'everest-forms' ) ),
				'evf_email_message'         => $this->get_smarttags( isset( $meta['email_message'] ) ? $meta['email_message'] : '{all_fields}' ),
			),
		);
	}

	/**
	 * Populate EVF form-level settings from NF form data.
	 *
	 * @since 3.3.0
	 *
	 * @param array  $form    EVF form array.
	 * @param object $nf_form NF form row from nf3_forms.
	 * @return array
	 */
	private function get_form_settings( $form, $nf_form ) {
		$form['settings'] = array(
			'email'                              => $this->get_email_notification_settings( $nf_form->id ),
			'form_title'                         => $nf_form->title,
			'form_description'                   => '',
			'form_disable_message'               => esc_html__( 'This form is disabled.', 'everest-forms' ),
			'successful_form_submission_message' => esc_html__( 'Thanks for contacting us! We will be in touch with you shortly.', 'everest-forms' ),
			'submission_message_scroll'          => '1',
			'redirect_to'                        => 'same',
			'custom_page'                        => '',
			'external_url'                       => '',
			'enable_redirect_query_string'       => '0',
			'query_string'                       => '',
			'layout_class'                       => 'default',
			'form_class'                         => '',
			'submit_button_text'                 => esc_html__( 'Submit', 'everest-forms' ),
			'submit_button_processing_text'      => esc_html__( 'Processing…', 'everest-forms' ),
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
				'form_id'   => absint( $nf_form->id ),
				'form_from' => $this->slug,
			),
		);

		return $form;
	}

	/**
	 * Parse NF list field options from serialized meta.
	 *
	 * @since 3.3.0
	 *
	 * @param array $meta Field meta.
	 * @return array EVF-format choices array.
	 */
	private function get_list_choices( $meta ) {
		$choices = array();
		$raw     = isset( $meta['options'] ) ? $meta['options'] : '';

		if ( empty( $raw ) ) {
			return $choices;
		}

		$options = maybe_unserialize( $raw );

		if ( ! is_array( $options ) ) {
			return $choices;
		}

		foreach ( $options as $option ) {
			$choices[] = array(
				'label'   => isset( $option['label'] ) ? $option['label'] : '',
				'value'   => isset( $option['value'] ) ? $option['value'] : '',
				'image'   => '',
				'default' => ( isset( $option['selected'] ) && $option['selected'] ) ? '1' : '',
			);
		}

		return $choices;
	}

	/**
	 * Convert a NF date format string to the closest EVF equivalent.
	 *
	 * @since 3.3.0
	 *
	 * @param string $nf_format NF date format string.
	 * @return string PHP date() format string.
	 */
	private function map_date_format( $nf_format ) {
		$map = array(
			'MM/DD/YYYY' => 'm/d/Y',
			'MM-DD-YYYY' => 'm-d-Y',
			'MM.DD.YYYY' => 'm.d.Y',
			'DD/MM/YYYY' => 'd/m/Y',
			'DD-MM-YYYY' => 'd-m-Y',
			'DD.MM.YYYY' => 'd.m.Y',
			'YYYY-MM-DD' => 'Y-m-d',
			'YYYY/MM/DD' => 'Y/m/d',
			'YYYY.MM.DD' => 'Y.m.d',
		);

		return isset( $map[ $nf_format ] ) ? $map[ $nf_format ] : 'm/d/Y';
	}

	/**
	 * Map a single NF field to an EVF field and append it to $form.
	 *
	 * @since 3.3.0
	 *
	 * @param array $form        EVF form array (passed by reference).
	 * @param array $nf_field    NF field data.
	 * @param array $unsupported Unsupported field labels (passed by reference).
	 */
	private function map_field( &$form, $nf_field, &$unsupported ) {
		$type        = $nf_field['type'];
		$label       = $nf_field['label'];
		$meta        = $nf_field['meta'];
		$nf_id       = $nf_field['id'];
		$required    = $nf_field['required'] ? '1' : '0';
		$default_val = $nf_field['default_value'];
		$placeholder = isset( $meta['placeholder'] ) ? $meta['placeholder'] : '';
		$description = isset( $meta['desc_text'] ) ? $meta['desc_text'] : '';
		$css_class   = isset( $meta['container_class'] ) ? $meta['container_class'] : '';
		$label_hide  = ( 'hidden' === $nf_field['label_pos'] ) ? '1' : '0';

		// Allocate a new field ID slot.
		if ( ! empty( $form['form_field_id'] ) ) {
			$form_field_id = absint( $form['form_field_id'] );
			++$form['form_field_id'];
		} else {
			$form_field_id         = 0;
			$form['form_field_id'] = '1';
		}

		$field_id = evf_get_random_string() . '-' . $form_field_id;

		switch ( $type ) {

			// ── Text / Address sub-fields ──────────────────────────────────
			case 'textbox':
			case 'address':
			case 'address2':
			case 'city':
			case 'zip':
			case 'state':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'text',
					'label'                          => $label,
					'meta-key'                       => 'text-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $this->get_smarttags( $default_val ),
					'css'                            => $css_class,
					'limit_enabled'                  => '0',
					'limit_count'                    => '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'input_mask'                     => '',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Textarea ───────────────────────────────────────────────────
			case 'textarea':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'textarea',
					'label'                          => $label,
					'meta-key'                       => 'textarea-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $this->get_smarttags( $default_val ),
					'css'                            => $css_class,
					'limit_enabled'                  => '0',
					'limit_count'                    => '1',
					'limit_mode'                     => 'characters',
					'min_length_count'               => '1',
					'min_length_mode'                => 'characters',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Email ──────────────────────────────────────────────────────
			case 'email':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'email',
					'label'                          => $label,
					'meta-key'                       => 'email-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $this->get_smarttags( $default_val ),
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Number ─────────────────────────────────────────────────────
			case 'number':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'number',
					'label'                          => $label,
					'meta-key'                       => 'number-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $default_val,
					'css'                            => $css_class,
					'step'                           => '0',
					'min_value'                      => isset( $meta['num_min'] ) ? $meta['num_min'] : '',
					'max_value'                      => isset( $meta['num_max'] ) ? $meta['num_max'] : '',
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Phone ──────────────────────────────────────────────────────
			case 'phone':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'phone',
					'label'                          => $label,
					'meta-key'                       => 'phone-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $default_val,
					'phone_format'                   => 'smart',
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── URL ────────────────────────────────────────────────────────
			case 'url':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'url',
					'label'                          => $label,
					'meta-key'                       => 'url-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $this->get_smarttags( $default_val ),
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Password ───────────────────────────────────────────────────
			case 'password':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'password',
					'label'                          => $label,
					'meta-key'                       => 'password-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── First Name ─────────────────────────────────────────────────
			case 'firstname':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'first-name',
					'label'                          => $label,
					'meta-key'                       => 'first-name-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $this->get_smarttags( $default_val ),
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Last Name ──────────────────────────────────────────────────
			case 'lastname':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'last-name',
					'label'                          => $label,
					'meta-key'                       => 'last-name-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'default_value'                  => $this->get_smarttags( $default_val ),
					'css'                            => $css_class,
					'regex_value'                    => '',
					'regex_message'                  => esc_html__( 'Please provide a valid value for this field.', 'everest-forms' ),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Hidden ─────────────────────────────────────────────────────
			case 'hidden':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'hidden',
					'label'                          => $label,
					'meta-key'                       => 'hidden-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => '1',
					'default_value'                  => $this->get_smarttags( $default_val ),
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Select / Dropdown ──────────────────────────────────────────
			case 'listselect':
			case 'liststate':
				$choices = $this->get_list_choices( $meta );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'select',
					'label'                          => $label,
					'meta-key'                       => 'dropdown_-' . $nf_id,
					'choices'                        => $choices,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'multiple_choices'               => '0',
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Multi-select ───────────────────────────────────────────────
			case 'listmultiselect':
				$choices = $this->get_list_choices( $meta );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'select',
					'label'                          => $label,
					'meta-key'                       => 'dropdown_-' . $nf_id,
					'choices'                        => $choices,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'multiple_choices'               => '1',
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Radio ──────────────────────────────────────────────────────
			case 'listradio':
				$choices = $this->get_list_choices( $meta );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'radio',
					'label'                          => $label,
					'meta-key'                       => 'radio-' . $nf_id,
					'choices'                        => $choices,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'input_columns'                  => '',
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Checkbox list ──────────────────────────────────────────────
			case 'listcheckbox':
				$choices = $this->get_list_choices( $meta );
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'checkbox',
					'label'                          => $label,
					'meta-key'                       => 'checkbox-' . $nf_id,
					'choices'                        => $choices,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'input_columns'                  => '',
					'choice_limit'                   => '',
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Single Checkbox ────────────────────────────────────────────
			case 'checkbox':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'checkbox',
					'label'                          => $label,
					'meta-key'                       => 'checkbox-' . $nf_id,
					'choices'                        => array(
						array(
							'label' => isset( $meta['checked_label'] ) ? $meta['checked_label'] : $label,
							'value' => '1',
							'image' => '',
						),
					),
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'input_columns'                  => '',
					'choice_limit'                   => '',
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Date ───────────────────────────────────────────────────────
			case 'date':
				$date_format = isset( $meta['date_format'] ) ? $this->map_date_format( $meta['date_format'] ) : 'm/d/Y';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'date-time',
					'label'                          => $label,
					'meta-key'                       => 'date-time-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'datetime_format'                => 'date',
					'datetime_style'                 => 'picker',
					'date_format'                    => $date_format,
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
					'min_time_hour'                  => '00',
					'min_time_minute'                => '00',
					'max_time_hour'                  => '23',
					'max_time_minute'                => '59',
					'nf_id'                          => $nf_id,
				);
				break;

			// ── File Upload ────────────────────────────────────────────────
			case 'file':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'file-upload',
					'label'                          => $label,
					'meta-key'                       => 'file-upload-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'max_file_size'                  => '',
					'max_file_number'                => '',
					'allowed_file_extensions'        => '',
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Image upload (NF image field) ──────────────────────────────
			case 'image':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'image-upload',
					'label'                          => $label,
					'meta-key'                       => 'image-upload-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Signature ──────────────────────────────────────────────────
			case 'signature':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'signature',
					'label'                          => $label,
					'meta-key'                       => 'signature-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Star Rating ────────────────────────────────────────────────
			case 'starrating':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'rating',
					'label'                          => $label,
					'meta-key'                       => 'rating-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── HTML / Note ────────────────────────────────────────────────
			case 'html':
			case 'note':
				$html_code = isset( $meta['default'] ) ? $meta['default'] : '';
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'    => $field_id,
					'type'  => 'html',
					'code'  => $html_code,
					'css'   => $css_class,
					'nf_id' => $nf_id,
				);
				break;

			// ── Divider (hr) ───────────────────────────────────────────────
			case 'hr':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'           => $field_id,
					'type'         => 'divider',
					'divider_type' => 'default',
					'css'          => $css_class,
					'nf_id'        => $nf_id,
				);
				break;

			// ── Country ────────────────────────────────────────────────────
			case 'listcountry':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'country',
					'label'                          => $label,
					'meta-key'                       => 'country-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'enable_country_flag'            => '0',
					'default'                        => array(),
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Privacy Policy / Terms ─────────────────────────────────────
			case 'terms':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'privacy-policy',
					'label'                          => $label,
					'meta-key'                       => 'privacy-policy-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Color Picker ───────────────────────────────────────────────
			case 'color':
				$form['structure'][ 'row_' . $form['form_field_id'] ]['grid_1'][] = $field_id;
				$form['form_fields'][ $field_id ]                                 = array(
					'id'                             => $field_id,
					'type'                           => 'color',
					'label'                          => $label,
					'meta-key'                       => 'color-' . $nf_id,
					'description'                    => $description,
					'required'                       => $required,
					'required_field_message_setting' => 'global',
					'required-field-message'         => '',
					'placeholder'                    => $placeholder,
					'label_hide'                     => $label_hide,
					'css'                            => $css_class,
					'nf_id'                          => $nf_id,
				);
				break;

			// ── Unsupported ────────────────────────────────────────────────
			default:
				$unsupported[] = $label ?: $type;
				break;
		}
	}

	/**
	 * Map NF form data to EVF format and import each form.
	 *
	 * @since 3.3.0
	 *
	 * @param array $nf_form_ids List of NF form IDs to migrate.
	 * @return array Map of NF form ID => import response.
	 */
	public function get_fm_mapped_form_data( $nf_form_ids ) {
		$nf_forms_data = array();

		foreach ( $nf_form_ids as $nf_form_id ) {
			$nf_form = $this->get_form( $nf_form_id );

			if ( ! $nf_form ) {
				$nf_forms_data[ $nf_form_id ] = false;
				continue;
			}

			$unsupported  = array();
			$upgrade_plan = array();
			$upgrade_omit = array();

			$form = array(
				'id'            => '',
				'form_enabled'  => '1',
				'form_field_id' => '',
				'form_fields'   => array(),
				'settings'      => array(),
			);

			$nf_fields = $this->get_nf_fields( $nf_form_id );

			if ( empty( $nf_fields ) ) {
				wp_send_json_error(
					array(
						'form_name' => sanitize_text_field( $nf_form->title ),
						'message'   => esc_html__( 'No form fields found.', 'everest-forms' ),
					)
				);
			}

			foreach ( $nf_fields as $nf_field ) {
				$this->map_field( $form, $nf_field, $unsupported );
			}

			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_fields_mapping', $form, $nf_form_id, $nf_form );
			$form = $this->get_form_settings( $form, $nf_form );
			$form = apply_filters( 'evf_fm_' . $this->slug . '_form_after_settings_mapping', $form, $nf_form_id, $nf_form );

			$response = $this->import_form( $form, $unsupported, $upgrade_plan, $upgrade_omit );

			$nf_forms_data[ $nf_form_id ] = $response;
		}

		return $nf_forms_data;
	}

	/**
	 * Migrate form entries from Ninja Forms to EVF.
	 *
	 * @since 3.3.0
	 *
	 * @param int $evf_form_id EVF form ID (destination).
	 * @param int $form_id     NF form ID (source).
	 * @return array Map of NF submission post ID => EVF entry ID.
	 */
	public function migrate_entry( $evf_form_id, $form_id ) {
		$form_data = evf()->form->get(
			absint( $evf_form_id ),
			array( 'content_only' => true )
		);

		if ( empty( $form_data ) || empty( $form_data['form_fields'] ) ) {
			return array();
		}

		$evf_form_fields  = $form_data['form_fields'];
		$evf_form_entries = array();

		// NF submissions are stored as a custom post type.
		$submissions = get_posts(
			array(
				'post_type'      => 'nf_sub',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_key'       => '_form_id',   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => absint( $form_id ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		);

		if ( empty( $submissions ) ) {
			return $evf_form_entries;
		}

		foreach ( $submissions as $submission ) {
			$entry_list = array();

			foreach ( $evf_form_fields as $field_key => $evf_field ) {
				$field_type     = $evf_field['type'];
				$field_meta_key = $evf_field['meta-key'];
				$field_name     = $evf_field['label'];
				$nf_field_id    = isset( $evf_field['nf_id'] ) ? $evf_field['nf_id'] : null;

				// Non-input layout fields carry no submission value.
				if ( in_array( $field_type, array( 'html', 'divider' ), true ) ) {
					continue;
				}

				if ( ! $nf_field_id ) {
					continue;
				}

				$raw_value = get_post_meta( $submission->ID, '_field_' . $nf_field_id, true );

				if ( '' === $raw_value || false === $raw_value ) {
					continue;
				}

				$entry = array();

				switch ( $field_type ) {

					case 'radio':
						$entry['id']        = $field_key;
						$entry['type']      = $field_type;
						$entry['meta_key']  = $field_meta_key;
						$entry['value']     = array(
							'name'  => $field_name,
							'type'  => $field_type,
							'label' => is_array( $raw_value ) ? implode( ', ', $raw_value ) : $raw_value,
						);
						$entry['value_raw'] = wp_json_encode( $raw_value );
						break;

					case 'checkbox':
						$values             = is_array( $raw_value ) ? $raw_value : array( $raw_value );
						$entry['id']        = $field_key;
						$entry['type']      = $field_type;
						$entry['meta_key']  = $field_meta_key;
						$entry['value']     = array(
							'name'  => $field_name,
							'type'  => $field_type,
							'label' => $values,
						);
						$entry['value_raw'] = wp_json_encode( $values );
						break;

					case 'select':
						$values             = is_array( $raw_value ) ? $raw_value : array( $raw_value );
						$entry['id']        = $field_key;
						$entry['type']      = $field_type;
						$entry['meta_key']  = $field_meta_key;
						$entry['name']      = $field_name;
						$entry['value']     = $values;
						$entry['value_raw'] = $values;
						break;

					case 'country':
						$entry['id']       = $field_key;
						$entry['type']     = $field_type;
						$entry['meta_key'] = $field_meta_key;
						$entry['name']     = $field_name;
						$entry['value']    = is_string( $raw_value ) ? $raw_value : '';
						break;

					default:
						$entry['id']       = $field_key;
						$entry['type']     = $field_type;
						$entry['meta_key'] = $field_meta_key;
						$entry['name']     = $field_name;
						$entry['value']    = is_array( $raw_value ) ? implode( ', ', $raw_value ) : $raw_value;
						break;
				}

				if ( ! empty( $entry ) ) {
					$entry_list[ $field_key ] = $entry;
				}
			}

			$entries = array(
				'user_id'         => $submission->post_author,
				'user_device'     => '',
				'user_ip_address' => get_post_meta( $submission->ID, '_ip_address', true ),
				'form_id'         => $evf_form_id,
				'referer'         => get_post_meta( $submission->ID, '_referer', true ),
				'fields'          => wp_json_encode( $entry_list ),
				'status'          => 'publish',
				'viewed'          => 0,
				'starred'         => 0,
				'date_created'    => $submission->post_date,
			);

			if ( $this->check_token_column() ) {
				$entries['token'] = null;
			}

			$entry_id = $this->save_migrated_entry( $entries, $entry_list, $form_data );

			$evf_form_entries[ $submission->ID ] = $entry_id;
		}

		return $evf_form_entries;
	}

	/**
	 * Check whether the EVF entries table has the token column (Save & Continue addon).
	 *
	 * @return bool
	 */
	public function check_token_column() {
		return defined( 'EVF_SAVE_AND_CONTINUE_VERSION' );
	}
}

new EVF_Fm_Ninjaforms();
