import { Box, Button, Checkbox, Flex, Input, InputGroup, InputRightElement, Stack, Table, Tbody, Td, Text, Th, Thead, Tr, useQuery, useToast } from '@chakra-ui/react';
import { FaChevronLeft, FaChevronRight } from "react-icons/fa";
import {
	Pagination,
	PaginationContainer,
	PaginationNext,
	PaginationPage,
	PaginationPageGroup,
	PaginationPrevious,
	PaginationSeparator,
	usePagination } from "@ajna/pagination";
import React, { useEffect, useState } from 'react';
import { __ } from "@wordpress/i18n";
import { SearchIcon } from '@chakra-ui/icons';
import { Select } from 'chakra-react-select';
import { bulkAssignPermission, bulkRemoveManager, getManagers, getWPRoles, removeManager } from './RoleAndPermissionAPI';
import TrashUserRoleModel from './TrashUserRoleModel';
import UserDisplayModal from './UserDisplayModal';
import { debounce } from "lodash";

const UserRoleTable = () => {

	const [isAllChecked, setIsAllChecked] = useState(false);
	const [evfPermission, setEvfPermission] = useState([]);
	const [checkedItems, setCheckedItems] = useState({});
	const [firstLoad,setFirstLoad] = useState(true);

	const [managers, setManagers] = useState([]);
	const [permissions, setPermissions] = useState([]);
	const [wpRoles, setWPRoles] = useState([]);
	const [userDeleted, setUserDeleted] = useState(false);
	const [selectedRows, setSelectedRows] = useState([]);
	const [bulkDelete, setBulkDelete] = useState(false);
	const [bulkDeleteSuccess, setBulkDeleteSuccess] = useState();
	const [searchManager, setSearchManager] = useState("");
	const [userAdded, setUserAdded] = useState(false);
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

	const handlePageSizeChange = ( selectedOption ) => {
		const pageSize = Number( selectedOption .value);
		setPageSize(pageSize);
	};

	const debounceSearch = debounce((val) => {
		setCurrentPage(1);
		setSearchManager(val);
	}, 800);

	useEffect(()=>{
		getManagers( offset, pageSize, searchManager ).then((res)=> {
			if ( res.success ) {
				setUserAdded(false);
				setUserDeleted(false);
				setManagers( res.managers );
				setTotalManagers(res.total)
				setPermissions( res.permissions.permissions);
			}

		})
	},[currentPage, pageSize, offset, userDeleted, bulkDeleteSuccess, searchManager, userAdded]);

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

	const handleCheckAll = (e) => {
		setFirstLoad(false);
		const checked = e.target.checked;
		setIsAllChecked(checked);

		const updatedCheckedItems = Object.keys(wpRoles).reduce((acc, role) => {
		  acc[role] = checked;
		  return acc;
		}, {});

		setCheckedItems(updatedCheckedItems);

		bulkAssignPermission(updatedCheckedItems).then((res) => {
		  if (res.success) {
			toast({
			  title: res.message,
			  status: "success",
			  duration: 3000,
			});
		  } else {
			toast({
			  title: res.message || "Something went wrong",
			  status: "error",
			  duration: 3000,
			});
		  }
		});
	  };

	  const handleIndividualCheck = (role, isChecked) => {
		setFirstLoad(false);
		const updatedCheckedItems = {
		  ...checkedItems,
		  [role]: isChecked,
		};
		setCheckedItems(updatedCheckedItems);

		bulkAssignPermission(updatedCheckedItems).then((res) => {
		  if (res.success) {
			toast({
			  title: res.message,
			  status: "success",
			  duration: 3000,
			});
		  } else {
			toast({
			  title: res.message || "Something went wrong",
			  status: "error",
			  duration: 3000,
			});
		  }
		});

		const allChecked = Object.values(updatedCheckedItems).every((item) => item);
		setIsAllChecked(allChecked);
	  };

	  useEffect(() => {
		getWPRoles().then((res) => {
		  setWPRoles(res.data.roles);
		  setEvfPermission(res.data.permission.permissions);

		  const initialCheckedItems = Object.keys(res.data.roles).reduce((acc, role) => {
			acc[role] = res.data.roles[role].checked;
			return acc;
		  }, {});
		  setCheckedItems(initialCheckedItems);
		});
	  }, []);

	const handleBulkDelete = () => {

		if ( selectedRows.length === 0 ) {
			toast({
				title: "Please select user.",
				status: 'error',
				duration: 3000
			})
		}else if ( !bulkDelete ) {
			toast({
				title: "Please choose bulk action.",
				status: 'error',
				duration: 3000
			})
		}else if( bulkDelete && selectedRows.length != 0 ){
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
		{/* Role Based */}
		<Box>
			<Flex justifyContent={"space-between"}>
				<Stack>
				<Text fontSize={"16px"} fontWeight="bold" height={"21px"} margin={"0"}>
					Role Based
				</Text>
				<Text fontSize={"14px"} fontWeight="normal" height={"21px"} margin={"0"}>
					By selecting additional roles below, you can give access to other user roles.
				</Text>
				</Stack>
				<Stack>
				<UserDisplayModal wp_roles={evfPermission} setUserAdded={setUserAdded} />
				</Stack>
			</Flex>

			<Stack margin={"24px 0px"} borderBottom={"1px solid #DCDCDC"} paddingBottom={"24px"}>
				<Checkbox
				width={"374px"}
				isChecked={isAllChecked}
				onChange={(e) => handleCheckAll(e)}
				>
				Check All
				</Checkbox>

				<Flex marginTop={"8px"} gap={"18px"}>
				{Object.entries(wpRoles).map(([roleKey, roleName]) => (
					<Checkbox
					key={roleKey}
					isChecked={firstLoad ? roleName.checked : checkedItems[roleKey]}
					onChange={(e) => handleIndividualCheck(roleKey, e.target.checked)}
					>
					{roleName.name}
					</Checkbox>
				))}
				</Flex>
			</Stack>
    	</Box>

	{/* User Role Table */}
		<Flex gap={"10px"} direction={"row"}>
		  <InputGroup w="220px" h={"38px"}>
			<InputRightElement pointerEvents="none" children={<SearchIcon color="#6B6B6B" />} />
			<Input
			  placeholder="Search"
			  focusBorderColor="blue.500"
			  borderRadius={"4px"}
			  padding={"10px 16px"}
			  onChange={(e) => debounceSearch(e.target.value)}
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
			  {totalManagers === 0 ? (
						<Tr>
							<Td colSpan="7">
								<Flex justify="center" align="center" flexDirection={"column"} height="100%">
									<img height={"236px"} width={"262px"} src={evf_roles_and_permission.not_found_image}/>
									<Stack marginTop={"16px"} textAlign={"center"} gap={0}>
										<Text margin={0} fontSize="lg" color="#222222" fontWeight={600}>
											{__("You don’t have any Manager yet", "everest-forms")}
										</Text>
										<Text margin={"8px 0 0 0"} fontSize="sm" color="#6B6B6B" fontWeight={400}>
											{__("Please create a manager and you are good to go.", "everest-forms")}
										</Text>
									</Stack>
								</Flex>
							</Td>
						</Tr>
					) : (
						managers?.map((value) => (
							<Tr key={value.id} textAlign="left" height="48px">
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
									<Flex gap="4px" flexWrap="wrap">
										{value.permissions.map((permission, index) => (
											<Text
												key={index}
												margin="0"
												cursor="pointer"
												height="22px"
												fontSize="12px"
												fontWeight="400"
												backgroundColor="#EDEDED"
												color="#383838"
												padding="2px 6px"
												borderRadius="5px"
											>
												{permissions[permission]}
											</Text>
										))}
									</Flex>
								</Td>
								<Td>
									<Flex alignItems="center">
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
										{" | "}
										<TrashUserRoleModel deleteManager={() => deleteManager(value.id)} />
									</Flex>
								</Td>
							</Tr>
						))
					)}
			  </Tbody>
			</Table>
		  </Box>

		  {/* Pagination */}
			<Stack mt={3}>
				<Flex alignItems="center" justify="space-between">
					<Flex alignItems="center">
						<Text fontSize="md" p={"4"}>
							{__("Show per page", "everest-forms")}
						</Text>
						<Select
							onChange={handlePageSizeChange}
							colorScheme="primary"
							isSearchable={false}
							options={mappedOptions}
							defaultValue={mappedOptions[0]}
						/>
					</Flex>
						<Pagination
							pagesCount={pagesCount}
							currentPage={currentPage}
							isDisabled={isDisabled}
							onPageChange={handlePageChange}
						>
							<PaginationContainer justify="space-between" padding={"4px"}>
								<PaginationPrevious
									_hover={{ bg: "#BEE3F8" }}
									bg="#E2E8F0"
									width={"38px"}
									height={"26px"}
								>
									<FaChevronLeft />
								</PaginationPrevious>
								<PaginationPageGroup
									align="center"
									marginLeft={"0"}
									separator={
										<PaginationSeparator
											bg="#63B3ED"
											fontSize="12px"
											width={"38px"}
											height={"26px"}
											jumpSize={"11px"}
										/>
									}
								>
									{pages?.map((page) => (
										<PaginationPage
											width={"38px"}
											height={"26px"}
											bg="#EDF2F7"
											key={`pagination_page_${page}`}
											padding={"4px 16px"}
											borderRadius={"3px"}
											page={page}
											fontSize="12px"
											_hover={{ bg: "#BEE3F8" }}
											_current={{
												bg: "#63B3ED",
												fontSize: "14px",
												width:"38px",
												height:"26px",
											}}
										/>
									))}
								</PaginationPageGroup>
								<PaginationNext
									_hover={{ bg: "#BEE3F8" }}
									bg="#E2E8F0"
									width={"38px"}
									height={"26px"}
								>
									<FaChevronRight />
								</PaginationNext>
							</PaginationContainer>
						</Pagination>
				</Flex>
			</Stack>
		</Stack>
	  </Stack>
	);
  };

  export default UserRoleTable;
