import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from "react";
import {
  ChakraProvider,
  Box,
  HStack,
  Text,
  Button,
  Icon,
} from "@chakra-ui/react";
import Main from "./components/Main";
import CreateWithAI from "./components/CreateWithAI";

const EVFIcon = (props) => (
  <Icon viewBox="0 0 24 24" {...props}>
    <path
      fill="#7e3bd0"
      d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"
    />
  </Icon>
);

const App = () => {
  const [currentView, setCurrentView] = useState<'templates' | 'ai'>(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get('view') === 'ai' ? 'ai' : 'templates';
  });

  useEffect(() => {
    const onPopState = () => {
      const params = new URLSearchParams(window.location.search);
      setCurrentView(params.get('view') === 'ai' ? 'ai' : 'templates');
    };
    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, []);

  const navigateToAI = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('view', 'ai');
    history.pushState({ view: 'ai' }, '', url.toString());
    setCurrentView('ai');
  };

  const navigateBack = () => {
    const url = new URL(window.location.href);
    url.searchParams.delete('view');
    history.pushState({ view: 'templates' }, '', url.toString());
    setCurrentView('templates');
  };

  const handleRefreshTemplates = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('refresh', Date.now().toString());
    window.location.href = url.toString();
  };

  return (
    <ChakraProvider>
      <Box
        bg="white"
        margin={currentView === 'ai' ? '0' : '24px'}
        border={currentView === 'ai' ? 'none' : '1px solid #e1e1e1'}
        borderRadius={currentView === 'ai' ? '0' : '13px'}
        overflow="hidden"
      >
        {currentView === 'ai' ? (
          <CreateWithAI onBack={navigateBack} />
        ) : (
          <>
            <HStack
              spacing={{ base: 4, md: 6 }}
              align="center"
              mb={0}
              borderBottom="1px solid #e1e1e1"
              p="0px 24px"
              direction={{ base: "column", md: "row" }}
              justify="space-between"
            >
              <HStack spacing={4} align="center">
                <EVFIcon boxSize="12" />
                <Text
                  borderLeft="1px solid #e1e1e1"
                  p="27px 0 27px 24px"
                  fontSize="18px"
                  fontWeight="semibold"
                  lineHeight="26px"
                  color="#383838"
                  margin="0px"
                >
                  {__("Add New Form", "everest-forms")}
                </Text>
              </HStack>
              <Button
                colorScheme="purple"
                variant="outline"
                onClick={handleRefreshTemplates}
                width={{ base: "full", md: "auto" }}
                display={{ base: "none", md: "inline-flex" }}
                fontSize="14px"
                lineHeight="20px"
                padding="8px 16px"
                fontWeight="medium"
                height="34px"
                borderRadius="4px"
              >
                {__("Refresh Templates", "everest-forms")}
              </Button>
            </HStack>

            <Box bg="white">
              <Main onCreateWithAI={navigateToAI} />
            </Box>
          </>
        )}
      </Box>
    </ChakraProvider>
  );
};

export default App;
