import {
	Badge,
	Box,
	Button,
	Center,
	Heading,
	HStack,
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
	SimpleGrid,
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
import { IoPlayOutline } from 'react-icons/io5';
import { MdOutlineRemoveRedEye } from 'react-icons/md';
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
	const closeModal = () => onClose();
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
			<HStack>
				<VStack 
					position="relative"
					bg="white"
					border="1px solid rgba(0, 0, 0, 0.08)"
					borderRadius="13px"
					p="26px 30px"
					mb="32px"
					overflow="hidden"
					display="flex"
					alignItems="flex-start"
					width="100%"
					sx={{
						'::before': {
						content: '""',
						position: "absolute",
						inset: "0",
						bg: "radial-gradient(ellipse 60% 120% at 100% 50%, rgba(96, 64, 240, 0.07) 0%, transparent 70%), radial-gradient(ellipse 40% 80% at 80% 20%, rgba(61, 126, 245, 0.06) 0%, transparent 60%)",
						pointerEvents: "none",
						},
					}}
					>
					<Text 
						display="inline-block"
						alignItems="center"
						gap="6px"
						bg="rgba(118, 64, 240, 0.08)"
						border="1px solid rgba(155, 64, 240, 0.2)"
						borderRadius="20px"
						p="4px 12px"
						fontSize="12px"
						fontWeight="medium"
						// color="#6040f0"
						color="#6b46c1"
						letterSpacing="0.23px"
						m="0 0 12px"
					>✦ {__('Ready-made templates', 'everest-forms')}</Text>
					<Heading 
						fontSize="26px"
						fontWeight="700"
						letterSpacing="-0.5px"
						color="#0f0f1a"
						lineHeight="1.2"
						margin="0 0 8px"
					>{__('Build faster with beautiful templates', 'everest-forms')}</Heading>
					<Text 
						fontSize="14px"
						// color="#6b6b85"
						color="gray.600"
						lineHeight="1.6"
						maxWidth="480px"
						m="0"
					>{__('Pick from 49 professionally designed form templates. Customize, deploy, and start collecting responses in minutes.', 'everest-forms')}</Text>
				</VStack>
			</HStack>
			
			<Heading
				as="h3"
				fontSize="18px"
				lineHeight="26px"
				letterSpacing="0.2px"
				fontWeight="semibold"
				m="0px 0px 32px"
				color="#26262E"
				borderBottom="1px solid #e1e1e1"
				paddingBottom="12px"
			>
				{selectedCategory}
			</Heading>
			{templates?.length ? (
				<SimpleGrid gridTemplateColumns="repeat(auto-fill, minmax(280px, 1fr))" spacing={6}>
					{templates.map((template) => (
						<Box
							key={template.slug}
							borderWidth="1px"
							borderRadius="13px"
							borderColor="#e1e1e1"
							overflow="hidden"
							position="relative"
							onMouseOver={() => setHoverCardId(template.id)}
							onMouseLeave={() => setHoverCardId(null)}
							textAlign="center"
							bg="white"
							p={0}
							transition="all .3s"
							_hover={{
								boxShadow: '0px 5px 24px rgba(58, 34, 93, 0.12)',
								borderColor: 'transparent',
								'::before': {
									content: '""',
									position: 'absolute',
									top: 0,
									left: 0,
									width: '100%',
									height: '250px',
									// bg: 'rgba(0, 0, 0, 0.4)',
									bg: "#181818",
									opacity: ".5",
									zIndex: 1,
								},
								'& > div > .template-title': {
									color: '#7545BB',
								},
							}}
						>
							<Center mb={0}>
								<Box
									position="relative"
									width="100%"
									display="flex"
									justifyContent="center"
									alignItems="center"
									// bg="#F7F4FB"
									bg="#f5f5f5"
									pt="80px"
									height="250px"
									borderRadius="6px 6px 0px 0px"
									overflow="hidden"
									transition="all .3s"
									borderBottom="1px solid #e1e1e1"
								>
									<Image
										boxShadow="0px 4px 24px rgba(10, 10, 10, 0.15)"
										src={template.imageUrl}
										alt={template.title}
										objectFit="contain"
										borderRadius="7px"
									/>

									{template.isPro && (
										<Badge
											bg="#4BCE61"
											// color="white"
											position="absolute"
											bottom="12px"
											right="12px"
											// borderRadius="6px"
											// fontSize="12px"
											// p="2px 6px"
											// textTransform="capitalize"
											zIndex="2"
											border="1px solid #ff8c39"
											background="#fffaf5"
											color="#ff8c39"
											fontWeight="semibold"
											textTransform="uppercase"
											p="4px 8px"
											borderRadius="4px"
											fontSize="11px"
										>
											{__('Pro', 'everest-forms')}
										</Badge>
									)}

									{/* Hover Buttons */}
									{hoverCardId === template.id && (
										<VStack
											spacing={3}
											flexDirection="row"
											position="absolute"
											top="50%"
											left="50%"
											transform="translate(-50%, -50%)"
											zIndex={2}
										>
											<Button
												borderRadius="4px"
												fontSize="14px"
												lineHeight="24px"
												fontWeight="medium"
												// leftIcon={<IoPlayOutline />}
												colorScheme="purple"
												onClick={() => handleTemplateClick(template)}
											>
												{__('Use Template', 'everest-forms')}
											</Button>
											{template.preview_link && (
												<Button
													borderRadius="4px"
													fontSize="14px"
													lineHeight="24px"
													fontWeight="medium"
													// leftIcon={<MdOutlineRemoveRedEye />}
													color="#0f0f1a"
													background="#f4f4f4"
													border="1px solid rgba(0,0,0,0.12)"
													// variant="outline"
													onClick={() =>
														window.open(template.preview_link, '_blank')
													}
													_hover={{ color: 'black', bg: 'white' }}
												>
													{__('Preview', 'everest-forms')}
												</Button>
											)}
										</VStack>
									)}
								</Box>
							</Center>

							{hoverCardId === template.id && (
								<Box
									as="button"
									onClick={() => handleFavoriteToggle(template.slug)}
									aria-label={`Toggle favorite for ${template.title}`}
									position="absolute"
									top={3}
									right={3}
									zIndex={3}
									bg="transparent"
									border="none"
									display="flex"
									alignItems="center"
									justifyContent="center"
									_hover={{ color: 'red.600' }}
								>
									<Icon
										as={
											favorites.includes(template.slug) ? FaHeart : FaRegHeart
										}
										boxSize={5}
										color={favorites.includes(template.slug) ? 'red' : 'white'}
									/>
								</Box>
							)}

							<VStack padding="16px">
								<Heading
									className="template-title"
									width="100%"
									textAlign="left"
									fontWeight="bold"
									fontSize="16px"
									margin="0px"
								>
									{template.title}
								</Heading>
								<Text
									textAlign="left"
									margin="0px"
									fontSize="14px"
									fontWeight="400"
									color="gray.600"
								>
									{template.description}
								</Text>
							</VStack>
						</Box>
					))}
				</SimpleGrid>
			) : (
				<Box
					display="flex"
					flexDirection="column"
					justifyContent="center"
					alignItems="center"
					height="80vh"
					width="100%"
				>
					<Image
						src={notFoundImage}
						alt={__('Not Found', 'everest-forms')}
						boxSize="300px"
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
