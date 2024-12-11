import { Box, Button, Checkbox, Flex, Stack, Text } from "@chakra-ui/react";
import React, { useEffect, useState } from "react";
import { bulkAssignPermission, getWPRoles } from "./RoleAndPermissionAPI";
import UserDisplayModal from "./UserDisplayModal";

const RoleBased = () => {
	const [isAllChecked, setIsAllChecked] = useState(false);
	const [wpRoles, setWPRoles] = useState([]);
	const [evfPermission, setEvfPermission] = useState([]);
	const [checkedItems, setCheckedItems] = useState({
		Editor: false,
		Author: false,
		Contributor: false,
		Subscriber: false,
	});

	const handleCheckAll = (e) => {
		const checked = e.target.checked;
		setIsAllChecked(checked);
		setCheckedItems({
			Editor: checked,
			Author: checked,
			Contributor: checked,
			Subscriber: checked,
		});
	};

	const handleIndividualCheck = (role, isChecked) => {
		setCheckedItems((prev) => ({
			...prev,
			[role]: isChecked,
		}));

		const allChecked = Object.values({
			...checkedItems,
			[role]: isChecked,
		}).every((item) => item);
		setIsAllChecked(allChecked);
	};

	useEffect(() => {
		bulkAssignPermission().then((res) => {

		});
	}, [isAllChecked]);

	useEffect(()=>{
		getWPRoles().then((res)=>{
			setWPRoles(res.data.roles);
			setEvfPermission(res.data.permission.permissions);
		});
	}, []);

	return (
		<Box>
			<Flex justifyContent={"space-between"}>
				<Stack>
					<Text fontSize={"16px"} fontWeight="bold" width={"1187px"} height={"21px"} margin={"0"}>
						Role Based
					</Text>
					<Text fontSize={"14px"} fontWeight="normal" width={"1187px"} height={"21px"} margin={"0"}>
						By selecting additional roles below, you can give access to other user roles.
					</Text>
				</Stack>
				<Stack>
					<UserDisplayModal wp_roles={evfPermission} />
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
					>
						{roleName}
					</Checkbox>
				))}
				</Flex>
			</Stack>
		</Box>
	);
};

export default RoleBased;
