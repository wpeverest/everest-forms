<?php
/**
 * Customize API: EVF_Customize_Dimension_Control class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Dimension Control class.
 *
 * @see WP_Customize_Control
 */
class EVF_Customize_Dimension_Control extends WP_Customize_Control {

	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'evf-dimension';

	/**
	 * Responsive active.
	 *
	 * @var boolean
	 */
	public $responsive = false;

	/**
	 * Responsive tabs.
	 *
	 * @var array
	 */
	public $responsive_tabs = array();

	/**
	 * Dimension Units.
	 *
	 * @var array
	 */
	public $unit_choices = array();

	/**
	 * Input type.
	 *
	 * @var string
	 */
	public $input_type = 'text';

	/**
	 * Inputs.
	 *
	 * @var array
	 */
	public $inputs = array();

	/**
	 * Allow Anchor.
	 *
	 * @var bool
	 */
	public $anchor = true;

	/**
	 * Default Anchor.
	 *
	 * @var bool
	 */
	public $default_anchor = true;

	/**
	 * EVF_Customize_Dimension_Control constructor.
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 * @param string               $id      An specific ID of the section.
	 * @param array                $args    Section arguments.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		$this->inputs          = ( isset( $this->inputs ) && ! empty( $this->inputs ) ) ? $this->inputs : $this->get_default_inputs();
		$this->responsive_tabs = ( isset( $this->responsive_tabs ) && ! empty( $this->responsive_tabs ) ) ? $this->responsive_tabs : $this->get_default_responsive_tabs();
	}

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

		$this->json['responsive']      = $this->responsive;
		$this->json['responsive_tabs'] = $this->responsive_tabs;
		$this->json['unit_choices']    = $this->unit_choices;
		$this->json['input_type']      = $this->input_type;
		$this->json['inputs']          = $this->inputs;
		$this->json['anchor']          = $this->anchor;
		$this->json['default_anchor']  = $this->default_anchor;
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
			<# var default_responsive = '' #>
			<# if ( data.label ) { #>
				<span class="customize-control-title">{{{ data.label }}}</span>
				<# if( data.responsive == true) { #>
					<ul class="responsive-tabs">
						<# var count = 1 #>
						<# Object.keys( data.responsive_tabs ).forEach( function( key ) { #>
							<li><label class="responsive-tab-item" title="{{{data.responsive_tabs[key]['title']}}}"><input type='radio' value='{{{key}}}' name='{{{data.id}}}_responsive' {{{( count == 1)? 'checked="checked"' : '' }}} /><span class="responsive-switcher-{{{key}}}">
							{{{data.responsive_tabs[key]['icon']}}}
							</span></label></li>
						<#
						if( count == 1 )
							default_responsive = key;
						count++;
						}); #>
					</ul>
				<# } #>
				<# if( data.unit_choices.length !== 0 ) { #>
					<ul class="dimension-units">
					<#
					var count = 1;
					var selected_unit = ""
					if( data.responsive == false ) {
						selected_unit = data.value['unit'];
					} else {
						selected_unit = ( data.value[default_responsive] != undefined ) ? data.value[default_responsive]['unit'] : undefined;
					}
					#>
					<# Object.keys( data.unit_choices ).forEach( function( key ) { #>
						<li>
							<label class="dimension-unit-item" title="{{{data.unit_choices[key]}}}">
								<input type='radio' value='{{{key}}}' name='{{{data.id}}}_unit' {{{( ( selected_unit!=undefined && selected_unit==key ) || count == 1)? 'checked="checked"' : '' }}} />
								<span class="unit-switcher">{{{data.unit_choices[key]}}}</span>
							</label>
						</li>
					<#
					count++
					}); #>
					</ul>
				<# } #>
			<# } #>
		</label>

		<div class="dimension-wrapper">
			<# if ( data.description ) { #><span class="description customize-control-description">{{{ data.description }}}</span><#
			} #>
			<div class="dimension-input-wrapper">
				<ul class="dimension-inputs">
					<# var prev_val = null #>
					<# Object.keys( data.inputs ).forEach( function( key ) { #>
						<#
						var value = "";
						if( data.responsive == false ) {
							value = data.value[key];
						}else{
							value = ( data.value[default_responsive] != undefined ) ? data.value[default_responsive][key] : '';
						}
						if( value!=undefined ) {
							if( data.default_anchor && prev_val!=null && prev_val != value ) {
								data.default_anchor = false;
							}
							prev_val = value;
						}

						#>
						<li><input type='{{{data.input_type}}}' name='{{{key}}}' value='{{{value}}}' {{{ data.inputAttrs }}} class='dimension-input' id='{{{data.id}}}-{{{key}}}' /><label for="{{{data.id}}}-{{{key}}}">{{{data.inputs[key]}}}</label></li>
					<# }); #>
					<# if( data.anchor == true ) { #>
						<li>
							<label class="dimension-anchor-wrapper {{{(data.default_anchor==true) ? 'linked' : 'unlinked' }}}">
								<span class="linked-icon">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M17.74 2.76c1.68 1.69 1.68 4.41 0 6.1l-1.53 1.52c-1.12 1.12-2.7 1.47-4.14 1.09l2.62-2.61.76-.77.76-.76c.84-.84.84-2.2 0-3.04-.84-.85-2.2-.85-3.04 0l-.77.76-3.38 3.38c-.37-1.44-.02-3.02 1.1-4.14l1.52-1.53c1.69-1.68 4.42-1.68 6.1 0zM8.59 13.43l5.34-5.34c.42-.42.42-1.1 0-1.52-.44-.43-1.13-.39-1.53 0l-5.33 5.34c-.42.42-.42 1.1 0 1.52.44.43 1.13.39 1.52 0zm-.76 2.29l4.14-4.15c.38 1.44.03 3.02-1.09 4.14l-1.52 1.53c-1.69 1.68-4.41 1.68-6.1 0-1.68-1.68-1.68-4.42 0-6.1l1.53-1.52c1.12-1.12 2.7-1.47 4.14-1.1l-4.14 4.15c-.85.84-.85 2.2 0 3.05.84.84 2.2.84 3.04 0z"/></g></svg>
								</span>
								<span class="unlinked-icon">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M17.74 2.26c1.68 1.69 1.68 4.41 0 6.1l-1.53 1.52c-.32.33-.69.58-1.08.77L13 10l1.69-1.64.76-.77.76-.76c.84-.84.84-2.2 0-3.04-.84-.85-2.2-.85-3.04 0l-.77.76-.76.76L10 7l-.65-2.14c.19-.38.44-.75.77-1.07l1.52-1.53c1.69-1.68 4.42-1.68 6.1 0zM2 4l8 6-6-8zm4-2l4 8-2-8H6zM2 6l8 4-8-2V6zm7.36 7.69L10 13l.74 2.35-1.38 1.39c-1.69 1.68-4.41 1.68-6.1 0-1.68-1.68-1.68-4.42 0-6.1l1.39-1.38L7 10l-.69.64-1.52 1.53c-.85.84-.85 2.2 0 3.04.84.85 2.2.85 3.04 0zM18 16l-8-6 6 8zm-4 2l-4-8 2 8h2zm4-4l-8-4 8 2v2z"/></g></svg>
								</span>
								<input type="checkbox" class='dimension-anchor' {{{(data.default_anchor==true) ? checked='checked' : '' }}} />
							</label>
						</li>
					<# } #>
					<li>
					 <div class="customize-control-content">
						<div class="everest-forms-dimension"></div>
						<div class="everest-forms-dimension-reset">
							<span class="reset dashicons dashicons-image-rotate"></span>
						</div>
					 </div>
					</li>
				</ul>
			</div>
		</div>

		<input class="dimension-hidden-value" type="hidden" {{{ data.link }}} value="{{{data.value}}}">
		<?php
	}

	/**
	 * Gives default responsive tabs
	 *
	 * @return array
	 */
	public function get_default_responsive_tabs() {
		return array(
			'desktop' => array(
				'title' => esc_attr__( 'Desktop', 'everest-forms' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M15,12V2H1V12H6v1H5v1h6V13H10V12ZM2,11V3H14v8Z"/></svg>',
			),
			'tablet'  => array(
				'title' => esc_attr__( 'Tablet', 'everest-forms' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M2,1V15H14V1ZM9,14H7V13H9Zm4-2H3V2H13Z"/></svg>',
			),
			'mobile'  => array(
				'title' => esc_attr__( 'Mobile', 'everest-forms' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M3,1V15H13V1ZM9,14H7V13H9Zm3-2H4V2h8Z"/></svg>',
			),
		);
	}

	/**
	 * Gives default inputs
	 *
	 * @return array
	 */
	public function get_default_inputs() {
		return array(
			'top'    => esc_attr__( 'Top', 'everest-forms' ),
			'right'  => esc_attr__( 'Right', 'everest-forms' ),
			'bottom' => esc_attr__( 'Bottom', 'everest-forms' ),
			'left'   => esc_attr__( 'Left', 'everest-forms' ),
		);
	}
}
