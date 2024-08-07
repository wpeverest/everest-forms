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

		add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_custom_css_js_section' ), 11, 2 );
		add_action( 'everest_forms_settings_panel_content', array( $this, 'add_custom_css_js_settings' ), 111, 1 );

		parent::__construct();
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		$sections = apply_filters(
			'everest_forms_builder_settings_section',
			array(
				'general'      => esc_html__( 'General', 'everest-forms' ),
				'email'        => esc_html__( 'Email', 'everest-forms' ),
				'confirmation' => esc_html__( 'Confirmations', 'everest-forms' ),
				'security'     => esc_html__( 'Anti-Spam and Security', 'everest-forms' ),
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

		?>
			<div class="everest-forms-active-email">
			<button class="everest-forms-btn everest-forms-btn-primary everest-forms-email-add" data-form_id="<?php echo isset( $_GET['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification ?>" data-source="email" data-type="<?php echo esc_attr( 'connection' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
					<path d="M12 21.95c-.6 0-1-.4-1-1v-8H3.1c-.6 0-1-.4-1-1s.4-1 1-1H11v-7.9c0-.6.4-1 1-1s1 .4 1 1v7.9h7.9c.6 0 1 .4 1 1s-.4 1-1 1H13v8c0 .6-.4 1-1 1Z"/>
			</svg>
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
								$remove_class    = 'everest-forms-email-remove';
								$duplicate_class = 'everest-forms-duplicate-email';
								$preview_class   = 'everest-forms-email-preview';
							} else {
								$remove_class    = 'everest-forms-email-default-remove';
								$duplicate_class = 'everest-forms-email-default-duplicate';
								$preview_class   = 'everest-forms-email-preview';
							}
							if ( isset( $email['enable_email_notification'] ) && '0' === $email['enable_email_notification'] ) {
								$email_status = isset( $email['enable_email_notification'] ) ? $email['enable_email_notification'] : '1';
							} else {
								$email_status = isset( $email[ $connection_id ]['enable_email_notification'] ) ? $email[ $connection_id ]['enable_email_notification'] : '1';
							}
							?>
									<li class="connection-list" data-connection-id="<?php echo esc_attr( $connection_id ); ?>">
										<a class="user-nickname" href="#"><?php echo esc_html( $connection_name ); ?></a>
										<div class="evf-email-side-section">
											<div class="evf-toggle-section">
												<span class="everest-forms-toggle-form">
													<input type="hidden" name="settings[email][<?php echo esc_attr( $connection_id ); ?>][enable_email_notification]" value="0" class="widefat">
													<input type="checkbox" class="evf-email-toggle" name="settings[email][<?php echo esc_attr( $connection_id ); ?>][enable_email_notification]" value="1" data-connection-id="<?php echo esc_attr( $connection_id ); ?>" <?php echo checked( '1', $email_status, false ); ?> >
													<span class="slider round"></span>
													</span>
											</div>
											<span class="evf-vertical-divider"></span>
											<a href="#">
												<span class="<?php echo esc_attr( $remove_class ); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
													<path fill-rule="evenodd" d="M9.293 3.293A1 1 0 0 1 10 3h4a1 1 0 0 1 1 1v1H9V4a1 1 0 0 1 .293-.707ZM7 5V4a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1h4a1 1 0 1 1 0 2h-1v13a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7H3a1 1 0 1 1 0-2h4Zm1 2h10v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7h2Zm2 3a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm5 7v-6a1 1 0 1 0-2 0v6a1 1 0 1 0 2 0Z" clip-rule="evenodd"/>
												</svg>
											</a>
											<span class="evf-vertical-divider"></span>
										<?php
										$preview_url = esc_url(
											add_query_arg(
												array(
													'evf_email_preview' => $connection_id,
													'form_id' => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0,
												),
												home_url()
											)
										);
										?>
											<a class="<?php echo esc_attr( $preview_class ); ?>" target="__blank" data-connection-id="<?php echo esc_attr( $connection_id ); ?>" href="<?php echo esc_url( $preview_url ); ?>">
												<span class="<?php echo esc_attr( $preview_class ); ?>">
												<svg  xmlns="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/1999/svg"
												viewBox="0 0 442.04 442.04" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g>
												<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
												<g id="SVGRepo_iconCarrier"> <g> <g>
													<path d="M221.02,341.304c-49.708,0-103.206-19.44-154.71-56.22C27.808,257.59,4.044,230.351,3.051,229.203 c-4.068-4.697-4.068-11.669,0-16.367c0.993-1.146,24.756-28.387,63.259-55.881c51.505-36.777,105.003-56.219,154.71-56.219 c49.708,0,103.207,19.441,154.71,56.219c38.502,27.494,62.266,54.734,63.259,55.881c4.068,4.697,4.068,11.669,0,16.367 c-0.993,1.146-24.756,28.387-63.259,55.881C324.227,321.863,270.729,341.304,221.02,341.304z M29.638,221.021 c9.61,9.799,27.747,27.03,51.694,44.071c32.83,23.361,83.714,51.212,139.688,51.212s106.859-27.851,139.688-51.212 c23.944-17.038,42.082-34.271,51.694-44.071c-9.609-9.799-27.747-27.03-51.694-44.071 c-32.829-23.362-83.714-51.212-139.688-51.212s-106.858,27.85-139.688,51.212C57.388,193.988,39.25,211.219,29.638,221.021z"></path> </g> <g> <path d="M221.02,298.521c-42.734,0-77.5-34.767-77.5-77.5c0-42.733,34.766-77.5,77.5-77.5c18.794,0,36.924,6.814,51.048,19.188 c5.193,4.549,5.715,12.446,1.166,17.639c-4.549,5.193-12.447,5.714-17.639,1.166c-9.564-8.379-21.844-12.993-34.576-12.993 c-28.949,0-52.5,23.552-52.5,52.5s23.551,52.5,52.5,52.5c28.95,0,52.5-23.552,52.5-52.5c0-6.903,5.597-12.5,12.5-12.5 s12.5,5.597,12.5,12.5C298.521,263.754,263.754,298.521,221.02,298.521z"></path> </g> <g> <path d="M221.02,246.021c-13.785,0-25-11.215-25-25s11.215-25,25-25c13.786,0,25,11.215,25,25S234.806,246.021,221.02,246.021z"></path>
												</g> </g> </g></svg>
											<a href="#" class="everest-forms-email-duplicate">
												<span class="<?php echo esc_attr( $duplicate_class ); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 25">
													<path fill-rule="evenodd" d="M3.033 3.533c.257-.257.605-.4.968-.4h9A1.368 1.368 0 0 1 14.369 4.5v1a.632.632 0 0 0 1.263 0v-1a2.632 2.632 0 0 0-2.631-2.632H4A2.632 2.632 0 0 0 1.368 4.5v9A2.631 2.631 0 0 0 4 16.131h1a.632.632 0 0 0 0-1.263H4A1.368 1.368 0 0 1 2.631 13.5v-9c0-.363.144-.711.401-.968Zm6.598 7.968A1.37 1.37 0 0 1 11 10.132h9c.756 0 1.368.613 1.368 1.369v9c0 .755-.612 1.368-1.368 1.368h-9A1.368 1.368 0 0 1 9.63 20.5v-9ZM11 8.869A2.632 2.632 0 0 0 8.368 11.5v9A2.632 2.632 0 0 0 11 23.131h9a2.632 2.632 0 0 0 2.63-2.631v-9A2.632 2.632 0 0 0 20 8.87h-9Z" clip-rule="evenodd"></path>
												</svg>
											</a>
										</div>
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
		$settings = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();

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
				'tooltip' => sprintf( esc_html__( 'Enter CSS class names for the form wrapper. Multiple class names should be separated with spaces. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/general-settings/#7-toc-title' ) ),
			)
		);

		do_action( 'everest_forms_field_required_indicators', $this->form_data, $settings );

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
			'toggle',
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
			'toggle',
			'settings',
			'disabled_entries',
			$this->form_data,
			esc_html__( 'Disable storing entry information', 'everest-forms' ),
			array(
				'default' => isset( $settings['disabled_entries'] ) ? $settings['disabled_entries'] : 0,
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Disable storing form entries. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/general-settings/#13-toc-title' ) ),
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

		echo "<div class = 'evf-email-settings-wrapper'>";

		foreach ( $settings['email'] as $connection_id => $connection ) :
			if ( preg_match( '/connection_/', $connection_id ) ) {
				// Backward Compatibility.
				if ( isset( $settings['email']['enable_email_notification'] ) && '0' === $settings['email']['enable_email_notification'] ) {
					$email_status = isset( $settings['email']['enable_email_notification'] ) ? $settings['email']['enable_email_notification'] : '1';
				} else {
					$email_status = isset( $settings['email'][ $connection_id ]['enable_email_notification'] ) ? $settings['email'][ $connection_id ]['enable_email_notification'] : '1';
				}
				$hidden_class                = '1' !== $email_status ? 'everest-forms-hidden' : '';
				$hidden_enable_setting_class = '1' === $email_status ? 'everest-forms-hidden' : '';
				$toggler_hide_class          = isset( $toggler_hide_class ) ? 'style=display:none;' : '';
				echo '<div class="evf-content-section evf-content-email-settings" ' . esc_attr( $toggler_hide_class ) . '>';
				echo '<div class="evf-content-section-title" ' . esc_attr( $toggler_hide_class ) . '>';
				echo '<div class="evf-title">' . esc_html__( 'Email', 'everest-forms' ) . '</div>';
				?>
				<div class="evf-enable-email-toggle <?php echo esc_attr( $hidden_enable_setting_class ); ?>"><img src="<?php echo esc_url( plugin_dir_url( EVF_PLUGIN_FILE ) . 'assets/images/enable-email-toggle.png' ); ?>" alt="<?php esc_attr_e( 'Click me to enable email settings', 'everest-forms' ); ?>"></div>
				<div class="evf-toggle-section">
					<label class="evf-toggle-switch">
						<input type="hidden" name="settings[email][<?php echo esc_attr( $connection_id ); ?>][enable_email_notification]" value="0" class="widefat">
						<input type="checkbox" name="settings[email][<?php echo esc_attr( $connection_id ); ?>][enable_email_notification]" value="1" data-connection-id="<?php echo esc_attr( $connection_id ); ?>" <?php echo checked( '1', $email_status, false ); ?> >
						<span class="evf-toggle-switch-wrap"></span>
						<span class="evf-toggle-switch-control"></span>
					</label>
				</div></div>
				<?php

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
						'tooltip'    => sprintf( esc_html__( 'Enter the recipient\'s email address (comma separated) to receive form entry notifications. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#1-toc-title' ) ),
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
						'tooltip'    => sprintf( esc_html__( 'Enter the From Name to be displayed in Email. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#2-toc-title' ) ),
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
						'tooltip'    => sprintf( esc_html__( 'Enter the Email address from which you want to send Email. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#3-toc-title' ) ),
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
						'tooltip'    => sprintf( esc_html__( 'Enter the reply to email address where you want the email to be received when this email is replied. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#4-toc-title' ) ),
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
						'tooltip'    => sprintf( esc_html__( 'Enter the subject of the email. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#5-toc-title' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				// --------------------------------------------------------------------//
				// Everest Forms AI Setting Section Start
				// --------------------------------------------------------------------//
				$everest_forms_ai_api_key = get_option( 'everest_forms_ai_api_key' );
				if ( ! empty( $everest_forms_ai_api_key ) ) {
					everest_forms_panel_field(
						'toggle',
						'email',
						'enable_ai_email_prompt',
						$this->form_data,
						esc_html__( 'Enable Email Prompt', 'everest-forms' ),
						array(
							'default'    => ! empty( $settings['email'][ $connection_id ]['enable_ai_email_prompt'] ) ? $settings['email'][ $connection_id ]['enable_ai_email_prompt'] : '0',
							'class'      => 'everest-forms-enable-email-prompt',
							/* translators: %1$s - email message prompt doc url*/
							'tooltip'    => sprintf( esc_html__( 'Check this option to enable the email message prompt. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/ai/#6-toc-title' ) ),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);
					everest_forms_panel_field(
						'textarea',
						'email',
						'evf_email_message_prompt',
						$this->form_data,
						esc_html__( 'Email Message Prompt', 'everest-forms' ),
						array(
							'default'    => isset( $settings['email'][ $connection_id ]['evf_email_message_prompt'] ) ? $settings['email'][ $connection_id ]['evf_email_message_prompt'] : '',
							'class'      => isset( $settings['email'][ $connection_id ]['enable_ai_email_prompt'] ) && '1' === $settings['email'][ $connection_id ]['enable_ai_email_prompt'] ? 'evf-email-message-prompt' : 'evf-email-message-prompt everest-forms-hidden',
							/* translators: %1$s - general settings docs url */
							'tooltip'    => sprintf( esc_html__( 'Enter the email message prompt. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/ai/#6-toc-title' ) ),
							'smarttags'  => array(
								'type'        => 'all',
								'form_fields' => 'all',
							),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);
				}
				// --------------------------------------------------------------------//
				// Everest Forms AI Setting Section End
				// --------------------------------------------------------------------//
				everest_forms_panel_field(
					'tinymce',
					'email',
					'evf_email_message',
					$this->form_data,
					esc_html__( 'Email Message', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_email_message'] ) ? evf_string_translation( $this->form_data['id'], 'evf_email_message', $settings['email'][ $connection_id ]['evf_email_message'] ) : __( '{all_fields}', 'everest-forms' ),
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the message of the email. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#6-toc-title' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
						/* translators: %s - all fields smart tag. */
						'after'      => empty( $everest_forms_ai_api_key ) ? '<p class="desc">' . sprintf( esc_html__( 'To display all form fields, use the %s Smart Tag.', 'everest-forms' ), '<code>{all_fields}</code>' ) . '</p>' : '<p class="desc">' . sprintf( esc_html__( 'To display all form fields, use the %1$s Smart Tag. Use %2$s Smart Tag for AI-generated emails', 'everest-forms' ), '<code>{all_fields}</code>', '<code>{ai_email_response}</code>' ) . '</p>',
					)
				);

				do_action( 'everest_forms_inline_email_settings', $this, $connection_id );

				echo '</div></div>';
			}

				endforeach;

				echo '</div>';

				// --------------------------------------------------------------------//
				// Preview Confirmation
				// --------------------------------------------------------------------//
				echo '<div class="evf-content-section evf-content-confirmation-settings">';
				echo '<div class="evf-content-section-title">';
				esc_html_e( 'Confirmations', 'everest-forms' );
				echo '</div>';

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
						'tooltip'     => sprintf( esc_html__( 'Success message that shows up after submitting form. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/general-settings/#4-toc-title' ) ),
					)
				);
				everest_forms_panel_field(
					'toggle',
					'settings',
					'submission_message_scroll',
					$this->form_data,
					__( 'Automatically scroll to the submission message', 'everest-forms' ),
					array(
						'default' => '1',
					)
				);

				echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Submission Redirection', 'everest-forms' ) . '</h4>';

				everest_forms_panel_field(
					'select',
					'settings',
					'redirect_to',
					$this->form_data,
					esc_html__( 'Redirect To', 'everest-forms' ),
					array(
						'default' => 'same',
						/* translators: %1$s - general settings docs url */
						'tooltip' => sprintf( esc_html__( 'Choose where to redirect after form submission. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/general-settings/#5-toc-title' ) ),
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
						'options' => $this->get_all_pages(),
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
					'toggle',
					'settings',
					'enable_redirect_query_string',
					$this->form_data,
					esc_html__( ' Append Query String', 'everest-forms' ),
					array(
						'default' => '0',
					)
				);

				everest_forms_panel_field(
					'text',
					'settings',
					'query_string',
					$this->form_data,
					esc_html__( 'Query String', 'everest-forms' ),
					array(
						'default'   => isset( $settings['query_string'] ) ? $settings['query_string'] : '',
						'class'     => isset( $settings['enable_redirect_query_string'] ) && '1' === $settings['enable_redirect_query_string'] ? '' : 'everest-forms-hidden',
						'smarttags' => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'after'     => '<p class="desc">' . sprintf( esc_html__( 'Example: firstname= {field_id="name_ys0GeZISRs-1"}&email={field_id="email_LbH5NxasXM-2"}', 'everest-forms' ) ) . '</p>',
					)
				);

				do_action( 'everest_forms_submission_redirection_settings', $this, 'submission_redirection' );
				echo '</div>';

				everest_forms_panel_field(
					'toggle',
					'settings',
					'preview_confirmation',
					$this->form_data,
					esc_html__( 'Show entry preview after form submission', 'everest-forms' ),
					array(
						'tooltip' => esc_html__( 'Show entry preview after form submission', 'everest-forms' ),
					)
				);

				everest_forms_panel_field(
					'select',
					'settings',
					'preview_confirmation_select',
					$this->form_data,
					esc_html__( 'Preview type', 'everest-forms' ),
					array(
						'default' => 'basic',
						'tooltip' => esc_html__( 'Choose preview style type.', 'everest-forms' ),
						'options' => array(
							'basic'   => esc_html__( 'Basic', 'everest-forms' ),
							'table'   => esc_html__( 'Table', 'everest-forms' ),
							'compact' => esc_html__( 'Compact', 'everest-forms' ),
						),
					)
				);
				echo '</div>';

				// --------------------------------------------------------------------//
				// Spam Protection and Security
				// --------------------------------------------------------------------//
				echo '<div class="evf-content-section evf-content-security-settings">';
				echo '<div class="evf-content-section-title">';
				esc_html_e( 'Anti-Spam and Security', 'everest-forms' );
				echo '</div>';
				echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Honeypot', 'everest-forms' ) . '</h4>';
				everest_forms_panel_field(
					'toggle',
					'settings',
					'honeypot',
					$this->form_data,
					esc_html__( 'Enable anti-spam honeypot', 'everest-forms' ),
					array(
						'default' => '1',
					)
				);
				do_action( 'everest_forms_inline_honeypot_settings', $this, 'honeypot', 'connection_1' );
				echo '</div>';
				/**
				* Akismet anit-spam protection.
				*
				* @since 2.0.4
				*/
				echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Akismet', 'everest-forms' ) . '</h4>';
				everest_forms_panel_field(
					'toggle',
					'settings',
					'akismet',
					$this->form_data,
					esc_html__( 'Enable Akismet anti-spam protection', 'everest-forms' ),
					array(
						'default' => '0',
					)
				);

				/**
				* Warning message if the installtion, activation and configuration are not proper.
				*/
		if ( ! file_exists( WP_PLUGIN_DIR . '/akismet/akismet.php' ) ) {
			printf( '<div class="evf-akismet"><span class="evf-akismet-warning"><span class="evf-akismet-warning-label">%s </span>%s <a href="%s" target="_blank">%s</a>%s</span> <a href="%s" target="_blank">%s</a></div>', esc_html__( 'Warning:- ', 'everest-forms' ), esc_html__( ' This feature is inactive because Akismet plugin ', 'everest-forms' ), esc_url( admin_url( 'plugins.php' ) ), esc_html__( 'has not been installed.', 'everest-forms' ), esc_html__( '  For more', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/' ), esc_html( 'information', 'everest-forms' ) );
		} elseif ( ! is_plugin_active( 'akismet/akismet.php' ) ) {
			printf( '<div class="evf-akismet"><span class="evf-akismet-warning"><span class="evf-akismet-warning-label">%s </span>%s <a href="%s" target="_blank">%s</a>%s</span> <a href="%s" target="_blank">%s</a></div>', esc_html__( 'Warning:- ', 'everest-forms' ), esc_html__( ' This feature is inactive because Akismet plugin ', 'everest-forms' ), esc_url( admin_url( 'plugins.php' ) ), esc_html__( 'has not been activated.', 'everest-forms' ), esc_html__( '  For more', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/' ), esc_html( 'information', 'everest-forms' ) );
		} elseif ( ! evf_is_akismet_configured() ) {
			printf( '<div class="evf-akismet"><span class="evf-akismet-warning"><span class="evf-akismet-warning-label">%s </span>%s <a href="%s" target="_blank">%s</a>%s</span> <a href="%s" target="_blank">%s</a></div>', esc_html__( 'Warning:- ', 'everest-forms' ), esc_html__( ' This feature is inactive because Akismet plugin ', 'everest-forms' ), esc_url( admin_url( 'plugins.php' ) ), esc_html__( 'has not been properly configured.', 'everest-forms' ), esc_html__( '  For more', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/' ), esc_html( 'information', 'everest-forms' ) );
		}
				echo '<div class="everest-forms-border-container everest-forms-akismet-protection-type">';
				everest_forms_panel_field(
					'select',
					'settings',
					'akismet_protection_type',
					$this->form_data,
					esc_html__( 'Protection type', 'everest-forms' ),
					array(
						'default' => 'validation_failed',
						'tooltip' => esc_html__( "Please select the protection type. Choosing 'Mark as Spam' allows the submission but marks the entry as spam, while selecting 'Make the form submission as failed' will prevent the form submission.", 'everest-forms' ),
						'options' => array(
							'validation_failed' => esc_html__( 'Make the form submission as failed', 'everest-forms' ),
							'mark_as_spam'      => esc_html__( 'Mark as Spam', 'everest-forms' ),
						),
					)
				);
				do_action( 'everest_forms_inline_akismet_settings', $this, 'akismet', 'connection_1' );

				do_action( 'everest_forms_inline_akismet_protection_type_settings', $this, 'akismet_protection_type', 'connection_1' );
				echo '</div>';
				echo '</div>';
				do_action( 'everest_forms_inline_security_settings', $this );

				/**
				* Minimum time for form submission.
				*
				* @since 3.0.1
				*/
				echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Waiting time for form submission', 'everest-forms' ) . '</h4>';
				everest_forms_panel_field(
					'toggle',
					'settings',
					'form_submission_min_waiting_time',
					$this->form_data,
					esc_html__( 'Enable minimum waiting time for form submission', 'everest-forms' ),
					array(
						'default' => '0',
						'tooltip' => esc_html__( 'Prevents the form submission before the specified time', 'everest-forms' ),
					)
				);

				echo '<div class="everest-forms-border-container everest-forms-form-submission-minimum-waiting-time">';
				everest_forms_panel_field(
					'number',
					'settings',
					'form_submission_min_waiting_time_input',
					$this->form_data,
					esc_html__( 'Form submission minimum waiting time (In seconds)', 'everest-forms' ),
					array(
						'default'   => '5',
						'tooltip'   => esc_html__( 'Enter the minimum time waiting time for form submission.', 'everest-forms' ),
						'min_value' => 1,
					)
				);

				do_action( 'everest_forms_inline_form_submission_min_waiting_time_settings', $this, 'form_submission_min_waiting_time', 'connection_1' );

				do_action( 'everest_forms_inline_form_submission_min_waiting_time_section_settings', $this, 'form_submission_min_waiting_time_section', 'connection_1' );
				echo '</div>';
				echo '</div>';
				echo '</div>';

				do_action( 'everest_forms_settings_panel_content', $this );
	}

	/**
	 * Add Custom CSS and JS menu item to the builder settings list.
	 *
	 * @param [array] $arr Setting Menu items list.
	 * @param [array] $form_data Form Data.
	 * @return array
	 */
	public function add_custom_css_js_section( $arr, $form_data ) {

		$arr['custom-css-js'] = esc_html__( 'Custom CSS and JS', 'everest-forms' );
		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			$pro_addons = array(
				'webhook'            => esc_html__( 'WebHook', 'everest-forms' ),
				'form_restriction'   => esc_html__( 'Form Restriction', 'everest-forms' ),
				'multi_part'         => esc_html__( 'Multi Part', 'everest-forms' ),
				'pdf_submission'     => esc_html__( 'PDF Submission', 'everest-forms' ),
				'post_submission'    => esc_html__( 'Post Submission', 'everest-forms' ),
				'save_and_continue'  => esc_html__( 'Save and Continue', 'everest-forms' ),
				'survey_polls_quiz'  => esc_html__( 'Survey,Polls,Quiz', 'everest-forms' ),
				'user_registration'  => esc_html__( 'User Registration', 'everest-forms' ),
				'conversation_forms' => esc_html__( 'Conversation Forms', 'everest-forms' ),
				'sms_notifications'  => esc_html__( 'SMS Notifications', 'everest-forms' ),
				'telegram'           => esc_html__( 'Telegram', 'everest-forms' ),
			);
			$arr        = array_merge( $arr, $pro_addons );
		}
		return $arr;
	}

	/**
	 * Add Custom Css and Js settings section.
	 *
	 * @param [array] $form_data Form Data.
	 * @return void
	 */
	public function add_custom_css_js_settings( $form_data ) {
		// --------------------------------------------------------------------//
		// Custom CSS and JS
		// --------------------------------------------------------------------//
		echo '<div class="evf-content-section evf-content-custom-css-js-settings">';
		echo '<div class="evf-content-section-title">';
		esc_html_e( 'Custom CSS and JS', 'everest-forms' );
		echo '</div>';
		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Custom CSS', 'everest-forms' ) . '</h4>';
		everest_forms_panel_field(
			'toggle',
			'settings',
			'evf-enable-custom-css',
			$this->form_data,
			esc_html__( 'Enable Custom CSS', 'everest-forms' ),
			array(
				'default' => '0',
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'evf-custom-css',
			$this->form_data,
			esc_html__( 'Custom CSS', 'everest-forms' )
		);
		echo '</div>';
		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Custom JS', 'everest-forms' ) . '</h4>';
		everest_forms_panel_field(
			'toggle',
			'settings',
			'evf-enable-custom-js',
			$this->form_data,
			esc_html__( 'Enable Custom JS', 'everest-forms' ),
			array(
				'default' => '0',
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'evf-custom-js',
			$this->form_data,
			esc_html__( 'Custom JS', 'everest-forms' )
		);
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Get all pages.
	 */
	public function get_all_pages() {
		$pages = array();
		foreach ( get_pages() as $page ) {
				$pages[ $page->ID ] = $page->post_title;
		}

					return $pages;
	}
}

return new EVF_Builder_Settings();
