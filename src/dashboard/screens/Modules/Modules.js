import React, { useContext, useEffect, useState, useCallback, useMemo } from "react";
import {
	Box,
	Container,
	Stack,
	Select,
	Tabs,
	Tab,
	TabList,
	TabPanels,
	TabPanel,
	Button,
	InputGroup,
	InputLeftElement,
	InputRightElement,
	Input,
	FormControl,
	useToast,
	Text,
	Spinner,
	Flex,
	Icon,
	IconButton
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import {
	getAllModules,
	bulkActivateModules,
	bulkDeactivateModules,
} from "./components/modules-api";
import ModuleBody from "./components/ModuleBody";
import AddonsSkeleton from "./../../skeleton/AddonsSkeleton/AddonsSkeleton";
import { Search } from "./../../components/Icon/Icon";
import dashboardReducer, {
	actionTypes,
} from "./../../reducers/DashboardReducer";
import DashboardContext from "./../../context/DashboardContext";
import { debounce } from "lodash";
import { CloseIcon } from "@chakra-ui/icons";

const Modules = () => {
	const toast = useToast();
	const [modules, setModules] = useState([]);
	const [originalModules, setOriginalModules] = useState([]);
	const [error, setError] = useState(null);
	const [selectedModuleData, setSelectedModuleData] = useState("");
	const [isSearching, setIsSearching] = useState(false);
	const [tabIndex, setTabIndex] = useState(0);
	const [isPerformingBulkAction, setIsPerformingBulkAction] = useState(false);
	const [bulkAction, setBulkAction] = useState("");
	const [modulesLoaded, setModulesLoaded] = useState(false);
	const [{ allModules }, dispatch] = useContext(DashboardContext);
	const [searchItem, setSearchItem] = useState('');
	const [noItemFound, setNoItemFound] = useState(false);

	const fetchModules = useCallback(() => {
		getAllModules()
			.then((data) => {
				if (data.success) {
					dispatch({
						type: actionTypes.GET_ALL_MODULES,
						allModules: data.modules_lists,
					});

					setOriginalModules(data.modules_lists);
					setModulesLoaded(true);
				}
			})
			.catch((error) => {
				setError(error.message);
			});
	}, [dispatch]);

	const filterModules = useCallback((modules, searchValue = '') => {
		let filteredModules = modules;

 		if (tabIndex === 1) {
			filteredModules = filteredModules.filter(module => module.type === "feature");
		} else if (tabIndex === 2) {
			filteredModules = filteredModules.filter(module => module.type === "addon");
		}

 		if (searchValue.trim()) {
			filteredModules = filteredModules.filter((module) =>
				module.title.toLowerCase().includes(searchValue.toLowerCase())
			);
		}

		setModules(filteredModules);
		setModulesLoaded(true);
		setNoItemFound(filteredModules.length === 0 && searchValue.trim() !== '');
	}, [tabIndex]);

	useEffect(() => {
		fetchModules();
	}, [fetchModules]);

	useEffect(() => {
		if (error !== null) {
			toast({
				title: error,
				status: "error",
				duration: 3000,
			});
		}
	}, [error]);

	useEffect(() => {
		if (originalModules.length > 0) {
			filterModules(originalModules, searchItem);
		}
	}, [tabIndex, originalModules, filterModules, searchItem]);

	const handleBulkActions = () => {
		setIsPerformingBulkAction(true);

		const actionFunction = bulkAction === "activate" ? bulkActivateModules : bulkDeactivateModules;

		actionFunction(selectedModuleData)
			.then((data) => {
				toast({
					title: data.message,
					status: data.success ? "success" : "error",
					duration: 3000,
				});
			})
			.catch((e) => {
				toast({
					title: e.message,
					status: "error",
					duration: 3000,
				});
			})
			.finally(() => {
				setIsPerformingBulkAction(false);
				setSelectedModuleData({});
				fetchModules();
			});
	};

	const performSearch = useCallback((val) => {
		filterModules(originalModules, val);
		setIsSearching(false);
	}, [originalModules, filterModules]);

	const debounceSearch = useMemo(
		() => debounce((val) => {
			performSearch(val);
		}, 200),
		[performSearch]
	);

	const handleSearchInputChange = (e) => {
		const val = e.target.value;
		setSearchItem(val);
		setIsSearching(true);
		debounceSearch(val);
	};

	const handleClearSearch = () => {
		setSearchItem('');
		setIsSearching(false);
		debounceSearch.cancel();
		filterModules(originalModules, '');
	};

	useEffect(() => {
		return () => {
			debounceSearch.cancel();
		};
	}, [debounceSearch]);

	const parseDate = (dateString) => {
		const [day, month, year] = dateString.split("/").map(Number);
		return new Date(year, month - 1, day);
	};

	const handleSorterChange = (sortType, data, setData) => {
		switch (sortType) {
			case "newest":
				setData(
					[...data].sort(
						(firstAddonInContext, secondAddonInContext) =>
							parseDate(secondAddonInContext.released_date) -
							parseDate(firstAddonInContext.released_date)
					)
				);
				break;
			case "oldest":
				setData(
					[...data].sort(
						(firstAddonInContext, secondAddonInContext) =>
							parseDate(firstAddonInContext.released_date) -
							parseDate(secondAddonInContext.released_date)
					)
				);
				break;
			case "asc":
				setData(
					[...data].sort(
						(firstAddonInContext, secondAddonInContext) =>
							firstAddonInContext.title.localeCompare(
								secondAddonInContext.title
							)
					)
				);
				break;
			case "desc":
				setData(
					[...data].sort(
					  (firstAddonInContext, secondAddonInContext) => secondAddonInContext.title.localeCompare(firstAddonInContext.title)
					)
				  );
				break;
			default:
				const sortedData = [...data].sort((firstAddonInContext, secondAddonInContext) => {
					if ('popular_rank' in firstAddonInContext && 'popular_rank' in secondAddonInContext) {
					  return firstAddonInContext.popular_rank - secondAddonInContext.popular_rank;
					} else if ('popular_rank' in firstAddonInContext) {
					  return -1;
					} else if ('popular_rank' in secondAddonInContext) {
					  return 1;
					} else {
					  return 0;
					}
				  });
				setData(sortedData);
		}
	};

	return (
		<Box
			top="var(--wp-admin--admin-bar--height, 0)"
			zIndex={1}
			minH="100vh"
			display="flex"
			flexDirection="column"
		>
			<Container maxW="container.xl">
				<Stack
					direction="row"
					minH="70px"
					justify="space-between"
					px="6"
				>
					<Stack direction="row" align="center" gap="5">
						<Select
							display="inline-flex"
							alignItems="center"
							size="md"
							bg="#DFDFE0"
							onChange={(e) => {
								handleSorterChange(
									e.target.value,
									modules,
									setModules
								);
							}}
							border="1px solid #DFDFE0 !important"
							borderRadius="4px !important"
							icon=""
							width="fit-content"
						>
							<option value="default">
								{__("Popular", "everest-forms")}
							</option>
							<option value="newest">
								{__("Newest", "everest-forms")}
							</option>
							<option value="oldest">
								{__("Oldest", "everest-forms")}
							</option>
							<option value="asc">
								{__("Ascending", "everest-forms")}
							</option>
							<option value="desc">
								{__("Descending", "everest-forms")}
							</option>
						</Select>

						<Tabs
							index={tabIndex}
							onChange={(index) => {
								setTabIndex(index);
							}}
						>
							<TabList>
								<Tab>
									{__("All Modules", "everest-forms")}
								</Tab>
								<Tab>
									{__("Features", "everest-forms")}
								</Tab>
								<Tab>
									{__("Addons", "everest-forms")}
								</Tab>
							</TabList>
						</Tabs>

						<Box display="flex" gap="8px">
							<Select
								display="inline-flex"
								alignItems="center"
								size="md"
								bg="#DFDFE0"
								placeholder={__(
									"Bulk Actions",
									"everest-forms"
								)}
								onChange={(e) => setBulkAction(e.target.value)}
								icon=""
								width="fit-content"
								border="1px solid #DFDFE0 !important"
								borderRadius="4px !important"
							>
								<option value="activate">
									{__("Activate", "everest-forms")}
								</option>
								<option value="deactivate">
									{__("Deactivate", "everest-forms")}
								</option>
							</Select>

							<Button
								fontSize="14px"
								variant="outline"
								fontWeight="normal"
								color="gray.600"
								borderRadius="base"
								border="1px solid #DFDFE0 !important"
								textDecor="none !important"
								padding="6px 12px"
								onClick={handleBulkActions}
								isLoading={isPerformingBulkAction}
							>
								{__("Apply", "everest-forms")}
							</Button>
						</Box>
					</Stack>
					<Stack direction="row" align="center" gap="7">
						<FormControl>
							<InputGroup>
								<InputLeftElement
									pointerEvents="none"
									top="2px"
								>
									<Search h="5" w="5" color="gray.300" />
								</InputLeftElement>
								<Input
									type="text"
									placeholder={__(
										"Search...",
										"everest-forms"
									)}
									paddingLeft="32px !important"
									paddingRight={searchItem ? "32px !important" : "12px !important"}
									value={searchItem}
									onChange={handleSearchInputChange}
								/>
								<InputRightElement top="2px">
									{isSearching ? (
										<Spinner size="sm" color="gray.400" />
									) : searchItem ? (
										<IconButton
											aria-label="Clear search"
											icon={<CloseIcon />}
											size="xs"
											variant="ghost"
											onClick={handleClearSearch}
											_hover={{ bg: "gray.100" }}
											borderRadius="full"
										/>
									) : null}
								</InputRightElement>
							</InputGroup>
						</FormControl>
					</Stack>
				</Stack>
			</Container>
			<Container
				maxW="container.xl"
				flex="1"
				display="flex"
				flexDirection="column"
			>
				{
					noItemFound ? (
						<Flex
							direction="column"
							align="center"
							justify="flex-start"
							flex="1"
							minH="calc(100vh - 140px)"
							gap="4"
							pt="120px"
						>
							<Box
								as="svg"
								width="80px"
								height="80px"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
								color="gray.400"
							>
								<circle cx="11" cy="11" r="8"></circle>
								<path d="m21 21-4.35-4.35"></path>
							</Box>
							<Text
								fontSize="18px"
								fontWeight="500"
								color="gray.700"
							>
								{__("Sorry, no result found.", "everest-forms")}
							</Text>
							<Text
								fontSize="14px"
								color="gray.500"
							>
								{__("Please try another search", "everest-forms")}
							</Text>
						</Flex>
					) : (
						<Box flex="1" minH="calc(100vh - 140px)">
							<Tabs index={tabIndex} h="100%">
								<TabPanels h="100%">
									<TabPanel h="100%">
										<ModuleBody
											isPerformingBulkAction={isPerformingBulkAction}
											filteredAddons={modules}
											setSelectedModuleData={setSelectedModuleData}
											selectedModuleData={selectedModuleData}
										/>
									</TabPanel>
									<TabPanel h="100%">
										<ModuleBody
											isPerformingBulkAction={isPerformingBulkAction}
											filteredAddons={modules}
											setSelectedModuleData={setSelectedModuleData}
											selectedModuleData={selectedModuleData}
										/>
									</TabPanel>
									<TabPanel h="100%">
										<ModuleBody
											isPerformingBulkAction={isPerformingBulkAction}
											filteredAddons={modules}
											setSelectedModuleData={setSelectedModuleData}
											selectedModuleData={selectedModuleData}
										/>
									</TabPanel>
								</TabPanels>
							</Tabs>
						</Box>
					)
				}
			</Container>
		</Box>
	);
};

export default Modules;
