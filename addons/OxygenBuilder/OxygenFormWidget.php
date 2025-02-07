<?php
/**
 * Oxygen form widget.
 *
 * @since 3.0.5
 * @package  EverestForms\Addons\OxygenBuilder\OxygenFormWidget
 */
namespace EverestForms\Addons\OxygenBuilder;

use EverestForms\Addons\OxygenBuilder\OxygenElement;

class OxygenFormWidget extends OxygenElement {

	public $css_added = false;

	/**
	 * Name.
	 *
	 * @since 3.0.5
	 */
	public function name() {
		return __( 'Forms', 'everest-forms' );
	}

	/**
	 * Slug.
	 *
	 * @since 3.0.5
	 */
	public function slug() {
		return 'evf_form_widget';
	}

	/**
	 * Accordion place.
	 *
	 * @since 3.0.5
	 */
	public function accordion_button_place() {
		return 'form';
	}
	/**
	 * Enqueue the styles.
	 *
	 * @since 3.0.5
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
	 * @since 3.0.5
	 */
	public function icon() {
		return \EVF_Admin_Menus::get_icon_svg();
	}

	/**
	 * Add controls.
	 *
	 * @since 3.0.5
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

		$this->form_container_style_controls();
		$this->form_input_labels_style();
		$this->submit_btn_style();
	}

	/**
	 * Form contrainer style controls.
	 *
	 * @since 3.0.5
	 */
	public function form_container_style_controls() {
		$section_container = $this->addControlSection(
			'evf_container',
			__( 'Form Container', 'everest-forms' ),
			'assets/icon.png',
			$this
		);
		$selector          = '.everest-forms';
		$section_container->addStyleControls(
			array(
				array(
					'name'     => __( 'Background Color', 'everest-forms' ),
					'selector' => $selector,
					'property' => 'background-color',
				),
				array(
					'name'     => __( 'Max Width', 'everest-forms' ),
					'selector' => $selector,
					'property' => 'width',
				),
			)
		);

		$section_container->addPreset(
			'padding',
			'evf_container_padding',
			__( 'Padding', 'everest-forms' ),
			$selector
		)->whiteList();

		$section_container->addPreset(
			'margin',
			'evf_container_margin',
			__( 'Margin', 'everest-forms' ),
			$selector
		)->whiteList();

		$section_container->addPreset(
			'border',
			'evf_container_border',
			__( 'Border', 'everest-forms' ),
			$selector
		)->whiteList();

		$section_container->addPreset(
			'border-radius',
			'evf_container_radius',
			__( 'Border Radius', 'everest-forms' ),
			$selector
		)->whiteList();

		$section_container->boxShadowSection(
			__( 'Box Shadow', 'everest-forms' ),
			$selector,
			$this
		);
	}

	/**
	 * Field input label styles.
	 *
	 * @since 3.0.5
	 */
	public function form_input_labels_style() {
		$section_label = $this->addControlSection(
			'evf-label',
			__( 'Labels', 'everest-forms' ),
			'assets/icon.png',
			$this
		);

		$selector = '.evf-field-label';
		$section_label->typographySection( __( 'Typography','everest-forms' ), $selector, $this );
		$section_label->addStyleControls(
			array(
				array(
					'name'     => __( 'Text Color', 'everest-forms' ),
					'selector' => $selector,
					'property' => 'color',
				),
			)
		);
		$section_label->addStyleControl(
			array(
				'name'     => __( 'Asterisk Color', 'everest-forms' ),
				'selector' => '.evf-field-label .required',
				'property' => 'color',
			)
		);
	}

	/**
	 * Submit button style.
	 *
	 * @since 3.0.5
	 */
	public function submit_btn_style() {
		$section_submit_btn = $this->addControlSection(
			'evf-submit-button',
			__( 'Submit Button', 'everest-forms' ),
			'assets/icon.png',
			$this
		);

		$selector_submit_bttn = '.everest-forms-submit-button';
		$section_submit_btn->addStyleControls(
			array(
				array(
					'name'     => __( 'Color', 'everest-forms' ),
					'selector' => $selector_submit_bttn,
					'property' => 'color',
				),
				array(
					'name'     => __( 'Background Color', 'everest-forms' ),
					'selector' => $selector_submit_bttn,
					'property' => 'background-color',
				),
				array(
					'name'     => __( 'Hover Color', 'everest-forms' ),
					'selector' => '.ff-btn-submit:hover',
					'property' => 'background-color',
				),
				array(
					'name'         => __( 'Width', 'everest-forms' ),
					'selector'     => $selector_submit_bttn,
					'property'     => 'width',
					'control_type' => 'slider-measurebox',
					'unit'         => 'px',
				),
				array(
					'name'         => __( 'Margin Top', 'everest-forms' ),
					'selector'     => $selector_submit_bttn,
					'property'     => 'margin-top',
					'control_type' => 'slider-measurebox',
					'unit'         => 'px',
				),
			)
		);

		$section_submit_btn->addPreset(
			'padding',
			'evf_submit_bttn_padding',
			__( 'Padding', 'everest-forms' ),
			$selector_submit_bttn
		)->whiteList();

		$section_submit_btn->addPreset(
			'margin',
			'evf_submit_bttn_margin',
			__( 'Margin', 'everest-forms' ),
			$selector_submit_bttn
		)->whiteList();

		$section_submit_btn->typographySection( __( 'Typography', 'everest-forms' ), $selector_submit_bttn, $this );
		$section_submit_btn->borderSection( __( 'Border', 'everest-forms' ), $selector_submit_bttn, $this );
		$section_submit_btn->borderSection( __( 'Hover Border', 'everest-forms' ), $selector_submit_bttn . ':hover', $this );
		$section_submit_btn->boxShadowSection( __( 'Box Shadow', 'everest-forms' ), $selector_submit_bttn, $this );
		$section_submit_btn->boxShadowSection( __( 'Hover Box Shadow', 'everest-forms' ), $selector_submit_bttn . ':hover', $this );
	}

	/**
	 * Render the element's UI by outputting HTML.
	 *
	 * @since 3.0.5
	 *
	 * @param array $options
	 * @param array $defaults
	 * @param mixed $content
	 */
	public function render( $options, $defaults, $content ) {

		$content = sprintf( '<div class="evf-widget">%s</div>', esc_html__( 'Everest Forms','everest-forms' ) );

		if ( ! isset( $options['evf_form'] ) || empty( $options['evf_form'] ) ) {

			echo wp_kses( $content, evf_get_allowed_html_tags( 'builder' ) );

			return;
		}

		$form_id = absint( $options['evf_form'] );

		if ( empty( $form_id ) ) {

			echo wp_kses( $content, evf_get_allowed_html_tags( 'builder' ) );

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

		echo wp_kses( $content, evf_get_allowed_html_tags( 'builder' ) );
	}
}
