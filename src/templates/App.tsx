import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from "react";
import {
  ChakraProvider,
  Box,
  HStack,
  Text,
  Icon,
  Tooltip,
} from "@chakra-ui/react";
import { FiRefreshCw } from "react-icons/fi";
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

// WordPress admin elements to hide in full-screen AI mode
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
  // Remove left margin and top padding the WP shell adds
  const wpContent = document.querySelector<HTMLElement>('#wpcontent');
  if (wpContent) {
    wpContent.dataset.origMargin = wpContent.style.marginLeft;
    wpContent.style.marginLeft = '0';
    wpContent.style.paddingTop = '0';
  }
  document.body.dataset.origPaddingTop = document.body.style.paddingTop;
  document.body.style.paddingTop = '0';
  document.body.style.marginTop = '0';
  // html.wp-toolbar also carries padding-top via --wp-admin--admin-bar--height
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
  const [currentView, setCurrentView] = useState<'templates' | 'ai'>(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get('view') === 'ai' ? 'ai' : 'templates';
  });

  // Apply / remove full-screen shell whenever the view changes
  useEffect(() => {
    if (currentView === 'ai') {
      enterFullscreen();
    } else {
      exitFullscreen();
    }
    // Restore on unmount (e.g. hard nav away)
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
    const url = new URL(window.location.href);
    url.searchParams.set('refresh', Date.now().toString());
    window.location.href = url.toString();
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
          margin="24px"
          border="1px solid #e1e1e1"
          borderRadius="13px"
          overflow="hidden"
        >
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
            <Tooltip label={__("Refresh Templates", "everest-forms")} placement="left" hasArrow>
              <Icon
                as={FiRefreshCw}
                boxSize="16px"
                color="#999"
                cursor="pointer"
                _hover={{ color: '#7545BB' }}
                onClick={handleRefreshTemplates}
                transition="color 0.2s"
              />
            </Tooltip>
          </HStack>

          <Box bg="white">
            <Main onCreateWithAI={navigateToAI} />
          </Box>
        </Box>
      )}
    </ChakraProvider>
  );
};

export default App;
