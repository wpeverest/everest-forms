import React from "react";
import { HashRouter } from "react-router-dom";
import { Container, ChakraProvider } from "@chakra-ui/react";
import Theme from "./Theme/Theme";
import Router from "./Router/Router";
import { Header } from "./components";
import dashboardReducer, { initialState } from "./reducers/DashboardReducer";
import { DashboardProvider } from "./context/DashboardContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

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
		<DashboardProvider initialState={initialState} dashboardReducer={dashboardReducer}>
			<HashRouter>
				<ChakraProvider theme={Theme}>
				<QueryClientProvider client={queryClient}>

					<Header />
						<Router />
						</QueryClientProvider>
 				</ChakraProvider>
			</HashRouter>
		</DashboardProvider>
	);
};

export default App;
