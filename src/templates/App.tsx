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
} from "@chakra-ui/react";
import Main from "./components/Main";

// Define Custom Icon
const CustomIcon = (props) => (
  <Icon viewBox="0 0 24 24" {...props}>
    <path
      fill="#7e3bd0"
      d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"
    />
  </Icon>
);

// Extracted component for tab filters
const TabFilters = ({ onTabChange }) => {
  const filters = useMemo(() => [__("All", "everest-forms"), __("Free", "everest-forms"), __("Premium", "everest-forms")], []);

  return (
    <Tabs variant="unstyled" ml="auto" onChange={onTabChange}>
      <TabList>
        {filters.map((label) => (
          <Tab
            key={label}
            _selected={{
              color: "purple.500",
              fontWeight: "bold",
              borderBottom: "2px solid",
              borderColor: "purple.500",
            }}
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
    url.searchParams.set('refresh', Date.now());
    window.location.href = url.toString();
  };

  return (
    <ChakraProvider>
      <Box margin={10} boxShadow="md">
        {/* Header Section with white background */}
        <HStack spacing={4} align="center" mb={5} bg="white" p={4} boxShadow="sm">
          <CustomIcon boxSize={6} />
          <Divider orientation="vertical" height="24px" />
          <Text fontSize="lg" fontWeight="bold">
            {__("Add New Form", "everest-forms")}
          </Text>
          <Button colorScheme="purple" variant="outline" onClick={handleRefreshTemplates}>
            {__("Refresh Templates", "everest-forms")}
          </Button>
          <TabFilters onTabChange={handleTabChange} />
        </HStack>

        {/* Main Content Area */}
        <Box bg="white" p={5} rounded="md" boxShadow="sm">
          <VStack align="start" spacing={4}>
            <Heading as="h1" size="md" m={0}>
              {__("Select a Template", "everest-forms")}
            </Heading>
            <Text fontSize="md" color="gray.600">
              {__(
                "To get started quickly, you can pick from our ready-made templates, begin with a blank form, or design your own.",
                "everest-forms"
              )}
            </Text>
          </VStack>
          <Main filter={selectedTab} />
        </Box>
      </Box>
    </ChakraProvider>
  );
};

export default App;
