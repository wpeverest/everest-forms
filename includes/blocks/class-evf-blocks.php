<?php
/**
 * Everest Forms blocks.
 *
 * @since 2.0.9
 * @package everest-forms
 */

defined( 'ABSPATH' ) || exit;
/**
 * Everest Forms blocks class.
 */
class EVF_Blocks {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @since 2.0.9
	 */
	private function init_hooks() {
		$this->includes();
		add_filter( 'block_categories_all', array( $this, 'block_categories' ), PHP_INT_MAX, 2 );
		add_action( 'init', array( $this, 'register_block_types' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}
	/**
	 * Includes the block type files.
	 *
	 * @since 0
	 */
	private function includes() {
		include_once EVF_ABSPATH . 'includes/blocks/block-types/class-evf-blocks-abstract.php';
		include_once EVF_ABSPATH . 'includes/blocks/block-types/class-evf-blocks-form-selector.php';
	}
	/**
	 * Enqueue Block Editor Assets.
	 *
	 * @return void.
	 */
	public function enqueue_block_editor_assets() {
		wp_register_style(
			'everest-forms-block-editor',
			evf()->plugin_url() . '/assets/css/everest-forms.css',
			array( 'wp-edit-blocks' ),
			defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( evf()->plugin_path() . '/assets/css/everest-forms.css' ) : EVF_VERSION
		);

		wp_register_script(
			'everest-forms-block-editor',
			evf()->plugin_url() . '/dist/blocks.min.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components' ),
			defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( evf()->plugin_path() . '/dist/blocks.min.js' ) : EVF_VERSION,
			true
		);
		$form_block_data = array(
			'evfRestApiNonce' => wp_create_nonce( 'wp_rest' ),
			'restURL'         => rest_url(),
		);
		wp_localize_script( 'everest-forms-block-editor', '_EVF_BLOCKS_', $form_block_data );
		wp_enqueue_script( 'everest-forms-block-editor' );
	}

	/**
	 * Add "Everest Forms" category to the blocks listing in post edit screen.
	 *
	 * @param array $block_categories All registered block categories.
	 * @return array
	 * @since 2.0.9
	 */
	public function block_categories( array $block_categories ) {

		return array_merge(
			array(
				array(
					'slug'  => 'everest-forms',
					'title' => esc_html__( 'Everest Forms', 'everest-forms' ),
				),
			),
			$block_categories
		);
	}
	/**
	 * Register block types.
	 *
	 * @return void
	 */
	public function register_block_types() {
		$block_types = $this->get_block_types();
		foreach ( $block_types as $block_type ) {
			new $block_type();
		}
	}

	/**
	 * Get block types.
	 *
	 * @return AbstractBlock[]
	 */
	private function get_block_types() {
		return apply_filters(
			'everest_forms_block_types',
			array(
				EVF_Blocks_Form_Selector::class, //phpcs:ignore;
			)
		);
	}
}
return new EVF_Blocks();
