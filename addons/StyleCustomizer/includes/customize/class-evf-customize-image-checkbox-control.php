<?php
/**
 * Customize API: EVF_Customize_Image_Checkbox_Control class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Image Checkbox Control class.
 *
 * @see WP_Customize_Control
 */
class EVF_Customize_Image_Checkbox_Control extends WP_Customize_Control {

	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'evf-image_checkbox';

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @uses WP_Customize_Control::to_json()
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
	 *
	 * @see WP_Customize_Control::print_template()
	 */
	public function content_template() {
		?>
		<label>
			<# if ( data.label ) { #><span class="customize-control-title">{{{ data.label }}}</span><# } #>
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><#
			} #>

		</label>
		<ul class="image-checkbox-wrapper">
			<# Object.keys( data.choices ).forEach( function( key ) { #>

			<li>
				<input id="image-checkbox-{{{data.id}}}-{{{key}}}" type="checkbox" name="image-checkbox-{{{data.id}}}" value = "{{{key}}}" {{{ ( data.value[key]!=undefined && data.value[key] == true ) ? 'checked="checked"' : '' }}}/>
				<label class="image-checkbox-item" title="{{{data.choices[key].name}}}" for="image-checkbox-{{{data.id}}}-{{{key}}}">
					<img src="{{{data.choices[key].image}}}" alt="{{{data.choices[key].name}}}" />
				</label>
			</li>
			<# } ); #>
		</ul>
		<input class="image-checkbox-hidden-value" type="hidden" {{{ data.link }}} >
		<?php
	}
}
