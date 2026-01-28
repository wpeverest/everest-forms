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

import { PageNotFound } from './../../components/Icon/Icon';
import DashboardContext from './../../context/DashboardContext';
import { actionTypes } from './../../reducers/DashboardReducer';
import AddonsSkeleton from './../../skeleton/AddonsSkeleton/AddonsSkeleton';
import { getAllModules } from './components/modules-api';
import CardsGrid from './components/CardsGrid';
import Categories from './components/Categories';
import Filters from './components/Filters';

const Modules = () => {
	const toast = useToast();
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

	const searchIndex = useMemo(() => {
		if (!state.originalModules || state.originalModules.length === 0) {
			return new Map();
		}

		const index = new Map();
		state.originalModules.forEach((module, idx) => {
			index.set(idx, {
				titleLower: module.title.toLowerCase(),
				category: module.category,
				status: module.status,
				plan: module.plan,
				module: module,
			});
		});

		return index;
	}, [state.originalModules]);

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

	// FIXED: Filter Modules - removed useCallback to avoid stale closure issues
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
			// If no modules yet, return early
			if (!modules || modules.length === 0) {
				setState((prev) => ({
					...prev,
					modules: [],
					noItemFound: true,
					isLoading: false,
					highlightedCategories: [],
				}));
				return;
			}

			// Get current filter values - use passed values or state
			const currentStatus =
				statusFilter !== null ? statusFilter : state.selectedStatus;
			const currentPlan = planFilter !== null ? planFilter : state.selectedPlan;
			const searchValue = searchItemRef.current.toLowerCase().trim();

			// Use search index for faster filtering if available
			const filtered = [];
			const categoriesWithResults = new Set();

			// Create temporary index if searchIndex is empty
			const indexToUse =
				searchIndex.size > 0
					? searchIndex
					: new Map(
							modules.map((mod, idx) => [
								idx,
								{
									titleLower: mod.title.toLowerCase(),
									category: mod.category,
									status: mod.status,
									plan: mod.plan,
									module: mod,
								},
							]),
						);

			indexToUse.forEach((indexedModule) => {
				// Early exit if category doesn't match
				if (
					category &&
					category !== 'All' &&
					indexedModule.category !== category
				) {
					return;
				}

				// Early exit if status doesn't match
				if (
					currentStatus &&
					currentStatus !== 'all' &&
					indexedModule.status !== currentStatus
				) {
					return;
				}

				// Early exit if plan doesn't match
				if (currentPlan && currentPlan !== 'all') {
					if (
						currentPlan === 'free' &&
						(!indexedModule.plan || !indexedModule.plan.includes('free'))
					) {
						return;
					}
					if (
						currentPlan === 'pro' &&
						(!indexedModule.plan || !indexedModule.plan.includes('pro'))
					) {
						return;
					}
				}

				// Early exit if search doesn't match
				if (searchValue && !indexedModule.titleLower.includes(searchValue)) {
					return;
				}

				// All filters passed, add to results
				filtered.push(indexedModule.module);

				// Track categories with results for highlighting
				if (searchValue && indexedModule.category) {
					categoriesWithResults.add(indexedModule.category);
				}
			});

			// Update state once with all results
			setState((prev) => ({
				...prev,
				modules: filtered,
				noItemFound: filtered.length === 0,
				isLoading: false,
				highlightedCategories: searchValue
					? Array.from(categoriesWithResults)
					: [],
			}));
		};

		if (showLoading) {
			// Use requestAnimationFrame for smoother UI updates
			requestAnimationFrame(() => {
				setTimeout(processFilter, 0);
			});
		} else {
			processFilter();
		}
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

					// Set original modules first
					setState((prev) => ({
						...prev,
						originalModules: deduplicatedModules,
						modulesLoaded: true,
					}));

					// Then filter with a slight delay to ensure state is updated
					setTimeout(() => {
						filterModules(deduplicatedModules, 'All', false);
					}, 0);
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

	const showToast = (title, status) => {
		toast({
			title: __(title, 'everest-forms'),
			status,
			duration: 3000,
			isClosable: true,
			position: 'top-right',
		});
	};

	// OPTIMIZATION 3: Reduced debounce time and immediate feedback
	const debounceSearch = useCallback(
		debounce(() => {
			const currentCategoryObj = categories.find(
				(cat) => cat.value === state.selectedCategory,
			);
			const currentInternalCategory = currentCategoryObj
				? currentCategoryObj.internalValue
				: 'All';

			filterModules(
				state.originalModules,
				currentInternalCategory,
				false,
				state.selectedStatus,
				state.selectedPlan,
			);
		}, 200), // Reduced from 300ms to 200ms for faster response
		[
			state.originalModules,
			state.selectedCategory,
			state.selectedStatus,
			state.selectedPlan,
			categories,
		],
	);

	const handleSearchInputChange = (e) => {
		const val = e.target.value;
		setState((prev) => ({ ...prev, searchItem: val }));
		searchItemRef.current = val;

		// OPTIMIZATION 4: Instant filtering for empty search
		if (val.length === 0) {
			// Cancel any pending debounced searches
			debounceSearch.cancel();

			// Clear highlights immediately
			setState((prev) => ({ ...prev, highlightedCategories: [] }));

			const currentCategoryObj = categories.find(
				(cat) => cat.value === state.selectedCategory,
			);
			const currentInternalCategory = currentCategoryObj
				? currentCategoryObj.internalValue
				: 'All';

			// Filter immediately without debounce
			filterModules(
				state.originalModules,
				currentInternalCategory,
				false,
				state.selectedStatus,
				state.selectedPlan,
			);
		} else if (val.length >= 2) {
			// OPTIMIZATION 5: Start searching at 2 characters instead of 3
			debounceSearch();
		}
	};

	const parseDate = (dateString) => {
		const [day, month, year] = dateString.split('/').map(Number);
		return new Date(year, month - 1, day);
	};

	// OPTIMIZATION 6: Sort using cached modules instead of originalModules
	const handleSorterChange = useCallback((sortType) => {
		setState((prev) => {
			let sortedModules = [...prev.modules];

			switch (sortType) {
				case 'newest':
					sortedModules.sort(
						(a, b) => parseDate(b.released_date) - parseDate(a.released_date),
					);
					break;
				case 'oldest':
					sortedModules.sort(
						(a, b) => parseDate(a.released_date) - parseDate(b.released_date),
					);
					break;
				case 'asc':
					sortedModules.sort((a, b) => a.title.localeCompare(b.title));
					break;
				case 'desc':
					sortedModules.sort((a, b) => b.title.localeCompare(a.title));
					break;
				case 'default':
					sortedModules.sort((a, b) => {
						if ('popular_rank' in a && 'popular_rank' in b) {
							return a.popular_rank - b.popular_rank;
						} else if ('popular_rank' in a) {
							return -1;
						} else if ('popular_rank' in b) {
							return 1;
						}
						return 0;
					});
					break;
				default:
					break;
			}

			return {
				...prev,
				modules: sortedModules,
				selectedSort: sortType,
			};
		});
	}, []);

	// Reset all filters to default values
	const handleResetFilters = () => {
		// Cancel any pending searches
		debounceSearch.cancel();

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

	// Helper function to get appropriate message based on active filters
	const getNoResultsMessage = () => {
		const hasSearch = state.searchItem.trim().length > 0;
		const hasFilters =
			state.selectedCategory !== 'All' ||
			state.selectedStatus !== 'all' ||
			state.selectedPlan !== 'all';

		if (hasSearch && hasFilters) {
			return {
				title: __('No modules match your search and filters', 'everest-forms'),
				subtitle: __(
					'Try adjusting your search term or filters',
					'everest-forms',
				),
			};
		} else if (hasSearch) {
			return {
				title: __('Sorry, no result found.', 'everest-forms'),
				subtitle: __('Please try another search', 'everest-forms'),
			};
		} else if (hasFilters) {
			return {
				title: __('No modules match your filters', 'everest-forms'),
				subtitle: __('Try adjusting your filter selection', 'everest-forms'),
			};
		}

		return {
			title: __('No modules available', 'everest-forms'),
			subtitle: __('Please check back later', 'everest-forms'),
		};
	};

	const noResultsMessage = getNoResultsMessage();

	return (
		<Box top="var(--wp-admin--admin-bar--height, 0)" zIndex={1} minH="100vh">
			<Container maxW="100%" p="20px">
				{/* Filters and Categories Section */}
				<Box mb="6">
					<Filters
						sortOptions={sortOptions}
						statusOptions={statusOptions}
						planOptions={planOptions}
						selectedSortValue={selectedSortValue}
						selectedStatusValue={selectedStatusValue}
						selectedPlanValue={selectedPlanValue}
						onSortChange={(selectedOption) => {
							handleSorterChange(selectedOption?.value || 'default');
						}}
						onStatusChange={(selectedOption) => {
							const newStatus = selectedOption?.value || 'all';
							setState((prev) => ({ ...prev, selectedStatus: newStatus }));

							const currentCategoryObj = categories.find(
								(cat) => cat.value === state.selectedCategory,
							);
							const currentInternalCategory = currentCategoryObj
								? currentCategoryObj.internalValue
								: 'All';

							filterModules(
								state.originalModules,
								currentInternalCategory,
								false,
								newStatus,
								null,
							);
						}}
						onPlanChange={(selectedOption) => {
							const newPlan = selectedOption?.value || 'all';
							setState((prev) => ({ ...prev, selectedPlan: newPlan }));

							const currentCategoryObj = categories.find(
								(cat) => cat.value === state.selectedCategory,
							);
							const currentInternalCategory = currentCategoryObj
								? currentCategoryObj.internalValue
								: 'All';

							filterModules(
								state.originalModules,
								currentInternalCategory,
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
				) : state.noItemFound ? (
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
							{noResultsMessage.title}
						</Text>
						<Text fontSize="14px" color="gray.500" textAlign="center">
							{noResultsMessage.subtitle}
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
