import {
	Pagination,
	PaginationContainer,
	PaginationNext,
	PaginationPage,
	PaginationPageGroup,
	PaginationPrevious,
	usePagination,
} from '@ajna/pagination';
import { CloseIcon, SearchIcon } from '@chakra-ui/icons';
import {
	Box,
	Button,
	Checkbox,
	Flex,
	HStack,
	Icon,
	IconButton,
	Input,
	InputGroup,
	InputRightElement,
	Skeleton,
	Stack,
	Table,
	Tbody,
	Td,
	Text,
	Th,
	Thead,
	Tr,
	useToast,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { Select } from 'chakra-react-select';
import { debounce } from 'lodash';
import { useEffect, useState } from 'react';
import {
	bulkAssignPermission,
	bulkRemoveManager,
	getManagers,
	getWPRoles,
	removeManager,
} from './RoleAndPermissionAPI';
import TrashUserRoleModel from './TrashUserRoleModel';
import UserDisplayModal from './UserDisplayModal';

const PRIMARY_COLOR = '#7E3BD0';
const BORDER_COLOR = '#BABABA';

const UserRoleTable = () => {
	const [isAllChecked, setIsAllChecked] = useState(false);
	const [evfPermission, setEvfPermission] = useState([]);
	const [checkedItems, setCheckedItems] = useState({});
	const [firstLoad, setFirstLoad] = useState(true);

	const [managers, setManagers] = useState([]);
	const [permissions, setPermissions] = useState([]);
	const [wpRoles, setWPRoles] = useState([]);
	const [userDeleted, setUserDeleted] = useState(false);
	const [selectedRows, setSelectedRows] = useState([]);
	const [bulkDelete, setBulkDelete] = useState(false);
	const [bulkDeleteSuccess, setBulkDeleteSuccess] = useState();
	const [searchManager, setSearchManager] = useState('');
	const [userAdded, setUserAdded] = useState(false);
	const [inputValue, setInputValue] = useState('');
	const [isLoading, setIsLoading] = useState(true);
	const [isFetching, setIsFetching] = useState(false);
	const toast = useToast();

	const [totalManagers, setTotalManagers] = useState(0);
	const mappedOptions = [
		{ label: 5, value: 5 },
		{ label: 10, value: 10 },
		{ label: 25, value: 25 },
		{ label: 50, value: 50 },
	];

	const outerLimit = 2;
	const innerLimit = 2;

	const {
		pages,
		pagesCount,
		offset,
		currentPage,
		setCurrentPage,
		isDisabled,
		pageSize,
		setPageSize,
	} = usePagination({
		total: totalManagers,
		limits: {
			outer: outerLimit,
			inner: innerLimit,
		},
		initialState: {
			pageSize: 5,
			isDisabled: false,
			currentPage: 1,
		},
	});

	const handlePageChange = (nextPage) => {
		setCurrentPage(nextPage);
	};

	const handlePageSizeChange = (selectedOption) => {
		const pageSize = Number(selectedOption.value);
		setPageSize(pageSize);
	};

	const debounceSearch = debounce((val) => {
		setCurrentPage(1);
		setSearchManager(val);
	}, 800);

	const handleClearSearch = () => {
		setInputValue('');
		setSearchManager('');
		setCurrentPage(1);
	};

	const handleInputChange = (e) => {
		const value = e.target.value;
		setInputValue(value);
		debounceSearch(value);
	};

	useEffect(() => {
		setIsFetching(true);
		getManagers(offset, pageSize, searchManager).then((res) => {
			if (res.success) {
				setUserAdded(false);
				setUserDeleted(false);
				setManagers(res.managers);
				setTotalManagers(res.total);
				setPermissions(res.permissions.permissions);
			}
			setIsLoading(false);
			setIsFetching(false);
		});
	}, [
		currentPage,
		pageSize,
		offset,
		userDeleted,
		bulkDeleteSuccess,
		searchManager,
		userAdded,
	]);

	useEffect(() => {
		getWPRoles().then((res) => {
			setWPRoles(res.data.roles);
		});
	}, []);

	const deleteManager = (userID) => {
		removeManager(userID).then((res) => {
			if (res.success) {
				setUserDeleted(true);
				toast({
					title: res.message,
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
			}
		});
	};

	const handleSelectAll = (isChecked) => {
		if (isChecked) {
			setSelectedRows(managers.map((manager) => manager.id));
		} else {
			setSelectedRows([]);
		}
	};

	const handleSelectRow = (id, isChecked) => {
		setSelectedRows((prevSelected) =>
			isChecked
				? [...prevSelected, id]
				: prevSelected.filter((rowId) => rowId !== id),
		);
	};

	const isAllSelected =
		managers.length > 0 && selectedRows.length === managers.length;
	const isIndeterminate =
		selectedRows.length > 0 && selectedRows.length < managers.length;

	const handleRolePermissionChange = (roleKey, isChecked) => {
		setFirstLoad(false);

		// Update the specific role's checked state
		const updatedCheckedItems = {
			...checkedItems,
			[roleKey]: isChecked,
		};

		setCheckedItems(updatedCheckedItems);
	};

	useEffect(() => {
		getWPRoles().then((res) => {
			setWPRoles(res.data.roles);
			setEvfPermission(res.data.permission.permissions);

			const initialCheckedItems = Object.keys(res.data.roles).reduce(
				(acc, role) => {
					acc[role] = res.data.roles[role].checked;
					return acc;
				},
				{},
			);
			setCheckedItems(initialCheckedItems);
		});
	}, []);

	const handleBulkDelete = () => {
		if (selectedRows.length === 0) {
			toast({
				title: 'Please select user.',
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		} else if (!bulkDelete) {
			toast({
				title: 'Please choose bulk action.',
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		} else if (bulkDelete && selectedRows.length != 0) {
			bulkRemoveManager(selectedRows).then((res) => {
				if (res.success) {
					toast({
						title: res.message,
						status: 'success',
						duration: 3000,
						isClosable: true,
					});

					setBulkDeleteSuccess(true);
					setSelectedRows([]);
				} else {
					toast({
						title: res.message,
						status: 'error',
						duration: 3000,
						isClosable: true,
					});
				}
			});
		}
	};

	const start = (currentPage - 1) * pageSize + 1;
	const end = Math.min(currentPage * pageSize, totalManagers);

	const tableHeading = ['ID', 'Name', 'Email', 'Role', 'Permission', 'Action'];

	return (
		<Stack gap={'5'} p="5">
			<Box bg="white" borderRadius="10px">
				<Flex justifyContent={'flex-end'} align="center" mb="5">
					<UserDisplayModal
						wp_roles={evfPermission}
						setUserAdded={setUserAdded}
						allRoles={wpRoles}
						roleCheckedItems={
							firstLoad
								? Object.keys(wpRoles).reduce((acc, key) => {
										acc[key] = wpRoles[key].checked;
										return acc;
									}, {})
								: checkedItems
						}
						onRolePermissionChange={handleRolePermissionChange}
					/>
				</Flex>

				<HStack align="center" justify={'space-between'} w="full">
					<HStack gap={4}>
						<Select
							placeholder={'Bulk Actions'}
							colorScheme="primary"
							options={[
								{
									label: __('Delete', 'everest-forms'),
									value: 'delete',
								},
							]}
							isClearable
							isSearchable={false}
							onChange={(selectedOption) =>
								setBulkDelete(selectedOption?.value || '')
							}
							chakraStyles={{
								control: (base, state) => ({
									...base,
									width: '200px',
									height: '36px',
									minHeight: '36px',
									border: state.isFocused
										? `1px solid ${PRIMARY_COLOR} !important`
										: `1px solid ${BORDER_COLOR}`,
									borderRadius: '4px',
									fontSize: '14px',
									backgroundColor: '#ffffff',
									boxShadow: state.isFocused
										? `0 0 0 1px ${PRIMARY_COLOR} !important`
										: 'none',
									'&:hover': {
										border: `1px solid ${BORDER_COLOR}`,
									},
								}),
								valueContainer: (base) => ({
									...base,
									padding: '0 16px',
									height: '36px',
									display: 'flex',
									alignItems: 'center',
								}),
								placeholder: (base) => ({
									...base,
									color: 'gray.500',
									fontSize: '14px',
									margin: 0,
								}),
								singleValue: (base) => ({
									...base,
									margin: 0,
									fontSize: '14px',
								}),
								dropdownIndicator: (base) => ({
									...base,
									color: BORDER_COLOR,
									padding: '8px',
									backgroundColor: 'transparent',
								}),
								indicatorSeparator: () => ({
									display: 'none',
								}),
							}}
						/>

						<Button
							minW="64px"
							height="36px"
							borderRadius="4px"
							border={`1px solid ${PRIMARY_COLOR}`}
							padding="8px 14px"
							type="button"
							bg="#ffffff"
							fontSize="14px"
							fontWeight="500"
							color={PRIMARY_COLOR}
							_hover={{
								bg: PRIMARY_COLOR,
								color: '#ffffff',
							}}
							onClick={handleBulkDelete}
						>
							{__('Apply', 'everest-forms')}
						</Button>
					</HStack>

					<HStack gap={4}>
						<InputGroup width="232px">
							<Input
								type="text"
								placeholder={__('Search...', 'everest-forms')}
								onChange={handleInputChange}
								value={inputValue}
								display="flex"
								width="232px"
								height="36px"
								px="16px"
								pr="40px"
								borderRadius="4px"
								border={`1px solid ${BORDER_COLOR} !important`}
								bg="#FFF"
								fontSize="14px"
								lineHeight="1"
								color="#374151"
								_focus={{
									outline: 'none',
									borderColor: `${PRIMARY_COLOR} !important`,
									boxShadow: `0 0 0 1px ${PRIMARY_COLOR}`,
								}}
								_placeholder={{ color: 'gray.500' }}
							/>
							<InputRightElement height="36px" pr="8px">
								{inputValue ? (
									<IconButton
										icon={<CloseIcon boxSize="10px" />}
										size="xs"
										variant="ghost"
										aria-label="Clear search"
										onClick={handleClearSearch}
										minW="24px"
										height="24px"
										color={'gray.500'}
										_hover={{
											bg: 'gray.100',
											color: '#374151',
										}}
									/>
								) : (
									<SearchIcon color={'gray.500'} boxSize="16px" />
								)}
							</InputRightElement>
						</InputGroup>
					</HStack>
				</HStack>
			</Box>

			<VStack bg="white" borderRadius="10px" gap={5} align={'start'} w={'full'}>
				<Box position="relative" w="full">
					{isFetching && !isLoading && (
						<Box
							position="absolute"
							top="0"
							left="0"
							right="0"
							bottom="0"
							bg="rgba(255, 255, 255, 0.7)"
							zIndex="10"
							display="flex"
							alignItems="center"
							justifyContent="center"
							borderRadius="5px"
						>
							<Box
								display="flex"
								alignItems="center"
								gap="8px"
								bg="white"
								px="16px"
								py="8px"
								borderRadius="6px"
								boxShadow="0 2px 8px rgba(0,0,0,0.1)"
							>
								<Box
									as="span"
									display="inline-block"
									w="16px"
									h="16px"
									border="2px solid"
									borderColor={`${PRIMARY_COLOR} transparent ${PRIMARY_COLOR} ${PRIMARY_COLOR}`}
									borderRadius="50%"
									animation="spin 0.8s linear infinite"
									sx={{
										'@keyframes spin': {
											'0%': { transform: 'rotate(0deg)' },
											'100%': { transform: 'rotate(360deg)' },
										},
									}}
								/>
								<Text fontSize="14px" color="#374151" fontWeight="500">
									{__('Loading...', 'everest-forms')}
								</Text>
							</Box>
						</Box>
					)}
					<Table
						variant="unstyled"
						width="100%"
						borderRadius="5px"
						border="1px solid #e1e1e1"
						bg="white"
						overflow="hidden"
						sx={{ borderCollapse: 'separate', borderSpacing: 0 }}
					>
						<Thead>
							<Tr borderBottom="1px solid #e1e1e1">
								<Th
									p="12px 0"
									pl="16px"
									bg="white"
									borderTop="none"
									borderBottom="1px solid #e1e1e1"
									width="48px"
								>
									<Checkbox
										isChecked={isAllSelected}
										isIndeterminate={isIndeterminate}
										onChange={(e) => handleSelectAll(e.target.checked)}
										size="md"
										colorScheme="purple"
									/>
								</Th>
								{tableHeading?.map((heading, index) => (
									<Th
										key={index}
										p="12px 0"
										bg="white"
										borderTop="none"
										borderBottom="1px solid #e1e1e1"
										color="#383838"
										fontFamily="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
										fontWeight="600"
										fontSize="14px"
										lineHeight="24px"
										textTransform="none"
										letterSpacing="normal"
										verticalAlign="middle"
										textAlign="left"
										whiteSpace="nowrap"
									>
										{__(heading, 'everest-forms')}
									</Th>
								))}
							</Tr>
						</Thead>

						<Tbody>
							{isLoading ? (
								<>
									{[...Array(5)].map((_, idx) => (
										<Tr
											key={idx}
											bg={idx % 2 === 0 ? '#faf8fc' : '#fff'}
											borderBottom="1px solid #e1e1e1"
										>
											<Td
												p="16px 0"
												pl="16px"
												width="48px"
												border="none"
												bg="transparent"
											>
												<Skeleton
													height="16px"
													width="16px"
													borderRadius="3px"
												/>
											</Td>
											{tableHeading.map((_, colIdx) => (
												<Td
													key={colIdx}
													p="16px 0"
													border="none"
													bg="transparent"
												>
													<Skeleton
														height="20px"
														width={colIdx === 0 ? '60px' : '100px'}
														borderRadius="4px"
													/>
												</Td>
											))}
										</Tr>
									))}
								</>
							) : !managers || managers.length === 0 ? (
								<Tr>
									<Td
										colSpan={tableHeading.length + 1}
										p="80px 20px"
										textAlign="center"
										bg="#f9fafb"
										border="none"
									>
										<VStack spacing={3}>
											<Box
												w="64px"
												h="64px"
												borderRadius="50%"
												bg="#f3f4f6"
												display="flex"
												alignItems="center"
												justifyContent="center"
											>
												<Icon
													viewBox="0 0 24 24"
													boxSize="32px"
													color="#9ca3af"
												>
													<path
														fill="currentColor"
														d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
													/>
												</Icon>
											</Box>
											<VStack spacing={1}>
												<Text fontSize="16px" fontWeight="600" color="#374151">
													{__(
														"You don't have any Manager yet",
														'everest-forms',
													)}
												</Text>
												<Text fontSize="14px" color="#6b7280">
													{__(
														'Please create a manager and you are good to go.',
														'everest-forms',
													)}
												</Text>
											</VStack>
										</VStack>
									</Td>
								</Tr>
							) : (
								managers?.map((value, index) => (
									<Tr
										key={value.id}
										bg={index % 2 === 0 ? '#faf8fc' : '#fff'}
										borderBottom="1px solid #e1e1e1"
										transition="background-color 0.15s ease, box-shadow 0.15s ease"
										_hover={{
											boxShadow: `0 2px 4px rgba(126, 59, 208, 0.04)`,
										}}
										_last={{
											borderBottom: 'none',
										}}
									>
										<Td
											p="16px 0"
											pl="16px"
											width="48px"
											textAlign="center"
											verticalAlign="top"
											border="none"
											bg="transparent"
										>
											<Checkbox
												isChecked={selectedRows.includes(value.id)}
												onChange={(e) =>
													handleSelectRow(value.id, e.target.checked)
												}
												size="md"
												colorScheme="purple"
											/>
										</Td>

										<Td
											p="16px 0"
											color="#222"
											fontFamily="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
											fontSize="14px"
											fontWeight="600"
											lineHeight="normal"
											verticalAlign="top"
											border="none"
											bg="transparent"
										>
											{value.id}
										</Td>

										<Td
											p="16px 0"
											color="#222"
											fontFamily="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
											fontSize="14px"
											fontWeight="600"
											lineHeight="normal"
											verticalAlign="top"
											border="none"
											bg="transparent"
										>
											{`${value.first_name} ${value.last_name}`}
										</Td>

										<Td
											p="16px 0"
											color="#222"
											fontFamily="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
											fontSize="14px"
											fontWeight="600"
											lineHeight="normal"
											verticalAlign="top"
											border="none"
											bg="transparent"
										>
											{value.email}
										</Td>

										<Td
											p="16px 0"
											color="#222"
											fontFamily="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
											fontSize="14px"
											fontWeight="600"
											lineHeight="normal"
											verticalAlign="top"
											border="none"
											bg="transparent"
										>
											{value.roles}
										</Td>

										<Td
											p="16px 0"
											fontFamily="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
											fontSize="14px"
											fontWeight="600"
											lineHeight="normal"
											verticalAlign="top"
											border="none"
											bg="transparent"
										>
											<Flex gap="4px" flexWrap="wrap">
												{value.permissions.map((permission, index) => (
													<Box
														key={index}
														as="span"
														display="inline-block"
														px="8px"
														py="2px"
														borderRadius="5px"
														fontSize="12px"
														fontWeight="500"
														lineHeight="1.5"
														bg="#f3f4f6"
														color="#383838"
													>
														{permissions[permission]}
													</Box>
												))}
											</Flex>
										</Td>

										<Td
											p="16px 0"
											fontFamily="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
											fontSize="14px"
											fontWeight="600"
											lineHeight="normal"
											verticalAlign="top"
											border="none"
											bg="transparent"
										>
											<Flex alignItems="center" gap="2">
												<Button
													as="span"
													variant="link"
													color={PRIMARY_COLOR}
													fontSize="14px"
													fontWeight="400"
													_hover={{
														textDecoration: 'underline',
														color: '#6328B3',
													}}
													onClick={() => {}}
												>
													<UserDisplayModal
														wp_roles={wpRoles}
														context="edit"
														value={{
															permission: value.permissions,
															email: value.email,
															permission_details: permissions,
														}}
														setUserAdded={setUserAdded}
													/>
												</Button>
												<Text color="#6b7280">|</Text>
												<TrashUserRoleModel
													deleteManager={() => deleteManager(value.id)}
												/>
											</Flex>
										</Td>
									</Tr>
								))
							)}
						</Tbody>
					</Table>
				</Box>

				{totalManagers > 0 && (
					<Flex
						justify="space-between"
						align="center"
						py="16px"
						px="0"
						bg="white"
						w="full"
					>
						<Text color="#6b7280" fontWeight="400" fontSize="14px">
							Showing {start}-{end} of {totalManagers} entries
						</Text>

						<Pagination
							pagesCount={pagesCount}
							currentPage={currentPage}
							isDisabled={isDisabled}
							onPageChange={handlePageChange}
						>
							<PaginationContainer justify="flex-end" p={0} gap="8px">
								<PaginationPrevious
									_hover={{
										bg: '#e5e7eb',
										color: '#374151',
									}}
									_disabled={{
										opacity: 0.4,
										cursor: 'not-allowed',
										pointerEvents: 'none',
										bg: 'transparent',
									}}
									bg="transparent"
									minW="32px"
									height="32px"
									borderRadius="6px"
									border="none"
									color="#6b7280"
									fontSize="20px"
									fontWeight="400"
									px="0"
									display="flex"
									alignItems="center"
									justifyContent="center"
								>
									‹
								</PaginationPrevious>

								<PaginationPageGroup align="center" gap="4px">
									{pages?.map((page) => (
										<PaginationPage
											key={`pagination_page_${page}`}
											page={page}
											minW="32px"
											height="32px"
											p="0"
											border="none"
											borderRadius="6px"
											bg="transparent"
											color="#374151"
											fontSize="14px"
											fontWeight="500"
											display="flex"
											alignItems="center"
											justifyContent="center"
											_hover={{
												bg: '#e5e7eb',
												color: '#374151',
											}}
											_current={{
												bg: PRIMARY_COLOR,
												color: '#fff',
												fontWeight: '600',
											}}
										/>
									))}
								</PaginationPageGroup>

								<PaginationNext
									_hover={{
										bg: '#e5e7eb',
										color: '#374151',
									}}
									_disabled={{
										opacity: 0.4,
										cursor: 'not-allowed',
										pointerEvents: 'none',
										bg: 'transparent',
									}}
									bg="transparent"
									minW="32px"
									height="32px"
									borderRadius="6px"
									border="none"
									color="#6b7280"
									fontSize="20px"
									fontWeight="400"
									px="0"
									display="flex"
									alignItems="center"
									justifyContent="center"
								>
									›
								</PaginationNext>

								<IconButton
									onClick={() => handlePageChange(pagesCount)}
									isDisabled={currentPage === pagesCount}
									variant="ghost"
									minW="32px"
									height="32px"
									borderRadius="6px"
									border="none"
									color="#6b7280"
									fontSize="20px"
									fontWeight="400"
									bg="transparent"
									px="0"
									display="flex"
									alignItems="center"
									justifyContent="center"
									_hover={{
										bg: '#e5e7eb',
										color: '#374151',
									}}
									_disabled={{
										opacity: 0.4,
										cursor: 'not-allowed',
										pointerEvents: 'none',
									}}
									aria-label="Last page"
									title="Last page"
									icon={<>»</>}
								/>
							</PaginationContainer>
						</Pagination>
					</Flex>
				)}
			</VStack>
		</Stack>
	);
};

export default UserRoleTable;
