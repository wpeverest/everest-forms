import {
	Box,
	Button,
	Center,
	Heading,
	Icon,
	Image,
	Input,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	Text,
	useDisclosure,
	useToast,
	VStack,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { FaHeart, FaRegHeart } from 'react-icons/fa';
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
	onCreateWithAI?: () => void;
}

const { restURL, security } = templatesScriptData;

const LockIcon = (props) => (
	<Icon viewBox="0 0 24 24" {...props}>
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 54 54">
			<rect width="54" height="54" fill="#FA5252" rx="27" />
			<path
				fill="#fff"
				d="M34 22.334h-1.166v-1.167A5.84 5.84 0 0 0 27 15.334a5.84 5.84 0 0 0-5.833 5.833v1.167H20a2.333 2.333 0 0 0-2.333 2.333v11.667A2.333 2.333 0 0 0 20 38.667h14a2.333 2.333 0 0 0 2.334-2.333V24.667A2.333 2.333 0 0 0 34 22.334Zm-10.5-1.167c0-1.93 1.57-3.5 3.5-3.5s3.5 1.57 3.5 3.5v1.167h-7v-1.167Zm4.667 10.177v2.657h-2.333v-2.657a2.323 2.323 0 0 1-.484-3.66 2.333 2.333 0 0 1 3.984 1.65c0 .861-.473 1.605-1.167 2.01Z"
			/>
		</svg>
	</Icon>
);

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
	const [formTemplateName, setFormTemplateName] = useState<string>('');
	const [selectedTemplateSlug, setSelectedTemplateSlug] = useState<string>('');
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [hoverCardId, setHoverCardId] = useState<number | null>(null);
	const [favorites, setFavorites] = useState<string[]>([]);
	const toast = useToast();
	const queryClient = useQueryClient();
	const [isPluginModalOpen, setIsPluginModalOpen] = useState(false);

	const openModal = () => onOpen();
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
		const requiredPlugins = template.addons ? Object.keys(template.addons) : [];

		try {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/plugin/upgrade`,
				method: 'POST',
				body: JSON.stringify({ requiredPlugins }),
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': security,
				},
			});

			const { plugin_status } = response as {
				plugin_status: Record<string, string>;
			};

			if (!plugin_status) {
				setFormTemplateName(template.title);
				openPluginModal();
				return;
			}

			setSelectedTemplateSlug(template.slug);
			setPreviewTemplate(template);
			setFormTemplateName(template.title);
			openModal();
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

	const handleFormTemplateSave = async () => {
		if (!formTemplateName) {
			toast({
				title: __('Form name required', 'everest-forms'),
				description: __(
					'Please provide a name for your form.',
					'everest-forms',
				),
				status: 'warning',
				position: 'bottom-right',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
			return;
		}

		try {
			const response = (await apiFetch({
				path: `${restURL}everest-forms/v1/templates/create`,
				method: 'POST',
				body: JSON.stringify({
					title: formTemplateName,
					slug: selectedTemplateSlug,
				}),
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': security,
				},
			})) as CreateTemplateResponse;

			if (response.success && response.data) {
				window.location.href = response.data.redirect;
			} else {
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

	const addonEntries = previewTemplate?.addons
		? Object.entries(previewTemplate.addons).map(([key, value]) => ({
			key,
			value,
		}))
		: [];

	const requiredPlugins = addonEntries.map((addon) => ({
		key: addon.key,
		value: addon.value,
	}));

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
												color="#FF8C39"
												bg="white"
												border="1.5px solid #FFD8B8"
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
											bg={favorites.includes(template.slug) ? 'white' : 'rgba(255,255,255,0.15)'}
											backdropFilter="blur(4px)"
											color={favorites.includes(template.slug) ? 'red.500' : 'white'}
											border="none"
											cursor="pointer"
											_hover={{
												bg: 'white',
												color: 'red.500',
											}}
											transition="all 0.2s"
										>
											<Icon
												as={favorites.includes(template.slug) ? FaHeart : FaRegHeart}
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
											transform={isHovered ? 'translateY(0)' : 'translateY(8px)'}
											transition="all 0.3s"
											onClick={() => handleTemplateClick(template)}
										>
											{__('Use this template', 'everest-forms')}
										</Button>

										{/* Edit with AI button */}
										<Button
											w="170px"
											h="36px"
											borderRadius="8px"
											bg="rgba(255,255,255,0.95)"
											color="#0e0e0e"
											fontSize="14px"
											fontWeight="500"
											boxShadow="0 1px 3px rgba(0,0,0,0.1)"
											leftIcon={<Icon as={LuSparkles} boxSize="3.5" />}
											_hover={{ bg: 'white', color: '#7545BB' }}
											opacity={isHovered ? 1 : 0}
											transform={isHovered ? 'translateY(0)' : 'translateY(8px)'}
											transition="all 0.3s 0.06s"
											onClick={() => onCreateWithAI && onCreateWithAI()}
										>
											{__('Edit with AI', 'everest-forms')}
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
												transform={isHovered ? 'translateY(0)' : 'translateY(8px)'}
												transition="all 0.3s 0.12s"
												onClick={() => window.open(template.preview_link, '_blank')}
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

			{/* Plugin required modal */}
			<Modal
				isCentered={true}
				isOpen={isPluginModalOpen}
				onClose={closePluginModal}
				size="lg"
			>
				<ModalOverlay />
				<ModalContent borderRadius="8px" padding="20px">
					<ModalHeader
						padding="0px"
						textAlign="center"
						fontSize="20px"
						lineHeight="28px"
						color="#26262E"
					>
						<LockIcon boxSize={10} />
						<Heading
							as="h2"
							margin="10px 0px 0px 0px"
							fontSize="20px"
							lineHeight="28px"
							fontWeight="bold"
						>
							{sprintf(
								__('%s is a Premium Template', 'everest-forms'),
								formTemplateName,
							)}
						</Heading>
					</ModalHeader>
					<ModalCloseButton top="12px" right="12px" />
					<ModalBody padding="0px" marginTop="16px" textAlign="center">
						<Text margin="0px" fontSize="16px" lineHeight="24px" mb="20px">
							{__(
								'This template requires premium addons. Please upgrade to the Premium to unlock all these awesome templates.',
								'everest-forms',
							)}
						</Text>
					</ModalBody>
					<ModalFooter justifyContent="flex-end" padding="0px">
						<Button variant="ghost" onClick={closePluginModal}>
							{__('OK', 'everest-forms')}
						</Button>
						<a
							href="https://everestforms.net/upgrade/?utm_medium=evf-template-page&utm_source=evf-free&utm_campaign=template-premium-popup"
							target="_blank"
							rel="noopener noreferrer"
							style={{ width: 'inherit' }}
						>
							<Button colorScheme="blue" ml={3}>
								{__('Upgrade Plan', 'everest-forms')}
							</Button>
						</a>
					</ModalFooter>
				</ModalContent>
			</Modal>

			{/* Template name + plugin status modal */}
			<Modal isCentered isOpen={isOpen} onClose={onClose} size="xl">
				<ModalOverlay />
				<ModalContent borderRadius="8px" padding="40px">
					<ModalHeader
						padding="0px"
						textAlign="left"
						fontSize="20px"
						lineHeight="28px"
						color="#26262E"
					>
						{__(
							'Uplift your form experience to the next level.',
							'everest-forms',
						)}
					</ModalHeader>
					<ModalCloseButton top="12px" right="12px" />
					<ModalBody padding="0px" marginTop="16px">
						<Box mb="20px" padding="0px">
							<Text margin="0px 0px 6px" fontSize="16px" lineHeight="29px">
								{__('Give it a name', 'everest-forms')}
							</Text>
							<Input
								width={'full'}
								value={formTemplateName}
								onChange={(e) => setFormTemplateName(e.target.value)}
								placeholder="Give it a name."
								size="md"
								_focus={{
									borderColor: '#7545BB',
									outline: 'none',
									boxShadow: 'none',
								}}
							/>
						</Box>

						<Box overflow="hidden" mb="0px" padding="0px">
							<PluginStatus
								requiredPlugins={requiredPlugins}
								onActivateAndContinue={handleFormTemplateSave}
							/>
						</Box>
					</ModalBody>
				</ModalContent>
			</Modal>
		</Box>
	);
};

export default TemplateList;
