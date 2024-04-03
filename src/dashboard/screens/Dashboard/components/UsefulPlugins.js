/**
 *  External Dependencies
 */
import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Button,
	Grid,
	Heading,
	HStack,
	Stack,
	Text,
	useDisclosure,
} from "@chakra-ui/react";
import { sprintf, __ } from "@wordpress/i18n";
import React, { useRef, useState, useEffect, useContext } from "react";

/**
 *  Internal Dependencies
 */
import { PLUGINS } from "./../../../Constants/Products";
import DashboardContext, {
	DashboardProvider,
} from "./../../../context/DashboardContext";
import UsePluginInstallActivate from "./../../../components/common/UsePluginInstallActivate";

const Plugin = ({ plugin, index }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const cancelRef = useRef();
	const [isPluginStatusLoading, setIsPluginStatusLoading] = useState(false);
	const [status, setStatus] = useState("inactive");
	const [{ pluginsStatus, themesStatus }, dispatch] =
		useContext(DashboardContext);

	useEffect(() => {
		const status =
			plugin.type === "theme"
				? themesStatus[plugin.slug]
				: pluginsStatus[plugin.slug];
		setStatus(status);
	}, [pluginsStatus[plugin.slug], themesStatus[plugin.slug]]);
	return (
		<HStack
			key={plugin.slug}
			gap="4px"
			justify="space-between"
			px="16px"
			py="18px"
			pl={index % 2 === 0 ? "0" : undefined}
		>
			<HStack>
				<plugin.logo w="40px" h="40px" />
				<Stack gap="6px">
					<Heading as="h4" fontSize="14px" fontWeight="semibold">
						{plugin.label}
					</Heading>
					<Text as="span" color="gray.500">
						{plugin.shortDescription}
					</Text>
				</Stack>
			</HStack>
			<Button
				variant="link"
				colorScheme="primary"
				color="var(--chakra-colors-primary-500) !important"
				fontSize="14px"
				fontWeight="normal"
				textDecor="underline"
				isLoading={isPluginStatusLoading}
				isDisabled={"active" === status}
				onClick={onOpen}
			>
				{status === "active"
					? __("Active", "everest-forms")
					: status === "inactive"
					? __("Activate", "everest-forms")
					: __("Install", "everest-forms")}
			</Button>
			<AlertDialog
				isOpen={isOpen}
				leastDestructiveRef={cancelRef}
				onClose={onClose}
				isCentered
			>
				<AlertDialogOverlay>
					<AlertDialogContent>
						<AlertDialogHeader fontSize="lg" fontWeight="semibold">
							{"inactive" === status
								? __("Activate Plugin", "everest-forms")
								: __("Install Plugin", "everest-forms")}
						</AlertDialogHeader>
						<AlertDialogBody>
							{"inactive" === status
								? sprintf(
										__(
											"Are you sure? You want to activate %s plugin.",
											"everest-forms"
										),
										plugin.label
								  )
								: sprintf(
										__(
											"Are you sure? You want to install and activate %s plugin.",
											"everest-forms"
										),
										plugin.label
								  )}
						</AlertDialogBody>
						<AlertDialogFooter>
							<UsePluginInstallActivate
								cancelRef={cancelRef}
								onClose={onClose}
								slug={plugin.slug}
								isPluginStatusLoading={isPluginStatusLoading}
								setIsPluginStatusLoading={
									setIsPluginStatusLoading
								}
							/>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
		</HStack>
	);
};

const UsefulPlugins = () => {
	return (
		<Grid
			gridTemplateColumns="1fr 1fr"
			sx={{
				"> div:nth-child(2n+1)": {
					borderRight: "1px",
					borderColor: "gray.100",
				},
				"> div:first-child, > div:nth-child(2)": {
					borderBottom: "1px",
					borderColor: "gray.100",
				},
			}}
		>
			<DashboardProvider>
				{PLUGINS.map((plugin, i) => (
					<Plugin key={plugin.slug} plugin={plugin} index={i} />
				))}
			</DashboardProvider>
		</Grid>
	);
};

export default UsefulPlugins;
