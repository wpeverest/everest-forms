import { ChakraProvider } from '@chakra-ui/react';
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { Header } from './components';
import Theme from './Theme/Theme';

(function () {
	const headerContainer = document.getElementById('evf-react-header-root');

	if (!headerContainer) return;

	const headerRoot = ReactDOM.createRoot(headerContainer);

	if (headerRoot) {
		headerRoot.render(
			<React.StrictMode>
				<ChakraProvider theme={Theme}>
					<BrowserRouter>
						<Header />
					</BrowserRouter>
				</ChakraProvider>
			</React.StrictMode>,
		);
	}
})();
