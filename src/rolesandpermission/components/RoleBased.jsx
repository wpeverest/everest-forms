import { Box, Button, Checkbox, Flex, Stack, Text } from "@chakra-ui/react";
import React, { useEffect, useState } from "react";
import { bulkAssignPermission, getWPRoles } from "./RoleAndPermissionAPI";

const RoleBased = () => {
	const [isAllChecked, setIsAllChecked] = useState(false);
	const [wpRoles, setWPRoles] = useState([]);
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
			setWPRoles(res.data);
		});
	}, []);

	return (
		<Box>
			<Flex>
				<Stack>
					<Text fontSize={"16px"} fontWeight="bold" width={"1187px"} height={"21px"} margin={"0"}>
						Role Based
					</Text>
					<Text fontSize={"14px"} fontWeight="normal" width={"1187px"} height={"21px"} margin={"0"}>
						By selecting additional roles below, you can give access to other user roles.
					</Text>
				</Stack>
				<Stack>
					<Button
						width={"113px"}
						height={"41px"}
						backgroundColor={"#7545BB"}
						padding={"10px 16px"}
						gap={"6px"}
						fontWeight={"500"}
						lineHeight={"21px"}
						fontSize={"14px"}
						color={"#FFFFFF"}
						_hover={{ backgroundColor: "#7545BB" }}
					>
						+ Add User
					</Button>
				</Stack>
			</Flex>
			<Stack margin={"24px 0px"}>
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
