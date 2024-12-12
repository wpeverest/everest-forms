import { Box, Button, Checkbox, Flex, Stack, Text, useToast } from "@chakra-ui/react";
import React, { useEffect, useState } from "react";
import { bulkAssignPermission, getWPRoles } from "./RoleAndPermissionAPI";
import UserDisplayModal from "./UserDisplayModal";

const RoleBased = () => {
  const [isAllChecked, setIsAllChecked] = useState(false);
  const [wpRoles, setWPRoles] = useState([]);
  const [evfPermission, setEvfPermission] = useState([]);
  const [checkedItems, setCheckedItems] = useState({});
  const toast = useToast();

  const handleCheckAll = (e) => {
    const checked = e.target.checked;
    setIsAllChecked(checked);

    const updatedCheckedItems = Object.keys(wpRoles).reduce((acc, role) => {
      acc[role] = checked;
      return acc;
    }, {});
    setCheckedItems(updatedCheckedItems);
  };

  const handleIndividualCheck = (role, isChecked) => {
    const updatedCheckedItems = {
      ...checkedItems,
      [role]: isChecked,
    };
    setCheckedItems(updatedCheckedItems);

    const allChecked = Object.values(updatedCheckedItems).every((item) => item);
    setIsAllChecked(allChecked);
  };

  useEffect(() => {
    getWPRoles().then((res) => {
      setWPRoles(res.data.roles);
      setEvfPermission(res.data.permission.permissions);

      const initialCheckedItems = Object.keys(res.data.roles).reduce((acc, role) => {
        acc[role] = false;
        return acc;
      }, {});
      setCheckedItems(initialCheckedItems);
    });
  }, []);

  useEffect( ()=>{
			bulkAssignPermission( checkedItems ).then( (res)=> {
				if ( res.success ) {
					toast({
						title: res.message,
						status: "success",
						duration: 3000,
					  })
				}
			})
  },[ checkedItems ])

  return (
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
              isChecked={checkedItems[roleKey]}
              onChange={(e) => handleIndividualCheck(roleKey, e.target.checked)}
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
