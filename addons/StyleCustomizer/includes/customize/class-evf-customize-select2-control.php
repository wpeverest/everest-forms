<?php
/**
 * Customize API: EVF_Customize_Select2_Control class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Select2 Control class.
 *
 * @see WP_Customize_Control
 */
class EVF_Customize_Select2_Control extends WP_Customize_Control {

	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'evf-select2';

	/**
	 * Use Google Fonts.
	 *
	 * @var bool
	 */
	public $google_font = false;

	/**
	 * Google Font List.
	 *
	 * @var array
	 */
	public $google_font_list = array();

	/**
	 * Item List.
	 *
	 * @var array
	 */
	public $item_list = array();

	/**
	 * Enqueue controls scripts.
	 */
	public function enqueue() {
		wp_enqueue_style( 'selectWoo' );
		wp_enqueue_script( 'selectWoo' );
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @uses WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();
		$this->json['id']               = $this->id;
		$this->json['value']            = $this->value();
		$this->json['link']             = $this->get_link();
		$this->json['choices']          = $this->choices;
		$this->json['default']          = $this->setting->default;
		$this->json['google_font']      = $this->google_font;
		$this->json['google_font_list'] = ( $this->google_font ) ? $this->get_google_fonts() : array();
		$this->json['item_list']        = $this->item_list;

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
			<span class="customize-control-title">{{ data.label }}</span>
		<# } #>
		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>

		<select class="evf-select2" {{{data.link}}} {{{data.inputAttrs}}} >
			<# if ( data.google_font === true ) { #>
				<# Object.keys( data.google_font_list ).forEach( function( key ) { #>
					<option value='{{{ data.google_font_list[key].family }}}' {{{ (data.value === data.google_font_list[key].family )?'selected':'' }}} >{{{ data.google_font_list[key].family }}}</option>
				<# } ); #>
			<#} else {#>
				<# Object.keys( data.item_list ).forEach( function( key ) { #>
					<option value='{{{ key }}}' {{{ (data.value === key )?'selected':'' }}} >{{{ data.item_list[key] }}}</option>
				<# } ); #>
			<# } #>
		</select>
		<?php
	}

	/**
	 * Returns Google Fonts.
	 *
	 * @return object Google fonts object.
	 */
	public function get_google_fonts() {
		$google_fonts = get_transient( 'evf_google_fonts' );

		if ( false === $google_fonts ) {
			$raw_google_fonts = wp_safe_remote_get( 'https://raw.githubusercontent.com/wpeverest/google-fonts/master/google-fonts.json' );

			if ( ! is_wp_error( $raw_google_fonts ) ) {
				$google_fonts = json_decode( wp_remote_retrieve_body( $raw_google_fonts ) );

				if ( isset( $google_fonts->items ) ) {
					$google_fonts = $google_fonts->items;
					set_transient( 'evf_google_fonts', $google_fonts, WEEK_IN_SECONDS );
				}
			}
		}

		return apply_filters( 'everest_forms_extensions_sections', $google_fonts );
	}
}
