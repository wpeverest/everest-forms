<?php
/**
 * EverestForm Gutenberg blocks
 *
 * @package EverstForms\Class
 * @version 1.3.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Guten Block Class.
 */
class EVF_Form_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Register the block and its scripts.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'everest-forms/form-selector',
			array(
				'attributes'      => array(
					'formId'             => array(
						'type' => 'string',
					),
					'className'          => array(
						'type' => 'string',
					),
					'displayTitle'       => array(
						'type' => 'boolean',
					),
					'displayDescription' => array(
						'type' => 'boolean',
					),
					'displayPopup'       => array(
						'type' => 'boolean',
					),
					'displayPopupType'   => array(
						'type' => 'string',
					),
					'displayPopupText'   => array(
						'type' => 'string',
					),
					'displayPopupSize'   => array(
						'type' => 'string',
					),
				),
				'editor_style'    => 'everest-forms-block-editor',
				'editor_script'   => 'everest-forms-block-editor',
				'render_callback' => array( $this, 'get_form_html' ),
			)
		);
	}

	/**
	 * Load Gutenberg block scripts.
	 */
	public function enqueue_block_editor_assets() {
		wp_register_style(
			'everest-forms-block-editor',
			evf()->plugin_url() . '/assets/css/everest-forms.css',
			array( 'wp-edit-blocks' ),
			defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( evf()->plugin_path() . '/assets/css/everest-forms.css' ) : EVF_VERSION
		);

		if ( defined( 'EFP_PLUGIN_FILE' ) ) {
			wp_register_script(
				'everest-forms-block-editor',
				plugins_url( '/assets/js/admin/gutenberg/form-block.min.js', EFP_PLUGIN_FILE ),
				array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components' ),
				defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( EFP_PLUGIN_FILE ) . '/assets/js/admin/gutenberg/form-block.min.js' ) : EFP_VERSION,
				true
			);
		} else {
			wp_register_script(
				'everest-forms-block-editor',
				evf()->plugin_url() . '/assets/js/admin/gutenberg/form-block.min.js',
				array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components' ),
				defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( evf()->plugin_path() . '/assets/js/admin/gutenberg/form-block.min.js' ) : EVF_VERSION,
				true
			);
		}

		$form_block_data = array(
			'forms' => evf()->form->get_multiple( array( 'order' => 'DESC' ) ),
			'i18n'  => array(
				'title'            => esc_html__( 'Everest Forms', 'everest-forms' ),
				'description'      => esc_html__( 'Select and display one of your forms.', 'everest-forms' ),
				'form_keywords'    => array(
					esc_html__( 'form', 'everest-forms' ),
					esc_html__( 'contact', 'everest-forms' ),
					esc_html__( 'survey', 'everest-forms' ),
				),
				'form_select'      => esc_html__( 'Select a Form', 'everest-forms' ),
				'form_settings'    => esc_html__( 'Form Settings', 'everest-forms' ),
				'form_selected'    => esc_html__( 'Form', 'everest-forms' ),
				'show_title'       => esc_html__( 'Show Title', 'everest-forms' ),
				'show_description' => esc_html__( 'Show Description', 'everest-forms' ),
				'show_Popup'       => esc_html__( 'Show Popup', 'everest-forms' ),
				'popup_type'       => esc_html__( 'Popup Type', 'everest-forms' ),
				'popup_size'       => esc_html__( 'Popup Size', 'everest-forms' ),
			),
		);
		wp_localize_script( 'everest-forms-block-editor', 'evf_form_block_data', $form_block_data );
	}

	/**
	 * Get form HTML to display in a Gutenberg block.
	 *
	 * @param  array $attr Attributes passed by Gutenberg block.
	 * @return string
	 */
	public function get_form_html( $attr ) {
		$form_id = ! empty( $attr['formId'] ) ? absint( $attr['formId'] ) : 0;

		if ( empty( $form_id ) ) {
			return '';
		}

		// Wrapper classes.
		$classes = 'everest-forms';
		if ( isset( $attr['className'] ) ) {
			$classes .= ' ' . $attr['className'];
		}

		$is_gb_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context']; // phpcs:ignore WordPress.Security.NonceVerification
		$title        = ! empty( $attr['displayTitle'] ) ? true : false;
		$description  = ! empty( $attr['displayDescription'] ) ? true : false;
		$popup        = ! empty( $attr['displayPopup'] ) ? true : false;
		$popup_type   = ! empty( $attr['displayPopupType'] ) ? $attr['displayPopupType'] : false;
		$popup_text   = ! empty( $attr['displayPopupText'] ) ? $attr['displayPopupText'] : 'View Form';
		$popup_size   = ! empty( $attr['displayPopupSize'] ) ? $attr['displayPopupSize'] : false;

		// Disable form fields if called from the Gutenberg editor.
		if ( $is_gb_editor ) {
			add_filter(
				'everest_forms_frontend_container_class',
				function ( $classes ) {
					$classes[] = 'evf-gutenberg-form-selector';
					$classes[] = 'evf-container-full';
					return $classes;
				}
			);
			add_action(
				'everest_forms_frontend_output',
				function () {
					echo '<fieldset disabled>';
				},
				3
			);
			add_action(
				'everest_forms_frontend_output',
				function () {
					echo '</fieldset>';
				},
				30
			);
		}

		return EVF_Shortcodes::shortcode_wrapper(
			array( 'EVF_Shortcode_Form', 'output' ),
			array(
				'id'          => $form_id,
				'title'       => $title,
				'description' => $description,
				'type'        => $popup_type,
				'text'        => $popup_text,
				'size'        => $popup_size,
			),
			array(
				'class' => evf_sanitize_classes( $classes ),
			)
		);
	}
}

new EVF_Form_Block();
