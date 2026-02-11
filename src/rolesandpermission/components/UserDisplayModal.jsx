import { AddIcon, InfoIcon } from '@chakra-ui/icons';
import {
	Alert,
	Box,
	Button,
	Checkbox,
	Flex,
	FormControl,
	FormLabel,
	Icon,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalHeader,
	ModalOverlay,
	Stack,
	Tab,
	TabList,
	TabPanel,
	TabPanels,
	Tabs,
	Text,
	Tooltip,
	useDisclosure,
	useToast,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { AsyncSelect, components, Select } from 'chakra-react-select';
import { useEffect, useMemo, useState } from 'react';
import {
	addManagerRole,
	bulkAssignPermission,
	getWPUsers,
} from './RoleAndPermissionAPI';

const PRIMARY_COLOR = '#7E3BD0';

const UserDisplayModal = ({
	wp_roles,
	context = '',
	value = {},
	setUserAdded = false,
	allRoles = {},
	roleCheckedItems = {},
	onRolePermissionChange,
}) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [userEmail, setUserEmail] = useState('');
	const [permissions, setPermissions] = useState([]);
	const [errors, setErrors] = useState([]);
	const [tabIndex, setTabIndex] = useState(0);
	const [localRoleCheckedItems, setLocalRoleCheckedItems] = useState({});
	const [usersPage, setUsersPage] = useState(1);
	const [hasMoreUsers, setHasMoreUsers] = useState(true);
	const toast = useToast();

	useEffect(() => {
		if (context === 'edit') {
			setUserEmail(value.email || '');
			setPermissions(value.permission || []);
		}
	}, [context, value]);

	useEffect(() => {
		if (isOpen) {
			setLocalRoleCheckedItems(roleCheckedItems);
		}
	}, [isOpen, roleCheckedItems]);

	const loadUsers = async (search, loadedOptions, { page }) => {
		try {
			const response = await getWPUsers(page, search || '');

			console.log('Loaded users response:', response);

			if (!response.success) {
				throw new Error(response.message || 'Failed to load users');
			}

			const users = response.data || [];
			const options = users
				.map((user) => {
					const email = user.user_email || user.email || '';
					const name = user.display_name || user.name || 'Unknown';
					return {
						value: email,
						label: email ? `${name} (${email})` : name,
					};
				})
				.filter((option) => option.value);

			// Check if there are more pages
			const hasMore = users.length === 20;

			return {
				options,
				hasMore,
				additional: {
					page: page + 1,
				},
			};
		} catch (error) {
			console.error('Error loading users:', error);
			toast({
				title: __('Failed to load users', 'everest-forms'),
				description:
					error.message ||
					__('Please check console for details', 'everest-forms'),
				status: 'error',
				duration: 5000,
				isClosable: true,
			});
			return {
				options: [],
				hasMore: false,
			};
		}
	};

	const selectedPermissions = useMemo(() => {
		return (
			permissions?.map((val) => ({
				value: val,
				label: value.permission_details?.[val],
			})) || []
		);
	}, [permissions, value.permission_details]);

	const all_permissions = useMemo(() => {
		return Object.entries(wp_roles).map(([key, label]) => ({
			label: label,
			value: key,
		}));
	}, [wp_roles]);

	const handleMultiplePermission = (selectedOptions) => {
		const selectedValues = selectedOptions
			? selectedOptions.map((option) => option.value)
			: [];
		setPermissions(selectedValues);
	};

	const handleAddManager = (email, assignedPermissions) => {
		addManagerRole(email, assignedPermissions).then((res) => {
			setErrors([]);
			if (!res.success) {
				const errorList = Object.entries(res.message).map(([key, message]) => ({
					key,
					message,
				}));
				setErrors(errorList);
			} else {
				setUserAdded(true);
				onClose();
				toast({
					title: res.message,
					status: 'success',
					duration: 3000,
				});
			}
		});
	};

	const handleLocalRoleChange = (roleKey, checked) => {
		setLocalRoleCheckedItems((prev) => ({
			...prev,
			[roleKey]: checked,
		}));
	};

	const handleRolePermissionDone = async () => {
		try {
			const response = await bulkAssignPermission(localRoleCheckedItems);
			if (response.success) {
				if (onRolePermissionChange) {
					Object.entries(localRoleCheckedItems).forEach(([key, value]) => {
						onRolePermissionChange(key, value);
					});
				}
				toast({
					title:
						response.message ||
						__('Role permissions updated successfully', 'everest-forms'),
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
				onClose();
			} else {
				toast({
					title:
						response.message ||
						__('Failed to update role permissions', 'everest-forms'),
					status: 'error',
					duration: 3000,
					isClosable: true,
				});
			}
		} catch (error) {
			console.error('Error updating role permissions:', error);
			toast({
				title: __(
					'An error occurred while updating role permissions',
					'everest-forms',
				),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		}
	};

	const addButtonStyles = {
		minW: '113px',
		height: '36px',
		backgroundColor: '#7E3BD0',
		padding: '8px 16px',
		gap: '6px',
		fontWeight: '500',
		fontSize: '14px',
		color: '#FFFFFF',
		borderRadius: '4px',
		_hover: {
			backgroundColor: '#6328B3',
		},
	};
	const editButtonStyle = {
		color: '#7E3BD0',
		fontWeight: '400',
		fontSize: '14px',
		backgroundColor: 'transparent',
		padding: 0,
		_hover: {
			textDecoration: 'underline',
			color: '#6328B3',
		},
	};

	return (
		<>
			<Button
				{...(context === 'edit' ? editButtonStyle : addButtonStyles)}
				onClick={onOpen}
				leftIcon={context === 'edit' ? null : <AddIcon boxSize="10px" />}
			>
				{context === 'edit' ? 'Edit' : 'Add Permission'}
			</Button>

			<Modal isOpen={isOpen} onClose={onClose} isCentered size={'lg'}>
				<ModalOverlay bg="rgba(0, 0, 0, 0.6)" backdropFilter="blur(4px)" />
				<ModalContent maxW="600px" mx={4}>
					<ModalHeader>
						Add Permission
						<Text fontSize="14px" fontWeight="normal" color="#6B6B6B" mt={2}>
							{context === 'edit'
								? 'View and manage the user permissions.'
								: 'Grant permissions by user email or assign admin access to entire WordPress roles.'}
						</Text>
					</ModalHeader>
					<ModalCloseButton />
					<ModalBody paddingTop={'0'} pb={6}>
						{context === 'edit' ? (
							<FormControl>
								<Stack gap={'28px'}>
									<Stack>
										<FormLabel
											display="flex"
											alignItems="center"
											fontSize="14px"
										>
											{__('User Email', 'everest-forms')}
										</FormLabel>
										<Select
											required
											options={[]}
											placeholder={__(
												'Enter the user email',
												'everest-forms',
											)}
											value={
												userEmail
													? { value: userEmail, label: userEmail }
													: null
											}
											onChange={(option) =>
												setUserEmail(option ? option.value : '')
											}
											isDisabled={true}
											size="md"
											components={{
												Input: (props) => (
													<components.Input {...props} autoComplete="off" />
												),
											}}
											chakraStyles={{
												control: (base, state) => ({
													...base,
													minHeight: '40px',
													border: state.isFocused
														? `1px solid ${PRIMARY_COLOR} !important`
														: '1px solid #BABABA',
													borderRadius: '4px',
													fontSize: '14px',
													backgroundColor: '#ffffff',
													boxShadow: state.isFocused
														? `0 0 0 1px ${PRIMARY_COLOR} !important`
														: 'none',
													'&:hover': {
														border: '1px solid #BABABA',
													},
												}),
												valueContainer: (base) => ({
													...base,
													padding: '2px 8px',
													fontSize: '14px',
													minHeight: '38px',
												}),
												placeholder: (base) => ({
													...base,
													color: '#A0AEC0',
													fontSize: '14px',
													lineHeight: '1.5',
												}),
												singleValue: (base) => ({
													...base,
													fontSize: '14px',
													lineHeight: '1.5',
												}),
												menu: (base) => ({
													...base,
													fontSize: '14px',
													zIndex: 9999,
												}),
												option: (base, state) => ({
													...base,
													fontSize: '14px',
													lineHeight: '1.5',
													padding: '8px 12px',
													backgroundColor: state.isSelected
														? PRIMARY_COLOR
														: state.isFocused
															? '#F7FAFC'
															: 'white',
													color: state.isSelected ? 'white' : '#2D3748',
													cursor: 'pointer',
												}),
												input: (base) => ({
													...base,
													fontSize: '14px',
													lineHeight: '1.5',
													padding: 0,
													margin: 0,
													color: '#2D3748',
												}),
												inputContainer: (base) => ({
													...base,
													padding: 0,
													margin: 0,
													fontSize: '14px',
												}),
												indicatorSeparator: () => ({
													display: 'none',
												}),
												dropdownIndicator: (base) => ({
													...base,
													color: '#CBD5E0',
													padding: '8px',
												}),
												loadingIndicator: (base) => ({
													...base,
													color: PRIMARY_COLOR,
												}),
											}}
										/>
										{errors.map((error, index) =>
											error.key === 'user_email' ? (
												<Alert borderRadius={'4px'} key={index} status="error">
													{error.message}
												</Alert>
											) : null,
										)}
									</Stack>

									<Stack>
										<FormLabel
											display="flex"
											alignItems="center"
											fontSize="14px"
										>
											{__('User Permissions', 'everest-forms')}
											<Tooltip label="User permission" fontSize="sm">
												<Icon
													as={InfoIcon}
													ml={2}
													boxSize={4}
													background="#BABABA"
													color="#FFFFFF"
													borderRadius="50%"
													padding="2px"
													border={'none'}
													_hover={{ cursor: 'pointer' }}
												/>
											</Tooltip>
										</FormLabel>
										<Select
											required
											isMulti
											size="md"
											placeholder={__(
												'Select user permission',
												'everest-forms',
											)}
											options={Object.entries(
												value.permission_details || {},
											).map(([key, label]) => ({
												value: key,
												label: label,
											}))}
											value={selectedPermissions}
											onChange={handleMultiplePermission}
											isClearable
											isSearchable={false}
										/>
										{errors.map((error, index) =>
											error.key === 'assigned_permission' ? (
												<Alert borderRadius={'4px'} key={index} status="error">
													{error.message}
												</Alert>
											) : null,
										)}
									</Stack>
								</Stack>
								<Flex justifyContent={'flex-end'} marginTop={'24px'}>
									<Button
										_hover={{ backgroundColor: '#FFFFF' }}
										color={'#6B6B6B'}
										fontWeight={'600'}
										fontSize={'16px'}
										lineHeight={'24px'}
										mr={3}
										onClick={onClose}
									>
										Back
									</Button>
									<Button
										color={'#FFFFFF'}
										fontWeight={'500'}
										fontSize={'16px'}
										backgroundColor={PRIMARY_COLOR}
										padding={'10px 16px'}
										borderRadius={'4px'}
										border={`1px solid ${PRIMARY_COLOR}`}
										width={'94px'}
										height={'39px'}
										_hover={{ backgroundColor: '#6328B3' }}
										onClick={(e) => handleAddManager(userEmail, permissions)}
									>
										Confirm
									</Button>
								</Flex>
							</FormControl>
						) : (
							<Tabs
								index={tabIndex}
								onChange={setTabIndex}
								colorScheme="purple"
							>
								<TabList borderBottom="2px solid #e1e1e1">
									<Tab
										_selected={{
											color: PRIMARY_COLOR,
											borderColor: PRIMARY_COLOR,
											borderBottom: `2px solid ${PRIMARY_COLOR}`,
										}}
										fontWeight="500"
										fontSize="14px"
									>
										{__('By User Email', 'everest-forms')}
									</Tab>
									<Tab
										_selected={{
											color: PRIMARY_COLOR,
											borderColor: PRIMARY_COLOR,
											borderBottom: `2px solid ${PRIMARY_COLOR}`,
										}}
										fontWeight="500"
										fontSize="14px"
									>
										{__('By Role', 'everest-forms')}
									</Tab>
								</TabList>

								<TabPanels>
									{/* Tab 1: Add by User Email */}
									<TabPanel px={0} pt={6}>
										<FormControl>
											<Stack gap={'28px'}>
												<Stack>
													<FormLabel
														display="flex"
														alignItems="center"
														fontSize="14px"
													>
														User Email
														<Tooltip label="User email" fontSize="sm">
															<Icon
																as={InfoIcon}
																ml={2}
																boxSize={4}
																background="#BABABA"
																color="#FFFFFF"
																borderRadius="50%"
																padding="2px"
																border={'none'}
																_hover={{ cursor: 'pointer' }}
															/>
														</Tooltip>
													</FormLabel>
													<AsyncSelect
														required
														cacheOptions
														defaultOptions
														loadOptions={loadUsers}
														placeholder={__(
															'Search and select user email',
															'everest-forms',
														)}
														value={
															userEmail
																? { value: userEmail, label: userEmail }
																: null
														}
														onChange={(option) =>
															setUserEmail(option ? option.value : '')
														}
														additional={{
															page: 1,
														}}
														size="md"
														components={{
															Input: (props) => (
																<components.Input
																	{...props}
																	autoComplete="off"
																/>
															),
														}}
														chakraStyles={{
															control: (base, state) => ({
																...base,
																minHeight: '40px',
																border: state.isFocused
																	? `1px solid ${PRIMARY_COLOR} !important`
																	: '1px solid #BABABA',
																borderRadius: '4px',
																fontSize: '14px',
																backgroundColor: '#ffffff',
																boxShadow: state.isFocused
																	? `0 0 0 1px ${PRIMARY_COLOR} !important`
																	: 'none',
																'&:hover': {
																	border: '1px solid #BABABA',
																},
															}),
															valueContainer: (base) => ({
																...base,
																padding: '2px 8px',
																fontSize: '14px',
																minHeight: '38px',
															}),
															placeholder: (base) => ({
																...base,
																color: '#A0AEC0',
																fontSize: '14px',
																lineHeight: '1.5',
															}),
															singleValue: (base) => ({
																...base,
																fontSize: '14px',
																lineHeight: '1.5',
															}),
															menu: (base) => ({
																...base,
																fontSize: '14px',
																zIndex: 9999,
															}),
															option: (base, state) => ({
																...base,
																fontSize: '14px',
																lineHeight: '1.5',
																padding: '8px 12px',
																backgroundColor: state.isSelected
																	? PRIMARY_COLOR
																	: state.isFocused
																		? '#F7FAFC'
																		: 'white',
																color: state.isSelected ? 'white' : '#2D3748',
																cursor: 'pointer',
															}),
															input: (base) => ({
																...base,
																fontSize: '14px',
																lineHeight: '1.5',
																padding: 0,
																margin: 0,
																color: '#2D3748',
															}),
															inputContainer: (base) => ({
																...base,
																padding: 0,
																margin: 0,
																fontSize: '14px',
															}),
															indicatorSeparator: () => ({
																display: 'none',
															}),
															dropdownIndicator: (base) => ({
																...base,
																color: '#CBD5E0',
																padding: '8px',
															}),
															loadingIndicator: (base) => ({
																...base,
																color: PRIMARY_COLOR,
															}),
														}}
													/>
													{errors.map((error, index) =>
														error.key === 'user_email' ? (
															<Alert
																borderRadius={'4px'}
																key={index}
																status="error"
															>
																{error.message}
															</Alert>
														) : null,
													)}
												</Stack>

												<Stack>
													<FormLabel
														display="flex"
														alignItems="center"
														fontSize="14px"
													>
														User Permission
														<Tooltip label="User permission" fontSize="sm">
															<Icon
																as={InfoIcon}
																ml={2}
																boxSize={4}
																background="#BABABA"
																color="#FFFFFF"
																borderRadius="50%"
																padding="2px"
																border={'none'}
																_hover={{ cursor: 'pointer' }}
															/>
														</Tooltip>
													</FormLabel>
													<Select
														required
														isMulti
														size="md"
														placeholder={__(
															'Select user permission',
															'everest-forms',
														)}
														options={all_permissions}
														onChange={handleMultiplePermission}
														isClearable
														isSearchable={false}
													/>
													{errors.map((error, index) =>
														error.key === 'assigned_permission' ? (
															<Alert
																borderRadius={'4px'}
																key={index}
																status="error"
															>
																{error.message}
															</Alert>
														) : null,
													)}
												</Stack>
											</Stack>
											<Flex justifyContent={'flex-end'} marginTop={'24px'}>
												<Button
													_hover={{ backgroundColor: '#FFFFF' }}
													color={'#6B6B6B'}
													fontWeight={'600'}
													fontSize={'16px'}
													lineHeight={'24px'}
													mr={3}
													onClick={onClose}
												>
													Cancel
												</Button>
												<Button
													color={'#FFFFFF'}
													fontWeight={'500'}
													fontSize={'16px'}
													backgroundColor={PRIMARY_COLOR}
													padding={'10px 16px'}
													borderRadius={'4px'}
													border={`1px solid ${PRIMARY_COLOR}`}
													minW={'94px'}
													height={'39px'}
													_hover={{ backgroundColor: '#6328B3' }}
													onClick={(e) =>
														handleAddManager(userEmail, permissions)
													}
												>
													Confirm
												</Button>
											</Flex>
										</FormControl>
									</TabPanel>

									{/* Tab 2: Add by Role */}
									<TabPanel px={0} pt={6}>
										<Stack gap={4}>
											<Alert
												status="info"
												borderRadius="6px"
												bg="#E6F2FF"
												border="1px solid #B3D9FF"
											>
												<Box>
													<Text
														fontSize="14px"
														fontWeight="600"
														color="#222"
														mb={2}
													>
														{__(
															'Grant Admin Access to WordPress Roles',
															'everest-forms',
														)}
													</Text>
													<Text fontSize="13px" color="#6B6B6B">
														{__(
															'Select roles below to grant all users with that role admin access to Everest Forms.',
															'everest-forms',
														)}
													</Text>
												</Box>
											</Alert>
											<Box>
												<VStack align="stretch" spacing={2}>
													{Object.entries(allRoles).map(
														([roleKey, roleName]) => (
															<Box
																key={roleKey}
																px={3}
																py={2}
																bg="white"
																border="1px solid #e1e1e1"
																borderRadius="4px"
																transition="all 0.2s"
																cursor="pointer"
																_hover={{
																	bg: '#f9fafb',
																	borderColor: PRIMARY_COLOR,
																	boxShadow:
																		'0 1px 3px rgba(126, 59, 208, 0.1)',
																}}
																onClick={() =>
																	handleLocalRoleChange(
																		roleKey,
																		!(localRoleCheckedItems[roleKey] || false),
																	)
																}
															>
																<Checkbox
																	isChecked={
																		localRoleCheckedItems[roleKey] || false
																	}
																	onChange={(e) =>
																		handleLocalRoleChange(
																			roleKey,
																			e.target.checked,
																		)
																	}
																	size="sm"
																	colorScheme="purple"
																	pointerEvents="none"
																>
																	<Text
																		fontSize="14px"
																		fontWeight="500"
																		color="#383838"
																	>
																		{roleName.name}
																	</Text>
																</Checkbox>
															</Box>
														),
													)}
												</VStack>
											</Box>
										</Stack>
										<Flex justifyContent={'flex-end'} marginTop={'24px'}>
											<Button
												color={'#FFFFFF'}
												fontWeight={'500'}
												fontSize={'16px'}
												backgroundColor={PRIMARY_COLOR}
												padding={'10px 16px'}
												borderRadius={'4px'}
												border={`1px solid ${PRIMARY_COLOR}`}
												minW={'94px'}
												height={'39px'}
												_hover={{ backgroundColor: '#6328B3' }}
												onClick={handleRolePermissionDone}
											>
												Done
											</Button>
										</Flex>
									</TabPanel>
								</TabPanels>
							</Tabs>
						)}
					</ModalBody>
				</ModalContent>
			</Modal>
		</>
	);
};

export default UserDisplayModal;
