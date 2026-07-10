import { ChakraProvider } from '@chakra-ui/react';
import React from 'react';
import { createRoot } from 'react-dom/client';
import BuilderAIChat from './BuilderAIChat';

// Mount the AI chat widget into the injected #evf-builder-ai div.
const mount = () => {
	let container = document.getElementById('evf-builder-ai');
	if (!container) {
		container = document.createElement('div');
		container.id = 'evf-builder-ai';
		document.body.appendChild(container);
	}
	createRoot(container).render(
		<ChakraProvider>
			<BuilderAIChat />
		</ChakraProvider>
	);
};

// Run after DOM is ready.
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', mount);
} else {
	mount();
}
