<?php
/**
 * EverestForms Email Settings
 *
 * @author      WPEverest
 * @category    Admin
 * @package     EverestForms/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EVF_Settings_Email', false ) ) :

	/**
	 * EVF_Admin_Settings_Email.
	 */
	class EVF_Settings_Email extends EVF_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'email';
			$this->icon  = 'dashicons dashicons-email';
			$this->label = __( 'Email', 'everest-forms' );

			parent::__construct();
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'everest_forms_email_settings', array(

					array(
						'title' => __( 'Email Settings', 'everest-forms' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'email_options',
					),
					array(
						'title'    => __( 'To Address', 'everest-forms' ),
						'desc'     => __( 'Enter the email address to send email', 'everest-forms' ),
						'id'       => 'evf_to_email',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => get_option('admin_email'),
					),
					array(
						'title'    => __( 'From Name', 'everest-forms' ),
						'desc'     => __( 'Email senders name', 'everest-forms' ),
						'id'       => 'evf_from_name',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => evf_sender_name(),
					),
					array(
						'title'    => __( 'From Address', 'everest-forms' ),
						'desc'     => __( 'Email senders address', 'everest-forms' ),
						'id'       => 'evf_from_address',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => evf_sender_address(),
					),
					array(
						'title'    => __( 'Email Subject', 'everest-forms' ),
						'desc'     => __( 'Email Subject', 'everest-forms' ),
						'id'       => 'evf_email_subject',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => 'New Form Entry'
					),
					array(
						'title'    => __( 'Email Message', 'everest-forms' ),
						'desc'     => __( 'Email Message', 'everest-forms' ),
						'id'       => 'evf_email_message',
						'type'     => 'tinymce',
						'desc_tip' => true,
						'css'      => 'max-width: 350px;',
						'default'  => '{all_fields}'
					),
					array(
						'type' => 'sectionend',
						'id'   => 'email_options',
					),

				)
			);


			return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			$settings = $this->get_settings();


			EVF_Admin_Settings::save_fields( $settings );
		}
	}

endif;

return new EVF_Settings_Email();
