import { Box, Flex, Spinner, useBreakpointValue } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { templatesScriptData } from '../utils/global';
import Sidebar from './Sidebar';
import TemplateList from './TemplateList';

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

const Main: React.FC<{ filter: string }> = ({ filter }) => {
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

	// Handle URL parameters for category pre-selection
	useEffect(() => {
		if (categorySetFromURL) return; // Already set, don't run again
		if (categories.length <= 1) return; // Wait for categories to be loaded (more than just "All Forms")

		const urlParams = new URLSearchParams(window.location.search);

		// Check for category parameter from Site Assistant
		if (urlParams.has('evf_template_category')) {
			const categorySlug = urlParams.get('evf_template_category') || '';

			// Normalize function to remove spaces and special characters
			const normalize = (str: string) =>
				str.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9]/g, '');

			const normalizedSlug = normalize(categorySlug);

			// Log for debugging
			console.log('Category slug from URL:', categorySlug);
			console.log('Normalized slug:', normalizedSlug);
			console.log('Available categories:', categories.map(c => c.name));

			// Find matching category by normalizing both strings
			const matchedCategory = categories.find((cat) => {
				const normalizedCatName = normalize(cat.name);
				const matches = normalizedCatName.includes(normalizedSlug) ||
					   normalizedSlug.includes(normalizedCatName);
				console.log(`Checking "${cat.name}" (${normalizedCatName}) against "${categorySlug}" (${normalizedSlug}): ${matches}`);
				return matches;
			});

			console.log('Matched category:', matchedCategory?.name);

			if (matchedCategory && matchedCategory.name !== __('All Forms', 'everest-forms')) {
				setState((prevState) => ({
					...prevState,
					selectedCategory: matchedCategory.name,
				}));
				setCategorySetFromURL(true);
			}
		}
	}, [categories, categorySetFromURL]); // Re-run when categories change

	const filteredTemplates = useMemo(() => {
		return templates.filter(
			(template) =>
				(selectedCategory === __('All Forms', 'everest-forms') ||
					template.categories.includes(selectedCategory)) &&
				template.title.toLowerCase().includes(searchTerm.toLowerCase()) &&
				(filter === 'All' ||
					(filter === 'Free' && !template.isPro) ||
					(filter === 'Premium' && template.isPro)),
		);
	}, [selectedCategory, searchTerm, templates, filter]);

	const handleCategorySelect = useCallback((category) => {
		setState((prevState) => ({ ...prevState, selectedCategory: category }));
	}, []);

	const handleSearchChange = useCallback((searchTerm) => {
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

	return (
		<Box>
			<Flex direction={{ base: 'column', md: 'row' }}>
				<Box
					width={sidebarWidth}
					mr={{ base: 0, md: 4 }}
					mb={{ base: 4, md: 0 }}
				>
					<Sidebar
						categories={categories}
						selectedCategory={state.selectedCategory}
						onCategorySelect={handleCategorySelect}
						onSearchChange={handleSearchChange}
					/>
				</Box>
				<Box
					width="1px"
					bg="linear-gradient(90deg, #CDD0D8 0%, rgba(255, 255, 255, 0) 158.04%)"
					mx="4"
					marginRight="28px"
				/>
				<Box flex={1}>
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
