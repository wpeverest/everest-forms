<?php
/**
 * Process form data
 *
 * @package EverestForms
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Form_Task class.
 */
class EVF_Form_Task {

	/**
	 * Holds errors.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $errors;

	/**
	 * Holds formatted fields.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $form_fields;

	/**
	 * Holds the ID of a successful entry.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $entry_id = 0;

	/**
	 * Form data and settings.
	 *
	 * @since 1.5.0
	 *
	 * @var array
	 */
	public $form_data = array();

	/**
	 * Is hash validation?
	 *
	 * @var 1.7.4
	 */
	public $is_valid_hash = false;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'listen_task' ) );
	}

	/**
	 * Listen to see if this is a return callback or a posted form entry.
	 *
	 * @since 1.0.0
	 */
	public function listen_task() {
		if ( ! empty( $_GET['everest_forms_return'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->entry_confirmation_redirect( '', wp_unslash( $_GET['everest_forms_return'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if ( ! empty( $_POST['everest_forms']['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->do_task( stripslashes_deep( $_POST['everest_forms'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
	}

	/**
	 * Do task of form entry
	 *
	 * @since 1.0.0
	 * @param array $entry $_POST object.
	 */
	public function do_task( $entry ) {
		try {
			$this->errors           = array();
			$this->form_fields      = array();
			$form_id                = absint( $entry['id'] );
			$form                   = evf()->form->get( $form_id );
			$honeypot               = false;
			$response_data          = array();
			$this->ajax_err         = array();
			$this->evf_notice_print = false;

			// Check nonce for form submission.
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'everest-forms_process_submit' ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$this->errors[ $form_id ]['header'] = esc_html__( 'We were unable to process your form, please try again.', 'everest-forms' );
				return $this->errors;
			}

			// Validate form is real and active (published).
			if ( ! $form || 'publish' !== $form->post_status ) {
				$this->errors[ $form_id ]['header'] = esc_html__( 'Invalid form. Please check again.', 'everest-forms' );
				return $this->errors;
			}

			// Formatted form data for hooks.
			$this->form_data = apply_filters( 'everest_forms_process_before_form_data', evf_decode( $form->post_content ), $entry );

			// Pre-process/validate hooks and filter. Data is not validated or cleaned yet so use with caution.
			$entry = apply_filters( 'everest_forms_process_before_filter', $entry, $this->form_data );

			do_action( 'everest_forms_process_before', $entry, $this->form_data );
			do_action( "everest_forms_process_before_{$form_id}", $entry, $this->form_data );

			$ajax_form_submission = isset( $this->form_data['settings']['ajax_form_submission'] ) ? $this->form_data['settings']['ajax_form_submission'] : 0;
			if ( '1' === $ajax_form_submission ) {

				// For the sake of validation we completely remove the validator option.
				update_option( 'evf_validation_error', '' );

				// Prepare fields for entry_save.
				foreach ( $this->form_data['form_fields'] as $field ) {
					if ( '' === isset( $this->form_data['form_fields']['meta-key'] ) ) {
						continue;
					}

					$field_id     = $field['id'];
					$field_type   = $field['type'];
					$field_submit = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';

					if ( 'signature' === $field_type ) {
						$field_submit = isset( $field_submit['signature_image'] ) ? $field_submit['signature_image'] : '';
					}

					$exclude = array( 'title', 'html', 'captcha', 'image-upload', 'file-upload' );

					if ( ! in_array( $field_type, $exclude, true ) ) {
						$this->form_fields[ $field_id ] = array(
							'id'       => $field_id,
							'name'     => sanitize_text_field( $field['label'] ),
							'meta_key' => $this->form_data['form_fields'][ $field_id ]['meta-key'],
							'type'     => $field_type,
							'value'    => evf_sanitize_textarea_field( $field_submit ),
						);
					}
				}
			}

			// Validate fields.
			foreach ( $this->form_data['form_fields'] as $field ) {
				$field_id     = $field['id'];
				$field_type   = $field['type'];
				$field_submit = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';

				do_action( "everest_forms_process_validate_{$field_type}", $field_id, $field_submit, $this->form_data, $field_type );

				if ( 'credit-card' === $field_type ) {
					$this->evf_notice_print = true;
				}

				if ( 'yes' === get_option( 'evf_validation_error' ) && $ajax_form_submission ) {
					$this->ajax_err[] = array( $field_type => $field_id );
					update_option( 'evf_validation_error', '' );
				}
			}

			// If validation issues occur, send the results accordingly.
			if ( $ajax_form_submission && count( $this->ajax_err ) ) {
				$response_data['error']    = $this->ajax_err;
				$response_data['message']  = __( 'Form has not been submitted, please see the errors below.', 'everest-forms' );
				$response_data['response'] = 'error';
				return $response_data;
			}

			// reCAPTCHA check.
			if ( ! apply_filters( 'everest_forms_recaptcha_disabled', false ) ) {
				$recaptcha_type      = get_option( 'everest_forms_recaptcha_type', 'v2' );
				$invisible_recaptcha = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );

				if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
					$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key' );
					$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key' );
				} elseif ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) {
					$site_key   = get_option( 'everest_forms_recaptcha_v2_invisible_site_key' );
					$secret_key = get_option( 'everest_forms_recaptcha_v2_invisible_secret_key' );
				} elseif ( 'v3' === $recaptcha_type ) {
					$site_key   = get_option( 'everest_forms_recaptcha_v3_site_key' );
					$secret_key = get_option( 'everest_forms_recaptcha_v3_secret_key' );
				}

				if ( ! empty( $site_key ) && ! empty( $secret_key ) && isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
					$error = esc_html__( 'Google reCAPTCHA verification failed, please try again later.', 'everest-forms' );
					$token = ! empty( $_POST['g-recaptcha-response'] ) ? evf_clean( wp_unslash( $_POST['g-recaptcha-response'] ) ) : false;

					if ( 'v3' === $recaptcha_type ) {
						$token = ! empty( $_POST['everest_forms']['recaptcha'] ) ? evf_clean( wp_unslash( $_POST['everest_forms']['recaptcha'] ) ) : false;
					}

					$raw_response = wp_safe_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $token );

					if ( ! is_wp_error( $raw_response ) ) {
						$response = json_decode( wp_remote_retrieve_body( $raw_response ) );

						// Check reCAPTCHA response.
						if ( empty( $response->success ) || ( 'v3' === $recaptcha_type && $response->score <= apply_filters( 'everest_forms_recaptcha_v3_threshold', '0.5' ) ) ) {
							if ( 'v3' === $recaptcha_type ) {
								if ( isset( $response->score ) ) {
									$error .= ' (' . esc_html( $response->score ) . ')';
								}
							}
							$this->errors[ $form_id ]['header'] = $error;
							return $this->errors;
						}
					}
				}
			}
			// Initial error check.
			$errors = apply_filters( 'everest_forms_process_initial_errors', $this->errors, $this->form_data );

			if ( ! empty( $errors[ $form_id ] ) ) {
				if ( empty( $errors[ $form_id ]['header'] ) ) {
					$errors[ $form_id ]['header'] = __( 'Form has not been submitted, please see the errors below.', 'everest-forms' );
				}
				$this->errors = $errors;
				return $this->errors;
			}

			// Early honeypot validation - before actual processing.
			if ( isset( $this->form_data['settings']['honeypot'] ) && '1' === $this->form_data['settings']['honeypot'] && ! empty( $entry['hp'] ) ) {
				$honeypot = esc_html__( 'Everest Forms honeypot field triggered.', 'everest-forms' );
			}

			$honeypot = apply_filters( 'everest_forms_process_honeypot', $honeypot, $this->form_fields, $entry, $this->form_data );

			// If spam - return early.
			if ( $honeypot ) {
				$logger = evf_get_logger();
				$logger->notice( sprintf( 'Spam entry for Form ID %d Response: %s', absint( $this->form_data['id'] ), evf_print_r( $entry, true ) ), array( 'source' => 'honeypot' ) );
				return $this->errors;
			}

			// Pass the form created date into the form data.
			$this->form_data['created'] = $form->post_date;

			// Format fields.
			foreach ( (array) $this->form_data['form_fields'] as $field ) {
				$field_id     = $field['id'];
				$field_key    = isset( $field['meta-key'] ) ? $field['meta-key'] : '';
				$field_type   = $field['type'];
				$field_submit = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';

				do_action( "everest_forms_process_format_{$field_type}", $field_id, $field_submit, $this->form_data, $field_key );
			}

			// This hook is for internal purposes and should not be leveraged.
			do_action( 'everest_forms_process_format_after', $this->form_data );

			// Process hooks/filter - this is where most addons should hook
			// because at this point we have completed all field validation and
			// formatted the data.
			$this->form_fields = apply_filters( 'everest_forms_process_filter', $this->form_fields, $entry, $this->form_data );

			do_action( 'everest_forms_process', $this->form_fields, $entry, $this->form_data );
			do_action( "everest_forms_process_{$form_id}", $this->form_fields, $entry, $this->form_data );

			$this->form_fields = apply_filters( 'everest_forms_process_after_filter', $this->form_fields, $entry, $this->form_data );

			// One last error check - don't proceed if there are any errors.
			if ( ! empty( $this->errors[ $form_id ] ) ) {
				if ( empty( $this->errors[ $form_id ]['header'] ) ) {
					$this->errors[ $form_id ]['header'] = esc_html__( 'Form has not been submitted, please see the errors below.', 'everest-forms' );
				}
				return $this->errors;
			}

			// Success - add entry to database.
			$entry_id = $this->entry_save( $this->form_fields, $entry, $this->form_data['id'], $this->form_data );

			// Success - send email notification.
			$this->entry_email( $this->form_fields, $entry, $this->form_data, $entry_id, 'entry' );

			// @todo remove this way of printing notices.
			add_filter( 'everest_forms_success', array( $this, 'check_success_message' ), 10, 2 );

			// Pass completed and formatted fields in POST.
			$_POST['everest-forms']['complete'] = $this->form_fields;

			// Pass entry ID in POST.
			$_POST['everest-forms']['entry_id'] = $entry_id;

			// Post-process hooks.
			do_action( 'everest_forms_process_complete', $this->form_fields, $entry, $this->form_data, $entry_id );
			do_action( "everest_forms_process_complete_{$form_id}", $this->form_fields, $entry, $this->form_data, $entry_id );
		} catch ( Exception $e ) {
			evf_add_notice( $e->getMessage(), 'error' );
			if ( '1' === $ajax_form_submission ) {
				$this->errors[]            = $e->getMessage();
				$response_data['message']  = $this->errors;
				$response_data['response'] = 'error';
				return $response_data;
			}
		}

		$settings = $this->form_data['settings'];
		$message  = isset( $settings['successful_form_submission_message'] ) ? $settings['successful_form_submission_message'] : __( 'Thanks for contacting us! We will be in touch with you shortly.', 'everest-forms' );

		if ( '1' === $ajax_form_submission ) {
			$response_data['message']  = $message;
			$response_data['response'] = 'success';

			// Backward Compatibility Check.
			switch ( $settings['redirect_to'] ) {
				case '0':
					$settings['redirect_to'] = 'same';
					break;

				case '1':
					$settings['redirect_to'] = 'custom_page';
					break;

				case '2':
					$settings['redirect_to'] = 'external_url';
					break;
			}

			if ( isset( $settings['redirect_to'] ) && 'external_url' === $settings['redirect_to'] ) {
				$response_data['redirect_url'] = isset( $settings['external_url'] ) ? esc_url( $settings['external_url'] ) : 'undefined';
			} elseif ( isset( $settings['redirect_to'] ) && 'custom_page' === $settings['redirect_to'] ) {
				$response_data['redirect_url'] = isset( $settings['custom_page'] ) ? get_page_link( absint( $settings['custom_page'] ) ) : 'undefined';
			}

			// Add notice only if credit card is populated in form fields.
			if ( isset( $this->evf_notice_print ) && $this->evf_notice_print ) {
				evf_add_notice( $message, 'success' );
			}

			$this->entry_confirmation_redirect( $this->form_data );

			return $response_data;
		} elseif ( 'same' === $this->form_data['settings']['redirect_to'] ) {
			evf_add_notice( $message, 'success' );
		}

		do_action( 'everest_forms_after_success_message', $this->form_data, $entry );

		$this->entry_confirmation_redirect( $this->form_data );
	}

	/**
	 * Process AJAX form submission.
	 *
	 * @since 1.6.0
	 *
	 * @param mixed $posted_data Posted data.
	 */
	public function ajax_form_submission( $posted_data ) {
		add_filter( 'wp_redirect', array( $this, 'ajax_process_redirect' ), 999 );
		$process = $this->do_task( stripslashes_deep( $posted_data ) );
		return $process;
	}

	/**
	 * Process AJAX redirect.
	 *
	 * @since 1.6.0
	 *
	 * @param string $url Redirect URL.
	 */
	public function ajax_process_redirect( $url ) {
		$form_id = isset( $_POST['everest_forms']['id'] ) ? absint( $_POST['everest_forms']['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $form_id ) ) {
			wp_send_json_error();
		}

		$response = array(
			'form_id'      => $form_id,
			'redirect_url' => $url,
		);

		$response = apply_filters( 'everest_forms_ajax_submit_redirect', $response, $form_id, $url );

		do_action( 'everest_forms_ajax_submit_completed', $form_id, $response );

		wp_send_json_success( $response );
	}

	/**
	 * Check the sucessful message.
	 *
	 * @param bool $status Message status.
	 * @param int  $form_id Form ID.
	 */
	public function check_success_message( $status, $form_id ) {
		if ( isset( $this->form_data['id'] ) && absint( $this->form_data['id'] ) === $form_id ) {
			return true;
		}
		return false;
	}

	/**
	 * Validate the form return hash.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash Base64-encoded hash of form and entry IDs.
	 * @return array|false False for invalid or form id.
	 */
	public function validate_return_hash( $hash = '' ) {
		$query_args = base64_decode( $hash );

		parse_str( $query_args, $output );

		// Verify hash matches.
		if ( wp_hash( $output['form_id'] . ',' . $output['entry_id'] ) !== $output['hash'] ) {
			return false;
		}

		// Get lead and verify it is attached to the form we received with it.
		$entry = evf_get_entry( $output['entry_id'] );

		if ( empty( $entry->form_id ) ) {
			return false;
		}

		if ( $output['form_id'] !== $entry->form_id ) {
			return false;
		}

		return array(
			'form_id'  => absint( $output['form_id'] ),
			'entry_id' => absint( $output['form_id'] ),
			'fields'   => null !== $entry && isset( $entry->fields ) ? $entry->fields : array(),
		);
	}

	/**
	 * Redirects user to a page or URL specified in the form confirmation settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $form_data Form data and settings.
	 * @param string $hash      Base64-encoded hash of form and entry IDs.
	 */
	public function entry_confirmation_redirect( $form_data = '', $hash = '' ) {
		$_POST = array(); // Clear fields after successful form submission.

		// Process return hash.
		if ( ! empty( $hash ) ) {
			$hash_data = $this->validate_return_hash( $hash );

			if ( ! $hash_data || ! is_array( $hash_data ) ) {
				return;
			}

			$this->is_valid_hash = true;
			$this->entry_id      = absint( $hash_data['entry_id'] );
			$this->form_fields   = json_decode( $hash_data['fields'], true );
			$this->form_data     = evf()->form->get(
				absint( $hash_data['form_id'] ),
				array(
					'content_only' => true,
				)
			);
		} else {
			$this->form_data = $form_data;
		}

		$settings = $this->form_data['settings'];

		// Backward Compatibility Check.
		switch ( $settings['redirect_to'] ) {
			case '0':
				$settings['redirect_to'] = 'same';
				break;

			case '1':
				$settings['redirect_to'] = 'custom_page';
				break;

			case '2':
				$settings['redirect_to'] = 'external_url';
				break;
		}

		if ( isset( $settings['redirect_to'] ) && 'custom_page' === $settings['redirect_to'] ) {
			?>
				<script>
				var redirect = '<?php echo esc_url( get_page_link( $settings['custom_page'] ) ); ?>';
				window.setTimeout( function () {
					window.location.href = redirect;
				})
				</script>
			<?php
		} elseif ( isset( $settings['redirect_to'] ) && 'external_url' === $settings['redirect_to'] ) {
			?>
			<script>
				window.setTimeout( function () {
					window.location.href = '<?php echo esc_url( $settings['external_url'] ); ?>';
				})
				</script>
			<?php
		}

		// Redirect if needed, to either a page or URL, after form processing.
		if ( ! empty( $this->form_data['settings']['confirmation_type'] ) && 'message' !== $this->form_data['settings']['confirmation_type'] ) {
			if ( 'redirect' === $this->form_data['settings']['confirmation_type'] ) {
				$url = apply_filters( 'everest_forms_process_smart_tags', $this->form_data['settings']['confirmation_redirect'], $this->form_data, $this->form_fields, $this->entry_id );
			}

			if ( 'page' === $this->form_data['settings']['confirmation_type'] ) {
				$url = get_permalink( (int) $this->form_data['settings']['confirmation_page'] );
			}
		}

		if ( ! empty( $this->form_data['id'] ) ) {
			$form_id = $this->form_data['id'];
		} else {
			return;
		}

		if ( isset( $settings['submission_message_scroll'] ) && $settings['submission_message_scroll'] ) {
			add_filter( 'everest_forms_success_notice_class', array( $this, 'add_scroll_notice_class' ) );
		}

		if ( ! empty( $url ) ) {
			$url = apply_filters( 'everest_forms_process_redirect_url', $url, $form_id, $this->form_fields );
			wp_safe_redirect( esc_url_raw( $url ) );
			do_action( 'everest_forms_process_redirect', $form_id );
			do_action( "everest_forms_process_redirect_{$form_id}", $form_id );
			exit;
		}
	}

	/**
	 * Add scroll notice class.
	 *
	 * @param  array $classes Notice Classes.
	 * @return array of notice classes.
	 */
	public function add_scroll_notice_class( $classes ) {
		$classes[] = 'everest-forms-submission-scroll';

		return $classes;
	}

	/**
	 * Sends entry email notifications.
	 *
	 * @param array  $fields    List of fields.
	 * @param array  $entry     Submitted form entry.
	 * @param array  $form_data Form data and settings.
	 * @param int    $entry_id  Saved entry id.
	 * @param string $context   In which context this email is sent.
	 */
	public function entry_email( $fields, $entry, $form_data, $entry_id, $context = '' ) {
		// Provide the opportunity to override via a filter.
		if ( ! apply_filters( 'everest_forms_entry_email', true, $fields, $entry, $form_data ) ) {
			return;
		}

		// Don't proceed if email notification is not enabled.
		if ( isset( $form_data['settings']['email']['enable_email_notification'] ) && '1' !== $form_data['settings']['email']['enable_email_notification'] ) {
			return;
		}

		// Make sure we have an entry id.
		if ( empty( $this->entry_id ) ) {
			$this->entry_id = (int) $entry_id;
		}

		$fields = apply_filters( 'everest_forms_entry_email_data', $fields, $entry, $form_data );

		if ( ! isset( $form_data['settings']['email']['connection_1'] ) ) {
			$old_email_data                                 = $form_data['settings']['email'];
			$form_data['settings']['email']                 = array();
			$form_data['settings']['email']['connection_1'] = array( 'connection_name' => __( 'Admin Notification', 'everest-forms' ) );

			$email_settings = array( 'evf_to_email', 'evf_from_name', 'evf_from_email', 'evf_reply_to', 'evf_email_subject', 'evf_email_message', 'attach_pdf_to_admin_email', 'show_header_in_attachment_pdf_file', 'conditional_logic_status', 'conditional_option', 'conditionals' );
			foreach ( $email_settings as $email_setting ) {
				$form_data['settings']['email']['connection_1'][ $email_setting ] = isset( $old_email_data[ $email_setting ] ) ? $old_email_data[ $email_setting ] : '';
			}
		}

		$notifications = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();

		foreach ( $notifications as $connection_id => $notification ) :
			$process_email = apply_filters( 'everest_forms_entry_email_process', true, $fields, $form_data, $context, $connection_id );

			if ( ! $process_email ) {
				continue;
			}

			$email        = array();
			$evf_to_email = isset( $notification['evf_to_email'] ) ? $notification['evf_to_email'] : '';

			// Setup email properties.
			/* translators: %s - form name. */
			$email['subject']        = ! empty( $notification['evf_email_subject'] ) ? $notification['evf_email_subject'] : sprintf( esc_html__( 'New %s Entry', 'everest-forms' ), $form_data['settings']['form_title'] );
			$email['address']        = explode( ',', apply_filters( 'everest_forms_process_smart_tags', $evf_to_email, $form_data, $fields, $this->entry_id ) );
			$email['address']        = array_map( 'sanitize_email', $email['address'] );
			$email['sender_name']    = ! empty( $notification['evf_from_name'] ) ? $notification['evf_from_name'] : get_bloginfo( 'name' );
			$email['sender_address'] = ! empty( $notification['evf_from_email'] ) ? $notification['evf_from_email'] : get_option( 'admin_email' );
			$email['reply_to']       = ! empty( $notification['evf_reply_to'] ) ? $notification['evf_reply_to'] : $email['sender_address'];
			$email['message']        = ! empty( $notification['evf_email_message'] ) ? $notification['evf_email_message'] : '{all_fields}';
			$email                   = apply_filters( 'everest_forms_entry_email_atts', $email, $fields, $entry, $form_data );

			$attachment = '';

			// Create new email.
			$emails = new EVF_Emails();
			$emails->__set( 'form_data', $form_data );
			$emails->__set( 'fields', $fields );
			$emails->__set( 'entry_id', $entry_id );
			$emails->__set( 'from_name', $email['sender_name'] );
			$emails->__set( 'from_address', $email['sender_address'] );
			$emails->__set( 'reply_to', $email['reply_to'] );

			/**
			 *  This filter relies on consistent data being passed for the resultant filters to function.
			 *  The third param passed for the filter, $fields, is derived from validation routine, not the DB.
			 */
			$emails->__set( 'attachments', apply_filters( 'everest_forms_email_file_attachments', $attachment, $fields, $form_data, 'entry-email', $connection_id, $entry_id ) );

			// Maybe include Cc and Bcc email addresses.
			if ( 'yes' === get_option( 'everest_forms_enable_email_copies' ) ) {
				if ( ! empty( $notification['evf_carboncopy'] ) ) {
					$emails->__set( 'cc', $notification['evf_carboncopy'] );
				}
				if ( ! empty( $notification['evf_blindcarboncopy'] ) ) {
					$emails->__set( 'bcc', $notification['evf_blindcarboncopy'] );
				}
			}

			$emails = apply_filters( 'everest_forms_entry_email_before_send', $emails );

			// Send entry email.
			foreach ( $email['address'] as $address ) {
				$emails->send( trim( $address ), $email['subject'], $email['message'], '', $connection_id );
			}

		endforeach;
	}

	/**
	 * Saves entry to database.
	 *
	 * @param array $fields    List of form fields.
	 * @param array $entry     User submitted data.
	 * @param int   $form_id   Form ID.
	 * @param array $form_data Prepared form settings.
	 * @return int
	 */
	public function entry_save( $fields, $entry, $form_id, $form_data = array() ) {
		global $wpdb;

		// Check if form has entries disabled.
		if ( isset( $form_data['settings']['disabled_entries'] ) && '1' === $form_data['settings']['disabled_entries'] ) {
			return;
		}

		// Provide the opportunity to override via a filter.
		if ( ! apply_filters( 'everest_forms_entry_save', true, $fields, $entry, $form_data ) ) {
			return;
		}

		do_action( 'everest_forms_process_entry_save', $fields, $entry, $form_id, $form_data );

		$fields      = apply_filters( 'everest_forms_entry_save_data', $fields, $entry, $form_data );
		$browser     = evf_get_browser();
		$user_ip     = evf_get_ip_address();
		$user_device = evf_get_user_device();
		$user_agent  = $browser['name'] . '/' . $browser['platform'] . '/' . $user_device;
		$referer     = ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$entry_id    = false;

		// GDPR enhancements - If user details are disabled globally discard the IP and UA.
		if ( 'yes' === get_option( 'everest_forms_disable_user_details' ) ) {
			$user_agent = '';
			$user_ip    = '';
		}

		$entry_data = array(
			'form_id'         => $form_id,
			'user_id'         => get_current_user_id(),
			'user_device'     => sanitize_text_field( $user_agent ),
			'user_ip_address' => sanitize_text_field( $user_ip ),
			'status'          => 'publish',
			'referer'         => $referer,
			'fields'          => wp_json_encode( $fields ),
			'date_created'    => current_time( 'mysql', true ),
		);

		if ( ! $entry_data['form_id'] ) {
			return new WP_Error( 'no-form-id', __( 'No form ID was found.', 'everest-forms' ) );
		}

		// Create entry.
		$success = $wpdb->insert( $wpdb->prefix . 'evf_entries', $entry_data );

		if ( is_wp_error( $success ) || ! $success ) {
			return new WP_Error( 'could-not-create', __( 'Could not create an entry', 'everest-forms' ) );
		}

		$entry_id = $wpdb->insert_id;

		// Create meta data.
		if ( $entry_id ) {
			foreach ( $fields as $field ) {
				$field = apply_filters( 'everest_forms_entry_save_fields', $field, $form_data, $entry_id );
				// Add only whitelisted fields to entry meta.
				if ( in_array( $field['type'], array( 'html', 'title' ), true ) ) {
					continue;
				}

				// If empty file is supplied, don't store their data nor send email.
				if ( in_array( $field['type'], array( 'image-upload', 'file-upload' ), true ) ) {

					// BW compatibility for previous file uploader.
					if ( isset( $field['value']['file_url'] ) && '' === $field['value']['file_url'] ) {
						continue;
					}
				}

				// If empty label is provided for choice field, don't store their data nor send email.
				if ( in_array( $field['type'], array( 'radio', 'payment-multiple' ), true ) ) {
					if ( isset( $field['value']['label'] ) && '' === $field['value']['label'] ) {
						continue;
					}
				} elseif ( in_array( $field['type'], array( 'checkbox', 'payment-checkbox' ), true ) ) {
					if ( isset( $field['value']['label'] ) && ( empty( $field['value']['label'] ) || '' === current( $field['value']['label'] ) ) ) {
						continue;
					}
				}

				if ( isset( $field['meta_key'], $field['value'] ) && '' !== $field['value'] ) {
					$entry_metadata = array(
						'entry_id'   => $entry_id,
						'meta_key'   => sanitize_key( $field['meta_key'] ),
						'meta_value' => maybe_serialize( $field['value'] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					);

					// Insert entry meta.
					$wpdb->insert( $wpdb->prefix . 'evf_entrymeta', $entry_metadata );
				}
			}
		}

		$this->entry_id = $entry_id;

		do_action( 'everest_forms_complete_entry_save', $entry_id, $fields, $entry, $form_id, $form_data );

		return $this->entry_id;
	}
}
