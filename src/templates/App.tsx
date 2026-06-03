import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from "react";
import {
  ChakraProvider,
  Box,
  HStack,
  Icon,
  Heading,
  Tooltip,
} from "@chakra-ui/react";
import { useQueryClient, useIsFetching } from "@tanstack/react-query";
import { FiRefreshCw, FiArrowLeft } from "react-icons/fi";
import Main from "./components/Main";
import CreateWithAI from "./components/CreateWithAI";

const WP_ELEMENTS = [
  '#wpadminbar',
  '#adminmenuwrap',
  '#adminmenuback',
  '#wpfooter',
];

const enterFullscreen = () => {
  WP_ELEMENTS.forEach(sel => {
    const el = document.querySelector<HTMLElement>(sel);
    if (el) el.style.display = 'none';
  });

  const wpContent = document.querySelector<HTMLElement>('#wpcontent');
  if (wpContent) {
    wpContent.dataset.origMargin = wpContent.style.marginLeft;
    wpContent.style.marginLeft = '0';
    wpContent.style.paddingTop = '0';
  }
  document.body.dataset.origPaddingTop = document.body.style.paddingTop;
  document.body.style.paddingTop = '0';
  document.body.style.marginTop = '0';

  document.documentElement.dataset.origPaddingTop = document.documentElement.style.paddingTop;
  document.documentElement.style.paddingTop = '0';
};

const exitFullscreen = () => {
  WP_ELEMENTS.forEach(sel => {
    const el = document.querySelector<HTMLElement>(sel);
    if (el) el.style.display = '';
  });
  const wpContent = document.querySelector<HTMLElement>('#wpcontent');
  if (wpContent) {
    wpContent.style.marginLeft = wpContent.dataset.origMargin ?? '';
    wpContent.style.paddingTop = '';
  }
  document.body.style.paddingTop = document.body.dataset.origPaddingTop ?? '';
  document.body.style.marginTop = '';
  document.documentElement.style.paddingTop = document.documentElement.dataset.origPaddingTop ?? '';
};

const App = () => {
  const queryClient = useQueryClient();
  const isFetching = useIsFetching() > 0;
  const [currentView, setCurrentView] = useState<'templates' | 'ai'>(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get('view') === 'ai' ? 'ai' : 'templates';
  });

  useEffect(() => {
    if (currentView === 'ai') {
      enterFullscreen();
    } else {
      exitFullscreen();
    }

    return () => { if (currentView === 'ai') exitFullscreen(); };
  }, [currentView]);

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
    queryClient.invalidateQueries(['templates']);
  };

  const handleBack = () => {
    window.history.back();
  };

  return (
    <ChakraProvider>
      {currentView === 'ai' ? (
        <Box bg="#f3f3f5" minHeight="100vh">
          <CreateWithAI onBack={navigateBack} />
        </Box>
      ) : (
        <Box
          bg="white"
          margin="20px"
          border="1px solid #e2e8f0"
          borderRadius="16px"
          overflow="hidden"
        >
          {/* Header */}
          <HStack
            as="header"
            h="56px"
            px="6"
            borderBottom="1px solid #e2e8f0"
            justify="space-between"
            align="center"
            bg="white"
          >
            <HStack spacing="3" align="center">
              <Box
                as="button"
                w="32px"
                h="32px"
                display="inline-flex"
                alignItems="center"
                justifyContent="center"
                borderRadius="8px"
                border="1px solid #e2e8f0"
                color="#383838"
                bg="transparent"
                cursor="pointer"
                onClick={handleBack}
                _hover={{ bg: "#f8fafc" }}
                transition="background 0.2s"
              >
                <Icon as={FiArrowLeft} boxSize="4" />
              </Box>
              <Heading as="h1" fontSize="16px" fontWeight="500" color="#0e0e0e" m="0">
                {__("Add New Form", "everest-forms")}
              </Heading>
            </HStack>
            <Tooltip label={__("Refresh Templates", "everest-forms")} placement="left" hasArrow>
              <Icon
                as={FiRefreshCw}
                boxSize="16px"
                color={isFetching ? '#7545BB' : '#999'}
                cursor={isFetching ? 'default' : 'pointer'}
                onClick={!isFetching ? handleRefreshTemplates : undefined}
                transition="color 0.2s"
                _hover={!isFetching ? { color: '#7545BB' } : {}}
                sx={isFetching ? { animation: 'spin 0.8s linear infinite', '@keyframes spin': { from: { transform: 'rotate(0deg)' }, to: { transform: 'rotate(360deg)' } } } : {}}
              />
            </Tooltip>
          </HStack>

          <Box>
            <Main onCreateWithAI={navigateToAI} />
          </Box>
        </Box>
      )}
    </ChakraProvider>
  );
};

export default App;
