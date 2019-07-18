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
		if ( ! empty( $_GET['everest_forms_return'] ) ) { // WPCS: CSRF ok.
			$this->entry_confirmation_redirect( '', $_GET['everest_forms_return'] ); // WPCS: sanitization ok, CSRF ok.
		}

		if ( ! empty( $_POST['everest_forms']['id'] ) ) { // WPCS: CSRF ok.
			$this->do_task( stripslashes_deep( $_POST['everest_forms'] ) ); // WPCS: sanitization ok, CSRF ok.
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
			$this->errors      = array();
			$this->form_fields = array();
			$form_id           = absint( $entry['id'] );
			$form              = EVF()->form->get( $form_id );
			$honeypot          = false;

			// Check nonce for form submission.
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'everest-forms_process_submit' ) ) { // WPCS: input var ok, sanitization ok.
				$this->errors[ $form_id ]['header'] = esc_html__( 'We were unable to process your form, please try again.', 'everest-forms' );
				return;
			}

			// Validate form is real and active (published).
			if ( ! $form || 'publish' !== $form->post_status ) {
				$this->errors[ $form_id ]['header'] = esc_html__( 'Invalid form. Please check again.', 'everest-forms' );
				return;
			}

			// Formatted form data for hooks.
			$this->form_data = apply_filters( 'everest_forms_process_before_form_data', evf_decode( $form->post_content ), $entry );

			// Pre-process/validate hooks and filter. Data is not validated or cleaned yet so use with caution.
			$entry = apply_filters( 'everest_forms_process_before_filter', $entry, $this->form_data );

			do_action( 'everest_forms_process_before', $entry, $this->form_data );
			do_action( "everest_forms_process_before_{$form_id}", $entry, $this->form_data );

			// Validate fields.
			foreach ( $this->form_data['form_fields'] as $field ) {
				$field_id     = $field['id'];
				$field_type   = $field['type'];
				$field_submit = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';

				do_action( "everest_forms_process_validate_{$field_type}", $field_id, $field_submit, $this->form_data, $field_type );
			}

			// reCAPTCHA check.
			$recaptcha_type = get_option( 'everest_forms_recaptcha_type', 'v2' );

			if ( 'v2' === $recaptcha_type ) {
				$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key' );
				$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key' );
			} else {
				$site_key   = get_option( 'everest_forms_recaptcha_v3_site_key' );
				$secret_key = get_option( 'everest_forms_recaptcha_v3_secret_key' );
			}

			if ( ! empty( $site_key ) && ! empty( $secret_key ) && isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] ) {
				if ( ( 'v2' === $recaptcha_type && ! empty( $_POST['g-recaptcha-response'] ) ) || ( 'v3' === $recaptcha_type && ! empty( $_POST['g-recaptcha-hidden'] ) ) ) {
					$response = 'v2' === $recaptcha_type ? evf_clean( wp_unslash( $_POST['g-recaptcha-response'] ) ) : evf_clean( wp_unslash( $_POST['g-recaptcha-hidden'] ) ); // PHPCS: input var ok.
					$raw_data = wp_safe_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response );

					if ( ! is_wp_error( $raw_data ) ) {
						$data = json_decode( wp_remote_retrieve_body( $raw_data ) );

						// Check reCAPTCHA response.
						if ( empty( $data->success ) || ( isset( $data->hostname ) && evf_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) !== $data->hostname ) || ( isset( $data->action, $data->score ) && ( 'everest_form' !== $data->action && 0.5 > floatval( $data->score ) ) ) ) {
							$this->errors[ $form_id ]['header'] = esc_html__( 'Incorrect reCAPTCHA, please try again.', 'everest-forms' );
							return;
						}
					}
				} else {
					// @todo This error message is not delivered in frontend. Need to fix :)
					$this->errors[ $form_id ]['recaptcha'] = esc_html__( 'reCAPTCHA is required.', 'everest-forms' );
				}
			}

			// Initial error check.
			$errors = apply_filters( 'everest_forms_process_initial_errors', $this->errors, $this->form_data );

			if ( ! empty( $errors[ $form_id ] ) ) {
				if ( empty( $errors[ $form_id ]['header'] ) ) {
					$errors[ $form_id ]['header'] = __( 'Form has not been submitted, please see the errors below.', 'everest-forms' );
				}
				$this->errors = $errors;
				return;
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
				return;
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
				return;
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
		}

		$message = isset( $this->form_data['settings']['successful_form_submission_message'] ) ? $this->form_data['settings']['successful_form_submission_message'] : __( 'Thanks for contacting us! We will be in touch with you shortly.', 'everest-forms' );

		evf_add_notice( $message, 'success' );

		do_action( 'everest_forms_after_success_message', $this->form_data, $entry );

		$this->entry_confirmation_redirect( $this->form_data );
	}

	/**
	 * Check the sucessful message.
	 *
	 * @param bool $status Message status.
	 * @param int  $form_id Form ID.
	 */
	public function check_success_message( $status, $form_id ) {
		if ( isset( $this->form_data['id'] ) && $form_id === absint( $this->form_data['id'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Validate the form return hash.
	 *
	 * @since  1.0.0
	 * @param  string $hash Hash data.
	 * @return mixed false for invalid or form id.
	 */
	public function validate_return_hash( $hash = '' ) {
		$query_args = base64_decode( $hash );

		parse_str( $query_args, $output );

		// Verify hash matches.
		if ( wp_hash( $output['form_id'] . ',' . $output['entry_id'] ) !== $output['hash'] ) {
			return false;
		}

		// Get lead and verify it is attached to the form we received with it.
		$entry = EVF()->entry->get( $output['entry_id'] );

		if ( $output['form_id'] !== $entry->form_id ) {
			return false;
		}

		return absint( $output['form_id'] );
	}

	/**
	 * Redirects user to a page or URL specified in the form confirmation settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $form_data Form data and settings.
	 * @param string $hash      Hash data.
	 */
	public function entry_confirmation_redirect( $form_data = '', $hash = '' ) {
		$_POST = array(); // clear fields after successful form submission

		if ( ! empty( $hash ) ) {
			$form_id = $this->validate_return_hash( $hash );

			if ( ! $form_id ) {
				return;
			}

			// Get form.
			$this->form_data = EVF()->form->get(
				$form_id,
				array(
					'content_only' => true,
				)
			);
		} else {
			$this->form_data = $form_data;
		}

		$settings = $this->form_data['settings'];
		if ( isset( $settings['redirect_to'] ) && '1' === $settings['redirect_to'] ) {
			?>
				<script>
				var redirect = '<?php echo get_permalink( $settings['custom_page'] ); ?>';
				window.setTimeout( function () {
					window.location.href = redirect;
				})
				</script>
			<?php
		} elseif ( isset( $settings['redirect_to'] ) && '2' == $settings['redirect_to'] ) {
			?>
			<script>
				window.setTimeout( function () {
					window.location.href = '<?php echo $settings['external_url']; ?>';
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
			wp_redirect( esc_url_raw( $url ) );
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
			$emails->__set( 'attachments', apply_filters( 'everest_forms_email_file_attachments', $attachment, $entry, $form_data, 'entry-email', $connection_id ) );

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
				$emails->send( trim( $address ), $email['subject'], $email['message'] );
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

		$fields     = apply_filters( 'everest_forms_entry_save_data', $fields, $entry, $form_data );
		$browser    = evf_get_browser();
		$user_ip    = evf_get_ip_address();
		$user_agent = $browser['name'] . '/' . $browser['platform'];
		$entry_id   = false;

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
			'referer'         => $_SERVER['HTTP_REFERER'],
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

				if ( isset( $field['value'], $field['meta_key'] ) && '' !== $field['value'] ) {
					$field_value    = is_array( $field['value'] ) ? serialize( $field['value'] ) : $field['value'];
					$entry_metadata = array(
						'entry_id'   => $entry_id,
						'meta_key'   => $field['meta_key'],
						'meta_value' => $field_value,
					);

					// Insert entry meta.
					$wpdb->insert( $wpdb->prefix . 'evf_entrymeta', $entry_metadata );
				}
			}
		}

		do_action( 'everest_forms_complete_entry_save', $entry_id, $fields, $entry, $form_id, $form_data );

		return $entry_id;
	}
}
