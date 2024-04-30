import React from "react";
import { HashRouter } from "react-router-dom";
import { Container, ChakraProvider } from "@chakra-ui/react";
import Theme from "./Theme/Theme";
import Router from "./Router/Router";
import { Header } from "./components";
import dashboardReducer, { initialState } from "./reducers/DashboardReducer";

import { DashboardProvider } from "./context/DashboardContext";

const App = () => {
	return (
		<DashboardProvider initialState={initialState} dashboardReducer={dashboardReducer}>
			<HashRouter>
				<ChakraProvider theme={Theme}>
					<Header />
					<Container maxW="container.xl">
						<Router />
					</Container>
				</ChakraProvider>
			</HashRouter>
		</DashboardProvider>
	);
};

export default App;
