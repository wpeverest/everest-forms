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

		register_block_type( 'everest-forms/form-selector', array(
			'attributes'      => array(
				'formId'       => array(
					'type' => 'string',
				),
			),
			'editor_style'    => 'everest-forms-block-editor',
			'editor_script'   => 'everest-forms-block-editor',
			'render_callback' => array( $this, 'get_form_html' ),
		) );
	}

	/**
	 * Load Gutenberg block scripts.
	 */
	public function enqueue_block_editor_assets() {
		wp_register_style(
			'everest-forms-block-editor',
			EVF()->plugin_url() . '/assets/css/everest-forms.css',
			array( 'wp-edit-blocks' ),
			defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( EVF()->plugin_path() . '/assets/css/everest-forms.css' ) : EVF_VERSION
		);

		wp_register_script(
			'everest-forms-block-editor',
			EVF()->plugin_url() . '/assets/js/admin/gutenberg/form-block.min.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-editor', 'wp-components' ),
			defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( EVF()->plugin_path() . '/assets/js/admin/gutenberg/form-block.min.js' ) : EVF_VERSION,
			true
		);

		$form_block_data = array(
			'forms' => EVF()->form->get( '', array( 'order' => 'DESC' ) ),
			'i18n'  => array(
				'title'         => esc_html__( 'Everest Forms', 'everest-forms' ),
				'description'   => esc_html__( 'Select &#38; display one of your form.', 'everest-forms' ),
				'form_select'   => esc_html__( 'Select a Form', 'everest-forms' ),
				'form_settings' => esc_html__( 'Form Settings', 'everest-forms' ),
				'form_selected' => esc_html__( 'Form', 'everest-forms' ),
			)
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

		$is_gb_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context'];

		// Disable form fields if called from the Gutenberg editor.
		if ( $is_gb_editor ) {
			add_filter( 'everest_forms_frontend_container_class', function ( $classes ) {
				$classes[] = 'evf-gutenberg-form-selector';
				return $classes;
			} );
			add_action( 'everest_forms_frontend_output', function () {
				echo '<fieldset disabled>';
			}, 3 );
			add_action( 'everest_forms_frontend_output', function () {
				echo '</fieldset>';
			}, 30 );
		}

		return EVF_Shortcodes::form(
			array(
				'id' => $form_id,
			)
		);
	}
}

new EVF_Form_Block();
