import React from "react";
import {
  ChakraProvider,
  Box,
  VStack,
  Heading,
  Text,
  HStack,
  TabList,
  Tab,
  Tabs,
  Button,
  Icon,
  Divider,
  Stack,
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

const App = () => {
  return (
    <ChakraProvider>
      <Box margin={10} boxShadow="md">
        {/* Header Section with white background */}
        <HStack spacing={4} align="center" mb={5} bg="white" p={4} boxShadow="sm">
          <CustomIcon boxSize={6} />
          <Divider orientation="vertical" height="24px" />
          <Text fontSize="lg" fontWeight="bold">
            Add New Form
          </Text>
          <Button colorScheme="purple" variant="outline">
            Refresh Templates
          </Button>
          <Tabs variant="unstyled" ml="auto">
            <TabList>
              {["All", "Free", "Premium"].map((label) => (
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
        </HStack>

        {/* Main Content Area */}
        <Box bg="white" p={5} rounded="md" boxShadow="sm">
          <VStack align="start" spacing={4}>
            {/* Heading with margin bottom */}
            <Heading as="h1" size="md" m={0}>
              Select a Template
            </Heading>
            <Text fontSize="md" color="gray.600">
              To get started quickly, you can pick from our ready-made templates, begin with a blank form, or design your own.
            </Text>
          </VStack>

          {/* Content component */}
          <Main />
        </Box>
      </Box>
    </ChakraProvider>
  );
};

export default App;
