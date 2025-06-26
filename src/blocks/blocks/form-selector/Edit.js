import React from "react";
import { Box, ChakraProvider, Link, Popover, PopoverArrow, PopoverBody, PopoverContent, PopoverTrigger, Tooltip } from "@chakra-ui/react";
import {
	SelectControl,
	ToggleControl,
	PanelBody,
	Placeholder,
	TextControl,
	TextHighlight,
	TextareaControl,
} from "@wordpress/components";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import { EverestFormIcon } from "../../components/Icon";
const ServerSideRender = wp.serverSideRender
	? wp.serverSideRender
	: wp.components.ServerSideRender;
const Edit = (props) => {
	const useProps = useBlockProps();
	const {
		attributes: {
			formId,
			displayTitle,
			displayDescription,
			popupType,
			popupButtonText,
			popupSize,
			popupHeaderTitle,
			popupFooterTitle,
			popupHeaderDesc,
			popupFooterDesc,
		},
		setAttributes,
	} = props;
	/* global _EVF_BLOCKS_ */
	const { evfRestApiNonce, restURL, logoUrl, isPro } =
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

	const textMap = {
		"popup-link": __("Popup Link Text", "everest-forms"),
		"popup-button": __("Popup Button Text", "everest-forms"),
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

							<Box
								opacity={ isPro && popupType !== 'none' ? 0.5 : 1}
								cursor={ isPro && popupType !== 'none' ? "not-allowed" : "pointer"}
							>
								<ToggleControl
									label={__("Show Title", "everest-forms")}
									checked={displayTitle}
									onChange={toggleDisplayTitle}
									disabled={ isPro && popupType !== 'none' ? true : false }
								/>
								<ToggleControl
									label={__("Show Description", "everest-forms")}
									checked={displayDescription}
									onChange={toggleDisplayDescription}
									disabled={ isPro && popupType !== 'none' ? true : false }
								/>
							</Box>

							{ ! isPro ? (
							<Popover trigger="hover" openDelay={300} closeDelay={300} placement="bottom">
									<PopoverTrigger>
										<Box
										marginTop={"16px"}
										>
											<SelectControl
											opacity={ "0.5" }
											cursor={ "not-allowed" }
												label={
													<span className="evf-popup-type-premium-label">
													{__("Popup Type", "everest-forms")}
													</span>
												}
												value={popupType}
												options={[
													{ label: __("None", "everest-forms"), value: "none" },
													{ label: __("Link", "everest-forms"), value: "popup-link" },
													{ label: __("Button", "everest-forms"), value: "popup-button" },
													{ label: __("Popup", "everest-forms"), value: "popup" },
												]}
												onChange={(type) => setAttributes({ popupType: type })}
												disabled={!isPro}
											/>
										</Box>
									</PopoverTrigger>

								<PopoverContent
									_focus={{ boxShadow: "0px 4px 50px 0px rgba(0, 0, 0, 0.16)" }}
									maxWidth={"248px"}
									boxShadow={"0px 4px 50px 0px rgba(0, 0, 0, 0.16)"}
								>
									<PopoverArrow bg="#ffff" />
									<PopoverBody
										fontSize={"14px"}
										fontWeight={"400"}
										color={"#444"}
										fontStyle={"normal"}
										lineHeight={"150%"}
									>
									This feature requires the Premium version.
									<Link
										href="https://everestforms.net/upgrade/?utm_medium=editor-evf-block&utm_source=evf-free&utm_campaign=inside-editor&utm_content=Upgrade%20Now"
										target="_blank"
										rel="noopener noreferrer"
										color="#754bb2"
										textDecoration="underline"
										fontWeight={"600"}
										marginLeft={"8px"}
										opacity={"0.9"}
										lineHeight={"150%"}
										textDecorationLine={"underline"}
										textDecorationStyle={"solid"}
									>
										Upgrade Now
									</Link>
									</PopoverBody>
								</PopoverContent>
								</Popover>
								) : (
								<SelectControl
									label={__("Popup Type", "everest-forms")}
									value={popupType}
									options={[
									{ label: __("None", "everest-forms"), value: "none" },
									{ label: __("Link", "everest-forms"), value: "popup-link" },
									{ label: __("Button", "everest-forms"), value: "popup-button" },
									{ label: __("Popup", "everest-forms"), value: "popup" },
									]}
									onChange={(type) => setAttributes( { popupType: type } ) }
									disabled={!isPro}
								/>
								)}

							{"none" !== popupType && isPro && (
								<>
									{"popup" !== popupType && (
										<TextControl
											label={__(
												textMap[popupType],
												"everest-forms",
											)}
											value={popupButtonText}
											onChange={(value) => {
												setAttributes({
													popupButtonText: value,
												});
											}}
										/>
									)}

									<SelectControl
										label={__(
											"Popup Size",
											"everest-forms",
										)}
										value={popupSize}
										options={[
											{
												label: __(
													"Default",
													"everest-forms",
												),
												value: "default",
											},
											{
												label: __(
													"Medium",
													"everest-forms",
												),
												value: "medium",
											},
											{
												label: __(
													"Large",
													"everest-forms",
												),
												value: "large",
											},
										]}
										onChange={(size) =>
											setAttributes({ popupSize: size })
										}
									/>
									<TextControl
										label={__(
											"Popup Header title",
											"everest-forms",
										)}
										value={popupHeaderTitle}
										onChange={(value) => {
											setAttributes({
												popupHeaderTitle: value,
											});
										}}
									/>
									<TextareaControl
										label={__(
											"Popup Header Description",
											"everest-forms",
										)}
										value={popupHeaderDesc}
										onChange={(value) => {
											setAttributes({
												popupHeaderDesc: value,
											});
										}}
									/>
									<TextControl
										label={__(
											"Popup Footer title",
											"everest-forms",
										)}
										value={popupFooterTitle}
										onChange={(value) => {
											setAttributes({
												popupFooterTitle: value,
											});
										}}
									/>
									<TextareaControl
										label={__(
											"Popup Footer Description",
											"everest-forms",
										)}
										value={popupFooterDesc}
										onChange={(value) => {
											setAttributes({
												popupFooterDesc: value,
											});
										}}
									/>
									<p>
										{__(
											"For the custom design of the Form, Popup Button or Link. ",
											"everest-forms",
										)}
										<a
											href="https://docs.everestforms.net/docs/style-customizer/"
											target="_blank"
											rel="noopener noreferrer"
											style={{
												color: "blue",
												textDecoration: "none",
												transition: "none",
											}}
											onMouseEnter={(e) =>
												(e.currentTarget.style.textDecoration =
													"underline")
											}
											onMouseLeave={(e) =>
												(e.currentTarget.style.textDecoration =
													"none")
											}
										>
											{__("Here", "everest-forms")}
										</a>
									</p>
								</>
							)}
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
