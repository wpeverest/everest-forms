import { Box, Divider, Flex, Heading, Spinner, Tab, TabList, Tabs, Text, useBreakpointValue, useToast } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
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

const Main: React.FC<{ onCreateWithAI?: () => void }> = ({ onCreateWithAI }) => {
	const toast = useToast();
	const [filter, setFilter] = useState(__('All', 'everest-forms'));
	const [isCreatingBlank, setIsCreatingBlank] = useState(false);
	const [state, setState] = useState({
		selectedCategory: __('All Forms', 'everest-forms'),
		searchTerm: '',
	});
	const [categorySetFromURL, setCategorySetFromURL] = useState(false);

	const { selectedCategory, searchTerm } = state;

	const {
		data: templates = [],
		isLoading,
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

				if (normalizedCatName === normalizedSlug) {
					console.log(`Exact match: "${cat.name}"`);
					return true;
				}

				if (normalizedCatName.startsWith(normalizedSlug)) {
					console.log(`Starts with match: "${cat.name}"`);
					return true;
				}

				if (normalizedSlug.startsWith(normalizedCatName)) {
					console.log(`Reverse starts with match: "${cat.name}"`);
					return true;
				}

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

					if (hasMatchingWord) {
						console.log(`Word match: "${cat.name}"`);
						return true;
					}

					if (
						normalizedCatName.includes(normalizedSlug) ||
						normalizedSlug.includes(normalizedCatName)
					) {
						console.log(`Contains match: "${cat.name}"`);
						return true;
					}

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

	const handleSearchChange = useCallback((searchTerm: string) => {
		setState((prevState) => ({ ...prevState, searchTerm }));
	}, []);

	const sidebarWidth = useBreakpointValue({ base: '100%', md: '250px' });

	if (isLoading)
		return (
			<Flex justify="center" align="center" height="100vh">
				<Spinner size="xl" />
			</Flex>
		);
	if (error) return <div>{(error as Error).message}</div>;

	const handleCreateWithAI = () => {
		if (onCreateWithAI) onCreateWithAI();
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

	return (
		<Box>
			<Box p="24px 30px 0px 30px">
				<CreateFormCTA
					onCreateWithAI={handleCreateWithAI}
					onCreateBlank={handleCreateBlank}
					isCreatingBlank={isCreatingBlank}
				/>
			</Box>

			<Box p="0px 30px" my="20px" position="relative">
				<Divider borderColor="#e1e1e1" />
				<Box
					position="absolute"
					top="50%"
					left="50%"
					transform="translate(-50%, -50%)"
					bg="white"
					px="16px"
				>
					<Text
						fontSize="13px"
						fontWeight="700"
						color="#7545BB"
						textTransform="uppercase"
						letterSpacing="1px"
						margin="0"
					>
						{__('or', 'everest-forms')}
					</Text>
				</Box>
			</Box>

			<Flex p="0px 30px 20px 30px" align="center" justify="space-between">
				<Heading
					as="h2"
					fontSize="18px"
					fontWeight="600"
					color="#0f0f1a"
					margin="0"
				>
					{__('All Templates', 'everest-forms')}
				</Heading>
				<Tabs
					variant="unstyled"
					onChange={(index) => {
						const filters = [__('All', 'everest-forms'), __('Free', 'everest-forms'), __('Premium', 'everest-forms')];
						setFilter(filters[index]);
					}}
				>
					<TabList background="#f3f4f6" gap="2px" borderRadius="5px" padding="4px">
						{[__('All', 'everest-forms'), __('Free', 'everest-forms'), __('Premium', 'everest-forms')].map((label) => (
							<Tab
								key={label}
								_selected={{ color: 'purple.500', background: 'white', boxShadow: '0 4px 24px 0 rgba(10,10,10,.06)' }}
								fontSize="14px"
								lineHeight="25px"
								color="#646970"
								borderBottom="2px solid transparent"
								fontWeight="medium"
								whiteSpace="nowrap"
								height="32px"
								borderRadius="4px"
								padding="6px 16px"
							>
								{label}
							</Tab>
						))}
					</TabList>
				</Tabs>
			</Flex>

			<Flex direction={{ base: 'column', md: 'row' }} gap="0">
				<Box
					maxWidth="310px"
					width="100%"
					p="0px 28px 30px 28px"
					boxSizing="border-box"
					borderRight="1px solid #e1e1e1"
				>
					<Sidebar
						categories={categories}
						selectedCategory={state.selectedCategory}
						onCategorySelect={handleCategorySelect}
						onSearchChange={handleSearchChange}
					/>
				</Box>
				<Box p="0px 30px 30px 30px" flex={1}>
					<TemplateList
						selectedCategory={selectedCategory}
						templates={filteredTemplates}
					/>
				</Box>
			</Flex>
		</Box>
	);
};

export default Main;
