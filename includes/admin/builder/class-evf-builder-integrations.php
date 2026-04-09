<?php
/**
 * EverestForms Builder Integrations
 *
 * @package EverestForms\Admin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Builder_Integrations', false ) ) {
	return new EVF_Builder_Integrations();
}

/**
 * EVF_Builder_Integrations class.
 */
class EVF_Builder_Integrations extends EVF_Builder_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id      = 'integrations';
		$this->label   = __( 'Integrations', 'everest-forms-pro' );
		$this->sidebar = true;

		parent::__construct();
	}


	/**
	 * Outputs the builder content.
	 */
	public function output_content() {
		$providers_active = apply_filters( 'everest_forms_available_integrations', array() );

		if ( empty( $providers_active ) ) {
			$upgrade_url = apply_filters(
				'everest_forms_upgrade_url',
				'https://everestforms.net/upgrade/?utm_medium=evf-form-builder&utm_source=evf-free&utm_campaign=builder-pro-field-popup&utm_content=Upgrade%20to%20Pro'
			);
			echo '<div class="evf-panel-content-section evf-panel-content-section-info evf-builder-get-started">';
			echo '<h3>' . esc_html__( 'Get Started with Integrations', 'everest-forms-pro' ) . '</h3>';
			echo '<p>' . esc_html__( 'Integrations are available in the Pro plan. Upgrade to install and connect them.', 'everest-forms-pro' ) . '</p>';
			echo '<div class="evf-builder-get-started-steps" style="display: flex; gap: 20px;">';
			echo '<span class="step" style="display: flex; align-items: center; gap: 10px; width: fit-content;"><span style="width: 26px; height: 26px; display: inline-block; background-color: #E1E1E1; border-radius: 4px; text-align: center; line-height: 24px;">1</span>' . esc_html__( 'Upgrade', 'everest-forms-pro' ) . '</span>';
			echo '<span class="step" style="display: flex; align-items: center; gap: 10px; width: fit-content;"><span style="width: 26px; height: 26px; display: inline-block; background-color: #E1E1E1; border-radius: 4px; text-align: center; line-height: 24px;">2</span>' . esc_html__( 'Activate add-on', 'everest-forms-pro' ) . '</span>';
			echo '<span class="step" style="display: flex; align-items: center; gap: 10px; width: fit-content;"><span style="width: 26px; height: 26px; display: inline-block; background-color: #E1E1E1; border-radius: 4px; text-align: center; line-height: 24px;">3</span>' . esc_html__( 'Connect', 'everest-forms-pro' ) . '</span>';
			echo '</div>';
			echo '<p style="margin-top: 40px;"><a class="everest-forms-btn everest-forms-btn-primary" style="display:inline-flex;align-items:center;gap:8px;" target="_blank" rel="noopener noreferrer" href="' . esc_url( $upgrade_url ) . '">' . esc_html__( 'Upgrade Plan', 'everest-forms-pro' ) . '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14" width="14" height="14" aria-hidden="true" focusable="false"><path fill="#efefef" d="m7 1.167 3.5 9.333h-7z"/><path fill="#fff" fill-rule="evenodd" d="M12 12.834H2v-1.667h10zm0-2.334H2l-.833-7L7 8.312 12.833 3.5z" clip-rule="evenodd"/></svg></a></p>';
			echo '</div>';
		} else {
			do_action( 'everest_forms_providers_panel_content', $this->form );
			wp_localize_script(
				'everest-forms-integrations-scripts',
				'evf_integration_data',
				isset( $this->form_data['integrations'] ) ? $this->form_data['integrations'] : array()
			);
		}
	}
}

return new EVF_Builder_Integrations();
