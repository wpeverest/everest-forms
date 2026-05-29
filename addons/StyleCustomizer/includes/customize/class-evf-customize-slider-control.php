<?php
/**
 * Customize API: EVF_Customize_Slider_Control class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Slider Control class.
 *
 * @see WP_Customize_Control
 */
class EVF_Customize_Slider_Control extends WP_Customize_Control {

	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'evf-slider';

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @uses WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();
		$this->json['default'] = $this->setting->default;
		$this->json['id']      = $this->id;
		$this->json['value']   = $this->value();
		$this->json['link']    = $this->get_link();
		$this->json['choices'] = $this->choices;

		$this->json['inputAttrs'] = '';
		foreach ( $this->input_attrs as $attr => $value ) {
			$this->json['inputAttrs'] .= $attr . '="' . esc_attr( $value ) . '" ';
		}
	}

	/**
	 * Don't render the control content from PHP, as it's rendered via JS on load.
	 */
	public function render_content() {}

	/**
	 * Render a JS template for control display.
	 *
	 * @see WP_Customize_Control::print_template()
	 */
	public function content_template() {
		?>
		<# if ( data.label ) { #>
			<label class="customize-control-title">{{ data.label }}</label>
		<# } #>
		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>
		<div class="customize-control-content">
			<div class="everest-forms-slider"></div>
			<div class="everest-forms-slider-input">
				<input {{{ data.inputAttrs }}} type="number" class="slider-input" value="{{ data.value }}" {{{ data.link }}}/>
				<span class="reset dashicons dashicons-image-rotate"></span>
			</div>
		</div>
		<?php
	}
}
