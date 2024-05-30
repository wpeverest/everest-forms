<?php
/**
 * Customize API: EVF_Customize_Color_Palette_Control class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Color Palette Control class.
 */
class EVF_Customize_Color_Palette_Control extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'evf-color-palette';

	/**
	 * Display label.
	 *
	 * @var bool
	 */
	public $display_label = false;

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 */
	public function to_json() {
		parent::to_json();
		$this->json['default'] = $this->setting->default;
		$this->json['id']      = $this->id;
		$this->json['value']   = $this->value();
		$this->json['link']    = $this->get_link();
		$this->json['choices'] = $this->choices;
	}

	/**
	 * Don't render the control content from PHP, as it's rendered via JS on load.
	 */
	public function render_content() {}

	/**
	 * Render a JS template for control display.
	 */
	protected function content_template() {
		?>
		<label>
			<# if ( data.label ) { #><span class="customize-control-title">{{{ data.label }}}</span><# } #>
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><# } #>
		</label>
		<ul class="color-palette">
			<# Object.keys( data.choices ).forEach( function( key ) { #>
				<li class="color-palette-item">
					<label class="color-palette-label" title="{{{data.choices[key].name}}}" for="color-palette-{{{data.id}}}-{{{key}}}">
						<input id="color-palette-{{{data.id}}}-{{{key}}}" type="checkbox" name="color-palette-{{{data.id}}}" value="{{{data.choices[key].color}}}" {{{data.link}}} {{{ (data.value[key])? 'checked="checked"' : '' }}} />
						<span class="color-palette-color" style="background-color:{{{data.choices[key].color}}};"></span>
					</label>
					<span class="tooltip">{{{data.choices[key].name}}}</span>
				</li>
			<# } ); #>
		</ul>
		<?php
	}
}
