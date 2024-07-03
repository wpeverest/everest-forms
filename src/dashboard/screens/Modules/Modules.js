import React, { useContext, useEffect, useState, useCallback } from "react";
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
	Input,
	FormControl,
	useToast,
	Text
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
					filterModules(data.modules_lists);
					setModulesLoaded(true);
				}
			})
			.catch((error) => {
				setError(error.message);
			});
	}, [dispatch, tabIndex]);

	const filterModules = (modules) => {
		let filteredModules = modules;

		if (tabIndex === 1) {
			filteredModules = modules.filter(module => module.type === "feature");
		} else if (tabIndex === 2) {
			filteredModules = modules.filter(module => module.type === "addon");
		}

		setModules(filteredModules);
		setModulesLoaded(true);
	};

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
		filterModules(originalModules);
	}, [tabIndex, originalModules]);

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

	const debounceSearch = debounce((val) => {
		setIsSearching(true);

		if (!val) {
			filterModules(originalModules);
			setIsSearching(false);
			return;
		}

		let searchedData = [];

		if (tabIndex === 1) {
			searchedData = originalModules.filter(
				(module) => module.type === "feature" && module.title.toLowerCase().includes(val.toLowerCase())
			);
		} else if (tabIndex === 2) {
			searchedData = originalModules.filter(
				(module) => module.type === "addon" && module.title.toLowerCase().includes(val.toLowerCase())
			);
		} else {
			searchedData = originalModules.filter((module) => module.title.toLowerCase().includes(val.toLowerCase()));
		}

		if (searchedData.length > 0) {
			setModules(searchedData);
			setModulesLoaded(true);
			setNoItemFound(false);
		} else {
			setModules([]);
			setModulesLoaded(false);
			setNoItemFound(true);
		}

		setIsSearching(false);
	}, 800);

	const handleSearchInputChange = (e) => {
		const val = e.target.value;
		setSearchItem(val);
		debounceSearch(val);
	};

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
		<Box top="var(--wp-admin--admin-bar--height, 0)" zIndex={1}>
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
								<Tab
									onClick={() => handleSearchInputChange({ target: { value: searchItem } })}
								>
									{__("All Modules", "everest-forms")}
								</Tab>
								<Tab
									onClick={() => handleSearchInputChange({ target: { value: searchItem } })}
								>
									{__("Features", "everest-forms")}
								</Tab>
								<Tab
									onClick={() => handleSearchInputChange({ target: { value: searchItem } })}
								>
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
									value={searchItem} onChange={handleSearchInputChange}
								/>
							</InputGroup>
						</FormControl>
					</Stack>
				</Stack>
			</Container>
			<Container maxW="container.xl">
				{
					isSearching ? (
						<AddonsSkeleton />
					) : (
						noItemFound ? (
							<Text
								align="center"
								fontSize="1.2rem"
								bg="red"
								color="white"
								p={4}
								m="5rem auto 0"
								width="60%"
								borderRadius="10px"
								fontWeight="bold"
							>
								{(() => {
									switch (tabIndex) {
										case 1:
											return __("Sorry, No features found", "everest-forms");
										case 2:
											return __("Sorry, No addons found", "everest-forms");
										default:
											return __("Sorry, No modules found", "everest-forms");
									}
								})()}
							</Text>
						) : (
							<Box>
								<Tabs index={tabIndex}>
									<TabPanels>
										<TabPanel>
											<ModuleBody
												isPerformingBulkAction={isPerformingBulkAction}
												filteredAddons={modules}
												setSelectedModuleData={setSelectedModuleData}
												selectedModuleData={selectedModuleData}
											/>
										</TabPanel>
										<TabPanel>
											<ModuleBody
												isPerformingBulkAction={isPerformingBulkAction}
												filteredAddons={modules}
												setSelectedModuleData={setSelectedModuleData}
												selectedModuleData={selectedModuleData}
											/>
										</TabPanel>
										<TabPanel>
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
					)
				}
			</Container>
		</Box>
	);
};

export default Modules;
