<?php
/**
 * EverestForms Email Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Email', false ) ) {
	return new EVF_Settings_Email();
}

/**
 * EVF_Settings_Email.
 */
class EVF_Settings_Email extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'email';
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
					'title' => __( 'Template Settings', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'email_template_options',
				),
				array(
					'title'   => __( 'Template', 'everest-forms' ),
					'type'    => 'radio',
					'id'      => 'everest_forms_email_template',
					'default' => 'default',
					'options' => array(
						'default' => esc_html__( 'HTML', 'everest-forms' ),
						'none'    => esc_html__( 'Plain', 'everest-forms' ),
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'email_template_options',
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

return new EVF_Settings_Email();
