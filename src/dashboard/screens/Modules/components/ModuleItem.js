/**
 *  External Dependencies
 */
import {
	Badge,
	Box,
	Checkbox,
	Heading,
	Image,
	Stack,
	Text,
	useToast,
	Link,
	Button,
	Divider,
	HStack,
	Switch,
	IconButton,
	Icon
} from "@chakra-ui/react";
import { SettingsIcon } from "@chakra-ui/icons";
import { __ } from "@wordpress/i18n";
import React, { useState, useEffect, useContext } from "react";

/**
 *  Internal Dependencies
 */
import { activateModule, deactivateModule } from "./modules-api";
import DashboardContext from "./../../../context/DashboardContext";
import { actionTypes } from "./../../../reducers/DashboardReducer";

const ModuleItem = (props) => {
	/* global _EVF_DASHBOARD_ */
	const { assetsURL, liveDemoURL, isPro, licensePlan, adminURL } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
	const [{ upgradeModal }, dispatch] = useContext(DashboardContext);
	const [requirementFulfilled, setRequirementFulfilled] = useState(false);
	const [licenseActivated, setLicenseActivated] = useState(false);
	const [moduleEnabled, setModuleEnabled] = useState(false);

	const overlayColor = "#1a202c";

	const {
		data,
		isChecked,
		onCheckedChange,
		isPerformingBulkAction,
		selectedModuleData,
	} = props;
	const toast = useToast();
	const {
		title,
		name,
		excerpt,
		slug,
		image,
		plan,
		link,
		status,
		required_plan,
		type,
	} = data;
	const [moduleStatus, setModuleStatus] = useState(status);
	const [isPerformingAction, setIsPerformingAction] = useState(false);
	const [moduleSettingsURL, setModuleSettingsURL] = useState('');

	const handleModuleAction = () => {
		setIsPerformingAction(true);

		if (moduleEnabled) {
			if (
				moduleStatus === "inactive" ||
				moduleStatus === "not-installed"
			) {
				activateModule(slug, name, type)
					.then((data) => {
						if (data.success) {
							toast({
								title: data.message,
								status: "success",
								duration: 3000,
							});
							// window.location.reload();
							setModuleStatus("active");
						} else {
							toast({
								title: data.message,
								status: "error",
								duration: 3000,
							});
							setModuleStatus("not-installed");
						}
					})
					.catch((e) => {
						toast({
							title: e.message,
							status: "error",
							duration: 3000,
						});
						setModuleStatus("not-installed");
					})
					.finally(() => {
						setIsPerformingAction(false);
					});
			} else {
				deactivateModule(slug, type)
					.then((data) => {
						if (data.success) {
							toast({
								title: data.message,
								status: "success",
								duration: 3000,
							});
							// window.location.reload();
							setModuleStatus("inactive");
						} else {
							toast({
								title: data.message,
								status: "error",
								duration: 3000,
							});
							setModuleStatus("active");
						}
					})
					.finally(() => {
						setIsPerformingAction(false);
					});
			}
		} else {
			const upgradeModalRef = { ...upgradeModal };
			upgradeModalRef.enable = true;
			// Handle Pro Upgrade notice
			dispatch({
				type: actionTypes.GET_UPGRADE_MODAL,
				upgradeModal: upgradeModalRef,
			});
		}
	};

	useEffect(() => {
		setModuleStatus(data.status);

		if (!upgradeModal.enable) {
			setIsPerformingAction(false);
		}

		if (isPro) {
			setModuleEnabled(true);
			if (licensePlan) {
				const requiredPlan = licensePlan;

				if (data.plan && data.plan.includes(requiredPlan.trim())) {
					setRequirementFulfilled(true);
				} else {
					setModuleEnabled(false);
				}
				setLicenseActivated(true);
			} else {
				setLicenseActivated(false);
				setModuleEnabled(false);
				if(data.slug=='ai-contact-form'){
					setModuleEnabled(true);
				}else{
					setModuleEnabled(false);
				}
			}
		} else {
			if(data.slug=='ai-contact-form'){
				setModuleEnabled(true);
			}else{
				setModuleEnabled(false);
			}
		}
	}, [data, upgradeModal]);

	const handleBoxClick = () => {
		const upgradeModalRef = { ...upgradeModal };
		upgradeModalRef.moduleType = data.type;
		upgradeModalRef.moduleName = data.name;

		if (!isPro) {
			upgradeModalRef.type = "pro";
			upgradeModalRef.enable = true;
		} else if (isPro && !licenseActivated) {
			upgradeModalRef.type = "license";
			upgradeModalRef.enable = true;
		} else if (isPro && licenseActivated && !requirementFulfilled) {
			upgradeModalRef.type = "requirement";
			upgradeModalRef.enable = true;
		} else {
			upgradeModalRef.enable = false;
		}

		dispatch({
			type: actionTypes.GET_UPGRADE_MODAL,
			upgradeModal: upgradeModalRef,
		});
	};

	const handleModuleSettingsURL = () => {
		var settingsURL = adminURL + data.setting_url
		window.location.replace(settingsURL)
	}

	return (
		<Box
			overflow="hidden"
			boxShadow="none"
			border="1px"
			borderRadius="base"
			borderColor="gray.100"
			display="flex"
			flexDir="column"
			bg="white"
		>
			<Box
				p="0"
				flex="1 1 0%"
				position="relative"
				overflow="visible"
				opacity={moduleEnabled ? 1 : 0.7}
				onClick={() => {
					!moduleEnabled && handleBoxClick();
				}}
			>

			<Box
				position="relative"
				display="inline-block"
				_hover={{
					"& .demo-video__holder": {
					opacity: "0.7"
					},
					"& .demo-player": {
					opacity: "1"
					}
				}}
			>

      		<Box
				className="demo-video__holder"
				position="absolute"
				top="0"
				left="0"
				width="100%"
				height="100%"
				backgroundColor={overlayColor}
				opacity="0"
				transition="opacity 0.3s ease"
			/>
      		<Image
				src={assetsURL + image}
				borderTopRightRadius="sm"
				borderTopLeftRadius="sm"
				w="full"
			/>

			{data.demo_video_url !== "" && (
				<Icon
					className="demo-player"
					color="white"
					boxSize={12}
					position="absolute"
					top="50%"
					left="50%"
					transform="translate(-50%, -50%)"
					opacity="0"
					transition="opacity 0.3s ease"
				>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
					<path fill="white" d="M0 0h24v24H0z"/>
					<path d="M20 5v14H4V5h16m0-2H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/>
					<path d="M9 16V8l7 4-7 4z"/>
				</svg>
				</Icon>
			)}

			</Box>
				<Badge
					backgroundColor="black"
					color="white"
					position="absolute"
					top="0"
					right="0"
					textTransform="none"
					fontSize="12px"
					fontWeight="500"
					p="5px"
					m="5px"
				>
					{data.required_plan ? data.slug === 'ai-contact-form' ? 'Free' : data.required_plan  : "Pro"}
				</Badge>
				<Box p="6">
					<Stack direction="column" spacing="4">
						<Stack
							direction="row"
							align="center"
							justify="space-between"
						>
							<Heading
								fontSize="sm"
								fontWeight="semibold"
								color="gray.700"
							>
								<Checkbox
									isChecked={isChecked}
									onChange={(e) => {
										moduleEnabled
											? onCheckedChange(
													slug,
													e.target.checked
											  )
											: handleBoxClick();
									}}
								>
									{title}
								</Checkbox>
							</Heading>
						</Stack>

						<Text
							fontWeight="400"
							fontSize="14px"
							color="gray.500"
							textAlign="left"
						>
							{excerpt}
						</Text>
					</Stack>
				</Box>
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
						href={link}
						fontSize="xs"
						color="gray.500"
						textDecoration="underline"
						isExternal
					>
						{__("Documentation", "everest-forms")}
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

				{moduleEnabled && (
					((data.setting_url !== "" && moduleStatus === "active") && (
					  <IconButton
						size='md'
						icon={<SettingsIcon />}
						onClick={handleModuleSettingsURL}
					  />
					))
				  )}

				{(moduleEnabled) && (
					<Switch
						isChecked= {'active'=== moduleStatus ? true: false}
						onChange = {moduleEnabled ? handleModuleAction : handleBoxClick}
						colorScheme="green"
					/>
				)}


				{(!moduleEnabled) &&(
					<Button
					colorScheme={"primary"}
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
					onClick={moduleEnabled ? handleModuleAction : handleBoxClick}
					isLoading={
						isPerformingAction ||
						(selectedModuleData.hasOwnProperty(slug) &&
							isPerformingBulkAction)
					}
				>
					{__("Upgrade Plan", "everest-forms")}
				</Button>
			)}
			</Box>
		</Box>
	);
};

export default ModuleItem;
