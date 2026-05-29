<?php
/**
 * EverestForms Builder Integrations
 *
 * @package EverestForms\Admin
 * @since
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Builder_Integrations class.
 */
if ( ! class_exists( 'EVF_Builder_Integrations' ) ) {

class EVF_Builder_Integrations extends EVF_Builder_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id      = 'integrations';
		$this->label   = __( 'Integrations', 'everest-forms' );
		$this->sidebar = true;

		parent::__construct();
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		$integrations = apply_filters( 'everest_forms_available_integrations', array() );

		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			$integrations = $this->get_free_integrations_catalog();
		}

		if ( ! empty( $integrations ) ) {
			foreach ( $integrations as $integration ) {
				if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
					$pro_icon = plugins_url( 'assets/images/icons/evf-pro-icon.png', EVF_PLUGIN_FILE );
					$video_id = isset( $integration['video_id'] ) ? $integration['video_id'] : ( isset( $integration['vedio_id'] ) ? $integration['vedio_id'] : '' );
					echo '<a href="#" class="integration-name evf-panel-tab evf-integrations-panel everest-forms-panel-sidebar-section everest-forms-panel-sidebar-section-' . esc_attr( $integration['id'] ) . ' upgrade-addons-settings" data-section="' . esc_attr( $integration['id'] ) . '" data-name="' . esc_attr( $integration['name'] ) . '" data-links="' . esc_attr( $video_id ) . '">';

					echo  '<div style="display: flex; align-items: center; gap: 12px;">';
					if ( ! empty( $integration['icon'] ) ) {
						echo '<figure class="logo"><img src="' . esc_url( $integration['icon'] ) . '"></figure>';
					}
					echo '<span>' . esc_html($integration['name']) . '</span>';
					echo '</div>';
					echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18"><rect width="16.889" height="16.889" x=".444" y=".444" fill="#ff8c39" stroke="#ff8c39" stroke-width=".889" rx="2.222"/><path fill="#efefef" d="m8.89 4.444 2.666 7.111H6.223z"/><path fill="#fff" fill-rule="evenodd" d="m4.445 6.222.635 5.333h7.619l.635-5.333-4.445 3.666zm8.254 5.841h-7.62v1.27h7.62z" clip-rule="evenodd"/></svg>';
					echo '</a>';

				} else {
					$this->add_sidebar_tab( $integration['name'], $integration['id'], $integration['icon'], $this->id );
					do_action( 'everest_forms_integration_connections_' . $integration['id'], $integration );
				}
			}
		}
	}

	/**
	 * Get free integrations catalog from extensions JSON.
	 *
	 * @return array
	 */
	private function get_free_integrations_catalog() {
		$catalog = array();
		$file    = dirname( EVF_PLUGIN_FILE ) . '/assets/extensions-json/sections/all_extensions.json';

		if ( file_exists( $file ) ) {
			$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$data    = json_decode( $content, true );

			if ( isset( $data['products'] ) && is_array( $data['products'] ) ) {
				$data['features'] = isset( $data['features'] ) && is_array( $data['features'] ) ? $data['features'] : array();
				$items            = array_merge( $data['products'], $data['features'] );

				foreach ( $items as $item ) {
					$category = isset( $item['category'] ) ? $item['category'] : '';
					if ( ! in_array( $category, array( 'Integrations', 'Email Marketing', 'CRM Integrations' ), true ) ) {
						continue;
					}

					$slug = isset( $item['slug'] ) ? $item['slug'] : '';
					$id   = str_replace( 'everest-forms-', '', $slug );
					$name = isset( $item['name'] ) ? $item['name'] : '';
					$name = str_replace( 'Everest Forms - ', '', $name );
					$name = str_replace( 'Everest Forms- ', '', $name );
					$name = str_replace( 'Everest Forms-', '', $name );
					$name = str_replace( 'Everest Forms ', '', $name );
					$name = trim( $name );

					$image = isset( $item['image'] ) ? $item['image'] : '';
					$icon  = ! empty( $image ) ? plugins_url( 'assets/' . ltrim( $image, '/' ), EVF_PLUGIN_FILE ) : '';

					$catalog[ $id ] = array(
						'id'       => $id,
						'name'     => $name,
						'icon'     => $icon,
						'video_id' => isset( $item['demo_video_url'] ) ? $item['demo_video_url'] : '',
					);
				}
			}
		}

		return array_values( $catalog );
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

}
