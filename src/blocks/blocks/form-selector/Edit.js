import React from "react";
import {
	Image,
	Box,
	Heading,
	Center,
	Card,
	CardBody,
	Text,
	Stack,
	Container
} from "@chakra-ui/react";
import { ChakraProvider } from "@chakra-ui/react";
import {
	SelectControl,
	ToggleControl,
	PanelBody,
	Placeholder,
} from "@wordpress/components";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";
import { EverestForm, ContactForm } from "./../../components/Icon";
import { createElement } from "@wordpress/element";
const EverestFormIcon = createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
	createElement( 'path', { fill: 'currentColor', d: 'M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z' } )
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
		<ChakraProvider>
			<Box {...useProps} maxW="sm" borderWidth="1px" borderRadius="lg" p={2}>
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
				<Card>
					<CardBody>
						<Center>
							<Heading as="h3" ml={5}>
								{__("Everest Forms", "everest-forms")}
							</Heading>
						</Center>
						<Center>
							<Stack spacing="3">
								<Text fontSize="sm" as="i">
									{__(
										"Select a form name to display one of your form.",
										"everest-forms",
									)}
								</Text>
							</Stack>
						</Center>
						<Center>
							<Box w="sm" m="4">
								<SelectControl
									key="evf-gutenberg-everest-form-selector-select-control"
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
							</Box>
						</Center>
					</CardBody>
				</Card>
			</Box>
		</ChakraProvider>
	);
};

export default Edit;
