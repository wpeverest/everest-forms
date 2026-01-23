import { ChakraProvider } from '@chakra-ui/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { HashRouter } from 'react-router-dom';
import Router from './Router/Router';
import Theme from './Theme/Theme';
import { DashboardProvider } from './context/DashboardContext';
import dashboardReducer, { initialState } from './reducers/DashboardReducer';

const App = () => {
	const queryClient = new QueryClient({
		defaultOptions: {
			queries: {
				refetchOnWindowFocus: false,
				refetchOnReconnect: false,
			},
		},
	});

	return (
		<DashboardProvider
			initialState={initialState}
			dashboardReducer={dashboardReducer}
		>
			<HashRouter>
				<ChakraProvider theme={Theme}>
					<QueryClientProvider client={queryClient}>
						<Router />
					</QueryClientProvider>
				</ChakraProvider>
			</HashRouter>
		</DashboardProvider>
	);
};

export default App;
