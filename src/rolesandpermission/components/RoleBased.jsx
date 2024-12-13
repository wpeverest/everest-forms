import { Box, Checkbox, Flex, Stack, Text, useToast } from "@chakra-ui/react";
import React, { useEffect, useState } from "react";
import { bulkAssignPermission, getWPRoles } from "./RoleAndPermissionAPI";
import UserDisplayModal from "./UserDisplayModal";

const RoleBased = () => {
  const [isAllChecked, setIsAllChecked] = useState(false);
  const [wpRoles, setWPRoles] = useState([]);
  const [evfPermission, setEvfPermission] = useState([]);
  const [checkedItems, setCheckedItems] = useState({});
  const [firstLoad,setFirstLoad] = useState(true);
  const toast = useToast();

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
              isChecked={firstLoad ? roleName.checked : checkedItems[roleKey]}
              onChange={(e) => handleIndividualCheck(roleKey, e.target.checked)}
            >
              {roleName.name}
            </Checkbox>
          ))}
        </Flex>
      </Stack>
    </Box>
  );
};

export default RoleBased;
