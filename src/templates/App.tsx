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
            fontSize={{ base: "sm", md: "md" }} // Responsive font size
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
      <Box margin={{ base: 4, md: 6, lg: 10 }} boxShadow="md">
        {/* Header Section with white background */}
        <HStack
          spacing={{ base: 2, md: 4 }}
          align="center"
          mb={5}
          bg="white"
          p={{ base: 3, md: 4 }}
          boxShadow="sm"
          direction={{ base: "column", md: "row" }} // Stack items vertically on smaller screens
        >
          <CustomIcon boxSize={{ base: 5, md: 6 }} />
          <Divider orientation="vertical" height={{ base: "16px", md: "24px" }} />
          <Text fontSize={{ base: "md", md: "lg" }} fontWeight="bold">
            {__("Add New Form", "everest-forms")}
          </Text>
          <Button colorScheme="purple" variant="outline" onClick={handleRefreshTemplates}>
            {__("Refresh Templates", "everest-forms")}
          </Button>
          <TabFilters onTabChange={handleTabChange} />
        </HStack>

        {/* Main Content Area */}
        <Box bg="white" p={{ base: 3, md: 5 }} rounded="md" boxShadow="sm">
          <VStack align="start" spacing={4}>
            <Heading as="h1" size={{ base: "sm", md: "md" }} m={0}>
              {__("Select a Template", "everest-forms")}
            </Heading>
            <Text fontSize={{ base: "sm", md: "md" }} color="gray.600">
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
