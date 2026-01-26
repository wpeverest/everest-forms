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
	FormLabel,
	Grid,
	Heading,
	HStack,
	Icon,
	IconButton,
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
}

interface ApiResponse {
	success: boolean;
	data: SiteAssistantData;
}

interface StepConfig {
	id: string;
	title: string;
	isCompleted: (data: SiteAssistantData | undefined) => boolean;
	renderContent: (stepNumber: number) => JSX.Element;
}

interface Props {
	siteAssistantQuery: UseQueryResult<any, any>;
}

const SiteAssistant: React.FC<Props> = ({ siteAssistantQuery }) => {
	const dashboardData =
		typeof _EVF_DASHBOARD_ !== 'undefined' ? _EVF_DASHBOARD_ : {};
	const { utmCampaign, evfRestApiNonce, restURL, adminEmail } = dashboardData;

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

		// Properly concatenate &tab=captcha
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
				description: __('Test email sent successfully.', 'everest-forms'),
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

	const handleSkipSpamProtection = () => {
		skipSpamProtectionMutation.mutate();
	};

	const handleSendTestEmail = () => {
		if (!testEmail || !testEmail.includes('@')) {
			toast({
				title: __('Invalid Email', 'everest-forms'),
				description: __('Please enter a valid email address.', 'everest-forms'),
				status: 'warning',
				duration: 3000,
				isClosable: true,
			});
			return;
		}
		sendTestEmailMutation.mutate(testEmail);
	};

	const renderSendTestEmailContent = (stepNumber: number) => (
		<Stack
			p="6"
			gap="5"
			bgColor="white"
			borderRadius="base"
			border="1px"
			borderColor="gray.100"
		>
			<HStack justify={'space-between'}>
				<HStack alignItems="center">
					<Heading as="h3" size="md" fontWeight="semibold">
						{stepNumber}
						{')'} {__('Send Test Email', 'everest-forms')}
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
					onClick={() => toggleOpen('sendTestEmail')}
					size="sm"
					boxShadow="none"
					borderRadius="base"
					variant={open?.sendTestEmail ? 'solid' : 'link'}
					border="none"
					bg={open?.sendTestEmail ? 'gray.100' : 'transparent'}
					_hover={{
						bg: open?.sendTestEmail ? 'gray.100' : 'inherit',
					}}
				/>
			</HStack>
			<Collapse in={open?.sendTestEmail}>
				<Stack gap={5}>
					<Divider color={'gray.200'} />
					<Text fontWeight={'light'} fontSize={'md'}>
						{__(
							'Make sure emails are being sent to your users. Test by sending a sample email to yourself.',
							'everest-forms',
						)}
					</Text>
					<FormControl gap={2}>
						<FormLabel>
							{__('Email Address (To send test email to)', 'everest-forms')}
						</FormLabel>
						<Input
							placeholder={__('Email', 'everest-forms')}
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
						/>
					</FormControl>
					<Button
						width={'fit-content'}
						colorScheme="primary"
						onClick={handleSendTestEmail}
						isLoading={sendTestEmailMutation.isLoading}
						loadingText={__('Sending...', 'everest-forms')}
					>
						{__('Send Test Email', 'everest-forms')}
					</Button>
				</Stack>
			</Collapse>
		</Stack>
	);

	useEffect(() => {
		if (adminEmail) {
			setTestEmail(adminEmail);
		}
	}, [adminEmail]);

	const renderSpamProtectionContent = (stepNumber: number) => (
		<Stack
			p="6"
			gap="5"
			bgColor="white"
			borderRadius="base"
			border="1px"
			borderColor="gray.100"
		>
			<HStack justify={'space-between'}>
				<HStack alignItems="center">
					<Heading as="h3" size="md" fontWeight="semibold">
						{stepNumber}
						{')'} {__('Spam Protection', 'everest-forms')}
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
					onClick={() => toggleOpen('spamProtection')}
					size="sm"
					boxShadow="none"
					borderRadius="base"
					variant={open?.spamProtection ? 'solid' : 'link'}
					border="none"
					bg={open?.spamProtection ? 'gray.100' : 'transparent'}
					_hover={{
						bg: open?.spamProtection ? 'gray.100' : 'inherit',
					}}
				/>
			</HStack>
			<Collapse in={open?.spamProtection}>
				<Stack gap={5}>
					<Divider color={'gray.200'} />
					<Text fontWeight={'light'} fontSize={'md'}>
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
							<Text fontSize="14px" color="gray.600">
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
						<Text color="gray.600" fontSize="14px">
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
							fontSize="14px"
							color="gray.500"
							textDecoration="underline"
							onClick={handleSkipSpamProtection}
							cursor="pointer"
							width="fit-content"
							opacity={skipSpamProtectionMutation.isLoading ? 0.6 : 1}
							pointerEvents={
								skipSpamProtectionMutation.isLoading ? 'none' : 'auto'
							}
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

	const stepsConfig: StepConfig[] = useMemo(
		() => [
			{
				id: 'sendTestEmail',
				title: __('Send Test Email', 'everest-forms'),
				isCompleted: (data) => !!data?.test_email_sent,
				renderContent: renderSendTestEmailContent,
			},
			{
				id: 'spamProtection',
				title: __('Spam Protection', 'everest-forms'),
				isCompleted: (data) =>
					!!data?.skipped_steps?.includes('spam_protection'),
				renderContent: renderSpamProtectionContent,
			},
		],
		[
			open,
			testEmail,
			sendTestEmailMutation.isLoading,
			skipSpamProtectionMutation.isLoading,
			siteData,
		],
	);

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
				<Stack gap="5">
					{visibleSteps.map((step, index) => (
						<React.Fragment key={step.id}>
							{step.renderContent(index + 1)}
						</React.Fragment>
					))}
				</Stack>

				<Stack gap="5">
					<Stack
						p="6"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Team} fontSize={'xl'} fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__('Everest Forms Community', 'everest-forms')}
							</Heading>
						</HStack>
						<Text fontSize="13px" color="gray.700">
							{__(
								'Join our exclusive group and connect with fellow Everest Forms members. Ask questions, contribute to discussions, and share feedback!',
								'everest-forms',
							)}
						</Text>
						<Link
							color="var(--chakra-colors-primary-500) !important"
							textDecor="underline"
							href={facebookGroup}
							isExternal
						>
							{__('Join our Facebook Group', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="6"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={DocsLines} fontSize={'xl'} fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__('Getting Started', 'everest-forms')}
							</Heading>
						</HStack>
						<Text fontSize="13px" color="gray.700">
							{__(
								'Check our documentation for detailed information on Everest Forms features and how to use them.',
								'everest-forms',
							)}
						</Text>
						<Link
							color="var(--chakra-colors-primary-500) !important"
							textDecor="underline"
							href={docURL}
							isExternal
						>
							{__('View Documentation', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="6"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Headphones} fontSize={'xl'} fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__('Support', 'everest-forms')}
							</Heading>
						</HStack>
						<Text fontSize="13px" color="gray.700">
							{__(
								'Submit a ticket for encountered issues and get help from our support team instantly.',
								'everest-forms',
							)}
						</Text>
						<Link
							color="var(--chakra-colors-primary-500) !important"
							textDecor="underline"
							href={ticketUrl}
							isExternal
						>
							{__('Create a Ticket', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="6"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Bulb} fontSize={'xl'} fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__('Feature Request', 'everest-forms')}
							</Heading>
						</HStack>
						<Text fontSize="13px" color="gray.700">
							{__(
								"Don't find a feature you're looking for? Suggest any features you think would enhance our product.",
								'everest-forms',
							)}
						</Text>
						<Link
							color="var(--chakra-colors-primary-500) !important"
							textDecor="underline"
							href={featureRequestURL}
							isExternal
						>
							{__('Request a Feature', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="6"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Star} fontSize={'xl'} fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__('Submit a Review', 'everest-forms')}
							</Heading>
						</HStack>
						<Text fontSize="13px" color="gray.700">
							{__(
								"Please take a moment to give us a review. We appreciate honest feedback that'll help us improve our plugin.",
								'everest-forms',
							)}
						</Text>
						<Link
							color="var(--chakra-colors-primary-500) !important"
							textDecor="underline"
							href={submitReviewUrl}
							isExternal
						>
							{__('Submit a Review', 'everest-forms')}
						</Link>
					</Stack>
					<Stack
						p="6"
						gap="3"
						bgColor="white"
						borderRadius="base"
						border="1px"
						borderColor="gray.100"
					>
						<HStack gap="2">
							<Icon as={Video} fontSize={'xl'} fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__('Video Tutorials', 'everest-forms')}
							</Heading>
						</HStack>
						<Text fontSize="13px" color="gray.700">
							{__(
								"Watch our step-by-step video tutorials that'll help you get the best out of Everest Forms's features.",
								'everest-forms',
							)}
						</Text>
						<Link
							color="var(--chakra-colors-primary-500) !important"
							textDecor="underline"
							isExternal
							href="https://www.youtube.com/@everestforms"
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
