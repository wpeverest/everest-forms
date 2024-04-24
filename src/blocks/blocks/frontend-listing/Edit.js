import React,{useState, useEffect} from "react";
import { Box, ChakraProvider } from "@chakra-ui/react";
import apiFetch from "@wordpress/api-fetch";
import {
	SelectControl,
	PanelBody,
	Placeholder,
} from "@wordpress/components";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
const ServerSideRender = wp.serverSideRender
	? wp.serverSideRender
	: wp.components.ServerSideRender;

import { EverestFormIcon } from "../../components/Icon";

const Edit = (props) => {
	const useProps = useBlockProps();
	const {
		attributes: { id },
		setAttributes,
	} = props;
	/* global _EVF_BLOCKS_ */
	const { evfRestApiNonce, restURL } =
		typeof _EVF_BLOCKS_ !== "undefined" && _EVF_BLOCKS_;
	const [frontendList, setFrontendList] = useState("");

	useEffect(() => {
		const fetchData = async () => {
			if (!frontendList) {
				try {
					const res = await apiFetch({
						path:
							restURL +
							"everest-form/v1/gutenberg-blocks/fronend-listing-list",
						method: "GET",
						headers: {
							"X-WP-Nonce": evfRestApiNonce,
						},
					});
					if (res.success) {
						setFrontendList(res.frontend_lists);
					}
				} catch (error) {
					console.error("Error fetching data:", error);
				}
			}
		};

		fetchData();
	}, []);
	const formOptions =  Object.keys(frontendList).map((value) => ({
		value: value.ID,
		label: value.post_title,
	}));
	const selectList = (id) => {
		setAttributes({ id: id });
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
						<PanelBody title={__("Everest Forms Frontend Listing", "everest-forms")}>
							<SelectControl
								label={__("Select a Frontend List", "everest-forms")}
								value={id}
								options={[
									{
										label: __(
											"Select a list",
											"everest-forms",
										),
										value: "",
									},
									...formOptions,
								]}
								onChange={selectList}
							/>
						</PanelBody>
					</InspectorControls>
					{id ? (
						<ServerSideRender
							key="evf-gutenberg-form-selector-server-side-renderer"
							block="everest-forms/form-selector"
							attributes={props.attributes}
						/>
					) : (
						<Placeholder
							key="evf-gutenberg-form-selector-wrap"
							icon={EverestFormIcon}
							instructions={__("Everest Forms Fronend Listing", "everest-forms")}
							className="everest-form-gutenberg-form-selector-wrap evf-test"
						>
							<SelectControl
								key="evf-gutenberg-form-selector-select-control"
								value={id}
								options={[
									{
										label: __(
											"Select a list",
											"everest-forms",
										),
										value: "",
									},
									...formOptions,
								]}
								onChange={selectList}
							/>
						</Placeholder>
					)}
				</Box>
			</ChakraProvider>
		</>
	);
};

export default Edit;
