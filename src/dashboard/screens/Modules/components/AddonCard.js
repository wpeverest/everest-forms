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
	Tooltip,
	useDisclosure,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useState } from 'react';
import { FaCog, FaPlay } from 'react-icons/fa';
import ReactPlayer from 'react-player';
import { activateModule, deactivateModule } from './modules-api';

const AddonCard = ({ addon, showToast, onModuleToggle }) => {
	const [isActive, setIsActive] = useState(addon.status === 'active');
	const [isLoading, setIsLoading] = useState(false);
	const [videoLoading, setVideoLoading] = useState(false);
	const {
		isOpen: isVideoOpen,
		onOpen: onVideoOpen,
		onClose: onVideoClose,
	} = useDisclosure();

	const { isPro, licensePlan } =
		typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_
			? _EVF_DASHBOARD_
			: {};

	const getImageUrl = (imagePath) => {
		const { assetsURL } =
			typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_;
		if (imagePath && assetsURL) {
			return assetsURL + imagePath;
		}
		return imagePath;
	};

	const moduleEnabled = useMemo(() => {
		if (!addon.plan || addon.plan.length === 0) {
			return true;
		}

		if (Array.isArray(addon.plan) && addon.plan.includes('free')) {
			return true;
		}

		if (isPro) {
			return true;
		}

		return false;
	}, [addon.plan, isPro]);

	const handleUpgradePlan = () => {
		const { upgradeURL } =
			typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_;
		if (upgradeURL) {
			const plan_upgrade_url =
				upgradeURL +
				'?utm_source=dashboard-modules&utm_medium=upgrade-button&utm_campaign=' +
				addon.slug;
			window.open(plan_upgrade_url, '_blank');
		}
	};

	const handleVideoPlay = () => {
		setVideoLoading(true);
		onVideoOpen();
	};

	// Sync local isActive state when addon.status changes (e.g. after category switch)
	useEffect(() => {
		setIsActive(addon.status === 'active');
	}, [addon.status]);

	const handleToggle = async () => {
		setIsLoading(true);
		try {
			let response;
			if (isActive) {
				response = await deactivateModule(addon.slug, addon.type);
				if (response.success) {
					setIsActive(false);
					onModuleToggle?.(addon.slug, 'inactive');
					showToast(
						response.message || 'Module deactivated successfully',
						'success',
					);
				} else {
					showToast(
						response.message || 'Failed to deactivate module',
						'error',
					);
				}
			} else {
				response = await activateModule(addon.slug, addon.name, addon.type);
				if (response.success) {
					setIsActive(true);
					onModuleToggle?.(addon.slug, 'active');
					showToast(
						response.message || 'Module activated successfully',
						'success',
					);
				} else {
					showToast(response.message || 'Failed to activate module', 'error');
				}
			}
		} catch (error) {
			showToast(error.message || 'An error occurred', 'error');
		}
		setIsLoading(false);
	};

	const getPlanBadge = (plan) => {
		if (!plan || plan.length === 0) {
			return 'Free';
		}

		if (Array.isArray(plan) && plan.includes('free')) {
			return 'Free';
		}
		return 'PRO';
	};

	const getPlanBadgeStyles = (plan) => {
		const badge = getPlanBadge(plan);

		if (badge === 'Free') {
			return {
				bg: 'transparent',
				border: '1px solid #D1D5DB',
				color: '#6B7280',
				fontSize: '11px',
				fontWeight: '500',
			};
		}

		return {
			bg: '#FFFAF5',
			border: '1px solid #FF8C39',
			color: '#FF8C39',
			fontSize: '11px',
			fontWeight: '600',
		};
	};

	const badgeStyles = getPlanBadgeStyles(addon.plan);

	return (
		<Box
			bg="white"
			borderRadius="lg"
			border="1px solid"
			borderColor="gray.200"
			p={{ base: '4', sm: '5', md: '6' }}
			_hover={{ boxShadow: 'md' }}
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
					bg="rgba(255, 255, 255, 0.8)"
					borderRadius="xl"
					alignItems="center"
					justifyContent="center"
					zIndex="10"
				>
					<Spinner size="lg" color="#7545bb" thickness="3px" />
				</Flex>
			)}

			<HStack
				align="start"
				spacing={{ base: '3', sm: '4' }}
				flex="1"
				mb={{ base: '4', sm: '5', md: '6' }}
			>
				<Box
					w={{ base: '9', sm: '10' }}
					h={{ base: '9', sm: '10' }}
					bg="gray.50"
					borderRadius="full"
					display="flex"
					alignItems="center"
					justifyContent="center"
					flexShrink={0}
					overflow="hidden"
				>
					{addon.image ? (
						<img
							src={getImageUrl(addon.image)}
							alt={addon.title}
							style={{
								width: '100%',
								height: '100%',
								objectFit: 'contain',
								borderRadius: '50%',
							}}
							onError={(e) => {
								e.target.style.display = 'none';
								if (e.target.nextSibling) {
									e.target.nextSibling.style.display = 'flex';
								}
							}}
						/>
					) : null}
					<Box
						display={addon.image ? 'none' : 'flex'}
						alignItems="center"
						justifyContent="center"
						fontSize={{ base: 'xl', sm: '2xl' }}
						width="100%"
						height="100%"
					>
						📋
					</Box>
				</Box>

				{/* Right Side - Title and Badge */}
				<VStack
					align="start"
					spacing={{ base: '2', sm: '3' }}
					flex="1"
					minW="0"
				>
					<HStack justify="space-between" w="full" align="start" spacing="2">
						<Heading
							size="sm"
							color="gray.800"
							fontWeight="600"
							fontSize={{ base: '14px', sm: '15px', md: '16px' }}
							noOfLines={2}
							flex="1"
							minW="0"
						>
							{addon.title}
						</Heading>
						<Badge
							fontSize={badgeStyles.fontSize}
							px="2"
							py="1"
							borderRadius="base"
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

					<Tooltip
						label={
							addon.excerpt || __('No description available.', 'everest-forms')
						}
						placement="bottom"
						hasArrow
						bg="white"
						color="gray.800"
						fontSize="sm"
						fontWeight="400"
						borderRadius="md"
						px="3"
						py="2"
					>
						<Text
							fontSize={{ base: '12px', sm: '13px' }}
							color="gray.600"
							lineHeight="1.5"
							noOfLines={2}
							cursor="help"
						>
							{addon.excerpt ||
								__('No description available.', 'everest-forms')}
						</Text>
					</Tooltip>
				</VStack>
			</HStack>

			<HStack
				justify="space-between"
				align="center"
				mt="auto"
				pt={{ base: '2', sm: '3' }}
				borderTop="1px solid"
				borderColor="gray.100"
				flexWrap={{ base: 'wrap', sm: 'nowrap' }}
				gap={{ base: '2', sm: '0' }}
			>
				<HStack
					spacing="2"
					fontSize={{ base: '12px', sm: '13px' }}
					flexWrap="wrap"
					flex={{ base: '1', sm: '0 1 auto' }}
				>
					{addon.link && (
						<Link
							href={addon.link}
							color="gray.600"
							textDecoration="none"
							isExternal
							_hover={{ color: '#7545bb', textDecoration: 'underline' }}
							fontWeight="500"
						>
							{__('Docs', 'everest-forms')}
						</Link>
					)}
					{addon.demo_video_url && (
						<>
							<Text color="gray.300" display={{ base: 'none', sm: 'block' }}>
								|
							</Text>
							<IconButton
								size="xs"
								icon={<Icon as={FaPlay} />}
								aria-label={__('Video Tutorial', 'everest-forms')}
								variant="ghost"
								color="gray.600"
								_hover={{ color: '#7545bb', bg: 'gray.50' }}
								onClick={handleVideoPlay}
							/>
						</>
					)}
					{addon.setting_url && isActive && (
						<>
							<Text color="gray.300" display={{ base: 'none', sm: 'block' }}>
								|
							</Text>
							<IconButton
								size="xs"
								icon={<FaCog />}
								aria-label={__('Settings', 'everest-forms')}
								variant="ghost"
								color="gray.600"
								_hover={{ color: '#7545bb', bg: 'gray.50' }}
								onClick={() => window.open(addon.setting_url, '_self')}
							/>
						</>
					)}
				</HStack>
				<HStack spacing="2" flexShrink={0}>
					{moduleEnabled ? (
						<Switch
							isChecked={isActive}
							onChange={handleToggle}
							isDisabled={isLoading}
							size="md"
							sx={{
								'& .chakra-switch__track': {
									bg: isActive ? '#7545bb' : 'gray.300',
								},
								'& .chakra-switch__track[data-checked]': {
									bg: '#7545bb',
								},
							}}
						/>
					) : (
						<Button
							size="sm"
							fontSize={{ base: '12px', sm: '13px' }}
							fontWeight="600"
							bg="#7545bb"
							color="white"
							borderColor="#7545bb"
							px={{ base: '3', sm: '4' }}
							h={{ base: '28px', sm: '32px' }}
							_hover={{
								bg: '#3a4a8f',
								borderColor: '#3a4a8f',
							}}
							_active={{
								bg: '#2d3b70',
							}}
							onClick={handleUpgradePlan}
						>
							{__('Upgrade Plan', 'everest-forms')}
						</Button>
					)}
				</HStack>
			</HStack>

			{/* YouTube Video Modal */}
			{isVideoOpen && addon.demo_video_url && (
				<Modal
					isOpen={isVideoOpen}
					onClose={onVideoClose}
					size={{ base: 'full', sm: 'xl', md: '2xl', lg: '3xl' }}
					isCentered
				>
					<ModalOverlay bg="blackAlpha.700" />
					<ModalContent
						mx={{ base: '0', sm: '4' }}
						my={{ base: '0', sm: 'auto' }}
					>
						<ModalHeader
							textAlign="center"
							fontSize={{ base: 'md', sm: 'lg' }}
							fontWeight="600"
							px={{ base: '4', sm: '6' }}
						>
							{addon.title}
						</ModalHeader>
						<ModalCloseButton />
						<ModalBody pb={{ base: '4', sm: '6' }} px={{ base: '4', sm: '6' }}>
							<Box
								position="relative"
								paddingTop="56.25%"
								bg="gray.100"
								borderRadius="md"
								overflow="hidden"
							>
								<ReactPlayer
									url={`https://www.youtube.com/watch?v=${addon.demo_video_url}`}
									playing={true}
									width="100%"
									height="100%"
									controls
									style={{
										position: 'absolute',
										top: 0,
										left: 0,
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
										<Spinner size="lg" color="#7545bb" />
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
