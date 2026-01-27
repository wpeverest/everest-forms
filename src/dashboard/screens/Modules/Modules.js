/**
 *  External Dependencies
 */
import { Box, Container, IconButton, Text, useToast } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { debounce } from 'lodash';
import {
	useCallback,
	useContext,
	useEffect,
	useMemo,
	useRef,
	useState,
} from 'react';
import { FaArrowUp } from 'react-icons/fa';

// Use your existing context import - NO CHANGES NEEDED
import { PageNotFound } from './../../components/Icon/Icon';
import DashboardContext from './../../context/DashboardContext';
import { actionTypes } from './../../reducers/DashboardReducer';
import AddonsSkeleton from './../../skeleton/AddonsSkeleton/AddonsSkeleton';
import { getAllModules } from './components/modules-api';

// Import new components
import CardsGrid from './components/CardsGrid';
import Categories from './components/Categories';
import Filters from './components/Filters';

const Modules = () => {
	const toast = useToast();
	// Use your existing context pattern
	const [{ allModules }, dispatch] = useContext(DashboardContext);

	const [state, setState] = useState({
		modules: [],
		originalModules: [],
		modulesLoaded: false,
		selectedModuleData: {},
		bulkAction: '',
		isPerformingBulkAction: false,
		searchItem: '',
		noItemFound: false,
		error: null,
		selectedCategory: 'All',
		selectedSort: 'default',
		selectedStatus: 'all',
		selectedPlan: 'all',
		isLoading: false,
		highlightedCategories: [],
	});
	const [showScrollTop, setShowScrollTop] = useState(false);
	const searchItemRef = useRef(state.searchItem);
	const isFirstRender = useRef(true);

	// Dynamic categories based on modules data
	const getDynamicCategories = () => {
		if (!state.originalModules || state.originalModules.length === 0) {
			return [{ value: 'All', label: 'All', internalValue: 'All' }];
		}

		// Get unique categories from modules
		const uniqueCategories = [
			...new Set(
				state.originalModules.map((module) => module.category).filter(Boolean),
			),
		];

		// Map category names to display names (customize these for Everest Forms)
		const categoryDisplayNames = {
			'Form Elements': 'Form Elements',
			Integrations: 'Integrations',
			Marketing: 'Marketing',
			'Payment Gateways': 'Payment Gateways',
			'Email Marketing': 'Email Marketing',
			Others: 'Others',
		};

		// Create category objects with both internal and display names
		const categories = [{ value: 'All', label: 'All', internalValue: 'All' }];

		uniqueCategories.forEach((category) => {
			categories.push({
				value: categoryDisplayNames[category] || category,
				label: categoryDisplayNames[category] || category,
				internalValue: category,
			});
		});

		return categories;
	};

	const categories = useMemo(
		() => getDynamicCategories(),
		[state.originalModules],
	);

	// Options for dropdowns
	const statusOptions = [
		{ label: 'All Status', value: 'all' },
		{ label: 'Active', value: 'active' },
		{ label: 'Inactive', value: 'inactive' },
	];

	const planOptions = [
		{ label: 'All Plans', value: 'all' },
		{ label: 'Free', value: 'free' },
		{ label: 'Pro', value: 'pro' },
	];

	const sortOptions = [
		{ label: __('All', 'everest-forms'), value: 'default' },
		{ label: __('Newest', 'everest-forms'), value: 'newest' },
		{ label: __('Oldest', 'everest-forms'), value: 'oldest' },
		{ label: __('Ascending', 'everest-forms'), value: 'asc' },
		{ label: __('Descending', 'everest-forms'), value: 'desc' },
	];

	// Memoized values for select components
	const selectedSortValue = useMemo(
		() =>
			sortOptions.find((option) => option.value === state.selectedSort) || null,
		[state.selectedSort],
	);

	const selectedStatusValue = useMemo(
		() =>
			statusOptions.find((option) => option.value === state.selectedStatus) ||
			null,
		[state.selectedStatus],
	);

	const selectedPlanValue = useMemo(
		() =>
			planOptions.find((option) => option.value === state.selectedPlan) || null,
		[state.selectedPlan],
	);

	// Deduplicate modules based on slug
	const deduplicateModules = (modules) => {
		const seen = new Set();
		return modules.filter((module) => {
			if (seen.has(module.slug)) {
				return false;
			}
			seen.add(module.slug);
			return true;
		});
	};

	const fetchModules = useCallback(() => {
		setState((prev) => ({ ...prev, isLoading: true }));
		getAllModules()
			.then((data) => {
				if (data.success) {
					const deduplicatedModules = deduplicateModules(data.modules_lists);

					dispatch({
						type: actionTypes.GET_ALL_MODULES,
						allModules: deduplicatedModules,
					});
					setState((prev) => ({
						...prev,
						originalModules: deduplicatedModules,
						modulesLoaded: true,
						isLoading: false,
					}));
					filterModules(deduplicatedModules, 'All', false);
				}
			})
			.catch((error) =>
				setState((prev) => ({
					...prev,
					error: error.message,
					modulesLoaded: true,
					isLoading: false,
				})),
			);
	}, [dispatch]);

	useEffect(() => {
		setState((prev) => ({ ...prev, isLoading: true }));
		fetchModules();
	}, [fetchModules]);

	// Scroll to top functionality
	useEffect(() => {
		const handleScroll = () => {
			const scrollTop =
				window.pageYOffset || document.documentElement.scrollTop;
			setShowScrollTop(scrollTop > 300);
		};

		window.addEventListener('scroll', handleScroll);
		return () => window.removeEventListener('scroll', handleScroll);
	}, []);

	const scrollToTop = () => {
		window.scrollTo({
			top: 0,
			behavior: 'smooth',
		});
	};

	// Filter Modules by Categories
	const filterModules = (
		modules,
		category,
		showLoading = false,
		statusFilter = null,
		planFilter = null,
	) => {
		if (showLoading) {
			setState((prev) => ({ ...prev, isLoading: true }));
		}

		const processFilter = () => {
			let filtered = modules;

			// Filter by category
			if (category && category !== 'All') {
				filtered = filtered.filter((mod) => mod.category === category);
			}

			// Filter by status
			const currentStatus =
				statusFilter !== null ? statusFilter : state.selectedStatus;
			if (currentStatus && currentStatus !== 'all') {
				filtered = filtered.filter((mod) => mod.status === currentStatus);
			}

			// Filter by plan
			const currentPlan = planFilter !== null ? planFilter : state.selectedPlan;
			if (currentPlan && currentPlan !== 'all') {
				filtered = filtered.filter((mod) => {
					if (currentPlan === 'free') {
						return mod.plan && mod.plan.includes('free');
					} else if (currentPlan === 'pro') {
						return mod.plan && mod.plan.includes('pro');
					}
					return true;
				});
			}

			// Filter by search term
			const searchValue = searchItemRef.current.toLowerCase();
			if (searchValue) {
				filtered = filtered.filter((mod) =>
					mod.title.toLowerCase().includes(searchValue),
				);
			}

			// Determine which categories contain search results
			let highlightedCategories = [];
			if (searchValue && searchValue.length >= 3) {
				const categoriesWithResults = [
					...new Set(filtered.map((mod) => mod.category).filter(Boolean)),
				];
				highlightedCategories = categoriesWithResults;
			}

			setState((prev) => ({
				...prev,
				modules: filtered,
				noItemFound: filtered.length === 0,
				isLoading: false,
				highlightedCategories: highlightedCategories,
			}));
		};

		if (showLoading) {
			setTimeout(processFilter, 150);
		} else {
			processFilter();
		}
	};

	const showToast = (title, status) => {
		toast({
			title: __(title, 'everest-forms'),
			status,
			duration: 3000,
			isClosable: true,
			position: 'top-right',
		});
	};

	// Search Modules
	const debounceSearch = useCallback(
		debounce((val) => {
			filterModules(state.originalModules, 'All', false);
		}, 300),
		[state.originalModules],
	);

	const handleSearchInputChange = (e) => {
		const val = e.target.value;
		setState((prev) => ({ ...prev, searchItem: val }));
		searchItemRef.current = val;

		if (val.length >= 3) {
			debounceSearch(val);
		} else if (val.length === 0) {
			setState((prev) => ({ ...prev, highlightedCategories: [] }));
			filterModules(
				state.originalModules,
				state.selectedCategory,
				false,
				state.selectedStatus,
				state.selectedPlan,
			);
		}
	};

	const parseDate = (dateString) => {
		const [day, month, year] = dateString.split('/').map(Number);
		return new Date(year, month - 1, day);
	};

	const handleSorterChange = (sortType, data) => {
		switch (sortType) {
			case 'newest':
				setState((prev) => ({
					...prev,
					modules: [...data].sort(
						(a, b) => parseDate(b.released_date) - parseDate(a.released_date),
					),
				}));
				break;
			case 'oldest':
				setState((prev) => ({
					...prev,
					modules: [...data].sort(
						(a, b) => parseDate(a.released_date) - parseDate(b.released_date),
					),
				}));
				break;
			case 'asc':
				setState((prev) => ({
					...prev,
					modules: [...data].sort((a, b) => a.title.localeCompare(b.title)),
				}));
				break;
			case 'desc':
				setState((prev) => ({
					...prev,
					modules: [...data].sort((a, b) => b.title.localeCompare(a.title)),
				}));
				break;
			case 'default':
				// Sort by popular_rank if available
				const sortedData = [...data].sort((a, b) => {
					if ('popular_rank' in a && 'popular_rank' in b) {
						return a.popular_rank - b.popular_rank;
					} else if ('popular_rank' in a) {
						return -1;
					} else if ('popular_rank' in b) {
						return 1;
					} else {
						return 0;
					}
				});
				setState((prev) => ({ ...prev, modules: sortedData }));
				break;
			default:
				setState((prev) => ({
					...prev,
					modulesLoaded: false,
				}));
		}
	};

	// Reset all filters to default values
	const handleResetFilters = () => {
		setState((prev) => ({
			...prev,
			selectedCategory: 'All',
			selectedSort: 'default',
			selectedStatus: 'all',
			selectedPlan: 'all',
			searchItem: '',
			highlightedCategories: [],
		}));
		searchItemRef.current = '';
		filterModules(state.originalModules, 'All', false, 'all', 'all');
	};

	return (
		<Box bg="#F9FAFB" minH="100vh" py={{ base: '16px', md: '24px' }}>
			<Container maxW="1400px" px={{ base: '16px', md: '24px' }}>
				{/* Filters and Categories Section */}
				<Box mb="4">
					<Filters
						sortOptions={sortOptions}
						statusOptions={statusOptions}
						planOptions={planOptions}
						selectedSortValue={selectedSortValue}
						selectedStatusValue={selectedStatusValue}
						selectedPlanValue={selectedPlanValue}
						onSortChange={(selectedOption) => {
							setState((prev) => ({
								...prev,
								selectedSort: selectedOption?.value || 'default',
							}));
							handleSorterChange(selectedOption?.value, state.originalModules);
						}}
						onStatusChange={(selectedOption) => {
							const newStatus = selectedOption?.value || 'all';
							setState((prev) => ({ ...prev, selectedStatus: newStatus }));
							filterModules(
								state.originalModules,
								state.selectedCategory,
								false,
								newStatus,
								null,
							);
						}}
						onPlanChange={(selectedOption) => {
							const newPlan = selectedOption?.value || 'all';
							setState((prev) => ({ ...prev, selectedPlan: newPlan }));
							filterModules(
								state.originalModules,
								state.selectedCategory,
								false,
								null,
								newPlan,
							);
						}}
						searchValue={state.searchItem}
						onSearchChange={handleSearchInputChange}
						onReset={handleResetFilters}
					/>

					<Categories
						categories={categories}
						selectedCategory={state.selectedCategory}
						highlightedCategories={state.highlightedCategories}
						onCategoryChange={(displayValue, internalValue) => {
							setState((prev) => ({ ...prev, selectedCategory: displayValue }));
							filterModules(state.originalModules, internalValue, true);
						}}
					/>
				</Box>

				{/* Content Section */}
				{state.isLoading || !state.modulesLoaded ? (
					<AddonsSkeleton />
				) : state.noItemFound && state.searchItem ? (
					<Box
						bg="white"
						borderRadius="lg"
						boxShadow="sm"
						display="flex"
						justifyContent="center"
						flexDirection="column"
						padding={{ base: '60px 20px', md: '100px' }}
						gap="4"
						alignItems="center"
						minH="400px"
					>
						<PageNotFound color="gray.300" />
						<Text
							fontSize={{ base: '18px', md: '20px' }}
							fontWeight="600"
							color="gray.800"
						>
							{__('Sorry, no result found.', 'everest-forms')}
						</Text>
						<Text fontSize="14px" color="gray.500" textAlign="center">
							{__('Please try another search', 'everest-forms')}
						</Text>
					</Box>
				) : (
					<CardsGrid
						modules={state.modules}
						selectedCategory={state.selectedCategory}
						showToast={showToast}
					/>
				)}
			</Container>

			{/* Scroll to Top Button */}
			{showScrollTop && (
				<IconButton
					position="fixed"
					bottom={{ base: '20px', md: '24px' }}
					right={{ base: '20px', md: '24px' }}
					zIndex="1000"
					aria-label="Scroll to top"
					icon={<FaArrowUp />}
					size="md"
					variant="solid"
					bg="white"
					border="1px solid"
					borderColor="#E5E7EB"
					color="#6B7280"
					borderRadius="full"
					boxShadow="0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)"
					w="48px"
					h="48px"
					_hover={{
						bg: '#F9FAFB',
						borderColor: '#D1D5DB',
						color: '#374151',
						transform: 'translateY(-2px)',
						boxShadow:
							'0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
					}}
					_active={{
						transform: 'translateY(0)',
					}}
					_focus={{
						boxShadow: '0 0 0 3px rgba(66, 99, 235, 0.1)',
					}}
					transition="all 0.2s ease"
					onClick={scrollToTop}
				/>
			)}
		</Box>
	);
};

export default Modules;
