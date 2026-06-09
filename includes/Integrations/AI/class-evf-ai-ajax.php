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

		if ( ! EVF_AI_Registration::is_registered() ) {
			EVF_AI_Registration::register();
		}

		if ( ! EVF_AI_Registration::is_registered() ) {
			wp_send_json_error(
				array(
					'message' => __( 'AI features are not available on local or staging sites.', 'everest-forms' ),
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

		$result = EVF_AI_Form_Builder::update_form( $form_id, $ai_response );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'form_id'          => $form_id,
				'form_title'       => get_the_title( $form_id ),
				'fields'           => EVF_AI_Form_Builder::get_field_summary( $form_id ),
				'required_addons'  => $ai_response['required_addons'] ?? array(),
				'multi_part_steps' => self::get_multi_part_steps( $form_id ),
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

		// Auto-register on first use
		if ( ! EVF_AI_Registration::is_registered() ) {
			EVF_AI_Registration::register();
		}

		if ( ! EVF_AI_Registration::is_registered() ) {
			wp_send_json_error(
				array(
					'message' => __( 'AI features are not available on local or staging sites.', 'everest-forms' ),
					'code'    => 'not_registered',
				)
			);
		}

		// Call gateway
		$ai_response = EVF_AI_API::generate_form( $prompt );
		error_log( print_r( $ai_response, true ) );
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

		wp_send_json_success(
			array(
				'form_id'          => $form_id,
				'form_title'       => get_the_title( $form_id ),
				'edit_url'         => admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . $form_id ),
				'tier'             => EVF_AI_Registration::get_tier(),
				'fields'           => EVF_AI_Form_Builder::get_field_summary( $form_id ),
				'required_addons'  => $ai_response['required_addons'] ?? array(),
				'multi_part_steps' => self::get_multi_part_steps( $form_id ),
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
