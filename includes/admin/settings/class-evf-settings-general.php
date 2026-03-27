<?php
/**
 * EverestForms General Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_General', false ) ) {
	return new EVF_Settings_General();
}

/**
 * EVF_Settings_General.
 */
class EVF_Settings_General extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = esc_html__( 'General', 'everest-forms' );

		parent::__construct();

		add_action( 'everest_forms_sections_' . $this->id, array( $this, 'output_sections' ) );
	}

	/**
	 * Get sections for general tab.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'validation_messages' => esc_html__( 'Validation Messages', 'everest-forms' ),
			'miscellaneous'       => esc_html__( 'Miscellaneous', 'everest-forms' ),
		);

		return apply_filters( 'everest_forms_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output sections in navigation sidebar.
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}

		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'validation_messages';

		echo '<ul class="evf-subsections">';

		foreach ( $sections as $id => $label ) {
			$url = add_query_arg(
				array(
					'page'    => 'evf-settings',
					'tab'     => $this->id,
					'section' => sanitize_title( $id ),
				),
				admin_url( 'admin.php' )
			);

			echo '<li><a href="' . esc_url( $url ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a></li>';
		}

		echo '</ul>';
	}

	/**
	 * Get settings array based on current section.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'validation_messages';

		switch ( $current_section ) {
			case 'miscellaneous':
				$settings = $this->get_miscellaneous_settings();
				break;
			default:
				$settings = $this->get_validation_messages_settings();
				break;
		}

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings, $current_section );
	}


	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	// public function get_settings() {
	// 	$settings = apply_filters(
	// 		'everest_forms_general_settings',
	// 		array(
	// 			array(
	// 				'title' => esc_html__( 'General Options', 'everest-forms' ),
	// 				'type'  => 'title',
	// 				'desc'  => '',
	// 				'id'    => 'general_options',
	// 			),
	// 			array(
	// 				'title'    => esc_html__( 'Disable User Details', 'everest-forms' ),
	// 				'desc'     => esc_html__( 'Disable storing the IP address and User Agent on all forms.', 'everest-forms' ),
	// 				'id'       => 'everest_forms_disable_user_details',
	// 				'default'  => 'no',
	// 				'type'     => 'toggle',
	// 				'desc_tip' => true,
	// 			),
	// 			array(
	// 				'title'    => esc_html__( 'Enable Log', 'everest-forms' ),
	// 				'desc'     => esc_html__( 'Enable storing the logs.', 'everest-forms' ),
	// 				'id'       => 'everest_forms_enable_log',
	// 				'default'  => 'no',
	// 				'type'     => 'toggle',
	// 				'desc_tip' => true,
	// 			),
	// 			array(
	// 				'type' => 'sectionend',
	// 				'id'   => 'general_options',
	// 			),
	// 		)
	// 	);

	// 	return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	// }

	public function get_miscellaneous_settings() {
		$settings = apply_filters(
			'everest_forms_general_miscellaneous_settings',
			array(
				array(
					'title' => esc_html__( 'Miscellaneous Options', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_miscellaneous_options',
				),
				array(
					'title'    => esc_html__( 'Disable User Details', 'everest-forms' ),
					'desc'     => esc_html__( 'Disable storing the IP address and User Agent on all forms.', 'everest-forms' ),
					'id'       => 'everest_forms_disable_user_details',
					'default'  => 'no',
					'type'     => 'toggle',
					'desc_tip' => true,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'general_miscellaneous_options',
				),
			)
		);
        return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	public function get_validation_messages_settings() {
		$settings = apply_filters(
			'everest_forms_validation_settings',
			array(
				array(
					'title' => esc_html__( 'Validation Messages', 'everest-forms' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'validation_options',
				),
				array(
					'title'    => esc_html__( 'Required', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the required form field', 'everest-forms' ),
					'id'       => 'everest_forms_required_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'This field is required.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Website URL', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid website url', 'everest-forms' ),
					'id'       => 'everest_forms_url_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'Please enter a valid URL.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Email', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid email', 'everest-forms' ),
					'id'       => 'everest_forms_email_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'Please enter a valid email address.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Email Suggestion', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid email suggestion', 'everest-forms' ),
					'id'       => 'everest_forms_email_suggestion',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'Did you mean {suggestion}?', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Confirm Value', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for confirm field value.', 'everest-forms' ),
					'id'       => 'everest_forms_confirm_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'Field values do not match.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Checkbox Selection Limit', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the checkbox selection limit.', 'everest-forms' ),
					'id'       => 'everest_forms_check_limit_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'You have exceeded number of allowed selections: {#}.', 'everest-forms' ),
				),
				array(
					'title'    => esc_html__( 'Number', 'everest-forms' ),
					'desc'     => esc_html__( 'Enter the message for the valid number', 'everest-forms' ),
					'id'       => 'everest_forms_number_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'Please enter a valid number.', 'everest-forms' ),
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

return new EVF_Settings_General();
