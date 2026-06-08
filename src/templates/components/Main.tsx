import {
	Box,
	Flex,
	Heading,
	Icon,
	Input,
	InputGroup,
	InputLeftElement,
	keyframes,
	Tab,
	TabList,
	Tabs,
	Text,
	useToast,
} from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import debounce from 'lodash.debounce';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { IoSearchOutline } from 'react-icons/io5';
import { FiRefreshCw } from 'react-icons/fi';
import { templatesScriptData } from '../utils/global';
import Sidebar from './Sidebar';
import TemplateList from './TemplateList';
import CreateFormCTA from './CreateFormCTA';

const { restURL, security } = templatesScriptData;

const fetchTemplates = async () => {
	const response = (await apiFetch({
		path: `${restURL}everest-forms/v1/templates`,
		method: 'GET',
		headers: {
			'X-WP-Nonce': security,
		},
	})) as { templates: { category: string; templates: Template[] }[] };

	if (response && Array.isArray(response.templates)) {
		const allTemplates = response.templates.flatMap(
			(category) => category.templates,
		);
		return allTemplates;
	} else {
		throw new Error(__('Unexpected response format.', 'everest-forms'));
	}
};

interface CreateFormResponse {
	success: boolean;
	data?: { id: number; redirect: string; status: number };
	message?: string;
}

const shimmer = keyframes`
  0%   { background-position: -600px 0; }
  100% { background-position:  600px 0; }
`;

const spin = keyframes`
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
`;

const skimmerStyle = {
	background: 'linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%)',
	backgroundSize: '600px 100%',
	animation: `${shimmer} 1.6s ease-in-out infinite`,
	borderRadius: '4px',
};

const SkelBox: React.FC<{ w?: string; h?: string; mb?: string; br?: string }> = ({ w = '100%', h = '14px', mb = '0', br = '4px' }) => (
	<Box w={w} h={h} mb={mb} borderRadius={br} sx={skimmerStyle} />
);

// Template card skeleton — matches actual card: gradient image bg + white inner wrapper + info section
const TemplateCardSkeleton = () => (
	<Box bg="white" borderRadius="12px" border="1px solid #e2e8f0" overflow="hidden" display="flex" flexDirection="column">
		{/* Image area: gradient bg + white inner card (matching actual card structure) */}
		<Box
			position="relative"
			borderBottom="1px solid #e2e8f0"
			pt="20px" px="20px" pb="0"
			overflow="hidden"
			background="linear-gradient(129deg, #F3F2F8 2.83%, #F7F5F9 110.96%)"
			minH="160px"
		>
			<Box
				w="100%"
				borderRadius="8px 8px 0 0"
				border="1px solid #e2e8f0"
				borderBottom="none"
				overflow="hidden"
				h="140px"
				sx={skimmerStyle}
				opacity={0.6}
			/>
		</Box>
		{/* Info section */}
		<Box p="20px" flex="1">
			<SkelBox w="62%" h="13px" mb="6px" />
			<SkelBox w="90%" h="11px" mb="4px" />
			<SkelBox w="76%" h="11px" />
		</Box>
	</Box>
);

const TemplateSkeleton = () => (
	<Box p="32px">
		{/* CTA cards — 2-col equal-width grid, matching CreateFormCTA layout */}
		<Flex gap="32px" mb="32px" direction={{ base: 'column', md: 'row' }}>
			{/* AI card: icon + badge row, then title/desc/link */}
			<Box flex="1" bg="white" borderRadius="16px" p="32px" border="1px solid #e2e8f0" display="flex" flexDirection="column">
				<Flex justify="space-between" align="center" mb="16px">
					<Box borderRadius="12px" w="48px" h="48px" sx={skimmerStyle} />
					<Box borderRadius="6px" w="44px" h="22px" sx={skimmerStyle} />
				</Flex>
				<SkelBox w="58%" h="17px" mb="8px" />
				<SkelBox w="92%" h="12px" mb="4px" />
				<SkelBox w="76%" h="12px" mb="24px" />
				<SkelBox w="86px" h="13px" />
			</Box>
			{/* Scratch card: standalone icon, then title/desc/link */}
			<Box flex="1" bg="white" borderRadius="16px" p="32px" border="1px solid #e2e8f0" display="flex" flexDirection="column">
				<Box borderRadius="12px" w="48px" h="48px" mb="20px" sx={skimmerStyle} />
				<SkelBox w="62%" h="17px" mb="8px" />
				<SkelBox w="92%" h="12px" mb="4px" />
				<SkelBox w="80%" h="12px" mb="24px" />
				<SkelBox w="70px" h="13px" />
			</Box>
		</Flex>

		{/* Template section card */}
		<Box bg="white" borderRadius="16px" border="1px solid #e2e8f0" overflow="hidden">

			{/* Top bar: search (left, w-256px) | heading + filter tabs (right) */}
			<Flex borderBottom="1px solid #e2e8f0" direction={{ base: 'column', md: 'row' }}>
				<Box w={{ base: '100%', md: '256px' }} minW={{ md: '256px' }} p="20px" borderRight={{ base: 'none', md: '1px solid #e2e8f0' }}>
					{/* Search input shimmer */}
					<Box h="36px" borderRadius="8px" sx={skimmerStyle} />
				</Box>
				<Flex flex="1" px="28px" py="20px" align="center" justify="space-between">
					{/* "Choose from Templates" heading */}
					<SkelBox w="190px" h="19px" />
					{/* Filter tabs pill */}
					<Box borderRadius="8px" w="150px" h="34px" sx={skimmerStyle} />
				</Flex>
			</Flex>

			{/* Sidebar + template grid */}
			<Flex direction={{ base: 'column', md: 'row' }}>
				{/* Sidebar: CATEGORIES label + rows + Can't find card */}
				<Box w={{ base: '100%', md: '256px' }} minW={{ md: '256px' }} p="20px" pt="12px" borderRight={{ base: 'none', md: '1px solid #e2e8f0' }}>
					{/* "CATEGORIES" label */}
					<Box w="72px" h="10px" mb="10px" sx={skimmerStyle} borderRadius="3px" />
					{/* Category rows */}
					{[78, 62, 88, 55, 72, 60, 70, 52, 65, 58].map((w, i) => (
						<Flex key={i} justify="space-between" align="center" mb="2px" px="12px" py="12px" borderRadius="8px">
							<Box w={`${w}%`} h="13px" sx={skimmerStyle} borderRadius="3px" />
							<Box w="20px" h="13px" sx={skimmerStyle} borderRadius="3px" />
						</Flex>
					))}
					{/* "Can't find a template?" card */}
					<Box mt="20px" borderRadius="12px" border="1px solid #e2e8f0" p="16px">
						<SkelBox w="68%" h="13px" mb="6px" />
						<SkelBox w="92%" h="11px" mb="4px" />
						<SkelBox w="80%" h="11px" mb="12px" />
						<Box h="36px" borderRadius="8px" sx={skimmerStyle} />
					</Box>
				</Box>

				{/* Template grid: 2-col (xl: 3-col) matching TemplateList */}
				<Box p={{ base: '20px', md: '28px' }} pt="12px" flex={1} minW="0">
					<Box sx={{
						display: 'grid',
						gridTemplateColumns: 'repeat(2, 1fr)',
						gap: '16px',
						'@media (min-width: 1280px)': { gridTemplateColumns: 'repeat(3, 1fr)' },
					}}>
						{Array.from({ length: 6 }).map((_, i) => (
							<TemplateCardSkeleton key={i} />
						))}
					</Box>
				</Box>
			</Flex>
		</Box>
	</Box>
);

const Main: React.FC<{ onCreateWithAI?: (formId?: number, title?: string) => void }> = ({ onCreateWithAI }) => {
	const toast = useToast();
	const [filter, setFilter] = useState(__('All', 'everest-forms'));
	const [isCreatingBlank, setIsCreatingBlank] = useState(false);
	const [searchInputValue, setSearchInputValue] = useState('');
	const [state, setState] = useState({
		selectedCategory: __('All Forms', 'everest-forms'),
		searchTerm: '',
	});
	const [categorySetFromURL, setCategorySetFromURL] = useState(false);

	const { selectedCategory, searchTerm } = state;

	const {
		data: templates = [],
		isLoading,
		isFetching,
		refetch,
		error,
	} = useQuery(['templates'], fetchTemplates);

	const categories = useMemo(() => {
		const categoriesSet = new Set<string>();
		templates.forEach((template) => {
			template.categories.forEach((category) => categoriesSet.add(category));
		});

		return [
			{ name: __('All Forms', 'everest-forms'), count: templates.length },
			...Array.from(categoriesSet).map((category) => ({
				name: category,
				count: templates.filter((template) =>
					template.categories.includes(category),
				).length,
			})),
		];
	}, [templates]);

	useEffect(() => {
		if (categorySetFromURL) return;
		if (categories.length <= 1) return;

		const urlParams = new URLSearchParams(window.location.search);

		if (urlParams.has('evf_template_category')) {
			const categorySlug = urlParams.get('evf_template_category') || '';

			const normalize = (str: string) =>
				str
					.toLowerCase()
					.replace(/\s+/g, '')
					.replace(/[^a-z0-9]/g, '');

			const normalizedSlug = normalize(categorySlug);

			let matchedCategory = categories.find((cat) => {
				const normalizedCatName = normalize(cat.name);

				if (normalizedCatName === normalizedSlug) return true;
				if (normalizedCatName.startsWith(normalizedSlug)) return true;
				if (normalizedSlug.startsWith(normalizedCatName)) return true;

				return false;
			});

			if (!matchedCategory) {
				matchedCategory = categories.find((cat) => {
					const normalizedCatName = normalize(cat.name);

					const slugWords = categorySlug.toLowerCase().split(/[\s-]+/);
					const catWords = cat.name.toLowerCase().split(/[\s-]+/);

					const hasMatchingWord = slugWords.some((word) =>
						catWords.some(
							(catWord) => catWord.includes(word) || word.includes(catWord),
						),
					);

					if (hasMatchingWord) return true;

					if (
						normalizedCatName.includes(normalizedSlug) ||
						normalizedSlug.includes(normalizedCatName)
					) return true;

					return false;
				});
			}

			if (
				matchedCategory &&
				matchedCategory.name !== __('All Forms', 'everest-forms')
			) {
				setState((prevState) => ({
					...prevState,
					selectedCategory: matchedCategory.name,
				}));
				setCategorySetFromURL(true);
			}
		}
	}, [categories, categorySetFromURL]);

	const filteredTemplates = useMemo(() => {
		return templates.filter(
			(template) =>
				template.slug !== 'blank' &&
				(selectedCategory === __('All Forms', 'everest-forms') ||
					template.categories.includes(selectedCategory)) &&
				template.title.toLowerCase().includes(searchTerm.toLowerCase()) &&
				(filter === __('All', 'everest-forms') ||
					(filter === __('Free', 'everest-forms') && !template.isPro) ||
					(filter === __('Premium', 'everest-forms') && template.isPro)),
		);
	}, [selectedCategory, searchTerm, templates, filter]);

	const handleCategorySelect = useCallback((category: string) => {
		setState((prevState) => ({ ...prevState, selectedCategory: category }));
	}, []);

	const debouncedSetSearch = useCallback(
		debounce((value: string) => {
			setState((prevState) => ({ ...prevState, searchTerm: value }));
		}, 300),
		[],
	);

	const handleSearchInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		const value = e.target.value;
		setSearchInputValue(value);
		debouncedSetSearch(value);
	};

	if (isLoading) return <TemplateSkeleton />;
	if (error) return <div>{(error as Error).message}</div>;

	const handleCreateWithAI = (formId?: number, title?: string) => {
		if (onCreateWithAI) onCreateWithAI(formId, title);
	};

	const handleCreateBlank = async () => {
		setIsCreatingBlank(true);
		try {
			const response = (await apiFetch({
				path: `${restURL}everest-forms/v1/templates/create`,
				method: 'POST',
				body: JSON.stringify({
					title: __('Untitled', 'everest-forms'),
					slug: 'blank',
				}),
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': security,
				},
			})) as CreateFormResponse;

			if (response.success && response.data) {
				window.location.href = response.data.redirect;
			} else {
				setIsCreatingBlank(false);
				toast({
					title: __('Error', 'everest-forms'),
					description: response.message || __('Failed to create form.', 'everest-forms'),
					status: 'error',
					position: 'bottom-right',
					duration: 5000,
					isClosable: true,
					variant: 'subtle',
				});
			}
		} catch (error) {
			setIsCreatingBlank(false);
			toast({
				title: __('Error', 'everest-forms'),
				description: __('An error occurred while creating the form.', 'everest-forms'),
				status: 'error',
				position: 'bottom-right',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		}
	};

	const filterLabels = [
		__('All', 'everest-forms'),
		__('Free', 'everest-forms'),
		__('Premium', 'everest-forms'),
	];

	return (
		<Box p="32px">
			{/* CTA Cards — 2-column equal-width grid */}
			<Box mb="32px">
				<CreateFormCTA
					onCreateWithAI={handleCreateWithAI}
					onCreateBlank={handleCreateBlank}
					isCreatingBlank={isCreatingBlank}
				/>
			</Box>

			{/* Template Section Card */}
			<Box
				bg="white"
				borderRadius="16px"
				border="1px solid #e2e8f0"
				overflow="hidden"
				position="relative"
			>
				{/* Subtle refetch indicator — thin animated bar at top */}
				{isFetching && !isLoading && (
					<Box
						position="absolute"
						top="0"
						left="0"
						right="0"
						h="2px"
						zIndex={10}
						borderRadius="16px 16px 0 0"
						overflow="hidden"
					>
						<Box
							position="absolute"
							top="0"
							left="0"
							right="0"
							h="100%"
							bg="rgba(117,69,187,0.15)"
						/>
						<Box
							position="absolute"
							top="0"
							h="100%"
							w="40%"
							bg="#7545BB"
							sx={{
								animation: 'refetch-slide 1s ease-in-out infinite',
								'@keyframes refetch-slide': {
									'0%':   { left: '-40%' },
									'100%': { left: '140%' },
								},
							}}
						/>
					</Box>
				)}

				{/* Top bar: search (left) | heading + filter tabs (right) */}
				<Flex
					align="center"
					borderBottom="1px solid #e2e8f0"
					direction={{ base: 'column', md: 'row' }}
				>
					{/* Search area — aligned with sidebar width */}
					<Box
						w={{ base: '100%', md: '256px' }}
						minW={{ md: '256px' }}
						p="20px"
						borderRight={{ base: 'none', md: '1px solid #e2e8f0' }}
						borderBottom={{ base: '1px solid #e2e8f0', md: 'none' }}
					>
						<InputGroup>
							<InputLeftElement pointerEvents="none" h="36px">
								<Icon as={IoSearchOutline} boxSize="4" color="#999" />
							</InputLeftElement>
							<Input
								placeholder={__('Search templates', 'everest-forms')}
								value={searchInputValue}
								onChange={handleSearchInputChange}
								fontSize="14px"
								border="1px solid #e2e8f0"
								borderRadius="8px"
								h="36px"
								pl="36px"
								pr="12px"
								_focus={{
									borderColor: '#7545BB',
									outline: 'none',
									boxShadow: 'none',
								}}
								_placeholder={{ color: '#999' }}
							/>
						</InputGroup>
					</Box>

					{/* Heading + filter tabs */}
					<Flex
						flex="1"
						align="center"
						justify="space-between"
						px={{ base: '20px', md: '28px' }}
						py="20px"
						wrap="wrap"
						gap="12px"
					>
						<Heading
							as="h2"
							fontSize="20px"
							fontWeight="500"
							color="#0e0e0e"
							m="0"
							letterSpacing="-0.01em"
						>
							{__('Choose from Templates', 'everest-forms')}
						</Heading>

						{/* Refetch button + Filter tabs */}
						<Flex align="center" gap="10px">
							<Box
								as="button"
								display="inline-flex"
								alignItems="center"
								gap="6px"
								px="10px"
								py="6px"
								borderRadius="8px"
								border="1px solid #e2e8f0"
								bg="white"
								fontSize="12px"
								fontWeight="500"
								color="#6b6b6b"
								cursor={isFetching ? 'not-allowed' : 'pointer'}
								onClick={() => { if (!isFetching) refetch(); }}
								_hover={{ borderColor: '#7545BB', color: '#7545BB' }}
								transition="all 0.15s"
								title={__('Refresh templates', 'everest-forms')}
							>
								<Icon
									as={FiRefreshCw}
									boxSize="12px"
									sx={isFetching ? { animation: `${spin} 0.7s linear infinite` } : {}}
								/>
								{__('Refetch', 'everest-forms')}
							</Box>

							<Tabs
								variant="unstyled"
								onChange={(index) => setFilter(filterLabels[index])}
							>
								<TabList
									bg="#f1f5f9"
									border="1px solid #e2e8f0"
									borderRadius="8px"
									p="4px"
									gap="0"
								>
									{filterLabels.map((label) => (
										<Tab
											key={label}
											px="12px"
											py="6px"
											borderRadius="6px"
											fontSize="12px"
											fontWeight="500"
											color="#6b6b6b"
											_selected={{
												bg: 'white',
												color: '#7445ba',
												boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
											}}
											_hover={{ color: '#0e0e0e' }}
											transition="all 0.15s"
										>
											{label}
										</Tab>
									))}
								</TabList>
							</Tabs>
						</Flex>
					</Flex>
				</Flex>

				{/* Sidebar + Template Grid */}
				<Flex direction={{ base: 'column', md: 'row' }}>
					{/* Sidebar */}
					<Box
						w={{ base: '100%', md: '256px' }}
						minW={{ md: '256px' }}
						p="20px"
						pt="12px"
						borderRight={{ base: 'none', md: '1px solid #e2e8f0' }}
						borderBottom={{ base: '1px solid #e2e8f0', md: 'none' }}
					>
						<Sidebar
							categories={categories}
							selectedCategory={state.selectedCategory}
							onCategorySelect={handleCategorySelect}
							onRequestTemplate={handleCreateWithAI}
						/>
					</Box>

					{/* Template grid */}
					<Box
						p={{ base: '20px', md: '28px' }}
						pt="12px"
						flex={1}
						minW="0"
						opacity={isFetching && !isLoading ? 0.55 : 1}
						transition="opacity 0.25s ease"
						pointerEvents={isFetching && !isLoading ? 'none' : 'auto'}
					>
						<TemplateList
							selectedCategory={selectedCategory}
							templates={filteredTemplates}
							onCreateWithAI={handleCreateWithAI}
						/>
					</Box>
				</Flex>
			</Box>
		</Box>
	);
};

export default Main;
