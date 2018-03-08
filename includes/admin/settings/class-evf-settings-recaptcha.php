<?php
/**
 * EverestForms reCAPTCHA Settings
 *
 * @author      WPEverest
 * @category    Admin
 * @package     EverestForms/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EVF_Settings_reCAPTCHA', false ) ) :

	/**
	 * EVF_Admin_Settings_reCAPTCHA.
	 */
	class EVF_Settings_reCAPTCHA extends EVF_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'recaptcha';
			$this->icon  = 'dashicons dashicons-lock';
			$this->label = __( 'reCAPTCHA', 'everest-forms' );

			parent::__construct();
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'everest_forms_recaptcha_settings', array(

					array(
						'title' => __( 'Google reCaptcha Integation', 'everest-forms' ),
						'type'  => 'title',
						'desc'  => sprintf( __('Get site key and secret key from google %1$s reCaptcha %2$s.', 'everest-forms' ), '<a href="https://www.google.com/recaptcha" target="_blank">', '</a>' ),
						'id'    => 'integration_options',
					),
					array(
						'title'    => __( 'Site Key', 'everest-forms' ),
						'desc'     => sprintf( __('Get site key from google %1$s reCaptcha %2$s.', 'everest-forms' ), '<a href="https://www.google.com/recaptcha" target="_blank">', '</a>' ),
						'id'       => 'evf_recaptcha_site_key',
						'default'  => '',
						'type'     => 'text',
						'class'    => '',
						'css'      => 'min-width: 350px;',
						'desc_tip' => true,

					),
					array(
						'title'    => __( 'Secret Key', 'everest-forms' ),
						'desc'     => sprintf( __('Get secret key from google %1$s reCaptcha %2$s.', 'everest-forms' ), '<a href="https://www.google.com/recaptcha" target="_blank">', '</a>' ),
						'id'       => 'evf_recaptcha_site_secret',
						'default'  => '',
						'type'     => 'text',
						'class'    => '',
						'css'      => 'min-width: 350px;',
						'desc_tip' => true,

					),


					array(
						'type' => 'sectionend',
						'id'   => 'integration_options',
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

return new EVF_Settings_reCAPTCHA();
