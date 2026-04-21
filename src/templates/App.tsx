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
      <Box bg="white" margin="24px" border="1px solid #e1e1e1" borderRadius="13px" overflow="hidden">
        <HStack
          spacing={{ base: 4, md: 6 }}
          align="center"
          mb={0}
		      borderBottom="1px solid #e1e1e1"
          // p="20px 24px"
          p="0px 24px"
          direction={{ base: "column", md: "row" }}
        >
          <EVFIcon boxSize="12" />
          <Text borderLeft="1px solid #e1e1e1" p="27px 0 27px 24px" fontSize="18px" fontWeight="semibold" lineHeight="26px" color="#383838" textAlign={{ base: "center", md: "left" }} margin="0px">
            {__("Add New Form", "everest-forms")}
          </Text>
          <Button
            colorScheme="purple"
            variant="outline"
            onClick={handleRefreshTemplates}
            width={{ base: "full", md: "auto" }}
            display={{ base: "none", md: "inline-flex" }}
			fontSize= "14px"
			lineHeight="20px"
			padding="8px 16px"
      fontWeight="medium"
			height="34px"
			borderRadius="4px"
          >
            {__("Refresh Templates", "everest-forms")}
          </Button>
          <TabFilters onTabChange={handleTabChange} />
        </HStack>

        {/* Main Content Area */}
        <Box bg="white" >
          <VStack align="start" padding="24px 0px 32px"  gap="6px" display="none">
            <Heading as="h1" color="#383838" fontSize="20px"lineHeight="28px" letterSpacing="0.2px" fontWeight="medium" m={0}>
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
