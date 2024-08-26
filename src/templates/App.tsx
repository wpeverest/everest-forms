import React from "react";
import { ChakraProvider, Box, VStack, Heading, Text } from "@chakra-ui/react";
import Main from "./components/Main";

const App = () => {
  return (
    <ChakraProvider>
      <Box p={4}>
        <VStack align="stretch" spacing={4}>
          <Heading as="h1" size="xl">Select a Template</Heading>
          <Text>
            To get started quickly, you can pick from our ready-made templates, begin with a blank form, or design your own.
          </Text>
          <Main />
        </VStack>
      </Box>
    </ChakraProvider>
  );
};

export default App;
