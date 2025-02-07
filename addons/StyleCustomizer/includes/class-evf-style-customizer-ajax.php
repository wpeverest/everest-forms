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
		add_action( 'wp_ajax_save_custom_color_palette', array( $this, 'evf_save_custom_color_palette' ) );

	}

	/**
	 * Handle AJAX request to save custom color palette.
	 */
	public function evf_save_custom_color_palette() {
		$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'color_palette' ) ) {
			wp_send_json_error( __( 'Nonce error. Please refresh the page.', 'everest-forms' ) );
			exit;
		}

		$label  = isset( $_POST['label'] ) ? sanitize_text_field( $_POST['label'] ) : '';
		$colors = isset( $_POST['colors'] ) ? $_POST['colors'] : array();

		if ( empty( $colors ) ) {
			wp_send_json_error( __( 'Colors array is empty.', 'everest-forms' ) );
			exit;
		}

		delete_option( 'everest_forms_custom_color_palettes' );

		$color_palettes = array();

		$color_palettes[] = array(
			'label'     => $label,
			'colors'    => $colors,
			'is_pro'    => true,
			'is_custom' => true,
		);

		update_option( 'everest_forms_custom_color_palettes', $color_palettes );

		wp_send_json_success( esc_html__( 'Color palette saved successfully!', 'everest-forms' ) );

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
			$template->image = plugins_url( 'addons/StyleCustomizer/assets/images/templates/default.png', EVF_PLUGIN_FILE );
			$template->data  = isset( $styles[ $form_id ] ) ? $styles[ $form_id ] : '';

			if ( ! empty( $template->data ) ) {
				unset( $template->data['template'] );
			}

			$templates->$template_slug = $template;

			update_option( 'evf_style_templates', wp_json_encode( $templates ) );

			wp_send_json_success(
				array(
					'template_id' => $template_slug,
					'message'     => __( 'Template saved successfully. Please reload the page to view changes. Reload Now?', 'everest-forms' ),
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
