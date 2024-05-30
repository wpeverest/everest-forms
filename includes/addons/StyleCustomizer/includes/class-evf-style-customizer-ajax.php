<?php
/**
 * EverestForms Style Customizer Ajax
 *
 * @package EverestForms_Style_Customizer
 * @since   1.0.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main EverestForms Style Customizer Ajax Class.
 *
 * @class EVF_Style_Customizer_Ajax
 */
final class EVF_Style_Customizer_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_save_template', array( $this, 'save_template' ) );
		add_action( 'wp_ajax_delete_template', array( $this, 'delete_template' ) );
	}

	/**
	 * Save styles as a template.
	 *
	 * @retEVFn void
	 */
	public function save_template() {
		$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'save_template' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce error. Please refresh the page.', 'everest-forms' ),
				)
			);
			exit;
		}

		$form_id = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '0';

		if ( ! empty( $form_id ) ) {
			$template_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

			if ( empty( $template_name ) ) {
				$template_name = strval( time() );
			}

			$template_slug = str_replace( ' ', '-', strtolower( $template_name ) );
			$templates     = json_decode( get_option( 'evf_style_templates' ) );
			$styles        = get_option( 'everest_forms_styles' );

			if ( isset( $templates->$template_slug ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Template name exists. Please change the template name and try again.', 'everest-forms' ),
					)
				);
				exit;
			}

			$template        = new stdClass();
			$template->name  = $template_name;
			$template->image = plugins_url( '/includes/addons/StyleCustomizer/assets/images/templates/default.png', EVF_PLUGIN_FILE );
			$template->data  = isset( $styles[ $form_id ] ) ? $styles[ $form_id ] : '';

			if ( ! empty( $template->data ) ) {
				unset( $template->data['template'] );
			}

			$templates->$template_slug = $template;

			update_option( 'evf_style_templates', wp_json_encode( $templates ) );

			wp_send_json_success(
				array(
					'template_id' => $template_slug,
					'message'     => __( 'Template saved successfully. Please reload the page to view changes. Reload Now?', 'user-registration-style-customizer' ),
				)
			);
			exit;
		}
	}


	/**
	 * Delete template.
	 *
	 * @retEVFn void
	 */
	public function delete_template() {
		$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'delete_template' ) ) {
			wp_send_json_error( __( 'Nonce error. Please refresh the page.', 'everest-forms' ) );
			exit;
		}

		$template_slug = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$templates     = json_decode( get_option( 'evf_style_templates' ) );

		if ( isset( $templates->$template_slug ) ) {
			unset( $templates->$template_slug );
		}

		update_option( 'evf_style_templates', wp_json_encode( $templates ) );
		wp_send_json_success( __( 'Template deleted successfully.', 'everest-forms' ) );
		exit;
	}
}

new EVF_Style_Customizer_Ajax();
