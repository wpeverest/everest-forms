<?php
/**
 * Oxygen form widget.
 *
 * @since xx.xx.xx
 * @package  EverestForms\Addons\OxygenBuilder\OxygenFormWidget
 */
namespace EverestForms\Addons\OxygenBuilder;

use EverestForms\Addons\OxygenBuilder\OxygenElement;

class OxygenFormWidget extends OxygenElement {

	public $css_added = false;

	/**
	 * Name.
	 *
	 * @since xx.xx.xx
	 */
	public function name() {
		return __( 'Forms', 'everest-forms' );
	}

	/**
	 * Slug.
	 *
	 * @since xx.xx.xx
	 */
	public function slug() {
		return 'evf_form_widget';
	}

	/**
	 * Accordion place.
	 *
	 * @since xx.xx.xx
	 */
	public function accordion_button_place() {
		return 'form';
	}
	/**
	 * Enqueue the styles.
	 *
	 * @since xx.xx.xx
	 */
	public function custom_init() {
		wp_register_style( 'everest-forms-admin', evf()->plugin_url() . '/assets/css/admin.css', array(), EVF_VERSION );
		wp_register_style( 'everest-forms-general', evf()->plugin_url() . '/assets/css/everest-forms.css', array(), EVF_VERSION );

		wp_enqueue_style( 'everest-forms-admin' );
		wp_enqueue_style( 'everest-forms-general' );

	}
	/**
	 * Icon.
	 *
	 * @since xx.xx.xx
	 */
	public function icon() {
		return \EVF_Admin_Menus::get_icon_svg();
	}

	/**
	 * Add controls.
	 *
	 * @since xx.xx.xx
	 */
	public function controls() {
		$templates_control = $this->addOptionControl(
			array(
				'type'    => 'dropdown',
				'name'    => __( 'Select a Form', 'everest-forms' ),
				'slug'    => 'evf_form',
				'value'   => Helper::get_form_list(),
				'default' => 'no',
				'css'     => false,
			)
		);

		$templates_control->rebuildElementOnChange();

	}

	/**
	 * Render the element's UI by outputting HTML.
	 *
	 * @since xx.xx.xx
	 *
	 * @param array $options
	 * @param array $defaults
	 * @param mixed $content
	 */
	public function render( $options, $defaults, $content ) {

		$content = sprintf( '<div class="evf-widget">%s</div>', esc_html__( 'Everest Forms' ) );

		if ( ! isset( $options['evf_form'] ) || empty( $options['evf_form'] ) ) {

			echo $content;

			return;
		}

		$form_id = absint( $options['evf_form'] );

		if ( empty( $form_id ) ) {

			echo $content;

			return;
		}

		// Getting the form.
		$content = \EVF_Shortcodes::shortcode_wrapper(
			array( 'EVF_Shortcode_Form', 'output' ),
			array(
				'id' => $form_id,
			),
			array( 'class' => 'everest-forms' )
		);

		echo $content;
	}
}
