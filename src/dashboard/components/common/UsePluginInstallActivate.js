/**
 *  External Dependencies
 */
import React, { useContext } from "react";
import { useToast, Button } from "@chakra-ui/react";
import apiFetch from "@wordpress/api-fetch";
import { sprintf, __ } from "@wordpress/i18n";

/**
 *  Internal Dependencies
 */
import DashboardContext from "./../../context/DashboardContext";
import { actionTypes } from "./../../reducers/DashboardReducer";

const UsePluginInstallActivate = ({
	cancelRef,
	onClose,
	slug,
	isPluginStatusLoading,
	setIsPluginStatusLoading,
}) => {
	const toast = useToast();
	const [{ pluginsStatus }, dispatch] = useContext(DashboardContext);
	/* global _EVF_DASHBOARD_ */
	const { evfRestApiNonce, restURL } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;

	const successCallback = (closeFunction) => {
		if (typeof closeFunction === "function") {
			closeFunction();
		}
	};

	const errorCallback = (closeFunction) => {
		if (typeof closeFunction === "function") {
			closeFunction();
		}
	};

	const activatePlugin = async ({ slug, file }) => {
		setIsPluginStatusLoading(true);
		try {
			const data = await apiFetch({
				path: restURL + `wp/v2/plugins/${slug}`,
				method: "POST",
				headers: {
					"X-WP-Nonce": evfRestApiNonce,
				},
				data: {
					plugin: file.replace(".php", ""),
					status: "active",
				},
			});

			pluginsStatus[`${data.plugin}.php`] = data.status;
			dispatch({
				type: actionTypes.GET_PLUGINS_STATUS,
				pluginsStatus: pluginsStatus,
			});

			toast({
				title: "Success",
				description: sprintf(
					__("%s plugin activated successfully", "everest-forms"),
					data.name
				),
				status: "success",
				duration: 5000,
				isClosable: true,
			});

			successCallback(onClose);
		} catch (e) {
			toast({
				title: "Error",
				description:
					e.message || __("An error occurred", "everest-forms"),
				status: "error",
				duration: 5000,
				isClosable: true,
			});

			errorCallback(onClose);
		} finally {
			setIsPluginStatusLoading(false);
			onClose();
		}
	};

	const installPlugin = async (slug) => {
		setIsPluginStatusLoading(true);

		try {
			const data = await apiFetch({
				path: restURL + "wp/v2/plugins",
				method: "POST",
				headers: {
					"X-WP-Nonce": evfRestApiNonce,
				},
				data: {
					slug: slug,
					status: "active",
				},
			});

			pluginsStatus[`${data.plugin}.php`] = data.status;
			dispatch({
				type: actionTypes.GET_PLUGINS_STATUS,
				pluginsStatus: pluginsStatus,
			});
			toast({
				title: "Success",
				description: sprintf(
					__(
						"%s plugin installed and activated successfully",
						"everest-forms"
					),
					data.name
				),
				status: "success",
				duration: 9000,
				isClosable: true,
			});
			successCallback(onClose);
		} catch (e) {
			toast({
				title: "Error",
				description: e.message || "An error occurred",
				status: "error",
				duration: 9000,
				isClosable: true,
			});
			errorCallback(onClose);
		} finally {
			setIsPluginStatusLoading(false);
		}
		onClose();
	};

	const performPluginAction = (slug) => {
		const pluginSlug = slug.split("/")[0];

		if (pluginsStatus[slug] === "not-installed") {
			installPlugin(pluginSlug);
		} else if (pluginsStatus[slug] === "inactive") {
			activatePlugin({
				slug: pluginSlug,
				file: slug,
			});
		}
	};

	return (
		<>
			<Button
				size="sm"
				fontSize="xs"
				fontWeight="normal"
				variant="outline"
				colorScheme="primary"
				isDisabled={isPluginStatusLoading}
				ref={cancelRef}
				onClick={onClose}
			>
				{__("Cancel", "everest-forms")}
			</Button>
			<Button
				size="sm"
				fontSize="xs"
				fontWeight="normal"
				colorScheme="primary"
				onClick={() => {
					performPluginAction(slug);
				}}
				ml={3}
				isLoading={isPluginStatusLoading}
			>
				{"inactive" === pluginsStatus[slug]
					? __("Activate", "everest-forms")
					: __("Install", "everest-forms")}
			</Button>
		</>
	);
};

export default UsePluginInstallActivate;
