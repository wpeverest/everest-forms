/**
 * Everest Forms Form Block
 *
 * A block for embedding a Everest Forms into a post/page.
 */

/* global evf_form_block_data, wp */
( function( blocks, i18n, element, components ) {

	var el = element.createElement, // function to create elements
        SelectControl = components.SelectControl, // select control
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
            var focus = props.focus;
            var formID = props.attributes.formID;
            var children = [];

            if ( ! formID ) {
                formID = ''; // Default.
            }

            function onFormChange( newFormID ) {
                // updates the form id on the props
                props.setAttributes( { formID: newFormID } );
            }

            // Set up the form dropdown in the side bar 'block' settings
            var inspectorControls = el( InspectorControls, {},
                el( SelectControl,
                    {
                        label: i18n.__( 'Selected Form' ),
                        value: formID,
                        options: evf_form_block_data.forms,
                        onChange: onFormChange
                    }
                )
            );

            /**
             * Create the div container, add an overlay so the user can interact
             * with the form in Gutenberg, then render the iframe with form
             */
            if ( '' === formID ) {
                children.push(
                    el( 'div', { style : {width: '100%' } },
                    el( 'img',{ src: 'weformsblock.block_logo' }),
                    el( 'h3', { className : 'weforms-title' }, 'weForms' ),
                    el( SelectControl, { value: formID, options: weformsblock.forms, onChange: onFormChange })
                ) );
            } else {
                children.push(
                    el( 'div', { className: 'weforms-form-container' },
                        el( 'div', { className: 'weforms-form-overlay'} ),
                        el( 'iframe', { src: evf_form_block_data.siteUrl + '?evf_preview=1&form_id=' + formID, height: '0', width: '500', scrolling: 'no' })
                    )
                )
            }

            return [
                children,
                !! focus && inspectorControls
            ];
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
