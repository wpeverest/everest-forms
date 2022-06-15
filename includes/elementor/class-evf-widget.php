<?php
/**
 * Everest Forms for Elementor.
 *
 * @package EverstForms\Class
 * @version 1.8.5
 */

use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Everest Forms Widget for Elementor.
 *
 * @since 1.8.5
 */
class EVF_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve shortcode widget name.
	 *
	 * @since 1.8.5
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'everest-forms';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve shortcode widget title.
	 *
	 * @since 1.8.5
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Everest Forms', 'everest-forms' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve shortcode widget icon.
	 *
	 * @since 1.8.5
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'everest-icon';
	}


	/**
	 * Get widget categories.
	 *
	 * @since 1.8.5
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		if ( class_exists( 'EverestForms_Style_Customizer' ) ) {
			return array(
				'everest-forms',
			);
		} else {
			return array(
				'basic',
			);
		}

	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 1.8.5
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'form', 'forms', 'everest-forms', 'contact form', 'everest', 'everestforms' );
	}

	/**
	 * Register controls.
	 *
	 * @since 1.8.5
	 */
	protected function register_controls() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		$this->start_controls_section(
			'section_content_layout',
			array(
				'label' => esc_html__( 'Form', 'everest-forms' ),
			)
		);
		$forms = $this->get_forms();
		$this->add_control(
			'everest_form',
			array(
				'label'   => esc_html__( 'Select Form', 'everest-forms' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $forms,
			)
		);
		$this->end_controls_section();

		do_action( 'everest_form_elemntor_style', $this );

	}

	/**
	 * Retrieve the shortcode.
	 *
	 * @since 1.8.5
	 */
	private function get_shortcode() {

		$settings = $this->get_settings_for_display();
		if ( ! $settings['everest_form'] ) {
			return '<p>' . __( 'Please select a Everest Forms.', 'everest-forms' ) . '</p>';
		}

		$attributes = array(
			'id' => $settings['everest_form'],
		);

		$this->add_render_attribute( 'shortcode', $attributes );

		$shortcode   = array();
		$shortcode[] = sprintf( '[everest_form %s]', $this->get_render_attribute_string( 'shortcode' ) );

		return implode( '', $shortcode );
	}

	/**
	 * Render widget output.
	 *
	 * @since 1.8.5
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
			<?php echo do_shortcode( $this->get_shortcode() ); ?>
		<?php
	}

	/**
	 * Retrieve the  available evf forms.
	 *
	 * @since 1.8.5
	 */
	public function get_forms() {

		$everest_forms = array();

		if ( empty( $everest_forms ) ) {

			$evf_forms = evf()->form->get();
			if ( ! empty( $evf_forms ) ) {
				foreach ( $evf_forms as $evf_form ) {
					$everest_forms[ $evf_form->ID ] = $evf_form->post_title;
				}
			} else {
				$everest_forms[0] = esc_html__( 'You have not created a form, Please Create a form first', 'everest-forms' );
			}

			return $everest_forms;
		}
	}
}
