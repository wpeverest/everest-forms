<?php
/**
 * EVF AI Form Builder — transforms gateway AI response into full EVF form structure
 * and inserts it as a WordPress post.
 *
 * Gateway returns a clean intermediate format (type, label, options, etc.).
 * This class handles ALL EVF-specific boilerplate so the gateway stays simple.
 */

defined( 'ABSPATH' ) || exit;

class EVF_AI_Form_Builder {

	/** Pro-only field types — visible in builder but locked for free users. */
	public static $pro_fields = [
		'password', 'color', 'range-slider', 'signature', 'repeater',
		'lookup', 'progress',
		'payment-single', 'payment-checkbox', 'payment-radio',
		'payment-quantity', 'payment-subtotal', 'payment-total',
		'payment-coupon', 'credit-card', 'payment-square',
		'payment-authorize-net', 'payment-subscription-plan',
	];

	/**
	 * Create a new EVF form from the AI gateway response.
	 * Saved as DRAFT — user must click "Use This Form" to publish.
	 *
	 * @param array $ai_response  Decoded JSON from /evf-ai/v1/generate
	 * @return int|WP_Error  New form post ID on success, WP_Error on failure.
	 */
	public static function create_form( array $ai_response ) {
		$title  = sanitize_text_field( $ai_response['form_title'] ?? __( 'AI Generated Form', 'everest-forms' ) );
		$fields = $ai_response['fields'] ?? [];

		if ( empty( $fields ) ) {
			return new WP_Error( 'no_fields', __( 'No fields were generated.', 'everest-forms' ) );
		}

		remove_all_filters( 'content_save_pre' );

		// Saved as DRAFT — becomes active only when user clicks "Use This Form"
		$post_id = wp_insert_post( [
			'post_title'   => $title,
			'post_type'    => 'everest_form',
			'post_status'  => 'draft',
			'post_content' => '{}',
		] );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$form_data = self::build_form_data( $post_id, $ai_response );

		wp_update_post( [
			'ID'           => $post_id,
			'post_content' => evf_encode( $form_data ),
		] );

		return $post_id;
	}

	/**
	 * Rebuild an existing (draft) AI form in place from a refined AI response.
	 * Keeps the same form id/status so the preview and builder stay in sync.
	 *
	 * @param int   $form_id     Existing draft form id.
	 * @param array $ai_response Refined AI form schema.
	 * @return int|WP_Error      The form id on success.
	 */
	public static function update_form( int $form_id, array $ai_response ) {
		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return new WP_Error( 'invalid_form', __( 'Form not found.', 'everest-forms' ) );
		}

		$fields = $ai_response['fields'] ?? [];
		if ( empty( $fields ) ) {
			return new WP_Error( 'no_fields', __( 'No fields were generated.', 'everest-forms' ) );
		}

		remove_all_filters( 'content_save_pre' );

		$title     = sanitize_text_field( $ai_response['form_title'] ?? get_the_title( $form_id ) );
		$form_data = self::build_form_data( $form_id, $ai_response );

		wp_update_post( [
			'ID'           => $form_id,
			'post_title'   => $title,
			'post_content' => evf_encode( $form_data ),
		] );

		return $form_id;
	}

	/**
	 * Publish a draft AI form — called when user clicks "Use This Form".
	 *
	 * @param int $form_id
	 * @return bool
	 */
	public static function activate_form( int $form_id ): bool {
		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return false;
		}

		return (bool) wp_update_post( [
			'ID'          => $form_id,
			'post_status' => 'publish',
		] );
	}

	/**
	 * Return summary of fields in a form for the preview modal.
	 *
	 * @param int $form_id
	 * @return array  [ [ label, type, is_pro ], ... ]
	 */
	public static function get_field_summary( int $form_id ): array {
		$post = get_post( $form_id );
		if ( ! $post ) {
			return [];
		}

		$data    = evf_decode( $post->post_content );
		$fields  = $data['form_fields'] ?? [];
		$summary = [];

		foreach ( $fields as $field ) {
			$type = $field['type'] ?? '';
			if ( in_array( $type, [ 'html', 'title', 'divider', 'hidden' ], true ) ) {
				continue; // skip non-input fields from preview list
			}
			$summary[] = [
				'label'  => $field['label'] ?? ucfirst( $type ),
				'type'   => $type,
				'is_pro' => in_array( $type, self::$pro_fields, true ),
			];
		}

		return $summary;
	}

	// ── Form data builder ─────────────────────────────────────────────────────

	/**
	 * Field types that are narrow enough to share a row (2-column layout).
	 * Everything else gets a full-width row.
	 */
	private static $narrow_types = [
		'text', 'first-name', 'last-name', 'email', 'phone',
		'number', 'url', 'date-time', 'select', 'country',
		'hidden', 'yes-no', 'rating', 'color', 'range-slider',
	];

	/**
	 * Forced pairs — if the current field type is a key and the NEXT field
	 * type is the value, always put them on the same row regardless of position.
	 */
	private static $forced_pairs = [
		'first-name' => 'last-name',
		'last-name'  => 'first-name',
	];

	private static function build_form_data( int $form_id, array $ai ): array {
		$built_fields   = [];
		$email_field_id = null;

		// Build all field objects first so we can look ahead for smart pairing
		$field_list = [];
		foreach ( ( $ai['fields'] ?? [] ) as $ai_field ) {
			$field_id  = self::generate_field_id();
			$evf_field = self::build_field( $field_id, $ai_field );
			if ( ! $evf_field ) {
				continue;
			}
			$built_fields[ $field_id ] = $evf_field;
			if ( 'email' === $evf_field['type'] && null === $email_field_id ) {
				$email_field_id = $field_id;
			}
			$field_list[] = [ 'id' => $field_id, 'type' => $evf_field['type'] ];
		}

		$structure = self::build_structure( $field_list );

		return [
			'id'             => $form_id,
			'form_field_id'  => (string) count( $built_fields ),
			'form_enabled'   => '1',
			'form_fields'    => $built_fields,
			'settings'       => self::build_settings( $ai, $email_field_id ),
			'structure'      => $structure,
		];
	}

	/**
	 * Build the structure object with smart 2-column grouping.
	 *
	 * Rules (in priority order):
	 *   1. first-name + last-name  → always same row
	 *   2. Two consecutive narrow fields → same row
	 *   3. Everything else (textarea, address, file-upload, etc.) → full-width row
	 */
	private static function build_structure( array $field_list ): array {
		$structure = [];
		$row       = 1;
		$i         = 0;
		$total     = count( $field_list );

		while ( $i < $total ) {
			$current = $field_list[ $i ];
			$next    = $field_list[ $i + 1 ] ?? null;

			$is_narrow      = in_array( $current['type'], self::$narrow_types, true );
			$next_is_narrow = $next && in_array( $next['type'], self::$narrow_types, true );

			// Forced pair (first-name ↔ last-name)
			$forced_next_type = self::$forced_pairs[ $current['type'] ] ?? null;
			$is_forced_pair   = $forced_next_type && $next && $next['type'] === $forced_next_type;

			if ( $is_forced_pair || ( $is_narrow && $next_is_narrow ) ) {
				// Two columns
				$structure[ 'row_' . $row ] = [
					'grid_1' => [ $current['id'] ],
					'grid_2' => [ $next['id'] ],
				];
				$i   += 2;
			} else {
				// Full width
				$structure[ 'row_' . $row ] = [
					'grid_1' => [ $current['id'] ],
				];
				$i++;
			}

			$row++;
		}

		return $structure;
	}

	// ── Field builder ─────────────────────────────────────────────────────────

	private static function build_field( string $field_id, array $ai_field ): ?array {
		$type  = sanitize_key( $ai_field['type'] ?? '' );
		$label = sanitize_text_field( $ai_field['label'] ?? ucfirst( $type ) );

		if ( ! $type ) {
			return null;
		}

		// Normalize: gateway may return `html` with reset-button HTML when user
		// asks for a reset button. Convert to the proper EVF `reset` type and
		// clear the raw description so it doesn't leak as display text.
		if ( 'html' === $type ) {
			$raw_desc = $ai_field['description'] ?? '';
			if ( preg_match( '/<button[^>]+type=["\']?reset["\']?/i', $raw_desc ) ) {
				$type                        = 'reset';
				$label                       = $label ?: __( 'Reset', 'everest-forms' );
				$ai_field['description']     = '';
			}
		}

		// Base keys every field has
		$field = [
			'id'                             => $field_id,
			'type'                           => $type,
			'label'                          => $label,
			'meta-key'                       => self::generate_meta_key( $label, $field_id ),
			'required'                       => ! empty( $ai_field['required'] ) ? '1' : '',
			'required_field_message_setting' => 'global',
			'required-field-message'         => '',
			'label_hide'                     => '0',
			'description'                    => sanitize_text_field( $ai_field['description'] ?? '' ),
			'css'                            => '',
		];

		// Placeholder (not all field types use it)
		if ( ! empty( $ai_field['placeholder'] ) ) {
			$field['placeholder'] = sanitize_text_field( $ai_field['placeholder'] );
		}

		// Type-specific additions
		switch ( $type ) {
			case 'text':
			case 'first-name':
			case 'last-name':
			case 'url':
			case 'number':
				$field['default_value']     = '';
				$field['limit_enabled']     = '0';
				$field['limit_count']       = '100';
				$field['limit_mode']        = 'characters';
				$field['min_length_enabled']= '0';
				$field['min_length_count']  = '1';
				$field['min_length_mode']   = 'characters';
				$field['input_mask']        = '';
				break;

			case 'textarea':
				$field['default_value'] = '';
				$field['limit_enabled'] = '0';
				$field['limit_count']   = '500';
				$field['limit_mode']    = 'characters';
				break;

			case 'email':
				$field['default_value']              = '';
				$field['confirmation_placeholder']   = '';
				$field['sublabel_hide']              = '';
				break;

			case 'phone':
				$field['default_value'] = '';
				$field['phone_format']  = 'smart';
				$field['input_mask']    = '';
				break;

			case 'date-time':
				$field['datetime_format']  = 'mm/dd/yyyy';
				$field['datetime_style']   = 'picker';
				$field['date_format']      = 'mm/dd/yyyy';
				$field['date_localization']= 'en';
				$field['date_mode']        = 'single';
				$field['time_format']      = '12';
				$field['time_interval']    = '1';
				break;

			case 'checkbox':
			case 'radio':
				$field['choices']       = self::build_choices( $ai_field['options'] ?? [ 'Option 1', 'Option 2' ] );
				$field['input_columns'] = '';
				$field['randomize']     = '0';
				$field['show_values']   = '0';
				$field['choices_images']= '0';
				if ( 'checkbox' === $type ) {
					$field['select_all']  = '0';
					$field['choice_limit']= '';
				}
				break;

			case 'select':
				$field['choices']         = self::build_choices( $ai_field['options'] ?? [ 'Option 1', 'Option 2' ] );
				$field['placeholder']     = $ai_field['placeholder'] ?? __( 'Select an option', 'everest-forms' );
				$field['enhanced_select'] = '0';
				$field['multiple_choices']= '0';
				$field['show_values']     = '0';
				break;

			case 'address':
				$field += self::address_sublabels();
				break;

			case 'file-upload':
				$field['extensions']   = 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx';
				$field['max_size']     = '10';
				$field['max_file_number'] = '1';
				$field['upload_message'] = __( 'Drop files here or click to upload', 'everest-forms' );
				$field['media_library'] = '0';
				break;

			case 'image-upload':
				$field['extensions'] = 'jpg,jpeg,png,gif';
				$field['max_size']   = '5';
				$field['max_file_number'] = '1';
				break;

			case 'rating':
				$field['number_of_stars'] = '5';
				$field['icon']            = 'star';
				$field['icon_size']       = 'medium';
				$field['icon_color']      = '#f4b942';
				break;

			case 'hidden':
				$field['default_value'] = '';
				break;

			case 'captcha':
				$field['format']   = 'math';
				$field['required'] = '1';
				break;

			case 'reset':
				$field['button_text'] = $label ?: __( 'Reset', 'everest-forms' );
				break;

			case 'html':
			case 'title':
			case 'divider':
				unset( $field['meta-key'], $field['required'], $field['required_field_message_setting'], $field['required-field-message'] );
				break;

			case 'privacy-policy':
				$field['choices']    = self::build_choices( [ __( 'I agree to the privacy policy', 'everest-forms' ) ] );
				$field['show_values']= '0';
				break;
		}

		return $field;
	}

	// ── Choices ───────────────────────────────────────────────────────────────

	private static function build_choices( array $options ): array {
		$choices = [];
		foreach ( $options as $i => $opt ) {
			$label     = sanitize_text_field( is_array( $opt ) ? ( $opt['label'] ?? '' ) : $opt );
			$choices[] = [
				'label'   => $label,
				'value'   => $label,
				'image'   => '',
				'default' => '',
			];
		}
		return $choices;
	}

	// ── Address sublabels ─────────────────────────────────────────────────────

	private static function address_sublabels(): array {
		return [
			'sublabel_hide'       => '0',
			'address1_label'      => __( 'Address Line 1', 'everest-forms' ),
			'address1_placeholder'=> '',
			'address1_default'    => '',
			'address1_hide'       => '0',
			'address2_label'      => __( 'Address Line 2', 'everest-forms' ),
			'address2_placeholder'=> '',
			'address2_default'    => '',
			'address2_hide'       => '0',
			'city_label'          => __( 'City', 'everest-forms' ),
			'city_placeholder'    => '',
			'city_default'        => '',
			'city_hide'           => '0',
			'state_label'         => __( 'State / Province', 'everest-forms' ),
			'state_placeholder'   => '',
			'state_default'       => '',
			'state_hide'          => '0',
			'postal_label'        => __( 'Zip / Postal Code', 'everest-forms' ),
			'postal_placeholder'  => '',
			'postal_default'      => '',
			'postal_hide'         => '0',
			'country_label'       => __( 'Country', 'everest-forms' ),
			'country_placeholder' => '',
			'country_default'     => '',
			'country_hide'        => '0',
		];
	}

	// ── Settings ──────────────────────────────────────────────────────────────

	private static function build_settings( array $ai, ?string $email_field_id ): array {
		$reply_to = $email_field_id
			? '{field_id="' . $email_field_id . '"}'
			: '{admin_email}';

		$settings = [
			'form_title'                         => sanitize_text_field( $ai['form_title'] ?? '' ),
			'form_desc'                          => sanitize_text_field( $ai['form_desc'] ?? '' ),
			'submit_button_text'                 => sanitize_text_field( $ai['submit_button_text'] ?? __( 'Submit', 'everest-forms' ) ),
			'submit_button_processing_text'      => __( 'Processing...', 'everest-forms' ),
			'successful_form_submission_message' => sanitize_text_field( $ai['success_message'] ?? __( 'Thanks for contacting us! We will be in touch shortly.', 'everest-forms' ) ),
			'submission_message_scroll'          => '1',
			'redirect_to'                        => 'same',
			'custom_page'                        => '',
			'external_url'                       => '',
			'layout_class'                       => 'default',
			'form_class'                         => '',
			'ajax_form_submission'               => '1',
			'disabled_entries'                   => '0',
			'honeypot'                           => '1',
			'recaptcha_support'                  => '0',
		];

		// Email notification — use AI-generated subject if provided
		if ( ! empty( $ai['send_email_notification'] ) ) {
			$default_subject = sprintf(
				/* translators: %s: form title */
				__( 'New submission: %s', 'everest-forms' ),
				$ai['form_title'] ?? 'Form'
			);
			$email_subject = ! empty( $ai['notification_subject'] )
				? sanitize_text_field( $ai['notification_subject'] )
				: $default_subject;

			$settings['email'] = [
				'connection_1' => [
					'enable_email_notification' => '1',
					'connection_name'           => __( 'Admin Notification', 'everest-forms' ),
					'evf_to_email'              => '{admin_email}',
					'evf_from_name'             => get_bloginfo( 'name' ),
					'evf_from_email'            => '{admin_email}',
					'evf_reply_to'              => $reply_to,
					'evf_email_subject'         => $email_subject,
					'evf_email_message'         => '{all_fields}',
					'evf_email_cc'              => '',
					'evf_email_bcc'             => '',
				],
			];
		}

		return $settings;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Generate a unique field ID in EVF format: 8 random alphanumeric chars.
	 * E.g. "a3f9b2c1"
	 */
	private static function generate_field_id(): string {
		return substr( md5( uniqid( '', true ) ), 0, 8 );
	}

	/**
	 * Generate meta-key from label. Falls back to field_id suffix if label is empty.
	 * EVF convention: lowercase, underscored, no special chars.
	 * E.g. "Email Address" → "email_address"
	 */
	private static function generate_meta_key( string $label, string $field_id ): string {
		$key = strtolower( trim( $label ) );
		$key = preg_replace( '/[^a-z0-9]+/', '_', $key );
		$key = trim( $key, '_' );
		return $key ?: 'field_' . substr( $field_id, 0, 4 );
	}
}
