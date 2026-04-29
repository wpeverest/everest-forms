import { __ } from '@wordpress/i18n';
import React, { useState, useMemo } from "react";
import {
  ChakraProvider,
  Box,
  HStack,
  Text,
  Tabs,
  TabList,
  Tab,
  Button,
  Icon,
  Divider,
  VStack,
  Heading,
  Link,
} from "@chakra-ui/react";
import Main from "./components/Main";

const BackIcon = (props) => (
  <Icon viewBox="0 0 22 22" {...props}>
    <path d="M10.352 3.935a.917.917 0 0 1 1.296 1.297l-5.769 5.767 5.769 5.77a.916.916 0 1 1-1.296 1.296l-6.417-6.417a.917.917 0 0 1 0-1.296z"/><path d="M17.416 10.083a.917.917 0 0 1 0 1.834H4.583a.917.917 0 0 1 0-1.834z"/>
  </Icon>
);

const TabFilters = ({ onTabChange }) => {
  const filters = useMemo(() => [__("All", "everest-forms"), __("Free", "everest-forms"), __("Premium", "everest-forms")], []);

  return (
    <Tabs variant="unstyled" ml="auto" onChange={onTabChange}>
      <TabList background="#f3f4f6" gap="2px" borderRadius="5px" padding="4px">
        {filters.map((label) => (
          <Tab
            key={label}
            _selected={{
              color: "purple.500",
              background: "white",
              boxShadow: "0 4px 24px 0 rgba(10,10,10,.06)",
            }}
            fontSize="14px"
            lineHeight="25px"
            color="#646970"
            borderBottom="2px solid transparent"
            fontWeight="medium"
            whiteSpace="nowrap"
            height="32px"
            borderRadius="4px"
            padding="6px 16px"
          >
            {label}
          </Tab>
        ))}
      </TabList>
    </Tabs>
  );
};

const App = () => {
  const [selectedTab, setSelectedTab] = useState<string>(__("All", "everest-forms"));

  // Handle tab changes
  const handleTabChange = (index: number) => {
    const filters = [__("All", "everest-forms"), __("Free", "everest-forms"), __("Premium", "everest-forms")];
    setSelectedTab(filters[index]);
  };

  // Handle refresh button click
  const handleRefreshTemplates = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('refresh', Date.now().toString());
    window.location.href = url.toString();
  };

  return (
    <ChakraProvider>
      <Box bg="#f5f5f5">
        {/* Header Section */}
        <HStack
          spacing={4}
          align="center"
          justify="space-between"
          borderBottom="1px solid #e1e1e1"
          p="14px 24px"
          bg="white"
          width="100%"
        >
          <HStack spacing={4}>
            <Link
              href="admin.php?page=evf-builder"
              display="flex"
              alignItems="center"
              justifyContent="center"
              width="34px"
              height="34px"
              borderRadius="4px"
              border="1px solid transparent"
              color="#383838"
              _hover={{ bg: "#faf8fc", borderColor: "#eee8f7" }}
            >
              <BackIcon boxSize="5" />
            </Link>
            <Text fontSize="18px" fontWeight="semibold" lineHeight="26px" color="#383838" margin={'0'}>
              {__("Add New Form", "everest-forms")}
            </Text>
            <Button
              colorScheme="purple"
              variant="outline"
              onClick={handleRefreshTemplates}
              fontSize="14px"
              fontWeight="medium"
              height="34px"
              borderRadius="4px"
              padding="0 16px"
            >
              {__("Refresh Templates", "everest-forms")}
            </Button>
          </HStack>

          <HStack spacing={3}>
            <TabFilters onTabChange={handleTabChange} />
          </HStack>
        </HStack>

        {/* Main Content Area with Margin */}
        <Box p="24px">
          <Box bg="white" border="1px solid #e1e1e1" borderRadius="13px" overflow="hidden">
            <Main filter={selectedTab} />
          </Box>
        </Box>
      </Box>
    </ChakraProvider>
  );
};

export default App;
