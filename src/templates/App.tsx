import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from "react";
import {
  ChakraProvider,
  Box,
  Flex,
  Heading,
  Link,
} from "@chakra-ui/react";
import Main from "./components/Main";
import CreateWithAI from "./components/CreateWithAI";

// The AI view's PageShell is a fixed full-viewport overlay at z-index 100000.
// Chakra's toast manager renders into a portal on document.body at
// `zIndex: var(--toast-z-index, 5500)` — well below the overlay — so toasts
// fired from that screen were painted underneath and never seen. Chakra does
// NOT bind --toast-z-index to the theme, so the only fix is to set the CSS
// variable on an ancestor of the portal (the <html> element).
const TOAST_Z_INDEX = 200001;

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
  const [currentView, setCurrentView] = useState<'templates' | 'ai'>(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get('view') === 'ai' ? 'ai' : 'templates';
  });
  // When entering the AI view from a template's "Edit with AI", the draft form to
  // preload (0 = fresh "Create with AI").
  const [aiFormId, setAiFormId] = useState(0);
  const [aiTitle, setAiTitle] = useState('');

  // Lift Chakra's toast layer above the AI overlay (PageShell, z-index 100000).
  // The toast portal mounts on document.body, so the --toast-z-index variable
  // must live on an ancestor of it — set it on <html> for the App's lifetime.
  useEffect(() => {
    const root = document.documentElement;
    const prev = root.style.getPropertyValue('--toast-z-index');
    root.style.setProperty('--toast-z-index', String(TOAST_Z_INDEX));
    return () => {
      if (prev) root.style.setProperty('--toast-z-index', prev);
      else root.style.removeProperty('--toast-z-index');
    };
  }, []);

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

  const navigateToAI = (formId: number = 0, title: string = '') => {
    // Coerce: only a real positive form id loads a prefilled form; anything else
    // (e.g. a stray click event passed by a button) opens the blank prompt screen.
    const id = typeof formId === 'number' && Number.isFinite(formId) && formId > 0 ? formId : 0;
    const t  = typeof title === 'string' ? title : '';
    setAiFormId(id);
    setAiTitle(t);
    const url = new URL(window.location.href);
    url.searchParams.set('view', 'ai');
    history.pushState({ view: 'ai' }, '', url.toString());
    setCurrentView('ai');
  };

  const navigateBack = () => {
    setAiFormId(0);
    setAiTitle('');
    const url = new URL(window.location.href);
    url.searchParams.delete('view');
    history.pushState({ view: 'templates' }, '', url.toString());
    setCurrentView('templates');
  };

  return (
    <ChakraProvider>
      {currentView === 'ai' ? (
        <Box bg="#f3f3f5" minHeight="100vh">
          <CreateWithAI onBack={navigateBack} initialFormId={aiFormId} initialTitle={aiTitle} />
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
            px="8"
            py="5"
            borderBottom="1px solid #e2e8f0"
          >
            <Heading as="h2" fontSize="18px" fontWeight="600" color="#0e0e0e" m="0" letterSpacing="-0.01em">
              {__("Add New Form", "everest-forms")}
            </Heading>
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
