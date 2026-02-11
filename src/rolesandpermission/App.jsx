import { ChakraProvider } from '@chakra-ui/react';
import UserRoleTable from './components/UserRoleTable';

const App = () => {
	return (
		<ChakraProvider>
			<UserRoleTable />
		</ChakraProvider>
	);
};

export default App;
