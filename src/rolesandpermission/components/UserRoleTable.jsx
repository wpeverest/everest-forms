import {
	Pagination,
	PaginationContainer,
	PaginationNext,
	PaginationPage,
	PaginationPageGroup,
	PaginationPrevious,
	PaginationSeparator,
	usePagination,
} from '@ajna/pagination';
import { LockIcon, SearchIcon } from '@chakra-ui/icons';
import {
	Alert,
	AlertIcon,
	Box,
	Button,
	Checkbox,
	Flex,
	Heading,
	IconButton,
	Input,
	InputGroup,
	InputLeftElement,
	Skeleton,
	Stack,
	Switch,
	Table,
	Tbody,
	Td,
	Text,
	Th,
	Thead,
	Tooltip,
	Tr,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { Select } from 'chakra-react-select';
import { debounce } from 'lodash';
import { useEffect, useMemo, useState } from 'react';
import {
	FaAngleDoubleLeft,
	FaAngleDoubleRight,
	FaAngleLeft,
	FaAngleRight,
} from 'react-icons/fa';
import {
	bulkAssignPermission,
	bulkRemoveManager,
	getManagers,
	getWPRoles,
	removeManager,
} from './RoleAndPermissionAPI';
import TrashUserRoleModel from './TrashUserRoleModel';
import UserDisplayModal from './UserDisplayModal';

const VISIBLE_PERM_COUNT = 4;

const PermTag = ({ children }) => (
	<Text
		as="span"
		margin="0"
		display="inline-flex"
		alignItems="center"
		fontSize="12px"
		fontWeight="500"
		bg="primary.25"
		color="primary.500"
		border="1px solid"
		borderColor="primary.100"
		padding="2px 10px"
		borderRadius="full"
		lineHeight="1.6"
	>
		{children}
	</Text>
);

const PermissionCell = ({ permissionKeys, permissionLabels }) => {
	const visible = permissionKeys.slice(0, VISIBLE_PERM_COUNT);
	const hidden = permissionKeys.slice(VISIBLE_PERM_COUNT);

	return (
		<Flex gap="6px" flexWrap="wrap" alignItems="center">
			{visible.map((perm, i) => (
				<PermTag key={i}>{permissionLabels[perm]}</PermTag>
			))}
			{hidden.length > 0 && (
				<Tooltip
					label={
						<Stack gap="4px" py="2px">
							{hidden.map((perm, i) => (
								<Text key={i} fontSize="12px">
									{permissionLabels[perm] || perm}
								</Text>
							))}
						</Stack>
					}
					placement="bottom-start"
					hasArrow
					bg="white"
					color="gray.700"
				>
					<Text
						as="span"
						color="primary.400"
						fontWeight="500"
						fontSize="12px"
						cursor="default"
						_hover={{ textDecoration: 'underline' }}
					>
						+{hidden.length} more
					</Text>
				</Tooltip>
			)}
		</Flex>
	);
};

const UserRoleTable = () => {
	const [checkedItems, setCheckedItems] = useState({});
	const [firstLoad, setFirstLoad] = useState(true);
	const [selectedRows, setSelectedRows] = useState([]);
	const [bulkDelete, setBulkDelete] = useState(false);
	const [searchManager, setSearchManager] = useState('');
	const toast = useToast();

	const [totalManagers, setTotalManagers] = useState(0);

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
		limits: { outer: outerLimit, inner: innerLimit },
		initialState: { pageSize: 10, isDisabled: false, currentPage: 1 },
	});

	const managersQuery = useQuery({
		queryKey: ['managers', offset, pageSize, searchManager],
		queryFn: () => getManagers(offset, pageSize, searchManager),
		keepPreviousData: true,
		staleTime: 60 * 1000,
		refetchOnWindowFocus: false,
		onSuccess: (data) => {
			setTotalManagers(data?.total ?? 0);
		},
	});

	const managers = managersQuery.data?.managers ?? [];
	const permissions = managersQuery.data?.permissions?.permissions ?? {};
	const isLoading = managersQuery.isLoading || managersQuery.isFetching;

	const wpRolesQuery = useQuery({
		queryKey: ['wpRoles'],
		queryFn: getWPRoles,
		staleTime: 5 * 60 * 1000,
		refetchOnWindowFocus: false,
	});

	const wpRoles = wpRolesQuery.data?.data?.roles ?? {};
	const evfPermission = wpRolesQuery.data?.data?.permission?.permissions ?? [];

	useEffect(() => {
		if (wpRolesQuery.data?.data?.roles) {
			const roles = wpRolesQuery.data.data.roles;
			const initial = Object.keys(roles).reduce((acc, role) => {
				acc[role] = roles[role].checked;
				return acc;
			}, {});
			setCheckedItems(initial);
		}
	}, [wpRolesQuery.data]);

	const deleteManagerMutation = useMutation({
		mutationFn: removeManager,
		onSuccess: (res) => {
			if (res.success) {
				toast({
					title: res.message,
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
				managersQuery.refetch();
			}
		},
	});

	const bulkRemoveMutation = useMutation({
		mutationFn: bulkRemoveManager,
		onSuccess: (res) => {
			if (res.success) {
				toast({
					title: res.message,
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
				setSelectedRows([]);
				managersQuery.refetch();
			} else {
				toast({
					title: res.message,
					status: 'error',
					duration: 3000,
					isClosable: true,
				});
			}
		},
	});

	const assignPermissionMutation = useMutation({
		mutationFn: bulkAssignPermission,
		onSuccess: (res) => {
			if (res.success) {
				toast({
					title: res.message,
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
			} else {
				toast({
					title: res.message || 'Something went wrong',
					status: 'error',
					duration: 3000,
					isClosable: true,
				});
			}
		},
	});

	const handlePageChange = (nextPage) => {
		setCurrentPage(nextPage);
	};

	const handlePageSizeChange = (selectedOption) => {
		setPageSize(Number(selectedOption.value));
		setCurrentPage(1);
	};

	const debounceSearch = debounce((val) => {
		setCurrentPage(1);
		setSearchManager(val);
	}, 800);

	const handleSelectAll = (checked) => {
		setSelectedRows(checked ? managers.map((m) => m.id) : []);
	};

	const handleSelectRow = (id, checked) => {
		setSelectedRows((prev) =>
			checked ? [...prev, id] : prev.filter((rowId) => rowId !== id),
		);
	};

	const isAllSelected =
		managers.length > 0 && selectedRows.length === managers.length;
	const isIndeterminate =
		selectedRows.length > 0 && selectedRows.length < managers.length;

	const checkedRoleKeys = useMemo(() => {
		const keys = new Set();
		Object.entries(firstLoad ? wpRoles : checkedItems).forEach(([key, val]) => {
			const isChecked = firstLoad ? val?.checked : val;
			if (isChecked) keys.add(key);
		});
		return keys;
	}, [checkedItems, firstLoad, wpRoles]);

	const isAdmin = (manager) => {
		if (!manager.role_keys || !Array.isArray(manager.role_keys)) return false;
		return manager.role_keys.includes('administrator');
	};

	const isRoleManaged = (manager) => {
		if (isAdmin(manager)) return true;
		if (!manager.role_keys || !Array.isArray(manager.role_keys)) return false;
		return manager.role_keys.some((key) => checkedRoleKeys.has(key));
	};

	const handleIndividualCheck = (role, checked) => {
		setFirstLoad(false);
		const updated = { ...checkedItems, [role]: checked };
		setCheckedItems(updated);
		assignPermissionMutation.mutate(updated);
	};

	const handleBulkDelete = () => {
		if (selectedRows.length === 0) {
			return toast({
				title: 'Please select user.',
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		}
		if (!bulkDelete) {
			return toast({
				title: 'Please choose bulk action.',
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		}
		bulkRemoveMutation.mutate(selectedRows);
	};

	return (
		<Stack gap="20px">
			<Flex justifyContent="space-between" alignItems="center">
				<Heading
					fontWeight="600"
					fontSize="20px"
					lineHeight="normal"
					color="#222222"
				>
					{__('Role Based Access', 'everest-forms')}
				</Heading>
				<UserDisplayModal wp_roles={evfPermission} />
			</Flex>

			{wpRolesQuery.isLoading ? (
				<Flex gap="12px">
					{[80, 70, 90].map((w, i) => (
						<Skeleton key={i} h="36px" w={`${w}px`} borderRadius="md" />
					))}
				</Flex>
			) : (
				<Flex gap="12px" flexWrap="wrap" alignItems="center">
					{Object.entries(wpRoles).map(([roleKey, roleName]) => {
						const isChecked = firstLoad
							? roleName.checked
							: checkedItems[roleKey];
						return (
							<Flex
								key={roleKey}
								as="label"
								alignItems="center"
								gap="8px"
								border="1px solid"
								borderColor={isChecked ? 'primary.200' : 'gray.200'}
								bg={isChecked ? 'primary.25' : 'white'}
								borderRadius="md"
								px="14px"
								py="8px"
								cursor="pointer"
								transition="all 0.15s"
								_hover={{ borderColor: 'primary.300' }}
							>
								<Switch
									isChecked={!!isChecked}
									onChange={(e) =>
										handleIndividualCheck(roleKey, e.target.checked)
									}
									size="sm"
									sx={{
										'& .chakra-switch__track': { bg: 'gray.300' },
										'& .chakra-switch__track[data-checked]': { bg: '#7545bb' },
									}}
								/>
								<Text fontSize="13px" fontWeight="500" color="#222222">
									{roleName.name}
								</Text>
							</Flex>
						);
					})}
				</Flex>
			)}

			{wpRolesQuery.isLoading ? (
				<Skeleton h="48px" w="100%" borderRadius="md" />
			) : (
				<Alert status="info" borderRadius="md" px="16px" py="12px" bg={"#f6f3fa"}>
					<AlertIcon boxSize="16px" mt="1px" alignSelf="flex-start" color="#7545bb"/>
					<Text
						fontSize="13px"
						lineHeight="1.5"
						borderRadius="0"
						color="#383838"
					>
						{checkedRoleKeys.size > 0 ? (
							<>
								<Text as="span" fontWeight="600">
									{Object.entries(wpRoles)
										.filter(([key]) => checkedRoleKeys.has(key))
										.map(([, val]) => val.name)
										.join(', ')}
								</Text>{' '}
								{checkedRoleKeys.size === 1
									? __('role has', 'everest-forms')
									: __('roles have', 'everest-forms')}{' '}
								{__('full Everest Forms access.', 'everest-forms')}
							</>
						) : (
							__(
								'No roles are currently enabled. Enable a role above to grant all Everest Forms permissions to users with that role.',
								'everest-forms',
							)
						)}
					</Text>
				</Alert>
			)}

			<Flex justifyContent="space-between" alignItems="center">
				<Stack direction="row" gap="16px">
					<Box minW="170px">
						<Select
							placeholder={__('Bulk Actions', 'everest-forms')}
							options={[
								{ label: __('Delete', 'everest-forms'), value: 'delete' },
							]}
							isClearable
							isSearchable={false}
							onChange={(opt) => setBulkDelete(opt?.value || '')}
							chakraStyles={{
								dropdownIndicator: (provided) => ({
									...provided,
									bg: 'transparent',
								}),
								indicatorSeparator: (provided) => ({
									...provided,
									display: 'none',
								}),
								option: (provided, state) => ({
									...provided,
									fontSize: '13px',
								}),
							}}
						/>
					</Box>
					<Button
						colorScheme="primary"
						variant={'outline'}
						onClick={handleBulkDelete}
						isLoading={bulkRemoveMutation.isLoading}
					>
						<Text fontWeight="500" fontSize="13px" lineHeight="19.5px">
							{__('Apply', 'everest-forms')}
						</Text>
					</Button>
				</Stack>

				<InputGroup w="220px">
					<InputLeftElement pointerEvents="none">
						<SearchIcon color="gray.400" />
					</InputLeftElement>
					<Input
						placeholder={__('Search...', 'everest-forms')}
						focusBorderColor="primary.400"
						borderRadius="4px"
						onChange={(e) => debounceSearch(e.target.value)}
					/>
				</InputGroup>
			</Flex>

			<Box
				border="1px solid"
				borderColor="gray.200"
				borderRadius="md"
				overflow="hidden"
				mt={8}
			>
				<Table fontSize="14px">
					<Thead>
						<Tr bg="white">
							<Th textTransform="none" width="40px">
								<Checkbox
									isChecked={isAllSelected}
									isIndeterminate={isIndeterminate}
									onChange={(e) => handleSelectAll(e.target.checked)}
								/>
							</Th>
							<Th textTransform="none" width="220px">
								<Text
									fontWeight="600"
									fontSize="14px"
									lineHeight="24px"
									color="#383838"
								>
									{__('Name', 'everest-forms')}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="600"
									fontSize="14px"
									lineHeight="24px"
									color="#383838"
								>
									{__('Email', 'everest-forms')}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="600"
									fontSize="14px"
									lineHeight="24px"
									color="#383838"
								>
									{__('Role', 'everest-forms')}
								</Text>
							</Th>
							<Th textTransform="none">
								<Text
									fontWeight="600"
									fontSize="14px"
									lineHeight="24px"
									color="#383838"
								>
									{__('Permission', 'everest-forms')}
								</Text>
							</Th>
						</Tr>
					</Thead>

					<Tbody>
						{isLoading ? (
							Array.from({ length: 5 }).map((_, i) => (
								<Tr
									key={i}
									bg={i % 2 === 0 ? 'white' : 'primary.15'}
									height="56px"
								>
									<Td>
										<Skeleton h="14px" w="14px" borderRadius="2px" />
									</Td>
									<Td>
										<Stack gap="6px">
											<Skeleton
												h="10px"
												w="70%"
												maxW="110px"
												borderRadius="sm"
											/>
											<Skeleton h="8px" w="40%" maxW="50px" borderRadius="sm" />
										</Stack>
									</Td>
									<Td>
										<Skeleton h="10px" w="80%" maxW="170px" borderRadius="sm" />
									</Td>
									<Td>
										<Skeleton h="10px" w="60%" maxW="70px" borderRadius="sm" />
									</Td>
									<Td>
										<Flex gap="6px">
											<Skeleton h="22px" w="90px" borderRadius="full" />
											<Skeleton h="22px" w="70px" borderRadius="full" />
										</Flex>
									</Td>
								</Tr>
							))
						) : totalManagers === 0 ? (
							<Tr>
								<Td colSpan="5" verticalAlign="top">
									<Flex
										justify="center"
										align="center"
										flexDirection="column"
										py="40px"
									>
										<img
											height="236px"
											width="262px"
											src={evf_roles_and_permission.not_found_image}
										/>
										<Stack marginTop="16px" textAlign="center" gap={0}>
											<Text
												margin={0}
												fontSize="lg"
												color="#222222"
												fontWeight={600}
											>
												{__("You don't have any Manager yet", 'everest-forms')}
											</Text>
											<Text
												margin="8px 0 0 0"
												fontSize="sm"
												color="#6B6B6B"
												fontWeight={400}
											>
												{__(
													'Please create a manager and you are good to go.',
													'everest-forms',
												)}
											</Text>
										</Stack>
									</Flex>
								</Td>
							</Tr>
						) : (
							managers?.map((value, rowIndex) => {
								const managed = isRoleManaged(value);
								return (
									<Tr
										key={value.id}
										role="group"
										textAlign="left"
										bg={rowIndex % 2 === 0 ? 'white' : 'primary.15'}
									>
										<Td verticalAlign="top" pt="14px">
											<Checkbox
												isChecked={selectedRows.includes(value.id)}
												onChange={(e) =>
													handleSelectRow(value.id, e.target.checked)
												}
											/>
										</Td>

										<Td verticalAlign="top" fontSize="14px">
											<Box>
												<Text lineHeight="1.4">
													{`${value.first_name} ${value.last_name}`}
												</Text>
												<Flex
													alignItems="center"
													gap="4px"
													mt="2px"
													visibility="hidden"
													_groupHover={{ visibility: 'visible' }}
												>
													<Text
														color="gray.500"
														fontSize="xs"
														userSelect="none"
													>
														ID: {value.id}
													</Text>
													{!managed && (
														<>
															<Text
																color="gray.300"
																fontSize="xs"
																userSelect="none"
															>
																|
															</Text>
															<UserDisplayModal
																wp_roles={permissions}
																context="edit"
																value={{
																	permission: value.permissions,
																	email: value.email,
																}}
															/>
														</>
													)}

													<Text
														color="gray.300"
														fontSize="xs"
														userSelect="none"
													>
														|
													</Text>
													<TrashUserRoleModel
														deleteManager={() =>
															deleteManagerMutation.mutate(value.id)
														}
													/>
												</Flex>
											</Box>
										</Td>

										<Td verticalAlign="top" fontSize="14px">
											{value.email}
										</Td>
										<Td verticalAlign="top" fontSize="14px">
											{value.roles}
										</Td>
										<Td verticalAlign="top">
											{managed ? (
												<Tooltip
													label={
														isAdmin(value)
															? __(
																	'Administrators have full access by default.',
																	'everest-forms',
																)
															: __(
																	'Permissions are managed via role-based access. Disable the role toggle above to edit individually.',
																	'everest-forms',
																)
													}
													fontSize="xs"
													placement="top"
													hasArrow
													bg={'white'}
													color={'gray.700'}
												>
													<Flex
														alignItems="center"
														gap="6px"
														cursor="help"
														display="inline-flex"
													>
														<LockIcon color="gray.400" boxSize="10px" />
														<Text
															fontSize="12px"
															fontWeight="500"
															color="gray.500"
														>
															{isAdmin(value)
																? __('All permissions (admin)', 'everest-forms')
																: __(
																		'All permissions (via role)',
																		'everest-forms',
																	)}
														</Text>
													</Flex>
												</Tooltip>
											) : (
												<PermissionCell
													permissionKeys={value.permissions}
													permissionLabels={permissions}
												/>
											)}
										</Td>
									</Tr>
								);
							})
						)}
					</Tbody>
				</Table>
			</Box>

			{/* ── Pagination ────────────────────────────────────── */}
			{!isLoading && totalManagers > 0 && (
				<Box borderTop="1px solid" borderColor="gray.200" px={2} py={2} mt={3}>
					<Flex alignItems="center" justify="space-between">
						<Text fontSize="sm" color="gray.500">
							{__('Showing', 'everest-forms')}{' '}
							{totalManagers === 0 ? 0 : (currentPage - 1) * pageSize + 1}-
							{Math.min(currentPage * pageSize, totalManagers)}{' '}
							{__('of', 'everest-forms')} {totalManagers}{' '}
							{__('entries', 'everest-forms')}
						</Text>

						<Flex alignItems="center" gap="2px">
							<IconButton
								aria-label={__('First page', 'everest-forms')}
								icon={<FaAngleDoubleLeft />}
								variant="ghost"
								colorScheme="gray"
								size="sm"
								fontSize="12px"
								isDisabled={isDisabled || currentPage === 1}
								onClick={() => handlePageChange(1)}
							/>

							<Pagination
								pagesCount={pagesCount}
								currentPage={currentPage}
								isDisabled={isDisabled}
								onPageChange={handlePageChange}
							>
								<PaginationContainer gap="2px">
									<PaginationPrevious
										variant="ghost"
										colorScheme="gray"
										size="sm"
										minW={8}
										h={8}
										fontSize="12px"
									>
										<FaAngleLeft />
									</PaginationPrevious>

									<PaginationPageGroup
										align="center"
										separator={
											<PaginationSeparator
												bg="transparent"
												color="gray.400"
												fontSize="sm"
												w={8}
												h={8}
												jumpSize={11}
											/>
										}
									>
										{pages?.map((page) => (
											<PaginationPage
												key={`pagination_page_${page}`}
												page={page}
												w={8}
												h={8}
												fontSize="sm"
												fontWeight="400"
												bg="transparent"
												borderRadius="md"
												color="gray.600"
												_hover={{ bg: 'primary.15' }}
												_current={{
													bg: 'primary.400',
													color: 'white',
													fontWeight: '500',
													borderRadius: 'md',
													_hover: { bg: 'primary.500' },
												}}
											/>
										))}
									</PaginationPageGroup>

									<PaginationNext
										variant="ghost"
										colorScheme="gray"
										size="sm"
										minW={8}
										h={8}
										fontSize="12px"
									>
										<FaAngleRight />
									</PaginationNext>
								</PaginationContainer>
							</Pagination>

							<IconButton
								aria-label={__('Last page', 'everest-forms')}
								icon={<FaAngleDoubleRight />}
								variant="ghost"
								colorScheme="gray"
								size="sm"
								fontSize="12px"
								isDisabled={isDisabled || currentPage === pagesCount}
								onClick={() => handlePageChange(pagesCount)}
							/>
						</Flex>
					</Flex>
				</Box>
			)}
		</Stack>
	);
};

export default UserRoleTable;
