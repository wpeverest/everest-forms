<?php
/**
 * This class handles all (notification) emails sent by Everest Forms.
 *
 * Heavily influenced by the great AffiliateWP plugin by Pippin Williamson.
 * https://github.com/AffiliateWP/AffiliateWP/blob/master/includes/emails/class-affwp-emails.php
 *
 * @package EverestForms\Classes\Emails
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email class.
 */
class EVF_Emails {

	/**
	 * Holds the from address.
	 *
	 * @var string
	 */
	private $from_address;

	/**
	 * Holds the from name.
	 *
	 * @var string
	 */
	private $from_name;

	/**
	 * Holds the reply-to address.
	 *
	 * @var string
	 */
	private $reply_to = false;

	/**
	 * Holds the carbon copy addresses.
	 *
	 * @var string
	 */
	private $cc = false;

	/**
	 * Holds the blind carbon copy addresses.
	 *
	 * @var string
	 */
	private $bcc = false;

	/**
	 * Holds the email content type.
	 *
	 * @var string
	 */
	private $content_type;

	/**
	 * Holds the email headers.
	 *
	 * @var string
	 */
	private $headers;

	/**
	 * Holds the email attachments.
	 *
	 * @var string
	 */
	public $attachments = '';

	/**
	 * Whether to send email in HTML.
	 *
	 * @var bool
	 */
	private $html = true;

	/**
	 * The email template to use.
	 *
	 * @var string
	 */
	private $template;

	/**
	 * Form data.
	 *
	 * @var array
	 */
	public $form_data = array();

	/**
	 * Fields, formatted, and sanitized.
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Entry ID.
	 *
	 * @var int
	 */
	public $entry_id = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( 'none' === $this->get_template() ) {
			$this->html = false;
		}

		// Hooks.
		add_action( 'everest_forms_email_send_before', array( $this, 'send_before' ) );
		add_action( 'everest_forms_email_send_after', array( $this, 'send_after' ) );
	}

	/**
	 * Set a property.
	 *
	 * @param string $key   Object property key.
	 * @param mixed  $value Object property value.
	 */
	public function __set( $key, $value ) {
		$this->$key = $value;
	}

	/**
	 * Get the email from name.
	 *
	 * @return string The email from name.
	 */
	public function get_from_name() {
		if ( ! empty( $this->from_name ) ) {
			$this->from_name = $this->process_tag( $this->from_name );
		} else {
			$this->from_name = get_bloginfo( 'name' );
		}

		return apply_filters( 'everest_forms_email_from_name', wp_specialchars_decode( $this->from_name ), $this );
	}

	/**
	 * Get the email from address.
	 *
	 * @return string The email from address.
	 */
	public function get_from_address() {
		if ( ! empty( $this->from_address ) ) {
			$this->from_address = $this->process_tag( $this->from_address );
		} else {
			$this->from_address = get_option( 'admin_email' );
		}

		return apply_filters( 'everest_forms_email_from_address', $this->from_address, $this );
	}

	/**
	 * Get the email reply-to.
	 *
	 * @return string The email reply-to address.
	 */
	public function get_reply_to() {
		if ( ! empty( $this->reply_to ) ) {
			$this->reply_to = $this->process_tag( $this->reply_to );

			if ( ! is_email( $this->reply_to ) ) {
				$this->reply_to = false;
			}
		}

		return apply_filters( 'everest_forms_email_reply_to', $this->reply_to, $this );
	}

	/**
	 * Get the email carbon copy addresses.
	 *
	 * @return string The email carbon copy addresses.
	 */
	public function get_cc() {
		if ( ! empty( $this->cc ) ) {
			$this->cc  = $this->process_tag( $this->cc );
			$addresses = array_map( 'trim', explode( ',', $this->cc ) );

			foreach ( $addresses as $key => $address ) {
				if ( ! is_email( $address ) ) {
					unset( $addresses[ $key ] );
				}
			}

			$this->cc = implode( ',', $addresses );
		}

		return apply_filters( 'everest_forms_email_cc', $this->cc, $this );
	}

	/**
	 * Get the email blind carbon copy addresses.
	 *
	 * @return string The email blind carbon copy addresses.
	 */
	public function get_bcc() {
		if ( ! empty( $this->bcc ) ) {
			$this->bcc = $this->process_tag( $this->bcc );
			$addresses = array_map( 'trim', explode( ',', $this->bcc ) );

			foreach ( $addresses as $key => $address ) {
				if ( ! is_email( $address ) ) {
					unset( $addresses[ $key ] );
				}
			}

			$this->bcc = implode( ',', $addresses );
		}

		return apply_filters( 'everest_forms_email_bcc', $this->bcc, $this );
	}

	/**
	 * Get the email content type.
	 *
	 * @return string The email content type.
	 */
	public function get_content_type() {
		if ( ! $this->content_type && $this->html ) {
			$this->content_type = apply_filters( 'everest_forms_email_default_content_type', 'text/html', $this );
		} elseif ( ! $this->html ) {
			$this->content_type = 'text/plain';
		}

		return apply_filters( 'everest_forms_email_content_type', $this->content_type, $this );
	}

	/**
	 * Get the email headers.
	 *
	 * @return string The email headers.
	 */
	public function get_headers() {
		if ( ! $this->headers ) {
			$this->headers = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
			if ( $this->get_reply_to() ) {
				$this->headers .= "Reply-To: {$this->get_reply_to()}\r\n";
			}
			if ( $this->get_cc() ) {
				$this->headers .= "Cc: {$this->get_cc()}\r\n";
			}
			if ( $this->get_bcc() ) {
				$this->headers .= "Bcc: {$this->get_bcc()}\r\n";
			}
			$this->headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";
		}

		return apply_filters( 'everest_forms_email_headers', $this->headers, $this );
	}

	/**
	 * Build the email.
	 *
	 * @param  string $message The email message.
	 * @return string
	 */
	public function build_email( $message ) {
		if ( false === $this->html ) {
			$message = $this->process_tag( $message, false, true );
			$message = str_replace( '{all_fields}', $this->everest_forms_html_field_value( false ), $message );

			return apply_filters( 'everest_forms_email_message', $message, $this );
		}

		ob_start();

		evf_get_template( 'emails/header-' . $this->get_template() . '.php' );

		// Hooks into the email header.
		do_action( 'everest_forms_email_header', $this );

		evf_get_template( 'emails/body-' . $this->get_template() . '.php' );

		// Hooks into the email body.
		do_action( 'everest_forms_email_body', $this );

		evf_get_template( 'emails/footer-' . $this->get_template() . '.php' );

		// Hooks into the email footer.
		do_action( 'everest_forms_email_footer', $this );

		$message = $this->process_tag( $message, false );
		$message = nl2br( $message );

		$body    = ob_get_clean();
		$message = str_replace( '{email}', $message, $body );
		$message = str_replace( '{all_fields}', $this->everest_forms_html_field_value( true ), $message );
		$message = make_clickable( $message );

		return apply_filters( 'everest_forms_email_message', $message, $this );
	}

	/**
	 * Send the email.
	 *
	 * @param string $to The To address.
	 * @param string $subject The subject line of the email.
	 * @param string $message The body of the email.
	 * @param array  $attachments Attachments to the email.
	 *
	 * @return bool
	 */
	public function send( $to, $subject, $message, $attachments = '' ) {
		if ( ! did_action( 'init' ) && ! did_action( 'admin_init' ) ) {
			evf_doing_it_wrong( __FUNCTION__, __( 'You cannot send emails with EVF_Emails until init/admin_init has been reached', 'everest-forms' ), null );
			return false;
		}

		// Don't send anything if emails have been disabled.
		if ( $this->is_email_disabled() ) {
			return false;
		}

		// Don't send if email address is invalid.
		if ( ! is_email( $to ) ) {
			return false;
		}

		// Hooks before email is sent.
		do_action( 'everest_forms_email_send_before', $this );

		$message           = $this->build_email( $message );
		$this->attachments = apply_filters( 'everest_forms_email_attachments', $this->attachments, $this );
		$subject           = evf_decode_string( $this->process_tag( $subject ) );

		// Let's do this.
		$sent = wp_mail( $to, $subject, $message, $this->get_headers(), $this->attachments );

		// Hooks after the email is sent.
		do_action( 'everest_forms_email_send_after', $this );

		return $sent;
	}

	/**
	 * Add filters/actions before the email is sent.
	 */
	public function send_before() {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Remove filters/actions after the email is sent.
	 */
	public function send_after() {
		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Converts text formatted HTML. This is primarily for turning line breaks
	 * into <p> and <br/> tags.
	 *
	 * @param  string $message Text to convert.
	 * @return string
	 */
	public function text_to_html( $message ) {
		if ( 'text/html' === $this->content_type || true === $this->html ) {
			$message = wpautop( $message );
		}

		return $message;
	}

	/**
	 * Processes a smart tag.
	 *
	 * @param string $string     String that may contain tags.
	 * @param bool   $sanitize   Toggle to maybe sanitize.
	 * @param bool   $linebreaks Toggle to process linebreaks.
	 *
	 * @return string
	 */
	public function process_tag( $string = '', $sanitize = true, $linebreaks = false ) {
		$tag = apply_filters( 'everest_forms_process_smart_tags', $string, $this->form_data, $this->fields, $this->entry_id );
		$tag = evf_decode_string( $tag );

		if ( $sanitize ) {
			if ( $linebreaks ) {
				$tag = evf_sanitize_textarea_field( $tag );
			} else {
				$tag = sanitize_text_field( $tag );
			}
		}

		return $tag;
	}

	/**
	 * Process the all fields smart tag if present.
	 *
	 * @param  bool $html Toggle to use HTML or plaintext.
	 * @return string
	 */
	public function everest_forms_html_field_value( $html = true ) {
		if ( empty( $this->fields ) ) {
			return '';
		}

		$message = '';

		if ( $html ) {
			/*
			 * HTML emails.
			 */
			ob_start();

			// Hooks into the email field.
			do_action( 'everest_forms_email_field', $this );

			evf_get_template( 'emails/field-' . $this->get_template() . '.php' );

			$field_template = ob_get_clean();

			$x = 1;
			foreach ( $this->fields as $field ) {

				if (
					! apply_filters( 'everest_forms_email_display_empty_fields', false ) &&
					( empty( $field['value'] ) && '0' !== $field['value'] )
				) {
					continue;
				}

				$field_val  = empty( $field['value'] ) && '0' !== $field['value'] ? '<em>' . __( '(empty)', 'everest-forms' ) . '</em>' : $field['value'];
				$field_name = $field['name'];

				if ( is_array( $field_val ) ) {
					$field_html = array();

					foreach ( $field_val as $meta_val ) {
						$field_html[] = $meta_val;
					}

					$field_val = implode( ', ', $field_html );
				}

				if ( empty( $field_name ) ) {
					$field_name = sprintf(
						/* translators: %d - field ID. */
						esc_html__( 'Field ID #%d', 'everest-forms' ),
						absint( $field['id'] )
					);
				}

				$field_item = $field_template;
				if ( 1 === $x ) {
					$field_item = str_replace( 'border-top:1px solid #dddddd;', '', $field_item );
				}

				$field_item  = str_replace( '{field_name}', $field_name, $field_item );
				$field_value = apply_filters( 'everest_forms_html_field_value', evf_decode_string( $field_val ), $field['value'], $this->form_data, 'email-html' );
				$field_item  = str_replace( '{field_value}', $field_value, $field_item );

				$message .= wpautop( $field_item );
				$x ++;
			}
		} else {
			/*
			 * Plain Text emails.
			 */
			foreach ( $this->fields as $field ) {
				if ( ! apply_filters( 'everest_forms_email_display_empty_fields', false ) && ( empty( $field['value'] ) && '0' !== $field['value'] ) ) {
					continue;
				}

				$field_val  = empty( $field['value'] ) && '0' !== $field['value'] ? esc_html__( '(empty)', 'everest-forms' ) : $field['value'];
				$field_name = $field['name'];

				if ( is_array( $field_val ) ) {
					$field_html = array();

					foreach ( $field_val as $meta_val ) {
						$field_html[] = $meta_val;
					}

					$field_val = implode( ', ', $field_html );
				}

				if ( empty( $field_name ) ) {
					$field_name = sprintf(
						/* translators: %d - field ID. */
						esc_html__( 'Field ID #%d', 'everest-forms' ),
						absint( $field['id'] )
					);
				}

				$message    .= '--- ' . evf_decode_string( $field_name ) . " ---\r\n\r\n";
				$field_value = evf_decode_string( $field_val ) . "\r\n\r\n";
				$message    .= apply_filters( 'everest_forms_plaintext_field_value', $field_value, $field['value'], $this->form_data, 'email-plain' );
			}
		}

		if ( empty( $message ) ) {
			$empty_message = esc_html__( 'An empty form was submitted.', 'everest-forms' );
			$message       = $html ? wpautop( $empty_message ) : $empty_message;
		}

		return $message;
	}

	/**
	 * Email kill switch if needed.
	 *
	 * @return bool
	 */
	public function is_email_disabled() {
		return (bool) apply_filters( 'everest_forms_disable_all_emails', false, $this );
	}

	/**
	 * Get the enabled email template.
	 *
	 * @todo Email template.
	 *
	 * @return string When filtering return 'none' to switch to text/plain email.
	 */
	public function get_template() {
		if ( ! $this->template ) {
			$this->template = get_option( 'everest_forms_email_template', 'default' );
		}

		return apply_filters( 'everest_forms_email_template', $this->template );
	}
}
