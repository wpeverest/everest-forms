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
		$this->label   = __( 'Settings', 'everest-forms' );
		$this->sidebar = true;

		add_action( 'everest_forms_settings_connections_email', array( $this, 'output_connections_list' ) );

		parent::__construct();
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		$sections = apply_filters( 'everest_forms_builder_settings_section', array(
			'general' => __( 'General', 'everest-forms' ),
			'email'   => __( 'Email', 'everest-forms' ),
		), $this->form_data );

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

		if ( ! empty( $_GET['form_id'] ) ) {
			$form_data = EVF()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true, ) );
		}

		return $form_data;
	}

	/**
	 * Outputs the connection lists on sidebar.
	 */
	public function output_connections_list() {
		$form_data = $this->form_data();
		$email = isset($form_data['settings']['email']) ? $form_data['settings']['email'] : array();

		if( empty( $email ) ){
			$email['connection_1'] = array( 'connection_name' => __('Admin Notification', 'everest-forms') );
		}
		 	?>
			<div class="everest-forms-active-email">
				<button class="everest-forms-btn everest-forms-email-add" data-form_id="<?php echo absint( $_GET['form_id'] ); ?>" data-source="email" data-type="<?php echo esc_attr( 'connection' ); ?>">
					<?php printf( esc_html__( 'Add New Email', 'everest-forms' ) ); ?>
				</button>
					<ul class="everest-forms-active-email-connections-list">
					<?php if ( ! empty( $email ) ){ ?>
						<h4><?php echo  esc_html__( 'Email Notifications', 'everest-forms' ) ?> </h4>
					<?php }
						if ( ! empty( $email ) ){
							foreach ( $email as $connection_id => $connection_data ){
								if( preg_match( '/connection_/' , $connection_id ) ){
									$connection_name = ! empty( $connection_data['connection_name'] ) ? $connection_data['connection_name'] : '';
									if( 'connection_1' !== $connection_id ) {
										$remove_class = 'email-remove';
									}else {
										$remove_class = 'email-default-remove';
									}
									?>
									<li class="connection-list" data-connection-id="<?php echo $connection_id; ?>">
										<a class="user-nickname" href="#"><?php echo $connection_name; ?></a>
										<a href="#"><span class="<?php echo $remove_class; ?>">Remove</a>
									</li>
							<?php }
							}
						} ?>
					</ul>
			</div>
			<?php
	 }

	/**
	 * Outputs the builder content.
	 */
	public function output_content() {
		$form_id     = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$user_emails = evf_get_all_email_fields_by_form_id( $form_id );
		$settings    = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();

		// --------------------------------------------------------------------//
		// General
		// --------------------------------------------------------------------//
		echo '<div class="evf-content-section evf-content-general-settings">';
		echo '<div class="evf-content-section-title">';
		_e( 'General', 'everest-forms' );
		echo '</div>';
		everest_forms_panel_field(
			'text',
			'settings',
			'form_title',
			$this->form_data,
			__( 'Form Name', 'everest-forms' ),
			array(
				'default' => $this->form->post_title,
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'successful_form_submission_message',
			$this->form_data,
			__( 'Successful form submission message', 'everest-forms' ),
			array(
				'default' => isset( $this->form->successful_form_submission_message ) ? $this->form->successful_form_submission_message : __( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'redirect_to',
			$this->form_data,
			__( 'Redirect To', 'everest-forms' ),
			array(
				'default' => '0',
				'options' => array(
					'0' => __( 'Same Page', 'everest-forms' ),
					'1' => __( 'Custom Page', 'everest-forms' ),
					'2' => __( 'External URL', 'everest-forms' ),
				),
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'custom_page',
			$this->form_data,
			__( 'Custom Page', 'everest-forms' ),
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
			__( 'External URL', 'everest-forms' ),
			array(
				'default' => isset( $this->form->external_url ) ? $this->form->external_url : '',
			)
		);
		everest_forms_panel_field(
			'select',
			'settings',
			'layout_class',
			$this->form_data,
			__( 'Layout Design', 'everest-forms' ),
			array(
				'default' => '0',
				'options' => array(
					'default' => __( 'Default', 'everest-forms' ),
					'layout-two' => __( 'Classic Layout', 'everest-forms' ),
				),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'form_class',
			$this->form_data,
			__( 'Form Class', 'everest-forms' ),
			array(
				'default' => isset( $this->form->form_class ) ? $this->form->form_class : '',
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_text',
			$this->form_data,
			__( 'Submit button text', 'everest-forms' ),
			array(
				'default' => isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'everest-forms' ),
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'recaptcha_support',
			$this->form_data,
			sprintf( __( 'Enable %1$s %2$s reCaptcha %3$s support', 'everest-forms' ), '<a title="', 'Please make sure the site key and secret are not empty in setting page." href="' . admin_url() . 'admin.php?page=evf-settings&tab=recaptcha" target="_blank">', '</a>' ),
			array(
				'default' => '0',
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'disabled_entries',
			$this->form_data,
			__( 'Disable storing entry information', 'everest-forms' ),
			array(
				'default' => isset( $settings['disabled_entries'] ) ? $settings['disabled_entries'] : 0,
			)
		);

		do_action( 'everest_forms_general_settings', $this );

		echo '</div>';

		// --------------------------------------------------------------------//
		// Email
		// --------------------------------------------------------------------//
		if ( ! isset( $settings['email']['connection_1'] ) ){
			$settings['email']['connection_1'] = array( 'connection_name' => __('Admin Notification', 'everest-forms') );

			$email_settings = array( 'evf_to_email', 'evf_from_name', 'evf_from_email', 'evf_reply_to', 'evf_email_subject', 'evf_email_message', 'attach_pdf_to_admin_email', 'show_header_in_attachment_pdf_file', 'conditional_logic_status', 'conditional_option', 'conditionals' );
			foreach ( $email_settings as $email_setting ) {
				$settings['email']['connection_1'][ $email_setting ] = isset( $settings['email'][ $email_setting ] ) ? $settings['email'][ $email_setting ] : '';
			}

			// Backward compatibility.
			$unique_connection_id = sprintf( 'connection_%s', uniqid() );
			if ( isset( $settings['email']['evf_send_confirmation_email'] ) && '1' === $settings['email']['evf_send_confirmation_email'] ) {
				$settings['email'][ $unique_connection_id ] = array( 'connection_name'   => esc_html__( 'User Notification', 'everest-forms' ) );

				foreach ( $email_settings as $email_setting ) {
					$settings['email'][ $unique_connection_id ][ $email_setting ] = isset( $settings['email'][ $email_setting ] ) ? $settings['email'][ $email_setting ] : '';
				}
			}
		}

		$form_name = isset( $settings['form_title'] ) ? ' - '. $settings['form_title'] : '';

		echo '<div class="evf-content-section evf-content-email-settings">';
		echo '<div class="evf-content-section-title">';
		_e( 'Email', 'everest-forms' );
		echo '</div>';

		foreach ( $settings['email'] as $connection_id => $connection ) :
			if ( preg_match( '/connection_/' , $connection_id ) ) {
				echo '<div class="evf-content-email-settings-inner" data-connection_id='.$connection_id.'>';

				everest_forms_panel_field(
					'text',
					'settings[email]['.$connection_id.']',
					'connection_name',
					$this->form_data,
					'',
					array(
						'default' => isset( $settings['email'][$connection_id]['connection_name'] ) ? $settings['email'][$connection_id]['connection_name'] : __('Admin Notification', 'everest-forms'),
						'class'   => 'everest-forms-email-name',
					)
				);
				everest_forms_panel_field(
					'text',
					'settings[email]['.$connection_id.']',
					'evf_to_email',
					$this->form_data,
					__( 'To Address', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_to_email'] ) ? $settings['email'][ $connection_id ]['evf_to_email'] : '{admin_email}',
						'tooltip'    => __( 'Enter your email address to receive notifications; separate with a comma if multiple addresses.', 'everest-forms' ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'email'
							),
					)
				);
				everest_forms_panel_field(
					'text',
					'settings[email]['.$connection_id.']',
					'evf_from_name',
					$this->form_data,
					__( 'From Name', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][$connection_id]['evf_from_name'] ) ? $settings['email'][$connection_id]['evf_from_name'] : get_bloginfo( 'name', 'display' ),
						'tooltip'    => __('Enter the From Name.', 'everest-forms'),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all'
							),
					)
				);
				everest_forms_panel_field(
					'text',
					'settings[email]['.$connection_id.']',
					'evf_from_email',
					$this->form_data,
					__( 'From Address', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_from_email'] ) ? $settings['email'][ $connection_id ]['evf_from_email'] : '{admin_email}',
						'tooltip'    => __('Enter the Email address from which you want to send Email.', 'everest-forms'),
						'smarttags'  => array(
							'type' => 'fields',
							'form_fields' => 'email'
							),
					)
				);
				everest_forms_panel_field(
					'text',
					'settings[email]['.$connection_id.']',
					'evf_reply_to',
					$this->form_data,
					__( 'Reply To', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_reply_to'] ) ? $settings['email'][ $connection_id ]['evf_reply_to'] : '',
						'tooltip'    => __('Enter the reply to email where the email address will be sent while user replies Email.', 'everest-forms'),
						'smarttags'  => array(
							'type'   => 'fields',
							'form_fields' => 'email'
							),
					)
				);
				everest_forms_panel_field(
					'text',
					'settings[email]['.$connection_id.']',
					'evf_email_subject',
					$this->form_data,
					__( 'Email Subject', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_email_subject'] ) ? $settings['email'][ $connection_id ]['evf_email_subject'] : sprintf( __( 'New Form Entry %s', 'everest-forms' ), $form_name ),
						'tooltip'    => __('Enter the subject of the email.', 'everest-forms'),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all'
							),
					)
				);
				everest_forms_panel_field(
					'tinymce',
					'settings[email]['.$connection_id.']',
					'evf_email_message',
					$this->form_data,
					__( 'Email Message', 'everest-forms' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['evf_email_message'] ) ? $settings['email'][ $connection_id ]['evf_email_message'] : __( '{all_fields}', 'everest-forms' ),
						'tooltip'    => __('Enter the message of Email.', 'everest-forms'),
						'smarttags'  => array(
							'type'   => 'all',
							'form_fields' => 'all'
							),
						)
					);

				do_action( 'everest_forms_inline_email_settings', $this , $connection_id );

				echo '</div>';
			}

		endforeach;

		echo '</div>';
		do_action( 'everest_forms_settings_panel_content', $this );
	}

	public function evf_get_all_pages(){
		$pages = array();
		foreach(get_pages() as $page){
			$pages[$page->ID] = $page->post_title; ;
		}

		return $pages;
	}
}

return new EVF_Builder_Settings();
