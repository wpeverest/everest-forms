<?php
/**
 * EVF AI AJAX — handles all wp_ajax_ actions for AI form generation.
 */

defined( 'ABSPATH' ) || exit;

class EVF_AI_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_evf_ai_generate_form', array( $this, 'generate_form' ) );
		add_action( 'wp_ajax_evf_ai_update_form', array( $this, 'update_form' ) );
		add_action( 'wp_ajax_evf_ai_get_usage', array( $this, 'get_usage' ) );
		add_action( 'wp_ajax_evf_ai_activate_form', array( $this, 'activate_form' ) );
		add_action( 'wp_ajax_evf_ai_render_fields', array( $this, 'render_fields' ) );
	}

	/**
	 * Return the builder's freshly-rendered fields canvas + options panel HTML for
	 * a form, so the in-builder AI chat can refresh the builder in place (no full
	 * page reload) after an AI edit.
	 */
	public function render_fields() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to use this feature.', 'everest-forms' ) ), 403 );
		}

		$form_id = absint( $_POST['form_id'] ?? 0 );
		$post    = $form_id ? get_post( $form_id ) : null;
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'everest-forms' ) ) );
		}

		// EVF_Builder_Page reads the form from $_GET['form_id'] in its constructor.
		$_GET['form_id'] = $form_id; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! class_exists( 'EVF_Builder_Fields', false ) ) {
			include_once dirname( EVF_PLUGIN_FILE ) . '/includes/admin/builder/class-evf-builder-page.php';
			include_once dirname( EVF_PLUGIN_FILE ) . '/includes/admin/builder/class-evf-builder-fields.php';
		}

		if ( ! class_exists( 'EVF_Builder_Fields', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Builder is unavailable.', 'everest-forms' ) ) );
		}

		$builder = new EVF_Builder_Fields();

		ob_start();
		$builder->output_fields_preview();
		$preview = ob_get_clean();

		ob_start();
		$builder->output_fields_options();
		$options = ob_get_clean();

		wp_send_json_success(
			array(
				'preview' => $preview,
				'options' => $options,
				'title'   => get_the_title( $form_id ),
			)
		);
	}

	/**
	 * Regenerate / refine the current draft AI form from a follow-up prompt.
	 * Rebuilds the SAME draft in place so the preview updates without creating a
	 * new form.
	 *
	 * NOTE: the Python gateway does not implement /ai/v1/update yet; this wires the
	 * call so it works once that endpoint ships. Until then it surfaces the
	 * gateway error to the user.
	 */
	public function update_form() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to use this feature.', 'everest-forms' ) ), 403 );
		}

		$form_id       = absint( $_POST['form_id'] ?? 0 );
		$prompt        = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );
		$refine_prompt = sanitize_textarea_field( wp_unslash( $_POST['refine_prompt'] ?? '' ) );

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'everest-forms' ) ) );
		}

		if ( empty( $prompt ) ) {
			wp_send_json_error( array( 'message' => __( 'Please describe what you want to change.', 'everest-forms' ) ) );
		}

		if ( EVF_AI_Registration::is_local_site() ) {
			wp_send_json_error(
				array(
					'message' => __( 'AI features are not available on local or staging sites.', 'everest-forms' ),
					'code'    => 'not_registered',
				)
			);
		}

		if ( ! EVF_AI_Registration::is_registered() ) {
			EVF_AI_Registration::register();
		}

		if ( ! EVF_AI_Registration::is_registered() ) {
			wp_send_json_error(
				array(
					'message' => __( 'This site could not be registered with the AI service. Please try again.', 'everest-forms' ),
					'code'    => 'not_registered',
				)
			);
		}

		$ai_response = EVF_AI_API::update_form( $prompt, $form_id, $refine_prompt );

		if ( is_wp_error( $ai_response ) ) {
			wp_send_json_error(
				array(
					'message' => $ai_response->get_error_message(),
					'code'    => $ai_response->get_error_code(),
				)
			);
		}

		if ( self::ai_requests_recaptcha( $ai_response ) && ! EVF_AI_Form_Builder::is_recaptcha_configured() ) {
			wp_send_json_error(
				array(
					'message' => __( 'reCAPTCHA is not configured yet. Please add your site key under Everest Forms > Settings > reCAPTCHA, then try again.', 'everest-forms' ),
					'code'    => 'recaptcha_not_configured',
				)
			);
		}

		// Snapshot form type before the update to detect type changes afterwards.
		$before_post     = get_post( $form_id );
		$before_data     = $before_post ? evf_decode( $before_post->post_content ) : array();
		$before_settings = $before_data['settings'] ?? array();
		$was_multipart   = ! empty( $before_settings['enable_multi_part'] ) && evf_string_to_bool( $before_settings['enable_multi_part'] );

		$result = EVF_AI_Form_Builder::update_form( $form_id, $ai_response );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Primary notice: check what was actually saved to the form.
		// Fallback: if the AI didn't set the form type (e.g. returned "standard"),
		// detect the user's intent from the prompt and surface the notice anyway.
		$notice = self::get_pro_feature_notice( $form_id );
		if ( '' === $notice && ! empty( $refine_prompt ) ) {
			$prompt_check = self::check_pro_feature_from_prompt( $refine_prompt );
			if ( $prompt_check ) {
				$notice = $prompt_check->get_error_message();
			}
		}

		// Read the saved form data once and reuse for all post-update checks.
		$after_post     = get_post( $form_id );
		$after_data     = $after_post ? evf_decode( $after_post->post_content ) : array();
		$after_settings = $after_data['settings'] ?? array();

		// A full page reload is needed whenever non-field settings changed — the fast
		// canvas refresh (evfReloadBuilderFields) only updates the fields panel and
		// doesn't refresh the Settings tab, so redirect/email/message changes would
		// appear to the user as if they had no effect.
		$needs_reload = false;

		// Detect whether the refine prompt explicitly asks for a settings change
		// that requires a full page reload to become visible in the builder.
		// Pure field operations never contain these terms, eliminating false positives.
		$prompt_lower = strtolower( $refine_prompt );

		// Email keywords: keyword match alone is sufficient — if the user asks to
		// set up / change email notifications, the Settings tab will have changed
		// regardless of which specific value differed.
		$email_keywords = array(
			'send mail',
			'send email',
			'send an email',
			'email notification',
			'email to admin',
			'mail to admin',
			'notify admin',
			'notification email',
			'admin notification',
			'from name',
			'send from',
			'sender name',
			'email from',
			'email subject',
			'mail subject',
		);
		foreach ( $email_keywords as $kw ) {
			if ( false !== strpos( $prompt_lower, $kw ) ) {
				$needs_reload = true;
				break;
			}
		}

		// Redirect keywords: run before/after comparison to confirm the redirect
		// setting actually changed (avoids triggering reload when AI preserves it).
		if ( ! $needs_reload ) {
			$redirect_keywords   = array(
				'redirect',
				'external url',
				'custom page',
				'thank you page',
				'confirmation page',
				'after submit',
				'after submission',
			);
			$prompt_has_redirect = false;
			foreach ( $redirect_keywords as $kw ) {
				if ( false !== strpos( $prompt_lower, $kw ) ) {
					$prompt_has_redirect = true;
					break;
				}
			}

			if ( $prompt_has_redirect ) {
				// redirect_to: '' and 'same' are equivalent.
				$normalize_redirect = static function ( string $val ): string {
					return '' === $val ? 'same' : $val;
				};
				if ( $normalize_redirect( $before_settings['redirect_to'] ?? '' ) !== $normalize_redirect( $after_settings['redirect_to'] ?? '' ) ) {
					$needs_reload = true;
				}
				if ( ! $needs_reload && ( $before_settings['external_url'] ?? '' ) !== ( $after_settings['external_url'] ?? '' ) ) {
					$needs_reload = true;
				}
				if ( ! $needs_reload && (int) ( $before_settings['custom_page'] ?? 0 ) !== (int) ( $after_settings['custom_page'] ?? 0 ) ) {
					$needs_reload = true;
				}
			}
		}

		// Multi-part type change also requires a full reload (existing behaviour).
		if ( ! $needs_reload && self::has_active_license() && class_exists( 'EverestForms_MultiPart' ) ) {
			$now_multipart = ! empty( $after_settings['enable_multi_part'] ) && evf_string_to_bool( $after_settings['enable_multi_part'] );
			if ( ! $was_multipart && $now_multipart ) {
				$needs_reload = true;
			}
		}

		wp_send_json_success(
			array(
				'form_id'          => $form_id,
				'form_title'       => get_the_title( $form_id ),
				'fields'           => EVF_AI_Form_Builder::get_field_summary( $form_id ),
				'required_addons'  => $ai_response['required_addons'] ?? array(),
				'multi_part_steps' => self::get_multi_part_steps( $form_id ),
				'preview_html'     => self::render_preview_html( $form_id ),
				'needs_reload'     => $needs_reload,
				'notice'           => $notice,
				'notice_url'       => '' !== $notice ? self::get_notice_upgrade_url() : '',
			)
		);
	}

	/**
	 * Generate a form from a user prompt and return the new form's edit URL.
	 */
	public function generate_form() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to use this feature.', 'everest-forms' ) ), 403 );
		}

		$prompt = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ?? '' ) );

		if ( empty( $prompt ) ) {
			wp_send_json_error( array( 'message' => __( 'Please describe the form you want to create.', 'everest-forms' ) ) );
		}

		if ( strlen( $prompt ) < 5 ) {
			wp_send_json_error( array( 'message' => __( 'Prompt is too short. Please provide more detail.', 'everest-forms' ) ) );
		}

		if ( EVF_AI_Registration::is_local_site() ) {
			wp_send_json_error(
				array(
					'message' => __( 'AI features are not available on local or staging sites.', 'everest-forms' ),
					'code'    => 'not_registered',
				)
			);
		}

		// Auto-register on first use
		if ( ! EVF_AI_Registration::is_registered() ) {
			EVF_AI_Registration::register();
		}

		if ( ! EVF_AI_Registration::is_registered() ) {
			wp_send_json_error(
				array(
					'message' => __( 'This site could not be registered with the AI service. Please try again.', 'everest-forms' ),
					'code'    => 'not_registered',
				)
			);
		}

		// Call gateway
		$ai_response = EVF_AI_API::generate_form( $prompt );

		// "Invalid token" means the stored token is stale (gateway restarted, URL changed, etc.)
		// Auto-heal: clear credentials, re-register, and retry once — transparent to the user.
		if ( is_wp_error( $ai_response ) && 'api_error' === $ai_response->get_error_code()
			&& false !== strpos( $ai_response->get_error_message(), 'Invalid token' ) ) {

			EVF_AI_Registration::clear_credentials();
			EVF_AI_Registration::register();
			$ai_response = EVF_AI_API::generate_form( $prompt );
		}

		if ( is_wp_error( $ai_response ) ) {
			$code = $ai_response->get_error_code();
			wp_send_json_error(
				array(
					'message' => $ai_response->get_error_message(),
					'code'    => $code,
				)
			);
		}

		// Build and insert the EVF form
		$form_id = EVF_AI_Form_Builder::create_form( $ai_response );

		if ( is_wp_error( $form_id ) ) {
			wp_send_json_error( array( 'message' => $form_id->get_error_message() ) );
		}

		$gen_notice = self::get_pro_feature_notice( $form_id );
		wp_send_json_success(
			array(
				'form_id'          => $form_id,
				'form_title'       => get_the_title( $form_id ),
				'edit_url'         => admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $form_id ),
				'tier'             => EVF_AI_Registration::get_tier(),
				'fields'           => EVF_AI_Form_Builder::get_field_summary( $form_id ),
				'required_addons'  => $ai_response['required_addons'] ?? array(),
				'multi_part_steps' => self::get_multi_part_steps( $form_id ),
				'preview_html'     => self::render_preview_html( $form_id ),
				'notice'           => $gen_notice,
				'notice_url'       => '' !== $gen_notice ? self::get_notice_upgrade_url() : '',
			)
		);
	}

	/**
	 * Publish a draft AI form — called when user clicks "Use This Form".
	 */
	public function activate_form() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( array(), 403 );
		}

		$form_id = absint( $_POST['form_id'] ?? 0 );
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'everest-forms' ) ) );
		}

		$ok = EVF_AI_Form_Builder::activate_form( $form_id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'Could not activate form.', 'everest-forms' ) ) );
		}

		wp_send_json_success(
			array(
				'form_id'  => $form_id,
				'edit_url' => admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $form_id ),
			)
		);
	}

	/**
	 * Return usage stats for display in the builder UI (requests used today, limit, etc.).
	 */
	public function get_usage() {
		check_ajax_referer( 'evf_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( array(), 403 );
		}

		$usage = EVF_AI_API::get_usage();

		if ( is_wp_error( $usage ) ) {
			wp_send_json_error( array( 'message' => $usage->get_error_message() ) );
		}

		wp_send_json_success( $usage );
	}

	/**
	 * Render the builder-canvas preview HTML for a form and return it as a string.
	 * Included inline in generate/update responses so React needs no second round trip.
	 *
	 * @param int $form_id
	 * @return string  HTML string, or empty string on failure.
	 */
	private static function render_preview_html( int $form_id ): string {
		$post = get_post( $form_id );
		if ( ! $post || 'everest_form' !== $post->post_type ) {
			return '';
		}

		$form_content = evf_decode( $post->post_content );
		if ( empty( $form_content ) || ! is_array( $form_content ) ) {
			return '';
		}

		if ( ! class_exists( 'EVF_Builder_Fields', false ) ) {
			include_once dirname( EVF_PLUGIN_FILE ) . '/includes/admin/builder/class-evf-builder-page.php';
			include_once dirname( EVF_PLUGIN_FILE ) . '/includes/admin/builder/class-evf-builder-fields.php';
		}

		if ( ! class_exists( 'EVF_Builder_Fields', false ) ) {
			return '';
		}

		$builder            = new EVF_Builder_Fields();
		$builder->form_data = $form_content;

		return $builder->render_ai_preview( $form_content );
	}

	/**
	 * Reliable Pro license check usable inside AJAX handlers.
	 *
	 * evf_get_license_plan() has a shortcut for AJAX/REST/Cron that returns the
	 * cached 'evf_saved_license_plan' option, which defaults to 'personal' even on
	 * free sites. This helper bypasses that shortcut and checks the actual license
	 * key option + Pro plugin activation status instead.
	 *
	 * @return bool True only when EVF Pro is active AND a license key is stored.
	 */
	private static function has_active_license(): bool {
		if ( ! get_option( 'everest-forms-pro_license_key' ) ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return (bool) is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' );
	}

	/**
	 * Return true when the EVF Pro plugin is active (regardless of license state).
	 * Used to distinguish "free user" (upgrade needed) from "Pro user with invalid
	 * license" (activate license) when showing feature-gate messages.
	 *
	 * @return bool
	 */
	private static function is_pro_installed(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return (bool) is_plugin_active( 'everest-forms-pro/everest-forms-pro.php' );
	}

	/**
	 * Check whether the saved form uses a Pro feature (multipart / conversational)
	 * that is unavailable on the current installation, and return a human-readable
	 * notice string. Returns empty string when everything is available.
	 *
	 * Called after create_form() / update_form() so it reflects what was actually
	 * saved rather than what the AI said it would do.
	 *
	 * @param int $form_id
	 * @return string
	 */
	/**
	 * Return the upgrade URL to include alongside a Pro feature notice.
	 * Returns the everestforms.net upgrade link when the user has no active license
	 * (free site or Pro installed but not activated). Returns empty string when the
	 * user has a valid license — they just need to install the addon, not upgrade.
	 *
	 * @return string
	 */
	private static function get_notice_upgrade_url(): string {
		if ( self::has_active_license() ) {
			return ''; // Already Pro — installing the addon is all that's needed.
		}
		return 'https://everestforms.net/upgrade/?utm_source=evf-free&utm_medium=ai-chat&utm_campaign=ai-pro-feature&utm_content=Upgrade+to+Pro';
	}

	private static function get_pro_feature_notice( int $form_id ): string {
		$post = get_post( $form_id );
		if ( ! $post ) {
			return '';
		}
		$data     = evf_decode( $post->post_content );
		$settings = $data['settings'] ?? array();

		$has_license   = self::has_active_license();
		$pro_installed = self::is_pro_installed();

		if ( EVF_AI_Form_Builder::$file_upload_limited ) {
			if ( ! $has_license ) {
				return __( 'Only one file upload field is included — the free plan allows one per form. Upgrade to EVF Pro to add more.', 'everest-forms' );
			}
		}

		if ( ! empty( $settings['enable_multi_part'] ) && evf_string_to_bool( $settings['enable_multi_part'] ) ) {
			if ( ! $has_license ) {
				if ( $pro_installed ) {
					return __( 'Your form is set up as Multi-Part. To activate it, please activate your Everest Forms Pro license under Everest Forms > Settings > License.', 'everest-forms' );
				}
				return __( 'Your form is set up as Multi-Part. This feature requires Everest Forms Pro — please upgrade to enable it.', 'everest-forms' );
			}
			if ( ! class_exists( 'EverestForms_MultiPart' ) ) {
				return __( 'Your form is set up as Multi-Part, but the Multi-Part addon is not active. Please install and activate it from Everest Forms > Addons.', 'everest-forms' );
			}
		}

		if ( ! empty( $settings['enable_conversational_forms'] ) && evf_string_to_bool( $settings['enable_conversational_forms'] ) ) {
			if ( ! $has_license ) {
				if ( $pro_installed ) {
					return __( 'Your form is set up as Conversational. To activate it, please activate your Everest Forms Pro license under Everest Forms > Settings > License.', 'everest-forms' );
				}
				return __( 'Your form is set up as Conversational. This feature requires Everest Forms Pro — please upgrade to enable it.', 'everest-forms' );
			}
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! is_plugin_active( 'everest-forms-conversational-forms/everest-forms-conversational-forms.php' ) ) {
				return __( 'Your form is set up as Conversational, but the Conversational Forms addon is not active. Please install and activate it from Everest Forms > Addons.', 'everest-forms' );
			}
		}

		return '';
	}

	/**
	 * Detect Pro feature requests directly from the user's prompt text, before
	 * calling the gateway. Matches natural-language variations of "multi-part form"
	 * and "conversational form". Returns a context-aware WP_Error when the feature
	 * is unavailable, null otherwise.
	 *
	 * @param string $prompt The user's refinement instruction.
	 * @return WP_Error|null
	 */
	private static function check_pro_feature_from_prompt( string $prompt ): ?WP_Error {
		$has_license   = self::has_active_license();
		$pro_installed = self::is_pro_installed();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( preg_match( '/multi[\s\-]?part|multi[\s\-]?step|wizard|step[\s\-]?by[\s\-]?step/i', $prompt ) ) {
			if ( ! $has_license ) {
				if ( $pro_installed ) {
					$message = __( 'Multi-Part Form requires an active Everest Forms Pro license. Please activate your license under Everest Forms > Settings > License.', 'everest-forms' );
				} else {
					$message = __( 'Multi-Part Form is a Pro feature. Please upgrade to Everest Forms Pro to use this feature.', 'everest-forms' );
				}
				return new WP_Error( 'pro_feature_required', $message );
			}
			if ( ! class_exists( 'EverestForms_MultiPart' ) ) {
				return new WP_Error( 'pro_feature_required', __( 'Multi-Part Forms addon is not active. Please install and activate it from Everest Forms > Addons.', 'everest-forms' ) );
			}
		}

		if ( preg_match( '/conversational|one[\s\-]?question[\s\-]?at[\s\-]?a[\s\-]?time/i', $prompt ) ) {
			if ( ! $has_license ) {
				if ( $pro_installed ) {
					$message = __( 'Conversational Form requires an active Everest Forms Pro license. Please activate your license under Everest Forms > Settings > License.', 'everest-forms' );
				} else {
					$message = __( 'Conversational Form is a Pro feature. Please upgrade to Everest Forms Pro to use this feature.', 'everest-forms' );
				}
				return new WP_Error( 'pro_feature_required', $message );
			}
			if ( ! is_plugin_active( 'everest-forms-conversational-forms/everest-forms-conversational-forms.php' ) ) {
				return new WP_Error( 'pro_feature_required', __( 'Conversational Forms addon is not active. Please install and activate it from Everest Forms > Addons.', 'everest-forms' ) );
			}
		}

		return null;
	}

	/**
	 * Return true when the AI response explicitly requests reCAPTCHA/hCaptcha/Turnstile,
	 * either via the top-level enable_recaptcha flag or via a field with one of those types.
	 *
	 * @param array $ai_response Decoded AI gateway response.
	 * @return bool
	 */
	private static function ai_requests_recaptcha( array $ai_response ): bool {
		if ( ! empty( $ai_response['enable_recaptcha'] ) ) {
			return true;
		}
		foreach ( $ai_response['fields'] ?? array() as $ai_field ) {
			$t = strtolower( sanitize_key( $ai_field['type'] ?? '' ) );
			$l = strtolower( $ai_field['label'] ?? '' );
			if ( in_array( $t, array( 'recaptcha', 'hcaptcha', 'turnstile' ), true )
				|| false !== strpos( $l, 'recaptcha' )
				|| false !== strpos( $l, 'hcaptcha' )
				|| false !== strpos( $l, 'turnstile' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether an AI response requests a Pro-only form type that is unavailable.
	 * A feature is only fully available when BOTH the addon is active AND a Pro license
	 * is active. Returns a context-aware WP_Error for every other combination:
	 *
	 *   no license + no addon  → upgrade to Pro
	 *   no license + addon active → activate your license
	 *   license   + no addon   → install/activate the addon
	 *   license   + addon active → allowed (returns null)
	 *
	 * @param array $ai_response Decoded AI gateway response.
	 * @return WP_Error|null
	 */
	private static function check_pro_feature_availability( array $ai_response ): ?WP_Error {
		$form_type       = $ai_response['form_type'] ?? 'standard';
		$required_addons = $ai_response['required_addons'] ?? array();
		$has_license     = self::has_active_license();

		// Detect intent from either form_type or required_addons (defensive fallback in case
		// the AI sets required_addons correctly but omits/misses the form_type key).
		$is_multipart      = 'multipart' === $form_type || in_array( 'multipart', $required_addons, true );
		$is_conversational = 'conversational' === $form_type || in_array( 'conversational-forms', $required_addons, true );

		$pro_installed = self::is_pro_installed();

		if ( $is_multipart ) {
			$addon_active = class_exists( 'EverestForms_MultiPart' );
			if ( ! $addon_active || ! $has_license ) {
				if ( $has_license ) {
					$message = __( 'Multi-Part Forms addon is not active. Please install and activate it from Everest Forms > Addons.', 'everest-forms' );
				} elseif ( $pro_installed ) {
					$message = __( 'Multi-Part Form requires an active Everest Forms Pro license. Please activate your license under Everest Forms > Settings > License.', 'everest-forms' );
				} else {
					$message = __( 'Multi-Part Form is a Pro feature. Please upgrade to Everest Forms Pro to use this feature.', 'everest-forms' );
				}
				return new WP_Error( 'pro_feature_required', $message );
			}
		}

		if ( $is_conversational ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$addon_active = is_plugin_active( 'everest-forms-conversational-forms/everest-forms-conversational-forms.php' );
			if ( ! $addon_active || ! $has_license ) {
				if ( $has_license ) {
					$message = __( 'Conversational Forms addon is not active. Please install and activate it from Everest Forms > Addons.', 'everest-forms' );
				} elseif ( $pro_installed ) {
					$message = __( 'Conversational Form requires an active Everest Forms Pro license. Please activate your license under Everest Forms > Settings > License.', 'everest-forms' );
				} else {
					$message = __( 'Conversational Form is a Pro feature. Please upgrade to Everest Forms Pro to use this feature.', 'everest-forms' );
				}
				return new WP_Error( 'pro_feature_required', $message );
			}
		}

		return null;
	}

	/**
	 * Return ordered array of multi-part step names for a given form, or empty
	 * array if the form is not a multi-part form.
	 *
	 * @param int $form_id
	 * @return string[]
	 */
	private static function get_multi_part_steps( int $form_id ): array {
		$post = get_post( $form_id );
		if ( ! $post ) {
			return array();
		}
		$data = evf_decode( $post->post_content );
		if ( empty( $data['settings']['enable_multi_part'] ) || ! evf_string_to_bool( $data['settings']['enable_multi_part'] ) ) {
			return array();
		}
		if ( empty( $data['multi_part'] ) ) {
			return array();
		}
		$steps = array();
		foreach ( array_values( $data['multi_part'] ) as $part ) {
			$steps[] = sanitize_text_field( $part['name'] ?? '' );
		}
		return $steps;
	}
}
