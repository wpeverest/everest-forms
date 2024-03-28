import React, { useEffect, useState } from "react";
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
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import { getAllModules } from "./components/modules-api";
import ModuleBody from "./components/ModuleBody";
import AddonsSkeleton from "./../../skeleton/AddonsSkeleton/AddonsSkeleton";

const Modules = () => {
	const toast = useToast();
	const [modules, setModules] = useState([]);
	const [error, setError] = useState(null);
	const [selectedModuleData, setSelectedModuleData] = useState("");
	const [isSearching, setIsSearching] = useState(false);
	const [tabIndex, setTabIndex] = useState(0);
	const [isPerformingBulkAction, setIsPerformingBulkAction] = useState(false);
	const [bulkAction, setBulkAction] = useState("");
	const [modulesLoaded, setModulesLoaded] = useState(false);

	useEffect(() => {
		getAllModules()
			.then((data) => {
				console.log(data);
				if (data.success) {
					setModules((prevModules) => {
						if (
							JSON.stringify(prevModules) !==
							JSON.stringify(data.modules_lists)
						) {
							return data.modules_lists;
						} else {
							return prevModules;
						}
					});
				}
			})
			.catch((error) => {
				setError(error.message);
			});
	}, []);

	useEffect(() => {
		if (error !== null) {
			toast({
				title: error,
				status: "error",
				duration: 3000,
			});
		}
	}, [error]);

	const handleBulkActions = () => {
		setIsPerformingBulkAction(true);

		if (bulkAction === "activate") {
			bulkActivateModules(selectedModuleData)
				.then((data) => {
					if (data.success) {
						toast({
							title: data.message,
							status: "success",
							duration: 3000,
						});
					} else {
						toast({
							title: data.message,
							status: "error",
							duration: 3000,
						});
					}
				})
				.catch((e) => {
					toast({
						title: e.message,
						status: "error",
						duration: 3000,
					});
				})
				.finally(() => {
					setModulesLoaded(false);
					setIsPerformingBulkAction(false);
					setSelectedModuleData({});
				});
		} else if (bulkAction === "deactivate") {
			bulkDeactivateModules(selectedModuleData)
				.then((data) => {
					if (data.success) {
						toast({
							title: data.message,
							status: "success",
							duration: 3000,
						});
					} else {
						toast({
							title: data.message,
							status: "error",
							duration: 3000,
						});
					}
				})
				.catch((e) => {
					toast({
						title: e.message,
						status: "error",
						duration: 3000,
					});
				})
				.finally(() => {
					setModulesLoaded(false);
					setIsPerformingBulkAction(false);
					setSelectedModuleData({});
				});
		}
	};
	const Search = () => {};
	const onSearchInput = () => {};

	const parseDate = (dateString) => {
		console.log(dateString);
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
						(firstAddonInContext, secondAddonInContext) =>
							secondAddonInContext.title.localeCompare(
								firstAddonInContext.title
							)
					)
				);
				break;
			default:
				setModulesLoaded(false);
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
								setIsSearching(true);
								setTabIndex(index);
								setModulesLoaded(false);
								new Promise(function (resolve, reject) {
									setTimeout(resolve, 1000);
								}).then(function () {
									setIsSearching(false);
								});
							}}
						>
							<TabList
								borderBottom="0px"
								border="1px solid #DFDFE0"
								borderRadius="4px"
							>
								<Tab
									fontSize="14px"
									borderRadius="4px 0 0 4px"
									style={{
										boxSizing: "border-box",
									}}
									_focus={{
										boxShadow: "none",
									}}
									_selected={{
										color: "white",
										bg: "#7e3bd0",
										marginBottom: "0px",
										boxShadow: "none",
									}}
									boxShadow="none !important"
									transition="none !important"
								>
									{__("All", "everest-forms")}
								</Tab>
								<Tab
									fontSize="14px"
									style={{
										boxSizing: "border-box",
									}}
									_focus={{
										boxShadow: "none",
									}}
									_selected={{
										color: "white",
										bg: "#7e3bd0",
										marginBottom: "0px",
										boxShadow: "none",
									}}
									boxShadow="none !important"
									borderRight="1px solid #E9E9E9"
									borderLeft="1px solid #E9E9E9"
									marginLeft="0px !important"
									transition="none !important"
								>
									{__("Features", "everest-forms")}
								</Tab>
								<Tab
									fontSize="14px"
									style={{
										boxSizing: "border-box",
									}}
									borderRadius="0 4px 4px 0"
									_focus={{
										boxShadow: "none",
									}}
									_selected={{
										color: "white",
										bg: "#7e3bd0",
										marginBottom: "0px",
										boxShadow: "none",
									}}
									marginLeft="0px !important"
									boxShadow="none !important"
									transition="none !important"
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
									{...onSearchInput}
								/>
							</InputGroup>
						</FormControl>
					</Stack>
				</Stack>
			</Container>
			<Container maxW="container.xl">
				{isSearching ? (
					<AddonsSkeleton />
				) : (
					<Box>
						<Tabs index={tabIndex}>
							<TabPanels>
								<TabPanel>
									<ModuleBody
										isPerformingBulkAction={
											isPerformingBulkAction
										}
										filteredAddons={modules}
										setSelectedModuleData={
											setSelectedModuleData
										}
										selectedModuleData={selectedModuleData}
									/>
								</TabPanel>
								<TabPanel>
									<ModuleBody
										isPerformingBulkAction={
											isPerformingBulkAction
										}
										filteredAddons={modules}
										setSelectedModuleData={
											setSelectedModuleData
										}
										selectedModuleData={selectedModuleData}
									/>
								</TabPanel>
								<TabPanel>
									<ModuleBody
										isPerformingBulkAction={
											isPerformingBulkAction
										}
										filteredAddons={modules}
										setSelectedModuleData={
											setSelectedModuleData
										}
										selectedModuleData={selectedModuleData}
									/>
								</TabPanel>
							</TabPanels>
						</Tabs>
					</Box>
				)}
			</Container>
		</Box>
	);
};

export default Modules;
