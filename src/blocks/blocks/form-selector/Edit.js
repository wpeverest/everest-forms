import React from "react";
import { Box, ChakraProvider } from "@chakra-ui/react";
import {
	SelectControl,
	ToggleControl,
	PanelBody,
	Placeholder,
} from "@wordpress/components";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import { createElement } from "@wordpress/element";
const ServerSideRender = wp.serverSideRender
	? wp.serverSideRender
	: wp.components.ServerSideRender;
const EverestFormIcon = createElement(
	"svg",
	{ width: 24, height: 24, viewBox: "0 0 24 24" },
	createElement("path", {
		fill: "currentColor",
		d: "M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z",
	}),
);
const Edit = (props) => {
	const useProps = useBlockProps();
	const {
		attributes: { formId, displayTitle, displayDescription },
		setAttributes,
	} = props;
	/* global _EVF_BLOCKS_ */
	const { evfRestApiNonce, restURL, logoUrl } =
		typeof _EVF_BLOCKS_ !== "undefined" && _EVF_BLOCKS_;

	const formOptions = _EVF_BLOCKS_.forms.map((value) => ({
		value: value.ID,
		label: value.post_title,
	}));
	const selectForm = (id) => {
		setAttributes({ formId: id });
	};
	const toggleDisplayTitle = (title) => {
		setAttributes({ displayTitle: title });
	};
	const toggleDisplayDescription = (description) => {
		setAttributes({ displayDescription: description });
	};
	return (
		<>
			<ChakraProvider>
				<Box
					{...useProps}
					maxW="sm"
					borderWidth="1px"
					borderRadius="lg"
					p={2}
				>
					<InspectorControls key="evf-gutenberg-form-selector-inspector-controls">
						<PanelBody title={__("Everest Forms", "everest-forms")}>
							<SelectControl
								label={__("Select a Form", "everest-forms")}
								value={formId}
								options={[
									{
										label: __(
											"Select a Form",
											"everest-forms",
										),
										value: "",
									},
									...formOptions,
								]}
								onChange={selectForm}
							/>
							<ToggleControl
								label={__("Show Title", "everest-forms")}
								checked={displayTitle}
								onChange={toggleDisplayTitle}
							/>
							<ToggleControl
								label={__("Show Description", "everest-forms")}
								checked={displayDescription}
								onChange={toggleDisplayDescription}
							/>
						</PanelBody>
					</InspectorControls>
					{formId ? (
						<ServerSideRender
							key="evf-gutenberg-form-selector-server-side-renderer"
							block="everest-forms/form-selector"
							attributes={props.attributes}
						/>
					) : (
						<Placeholder
							key="evf-gutenberg-form-selector-wrap"
							icon={EverestFormIcon}
							instructions={__("Everest Forms", "everest-forms")}
							className="everest-form-gutenberg-form-selector-wrap evf-test"
						>
							<SelectControl
								key="evf-gutenberg-form-selector-select-control"
								value={formId}
								options={[
									{
										label: __(
											"Select a form",
											"everest-forms",
										),
										value: "",
									},
									...formOptions,
								]}
								onChange={selectForm}
							/>
						</Placeholder>
					)}
				</Box>
			</ChakraProvider>
		</>
	);
};

export default Edit;
