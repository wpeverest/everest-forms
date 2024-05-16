import React from "react";
import { Box, ChakraProvider } from "@chakra-ui/react";
import { ToggleControl, PanelBody, TextControl } from "@wordpress/components";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import metadata from "./block.json";

const ServerSideRender = wp.serverSideRender
	? wp.serverSideRender
	: wp.components.ServerSideRender;

const Edit = (props) => {
	const useProps = useBlockProps();
	const {
		attributes: { redirect_url, recaptcha },
		setAttributes,
	} = props;
	const blockName = metadata.name;

	const setRedirectUrl = (url) => {
		setAttributes({ redirect_url: url });
	};

	const setRecaptcha = (recaptcha) => {
		setAttributes({ recaptcha: recaptcha });
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
						<PanelBody
							title={__(
								"Everest Forms User Login",
								"everest-forms",
							)}
						>
							<TextControl
								key="evf-gutenberg-user-login-redirect-url"
								label={__("Redirect URL", "user-registration")}
								value={redirect_url}
								onChange={setRedirectUrl}
							/>
							<ToggleControl
								key="evf-gutenberg-user-login-recaptcha"
								label={__("Enable Recaptcha", "user-registration")}
								checked={recaptcha}
								onChange={setRecaptcha}
							/>
						</PanelBody>
					</InspectorControls>

					<ServerSideRender
						key="evf-gutenberg-user-login-server-side-renderer"
						block={blockName}
						attributes={props.attributes}
					/>
				</Box>
			</ChakraProvider>
		</>
	);
};

export default Edit;
