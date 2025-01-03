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
import { bulkRemoveManager, getManagers, getWPRoles, removeManager } from './RoleAndPermissionAPI';
import TrashUserRoleModel from './TrashUserRoleModel';
import UserDisplayModal from './UserDisplayModal';
import { debounce } from "lodash";

const UserRoleTable = () => {
	const [managers, setManagers] = useState([]);
	const [permissions, setPermissions] = useState([]);
	const [wpRoles, setWPRoles] = useState([]);
	const [userDeleted, setUserDeleted] = useState(false);
	const [selectedRows, setSelectedRows] = useState([]);
	const [bulkDelete, setBulkDelete] = useState(false);
	const [bulkDeleteSuccess, setBulkDeleteSuccess] = useState();
	const [searchManager, setSearchManager] = useState("");
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
		setSearchManager(val);
	}, 800);

	useEffect(()=>{
		getManagers( offset, pageSize, searchManager ).then((res)=> {
			if ( res.success ) {
				setManagers( res.managers );
				setTotalManagers(res.total)
				setPermissions( res.permissions.permissions);
			}

		})
	},[currentPage, pageSize, offset, userDeleted, bulkDeleteSuccess, searchManager]);

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
						<PaginationContainer justify="space-between" p={4}>
							<PaginationPrevious
								_hover={{ bg: "primary.200" }}
								bg="gray.200"
							>
								<FaChevronLeft />
							</PaginationPrevious>
							<PaginationPageGroup
								align="center"
								separator={
									<PaginationSeparator
										bg="blue.300"
										fontSize="sm"
										w={7}
										jumpSize={11}
									/>
								}
							>
								{pages?.map((page) => (
									<PaginationPage
										width={"7px"}
										bg="grey.200"
										key={`pagination_page_${page}`}
										page={page}
										fontSize="sm"
										_hover={{ bg: "primary.200" }}
										_current={{
											bg: "blue.300",
											fontSize: "sm",
											w: 7,
										}}
									/>
								))}
							</PaginationPageGroup>
							<PaginationNext
								_hover={{ bg: "primary.200" }}
								bg="gray.200"
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
