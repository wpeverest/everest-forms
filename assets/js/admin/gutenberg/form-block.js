/**
 * Everest Forms Form Block
 *
 * A block for embedding a Everest Forms into a post/page.
 */

'use strict';
/* global evf_form_block_data, wp */
const { __ } = wp.i18n;
const { createElement } = wp.element;
const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { serverSideRender: ServerSideRender } = wp;
const {
	PanelBody, SelectControl, ToggleControl,TextControl,RadioControl, Placeholder
} = wp.components;

const EverestFormIcon = createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
	createElement( 'path', { fill: 'currentColor', d: 'M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z' } )
);

registerBlockType( 'everest-forms/form-selector', {
	title: evf_form_block_data.i18n.title,
	icon: EverestFormIcon,
	category: 'widgets',
	keywords: evf_form_block_data.i18n.form_keywords,
	description: evf_form_block_data.i18n.description,
	attributes: {
		formId: {
			type: 'string',
		},
		displayTitle: {
			type: 'boolean',
		},
		displayDescription: {
			type: 'boolean',
		},
		displayPopup: {
			type: 'boolean',
		},
		displayPopupType: {
			type: 'string',
		},
		displayPopupText: {
			type: 'string',
		},
		displayPopupSize: {
			type: 'string',
		},
	},
	edit( props ) {
		const { attributes: { formId = '', displayTitle = false, displayDescription = false, displayPopup=false,displayPopupType='',displayPopupText='',displayPopupSize='' }, setAttributes } = props;
		const formOptions = evf_form_block_data.forms.map( value => (
			{ value: value.ID, label: value.post_title }
		) );
		let jsx;

		formOptions.unshift( { value: '', label: evf_form_block_data.i18n.form_select } );

		function selectForm( value ) {
			setAttributes( { formId: value } );
		}

		function toggleDisplayTitle( value ) {
			setAttributes( { displayTitle: value } );
		}

		function toggleDisplayDescription( value ) {
			setAttributes( { displayDescription: value } );
		}
		function toggleDisplayPopup( value ) {
			setAttributes( { displayPopup: value } );
		}
		function selectPopupType(value) {
			setAttributes( { displayPopupType: value } );
		}
		function enterPopupText(value) {
			setAttributes( { displayPopupText: value } );
		}
		function selectPopupSize(value) {
			setAttributes( { displayPopupSize: value } );
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
					<ToggleControl
						label={ evf_form_block_data.i18n.show_title }
						checked={ displayTitle }
						onChange={ toggleDisplayTitle }
					/>
					<ToggleControl
						label={ evf_form_block_data.i18n.show_description }
						checked={ displayDescription }
						onChange={ toggleDisplayDescription }
					/>
					<ToggleControl
						label={ evf_form_block_data.i18n.show_Popup }
						checked={ displayPopup }
						onChange={ toggleDisplayPopup }
					/>
					{displayPopup === true ?
						[<RadioControl
						key="evf-gutenberg-form-selector-radio-control"
						label={ evf_form_block_data.i18n.popup_type }
						options={[
							{
								label: "popup",
								value: "popup",
							},
							{
								label: "button",
								value: "popup-button",
							},
							{
								label: "link",
								value: "popup-link",
							},
						]}
						onChange={selectPopupType}
					/>,
					<RadioControl
						key="evf-size-gutenberg-form-selector-radio-control"
						label={ evf_form_block_data.i18n.popup_size }
						options={[
							{
								label: "Default",
								value: "default",
							},
							{
								label: "Medium",
								value: "medium",
							},
							{
								label: "Large",
								value: "large",
							},
						]}
						onChange={selectPopupSize}
					/>,
					<TextControl
					key="evf-gutenberg-form-selector-text"
					label="Text"
					value={displayPopupText}
					onChange={enterPopupText}
				/>,
			] : ''
				}
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
					icon={ EverestFormIcon }
					instructions={ evf_form_block_data.i18n.title }
					className="everest-form-gutenberg-form-selector-wrap">
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
