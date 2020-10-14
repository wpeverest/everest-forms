<?php
/**
 * EverestForms Builder Settings
 *
 * @package EverestForms\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Builder_Settings', false ) ) {
	return new EVF_Builder_Settings();
}

/**
 * EVF_Builder_Settings class.
 */
class EVF_Builder_Settings extends EVF_Builder_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id      = 'settings';
		$this->label   = esc_html__( 'Settings', 'everest-forms' );
		$this->sidebar = true;

		add_action( 'everest_forms_settings_connections_email', array( $this, 'output_connections_list' ) );

		parent::__construct();
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		$sections = apply_filters(
			'everest_forms_builder_settings_section',
			array(
				'general' => esc_html__( 'General', 'everest-forms' ),
				'email'   => esc_html__( 'Email', 'everest-forms' ),
			),
			$this->form_data
		);

		if ( ! empty( $sections ) ) {
			foreach ( $sections as $slug => $section ) {
				$this->add_sidebar_tab( $section, $slug );
				do_action( 'everest_forms_settings_connections_' . $slug, $section );
			}
		}
	}

	/**
	 * Get form data
	 *
	 * @return array form data.
	 */
	private function form_data() {
		$form_data = array();

		if ( ! empty( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_data = evf()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		return $form_data;
	}

	/**
	 * Outputs the connection lists on sidebar.
	 */
	public function output_connections_list() {
		$form_data = $this->form_data();
		$email     = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();

		if ( empty( $email ) ) {
			$email['connection_1'] = array( 'connection_name' => __( 'Admin Notification', 'everest-forms' ) );
		}
		$email_status = isset( $form_data['settings']['email']['enable_email_notification'] ) ? $form_data['settings']['email']['enable_email_notification'] : '1';
		$hidden_class = '1' !== $email_status ? 'everest-forms-hidden' : '';

		?>
			<div class="everest-forms-active-email <?php echo esc_attr( $hidden_class ); ?>">
				<button class="everest-forms-btn everest-forms-btn-primary everest-forms-email-add" data-form_id="<?php echo absint( $_GET['form_id'] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotValidated ?>" data-source="email" data-type="<?php echo esc_attr( 'connection' ); ?>">
					<?php printf( esc_html__( 'Add New Email', 'everest-forms' ) ); ?>
				</button>
					<ul class="everest-forms-active-email-connections-list">
					<?php if ( ! empty( $email ) ) { ?>
						<h4><?php echo esc_html__( 'Email Notifications', 'everest-forms' ); ?> </h4>
						<?php
					}
					if ( ! empty( $email ) ) {
						foreach ( $email as $connection_id => $connection_data ) {
							if ( preg_match( '/connection_/', $connection_id ) ) {
								$connection_name = ! empty( $connection_data['connection_name'] ) ? $connection_data['connection_name'] : '';
								if ( 'connection_1' !== $connection_id ) {
									$remove_class = 'email-remove';
								} else {
									$remove_class = 'email-default-remove';
								}
								?>
									<li class="connection-list" data-connection-id="<?php echo esc_attr( $connection_id ); ?>">
										<a class="user-nickname" href="#"><?php echo esc_html( $connection_name ); ?></a>
										<a href="#"><span class="<?php echo esc_attr( $remove_class ); ?>"><?php esc_html_e( 'Remove', 'everest-forms' ); ?></a>
									</li>
								<?php
							}
						}
					}
					?>
					</ul>
			</div>
			<?php
	}

	/**
	 * Outputs the builder content.
	 */
	public function output_content() {
		$settings     = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();
		$email_status = isset( $this->form_data['settings']['enable_email_notification'] ) ? $this->form_data['settings']['enable_email_notification'] : 0;

		// --------------------------------------------------------------------//
		// General
		// --------------------------------------------------------------------//
		echo '<div class="evf-content-section evf-content-general-settings">';
		echo '<div class="evf-content-section-title">';
		esc_html_e( 'General', 'everest-forms' );
		echo '</div>';
		everest_forms_panel_field(
			'text',
			'settings',
			'form_title',
			$this->form_data,
			esc_html__( 'Form Name', 'everest-forms' ),
			array(
				'default' => isset( $this->form->post_title ) ? $this->form->post_title : '',
				'tooltip' => esc_html__( 'Give a name to this form', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'form_description',
			$this->form_data,
			esc_html__( 'Form description', 'everest-forms' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->form_description ) ? $this->form->form_description : '',
				'tooltip'     => sprintf( esc_html__( 'Give the description to this form', 'everest-forms' ) ),
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'form_disable_message',
			$this->form_data,
			esc_html__( 'Form disabled message', 'everest-forms' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->form_disable_message ) ? $this->form->form_disable_message : __( 'This form is disabled.', 'everest-forms' ),
				'tooltip'     => sprintf( esc_html__( 'Message that shows up if the form is disabled.', 'everest-forms' ) ),
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'successful_form_submission_message',
			$this->form_data,
			esc_html__( 'Successful form submission message', 'everest-forms' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->successful_form_submission_message ) ? $this->form->successful_form_submission_message : __( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms' ),
				/* translators: %1$s - general settings docs url */
				'tooltip'     => sprintf( esc_html__( 'Success message that shows up after submitting form. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/general-settings/#successful-form-submission-message' ) ),
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'submission_message_scroll',
			$this->form_data,
			__( 'Automatically scroll to the submission message', 'everest-forms' ),
			array(
				'default' => '1',
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'redirect_to',
			$this->form_data,
			esc_html__( 'Redirect To', 'everest-forms' ),
			array(
				'default' => 'same',
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Choose where to redirect after form submission. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/general-settings/#redirect-to' ) ),
				'options' => array(
					'same'         => esc_html__( 'Same Page', 'everest-forms' ),
					'custom_page'  => esc_html__( 'Custom Page', 'everest-forms' ),
					'external_url' => esc_html__( 'External URL', 'everest-forms' ),
				),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'custom_page',
			$this->form_data,
			esc_html__( 'Custom Page', 'everest-forms' ),
			array(
				'default' => '0',
				'options' => $this->evf_get_all_pages(),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'external_url',
			$this->form_data,
			esc_html__( 'External URL', 'everest-forms' ),
			array(
				'default' => isset( $this->form->external_url ) ? $this->form->external_url : '',
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'layout_class',
			$this->form_data,
			esc_html__( 'Layout Design', 'everest-forms' ),
			array(
				'default' => '0',
				'tooltip' => esc_html__( 'Choose design template for the Form', 'everest-forms' ),
				'options' => array(
					'default'    => esc_html__( 'Default', 'everest-forms' ),
					'layout-two' => esc_html__( 'Classic Layout', 'everest-forms' ),
				),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'form_class',
			$this->form_data,
			esc_html__( 'Form Class', 'everest-forms' ),
			array(
				'default' => isset( $this->form->form_class ) ? $this->form->form_class : '',
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Enter CSS class names for the form wrapper. Multiple class names should be separated with spaces. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/general-settings/#form-class' ) ),
			)
		);
		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Submit Button', 'everest-forms' ) . '</h4>';
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_text',
			$this->form_data,
			esc_html__( 'Submit button text', 'everest-forms' ),
			array(
				'default' => isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter desired text for submit button.', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_processing_text',
			$this->form_data,
			__( 'Submit button processing text', 'everest-forms' ),
			array(
				'default' => isset( $settings['submit_button_processing_text'] ) ? $settings['submit_button_processing_text'] : __( 'Processing&hellip;', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter the submit button text that you would like the button to display while the form submission is processing.', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_class',
			$this->form_data,
			esc_html__( 'Submit button class', 'everest-forms' ),
			array(
				'default' => isset( $settings['submit_button_class'] ) ? $settings['submit_button_class'] : '',
				'tooltip' => esc_html__( 'Enter CSS class names for submit button. Multiple class names should be separated with spaces.', 'everest-forms' ),
			)
		);
		do_action( 'everest_forms_inline_submit_settings', $this, 'submit', 'connection_1' );
		echo '</div>';
		do_action( 'everest_forms_inline_integrations_settings', $this->form_data, $settings );
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'honeypot',
			$this->form_data,
			esc_html__( 'Enable anti-spam honeypot', 'everest-forms' ),
			array(
				'default' => '1',
			)
		);
		$recaptcha_type   = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$recaptcha_key    = get_option( 'everest_forms_recaptcha_' . $recaptcha_type . '_site_key' );
		$recaptcha_secret = get_option( 'everest_forms_recaptcha_' . $recaptcha_type . '_secret_key' );
		if ( ! empty( $recaptcha_key ) && ! empty( $recaptcha_secret ) ) {
			everest_forms_panel_field(
				'checkbox',
				'settings',
				'recaptcha_support',
				$this->form_data,
				'v3' === $recaptcha_type ? esc_html__( 'Enable Google reCAPTCHA v3', 'everest-forms' ) : ( 'yes' === get_option( 'everest_forms_recaptcha_v2_invisible' ) ? esc_html__( 'Enable Google Invisible reCAPTCHA v2', 'everest-forms' ) : esc_html__( 'Enable Google Checkbox reCAPTCHA v2', 'everest-forms' ) ),
				array(
					'default' => '0',
					/* translators: %1$s - general settings docs url */
					'tooltip' => sprintf( esc_html__( 'Enable Google reCaptcha. Make sure the site key and secret key is set in settings page. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/general-settings/#enable-recaptcha-support' ) ),
				)
			);
		}
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'ajax_form_submission',
			$this->form_data,
			esc_html__( 'Enable Ajax Form Submission', 'everest-forms' ),
			array(
				'default' => isset( $settings['ajax_form_submission'] ) ? $settings['ajax_form_submission'] : 0,
				'tooltip' => esc_html__( 'Enables form submission without reloading the page.', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'disabled_entries',
			$this->form_data,
			esc_html__( 'Disable storing entry information', 'everest-forms' ),
			array(
				'default' => isset( $settings['disabled_entries'] ) ? $settings['disabled_entries'] : 0,
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Disable storing form entries. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/general-settings/#disable-storing-entry-information' ) ),
			)
		);

		do_action( 'everest_forms_inline_general_settings', $this );

		echo '</div>';

		// --------------------------------------------------------------------//
		// Email
		// --------------------------------------------------------------------//
		$form_name = isset( $settings['form_title'] ) ? ' - ' . $settings['form_title'] : '';
		if ( ! isset( $settings['email']['connection_1'] ) ) {
			$settings['email']['connection_1']                   = array( 'connection_name' => __( 'Admin Notification', 'everest-forms' ) );
			$settings['email']['connection_1']['evf_to_email']   = isset( $settings['email']['evf_to_email'] ) ? $settings['email']['evf_to_email'] : '{admin_email}';
			$settings['email']['connection_1']['evf_from_name']  = isset( $settings['email']['evf_from_name'] ) ? $settings['email']['evf_from_name'] : get_bloginfo( 'name', 'display' );
			$settings['email']['connection_1']['evf_from_email'] = isset( $settings['email']['evf_from_email'] ) ? $settings['email']['evf_from_email'] : '{admin_email}';
			$settings['email']['connection_1']['evf_reply_to']   = isset( $settings['email']['evf_reply_to'] ) ? $settings['email']['evf_reply_to'] : '';
			/* translators: %s: Form Name */
			$settings['email']['connection_1']['evf_email_subject'] = isset( $settings['email']['evf_email_subject'] ) ? $settings['email']['evf_email_subject'] : sprintf( esc_html__( 'New Form Entry %s', 'everest-forms' ), $form_name );
			$settings['email']['connection_1']['evf_email_message'] = isset( $settings['email']['evf_email_message'] ) ? $settings['email']['evf_email_message'] : '{all_fields}';

			$email_settings = array( 'attach_pdf_to_admin_email', 'show_header_in_attachment_pdf_file', 'conditional_logic_status', 'conditional_option', 'conditionals' );
			foreach ( $email_settings as $email_setting ) {
				$settings['email']['connection_1'][ $email_setting ] = isset( $settings['email'][ $email_setting ] ) ? $settings['email'][ $email_setting ] : '';
			}

			// Backward compatibility.
			$unique_connection_id = sprintf( 'connection_%s', uniqid() );
			if ( isset( $settings['email']['evf_send_confirmation_email'] ) && '1' === $settings['email']['evf_send_confirmation_email'] ) {
				$settings['email'][ $unique_connection_id ] = array( 'connection_name' => esc_html__( 'User Notification', 'everest-forms' ) );

				foreach ( $email_settings as $email_setting ) {
					$settings['email'][ $unique_connection_id ][ $email_setting ] = isset( $settings['email'][ $email_setting ] ) ? $settings['email'][ $email_setting ] : '';
				}
			}
		}

		$email_status = isset( $settings['email']['enable_email_notification'] ) ? $settings['email']['enable_email_notification'] : '1';
		$hidden_class = '1' !== $email_status ? 'everest-forms-hidden' : '';

		echo '<div class="evf-content-section evf-content-email-settings">';
		echo '<div class="evf-content-section-title">';
		echo '<div class="evf-title">' . esc_html__( 'Email', 'everest-forms' ) . '</div>';
		?>
		<div class="evf-toggle-section">
			<label class="evf-toggle-switch">
				<input type="hidden" name="settings[email][enable_email_notification]" value="0" class="widefat">
				<input type="checkbox" name="settings[email][enable_email_notification]" value="1" <?php echo checked( '1', $email_status, false ); ?> >
				<span class="evf-toggle-switch-wrap"></span>
				<span class="evf-toggle-switch-control"></span>
			</label>
		</div></div>
		<?php
		if ( '1' !== $email_status ) {
			printf( '<p class="email-disable-message everest-forms-notice everest-forms-notice-info">%s</p>', esc_html__( 'Turn on Email settings to manage your email notifications.', 'everest-forms' ) );
		}

		foreach ( $settings['email'] as $connection_id => $connection ) :
			if ( preg_match( '/connection_/', $connection_id ) ) {
				echo '<div class="evf-content-email-settings-inner ' . esc_attr( $hidden_class ) . '" data-connection_id=' . esc_attr( $connection_id ) . '>';

				everest_forms_panel_field(
					'text',
					'email',
					'connection_name',
					$this->form_data,
					'',
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['connection_name'] ) ? $settings['email'][ $connection_id ]['connection_name'] : __( 'Admin Notification', 'everest-forms' ),
						'class'      => 'everest-forms-email-name',
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);

				everest_forms_panel_field(
					'text',
					'email',
					'evf_to_email',
					$this->form_data,
					esc_html__( 'To Address', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_to_email'] ) ? $settings['email'][ $connection_id ]['evf_to_email'] : '{admin_email}',
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the recipient\'s email address (comma separated) to receive form entry notifications. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/email-settings/#to-address' ) ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'email',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				if ( 'yes' === get_option( 'everest_forms_enable_email_copies' ) ) {
					everest_forms_panel_field(
						'text',
						'email',
						'evf_carboncopy',
						$this->form_data,
						esc_html__( 'Cc Address', 'everest-forms' ),
						array(
							'default'    => isset( $settings['email'][ $connection_id ]['evf_carboncopy'] ) ? $settings['email'][ $connection_id ]['evf_carboncopy'] : '',
							'tooltip'    => esc_html__( 'Enter Cc recipient\'s email address (comma separated) to receive form entry notifications.', 'everest-forms' ),
							'smarttags'  => array(
								'type'        => 'fields',
								'form_fields' => 'email',
							),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);
					everest_forms_panel_field(
						'text',
						'email',
						'evf_blindcarboncopy',
						$this->form_data,
						esc_html__( 'Bcc Address', 'everest-forms' ),
						array(
							'default'    => isset( $settings['email'][ $connection_id ]['evf_blindcarboncopy'] ) ? $settings['email'][ $connection_id ]['evf_blindcarboncopy'] : '',
							'tooltip'    => esc_html__( 'Enter Bcc recipient\'s email address (comma separated) to receive form entry notifications.', 'everest-forms' ),
							'smarttags'  => array(
								'type'        => 'fields',
								'form_fields' => 'email',
							),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);
				}
				everest_forms_panel_field(
					'text',
					'email',
					'evf_from_name',
					$this->form_data,
					esc_html__( 'From Name', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_from_name'] ) ? $settings['email'][ $connection_id ]['evf_from_name'] : get_bloginfo( 'name', 'display' ),
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the From Name to be displayed in Email. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/email-settings/#from-name' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'text',
					'email',
					'evf_from_email',
					$this->form_data,
					esc_html__( 'From Address', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_from_email'] ) ? $settings['email'][ $connection_id ]['evf_from_email'] : '{admin_email}',
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the Email address from which you want to send Email. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/email-settings/#from-address' ) ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'email',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'text',
					'email',
					'evf_reply_to',
					$this->form_data,
					esc_html__( 'Reply To', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_reply_to'] ) ? $settings['email'][ $connection_id ]['evf_reply_to'] : '',
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the reply to email address where you want the email to be received when this email is replied. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/email-settings/#reply-to' ) ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'email',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'text',
					'email',
					'evf_email_subject',
					$this->form_data,
					esc_html__( 'Email Subject', 'everest-forms' ),
					array(
						/* translators: %s: Form Name */
						'default'    => isset( $settings['email'][ $connection_id ]['evf_email_subject'] ) ? $settings['email'][ $connection_id ]['evf_email_subject'] : sprintf( esc_html__( 'New Form Entry %s', 'everest-forms' ), $form_name ),
						/* translators: %1$s - General Settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the subject of the email. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/email-settings/#email-subject' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'tinymce',
					'email',
					'evf_email_message',
					$this->form_data,
					esc_html__( 'Email Message', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_email_message'] ) ? $settings['email'][ $connection_id ]['evf_email_message'] : __( '{all_fields}', 'everest-forms' ),
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the message of the email. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/docs/everest-forms/individual-form-settings/email-settings/#email-message' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
						/* translators: %s - all fields smart tag. */
						'after'      => '<p class="desc">' . sprintf( esc_html__( 'To display all form fields, use the %s Smart Tag.', 'everest-forms' ), '<code>{all_fields}</code>' ) . '</p>',
					)
				);

				do_action( 'everest_forms_inline_email_settings', $this, $connection_id );

				echo '</div>';
			}

		endforeach;

		echo '</div>';
		do_action( 'everest_forms_settings_panel_content', $this );
	}

	/**
	 * Get all pages.
	 */
	public function evf_get_all_pages() {
		$pages = array();
		foreach ( get_pages() as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}

		return $pages;
	}
}

return new EVF_Builder_Settings();
