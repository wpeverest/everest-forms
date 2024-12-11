import { Box, Button, Checkbox, Flex, Input, InputGroup, InputRightElement, Stack, Table, Tbody, Td, Text, Th, Thead, Tr, useQuery, useToast } from '@chakra-ui/react';
import React, { useContext, useEffect, useState } from 'react';
import { __ } from "@wordpress/i18n";
import { SearchIcon, TriangleDownIcon, TriangleUpIcon } from '@chakra-ui/icons';
import { Select } from 'chakra-react-select';
import { getManagers, getWPRoles, removeManager } from './RoleAndPermissionAPI';
import TrashUserRoleModel from './TrashUserRoleModel';
import UserDisplayModal from './UserDisplayModal';

const UserRoleTable = () => {

	const [managers, setManagers ] = useState([]);
	const [permissions, setPermissions ] = useState([]);
	const [wpRoles, setWPRoles] = useState([]);
	const [userDeleted, setUserDeleted] = useState(false);
	const toast = useToast();

	useEffect(() => {
		getManagers().then((res) => {
			if ( res.success ) {
				setManagers( res.managers );
				setPermissions( res.permissions.permissions);
			}
		})
	}, [userDeleted])

	useEffect(()=>{
		getWPRoles().then((res)=>{
			setWPRoles(res.data.roles);
		});
	}, []);

	const deleteManager = ( userID) => {
		removeManager( userID ).then(
			(res) => {
				if ( res.success ) {
					setUserDeleted(true);
					toast({
						title: res.message,
						status: "success",
						duration: 3000,
					});
				}
			}
		)
	}
  return (
	<Stack gap={"20px"}>
		<Flex gap={"10px"} direction={"row"}>
				<InputGroup w="220px" h={"38px"}>
					<InputRightElement
						pointerEvents="none"
						children={<SearchIcon color="#6B6B6B" />}
					/>
					<Input
						placeholder="Search"
						focusBorderColor="blue.500"
						borderRadius={"4px"}
						padding={"10px 16px"}
					/>
				</InputGroup>
				<Select
					size="md"
					placeholder={__(
						"Bulk Actions",
						"everest-forms",
					)}
					options={[
						{
							label: __("Delete", "everest-forms"),
							value: "delete",
						},
					]}
					// onChange={(option) => setActionType(option?.value)}
					isClearable
					isSearchable={false}
				/>
				<Button
					minW="64px"
					minH="36px"
					borderRadius="3px"
					border="1px solid #475BB2"
					padding="8px 14px 8px 14px"
					type="button"
					bg="#F6F7F7"
				>
					<Text fontWeight="500" size="13px" lineHeight="19.5px" color={"#475BB2"} width={"36px"} height={"20px"}>
						{__("Apply", "everest-forms")}
					</Text>
				</Button>
		</Flex>

		<Stack>
			<Box borderWidth="1px" rounded="md" overflow="auto">
			<Table size="sm">
				<Thead>
				<Tr height={"48px"} textAlign={"left"}>
					<Th width={"31px"} borderTopLeftRadius={"5px"} border={"1px 1px 1px"} padding={"12px 16px"} gap={"5px"}>
						<Checkbox borderRadius={"2px"} borderColor={"#999999"}/>
					</Th>
					<Th width={"100px"} >ID <TriangleUpIcon color={"#6B6B6B"} /></Th>
					<Th  width={"107px"} >Name <TriangleUpIcon color={"#6B6B6B"} /></Th>
					<Th width={"217px"}>Email <TriangleUpIcon color={"#6B6B6B"} /></Th>
					<Th width={"206px"}>Role <TriangleUpIcon color={"#6B6B6B"} /></Th>
					{/* <Th width={"286.5px"}>Forms <TriangleUpIcon color={"#6B6B6B"} /></Th> */}
					<Th width={"286.5px"}>Permission <TriangleUpIcon color={"#6B6B6B"} /></Th>
					<Th width={"150px"}>Action</Th>
				</Tr>
				</Thead>
				<Tbody >
					{
						managers?.map((value) => (
							<Tr key={value.id} textAlign={"left"} height={"48px"}>
								<Td width={"31px"} borderTopLeftRadius={"5px"} border={"1px 1px 1px"} padding={"12px 16px"} gap={"5px"}>
									<Checkbox borderRadius={"2px"} borderColor={"#999999"}/>
								</Td>
								<Td>{value.id}</Td>
								<Td>{value.first_name}{" "}{value.last_name}</Td>
								<Td>{value.email}</Td>
								<Td>{value.roles}</Td>
								{/* <Td>Test Form</Td> */}
								<Td>
									<Flex gap={"4px"} flexWrap={"wrap"}>
										{ value.permissions.map( (permission, index) => (
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
										<UserDisplayModal wp_roles={wpRoles} context={"edit"} value={
											{'permission' : value.permissions, 'email' : value.email, 'permission_details' : permissions}
										} /> | <TrashUserRoleModel deleteManager={() => deleteManager(value.id)}/>
									</Flex>
								</Td>
							</Tr>
						))
					}

				</Tbody>
			</Table>
			</Box>
      </Stack>
	</Stack>
  )
}

export default UserRoleTable
