/**
 *  External Dependencies
 */
import {
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
	const { utmCampaign, evfRestApiNonce, restURL, adminEmail, adminURL } =
		dashboardData;

	const toast = useToast();
	const queryClient = useQueryClient();

	const [open, setOpen] = useState<Record<string, boolean>>({});
	const [testEmail, setTestEmail] = useState<string>(adminEmail || '');

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
			queryClient.setQueryData(['siteAssistant'], data);
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
			setTestEmail('');
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
		>
			<HStack
				justify={'space-between'}
				cursor="pointer"
				onClick={() => toggleOpen('sendTestEmail')}
			>
				<HStack alignItems="center">
					<Heading as="h3" fontSize="19px" fontWeight="600" color="grey.500">
						{__('Send Test Email', 'everest-forms')}
					</Heading>
					{siteData?.data?.test_email_sent && (
						<Text fontSize="sm" color="green.500" fontWeight="medium">
							{__('✓ Completed', 'everest-forms')}
						</Text>
					)}
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
					<HStack align="flex-start" gap={3}>
						<Text
							fontSize="14px"
							fontWeight="400"
							color="grey.350"
							lineHeight="19.3px"
						>
							{__(
								"This tool sends a real test email to confirm your website can deliver messages. If you don't receive it, your email settings may need to be configured.",
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
						<HStack align="center" spacing={4} width="100%">
							<Text
								fontSize="15px"
								fontWeight="600"
								color="grey.500"
								whiteSpace="nowrap"
								flexShrink={0}
							>
								{__('Your Email Address', 'everest-forms')}
							</Text>
							<Input
								placeholder={__(
									'Enter the address where the test email should be delivered.',
									'everest-forms',
								)}
								type="email"
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
						</HStack>
					</FormControl>

					<Flex justify="space-between" align="center">
						<Button
							width={'fit-content'}
							colorScheme="primary"
							onClick={handleSendTestEmail}
							isLoading={sendTestEmailMutation.isLoading}
							loadingText={__('Sending...', 'everest-forms')}
						>
							{__('Send Test Email', 'everest-forms')}
						</Button>
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
				id: 'spamProtection',
				title: __('Spam Protection', 'everest-forms'),
				isCompleted: (data) =>
					!!data?.skipped_steps?.includes('spam_protection'),
				renderContent: renderSpamProtectionContent,
			},
			{
				id: 'sendTestEmail',
				title: __('Send Test Email', 'everest-forms'),
				isCompleted: (data) =>
					!!data?.test_email_sent ||
					!!data?.skipped_steps?.includes('send_test_email'),
				renderContent: renderSendTestEmailContent,
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
