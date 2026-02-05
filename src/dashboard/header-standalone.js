import { ChakraProvider, useToast } from '@chakra-ui/react';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { Header } from './components';
import Theme from './Theme/Theme';

const queryClient = new QueryClient();

function HeaderWithQuery() {
	const toast = useToast();

	/* global _EVF_DASHBOARD_ */
	const {
		evfRestApiNonce,
		restURL,
		allStepsCompleted,
	} =
		typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_;

	const siteAssistantQuery = useQuery({
		queryKey: ['siteAssistant'],
		queryFn: async () => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/site-assistant`,
				method: 'GET',
				headers: {
					'X-WP-Nonce': evfRestApiNonce,
				},
			});
			return response;
		},
		cacheTime: Infinity,
		staleTime: Infinity,
		retry: 1,
		onError: (error) => {
			console.error('Error fetching site assistant data:', error);
			toast({
				title: __('Error', 'everest-forms'),
				description: __('Failed to load setup status.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		},
	});

	const isAllStepsCompleted = siteAssistantQuery?.isLoading
		? Boolean(allStepsCompleted === '1')
		: siteAssistantQuery?.data?.data?.all_steps_completed;

	return <Header hideSiteAssistant={isAllStepsCompleted} />;
}

(function () {
	const headerContainer = document.getElementById('evf-react-header-root');

	if (!headerContainer) return;

	const headerRoot = ReactDOM.createRoot(headerContainer);

	if (headerRoot) {
		headerRoot.render(
			<React.StrictMode>
				<QueryClientProvider client={queryClient}>
					<ChakraProvider theme={Theme}>
						<BrowserRouter>
							<HeaderWithQuery />
						</BrowserRouter>
					</ChakraProvider>
				</QueryClientProvider>
			</React.StrictMode>,
		);
	}
})();
