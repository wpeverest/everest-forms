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
	Modal,
	Tooltip,
	ModalCloseButton,
	ModalContent,
	ModalOverlay,
	ModalHeader,
	Spinner,
	useDisclosure,
	Icon,
} from "@chakra-ui/react";
import { SettingsIcon, WarningIcon } from "@chakra-ui/icons";
import { __ } from "@wordpress/i18n";
import React, { useState, useEffect, useContext } from "react";
import YouTubePlayer from 'react-player/youtube';
import { FaInfoCircle, FaPlayCircle } from 'react-icons/fa';

/**
 *  Internal Dependencies
 */
import { activateModule, deactivateModule } from "./modules-api";
import DashboardContext from "./../../../context/DashboardContext";
import { actionTypes } from "./../../../reducers/DashboardReducer";
import { FreeModules } from "../../../Constants/Products";

const ModuleItem = (props) => {
	/* global _EVF_DASHBOARD_ */
	const { assetsURL, liveDemoURL, isPro, licensePlan, adminURL, upgradeURL } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
	const [{ upgradeModal }, dispatch] = useContext(DashboardContext);
	const [requirementFulfilled, setRequirementFulfilled] = useState(false);
	const [licenseActivated, setLicenseActivated] = useState(false);
	const [moduleEnabled, setModuleEnabled] = useState(false);

	const [showPlayVideoButton, setShowPlayVideoButton] = useState(false);
	const [thumbnailVideoPlaying, setThumbnailVideoPlaying] = useState(false);

	const [thumbnailVideoLoading, setThumbnailVideoLoading] = useState(true);
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [isAddonActivating, setAddonActivated] = useState(false);

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
		demo_video_url,
		setting_url
	} = data;
	const [moduleStatus, setModuleStatus] = useState(status);
	const [isPerformingAction, setIsPerformingAction] = useState(false);
	const [moduleSettingsURL, setModuleSettingsURL] = useState('');

	const handleModuleAction = () => {
		setAddonActivated(true);
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
							setAddonActivated(false);
							setModuleStatus("active");
						} else {
							toast({
								title: data.message,
								status: "error",
								duration: 3000,
							});
							setAddonActivated(false);
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
						setAddonActivated(false);
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
						setAddonActivated(false);
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
				if(FreeModules.includes(data.slug)){
					setModuleEnabled(true);
				}else{
					setModuleEnabled(false);
				}
			}
		} else {
			if(FreeModules.includes(data.slug)){
				setModuleEnabled(true);
			}else{
				setModuleEnabled(false);
			}
		}
	}, [data, upgradeModal]);

	useEffect(() => {
		if (thumbnailVideoPlaying) {
			setShowPlayVideoButton(false);
		}
	}, [thumbnailVideoPlaying]);

	const handleBoxClick = () => {
		const upgradeModalRef = { ...upgradeModal };
		upgradeModalRef.moduleType = data.type;
		upgradeModalRef.moduleName = data.name;

		if (!isPro) {
			const plan_upgrade_url = upgradeURL + 'utm_medium=addon-activation-page&utm_source=evf-free&utm_campaign=addon-page-feature-block&utm_content=Upgrade%20Plan'
			window.open(plan_upgrade_url,'_blank');
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
		var settingsURL = adminURL + setting_url
		window.open(settingsURL, '_blank');
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
				>

			<Box
				position="relative"
				borderTopRightRadius="sm"
				borderTopLeftRadius="sm"
				overflow="hidden"
				height={"178px"}
				onMouseLeave={() => demo_video_url && setShowPlayVideoButton(false)}
			>

			{((demo_video_url && !thumbnailVideoPlaying) || !demo_video_url) && (
				<Image
					src={assetsURL + image}
					borderTopRightRadius="sm"
					borderTopLeftRadius="sm"
					w="full"
					height={"178px"}
					onMouseOver={() =>
							{if (demo_video_url) {
								setShowPlayVideoButton(true);
							}
						}
					}
				/>
			)}


			{thumbnailVideoPlaying && (
				<Modal isOpen={true} onClose={() => setThumbnailVideoPlaying(false)} size="3xl">
				<ModalOverlay />
				<ModalContent px={4} pb={4}>
				<ModalHeader textAlign="center">{title}</ModalHeader>
				<ModalCloseButton/>
				<YouTubePlayer
					url={'https://www.youtube.com/embed/'+demo_video_url}
					playing={true}
					width={'100%'}
					controls
					onReady={() => setThumbnailVideoLoading(false)}
					onBufferEnd={() => setThumbnailVideoLoading(false)}
				/>

				{thumbnailVideoLoading && (
					<Box
						position={'absolute'}
						top={'50%'}
						left={'50%'}
						transform={'translate(-50%, -50%)'}
					>
						<Spinner size={'lg'} />
					</Box>
				)}
				</ModalContent>
				</Modal>
			)}

			{showPlayVideoButton && (
				<Box
					pos="absolute"
					top={0}
					left={0}
					right={0}
					bottom={0}
					bg="black"
					opacity={0.7}
					display="flex"
					alignItems="center"
					justifyContent="center"
					borderTopStartRadius={10}
					borderTopEndRadius={10}
				>
					<Tooltip label={__('Play Video', 'everest-forms')}>
						<span>
							<FaPlayCircle
								color="white"
								size={50}
								cursor={'pointer'}
								onClick={() => {
									setThumbnailVideoPlaying(true);
									setThumbnailVideoLoading(true);
								}}
							/>
						</span>
					</Tooltip>
				</Box>
			)}

			{
				data.dependent_status === 'inactive' && data.status === 'active' && (
					<Box
					pos="absolute"
					left={0}
					bottom={0}
					bg="rgba(0, 0, 0, 0.7)"
					padding={"8px 20px"}
					display="flex"
					justifyContent="center"
					backdropFilter="blur(5px)"
					width={'100%'}
			>
				<Image src={_EVF_DASHBOARD_.alert_icon} w={'5'} h={'5'}/>
				<Text
					color="white"
					fontWeight={600}
					fontSize={'14px'}
					lineHeight={'21px'}
					marginLeft="10px"

				>
				Activate { data.required_plugin } plugin to use this addon.
				</Text>
			</Box>
			)

			}

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
					{data.required_plan ? FreeModules.includes(data.slug) ? 'Free' : data.required_plan  : "Pro"}
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
									opacity={ data.dependent_status === 'inactive' ? 0.5 : 1 }
								>
									{title}
								</Checkbox>
							</Heading>
							{
								data.is_dependent && data.dependent_status !== 'active' && (
										<Tooltip
											showArrow
											label={sprintf(
												__(
													"Requires %s",
													"everest-forms"
												),
												data.required_plugin
											)}
										>
										<Box
											cursor="pointer"
										>
											<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M12.5 17C12.7833 17 13.021 16.904 13.213 16.712C13.405 16.52 13.5007 16.2827 13.5 16V12C13.5 11.7167 13.404 11.4793 13.212 11.288C13.02 11.0967 12.7827 11.0007 12.5 11C12.2173 10.9993 11.98 11.0953 11.788 11.288C11.596 11.4807 11.5 11.718 11.5 12V16C11.5 16.2833 11.596 16.521 11.788 16.713C11.98 16.905 12.2173 17.0007 12.5 17ZM12.5 9C12.7833 9 13.021 8.904 13.213 8.712C13.405 8.52 13.5007 8.28267 13.5 8C13.4993 7.71733 13.4033 7.48 13.212 7.288C13.0207 7.096 12.7833 7 12.5 7C12.2167 7 11.9793 7.096 11.788 7.288C11.5967 7.48 11.5007 7.71733 11.5 8C11.4993 8.28267 11.5953 8.52033 11.788 8.713C11.9807 8.90567 12.218 9.00133 12.5 9ZM12.5 22C11.1167 22 9.81667 21.7373 8.6 21.212C7.38334 20.6867 6.325 19.9743 5.425 19.075C4.525 18.1757 3.81267 17.1173 3.288 15.9C2.76333 14.6827 2.50067 13.3827 2.5 12C2.49933 10.6173 2.762 9.31733 3.288 8.1C3.814 6.88267 4.52633 5.82433 5.425 4.925C6.32367 4.02567 7.382 3.31333 8.6 2.788C9.818 2.26267 11.118 2 12.5 2C13.882 2 15.182 2.26267 16.4 2.788C17.618 3.31333 18.6763 4.02567 19.575 4.925C20.4737 5.82433 21.1863 6.88267 21.713 8.1C22.2397 9.31733 22.502 10.6173 22.5 12C22.498 13.3827 22.2353 14.6827 21.712 15.9C21.1887 17.1173 20.4763 18.1757 19.575 19.075C18.6737 19.9743 17.6153 20.687 16.4 21.213C15.1847 21.739 13.8847 22.0013 12.5 22Z" fill="#ECC94B"/>
											</svg>
										</Box>
										</Tooltip>
								)
							}
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
				<HStack align="center" flexDirection={"column"} alignItems={"unset"} gap={"0"}>
					<Link
						href={link}
						fontSize="xs"
						color="gray.500"
						textDecoration="underline"
						isExternal
					>
						{__("Documentation", "everest-forms")}
					</Link>
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
					((setting_url !== "" && moduleStatus === "active") && (
					  <IconButton
						size='sm'
						icon={<SettingsIcon />}
						onClick={handleModuleSettingsURL}
					  />
					))
				  )}

			{moduleEnabled && (
			<>
				{isAddonActivating ? (
					<Spinner
					speed='0.50s'
					emptyColor='gray.200'
					color='blue.500'
					size='md'
				  />
				) : (
				<Switch
					isChecked={moduleStatus === 'active'}
					onChange={moduleEnabled ? handleModuleAction : handleBoxClick}
					colorScheme="green"
				/>
				)}
			</>
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
