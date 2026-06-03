import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from "react";
import {
  ChakraProvider,
  Box,
  Flex,
  Heading,
  Icon,
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
  '#evf-react-header-root',
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
          {/* Page title */}
          <Flex
            align="center"
            justify="space-between"
            px="8"
            py="5"
            borderBottom="1px solid #e2e8f0"
          >
            <Heading as="h2" fontSize="18px" fontWeight="600" color="#0e0e0e" m="0" letterSpacing="-0.01em">
              {__("Add New Form", "everest-forms")}
            </Heading>
            <Tooltip label={__("Refresh Templates", "everest-forms")} placement="left" hasArrow>
              <Icon
                as={FiRefreshCw}
                boxSize="15px"
                color={isFetching ? '#7545BB' : '#9ca3af'}
                cursor={isFetching ? 'default' : 'pointer'}
                onClick={!isFetching ? handleRefreshTemplates : undefined}
                transition="color 0.2s"
                _hover={!isFetching ? { color: '#7545BB' } : {}}
                sx={isFetching ? { animation: 'spin 0.8s linear infinite', '@keyframes spin': { from: { transform: 'rotate(0deg)' }, to: { transform: 'rotate(360deg)' } } } : {}}
              />
            </Tooltip>
          </Flex>

          <Box>
            <Main onCreateWithAI={navigateToAI} />
          </Box>
        </Box>
      )}
    </ChakraProvider>
  );
};

export default App;
