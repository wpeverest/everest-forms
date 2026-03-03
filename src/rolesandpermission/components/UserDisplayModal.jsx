import { AddIcon } from '@chakra-ui/icons';
import {
	Alert,
	Button,
	Flex,
	FormControl,
	FormLabel,
	Input,
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
import { __ } from '@wordpress/i18n';
import { Select } from 'chakra-react-select';
import { useEffect, useMemo, useState } from 'react';
import { addManagerRole } from './RoleAndPermissionAPI';

const UserDisplayModal = ({
	wp_roles,
	context = '',
	value = {},
	setUserAdded = false,
}) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [userEmail, setUserEmail] = useState('');
	const [permissions, setPermissions] = useState([]);
	const [errors, setErrors] = useState([]);
	const toast = useToast();

	useEffect(() => {
		if (context === 'edit') {
			setUserEmail(value.email || '');
			setPermissions(value.permission || []);
		}
	}, [context, value]);

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
					_hover={{ color: 'blue.500', textDecoration: 'none' }}
					onClick={onOpen}
				>
					{__('Edit', 'everest-forms')}
				</Button>
			) : (
				<Button style={addButtonStyles} onClick={onOpen}>
					<AddIcon
						height={'9.95px'}
						width={'9.9px'}
						fontWeight={'500'}
						color={'#FFFFFF'}
					/>{' '}
					{__('Add User', 'everest-forms')}
				</Button>
			)}

			<Modal isOpen={isOpen} onClose={onClose} isCentered size={'lg'}>
				<ModalOverlay />
				<ModalContent p={2}>
					<ModalHeader>
						{context === 'edit' ? 'Edit User' : 'Add User'}
						<Text mt={1}>
							{__(
								'View and manage the list of current managers, their assigned roles, and permissions.',
								'everest-forms',
							)}
						</Text>
					</ModalHeader>
					<ModalCloseButton />
					<ModalBody paddingTop={'0'}>
						<FormControl>
							<Stack gap={'28px'}>
								<Stack>
									<FormLabel display="flex" alignItems="center" fontSize="14px">
										{__('User Email', 'everest-forms')}
									</FormLabel>
									<Input
										required
										type="email"
										placeholder="User Email Address"
										value={userEmail}
										onChange={(e) => setUserEmail(e.target.value)}
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
									<FormLabel display="flex" alignItems="center" fontSize="14px">
										{__('User Permission', 'everest-forms')}
									</FormLabel>
									<Select
										required
										isMulti
										size="md"
										placeholder={__('Select user permission', 'everest-forms')}
										options={
											context === 'edit'
												? Object.entries(value.permission_details || {}).map(
														([key, label]) => ({
															value: key,
															label: label,
														}),
													)
												: all_permissions
										}
										value={context === 'edit' ? selectedPermissions : undefined}
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
							<Flex justifyContent={'flex-end'} mt={'6'} gap={3}>
								<Button
									fontWeight={'600'}
									lineHeight={'24px'}
									onClick={onClose}
									variant="outline"
								>
									{__('Back', 'learning-management-system')}
								</Button>
								<Button
									color={'#FFFFFF'}
									fontWeight={'500'}
									backgroundColor={'#7545BB'}
									padding={'10px 16px'}
									borderRadius={'4px'}
									border={'1px solid #7545BB'}
									width={'94px'}
									height={'39px'}
									_hover={{ backgroundColor: '#7545BB' }}
									onClick={(e) => handleAddManager(userEmail, permissions)}
								>
									{__('Confirm', 'learning-management-system')}
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
