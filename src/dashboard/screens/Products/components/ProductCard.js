/**
 *  External Dependencies
 */
import React, { useRef, useState, useEffect, useContext } from "react";
import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Box,
	Button,
	Divider,
	Heading,
	HStack,
	Image,
	Link,
	Stack,
	Text,
	useDisclosure,
} from "@chakra-ui/react";
import { sprintf, __ } from "@wordpress/i18n";

/**
 *  Internal Dependencies
 */
import UsePluginInstallActivate from "../../../components/common/UsePluginInstallActivate";
import DashboardContext from "../../../context/DashboardContext";

const ProductCard = (props) => {
	/* global _EVF_DASHBOARD_ */
	const { adminURL } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;

	const [{ pluginsStatus, themesStatus }, dispatch] =
		useContext(DashboardContext);
	const { label, description, image, website, slug, type, liveDemoURL } =
		props;
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [status, setStatus] = useState("inactive");
	const [isPluginStatusLoading, setIsPluginStatusLoading] = useState(false);

	useEffect(() => {
		const status =
			type === "theme" ? themesStatus[slug] : pluginsStatus[slug];
		setStatus(status);
	}, [pluginsStatus[slug], themesStatus[slug]]);
	const cancelRef = useRef();
	return (
		<>
			<Box
				overflow="hidden"
				boxShadow="none"
				border="1px"
				borderRadius="base"
				borderColor="gray.100"
				display="flex"
				flexDir="column"
			>
				<Box p="0" flex="1 1 0%">
					<Image w={283} h={132} src={image} />
					<Stack gap="2" px="4" py="5">
						<Heading
							as="h3"
							size="md"
							m="0"
							fontSize="md"
							fontWeight="semibold"
						>
							{label}
						</Heading>
						<Text m="0" color="gray.600" fontSize="13px">
							{description}
						</Text>
					</Stack>
				</Box>
				<Divider color="gray.300" />
				<Box
					px="4"
					py="5"
					justifyContent="space-between"
					alignItems="center"
					display="flex"
				>
					<HStack gap="1" align="center">
						<Link
							href={website}
							fontSize="xs"
							color="gray.500"
							textDecoration="underline"
							isExternal
						>
							{__("Learn More", "everest-forms")}
						</Link>
						<Text as="span" lineHeight="1" color="gray.500">
							|
						</Text>
						<Link
							href={liveDemoURL}
							fontSize="xs"
							color="gray.500"
							textDecoration="underline"
							isExternal
						>
							{__("Live Demo", "everest-forms")}
						</Link>
					</HStack>
					<Button
						colorScheme="primary"
						size="sm"
						fontSize="xs"
						borderRadius="base"
						fontWeight="semibold"
						_hover={{
							color: "white",
							textDecoration: "none",
						}}
						_focus={{
							color: "white",
							textDecoration: "none",
						}}
						isDisabled={"active" === status}
						as={"theme" === type ? Link : undefined}
						href={
							"theme" === type
								? "inactive" === status
									? `${adminURL}themes.php?search=${slug}`
									: `${adminURL}/theme-install.php?search=${slug}`
								: undefined
						}
						onClick={"plugin" === type ? onOpen : undefined}
						isLoading={
							"plugin" === type
								? isPluginStatusLoading
								: undefined
						}
					>
						{"active" === status
							? __("Activated", "everest-forms")
							: "inactive" === status
							? __("Activate", "everest-forms")
							: __("Install", "everest-forms")}
					</Button>
				</Box>
			</Box>
			{type === "plugin" && (
				<AlertDialog
					isOpen={isOpen}
					leastDestructiveRef={cancelRef}
					onClose={onClose}
					isCentered
				>
					<AlertDialogOverlay>
						<AlertDialogContent>
							<AlertDialogHeader
								fontSize="lg"
								fontWeight="semibold"
							>
								{"inactive" === pluginsStatus[slug]
									? __("Activate Plugin", "everest-forms")
									: __("Install Plugin", "everest-forms")}
							</AlertDialogHeader>
							<AlertDialogBody>
								{"inactive" === pluginsStatus[slug]
									? sprintf(
											__(
												"Are you sure? You want to activate %s plugin.",
												"everest-forms"
											),
											label
									  )
									: sprintf(
											__(
												"Are you sure? You want to install and activate %s plugin.",
												"everest-forms"
											),
											label
									  )}
							</AlertDialogBody>
							<AlertDialogFooter>
								<UsePluginInstallActivate
									cancelRef={cancelRef}
									onClose={onClose}
									slug={slug}
									isPluginStatusLoading={
										isPluginStatusLoading
									}
									setIsPluginStatusLoading={
										setIsPluginStatusLoading
									}
								/>
							</AlertDialogFooter>
						</AlertDialogContent>
					</AlertDialogOverlay>
				</AlertDialog>
			)}
		</>
	);
};

export default ProductCard;
