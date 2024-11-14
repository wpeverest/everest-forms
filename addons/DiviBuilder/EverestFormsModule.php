<?php
/**
 * Everest Forms Divi Module File.
 *
 * @package EverestForms\Divi
 * @since 3.0.5
 */
namespace EverestForms\Addons\DiviBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms Divi module class.
 *
 * @since 3.0.5
 */
class EverestFormsModule extends \ET_Builder_Module {
	/**
	 * Module slug.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	public $slug = 'everest_forms_divi_builder';

	/**
	 * Whether module support visual builder. e.g `on` or `off`.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	public $vb_support = 'on';

	/**
	 * List of controls to allow module customization.
	 *
	 * @since 1.6.13
	 * @var array
	 */
	protected $setting_controls = array();

	/**
	 * Divi builder init function.
	 *
	 * @since 3.0.5
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
	 * @since 3.0.5
	 * @return array $fields Array of settings fields.
	 */
	public function get_fields() {

		$forms        = evf_get_all_forms();
		$default_form = array( esc_html__( 'Select Form', 'everest-forms' ) );
		$forms        = $default_form + $forms;

		$fields = array(
			'form_id'              => array(
				'label'            => esc_html__( 'Select Form', 'everest-forms' ),
				'type'             => 'select',
				'option_category'  => 'basic_option',
				'toggle_slug'      => 'main_content',
				'options'          => $forms,
				'default'          => '5',
				'computed_affects' => array(
					'__rendered_evf_forms',
				),
			),
			'__rendered_evf_forms' => array(
				'type'                => 'computed',
				'computed_callback'   => 'EverestForms\Addons\DiviBuilder\EverestFormsModule::rendered_evf_forms',
				'computed_depends_on' => array(
					'form_id',
				),
				'computed_minimum'    => array(
					'form_id',
				),
			),

		);
		return $fields;
	}

	/**
	 * Advanced Fields Settings.
	 *
	 * @since 3.0.5
	 */
	public function get_advanced_fields_config() {
		return array(
			'link_options' => false,
			'text'         => false,
			'borders'      => false,
			'box_shadow'   => false,
			'button'       => false,
			'filters'      => false,
			'fonts'        => false,
		);
	}

	/**
	 * Renders the Everest Form in Visual Builder and Frontend.
	 *
	 * @since 3.0.5
	 * @param array $props Module properties.
	 * @return string HTML output.
	 */
	public static function rendered_evf_forms( $props = array() ) {
		$form_id = isset( $props['form_id'] ) ? $props['form_id'] : '0';

		// Check if we are in the Divi Visual Builder
		if ( et_fb_enabled() ) {
			return "<div class='everest-forms-divi-preview'>" . esc_html__( 'Everest Forms Preview', 'everest-forms' ) . '</div>';
		}

		if ( '0' === $form_id ) {
			return "<div class='everest-forms-divi-empty-form' style='text-align:center'><img src='" . plugin_dir_url( EVF_PLUGIN_FILE ) . 'assets/images/icons/Everest-forms-Logo.png' . "'/></div>";
		}

		// Render the form via shortcode in the frontend.
		$divi_shortcode = sprintf( "[everest_form id='%s']", $form_id );
		$output         = "<div class='everest-forms-divi-builder'>";
		$output        .= do_shortcode( $divi_shortcode );
		$output        .= '</div>';

		return $output;
	}

	/**
	 * Render the module on frontend.
	 *
	 * @since 3.0.5
	 * @param array  $unprocessed_props Array of unprocessed Properties.
	 * @param string $content Contents being processed from the prop.
	 * @param string $render_slug The slug of rendering module for rendering output.
	 * @return string HTML content for rendering.
	 */
	public function render( $unprocessed_props, $content, $render_slug ) {
		return $this->_render_module_wrapper( static::rendered_evf_forms( $this->props ), $render_slug );
	}

	/**
	 * Enqueue Divi Builder JavaScript.
	 *
	 * @since 3.0.5
	 */
	public function load_divi_builder_scripts() {
		$enqueue_script = array( 'wp-element', 'react', 'react-dom' );
		wp_register_script(
			'everest-forms-divi-builder',
			evf()->plugin_url() . '/dist/divibuilder.min.js',
			$enqueue_script,
			evf()->version,
			true
		);
		wp_enqueue_script( 'everest-forms-divi-builder' );
	}
}
