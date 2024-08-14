<?php
/**
 * Everest Forms Divi Module File.
 *
 * @package EverestForms\Divi
 *
 * @since xx.xx.xx
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms Divi module class.
 *
 * @since xx.xx.xx
 */
class EVF_Divi_Builder extends \ET_Builder_Module {
	/**
	 * Module slug.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $slug = 'everest_forms_divi_builder';

	/**
	 * Whether module support visual builder. e.g `on` or `off`.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $vb_support = 'on';

	/**
	 * Divi builder init function
	 *
	 * @since xx.xx.xx
	 */
	public function init() {
		$this->name = esc_html__( 'Everest Forms', 'everest-forms' );

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Forms', 'everest-forms' ),
				),
			),
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'load_divi_builder_scripts' ) );
	}

	/**
	 * Displays the Module setting fields.
	 *
	 * @since xx.xx.xx
	 *
	 * @return array $fields Array of settings fields.
	 */
	public function get_fields() {

		$forms = evf_get_all_forms();

		$fields = array(
			'form_id'    => array(
				'label'           => esc_html__( 'Select Form', 'everest-forms' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'main_content',
				'options'         => $forms,
				'default'         => '5',
			),
			'show_title' => array(
				'label'           => esc_html__( 'Show Title', 'everest-forms' ),
				'type'            => 'yes_no_button',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'main_content',
				'options'         => array(
					'off' => esc_html__( 'Off', 'everest-forms' ),
					'on'  => esc_html__( 'On', 'everest-forms' ),
				),
			),
			'show_desc'  => array(
				'label'           => esc_html__( 'Show Description', 'everest-forms' ),
				'option_category' => 'basic_option',
				'type'            => 'yes_no_button',
				'toggle_slug'     => 'main_content',
				'options'         => array(
					'off' => esc_html__( 'Off', 'everest-forms' ),
					'on'  => esc_html__( 'On', 'everest-forms' ),
				),
			),
		);
		return $fields;
	}

	/**
	 * Advanced Fields Settings.
	 *
	 * @since xx.xx.xx
	 */
	public function get_advanced_fields_config() {
		$advanced_fields = array(
			'link_options' => false,
			'text'         => false,
			'borders'      => false,
			'box_shadow'   => false,
			'button'       => false,
			'filters'      => false,
			'fonts'        => false,
		);

		return $advanced_fields;
	}

	/**
	 * Render the module on frontend.
	 *
	 * @since xx.xx.xx
	 *
	 * @param  array  $unprocessed_props Array of unprocessed Properties.
	 * @param  string $content Contents being processed from the prop.
	 * @param  string $render_slug The slug of rendering module for rendering output.
	 *
	 * @return string HTMl content for rendering.
	 */
	public function render( $unprocessed_props, $content, $render_slug ) {
		$form_id = isset( $this->props['form_id'] ) ? $this->props['form_id'] : '5';

		$divi_shortcode = sprintf( "[everest_form id='%s']", $form_id );
		$output         = sprintf( "<div class = '%s'>", 'everest-forms-divi-builder' );
		$output        .= do_shortcode( $divi_shortcode );
		$output        .= sprintf( '</div>' );

		return $output;
	}

	/**
	 * Function to enqueue the divi builder JS.
	 *
	 * @since xx.xx.xx
	 */
	public function load_divi_builder_scripts() {
		wp_enqueue_script( 'everest-forms-divi-builder' );
		$enqueue_script = array( 'wp-element', 'react', 'react-dom' );
		wp_register_script(
			'everest-forms-divi-builder',
			evf()->plugin_url() . '/dist/divibuilder.min.js',
			$enqueue_script,
			evf()->version,
			true
		);
	}
}
new EVF_Divi_Builder();
