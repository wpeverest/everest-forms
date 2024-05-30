<?php
/**
 * Customize API: EVF_Customize_Toggle_Control class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Toggle Control class.
 *
 * @see WP_Customize_Control
 */
class EVF_Customize_Toggle_Control extends WP_Customize_Control {

	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'evf-toggle';

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @uses WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();
		$this->json['id']           = $this->id;
		$this->json['value']        = $this->value();
		$this->json['link']         = $this->get_link();
		$this->json['defaultValue'] = $this->setting->default;
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
		<label for="toggle_{{ data.id }}">
			<span class="customize-control-title">{{{ data.label }}}</span>
			<# if ( data.description ) { #>
				<span class="description customize-control-description">{{{ data.description }}}</span>
			<# } #>
			<input id="toggle_{{ data.id }}" type="checkbox" class="screen-reader-text" value="{{ data.value }}" {{{ data.link }}} <# if ( true === data.value ) { #> checked="checked"<# } #> />
			<span class="switch">
				<span class="switch-content"></span>
			</span>
		</label>
		<?php
	}
}
