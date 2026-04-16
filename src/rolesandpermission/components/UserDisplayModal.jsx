import { AddIcon } from '@chakra-ui/icons';
import {
	Alert,
	Button,
	Flex,
	FormControl,
	FormLabel,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalHeader,
	ModalOverlay,
	Stack,
	Text,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { Select } from 'chakra-react-select';
import { debounce } from 'lodash';
import { useEffect, useMemo, useRef, useState } from 'react';
import { addManagerRole, getWPUsers } from './RoleAndPermissionAPI';

const selectChakraStyles = {
	dropdownIndicator: (provided) => ({ ...provided, bg: 'transparent' }),
	indicatorSeparator: (provided) => ({ ...provided, display: 'none' }),
	option: (provided) => ({ ...provided, fontSize: '13px' }),
	input: (base) => ({
		...base,
		border: 'none',
		outline: 'none',
		boxShadow: 'none',
		padding: 0,
		margin: 0,
		background: 'transparent',
		height: 'auto',
		minHeight: 0,
		width: 0,
	}),
};

const UserDisplayModal = ({ wp_roles, context = '', value = {} }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const queryClient = useQueryClient();
	const [selectedUser, setSelectedUser] = useState(null);
	const [permissions, setPermissions] = useState([]);
	const [errors, setErrors] = useState([]);
	const [isSubmitting, setIsSubmitting] = useState(false);
	const [searchTerm, setSearchTerm] = useState('');
	const toast = useToast();

	const debouncedSetSearch = useRef(
		debounce((val) => setSearchTerm(val), 400),
	).current;

	useEffect(() => () => debouncedSetSearch.cancel(), [debouncedSetSearch]);

	useEffect(() => {
		if (!isOpen) return;
		if (context === 'edit') {
			setSelectedUser(
				value.email ? { value: value.email, label: value.email } : null,
			);
			setPermissions(
				Array.isArray(value.permission) ? [...value.permission] : [],
			);
		} else {
			setSelectedUser(null);
			setPermissions([]);
			setSearchTerm('');
		}
		setErrors([]);
	}, [isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

	const {
		data: usersData,
		fetchNextPage,
		hasNextPage,
		isFetchingNextPage,
		isLoading: isUsersLoading,
	} = useInfiniteQuery({
		queryKey: ['wp-users', searchTerm],
		queryFn: ({ pageParam = 1 }) =>
			getWPUsers({ page: pageParam, search: searchTerm }),
		getNextPageParam: (lastPage, allPages) =>
			lastPage.has_more ? allPages.length + 1 : undefined,
		enabled: isOpen && context !== 'edit',
		staleTime: 30 * 1000,
		keepPreviousData: true,
	});

	const userOptions = useMemo(
		() => usersData?.pages.flatMap((page) => page.users) ?? [],
		[usersData],
	);

	const handleInputChange = (val) => {
		debouncedSetSearch(val);
	};

	const allPermissionOptions = useMemo(
		() =>
			Object.entries(wp_roles).map(([key, label]) => ({
				label,
				value: key,
			})),
		[wp_roles],
	);

	const handleMultiplePermission = (selectedOptions) => {
		setPermissions(selectedOptions ? selectedOptions.map((o) => o.value) : []);
	};

	const handleAddManager = () => {
		const email = selectedUser?.value;
		setIsSubmitting(true);
		addManagerRole(email, permissions)
			.then((res) => {
				setErrors([]);
				if (!res.success) {
					const errorList = Object.entries(res.message).map(
						([key, message]) => ({ key, message }),
					);
					setErrors(errorList);
				} else {
					onClose();
					toast({
						title: res.message,
						status: 'success',
						duration: 3000,
						isClosable: true,
					});

					if (context === 'edit') {
						queryClient.setQueriesData(
							{ queryKey: ['managers'] },
							(oldData) => {
								if (!oldData?.managers) return oldData;
								return {
									...oldData,
									managers: oldData.managers.map((m) =>
										m.email === email
											? { ...m, permissions: [...permissions] }
											: m,
									),
								};
							},
						);
					}

					queryClient.invalidateQueries({ queryKey: ['managers'] });
				}
			})
			.finally(() => setIsSubmitting(false));
	};

	const addButtonStyles = {
		width: '113px',
		height: '41px',
		backgroundColor: '#7545BB',
		padding: '10px 16px',
		gap: '6px',
		fontWeight: '500',
		lineHeight: '21px',
		fontSize: '14px',
		color: '#FFFFFF',
	};

	return (
		<>
			{context === 'edit' ? (
				<Button
					variant="link"
					color="gray.500"
					fontWeight="400"
					fontSize="13px"
					minW="auto"
					height="auto"
					padding={0}
					_hover={{ color: 'primary.400', textDecoration: 'none' }}
					onClick={onOpen}
				>
					{__('Edit', 'everest-forms')}
				</Button>
			) : (
				<Button style={addButtonStyles} onClick={onOpen}>
					<AddIcon
						height="9.95px"
						width="9.9px"
						fontWeight="500"
						color="#FFFFFF"
					/>{' '}
					{__('Add User', 'everest-forms')}
				</Button>
			)}

			<Modal isOpen={isOpen} onClose={onClose} isCentered size="lg">
				<ModalOverlay />
				<ModalContent p={2}>
					<ModalHeader>
						{context === 'edit'
							? __('Edit User', 'everest-forms')
							: __('Add User', 'everest-forms')}
						<Text mt={1} fontSize="sm" fontWeight="400" color="gray.500">
							{__(
								'View and manage the list of current managers, their assigned roles, and permissions.',
								'everest-forms',
							)}
						</Text>
					</ModalHeader>
					<ModalCloseButton />

					<ModalBody paddingTop="0">
						<FormControl>
							<Stack gap="28px">
								<Stack>
									<FormLabel display="flex" alignItems="center" fontSize="14px">
										{__('User Email', 'everest-forms')}
									</FormLabel>

									<Select
										placeholder={__('Select a user', 'everest-forms')}
										options={context === 'edit' ? [] : userOptions}
										value={selectedUser}
										onChange={setSelectedUser}
										onInputChange={
											context === 'edit' ? undefined : handleInputChange
										}
										filterOption={() => true}
										isLoading={
											context !== 'edit' &&
											(isUsersLoading || isFetchingNextPage)
										}
										isDisabled={context === 'edit'}
										isClearable={context !== 'edit'}
										isSearchable={false}
										menuIsOpen={context === 'edit' ? false : undefined}
										onMenuScrollToBottom={() => {
											if (hasNextPage && !isFetchingNextPage) {
												fetchNextPage();
											}
										}}
										noOptionsMessage={() =>
											isUsersLoading
												? __('Loading…', 'everest-forms')
												: __('No users found', 'everest-forms')
										}
										loadingMessage={() => __('Loading…', 'everest-forms')}
										chakraStyles={selectChakraStyles}
									/>

									{errors.map((error, index) =>
										error.key === 'user_email' ? (
											<Alert borderRadius="4px" key={index} status="error">
												{error.message}
											</Alert>
										) : null,
									)}
								</Stack>

								<Stack>
									<FormLabel display="flex" alignItems="center" fontSize="14px">
										{__('User Permission', 'everest-forms')}
									</FormLabel>

									<Select
										isMulti
										size="md"
										placeholder={__('Select user permission', 'everest-forms')}
										options={allPermissionOptions}
										value={permissions.map((val) => ({
											value: val,
											label: wp_roles[val] || val,
										}))}
										onChange={handleMultiplePermission}
										isClearable
										isSearchable={false}
										chakraStyles={selectChakraStyles}
									/>

									{errors.map((error, index) =>
										error.key === 'assigned_permission' ? (
											<Alert borderRadius="4px" key={index} status="error">
												{error.message}
											</Alert>
										) : null,
									)}
								</Stack>
							</Stack>

							<Flex justifyContent="flex-end" mt="6" gap={3}>
								<Button onClick={onClose} variant="outline">
									{__('Back', 'everest-forms')}
								</Button>
								<Button
									color="#FFFFFF"
									backgroundColor="#7545BB"
									padding="10px 16px"
									borderRadius="4px"
									border="1px solid #7545BB"
									width="94px"
									height="39px"
									_hover={{ backgroundColor: '#7545BB' }}
									onClick={handleAddManager}
									isLoading={isSubmitting}
								>
									{__('Confirm', 'everest-forms')}
								</Button>
							</Flex>
						</FormControl>
					</ModalBody>
				</ModalContent>
			</Modal>
		</>
	);
};

export default UserDisplayModal;
