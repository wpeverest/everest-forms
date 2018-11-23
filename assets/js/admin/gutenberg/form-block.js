/**
 * Everest Forms Form Block
 *
 * A block for embedding a Everest Forms into a post/page.
 */

'use strict';

/* global evf_form_block_data, wp */
const { createElement } = wp.element;
const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.editor;
const { SelectControl, ToggleControl, PanelBody, ServerSideRender, Placeholder } = wp.components;

const everestFormIcon = createElement( 'svg', { width: 20, height: 20, viewBox: '0 0 20 20', className: 'dashicon' },
	createElement( 'path', { fill: 'currentColor', d: 'M4.5 0v3H0v17h20V0H4.5zM9 19H1V4h8v15zm10 0h-9V3H5.5V1H19v18zM6.5 6h-4V5h4v1zm1 2v1h-5V8h5zm-5 3h3v1h-3v-1z' } )
);

registerBlockType( 'everest-forms/form-selector', {
	title: evf_form_block_data.i18n.title,
	description: evf_form_block_data.i18n.description,
	icon: everestFormIcon,
	category: 'widgets',
	attributes: {
		formId: {
			type: 'string',
		},
	},
	edit( props ) {
		const { attributes: { formId = '' }, setAttributes } = props;
		const formOptions = evf_form_block_data.forms.map( value => (
			{ value: value.ID, label: value.post_title }
		) );
		let jsx;

		formOptions.unshift( { value: '', label: evf_form_block_data.i18n.form_select } );

		function selectForm( value ) {
			setAttributes( { formId: value } );
		}

		jsx = [
			<InspectorControls key="evf-gutenberg-form-selector-inspector-controls">
				<PanelBody title={ evf_form_block_data.i18n.form_settings }>
					<SelectControl
						label={ evf_form_block_data.i18n.form_selected }
						value={ formId }
						options={ formOptions }
						onChange={ selectForm }
					/>
				</PanelBody>
			</InspectorControls>
		];

		if ( formId ) {
			jsx.push(
				<ServerSideRender
					key="evf-gutenberg-form-selector-server-side-renderer"
					block="everest-forms/form-selector"
					attributes={ props.attributes }
				/>
			);
		} else {
			jsx.push(
				<Placeholder
					key="evf-gutenberg-form-selector-wrap"
					className="evf-gutenberg-form-selector-wrap">
					<img src={ evf_form_block_data.logo_url }/>
					<h2>{ evf_form_block_data.i18n.title }</h2>
					<SelectControl
						key="evf-gutenberg-form-selector-select-control"
						value={ formId }
						options={ formOptions }
						onChange={ selectForm }
					/>
				</Placeholder>
			);
		}

		return jsx;
	},
	save() {
		return null;
	},
} );
