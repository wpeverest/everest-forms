<?php
/**
 * Everest Forms Divi Module File.
 *
 * @package EverestForms\Divi
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
	 * List of controls to allow module customization.
	 *
	 * @since 1.6.13
	 * @var array
	 */
	protected $setting_controls = array();

	/**
	 * Divi builder init function.
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

		// Computed controls.
		$this->add_computed_control(
			'__rendered_evf_forms',
			'rendered_evf_forms',
			array( 'form_id' )
		);
	}

	/**
	 * Add a computed control.
	 *
	 * @since 1.6.13
	 * @param string $name
	 * @param string $callback_static_method
	 * @param array  $dependency_props
	 */
	public function add_computed_control( $name, $callback_static_method, $dependency_props = array() ) {
		if ( empty( $name ) || empty( $callback_static_method ) || ! is_string( $callback_static_method ) ) {
			return;
		}

		$this->setting_controls[ $name ] = array(
			'type'                => 'computed',
			'computed_callback'   => array( static::class, $callback_static_method ),
			'computed_depends_on' => $dependency_props,
			'computed_minimum'    => $dependency_props,
		);
	}

	/**
	 * Displays the Module setting fields.
	 *
	 * @since xx.xx.xx
	 * @return array $fields Array of settings fields.
	 */
	public function get_fields() {

		$forms        = evf_get_all_forms();
		$default_form = array( esc_html__( 'Select Form', 'everest-forms' ) );
		$forms        = $default_form + $forms;

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
	 * @since xx.xx.xx
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
	 * @since xx.xx.xx
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
	 * @since xx.xx.xx
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

// Initialize the module.
new EVF_Divi_Builder();
