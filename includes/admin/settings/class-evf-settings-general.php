<?php
/**
 * EverestForms General Settings
 *
 * @author      WPEverest
 * @category    Admin
 * @package     EverestForms/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EVF_Settings_General', false ) ) :

	/**
	 * EVF_Admin_Settings_General.
	 */
	class EVF_Settings_General extends EVF_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'general';
			$this->icon  = 'dashicons dashicons-admin-settings';
			$this->label = __( 'General', 'everest-forms' );

			parent::__construct();
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'everest_forms_general_settings', array(

					array(
						'title' => __( 'General Options', 'everest-forms' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'general_options',
					),
					array(
						'title'    => __( 'Form Submit Button Label', 'everest-forms' ),
						'desc'     => __( 'Button label to register', 'everest-forms' ),
						'id'       => 'everest_forms_form_submit_button_label',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'default'  => __('Submit','everest-forms'),
					),
					array(
						'title'    => __( 'Disable Form Entries', 'everest-forms' ),
						'desc'     => '',
						'id'       => 'everest_forms_disable_form_entries',
						'type'     => 'checkbox',
						'desc_tip' => true,		
						'default'  => 'no',				
					),
					array(
						'title'    => __( 'Successful Form Submission Message', 'everest-forms' ),
						'desc'     => __( 'Enter the text message after successful form submission.', 'everest-forms' ),
						'id'       => 'everest_forms_successful_form_submission_message',
						'type'     => 'textarea',
						'desc_tip' => true,
						'css'      => 'min-width: 350px; min-height: 200px;',
						'default'  => __('Thanks for contacting us! We will be in touch with you shortly.','everest-forms'),
					),

					array(
						'type' => 'sectionend',
						'id'   => 'general_options',
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

return new EVF_Settings_General();
