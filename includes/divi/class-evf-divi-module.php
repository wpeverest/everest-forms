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
class EVF_Divi_Module extends \ET_Builder_Module {
	/**
	 * Module slug.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $slug = 'everest_forms_module';

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
	}

	/**
	 * Displays the Module setting fields.
	 *
	 * @since xx.xx.xx
	 *
	 * @return array $fields Array of settings fields.
	 */
	public function get_fields() {

		$forms  = evf_get_all_forms();
		$fields = array(
			'form_id'    => array(
				'label'           => esc_html__( 'Form', 'everest-forms' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'toggle_slug'     => 'main_content',
				'options'         => $forms,
				'default'         => '0',
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
	 * @since 0
	 *
	 * @param  array  $unprocessed_props Array of unprocessed Properties.
	 * @param  string $content Contents being processed from the prop.
	 * @param  string $render_slug The slug of rendering module for rendering output.
	 */
	public function render( $unprocessed_props, $content, $render_slug ) {

	}
}

 new EVF_Divi_Module();
