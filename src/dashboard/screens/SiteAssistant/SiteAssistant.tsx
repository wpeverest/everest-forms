/**
 *  External Dependencies
 */
import {
	Alert,
	AlertIcon,
	Box,
	Button,
	Collapse,
	Container,
	Divider,
	Flex,
	FormControl,
	Grid,
	Heading,
	HStack,
	Icon,
	IconButton,
	Image,
	Input,
	Link,
	Stack,
	Text,
	useToast,
} from '@chakra-ui/react';
import {
	useMutation,
	useQueryClient,
	UseQueryResult,
} from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { BiChevronDown, BiChevronUp } from 'react-icons/bi';

/**
 *  Internal Dependencies
 */
import {
	Bulb,
	DocsLines,
	Headphones,
	Star,
	Team,
	Video,
} from '../../components/Icon/Icon';
import applicationFormIcon from '../../images/icons/application-form.png';
import businessFormIcon from '../../images/icons/business-form.png';
import educationFormIcon from '../../images/icons/education-form.png';
import feedbackFormIcon from '../../images/icons/feedback-form.png';
import healthcareFormIcon from '../../images/icons/healthcare-form.png';
import informationFormIcon from '../../images/icons/infromation-form.png';
import SiteAssistantSkeleton from '../../skeleton/SiteAssistantSkeleton';
import {
	docURL,
	facebookGroup,
	featureRequestURL,
	submitReviewUrl,
	ticketUrl,
} from '../../utils/constants';

interface SiteAssistantData {
	skipped_steps: string[];
	test_email_sent: boolean;
	has_forms: boolean;
	email_sent?: boolean;
	last_form_email_status?: 'success' | 'failed' | '';
	is_smtp_active?: boolean;
	is_smart_smtp_installed?: boolean;
	is_smart_smtp_active?: boolean;
}

interface ApiResponse {
	success: boolean;
	data: SiteAssistantData;
}

interface StepConfig {
	id: string;
	title: string;
	isCompleted: (data: SiteAssistantData | undefined) => boolean;
	renderContent: () => JSX.Element;
}

interface Props {
	siteAssistantQuery: UseQueryResult<any, any>;
}

const SiteAssistant: React.FC<Props> = ({ siteAssistantQuery }) => {
	const dashboardData =
		typeof _EVF_DASHBOARD_ !== 'undefined' ? _EVF_DASHBOARD_ : {};
	const {
		utmCampaign,
		evfRestApiNonce,
		restURL,
		adminEmail,
		adminURL,
		isPro,
		ajaxURL,
		smartSmtpNonce,
	} = dashboardData;

	const toast = useToast();
	const queryClient = useQueryClient();

	const [open, setOpen] = useState<Record<string, boolean>>({});
	const [testEmail, setTestEmail] = useState<string>(adminEmail || '');
	const [isInstallingSmtp, setIsInstallingSmtp] = useState(false);
	const [smtpInstallError, setSmtpInstallError] = useState<string | null>(null);

	const toggleOpen = useCallback((id: string) => {
		setOpen((prev) => ({ ...prev, [id]: !prev[id] }));
	}, []);

	const handleConfigureRecaptcha = () => {
		const settingsURL =
			window._EVF_DASHBOARD_?.settingsURL ||
			`${window.location.origin}/wp-admin/admin.php?page=evf-settings`;

		window.open(`${settingsURL}&tab=recaptcha`, '_blank');
	};

	const handleOtherSpamFeatures = () => {
		const settingsURL =
			window._EVF_DASHBOARD_?.settingsURL ||
			`${window.location.origin}/wp-admin/admin.php?page=evf-settings`;

		window.open(`${settingsURL}&tab=recaptcha`, '_blank');
	};

	const { data: siteData, isLoading, error } = siteAssistantQuery;

	const skipSpamProtectionMutation = useMutation({
		mutationFn: async () => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/site-assistant/skip-setup`,
				method: 'POST',
				headers: {
					'X-WP-Nonce': evfRestApiNonce,
				},
				data: {
					step: 'spam_protection',
				},
			});
			return response as ApiResponse;
		},
		onSuccess: (data) => {
			queryClient.setQueryData(['siteAssistant'], data);
			toast({
				title: __('Success', 'everest-forms'),
				description: __('Spam protection setup skipped.', 'everest-forms'),
				status: 'success',
				duration: 3000,
				isClosable: true,
			});
		},
		onError: (error: any) => {
			console.error('Error skipping spam protection:', error);
			toast({
				title: __('Error', 'everest-forms'),
				description:
					error?.message ||
					__('Failed to skip spam protection setup.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		},
	});

	const sendTestEmailMutation = useMutation({
		mutationFn: async (email: string) => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/site-assistant/test-email`,
				method: 'POST',
				headers: {
					'X-WP-Nonce': evfRestApiNonce,
				},
				data: {
					email: email,
				},
			});
			return response as ApiResponse;
		},
		onSuccess: (data) => {
			queryClient.setQueryData(['siteAssistant'], (old: ApiResponse | undefined) => {
				const prev = old?.data;
				const incoming = data.data;
				const merged: SiteAssistantData = {
					...prev,
					...incoming,
					skipped_steps: incoming?.skipped_steps ?? prev?.skipped_steps ?? [],
					test_email_sent:
						incoming?.test_email_sent ?? prev?.test_email_sent ?? false,
					has_forms: incoming?.has_forms ?? prev?.has_forms ?? false,
				};
				const next: ApiResponse = {
					success: data.success ?? old?.success ?? true,
					data: merged,
				};
				return next;
			});
			if (data?.data?.email_sent) {
				toast({
					title: __('Success', 'everest-forms'),
					description: __(
						"Test email sent successfully. Didn't receive it? Please check your Spam or Junk folder.",
						'everest-forms',
					),
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
			} else {
				toast({
					title: __('Error', 'everest-forms'),
					description: __(
						'We could not send the test email. Please check your mail configuration.',
						'everest-forms',
					),
					status: 'error',
					duration: 5000,
					isClosable: true,
				});
			}
		},
		onError: (error: any) => {
			console.error('Error sending test email:', error);
			toast({
				title: __('Error', 'everest-forms'),
				description:
					error?.message || __('Failed to send test email.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		},
	});

	const skipSendTestEmailMutation = useMutation({
		mutationFn: async () => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/site-assistant/skip-setup`,
				method: 'POST',
				headers: {
					'X-WP-Nonce': evfRestApiNonce,
				},
				data: {
					step: 'send_test_email',
				},
			});
			return response as ApiResponse;
		},
		onSuccess: (data) => {
			queryClient.setQueryData(['siteAssistant'], data);
			toast({
				title: __('Success', 'everest-forms'),
				description: __('Send test email step skipped.', 'everest-forms'),
				status: 'success',
				duration: 3000,
				isClosable: true,
			});
		},
		onError: (error: any) => {
			console.error('Error skipping send test email:', error);
			toast({
				title: __('Error', 'everest-forms'),
				description:
					error?.message ||
					__('Failed to skip send test email step.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		},
	});

	const assistantData = siteData?.data;
	const mutationData = sendTestEmailMutation.data?.data;

	const resolvedSmtpInstalled =
		mutationData?.is_smart_smtp_installed ??
		assistantData?.is_smart_smtp_installed;
	const resolvedSmtpActive =
		mutationData?.is_smtp_active ?? assistantData?.is_smtp_active;
	const resolvedSmartSmtpPluginActive =
		mutationData?.is_smart_smtp_active ?? assistantData?.is_smart_smtp_active;
	const emailSentFromMutation = mutationData?.email_sent;
	const testEmailSent =
		emailSentFromMutation ?? assistantData?.test_email_sent ?? false;
	const resolvedLastFormEmailStatus =
		mutationData?.last_form_email_status ??
		assistantData?.last_form_email_status ??
		'';
	const hasSuccessfulFormDelivery = resolvedLastFormEmailStatus === 'success';

	// POST result is merged into the siteAssistant query cache. Mutation state can
	// reset (remount, minified bundle timing) before the next paint, so do not rely
	// on `sendTestEmailMutation.isSuccess` alone — `email_sent === false` on cached
	// data matches the REST failure payload and the error toast branch.
	const testEmailSendExplicitlyFailed =
		(sendTestEmailMutation.isSuccess &&
			mutationData != null &&
			mutationData.email_sent !== true &&
			mutationData.test_email_sent !== true) ||
		assistantData?.email_sent === false;

	const emailStatus: 'idle' | 'sent' | 'failed' = sendTestEmailMutation.isError
		? 'failed'
		: testEmailSent || hasSuccessfulFormDelivery
			? 'sent'
			: testEmailSendExplicitlyFailed
				? 'failed'
				: resolvedLastFormEmailStatus === 'failed'
					? 'failed'
					: 'idle';

	const handleInstallSmtpPlugin = async () => {
		setIsInstallingSmtp(true);
		setSmtpInstallError(null);
		try {
			const normalizedAdminUrl = (adminURL || '').endsWith('/')
				? (adminURL || '').slice(0, -1)
				: adminURL || '';
			const ajaxEndpoint = ajaxURL || `${normalizedAdminUrl}/admin-ajax.php`;
			const formData = new FormData();
			formData.append('action', 'everest_forms_install_and_activate_smart_smtp');
			formData.append('security', smartSmtpNonce || '');
			const response = await fetch(ajaxEndpoint, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			});
			const result = await response.json();
			if (result.success) {
				queryClient.setQueryData(['siteAssistant'], (old: ApiResponse | undefined) => {
					const prev = old?.data;
					const merged: SiteAssistantData = {
						...prev,
						is_smart_smtp_installed: true,
						is_smart_smtp_active: true,
						skipped_steps: prev?.skipped_steps ?? [],
						test_email_sent: prev?.test_email_sent ?? false,
						has_forms: prev?.has_forms ?? false,
					};
					const next: ApiResponse = {
						success: old?.success ?? true,
						data: merged,
					};
					return next;
				});
				await queryClient.invalidateQueries({ queryKey: ['siteAssistant'] });
				if (result.data?.redirection_url) {
					window.location.href = result.data.redirection_url;
				}
			} else {
				setSmtpInstallError(
					result.data?.message ||
						__('Installation failed. Please try manually.', 'everest-forms'),
				);
			}
		} catch {
			setSmtpInstallError(
				__('Installation failed. Please try manually.', 'everest-forms'),
			);
		} finally {
			setIsInstallingSmtp(false);
		}
	};

	const handleSkipSpamProtection = () => {
		skipSpamProtectionMutation.mutate();
	};

	const handleSendTestEmail = () => {
		if (!testEmail || !testEmail.includes('@')) {
			toast({
				title: __('Invalid Email', 'everest-forms'),
				description: __('Please enter a valid email address.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
			return;
		}
		sendTestEmailMutation.mutate(testEmail);
	};

	const formCategories = [
		{
			name: __('Application Form', 'everest-forms'),
			count: 15,
			icon: applicationFormIcon,
			slug: 'application',
		},
		{
			name: __('Business Form', 'everest-forms'),
			count: 2,
			icon: businessFormIcon,
			slug: 'bussiness',
		},
		{
			name: __('Education Form', 'everest-forms'),
			count: 2,
			icon: educationFormIcon,
			slug: 'education',
		},
		{
			name: __('Information Form', 'everest-forms'),
			count: 7,
			icon: informationFormIcon,
			slug: 'information',
		},
		{
			name: __('Health Care Form', 'everest-forms'),
			count: 1,
			icon: healthcareFormIcon,
			slug: 'healthcare',
		},
		{
			name: __('Feedback Form', 'everest-forms'),
			count: 8,
			icon: feedbackFormIcon,
			slug: 'feedback',
		},
	];

	const handleCreateNewForm = () => {
		createBlankFormMutation.mutate(__('Untitled', 'everest-forms'));
	};

	const handleViewAllTemplates = () => {
		const templatesURL = `${adminURL}admin.php?page=evf-builder&create-form=1`;
		window.location.href = templatesURL;
	};

	const handleCategoryClick = (categorySlug: string) => {
		const categoryURL = `${adminURL}admin.php?page=evf-builder&create-form=1&evf_template_category=${categorySlug}`;
		window.location.href = categoryURL;
	};

	const skipCreateFormMutation = useMutation({
		mutationFn: async () => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/site-assistant/skip-setup`,
				method: 'POST',
				headers: {
					'X-WP-Nonce': evfRestApiNonce,
				},
				data: {
					step: 'create_form',
				},
			});
			return response as ApiResponse;
		},
		onSuccess: (data) => {
			queryClient.setQueryData(['siteAssistant'], data);
			toast({
				title: __('Success', 'everest-forms'),
				description: __('Form creation step skipped.', 'everest-forms'),
				status: 'success',
				duration: 3000,
				isClosable: true,
			});
		},
		onError: (error: any) => {
			console.error('Error skipping create form:', error);
			toast({
				title: __('Error', 'everest-forms'),
				description:
					error?.message ||
					__('Failed to skip create form step.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		},
	});

	const handleSkipCreateForm = () => {
		skipCreateFormMutation.mutate();
	};

	const createBlankFormMutation = useMutation({
		mutationFn: async (formName: string) => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/templates/create`,
				method: 'POST',
				body: JSON.stringify({
					title: formName,
					slug: 'blank_form',
				}),
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': evfRestApiNonce,
				},
			});
			return response as { success: boolean; data: { redirect: string } };
		},
		onSuccess: (response) => {
			if (response.success && response.data) {
				window.location.href = response.data.redirect;
			}
		},
		onError: (error: any) => {
			console.error('Error creating blank form:', error);
			toast({
				title: __('Error', 'everest-forms'),
				description:
					error?.message || __('Failed to create blank form.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		},
	});

	const renderCreateFormContent = () => (
		<Stack
			p="6"
			gap="5"
			bgColor="white"
			borderRadius="base"
			border="1px"
			borderColor="gray.100"
			overflow="hidden"
			width="100%"
			minWidth="0"
		>
			<HStack
				justify={'space-between'}
				cursor="pointer"
				onClick={() => toggleOpen('createForm')}
			>
				<Heading as="h3" fontSize="19px" fontWeight="600" color="grey.500">
					{__('Start Creating Forms', 'everest-forms')}
				</Heading>
				<IconButton
					aria-label={'createForm'}
					icon={
						<Icon
							as={open?.createForm ? BiChevronUp : BiChevronDown}
							fontSize="2xl"
							fill={open?.createForm ? 'primary.500' : 'black'}
						/>
					}
					cursor={'pointer'}
					fontSize={'xl'}
					size="sm"
					boxShadow="none"
					borderRadius="base"
					variant={open?.createForm ? 'solid' : 'link'}
					border="none"
					bg={open?.createForm ? 'gray.100' : 'transparent'}
					_hover={{
						bg: open?.createForm ? 'gray.100' : 'inherit',
					}}
					pointerEvents="none"
				/>
			</HStack>
			<Collapse in={open?.createForm}>
				<Stack gap={6}>
					<Divider color={'gray.200'} />
					<Text
						fontSize="14px"
						fontWeight="400"
						color="grey.350"
						lineHeight="19.3px"
					>
						{__(
							'To get started quickly, you can create a new form from scratch or choose from the categories.',
							'everest-forms',
						)}
					</Text>
					<Button
						width={'fit-content'}
						colorScheme="primary"
						onClick={handleCreateNewForm}
						leftIcon={<Text fontSize="lg">+</Text>}
						isLoading={createBlankFormMutation.isLoading}
						loadingText={__('Creating...', 'everest-forms')}
					>
						{__('Create New Form', 'everest-forms')}
					</Button>
					<Box>
						<HStack justify="space-between" mb={4}>
							<Text
								fontSize="12px"
								fontWeight="600"
								color="grey.300"
								textTransform="uppercase"
								letterSpacing="0.5px"
							>
								{__('CATEGORIES', 'everest-forms')}
							</Text>
							<Link
								color="primary.500"
								_hover={{
									color: 'primary.600',
								}}
								textDecoration="underline"
								onClick={handleViewAllTemplates}
								cursor="pointer"
								fontSize="sm"
							>
								{__('View All', 'everest-forms')}
							</Link>
						</HStack>
						<Grid
							templateColumns={{
								base: '1fr',
								md: 'repeat(2, 1fr)',
								lg: 'repeat(3, 1fr)',
							}}
							gap={5}
						>
							{formCategories.map((category, index) => (
								<Box
									key={index}
									px={4}
									py={3}
									border="1px"
									borderColor="gray.200"
									borderRadius="base"
									_hover={{
										borderColor: 'primary.500',
										cursor: 'pointer',
									}}
									onClick={() => handleCategoryClick(category.slug)}
								>
									<HStack spacing={3}>
										<Box p={3} bg={'primary.15'} rounded={'base'}>
											<Image
												src={category.icon}
												alt={category.name}
												boxSize="20px"
												objectFit="contain"
											/>
										</Box>
										<Flex gap={1} direction={'column'}>
											<Text
												fontSize="14px"
												fontWeight="600"
												color="grey.400"
												lineHeight="150%"
											>
												{category.name}
											</Text>
											<Text
												fontSize="12px"
												fontWeight="400"
												color="grey.175"
												lineHeight="150%"
												textTransform="capitalize"
											>
												{category.count}{' '}
												{category.count > 1
													? __('Templates', 'everest-forms')
													: __('Template', 'everest-forms')}
											</Text>
										</Flex>
									</HStack>
								</Box>
							))}
						</Grid>
					</Box>
					<Flex justify="flex-end">
						<Link
							fontSize="13px"
							fontWeight="400"
							color="grey.150"
							letterSpacing="0.2px"
							onClick={handleSkipCreateForm}
							cursor="pointer"
							opacity={skipCreateFormMutation.isLoading ? 0.6 : 1}
							pointerEvents={skipCreateFormMutation.isLoading ? 'none' : 'auto'}
							textDecor={'underline'}
						>
							{skipCreateFormMutation.isLoading
								? __('Skipping...', 'everest-forms')
								: __('Skip Setup', 'everest-forms')}
						</Link>
					</Flex>
				</Stack>
			</Collapse>
		</Stack>
	);

	const renderSendTestEmailContent = () => (
		<Stack
			p="6"
			gap="5"
			bgColor="white"
			borderRadius="base"
			border="1px"
			borderColor="gray.100"
			overflow="hidden"
			width="100%"
			minWidth="0"
			borderTopWidth="2px"
			borderTopColor="#7545BB"
			borderStyle="solid"
		>
			<HStack
				justify={'space-between'}
				cursor="pointer"
				onClick={() => toggleOpen('sendTestEmail')}
			>
				<HStack alignItems="center">
					<Heading as="h3" fontSize="19px" fontWeight="600" color="grey.500">
						{__('Send Test Email', 'everest-forms')}
						<Box as="span" fontSize="inherit" fontWeight="inherit" color="inherit">
							{' '}
							<Box
								as="span"
								display="inline-flex"
								alignItems="center"
								justifyContent="center"
								w="8px"
								h="8px"
								bg="7545BB"
								borderRadius="full"
								lineHeight="1"
								verticalAlign="middle"
								transform="translateY(-1px)"
							>
							</Box>
						</Box>
					</Heading>
				</HStack>
				<IconButton
					aria-label={'sendTestEmail'}
					icon={
						<Icon
							as={open?.sendTestEmail ? BiChevronUp : BiChevronDown}
							fontSize="2xl"
							fill={open?.sendTestEmail ? 'primary.500' : 'black'}
						/>
					}
					cursor={'pointer'}
					fontSize={'xl'}
					size="sm"
					boxShadow="none"
					borderRadius="base"
					variant={open?.sendTestEmail ? 'solid' : 'link'}
					border="none"
					bg={open?.sendTestEmail ? 'gray.100' : 'transparent'}
					_hover={{
						bg: open?.sendTestEmail ? 'gray.100' : 'inherit',
					}}
					pointerEvents="none"
				/>
			</HStack>
			<Collapse in={open?.sendTestEmail}>
				<Stack gap={5} minWidth="0" width="100%">
					<Divider color={'gray.200'} />
					{emailStatus === 'sent' && (
						<Alert
							status="success"
							borderRadius="md"
							border="1px"
							borderColor="#389E2E !important"
							borderStyle="solid"
							fontSize="sm"
							sx={{ backgroundColor: '#4CC74114 !important' }}
						>
							<AlertIcon />
							<Text fontSize="sm" color="#389E2E!important">
								{__(
									'Test Email Sent Successfully - Your email delivery is working. Form notifications should reach your inbox reliably.',
									'everest-forms',
								)}
							</Text>
						</Alert>
					)}
					{(emailStatus === 'failed') &&
						!resolvedSmartSmtpPluginActive && (
							<Box
								p={4}
								border="1px"
								borderColor="gray.200"
								borderRadius="md"
								bg="#007BFF0D"
							>
								<Flex justify="space-between" align="center" gap={4}>
									<HStack align="flex-start" spacing={3} flex={1}>
										<Box
											p={2}
											bg="primary.15"
											borderRadius="md"
											flexShrink={0}
											mt="1px"
											border="1px"
											borderColor="#EBEBEB"
										>
											<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
												<path d="M10.3588 8.88574H28.2297V21.0705L10.3588 21.0705V8.88574Z" fill="url(#paint0_linear_10119_3622)" stroke="url(#paint1_linear_10119_3622)" stroke-width="0.812315"/>
												<path d="M28.6358 21.4769L9.95259 8.47986V21.4769H28.6358Z" fill="url(#paint2_linear_10119_3622)"/>
												<path d="M19.8124 15.1899L27.8234 8.48005L19.8124 3.30237L10.7648 8.48005L19.8124 15.1899Z" fill="url(#paint3_linear_10119_3622)" stroke="url(#paint4_linear_10119_3622)" stroke-width="1.05601"/>
												<path d="M3.06884 16.2518L20.3557 16.2517L20.5126 27.9754H3.06884V16.2518Z" fill="url(#paint5_linear_10119_3622)" stroke="url(#paint6_linear_10119_3622)" stroke-width="0.812315"/>
												<path d="M19.3879 16.1967H3.9625L11.3976 21.0705L19.3879 16.1967Z" fill="#0062CC" stroke="#0062CC" stroke-width="0.812315"/>
												<path d="M27.896 9.03432L12.5214 23.2993L7.55934 19.2333L2.69446 15.8498L11.5771 20.6643L17.4664 16.6027L23.3557 12.5411L27.896 9.03432Z" fill="white"/>
												<defs>
													<linearGradient id="paint0_linear_10119_3622" x1="19.2942" y1="8.88574" x2="19.2942" y2="21.0705" gradientUnits="userSpaceOnUse">
														<stop offset="0.389" stop-color="#3395FF"/>
														<stop offset="1" stop-color="#004A99"/>
													</linearGradient>
													<linearGradient id="paint1_linear_10119_3622" x1="19.2942" y1="8.88574" x2="19.2942" y2="21.0705" gradientUnits="userSpaceOnUse">
														<stop stop-color="#3396FF"/>
														<stop offset="1" stop-color="#004A99"/>
													</linearGradient>
													<linearGradient id="paint2_linear_10119_3622" x1="9.95259" y1="8.47986" x2="19.6859" y2="20.3432" gradientUnits="userSpaceOnUse">
														<stop stop-color="#004A99" stop-opacity="0.76"/>
														<stop offset="0.974" stop-color="#3395FF"/>
													</linearGradient>
													<linearGradient id="paint3_linear_10119_3622" x1="19.2941" y1="1.16922" x2="19.2941" y2="14.9786" gradientUnits="userSpaceOnUse">
														<stop offset="0.404" stop-color="#3396FF"/>
														<stop offset="0.759" stop-color="#004A99"/>
													</linearGradient>
													<linearGradient id="paint4_linear_10119_3622" x1="19.2941" y1="1.16922" x2="19.2941" y2="14.9786" gradientUnits="userSpaceOnUse">
														<stop offset="0.4" stop-color="#3395FF"/>
														<stop offset="0.76" stop-color="#004A99"/>
													</linearGradient>
													<linearGradient id="paint5_linear_10119_3622" x1="11.5772" y1="15.7907" x2="11.5772" y2="27.9755" gradientUnits="userSpaceOnUse">
														<stop offset="0.344" stop-color="#3395FF"/>
														<stop offset="0.939" stop-color="#004A99"/>
													</linearGradient>
													<linearGradient id="paint6_linear_10119_3622" x1="11.5772" y1="15.7907" x2="11.5772" y2="27.9755" gradientUnits="userSpaceOnUse">
														<stop offset="0.364" stop-color="#3396FF"/>
														<stop offset="1" stop-color="#004794"/>
													</linearGradient>
												</defs>
											</svg>
										</Box>
										<Box>
											<HStack spacing={1} mb={1}>
												<Text fontSize="sm" fontWeight="600" color="grey.500">
													{emailStatus === 'failed'
														? __('Fix this with SmartSMTP', 'everest-forms')
														: __('Want more reliable delivery?', 'everest-forms')}
												</Text>
												<Link
													href="https://wordpress.org/plugins/smart-smtp/"
													isExternal
													color="primary.500"
													fontSize="sm"
												>
													<svg
														xmlns="http://www.w3.org/2000/svg"
														viewBox="0 0 14 14"
														width="14"
														height="14"
														style={{
															width: '14px',
															minWidth: '14px',
															height: '14px',
															display: 'inline-block',
															flex: '0 0 14px',
															marginRight: '6px',
														}}
													>
														<path d="M1.167 11.083V4.667a1.75 1.75 0 0 1 1.75-1.75h3.5a.583.583 0 0 1 0 1.166h-3.5a.585.585 0 0 0-.584.584v6.416a.585.585 0 0 0 .584.584h6.416a.585.585 0 0 0 .584-.584v-3.5a.583.583 0 0 1 1.166 0v3.5a1.75 1.75 0 0 1-1.75 1.75H2.917a1.75 1.75 0 0 1-1.75-1.75M12.833 5.25a.583.583 0 1 1-1.166 0V3.157L6.247 8.58a.584.584 0 0 1-.826-.825l5.422-5.421H8.75a.583.583 0 1 1 0-1.166h3.5c.322 0 .583.26.583.583z" />
													</svg>
												</Link>
											</HStack>
											<Text fontSize="xs" color="grey.350" lineHeight="1.5">
												{emailStatus === 'failed'
													? __(
														'SmartSMTP sends emails through a proper mail service instead of your hosting server.',
														'everest-forms',
													)
													: __(
														"SmartSMTP adds proper email authentication so your notifications don't end up in spam.",
														'everest-forms',
													)}
											</Text>
											{smtpInstallError && (
												<Text fontSize="xs" color="red.500" mt={1}>
													{smtpInstallError}
												</Text>
											)}
										</Box>
									</HStack>
									<Button
										bgColor="#007BFF !important"
										color="white"
										size="sm"
										onClick={handleInstallSmtpPlugin}
										isLoading={isInstallingSmtp}
										loadingText={__('Installing...', 'everest-forms')}
										flexShrink={0}
									>
										{resolvedSmtpInstalled
											? __('Activate SmartSMTP', 'everest-forms')
											: __('Install & Activate SmartSMTP', 'everest-forms')}
									</Button>
								</Flex>
							</Box>
						)}
					<HStack align="flex-start" gap={3}>
						<Text
							fontSize="14px"
							fontWeight="400"
							color="grey.350"
							lineHeight="19.3px"
						>
							{__(
								"Verify that your site can send emails. Enter your address and we'll send a quick test.",
								'everest-forms',
							)}
						</Text>
					</HStack>

					<FormControl
						width="100%"
						maxWidth="100%"
						sx={{ overflow: 'hidden' }}
						padding="0px 1px"
					>
						<Box marginBottom="12px">
							<Text
								fontSize="14px"
								fontWeight="600"
								color="grey.500"
								whiteSpace="nowrap"
								flexShrink={0}
							>
								{__('Your Email Address', 'everest-forms')}
							</Text>
						</Box>
						<Flex display="flex" gap={3} align="center" direction="row" width="100%">

							<Input
								placeholder={__(
									'Enter the address where the test email should be delivered.',
									'everest-forms',
								)}
								type="email"
								bgColor="#ECECF033 !important"
								value={testEmail}
								onChange={(e) => setTestEmail(e.target.value)}
								isDisabled={sendTestEmailMutation.isLoading}
								onKeyDown={(e) => {
									if (e.key === 'Enter') {
										e.preventDefault();
										handleSendTestEmail();
									}
								}}
								sx={{
									padding: '0 12px !important',
									paddingRight: '12px !important',
									boxSizing: 'border-box !important',
									width: '100% !important',
									maxWidth: '100% !important',
									border: '1px solid #e1e1e1 !important',
									fontSize: '14px !important',
								}}
							/>
							<Button
								width={'fit-content'}
								colorScheme="primary"
								fontSize="13px" fontWeight="500"
								onClick={handleSendTestEmail}
								isLoading={sendTestEmailMutation.isLoading}
								loadingText={__('Sending...', 'everest-forms')}
							>
								<svg
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 14 14"
									width="14"
									height="14"
									style={{
										width: '14px',
										minWidth: '14px',
										height: '14px',
										display: 'inline-block',
										flex: '0 0 14px',
										marginRight: '6px',
									}}
								>
									<g clipPath="url(#a)">
										<path
											fill="currentColor"
											d="m12.613.584.122.019.12.035q.114.044.214.118l.094.081.08.094q.075.099.12.215l.034.119.019.122q.014.182-.048.353l.002.001-3.792 11.084a.876.876 0 0 1-1.581.162l-.059-.12-1.855-4.626-.055-.104a.6.6 0 0 0-.165-.165l-.104-.055-4.626-1.855a.875.875 0 0 1-.55-.834l.014-.133a.9.9 0 0 1 .156-.362L.84 4.63a.9.9 0 0 1 .335-.209L12.259.63v.002a.9.9 0 0 1 .354-.048M2.295 5.271l3.898 1.563a1.75 1.75 0 0 1 .899.81l.074.162 1.562 3.898 3.344-9.777z"
										/>
										<path
											fill="currentColor"
											d="M12.335.84a.584.584 0 0 1 .825.825L6.78 8.046a.584.584 0 0 1-.825-.825z"
										/>
									</g>
									<defs>
										<clipPath id="a">
											<path d="M0 0h14v14H0z" />
										</clipPath>
									</defs>
								</svg>
								{__('Send Test Email', 'everest-forms')}

							</Button>
						</Flex>
					</FormControl>

					<Flex justifyContent="flex-end">

						<Link
							fontSize="13px"
							fontWeight="400"
							color="grey.150"
							letterSpacing="0.2px"
							onClick={() => skipSendTestEmailMutation.mutate()}
							cursor="pointer"
							opacity={skipSendTestEmailMutation.isLoading ? 0.6 : 1}
							pointerEvents={
								skipSendTestEmailMutation.isLoading ? 'none' : 'auto'
							}
							textDecor={'underline'}
						>
							{skipSendTestEmailMutation.isLoading
								? __('Skipping...', 'everest-forms')
								: __('Skip Setup', 'everest-forms')}
						</Link>
					</Flex>
				</Stack>
			</Collapse>
		</Stack>
	);

	useEffect(() => {
		if (adminEmail) {
			setTestEmail(adminEmail);
		}
	}, [adminEmail]);

	const renderSpamProtectionContent = () => (
		<Stack
			p="6"
			gap="5"
			bgColor="white"
			borderRadius="base"
			border="1px"
			borderColor="gray.100"
			overflow="hidden"
			width="100%"
			minWidth="0"
		>
			<HStack
				justify={'space-between'}
				cursor="pointer"
				onClick={() => toggleOpen('spamProtection')}
			>
				<HStack alignItems="center">
					<Heading as="h3" fontSize="19px" fontWeight="600" color="grey.500">
						{__('Spam Protection', 'everest-forms')}
					</Heading>
				</HStack>
				<IconButton
					aria-label={'spamProtection'}
					icon={
						<Icon
							as={open?.spamProtection ? BiChevronUp : BiChevronDown}
							fontSize="2xl"
							fill={open?.spamProtection ? 'primary.500' : 'black'}
						/>
					}
					cursor={'pointer'}
					fontSize={'xl'}
					size="sm"
					boxShadow="none"
					borderRadius="base"
					variant={open?.spamProtection ? 'solid' : 'link'}
					border="none"
					bg={open?.spamProtection ? 'gray.100' : 'transparent'}
					_hover={{
						bg: open?.spamProtection ? 'gray.100' : 'inherit',
					}}
					pointerEvents="none"
				/>
			</HStack>
			<Collapse in={open?.spamProtection}>
				<Stack gap={5}>
					<Divider color={'gray.200'} />
					<Text
						fontSize="14px"
						fontWeight="400"
						color="grey.350"
						lineHeight="19.3px"
					>
						{__(
							'Set up protection against spam submissions. We recommend enabling reCaptcha v2.',
							'everest-forms',
						)}
					</Text>
					<Flex
						bg="#f9fafc"
						p="4"
						borderRadius="md"
						justify="space-between"
						align="center"
					>
						<Box>
							<Text fontSize={'15px'} fontWeight="bold" mb={1}>
								{__('reCaptcha v2', 'everest-forms')}
							</Text>
							<Text fontSize="14px" color="grey.600">
								{__('Enable Google reCaptcha protection', 'everest-forms')}
							</Text>
						</Box>
						<Link
							color="primary.500"
							textDecoration="underline"
							onClick={handleConfigureRecaptcha}
							cursor="pointer"
						>
							{__('Configure Settings', 'everest-forms')}
						</Link>
					</Flex>
					<HStack justifyContent="space-between" alignItems={'flex-end'}>
						<Text color="grey.600" fontSize="14px">
							{__(
								'You can also set up other spam protection features from ',
								'everest-forms',
							)}
							<Link
								color="primary.500"
								textDecoration="underline"
								onClick={handleOtherSpamFeatures}
								cursor="pointer"
							>
								{__('here', 'everest-forms')}
							</Link>
							.
						</Text>
						<Link
							fontSize="13px"
							fontWeight="400"
							color="grey.150"
							letterSpacing="0.2px"
							onClick={handleSkipSpamProtection}
							cursor="pointer"
							width="fit-content"
							opacity={skipSpamProtectionMutation.isLoading ? 0.6 : 1}
							pointerEvents={
								skipSpamProtectionMutation.isLoading ? 'none' : 'auto'
							}
							textDecor={'underline'}
						>
							{skipSpamProtectionMutation.isLoading
								? __('Skipping...', 'everest-forms')
								: __('Skip Setup', 'everest-forms')}
						</Link>
					</HStack>
				</Stack>
			</Collapse>
		</Stack>
	);

	const stepsConfig: StepConfig[] = useMemo(() => {
		const steps: StepConfig[] = [];

		if (!siteData?.data?.has_forms) {
			steps.push({
				id: 'createForm',
				title: __('Start Creating Forms', 'everest-forms'),
				isCompleted: (data) =>
					!!data?.skipped_steps?.includes('create_form') || !!data?.has_forms,
				renderContent: renderCreateFormContent,
			});
		}

		steps.push(
			{
				id: 'sendTestEmail',
				title: __('Send Test Email', 'everest-forms'),
				isCompleted: (data) =>
					!!data?.test_email_sent ||
					!!data?.skipped_steps?.includes('send_test_email') ||
					data?.last_form_email_status === 'success',
				renderContent: renderSendTestEmailContent,
			},
			{
				id: 'spamProtection',
				title: __('Spam Protection', 'everest-forms'),
				isCompleted: (data) =>
					!!data?.skipped_steps?.includes('spam_protection'),
				renderContent: renderSpamProtectionContent,
			},
			
		);

		return steps;
	}, [
		open,
		testEmail,
		sendTestEmailMutation.isLoading,
		skipSpamProtectionMutation.isLoading,
		skipCreateFormMutation.isLoading,
		skipSendTestEmailMutation.isLoading,
		siteData,
	]);

	const visibleSteps = useMemo(() => {
		return stepsConfig.filter((step) => !step.isCompleted(siteData?.data));
	}, [stepsConfig, siteData]);

	const firstStepId = visibleSteps.length > 0 ? visibleSteps[0].id : null;

	useEffect(() => {
		if (firstStepId && open[firstStepId] === undefined) {
			setOpen((prev) => ({ ...prev, [firstStepId]: true }));
		}
	}, [firstStepId]);

	useEffect(() => {
		if (!isLoading && siteData && visibleSteps.length === 0) {
			let cleanURL = adminURL || '';
			if (cleanURL.endsWith('/')) cleanURL = cleanURL.slice(0, -1);
			if (cleanURL.endsWith('/admin.php')) cleanURL = cleanURL.slice(0, -10);
			const targetPage = isPro ? 'evf-analytics' : 'evf-entries';
			window.location.href = `${cleanURL}/admin.php?page=${targetPage}`;
		}
	}, [visibleSteps.length, isLoading, siteData]);

	if (isLoading) {
		return <SiteAssistantSkeleton />;
	}

	return (
		<Container maxW="full" py={10}>
			<Grid
				gridGap="5"
				gridTemplateColumns={{
					sm: '1fr',
					md: '2fr 2fr',
					lg: '3fr 2fr',
					xl: '3fr 1fr',
				}}
			>
				<Stack gap="6">
					{visibleSteps.map((step) => (
						<React.Fragment key={step.id}>
							{step.renderContent()}
						</React.Fragment>
					))}
				</Stack>

				<Stack gap="5">
					<Stack
						p="4"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Team} fontSize={'xl'} fill="primary.400" />
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="600"
								color="grey.400"
								lineHeight="150%"
							>
								{__('Everest Forms Community', 'everest-forms')}
							</Heading>
						</HStack>
						<Text
							fontSize="13px"
							fontWeight="400"
							color="grey.300"
							lineHeight="19.3px"
							mt="12px"
						>
							{__(
								'Join our exclusive group and connect with fellow Everest Forms members. Ask questions, contribute to discussions, and share feedback!',
								'everest-forms',
							)}
						</Text>
						<Link
							fontSize="13px"
							fontWeight="400"
							color="primary.400"
							letterSpacing="0.2px"
							textDecoration="underline"
							href={facebookGroup}
							isExternal
							mt="20px"
						>
							{__('Join our Facebook Group', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="4"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={DocsLines} fontSize={'xl'} fill="primary.400" />
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="600"
								color="grey.400"
								lineHeight="150%"
							>
								{__('Getting Started', 'everest-forms')}
							</Heading>
						</HStack>
						<Text
							fontSize="13px"
							fontWeight="400"
							color="grey.300"
							lineHeight="19.3px"
							mt="12px"
						>
							{__(
								'Check our documentation for detailed information on Everest Forms features and how to use them.',
								'everest-forms',
							)}
						</Text>
						<Link
							fontSize="13px"
							fontWeight="400"
							color="primary.400"
							letterSpacing="0.2px"
							textDecoration="underline"
							href={docURL}
							isExternal
							mt="20px"
						>
							{__('View Documentation', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="4"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Headphones} fontSize={'xl'} fill="primary.400" />
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="600"
								color="grey.400"
								lineHeight="150%"
							>
								{__('Support', 'everest-forms')}
							</Heading>
						</HStack>
						<Text
							fontSize="13px"
							fontWeight="400"
							color="grey.300"
							lineHeight="19.3px"
							mt="12px"
						>
							{__(
								'Submit a ticket for encountered issues and get help from our support team instantly.',
								'everest-forms',
							)}
						</Text>
						<Link
							fontSize="13px"
							fontWeight="400"
							color="primary.400"
							letterSpacing="0.2px"
							textDecoration="underline"
							href={ticketUrl}
							isExternal
							mt="20px"
						>
							{__('Create a Ticket', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="4"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Bulb} fontSize={'xl'} fill="primary.400" />
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="600"
								color="grey.400"
								lineHeight="150%"
							>
								{__('Feature Request', 'everest-forms')}
							</Heading>
						</HStack>
						<Text
							fontSize="13px"
							fontWeight="400"
							color="grey.300"
							lineHeight="19.3px"
							mt="12px"
						>
							{__(
								"Don't find a feature you're looking for? Suggest any features you think would enhance our product.",
								'everest-forms',
							)}
						</Text>
						<Link
							fontSize="13px"
							fontWeight="400"
							color="primary.400"
							letterSpacing="0.2px"
							textDecoration="underline"
							href={featureRequestURL}
							isExternal
							mt="20px"
						>
							{__('Request a Feature', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="4"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Star} fontSize={'xl'} fill="primary.400" />
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="600"
								color="grey.400"
								lineHeight="150%"
							>
								{__('Submit a Review', 'everest-forms')}
							</Heading>
						</HStack>
						<Text
							fontSize="13px"
							fontWeight="400"
							color="grey.300"
							lineHeight="19.3px"
							mt="12px"
						>
							{__(
								"Please take a moment to give us a review. We appreciate honest feedback that'll help us improve our plugin.",
								'everest-forms',
							)}
						</Text>
						<Link
							fontSize="13px"
							fontWeight="400"
							color="primary.400"
							letterSpacing="0.2px"
							textDecoration="underline"
							href={submitReviewUrl}
							isExternal
							mt="20px"
						>
							{__('Submit a Review', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="4"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Video} fontSize={'xl'} fill="primary.400" />
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="600"
								color="grey.400"
								lineHeight="150%"
							>
								{__('Video Tutorials', 'everest-forms')}
							</Heading>
						</HStack>
						<Text
							fontSize="13px"
							fontWeight="400"
							color="grey.300"
							lineHeight="19.3px"
							mt="12px"
						>
							{__(
								"Watch our step-by-step video tutorials that'll help you get the best out of Everest Forms's features.",
								'everest-forms',
							)}
						</Text>
						<Link
							fontSize="13px"
							fontWeight="400"
							color="primary.400"
							letterSpacing="0.2px"
							textDecoration="underline"
							isExternal
							href="https://www.youtube.com/@everestforms"
							mt="20px"
						>
							{__('Watch Videos', 'everest-forms')}
						</Link>
					</Stack>
				</Stack>
			</Grid>
		</Container>
	);
};

export default SiteAssistant;