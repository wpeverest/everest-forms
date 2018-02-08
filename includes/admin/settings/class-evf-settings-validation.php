<?php
/**
 * EverestForms Validation Settings
 *
 * @author      WPEverest
 * @category    Admin
 * @package     EverestForms/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EVF_Settings_Validation', false ) ) :

	/**
	 * EVF_Admin_Settings_Validation.
	 */
	class EVF_Settings_Validation extends EVF_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'validation';
			$this->icon  = 'dashicons dashicons-warning';
			$this->label = __( 'Validations', 'everest-forms' );

			parent::__construct();
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'everest_forms_validation_settings', array(

					array(
						'title' => __( 'Validation Messages', 'everest-forms' ),
						'type'  => 'title',
						'desc'  => 'These messages are displayed to the user as they fill out a form in real-time.',
						'id'    => 'validation_options',
					),
					array(
						'title'    => __( 'Required', 'everest-forms' ),
						'desc'     => __( 'Enter the message for the required form field', 'everest-forms' ),
						'id'       => 'evf_required_validation',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => __('This field is required.','everest-forms'),
					),
					array(
						'title'    => __( 'Website URL', 'everest-forms' ),
						'desc'     => __( 'Enter the message for the valid website url', 'everest-forms' ),
						'id'       => 'evf_url_validation',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => __('Please enter a valid URL.','everest-forms'),
					),
					array(
						'title'    => __( 'Email', 'everest-forms' ),
						'desc'     => __( 'Enter the message for the valid email', 'everest-forms' ),
						'id'       => 'evf_email_validation',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => __('Please enter a valid email address.','everest-forms'),
					),
					array(
						'title'    => __( 'Number', 'everest-forms' ),
						'desc'     => __( 'Enter the message for the valid number', 'everest-forms' ),
						'id'       => 'evf_number_validation',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => __('Please enter a valid number.','everest-forms'),
					),
					array(
						'title'    => __( 'reCAPTCHA', 'everest-forms' ),
						'desc'     => __( 'Enter the message for the valid recaptcha', 'everest-forms' ),
						'id'       => 'evf_recaptcha_validation',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => __('Invalid reCaptcha Code.','everest-forms'),
					),
					array(
						'type' => 'sectionend',
						'id'   => 'validation_options',
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

return new EVF_Settings_Validation();
