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

const EVFIcon = (props) => (
  <Icon viewBox="0 0 24 24" {...props}>
    <path
      fill="#7e3bd0"
      d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"
    />
  </Icon>
);

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
            fontSize={{ base: "sm", md: "md", lg: "lg" }}
            px={{ base: 1, md: 2 }} // Add horizontal padding to tabs
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
      <Box margin={{ base: 4, md: 6, lg: 10 }} boxShadow="md">
        <HStack
          spacing={{ base: 4, md: 6 }} // Adjust spacing
          align="center"
          mb={5}
          bg="white"
          p={{ base: 3, md: 4 }}
          boxShadow="sm"
          direction={{ base: "column", md: "row" }}
        >
          <EVFIcon boxSize={{ base: 5, md: 6, lg: 7 }} />
          <Divider orientation="vertical" height={{ base: "16px", md: "24px", lg: "32px" }} />
          <Text fontSize={{ base: "md", md: "lg", lg: "xl" }} fontWeight="bold" textAlign={{ base: "center", md: "left" }}>
            {__("Add New Form", "everest-forms")}
          </Text>
          <Button
            colorScheme="purple"
            variant="outline"
            onClick={handleRefreshTemplates}
            size={{ base: "sm", md: "md", lg: "lg" }}
            width={{ base: "full", md: "auto" }}
            display={{ base: "none", md: "inline-flex" }} // Hide button on small screens
          >
            {__("Refresh Templates", "everest-forms")}
          </Button>
          <TabFilters onTabChange={handleTabChange} />
        </HStack>

        {/* Main Content Area */}
        <Box bg="white" p={{ base: 3, md: 5, lg: 6 }} rounded="md" boxShadow="sm">
          <VStack align="start" spacing={4}>
            <Heading as="h1" size={{ base: "md", md: "lg", lg: "xl" }} m={0}>
              {__("Select a Template", "everest-forms")}
            </Heading>
            <Text fontSize={{ base: "sm", md: "md", lg: "lg" }} color="gray.600">
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
	