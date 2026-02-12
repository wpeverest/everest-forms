import React, { useState, useEffect, useMemo } from "react";
import { AddIcon, InfoIcon } from "@chakra-ui/icons";
import {
	Modal,
	ModalOverlay,
	ModalContent,
	ModalHeader,
	ModalBody,
	ModalCloseButton,
	useDisclosure,
	Button,
	Text,
	FormControl,
	FormLabel,
	Input,
	Tooltip,
	Icon,
	Stack,
	Alert,
	Flex,
	useToast,
} from "@chakra-ui/react";
import { Select } from "chakra-react-select";
import { __ } from "@wordpress/i18n";
import { addManagerRole } from "./RoleAndPermissionAPI";

const UserDisplayModal = ({ wp_roles, context = "", value = {}, setUserAdded=false }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [userEmail, setUserEmail] = useState("");
	const [permissions, setPermissions] = useState([]);
	const [errors, setErrors] = useState([]);
	const toast = useToast();

	useEffect(() => {
		if (context === "edit") {
			setUserEmail(value.email || "");
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
				const errorList = Object.entries(res.message).map(
					([key, message]) => ({ key, message })
				);
				setErrors(errorList);
			} else {
				setUserAdded(true);
				onClose();
				toast({
					title: res.message,
					status: "success",
					duration: 3000,
				});
			}
		});
	};

	const addButtonStyles = {
		width: "113px",
		height: "41px",
		backgroundColor: "#7545BB",
		padding: "10px 16px",
		gap: "6px",
		fontWeight: "500",
		lineHeight: "21px",
		fontSize: "14px",
		color: "#FFFFFF",
	};
	const editButtonStyle = {
		color: "#475BB2",
		fontWeight: "400",
		fontSize: "13px",
		backgroundColor: "#ffffff",
		padding: 0,
	};

	return (
		<>
			<Button
				style={context === "edit" ? editButtonStyle : addButtonStyles}
				onClick={onOpen}
			>
				{context === "edit" ? (
					"Edit"
				) : (
					<>
						<AddIcon
							height={"9.95px"}
							width={"9.9px"}
							fontWeight={"500"}
							color={"#FFFFFF"}
						/>{" "}
						Add User
					</>
				)}
			</Button>

			<Modal isOpen={isOpen} onClose={onClose} isCentered size={"lg"}>
				<ModalOverlay
					bg="none"
					backdropFilter="auto"
					backdropInvert="0%"
					backdropBlur="2px"
				/>
				<ModalContent>
					<ModalHeader>
						{context === "edit" ? "Edit User" : "Add User"}
						<Text>
							View and manage the list of current managers, their assigned roles,
							and permissions.
						</Text>
					</ModalHeader>
					<ModalCloseButton />
					<ModalBody paddingTop={"0"}>
						<FormControl>
							<Stack gap={"28px"}>
								<Stack>
									<FormLabel display="flex" alignItems="center" fontSize="14px">
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
												border={"none"}
												_hover={{ cursor: "pointer" }}
											/>
										</Tooltip>
									</FormLabel>
									<Input
										required
										type="email"
										placeholder="User Email Address"
										value={userEmail}
										onChange={(e) => setUserEmail(e.target.value)}
									/>
									{errors.map((error, index) =>
										error.key === "user_email" ? (
											<Alert
												borderRadius={"4px"}
												key={index}
												status="error"
											>
												{error.message}
											</Alert>
										) : null
									)}
								</Stack>

								<Stack>
									<FormLabel display="flex" alignItems="center" fontSize="14px">
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
												border={"none"}
												_hover={{ cursor: "pointer" }}
											/>
										</Tooltip>
									</FormLabel>
									<Select
										required
										isMulti
										size="md"
										placeholder={__(
											"Select user permission",
											"everest-forms"
										)}
										options={
											context === "edit"
												? Object.entries(value.permission_details || {}).map(
														([key, label]) => ({
															value: key,
															label: label,
														})
												  )
												: all_permissions
										}
										value={context === "edit" ? selectedPermissions : undefined}
										onChange={handleMultiplePermission}
										isClearable
										isSearchable={false}
									/>
									{errors.map((error, index) =>
										error.key === "assigned_permission" ? (
											<Alert
												borderRadius={"4px"}
												key={index}
												status="error"
											>
												{error.message}
											</Alert>
										) : null
									)}
								</Stack>
							</Stack>
							<Flex justifyContent={"flex-end"} marginTop={"24px"}>
								<Button
									_hover={{ backgroundColor: "#FFFFF" }}
									color={"#6B6B6B"}
									fontWeight={"600"}
									fontSize={"16px"}
									lineHeight={"24px"}
									mr={3}
									onClick={onClose}
								>
									Back
								</Button>
								<Button
									color={"#FFFFFF"}
									fontWeight={"500"}
									fontSize={"16px"}
									backgroundColor={"#7545BB"}
									padding={"10px 16px"}
									borderRadius={"4px"}
									border={"1px solid #7545BB"}
									width={"94px"}
									height={"39px"}
									_hover={{ backgroundColor: "#7545BB" }}
									onClick={(e) =>
										handleAddManager(userEmail, permissions)
									}
								>
									Confirm
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
