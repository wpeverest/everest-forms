import { Box, Button, Checkbox, Flex, Input, InputGroup, InputRightElement, Stack, Table, Tbody, Td, Text, Th, Thead, Tr, useQuery, useToast } from '@chakra-ui/react';
import React, { useContext, useEffect, useState } from 'react';
import { __ } from "@wordpress/i18n";
import { SearchIcon, TriangleDownIcon, TriangleUpIcon } from '@chakra-ui/icons';
import { Select } from 'chakra-react-select';
import { bulkRemoveManager, getManagers, getWPRoles, removeManager } from './RoleAndPermissionAPI';
import TrashUserRoleModel from './TrashUserRoleModel';
import UserDisplayModal from './UserDisplayModal';

const UserRoleTable = () => {
	const [managers, setManagers] = useState([]);
	const [permissions, setPermissions] = useState([]);
	const [wpRoles, setWPRoles] = useState([]);
	const [userDeleted, setUserDeleted] = useState(false);
	const [selectedRows, setSelectedRows] = useState([]);
	const [bulkDelete, setBulkDelete] = useState(false);
	const [bulkDeleteSuccess, setBulkDeleteSuccess] = useState();
	const toast = useToast();

	useEffect(() => {
	  getManagers().then((res) => {
		if ( res.success ) {
				setManagers( res.managers );
				setPermissions( res.permissions.permissions);
		}
	  });
	}, [userDeleted, bulkDeleteSuccess]);

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
			status: "success",
			duration: 3000,
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
		isChecked ? [...prevSelected, id] : prevSelected.filter((rowId) => rowId !== id)
	  );
	};

	const isAllSelected = managers.length > 0 && selectedRows.length === managers.length;
	const isIndeterminate = selectedRows.length > 0 && selectedRows.length < managers.length;

	const handleBulkDelete = () => {
		if ( !bulkDelete ) {
			toast({
				title: "Please choose bulk action.",
				status: 'error',
				duration: 3000
			})
		}
		if ( selectedRows.length === 0 ) {
			toast({
				title: "Please select user.",
				status: 'error',
				duration: 3000
			})
		}
		if( bulkDelete && selectedRows.length != 0 ){
			bulkRemoveManager( selectedRows ).then( (res) => {
				if ( res.success ) {
					toast({
						title: res.message,
						status: 'success',
						duration: 3000
					})

					setBulkDeleteSuccess(true);
					setSelectedRows([]);
				}else{
					toast({
						title: res.message,
						status: 'error',
						duration: 3000
					})
				}
			})
		}
	}

	return (
	  <Stack gap={"20px"}>
		<Flex gap={"10px"} direction={"row"}>
		  <InputGroup w="220px" h={"38px"}>
			<InputRightElement pointerEvents="none" children={<SearchIcon color="#6B6B6B" />} />
			<Input
			  placeholder="Search"
			  focusBorderColor="blue.500"
			  borderRadius={"4px"}
			  padding={"10px 16px"}
			/>
		  </InputGroup>
		  <Select
			size="md"
			placeholder={__("Bulk Actions", "everest-forms")}
			options={[
				{
				label: __("Delete", "everest-forms"),
				value: "delete",
				},
			]}
			isClearable
			isSearchable={false}
			onChange={(selectedOption) => setBulkDelete(selectedOption?.value || "")}
			/>
		  <Button
			minW="64px"
			minH="36px"
			borderRadius="3px"
			border="1px solid #475BB2"
			padding="8px 14px 8px 14px"
			type="button"
			bg="#F6F7F7"
			onClick={handleBulkDelete}
		  >
			<Text
			  fontWeight="500"
			  size="13px"
			  lineHeight="19.5px"
			  color={"#475BB2"}
			  width={"36px"}
			  height={"20px"}
			>
			  {__("Apply", "everest-forms")}
			</Text>
		  </Button>
		</Flex>

		<Stack>
		  <Box borderWidth="1px" rounded="md" overflow="auto">
			<Table size="sm">
			  <Thead>
				<Tr height={"48px"} textAlign={"left"}>
				  <Th>
					<Checkbox
					  isChecked={isAllSelected}
					  isIndeterminate={isIndeterminate}
					  onChange={(e) => handleSelectAll(e.target.checked)}
					/>
				  </Th>
				  <Th>ID</Th>
				  <Th>Name</Th>
				  <Th>Email</Th>
				  <Th>Role</Th>
				  <Th>Permission</Th>
				  <Th>Action</Th>
				</Tr>
			  </Thead>
			  <Tbody>
				{managers?.map((value) => (
				  <Tr key={value.id} textAlign={"left"} height={"48px"}>
					<Td>
					  <Checkbox
						isChecked={selectedRows.includes(value.id)}
						onChange={(e) => handleSelectRow(value.id, e.target.checked)}
					  />
					</Td>
					<Td>{value.id}</Td>
					<Td>{`${value.first_name} ${value.last_name}`}</Td>
					<Td>{value.email}</Td>
					<Td>{value.roles}</Td>
					<Td>
					  <Flex gap={"4px"} flexWrap={"wrap"}>
						{value.permissions.map((permission, index) => (
						  <Text
							margin={"0"}
							cursor={"pointer"}
							height="22px"
							fontSize={"12px"}
							fontWeight={"400"}
							backgroundColor={"#EDEDED"}
							color={"#383838"}
							padding={"2px 6px"}
							borderRadius={"5px"}
							key={index}
						  >
							{permissions[permission]}
						  </Text>
						))}
					  </Flex>
					</Td>
					<Td>
					  <Flex alignItems={"center"}>
						<UserDisplayModal
						  wp_roles={wpRoles}
						  context={"edit"}
						  value={{
							permission: value.permissions,
							email: value.email,
							permission_details: permissions,
						  }}
						/>{" "}
						| <TrashUserRoleModel deleteManager={() => deleteManager(value.id)} />
					  </Flex>
					</Td>
				  </Tr>
				))}
			  </Tbody>
			</Table>
		  </Box>
		</Stack>
	  </Stack>
	);
  };

  export default UserRoleTable;
