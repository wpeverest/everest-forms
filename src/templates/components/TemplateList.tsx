import {
	Box,
	Button,
	Flex,
	HStack,
	Icon,
	Image,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalHeader,
	ModalOverlay,
	Text,
	useDisclosure,
	useToast,
	VStack,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { FaHeart, FaRegHeart } from 'react-icons/fa';
import { FiArrowRight } from 'react-icons/fi';
import { LuSparkles } from 'react-icons/lu';
import notFoundImage from '../images/not-found-image.png';
import { templatesScriptData } from '../utils/global';
import PluginStatus from './PluginStatus';

interface Template {
	id: number;
	title: string;
	slug: string;
	imageUrl: string;
	description: string;
	isPro: boolean;
	preview_link?: string;
	addons?: { [key: string]: string };
	categories?: string[];
}

interface TemplateListProps {
	selectedCategory: string;
	templates: Template[];
	onCreateWithAI?: (formId?: number, title?: string) => void;
}

const { restURL, security } = templatesScriptData;

// "Edit with AI" is disabled (shown greyed-out) on local / development sites where the AI gateway is unavailable.
const AI_ENABLED = !!templatesScriptData?.aiEnabled;

interface CreateTemplateResponse {
	success: boolean;
	data?: {
		id: number;
		redirect: string;
		status: number;
	};
	message?: string;
}

const TemplateList: React.FC<TemplateListProps> = ({
	selectedCategory,
	templates,
	onCreateWithAI,
}) => {
	const [previewTemplate, setPreviewTemplate] = useState<Template | null>(null);
	const [selectedTemplateSlug, setSelectedTemplateSlug] = useState<string>('');
	const [modalState, setModalState] = useState<'addons' | 'choose'>('choose');
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [hoverCardId, setHoverCardId] = useState<number | null>(null);
	const [favorites, setFavorites] = useState<string[]>([]);
	const [isCreating, setIsCreating] = useState(false);
	const toast = useToast();
	const queryClient = useQueryClient();
	const [isPluginModalOpen, setIsPluginModalOpen] = useState(false);
	const [lockedTemplateName, setLockedTemplateName] = useState('');

	const openPluginModal = () => setIsPluginModalOpen(true);
	const closePluginModal = () => setIsPluginModalOpen(false);

	useEffect(() => {
		const savedFavorites = localStorage.getItem('favorites');

		if (savedFavorites) {
			setFavorites(JSON.parse(savedFavorites));
		} else {
			const fetchFavorites = async () => {
				try {
					const response: any = await apiFetch({
						path: `${restURL}everest-forms/v1/templates/favorite_forms`,
						method: 'GET',
						headers: {
							'X-WP-Nonce': security,
						},
					});

					if (response && Array.isArray(response)) {
						setFavorites(response);
						localStorage.setItem('favorites', JSON.stringify(response));
					}
				} catch (error) {
					console.error('Error fetching favorites:', error);
				}
			};

			fetchFavorites();
		}
	}, []);

	const handleTemplateClick = async (template: Template) => {
		const addonKeys = template.addons ? Object.keys(template.addons) : [];

		try {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/plugin/upgrade`,
				method: 'POST',
				body: JSON.stringify({ requiredPlugins: addonKeys }),
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': security },
			});

			const { plugin_status } = response as {
				plugin_status: Record<string, string>;
			};

			if (!plugin_status) {
				setLockedTemplateName(template.title);
				openPluginModal();
				return;
			}

			setSelectedTemplateSlug(template.slug);
			setPreviewTemplate(template);

			// No addons required → go straight to the choose state
			if (addonKeys.length === 0) {
				setModalState('choose');
			} else {
				setModalState('addons');
			}
			onOpen();
		} catch (error) {
			toast({
				title: __('Error', 'everest-forms'),
				description: __(
					'An error occurred while checking the plugin status. Please try again.',
					'everest-forms',
				),
				status: 'error',
				position: 'bottom-right',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		}
	};

	// Called when all addons are active — transition to the choose view
	const handleAddonsReady = () => {
		setModalState('choose');
	};

	// "Edit with AI": create a DRAFT form from the template, then open the AI
	// preview with it loaded so the user can refine it by prompting.
	const [aiCreatingSlug, setAiCreatingSlug] = useState('');
	const handleEditWithAI = async (template: Template) => {
		if (aiCreatingSlug) return;
		setAiCreatingSlug(template.slug);
		try {
			const response = (await apiFetch({
				path: `${restURL}everest-forms/v1/templates/create`,
				method: 'POST',
				body: JSON.stringify({
					title: template.title,
					slug: template.slug,
					draft: true,
				}),
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': security },
			})) as CreateTemplateResponse;

			if (response.success && response.data?.id) {
				// Navigate into the AI flow with the draft loaded (component unmounts).
				onCreateWithAI?.(response.data.id, template.title);
			} else {
				throw new Error(response.message || 'create_failed');
			}
		} catch (error) {
			setAiCreatingSlug('');
			toast({
				title: __('Error', 'everest-forms'),
				description: __(
					'Could not start AI editing. Please try again.',
					'everest-forms',
				),
				status: 'error',
				position: 'bottom-right',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		}
	};

	// Creates the form using the template title as name
	const handleCreateForm = async () => {
		if (!previewTemplate) return;
		setIsCreating(true);
		try {
			const response = (await apiFetch({
				path: `${restURL}everest-forms/v1/templates/create`,
				method: 'POST',
				body: JSON.stringify({
					title: previewTemplate.title,
					slug: selectedTemplateSlug,
				}),
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': security },
			})) as CreateTemplateResponse;

			if (response.success && response.data) {
				window.location.href = response.data.redirect;
			} else {
				setIsCreating(false);
				toast({
					title: __('Error', 'everest-forms'),
					description:
						response.message ||
						__('Failed to create form template.', 'everest-forms'),
					status: 'error',
					position: 'bottom-right',
					duration: 5000,
					isClosable: true,
					variant: 'subtle',
				});
			}
		} catch (error) {
			setIsCreating(false);
			toast({
				title: __('Error', 'everest-forms'),
				description: __(
					'An error occurred while creating the form template.',
					'everest-forms',
				),
				status: 'error',
				position: 'bottom-right',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		}
	};

	const mutation = useMutation(
		async (slug: string) => {
			const newFavorites = favorites.includes(slug)
				? favorites.filter((item) => item !== slug)
				: [...favorites, slug];

			setFavorites(newFavorites);
			localStorage.setItem('favorites', JSON.stringify(newFavorites));

			await apiFetch({
				path: `${restURL}everest-forms/v1/templates/favorite`,
				method: 'POST',
				body: JSON.stringify({
					action: newFavorites.includes(slug)
						? 'add_favorite'
						: 'remove_favorite',
					slug,
				}),
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': security,
				},
			});

			return newFavorites;
		},
		{
			onError: () => {
				toast({
					title: __('Error', 'everest-forms'),
					description: __(
						'An error occurred while updating favorites.',
						'everest-forms',
					),
					status: 'error',
					position: 'bottom-right',
					duration: 5000,
					isClosable: true,
					variant: 'subtle',
				});
			},
			onSuccess: (newFavorites) => {
				queryClient.invalidateQueries(['templates']);
				setFavorites(newFavorites);
				localStorage.setItem('favorites', JSON.stringify(newFavorites));
				queryClient.invalidateQueries(['favorites']);
			},
		},
	);

	const handleFavoriteToggle = (slug: string) => {
		mutation.mutate(slug);
	};

	const requiredPlugins = previewTemplate?.addons
		? Object.entries(previewTemplate.addons).map(([key, value]) => ({
				key,
				value,
			}))
		: [];

	return (
		<Box padding="0">
			{templates?.length ? (
				<Box
					sx={{
						display: 'grid',
						gridTemplateColumns: 'repeat(2, 1fr)',
						gap: '16px',
						'@media (min-width: 1280px)': {
							gridTemplateColumns: 'repeat(3, 1fr)',
						},
						'@media (max-width: 640px)': {
							gridTemplateColumns: '1fr',
						},
					}}
				>
					{templates.map((template) => {
						const isHovered = hoverCardId === template.id;
						return (
							<Box
								key={template.slug}
								borderRadius="12px"
								border="1px solid #e2e8f0"
								overflow="hidden"
								position="relative"
								onMouseEnter={() => setHoverCardId(template.id)}
								onMouseLeave={() => setHoverCardId(null)}
								bg="white"
								display="flex"
								flexDirection="column"
								transition="all 0.25s"
								_hover={{
									borderColor: 'rgba(117,69,187,0.4)',
									boxShadow: '0 8px 24px -12px rgba(117,69,187,0.18)',
									transform: 'translateY(-2px)',
								}}
							>
								{/* Image area */}
								<Box
									position="relative"
									borderBottom="1px solid #e2e8f0"
									pt="20px"
									px="20px"
									pb="0"
									display="flex"
									alignItems="flex-start"
									justifyContent="center"
									overflow="hidden"
									background="linear-gradient(129deg, #F3F2F8 2.83%, #F7F5F9 110.96%)"
									minH="160px"
								>
									{/* Image wrapper — white card with shadow */}
									<Box
										position="relative"
										w="100%"
										bg="white"
										borderRadius="8px 8px 0 0"
										border="1px solid #e2e8f0"
										borderBottom="none"
										overflow="hidden"
										boxShadow="0 6px 24px 0 #E5E1EF"
									>
										{/* Pro badge inside image wrapper */}
										{template.isPro && (
											<Box
												as="span"
												position="absolute"
												top="10px"
												right="10px"
												fontSize="10px"
												fontWeight="700"
												textTransform="uppercase"
												letterSpacing="0.06em"
												color="#7545BB"
												bg="#f3eefc"
												border="1px solid #e6ddf6"
												px="8px"
												py="2px"
												borderRadius="4px"
												zIndex={2}
												display="inline-flex"
												alignItems="center"
											>
												{__('Pro', 'everest-forms')}
											</Box>
										)}
										<Image
											src={template.imageUrl}
											alt={template.title}
											display="block"
											w="100%"
											h="auto"
											objectFit="cover"
											objectPosition="top"
											borderRadius="6px"
											loading="lazy"
										/>
									</Box>

									{/* Hover overlay — dark gradient */}
									<Box
										position="absolute"
										inset="0"
										bgGradient="linear(to-b, rgba(14,14,14,0.2), rgba(14,14,14,0.4), rgba(14,14,14,0.6))"
										opacity={isHovered ? 1 : 0}
										transition="opacity 0.3s"
										display="flex"
										flexDirection="column"
										alignItems="center"
										justifyContent="center"
										gap="8px"
										px="20px"
										zIndex={2}
									>
										{/* Favorite button */}
										<Box
											as="button"
											onClick={(e) => {
												e.stopPropagation();
												handleFavoriteToggle(template.slug);
											}}
											aria-label={`Toggle favorite for ${template.title}`}
											position="absolute"
											top="12px"
											right="12px"
											w="28px"
											h="28px"
											display="inline-flex"
											alignItems="center"
											justifyContent="center"
											borderRadius="full"
											bg={
												favorites.includes(template.slug)
													? 'white'
													: 'rgba(255,255,255,0.15)'
											}
											backdropFilter="blur(4px)"
											color={
												favorites.includes(template.slug) ? 'red.500' : 'white'
											}
											border="none"
											cursor="pointer"
											_hover={{
												bg: 'white',
												color: 'red.500',
											}}
											transition="all 0.2s"
										>
											<Icon
												as={
													favorites.includes(template.slug)
														? FaHeart
														: FaRegHeart
												}
												boxSize="3.5"
											/>
										</Box>

										{/* Use Template button */}
										<Button
											w="170px"
											h="36px"
											borderRadius="8px"
											bg="#7545BB"
											color="white"
											fontSize="14px"
											fontWeight="500"
											boxShadow="0 6px 18px -6px rgba(117,69,187,0.55)"
											_hover={{ bg: 'rgba(117,69,187,0.9)' }}
											opacity={isHovered ? 1 : 0}
											transform={
												isHovered ? 'translateY(0)' : 'translateY(8px)'
											}
											transition="all 0.3s"
											onClick={() => handleTemplateClick(template)}
										>
											{__('Use this template', 'everest-forms')}
										</Button>

										{/* Preview button */}
										{template.preview_link && (
											<Button
												w="170px"
												h="36px"
												borderRadius="8px"
												bg="transparent"
												border="1px solid rgba(255,255,255,0.4)"
												color="white"
												fontSize="14px"
												fontWeight="500"
												_hover={{ bg: 'rgba(255,255,255,0.1)' }}
												opacity={isHovered ? 1 : 0}
												transform={
													isHovered ? 'translateY(0)' : 'translateY(8px)'
												}
												transition="all 0.3s 0.06s"
												onClick={() =>
													window.open(template.preview_link, '_blank')
												}
											>
												{__('Preview', 'everest-forms')}
											</Button>
										)}
									</Box>
								</Box>

								{/* Card info */}
								<Box p="20px" flex="1" display="flex" flexDirection="column">
									<Text
										className="template-title"
										fontSize="14px"
										fontWeight="600"
										color="#0e0e0e"
										mb="4px"
										margin="0 0 4px 0"
										transition="color 0.2s"
										sx={{ '.template-card:hover &': { color: '#7545BB' } }}
									>
										{template.title}
									</Text>
									<Text
										fontSize="12px"
										color="#6b6b6b"
										lineHeight="1.6"
										margin="0"
										flex="1"
									>
										{template.description}
									</Text>
								</Box>
							</Box>
						);
					})}
				</Box>
			) : (
				<Box
					display="flex"
					flexDirection="column"
					justifyContent="center"
					alignItems="center"
					minH="400px"
					width="100%"
				>
					<Image
						src={notFoundImage}
						alt={__('Not Found', 'everest-forms')}
						boxSize="260px"
						objectFit="cover"
					/>
					<Text mt={4} fontSize="lg" fontWeight="bold" textAlign="center">
						{__('No Templates Found', 'everest-forms')}
					</Text>
					<Text margin={0} fontSize="sm" textAlign="center" color="gray.600">
						{__(
							"Sorry, we didn't find any templates that match your criteria",
							'everest-forms',
						)}
					</Text>
				</Box>
			)}

			{/* ── Premium / locked template modal ────────────────────────────── */}
			<Modal
				isCentered
				isOpen={isPluginModalOpen}
				onClose={closePluginModal}
				size="md"
			>
				<ModalOverlay bg="rgba(0,0,0,0.35)" backdropFilter="blur(2px)" />
				<ModalContent
					borderRadius="16px"
					p="0"
					overflow="hidden"
					boxShadow="0 8px 32px rgba(0,0,0,0.1)"
				>
					<ModalHeader p="0">
						<Flex
							align="center"
							gap="12px"
							px="24px"
							pt="22px"
							pb="16px"
							borderBottom="1px solid #f1f5f9"
						>
							<Box
								w="36px"
								h="36px"
								borderRadius="8px"
								bg="rgba(117,69,187,0.1)"
								display="flex"
								alignItems="center"
								justifyContent="center"
								flexShrink={0}
							>
								<Icon as={LuSparkles} boxSize="16px" color="#7545BB" />
							</Box>
							<Box flex="1" minW="0">
								<Text
									fontSize="15px"
									fontWeight="600"
									color="#0e0e0e"
									m="0"
									noOfLines={1}
								>
									{lockedTemplateName}
								</Text>
								<Text fontSize="12px" color="#9ca3af" m="0">
									{__('Premium Template', 'everest-forms')}
								</Text>
							</Box>
						</Flex>
					</ModalHeader>
					<ModalCloseButton
						top="14px"
						right="16px"
						size="sm"
						borderRadius="6px"
						_hover={{ bg: '#f1f5f9' }}
					/>

					<ModalBody px="24px" py="20px">
						<Text fontSize="13px" color="#6b7280" lineHeight="1.65" m="0">
							{__(
								'This template requires a premium plan. Upgrade to unlock all premium templates and features.',
								'everest-forms',
							)}
						</Text>
					</ModalBody>

					<Box px="24px" pb="22px">
						<Flex gap="10px">
							<Box
								as="button"
								flex="1"
								h="38px"
								borderRadius="8px"
								border="1px solid #e2e8f0"
								bg="white"
								color="#374151"
								fontSize="13px"
								fontWeight="500"
								cursor="pointer"
								onClick={closePluginModal}
								_hover={{ bg: '#f8fafc' }}
								transition="background 0.2s"
							>
								{__('Cancel', 'everest-forms')}
							</Box>
							<Box
								as="a"
								href="https://everestforms.net/upgrade/?utm_medium=evf-template-page&utm_source=evf-free&utm_campaign=template-premium-popup"
								target="_blank"
								rel="noopener noreferrer"
								flex="1"
								h="38px"
								borderRadius="8px"
								bg="#7545BB"
								color="white"
								fontSize="13px"
								fontWeight="500"
								cursor="pointer"
								display="flex"
								alignItems="center"
								justifyContent="center"
								gap="6px"
								_hover={{ bg: '#6a3daa', color: 'white' }}
								transition="background 0.2s"
								textDecoration="none"
							>
								{__('Upgrade Plan', 'everest-forms')}
							</Box>
						</Flex>
					</Box>
				</ModalContent>
			</Modal>

			{/* ── Template addon / choose modal ───────────────────────────────── */}
			<Modal isCentered isOpen={isOpen} onClose={onClose} size="md">
				<ModalOverlay bg="rgba(0,0,0,0.4)" backdropFilter="blur(2px)" />
				<ModalContent
					borderRadius="16px"
					p="0"
					overflow="hidden"
					boxShadow="0 20px 60px rgba(0,0,0,0.12)"
				>
					{/* Header */}
					<ModalHeader
						px="24px"
						pt="22px"
						pb="16px"
						borderBottom="1px solid #f1f5f9"
						p="0"
					>
						<Flex
							align="center"
							gap="12px"
							px="24px"
							pt="22px"
							pb="16px"
							borderBottom="1px solid #f1f5f9"
						>
							<Box
								w="36px"
								h="36px"
								borderRadius="8px"
								bg="rgba(117,69,187,0.1)"
								display="flex"
								alignItems="center"
								justifyContent="center"
								flexShrink={0}
							>
								<Icon as={LuSparkles} boxSize="16px" color="#7545BB" />
							</Box>
							<Box flex="1" minW="0">
								<Text
									fontSize="15px"
									fontWeight="600"
									color="#0e0e0e"
									m="0"
									noOfLines={1}
								>
									{previewTemplate?.title}
								</Text>
								<Text fontSize="12px" color="#9ca3af" m="0">
									{modalState === 'addons'
										? __('Required addons', 'everest-forms')
										: __('Ready to use', 'everest-forms')}
								</Text>
							</Box>
						</Flex>
					</ModalHeader>
					<ModalCloseButton
						top="14px"
						right="16px"
						size="sm"
						borderRadius="6px"
						_hover={{ bg: '#f1f5f9' }}
					/>

					<ModalBody px="24px" py="20px">
						{/* ── Addons state ── */}
						{modalState === 'addons' && (
							<VStack align="stretch" spacing="0">
								<HStack spacing="8px" mb="16px">
									<Box
										w="4px"
										h="4px"
										borderRadius="full"
										bg="#7545BB"
										flexShrink={0}
										mt="1px"
									/>
									<Text fontSize="13px" color="#6b7280" m="0" lineHeight="1.55">
										{__(
											'This template requires the following addons to be installed and activated:',
											'everest-forms',
										)}
									</Text>
								</HStack>
								<PluginStatus
									requiredPlugins={requiredPlugins}
									onActivateAndContinue={handleAddonsReady}
								/>
							</VStack>
						)}

						{/* ── Choose state ── */}
						{modalState === 'choose' && (
							<VStack align="stretch" spacing="16px">
								{/* Primary: Use this template */}
								<Box
									as="button"
									w="100%"
									h="44px"
									display="inline-flex"
									alignItems="center"
									justifyContent="center"
									gap="8px"
									borderRadius="10px"
									bg="#7545BB"
									color="white"
									fontSize="15px"
									fontWeight="600"
									border="none"
									cursor={isCreating ? 'not-allowed' : 'pointer'}
									opacity={isCreating ? 0.7 : 1}
									onClick={!isCreating ? handleCreateForm : undefined}
									transition="background 0.2s, opacity 0.2s"
									_hover={!isCreating ? { bg: '#6a3daa' } : {}}
								>
									{isCreating ? (
										<Box
											w="16px"
											h="16px"
											border="2px solid rgba(255,255,255,0.4)"
											borderTopColor="white"
											borderRadius="full"
											sx={{
												animation: 'spin 0.7s linear infinite',
												'@keyframes spin': {
													from: { transform: 'rotate(0deg)' },
													to: { transform: 'rotate(360deg)' },
												},
											}}
										/>
									) : (
										<Icon as={FiArrowRight} boxSize="4" />
									)}
									<Text margin="0" color="white">
										{isCreating
											? __('Creating…', 'everest-forms')
											: __('Use this template', 'everest-forms')}
									</Text>
								</Box>

								{/* OR divider */}
								<Flex align="center" gap="12px">
									<Box flex="1" h="1px" bg="#e2e8f0" />
									<Text
										fontSize="12px"
										fontWeight="500"
										color="#9ca3af"
										m="0"
										textTransform="uppercase"
										letterSpacing="0.05em"
									>
										{__('or', 'everest-forms')}
									</Text>
									<Box flex="1" h="1px" bg="#e2e8f0" />
								</Flex>

								{/* Secondary: Edit with AI */}
								<Box
									as="button"
									w="100%"
									display="inline-flex"
									alignItems="center"
									justifyContent="center"
									gap="8px"
									py="10px"
									borderRadius="10px"
									bg="transparent"
									border="none"
									color="#7545BB"
									fontSize="14px"
									fontWeight="500"
									disabled={!AI_ENABLED}
									title={
										AI_ENABLED
											? undefined
											: __('Not available on local sites', 'everest-forms')
									}
									cursor={
										!AI_ENABLED || aiCreatingSlug ? 'not-allowed' : 'pointer'
									}
									opacity={!AI_ENABLED ? 0.6 : aiCreatingSlug ? 0.7 : 1}
									onClick={() => {
										if (AI_ENABLED && !aiCreatingSlug && previewTemplate)
											handleEditWithAI(previewTemplate);
									}}
									transition="color 0.2s"
									_hover={AI_ENABLED ? { color: '#6a3daa' } : {}}
								>
									<Icon as={LuSparkles} boxSize="4" />
									<Text margin="0">
										{aiCreatingSlug
											? __('Loading…', 'everest-forms')
											: __('Edit with AI', 'everest-forms')}
									</Text>
								</Box>
							</VStack>
						)}
					</ModalBody>
				</ModalContent>
			</Modal>
		</Box>
	);
};

export default TemplateList;
