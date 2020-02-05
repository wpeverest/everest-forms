<?php
/**
 * EverestForms Validation Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Validation', false ) ) {
	return new EVF_Settings_Validation();
}

/**
 * EVF_Settings_Validation.
 */
class EVF_Settings_Validation extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'validation';
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
			'everest_forms_validation_settings',
			array(
				array(
					'title' => esc_html__( 'Validation Messages', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => 'Validation Messages for Form Fields.',
					'id'    => 'validation_options',
				),
				array(
					'title'    => esc_html__( 'Required', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the required form field', 'everest-forms' ),
					'id'       => 'everest_forms_required_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'This field is required.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Website URL', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid website url', 'everest-forms' ),
					'id'       => 'everest_forms_url_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Please enter a valid URL.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Email', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid email', 'everest-forms' ),
					'id'       => 'everest_forms_email_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Please enter a valid email address.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Email Suggestion', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid email suggestion', 'everest-forms' ),
					'id'       => 'everest_forms_email_suggestion',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Did you mean {suggestion}?', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Confirm Value', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for confirm field value.', 'everest-forms' ),
					'id'       => 'everest_forms_confirm_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Field values do not match.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Checkbox Selection Limit', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the checkbox selection limit.', 'everest-forms' ),
					'id'       => 'everest_forms_check_limit_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'You have exceeded number of allowed selections: {#}.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Number', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid number', 'everest-forms' ),
					'id'       => 'everest_forms_number_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Please enter a valid number.', 'everest-forms' ),
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

return new EVF_Settings_Validation();
