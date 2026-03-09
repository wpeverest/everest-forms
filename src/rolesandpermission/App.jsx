import React from 'react';
import { ChakraProvider, Stack } from '@chakra-ui/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import Theme from '../dashboard/Theme/Theme';
import UserRoleTable from './components/UserRoleTable';

const queryClient = new QueryClient();

const App = () => {
	return (
		<ChakraProvider theme={Theme}>
			<QueryClientProvider client={queryClient}>
				<Stack
					direction="column"
					gap="16px"
					padding="20px 20px 40px 20px"
					bg="white"
				>
					<UserRoleTable />
				</Stack>
			</QueryClientProvider>
		</ChakraProvider>
	);
};

export default App;
