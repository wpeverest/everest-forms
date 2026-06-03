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
import { FiRefreshCw } from "react-icons/fi";
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
          {/* Global header */}
          <HStack
            as="header"
            h="64px"
            px="7"
            borderBottom="1px solid #e2e8f0"
            justify="space-between"
            align="center"
            bg="white"
          >
            {/* Brand + title */}
            <HStack spacing="4" align="center">
              {/* EVF icon mark */}
              <Box
                w="32px"
                h="32px"
                borderRadius="8px"
                bgGradient="linear(135deg, #7545BB 0%, #9660db 100%)"
                display="flex"
                alignItems="center"
                justifyContent="center"
                flexShrink={0}
              >
                <Icon viewBox="0 0 24 24" boxSize="18px" color="white">
                  <path
                    fill="currentColor"
                    d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"
                  />
                </Icon>
              </Box>
              {/* Divider */}
              <Box w="1px" h="28px" bg="#e2e8f0" />
              {/* Page title */}
              <Heading as="h1" fontSize="20px" fontWeight="600" color="#0e0e0e" m="0" letterSpacing="-0.01em">
                {__("Add New Form", "everest-forms")}
              </Heading>
            </HStack>

            {/* Refresh */}
            <Tooltip label={__("Refresh Templates", "everest-forms")} placement="left" hasArrow>
              <Icon
                as={FiRefreshCw}
                boxSize="16px"
                color={isFetching ? '#7545BB' : '#9ca3af'}
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
