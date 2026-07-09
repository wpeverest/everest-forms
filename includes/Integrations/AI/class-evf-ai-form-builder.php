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
		'password', 'color', 'range-slider', 'signature', 'repeater-fields',
		'lookup', 'progress',
		'payment-single', 'payment-checkbox', 'payment-multiple',
		'payment-quantity', 'payment-subtotal', 'payment-total',
		'payment-coupon', 'credit-card', 'payment-square',
		'payment-authorize-net', 'payment-subscription-plan',
		'payment-gateway-selector',
	];

	/** Set to true when a file-upload field was dropped because the free-tier limit (1) was reached. */
	public static $file_upload_limited = false;

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

		remove_all_filters( 'content_save_pre' );

		// Preserve per-field settings (e.g. label_hide) that were applied by a prior AI
		// request but may be absent from this AI response. Merge before building form data.
		$existing_data  = evf_decode( $post->post_content );
		$existing_index = self::index_fields_by_label_type( $existing_data['form_fields'] ?? array() );
		$ai_response    = self::merge_field_settings( $ai_response, $existing_index );

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
		self::$file_upload_limited = false;
		$built_fields   = [];
		$email_field_id = null;

		// Pre-compute which step each field index belongs to (for multipart pairing)
		$field_step_map = [];
		foreach ( ( $ai['multipart_steps'] ?? [] ) as $step_idx => $step ) {
			foreach ( ( $step['field_indices'] ?? [] ) as $fi ) {
				$field_step_map[ $fi ] = $step_idx;
			}
		}

		// Promote explicit recaptcha/hcaptcha/turnstile field requests to the form-level
		// recaptcha_support setting. The AI sometimes returns these as a field type rather
		// than setting enable_recaptcha, so we detect and convert them here.
		foreach ( ( $ai['fields'] ?? [] ) as $ai_field ) {
			$t = strtolower( sanitize_key( $ai_field['type'] ?? '' ) );
			$l = strtolower( $ai_field['label'] ?? '' );
			if ( in_array( $t, [ 'recaptcha', 'hcaptcha', 'turnstile' ], true )
				|| false !== strpos( $l, 'recaptcha' )
				|| false !== strpos( $l, 'hcaptcha' )
				|| false !== strpos( $l, 'turnstile' ) ) {
				$ai['enable_recaptcha'] = true;
				break;
			}
		}

		// Build all field objects first so we can look ahead for smart pairing
		$field_list        = [];
		$field_index       = 0;
		$file_upload_count = 0;
		$is_pro_active     = defined( 'EVF_PRO_VERSION' ) || class_exists( 'EVF_Pro' );
		foreach ( ( $ai['fields'] ?? [] ) as $ai_field ) {
			// Free tier: only one file-upload field is allowed per form.
			if ( ! $is_pro_active && 'file-upload' === ( $ai_field['type'] ?? '' ) ) {
				if ( $file_upload_count >= 1 ) {
					self::$file_upload_limited = true;
					$field_index++;
					continue;
				}
				$file_upload_count++;
			}

			$field_id  = self::generate_field_id();
			$evf_field = self::build_field( $field_id, $ai_field );
			if ( ! $evf_field ) {
				$field_index++;
				continue;
			}
			$built_fields[ $field_id ] = $evf_field;
			if ( 'email' === $evf_field['type'] && null === $email_field_id ) {
				$email_field_id = $field_id;
			}
			$field_list[] = [
				'id'    => $field_id,
				'type'  => $evf_field['type'],
				'width' => sanitize_key( $ai_field['width'] ?? '' ), // 'full'|'half'|''
				'step'  => $field_step_map[ $field_index ] ?? -1,    // -1 = no multipart
			];
			$field_index++;
		}

		// Ensure every field has a unique meta-key within this form.
		$used_keys = [];
		foreach ( $built_fields as &$field ) {
			if ( ! isset( $field['meta-key'] ) ) {
				continue;
			}
			$base = $field['meta-key'];
			if ( ! in_array( $base, $used_keys, true ) ) {
				$used_keys[] = $base;
				continue;
			}
			$n = 2;
			while ( in_array( $base . '_' . $n, $used_keys, true ) ) {
				$n++;
			}
			$field['meta-key'] = $base . '_' . $n;
			$used_keys[]       = $field['meta-key'];
		}
		unset( $field );

		$structure  = self::build_structure( $field_list );
		$form_data  = [
			'id'             => $form_id,
			'form_field_id'  => (string) count( $built_fields ),
			'form_enabled'   => '1',
			'form_fields'    => $built_fields,
			'settings'       => self::build_settings( $ai, $email_field_id ),
			'structure'      => $structure,
		];

		// Multipart — inject step data mapping field indices → row IDs from structure
		if ( 'multipart' === ( $ai['form_type'] ?? '' ) && ! empty( $ai['multipart_steps'] ) ) {
			$form_data['multi_part'] = self::build_multipart_data(
				$ai['multipart_steps'],
				array_keys( $built_fields ),
				$structure
			);
		}

		return $form_data;
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

			// Explicit AI width override takes priority over auto-pairing logic
			$current_width = $current['width'] ?? '';
			$next_width    = $next['width'] ?? '';
			$force_full    = 'full' === $current_width;
			$force_half    = 'half' === $current_width && $next && 'half' === $next_width;

			$is_narrow      = in_array( $current['type'], self::$narrow_types, true );
			$next_is_narrow = $next && in_array( $next['type'], self::$narrow_types, true );

			// Forced pair (first-name ↔ last-name) by type
			$forced_next_type = self::$forced_pairs[ $current['type'] ] ?? null;
			$is_forced_pair   = $forced_next_type && $next && $next['type'] === $forced_next_type;

			// Never pair fields from different multipart steps (would share a row across parts)
			$same_step = ( -1 === ( $current['step'] ?? -1 ) )
				|| ! isset( $next['step'] )
				|| $current['step'] === $next['step'];

			// Two-column: explicit half+half OR auto-pair (unless force_full or cross-step)
			if ( ! $force_full && $same_step && ( $force_half || $is_forced_pair || ( $is_narrow && $next_is_narrow ) ) ) {
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
				$type                    = 'reset';
				$label                   = $label ?: __( 'Reset', 'everest-forms' );
				$ai_field['description'] = '';
			}
		}

		// reCAPTCHA, hCaptcha, and Turnstile are form-level settings, not draggable
		// fields. build_form_data() already promoted the request to enable_recaptcha
		// via its pre-scan; skip field creation here.
		$lc_label = strtolower( $label );
		if ( in_array( $type, [ 'recaptcha', 'hcaptcha', 'turnstile' ], true )
			|| false !== strpos( $lc_label, 'recaptcha' )
			|| false !== strpos( $lc_label, 'hcaptcha' )
			|| false !== strpos( $lc_label, 'turnstile' ) ) {
			return null;
		}

		// Normalize: gateway may return `text` (or a non-existent `math` type)
		// for math-captcha requests — convert to the proper EVF `captcha` type.
		if ( 'captcha' !== $type ) {
			$combined = strtolower( $label . ' ' . ( $ai_field['description'] ?? '' ) );
			if ( 'math' === $type || false !== strpos( $combined, 'captcha' ) ) {
				$type  = 'captcha';
				$label = $label ?: __( 'Math Captcha', 'everest-forms' );
			}
		}

		// Normalize: EVF's payment-radio field is registered internally as
		// `payment-multiple`. Map the intuitive gateway name to the real type.
		if ( 'payment-radio' === $type ) {
			$type = 'payment-multiple';
		}

		// Normalize: `credit-card` (and the per-gateway square/authorize-net fields)
		// are legacy types now unified into the single Payment Gateway field. The
		// gateway prompt no longer emits them, but remap defensively so any cached
		// or older AI response still produces the modern, frontend-rendering field.
		if ( in_array( $type, array( 'credit-card', 'payment-square', 'payment-authorize-net' ), true ) ) {
			$type  = 'payment-gateway-selector';
			$label = $label ?: __( 'Payment Gateway', 'everest-forms' );
		}

		// Normalize: the repeater field is registered as `repeater-fields` internally.
		if ( 'repeater' === $type ) {
			$type = 'repeater-fields';
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
			'label_hide'                     => ! empty( $ai_field['label_hide'] ) ? '1' : '0',
			'description'                    => sanitize_text_field( $ai_field['description'] ?? '' ),
			'css'                            => sanitize_text_field( $ai_field['css'] ?? '' ),
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
				$field['default_value']     = sanitize_text_field( $ai_field['default_value'] ?? '' );
				$field['limit_enabled']     = '0';
				$field['limit_count']       = '100';
				$field['limit_mode']        = 'characters';
				$field['min_length_enabled']= '0';
				$field['min_length_count']  = '1';
				$field['min_length_mode']   = 'characters';
				$field['input_mask']        = '';
				break;

			case 'textarea':
				$field['default_value'] = sanitize_textarea_field( $ai_field['default_value'] ?? '' );
				$field['limit_enabled'] = '0';
				$field['limit_count']   = '500';
				$field['limit_mode']    = 'characters';
				break;

			case 'email':
				$field['default_value']            = sanitize_text_field( $ai_field['default_value'] ?? '' );
				$field['confirmation_placeholder'] = '';
				$field['sublabel_hide']            = ! empty( $ai_field['sublabel_hide'] ) ? '1' : '';
				break;

			case 'phone':
				$field['default_value'] = sanitize_text_field( $ai_field['default_value'] ?? '' );
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
				if ( ! empty( $ai_field['sublabel_hide'] ) ) {
					$field['sublabel_hide'] = '1';
				}
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
				$field['default_value'] = sanitize_text_field( $ai_field['default_value'] ?? '' );
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

			case 'payment-multiple':
				$field['choices']        = self::build_payment_choices( $ai_field['options'] ?? [] );
				$field['input_columns']  = '';
				$field['choices_images'] = '0';
				break;

			case 'payment-checkbox':
				$field['choices']        = self::build_payment_choices( $ai_field['options'] ?? [] );
				$field['input_columns']  = '';
				$field['choices_images'] = '0';
				$field['select_all']     = '0';
				break;

			case 'repeater-fields':
				$field['repeater_field_hide']          = '0';
				$field['repeater_repeat_limit']        = '';
				$field['repeater_button_add_new_label']= 'Add';
				$field['repeater_button_remove_label'] = 'Remove';
				break;

			case 'captcha':
				$field['format']   = 'math';
				$field['required'] = '1';
				break;

			case 'reset':
				$field['button_text'] = $label ?: __( 'Reset', 'everest-forms' );
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

	/**
	 * Build choices for payment-multiple / payment-checkbox fields.
	 * Value must be a numeric price string (e.g. "10.00"), not the label text.
	 * If the gateway supplies a price in the option (array with 'value', or a
	 * string like "VIP - $50"), extract it; otherwise assign ascending defaults.
	 */
	private static function build_payment_choices( array $options ): array {
		$defaults = [ '10.00', '20.00', '30.00', '40.00', '50.00' ];
		$choices  = [];

		foreach ( $options as $i => $opt ) {
			if ( is_array( $opt ) ) {
				$label = sanitize_text_field( $opt['label'] ?? '' );
				$price = self::extract_price( $opt['value'] ?? '' ) ?? $defaults[ $i ] ?? '10.00';
			} else {
				$raw   = sanitize_text_field( $opt );
				// Try to pull a dollar/numeric price out of strings like "VIP – $50" or "General ($20.00)"
				$price = self::extract_price( $raw ) ?? $defaults[ $i ] ?? '10.00';
				// Strip the price annotation from the label so it doesn't duplicate.
				$label = trim( preg_replace( '/[\(\-–—]*\s*\$[\d,]+(\.\d{1,2})?\s*[\)]*/', '', $raw ) );
				$label = $label ?: $raw;
			}

			$choices[] = [
				'label'   => $label,
				'value'   => $price,
				'image'   => '',
				'default' => '',
			];
		}

		// If no options came from the gateway, use the field's own defaults.
		if ( empty( $choices ) ) {
			foreach ( [ 'Option 1', 'Option 2', 'Option 3' ] as $i => $lbl ) {
				$choices[] = [
					'label'   => $lbl,
					'value'   => $defaults[ $i ],
					'image'   => '',
					'default' => '',
				];
			}
		}

		return $choices;
	}

	/** Extract a numeric price string from a value or annotated label. Returns null on failure. */
	private static function extract_price( string $raw ): ?string {
		// Accept plain numbers ("25", "25.00") or dollar-prefixed ("$25", "$25.00")
		if ( preg_match( '/\$?([\d,]+(?:\.\d{1,2})?)/', $raw, $m ) ) {
			$num = (float) str_replace( ',', '', $m[1] );
			if ( $num > 0 ) {
				return number_format( $num, 2, '.', '' );
			}
		}
		return null;
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

		$is_multipart       = 'multipart' === ( $ai['form_type'] ?? '' );
		$is_conversational  = 'conversational' === ( $ai['form_type'] ?? '' );

		$settings = [
			'form_title'                         => sanitize_text_field( $ai['form_title'] ?? '' ),
			'form_desc'                          => sanitize_text_field( $ai['form_desc'] ?? '' ),
			'submit_button_text'                 => sanitize_text_field( $ai['submit_button_text'] ?? __( 'Submit', 'everest-forms' ) ),
			'submit_button_processing_text'      => __( 'Processing...', 'everest-forms' ),
			'successful_form_submission_message' => sanitize_text_field( $ai['success_message'] ?? __( 'Thanks for contacting us! We will be in touch shortly.', 'everest-forms' ) ),
			'submission_message_scroll'          => '1',
			'redirect_to'                        => self::validate_redirect_to( $ai['redirect_to'] ?? 'same' ),
			'custom_page'                        => absint( $ai['redirect_custom_page_id'] ?? 0 ),
			'external_url'                       => esc_url_raw( $ai['redirect_external_url'] ?? '' ),
			'layout_class'                       => 'default',
			'form_class'                         => '',
			'ajax_form_submission'               => '1',
			'disabled_entries'                   => '0',
			// Anti-spam: AI always enables honeypot; reCAPTCHA only if AI says so AND it's configured.
			'honeypot'                           => ! empty( $ai['enable_honeypot'] ) ? '1' : '1',
			'recaptcha_support'                  => ! empty( $ai['enable_recaptcha'] ) && self::is_recaptcha_configured() ? '1' : '0',
			// Multipart — enable_multi_part flag + indicator/nav settings read by builder
			'enable_multi_part' => $is_multipart ? '1' : '0',
			'multi_part'        => $is_multipart ? array(
				'indicator'       => 'progress',
				'indicator_color' => '#7e3bd0',
				'nav_align'       => 'center',
			) : array(),
			// Conversational — enable flag + sub-settings read by conversational forms plugin
			'enable_conversational_forms' => $is_conversational ? '1' : '0',
			'conversational_forms'        => $is_conversational ? array(
				'conversational_forms_url'                                   => sanitize_title( $ai['conversational_url'] ?? sanitize_title( $ai['form_title'] ?? '' ) ),
				'enable_welcome_message'                                     => 'no',
				'enable_page_navigation'                                     => '1',
				'enable_branding'                                            => '1',
				'everest_forms_conversational_forms_color_picker'            => '#7e3bd0',
				'everest_forms_conversational_form_background_layout'        => 'default',
				'everest_forms_conversational_forms_background_image'        => '',
				'everest_forms_conversational_forms_opacity'                 => '',
				'everest_forms_conversational_forms_themes'                  => '',
			) : array(),
		];

		// Admin email notification
		if ( ! empty( $ai['send_email_notification'] ) ) {
			$email_subject = ! empty( $ai['notification_subject'] )
				? sanitize_text_field( $ai['notification_subject'] )
				: sprintf( __( 'New submission: %s', 'everest-forms' ), $ai['form_title'] ?? 'Form' );

			$notif_from_name = ! empty( $ai['notification_from_name'] )
				? sanitize_text_field( $ai['notification_from_name'] )
				: '';
			$notif_reply_to  = self::resolve_reply_to( $ai['notification_reply_to'] ?? 'auto', $reply_to );
			$notif_message   = ! empty( $ai['notification_message'] )
				? wp_kses_post( $ai['notification_message'] )
				: '{all_fields}';

			$settings['email'] = [
				'connection_1' => [
					'enable_email_notification' => '1',
					'connection_name'           => __( 'Admin Notification', 'everest-forms' ),
					'evf_to_email'              => '{admin_email}',
					'evf_from_name'             => $notif_from_name,
					'evf_from_email'            => '{admin_email}',
					'evf_reply_to'              => $notif_reply_to,
					'evf_email_subject'         => $email_subject,
					'evf_email_message'         => $notif_message,
					'evf_email_cc'              => '',
					'evf_email_bcc'             => '',
				],
			];

			// User confirmation email — when AI says to send one + email field found
			if ( ! empty( $ai['send_user_confirmation'] ) && $email_field_id ) {
				$confirm_subject = ! empty( $ai['user_confirmation_subject'] )
					? sanitize_text_field( $ai['user_confirmation_subject'] )
					: sprintf( __( 'Thank you for your submission — %s', 'everest-forms' ), $ai['form_title'] ?? 'Form' );
				$confirm_message = ! empty( $ai['user_confirmation_message'] )
					? sanitize_textarea_field( $ai['user_confirmation_message'] )
					: __( 'Thank you! We have received your submission and will be in touch shortly.', 'everest-forms' );

				$ucfm_from_name = ! empty( $ai['user_confirmation_from_name'] )
					? sanitize_text_field( $ai['user_confirmation_from_name'] )
					: '';
				$ucfm_reply_to  = self::resolve_reply_to( $ai['user_confirmation_reply_to'] ?? 'auto', '{admin_email}' );

				$settings['email']['connection_2'] = [
					'enable_email_notification' => '1',
					'connection_name'           => __( 'User Confirmation', 'everest-forms' ),
					'evf_to_email'              => '{field_id="' . $email_field_id . '"}',
					'evf_from_name'             => $ucfm_from_name,
					'evf_from_email'            => '{admin_email}',
					'evf_reply_to'              => $ucfm_reply_to,
					'evf_email_subject'         => $confirm_subject,
					'evf_email_message'         => $confirm_message,
					'evf_email_cc'              => '',
					'evf_email_bcc'             => '',
				];
			}
		}

		return $settings;
	}

	// ── Multipart ─────────────────────────────────────────────────────────────

	/**
	 * Build EVF multi_part structure from AI step definitions.
	 *
	 * The multipart plugin splits the form at row boundaries — it renders rows
	 * sequentially and opens a new <div id="part_N"> when the last row of a part
	 * is reached (line 943 in class-everest-forms-multi-part.php). So each part
	 * needs `rows` (row IDs from the structure object), not just field IDs.
	 *
	 * @param array $steps       AI multipart_steps: [{ title, field_indices[] }, ...]
	 * @param array $field_ids   Ordered list of actual field IDs
	 * @param array $structure   EVF structure object (row_X => [grid_1 => [field_id, ...]])
	 * @return array             EVF multi_part format
	 */
	private static function build_multipart_data( array $steps, array $field_ids, array $structure ): array {
		// Build reverse map: field_id → row_key
		$field_to_row = [];
		foreach ( $structure as $row_key => $grids ) {
			foreach ( $grids as $grid ) {
				foreach ( (array) $grid as $fid ) {
					$field_to_row[ $fid ] = $row_key;
				}
			}
		}

		$multi_part = [];
		foreach ( $steps as $step_index => $step ) {
			$part_id  = $step_index + 1;
			$part_key = 'part_' . $part_id;
			$indices  = $step['field_indices'] ?? [];

			// Collect the row IDs for every field in this step (unique, preserving order)
			$step_rows = [];
			$step_fids = [];
			foreach ( $indices as $idx ) {
				if ( ! isset( $field_ids[ $idx ] ) ) {
					continue;
				}
				$fid       = $field_ids[ $idx ];
				$step_fids[] = $fid;
				$row_key   = $field_to_row[ $fid ] ?? null;
				if ( $row_key && ! in_array( $row_key, $step_rows, true ) ) {
					$step_rows[] = $row_key;
				}
			}

			$step_title = sanitize_text_field( $step['title'] ?? sprintf( __( 'Part %d', 'everest-forms' ), $part_id ) );
			$multi_part[ $part_key ] = [
				'id'     => (string) $part_id,
				'name'   => $step_title,
				'next'   => __( 'Next', 'everest-forms' ),
				'prev'   => __( 'Previous', 'everest-forms' ),
				'rows'   => $step_rows,   // row IDs — plugin uses this to split the form
				'fields' => $step_fids,   // field IDs — used by builder admin UI
			];
		}
		return $multi_part;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Build a lookup of existing form fields indexed by "type||label".
	 *
	 * @param array $form_fields Raw form_fields array from EVF form data.
	 * @return array
	 */
	private static function index_fields_by_label_type( array $form_fields ): array {
		$index = array();
		foreach ( $form_fields as $field ) {
			$key           = ( $field['type'] ?? '' ) . '||' . ( $field['label'] ?? '' );
			$index[ $key ] = $field;
		}
		return $index;
	}

	/**
	 * Merge per-field settings from the existing saved form into the AI response.
	 *
	 * When the AI returns a field matching an existing one (by type+label), settings
	 * from the existing field are restored to prevent subsequent AI requests from
	 * silently resetting them.
	 *
	 * Two strategies are used:
	 *
	 * - Sticky flags (label_hide, sublabel_hide): if the existing field has these
	 *   enabled ('1'), always force them back — the AI frequently returns the default
	 *   (false) for unchanged fields, which would otherwise reset them.
	 *
	 * - Scalar settings (description, placeholder, css, required): only copied when
	 *   the AI omitted the key entirely, so explicit AI changes are still honoured.
	 *
	 * @param array $ai_response    Decoded AI gateway response.
	 * @param array $existing_index Existing fields indexed by "type||label".
	 * @return array Modified $ai_response with merged field settings.
	 */
	private static function merge_field_settings( array $ai_response, array $existing_index ): array {
		// Sticky flags: if existing is active ('1'), always restore — the AI returns
		// false by default for unchanged fields, which would silently reset them.
		$sticky_flags = array( 'label_hide', 'sublabel_hide' );

		// Scalar settings: only restore when AI omitted the key entirely.
		$preservable = array( 'required', 'description', 'placeholder', 'css' );

		foreach ( ( $ai_response['fields'] ?? array() ) as $i => $ai_field ) {
			$key = ( $ai_field['type'] ?? '' ) . '||' . ( $ai_field['label'] ?? '' );
			if ( ! isset( $existing_index[ $key ] ) ) {
				continue;
			}

			$existing = $existing_index[ $key ];

			foreach ( $sticky_flags as $flag ) {
				if ( '1' === ( $existing[ $flag ] ?? '0' ) ) {
					$ai_response['fields'][ $i ][ $flag ] = '1';
				}
			}

			foreach ( $preservable as $prop ) {
				if ( ! array_key_exists( $prop, $ai_field )
					&& ! empty( $existing[ $prop ] )
					&& '0' !== (string) $existing[ $prop ] ) {
					$ai_response['fields'][ $i ][ $prop ] = $existing[ $prop ];
				}
			}
		}

		return $ai_response;
	}

	/**
	 * Validate redirect_to — only accept known values, fall back to 'same'.
	 */
	private static function validate_redirect_to( string $val ): string {
		return in_array( $val, array( 'same', 'custom_page', 'external_url' ), true ) ? $val : 'same';
	}

	/**
	 * Resolve AI reply_to value.
	 * "auto" or empty → use $default (auto-detected email field smart tag or admin email).
	 * Anything else   → sanitize and use as-is (explicit email or smart tag).
	 */
	private static function resolve_reply_to( string $val, string $default ): string {
		if ( '' === $val || 'auto' === $val ) {
			return $default;
		}
		return sanitize_text_field( $val );
	}

	/**
	 * Check whether reCAPTCHA (any type) has a site key configured in EVF settings.
	 * Used to avoid enabling recaptcha_support when no key is set up.
	 */
	public static function is_recaptcha_configured(): bool {
		$type = get_option( 'everest_forms_recaptcha_type', 'v2' );
		switch ( $type ) {
			case 'v2':
				return (bool) get_option( 'everest_forms_recaptcha_v2_site_key' );
			case 'v2_invisible':
				return (bool) get_option( 'everest_forms_recaptcha_v2_invisible_site_key' );
			case 'v3':
				return (bool) get_option( 'everest_forms_recaptcha_v3_site_key' );
			case 'hcaptcha':
				return (bool) get_option( 'everest_forms_recaptcha_hcaptcha_site_key' );
			case 'turnstile':
				return (bool) get_option( 'everest_forms_recaptcha_turnstile_site_key' );
		}
		return false;
	}

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
