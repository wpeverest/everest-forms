/**
 * External Dependencies
 */
import {
	Badge,
	Box,
	Button,
	Flex,
	Heading,
	HStack,
	Icon,
	IconButton,
	Link,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalHeader,
	ModalOverlay,
	Spinner,
	Switch,
	Text,
	useDisclosure,
	VStack
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "react";
import { FaCog, FaPlay } from "react-icons/fa";
import ReactPlayer from "react-player";
import { activateModule, deactivateModule } from "./modules-api";

const AddonCard = ({ addon, showToast }) => {
	const [isActive, setIsActive] = useState(addon.status === "active");
	const [isLoading, setIsLoading] = useState(false);
	const [moduleEnabled, setModuleEnabled] = useState(false);
	const [videoLoading, setVideoLoading] = useState(false);
	const {
		isOpen: isVideoOpen,
		onOpen: onVideoOpen,
		onClose: onVideoClose
	} = useDisclosure();

	// Get global variables from Everest Forms
	const { isPro, licensePlan } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_
			? _EVF_DASHBOARD_
			: {};

	// Get assets URL from global variable
	const getImageUrl = (imagePath) => {
		const { assetsURL } =
			typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
		if (imagePath && assetsURL) {
			return assetsURL + imagePath;
		}
		return imagePath;
	};

	// Check if module is enabled based on plan requirements
	useEffect(() => {
		// If no plan array or empty, assume it's a free module
		if (!addon.plan || addon.plan.length === 0) {
			setModuleEnabled(true);
			return;
		}

		// Check if module has "free" in plan array
		if (Array.isArray(addon.plan) && addon.plan.includes("free")) {
			setModuleEnabled(true);
			return;
		}

		// If user has any paid plan (isPro is true), enable the module
		// Everest Forms uses: personal, agency, themegrill agency
		if (isPro) {
			setModuleEnabled(true);
			return;
		}

		// User is on free plan but module requires paid plan
		setModuleEnabled(false);
	}, [addon.plan, isPro]);

	const handleUpgradePlan = () => {
		const { upgradeURL } =
			typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
		if (upgradeURL) {
			const plan_upgrade_url =
				upgradeURL +
				"?utm_source=dashboard-modules&utm_medium=upgrade-button&utm_campaign=" +
				addon.slug;
			window.open(plan_upgrade_url, "_blank");
		}
	};

	const handleVideoPlay = () => {
		setVideoLoading(true);
		onVideoOpen();
	};

	const handleToggle = async () => {
		setIsLoading(true);
		try {
			let response;
			if (isActive) {
				response = await deactivateModule(addon.slug, addon.type);
				if (response.success) {
					setIsActive(false);
					showToast(
						response.message || __("Module deactivated successfully", "everest-forms"),
						"success"
					);
				} else {
					showToast(
						response.message || __("Failed to deactivate module", "everest-forms"),
						"error"
					);
				}
			} else {
				response = await activateModule(
					addon.slug,
					addon.name,
					addon.type
				);
				if (response.success) {
					setIsActive(true);
					showToast(
						response.message || __("Module activated successfully", "everest-forms"),
						"success"
					);
				} else {
					showToast(
						response.message || __("Failed to activate module", "everest-forms"),
						"error"
					);
				}
			}
		} catch (error) {
			showToast(
				error.message || __("An error occurred", "everest-forms"),
				"error"
			);
		}
		setIsLoading(false);
	};

	const getPlanBadge = (plan) => {
		// If no plan, it's free
		if (!plan || plan.length === 0) {
			return "Free";
		}

		// Check for free in array
		if (Array.isArray(plan) && plan.includes("free")) {
			return "Free";
		}

		// For Everest Forms, paid plans are: personal, agency, themegrill agency
		// Show "PRO" for all paid plans
		return "PRO";
	};

	const getPlanBadgeStyles = (plan) => {
		const badge = getPlanBadge(plan);

		if (badge === "Free") {
			return {
				bg: "transparent",
				border: "1px solid #D1D5DB",
				color: "#6B7280",
				fontSize: "11px",
				fontWeight: "500"
			};
		}

		// PRO badge - blue style
		return {
			bg: "#EFF6FF",
			border: "1px solid #93C5FD",
			color: "#3B82F6",
			fontSize: "11px",
			fontWeight: "600"
		};
	};

	const badgeStyles = getPlanBadgeStyles(addon.plan);

	return (
		<Box
			bg="white"
			borderRadius="lg"
			border="1px solid"
			borderColor="gray.200"
			p="5"
			boxShadow="sm"
			_hover={{
				boxShadow: "md",
				borderColor: "gray.300"
			}}
			transition="all 0.2s"
			position="relative"
			height="100%"
			display="flex"
			flexDirection="column"
		>
			{/* Loading Overlay */}
			{isLoading && (
				<Flex
					position="absolute"
					top="0"
					left="0"
					right="0"
					bottom="0"
					bg="rgba(255, 255, 255, 0.9)"
					borderRadius="lg"
					alignItems="center"
					justifyContent="center"
					zIndex="10"
				>
					<Spinner size="lg" color="#475bb2" thickness="3px" />
				</Flex>
			)}

			{/* Main Content Layout */}
			<HStack align="start" spacing="3" mb="4">
				{/* Left Side - Icon */}
				<Box
					w="48px"
					h="48px"
					bg="white"
					borderRadius="lg"
					display="flex"
					alignItems="center"
					justifyContent="center"
					border="1px solid"
					borderColor="gray.200"
					flexShrink={0}
					overflow="hidden"
					p="2"
				>
					{addon.image ? (
						<img
							src={getImageUrl(addon.image)}
							alt={addon.title}
							style={{
								width: "100%",
								height: "100%",
								objectFit: "contain"
							}}
							onError={(e) => {
								e.target.style.display = "none";
								if (e.target.nextSibling) {
									e.target.nextSibling.style.display = "flex";
								}
							}}
						/>
					) : null}
					<Box
						display={addon.image ? "none" : "flex"}
						alignItems="center"
						justifyContent="center"
						fontSize="2xl"
						width="100%"
						height="100%"
						color="gray.400"
					>
						📋
					</Box>
				</Box>

				{/* Right Side - Title and Badge */}
				<VStack align="start" spacing="2" flex="1">
					<HStack justify="space-between" w="full" align="start">
						<Heading
							size="sm"
							color="gray.900"
							fontWeight="600"
							fontSize="15px"
							lineHeight="1.4"
						>
							{addon.title}
						</Heading>
						<Badge
							fontSize={badgeStyles.fontSize}
							px="2"
							py="0.5"
							borderRadius="md"
							bg={badgeStyles.bg}
							border={badgeStyles.border}
							color={badgeStyles.color}
							fontWeight={badgeStyles.fontWeight}
							flexShrink={0}
							textTransform="uppercase"
						>
							{getPlanBadge(addon.plan)}
						</Badge>
					</HStack>

					{/* Description */}
					<Text
						fontSize="13px"
						color="gray.600"
						lineHeight="1.5"
						noOfLines={2}
					>
						{addon.excerpt || "No description available."}
					</Text>
				</VStack>
			</HStack>

			{/* Footer Section */}
			<HStack justify="space-between" align="center" mt="auto" pt="3" borderTop="1px solid" borderColor="gray.100">
				<HStack spacing="2" fontSize="13px">
					{addon.link && (
						<Link
							href={addon.link}
							color="gray.600"
							textDecoration="none"
							isExternal
							_hover={{ color: "#475bb2", textDecoration: "underline" }}
							fontWeight="500"
						>
							{__("Docs", "everest-forms")}
						</Link>
					)}
					{addon.demo_video_url && (
						<>
							<Text color="gray.300">|</Text>
							<IconButton
								size="xs"
								icon={<Icon as={FaPlay} />}
								aria-label={__("Video Tutorial", "everest-forms")}
								variant="ghost"
								color="gray.600"
								_hover={{ color: "#475bb2", bg: "gray.50" }}
								onClick={handleVideoPlay}
							/>
						</>
					)}
					{addon.setting_url && isActive && (
						<>
							<Text color="gray.300">|</Text>
							<IconButton
								size="xs"
								icon={<FaCog />}
								aria-label={__("Settings", "everest-forms")}
								variant="ghost"
								color="gray.600"
								_hover={{ color: "#475bb2", bg: "gray.50" }}
								onClick={() => window.open(addon.setting_url, "_self")}
							/>
						</>
					)}
				</HStack>
				<HStack spacing="2">
					{moduleEnabled ? (
						<Switch
							isChecked={isActive}
							onChange={handleToggle}
							isDisabled={isLoading}
							size="md"
							sx={{
								"& .chakra-switch__track": {
									bg: isActive ? "#475bb2" : "gray.300"
								},
								"& .chakra-switch__track[data-checked]": {
									bg: "#475bb2"
								}
							}}
						/>
					) : (
						<Button
							size="sm"
							fontSize="13px"
							fontWeight="600"
							bg="#475bb2"
							color="white"
							borderColor="#475bb2"
							px="4"
							h="32px"
							_hover={{
								bg: "#3a4a8f",
								borderColor: "#3a4a8f"
							}}
							_active={{
								bg: "#2d3b70"
							}}
							onClick={handleUpgradePlan}
						>
							{__("Upgrade Plan", "everest-forms")}
						</Button>
					)}
				</HStack>
			</HStack>

			{/* YouTube Video Modal */}
			{isVideoOpen && addon.demo_video_url && (
				<Modal
					isOpen={isVideoOpen}
					onClose={onVideoClose}
					size="3xl"
					isCentered
				>
					<ModalOverlay bg="blackAlpha.700" />
					<ModalContent mx="4">
						<ModalHeader textAlign="center" fontSize="lg" fontWeight="600">
							{addon.title}
						</ModalHeader>
						<ModalCloseButton />
						<ModalBody pb="6">
							<Box position="relative" paddingTop="56.25%" bg="gray.100" borderRadius="md" overflow="hidden">
								<ReactPlayer
									url={`https://www.youtube.com/watch?v=${addon.demo_video_url}`}
									playing={true}
									width="100%"
									height="100%"
									controls
									style={{
										position: "absolute",
										top: 0,
										left: 0
									}}
									onReady={() => setVideoLoading(false)}
									onStart={() => setVideoLoading(false)}
								/>
								{videoLoading && (
									<Box
										position="absolute"
										top="50%"
										left="50%"
										transform="translate(-50%, -50%)"
									>
										<Spinner size="lg" color="#475bb2" />
									</Box>
								)}
							</Box>
						</ModalBody>
					</ModalContent>
				</Modal>
			)}
		</Box>
	);
};

export default AddonCard;
