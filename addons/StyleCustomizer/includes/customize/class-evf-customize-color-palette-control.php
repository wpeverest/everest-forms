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
	public $type = 'evf-color_palette';

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 */
	public function to_json() {
		parent::to_json();
		$this->json['default'] = $this->setting->default;
		$this->json['id']      = $this->id;
		$this->json['link']    = $this->get_link();
		$this->json['choices'] = $this->choices;

		$this->json['inputAttrs'] = '';
		foreach ( $this->input_attrs as $attr => $value ) {
			$this->json['inputAttrs'] .= $attr . '="' . esc_attr( $value ) . '" ';
		}

		$value               = $this->value();
		$this->json['value'] = array();
		if ( is_array( $value ) ) {
			foreach ( $this->value() as $key => $value ) {
				if ( is_numeric( $key ) ) {
					$this->json['value'][ $value ] = true;
				} else {
					$this->json['value'][ $key ] = $value;
				}
			}
		} elseif ( ! empty( $value ) ) {
			$this->json['value'] = array( $value => true );
		}
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
		<label {{{data.inputAttrs}}}>
			<# if ( data.label ) { #><span class="customize-control-title">{{{ data.label }}}
			<span class="<?php echo ! defined( 'EFP_PLUGIN_FILE' ) ? 'evf-pro-feature' : 'color-palette-edit-icon'; ?>" style="cursor:pointer;">&#9998;</span>
		 </span><# } #>
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><# } #>

		<ul class="color-palette">
		<# Object.keys( data.choices ).forEach( function( key ) { #>

			<li class="color-palette-item">
				<label class="color-palette-label" title="{{{data.choices[key].name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}}}" for="color-palette-{{{data.id}}}-{{{key}}}">
					<input  id="color-palette-{{{data.id}}}-{{{key}}}" type="checkbox" name="color-palette-{{{data.id}}}" value={{{data.choices[key].color}}} data-key="{{{key}}}" data-title="{{{data.choices[key].name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}}}" class="color-group-{{{key.charAt(0)}}}" {{{data.inputAttrs}}}/>
					<span class="color-palette-color" style="background-color:{{{data.choices[key].color}}};"></span>
				</label>
			</li>
		<# } ); #>
		</ul>
		<input class="color-palette-hidden-value" type="hidden" {{{ data.link }}} >
		</label>
		<?php
	}
}
