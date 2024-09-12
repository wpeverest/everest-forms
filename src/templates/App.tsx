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
      <Box bg="white" margin="20px" padding="0px 24px 50px" boxShadow="0px 4px 50px rgba(0, 0, 0, 0.06)">
        <HStack
          spacing={{ base: 4, md: 6 }}
          align="center"
          mb={0}
		  borderBottom="1px solid #CDD0D8"
         padding="20px 10px"
          direction={{ base: "column", md: "row" }}
        >
          <EVFIcon boxSize="12" />
          <Divider orientation="vertical" height="40px" borderColor="#CDD0D8" />
          <Text fontSize="18px" fontWeight="semibold" lineHeight="26px" color="#383838" textAlign={{ base: "center", md: "left" }} margin="0px">
            {__("Add New Form", "everest-forms")}
          </Text>
          <Button
            colorScheme="purple"
            variant="outline"
            onClick={handleRefreshTemplates}
            width={{ base: "full", md: "auto" }}
            display={{ base: "none", md: "inline-flex" }}
			fontSize= "15px"
			lineHeight="20px"
			padding="8px 16px"
			height="auto"
			borderRadius="4px"
          >
            {__("Refresh Templates", "everest-forms")}
          </Button>
          <TabFilters onTabChange={handleTabChange} />
        </HStack>

        {/* Main Content Area */}
        <Box bg="white" >
          <VStack align="start" padding="20px 0px 32px"  gap="12px">
            <Heading as="h1" fontSize="24px"lineHeight="34px" letterSpacing="0.4px" fontWeight="semibold" m={0}>
              {__("Select a Template", "everest-forms")}
            </Heading>
            <Text fontSize="14px" lineHeight="24px" color="#4D4D4D" fontWeight="400" margin="0px" >
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
