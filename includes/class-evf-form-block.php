<?php
/**
 * EverestForm Gutenberg blocks
 *
 * @package EverstForms\Class
 * @version 1.3.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Features Class.
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
		register_block_type( 'everest-forms/form-selector', array(
			'editor_script' => 'everest-forms-block-editor',
		) );
	}

	/**
	 * Load Gutenberg block scripts.
	 */
	public function enqueue_block_editor_assets() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'everest-forms-block-editor', EVF()->plugin_url() . '/assets/js/admin/form-block' . $suffix . '.js', array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components' ), EVF_VERSION, true );

		$form_block_data = array(
			'forms'   => evf_get_all_forms(),
			'siteUrl' => get_site_url(),
			'i18n'    => array(
				'title'            => esc_html__( 'Everest Forms', 'everest-forms' ),
				'description'      => esc_html__( 'Select & display one of your form.', 'everest-forms' ),
				'form_select'      => esc_html__( 'Select a Form', 'everest-forms' ),
				'form_settings'    => esc_html__( 'Form Settings', 'everest-forms' ),
				'form_selected'    => esc_html__( 'Form', 'everest-forms' ),
			)
		);
		wp_localize_script( 'everest-forms-block-editor', 'evf_form_block_data', $form_block_data );

		wp_enqueue_script( 'everest-forms-block-editor' );
	}

	/**
	 * Get form HTML to display in a Gutenberg block.
	 *
	 * @param  array $attr Attributes passed by Gutenberg block.
	 * @return string
	 */
	public function get_form_html( $attr ) {}
}

new EVF_Form_Block();
