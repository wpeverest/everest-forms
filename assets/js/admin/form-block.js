/**
 * Everest Forms Form Block
 *
 * A block for embedding a Everest Forms into a post/page.
 */
( function( blocks, i18n, element, components ) {

	var el = element.createElement, // function to create elements
		TextControl = components.TextControl,// text input control
		InspectorControls = wp.editor.InspectorControls, // sidebar controls
		Sandbox = components.Sandbox; // needed to register the block

	// register our block
	blocks.registerBlockType( 'everest-forms/form-selector', {
		title: 'Everest Forms',
		icon: 'feedback',
		category: 'common',
		attributes: {
            formID: {
                type: 'integer',
                default: 0
            },
		},
		edit: function( props ) {

		},
		save: function( props ) {

            var formID = props.attributes.formID;

            if( ! formID ) return '';
			/**
			 * we're essentially just adding a short code, here is where
			 * it's save in the editor
			 *
			 * return content wrapped in DIV b/c raw HTML is unsupported
			 * going forward
			 */
			var returnHTML = '[everest_forms id=' + parseInt( formID ) + ']';
			return el( 'div', null, returnHTML);
		}
	} );

} )(
	window.wp.blocks,
	window.wp.i18n,
	window.wp.element,
	window.wp.components
);
