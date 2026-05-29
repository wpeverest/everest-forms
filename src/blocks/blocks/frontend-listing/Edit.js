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
							"everest-forms/v1/gutenberg-blocks/frontend-listing-list",
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
	const frontendOptions = Object.keys(frontendList).map((index) => ({
		value: Number(index),
		label: frontendList[index],
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
						<PanelBody title={__("Everest Forms", "everest-forms")}>
							<SelectControl
								label={__("Select list", "everest-forms")}
								value={id}
								options={[
									{
										label: __(
											"Select list",
											"everest-forms",
										),
										value: "",
									},
									...frontendOptions,
								]}
								onChange={selectList}
							/>
						</PanelBody>
					</InspectorControls>
						<Placeholder
							key="evf-gutenberg-form-selector-wrap"
							icon={EverestFormIcon}
							instructions={__("Everest Form Frontend Listing", "everest-forms")}
							className="everest-form-gutenberg-form-selector-wrap evf-test"
						>
							<SelectControl
								key="evf-gutenberg-form-selector-select-control"
								value={id}
								options={[
									{
										label: __(
											"Select list",
											"everest-forms",
										),
										value: "",
									},
									...frontendOptions,
								]}
								onChange={selectList}
							/>
						</Placeholder>
				</Box>
			</ChakraProvider>
		</>
	);
};

export default Edit;
