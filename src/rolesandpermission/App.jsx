import React from "react";
import { ChakraProvider, Stack } from "@chakra-ui/react";
import UserRoleTable from "./components/UserRoleTable";

const App = () => {
	return (
		<ChakraProvider>
			<Stack padding={"28px 32px"} backgroundColor={"#FFFFFF"} borderRadius={"7px"} direction={"column"}>
				<UserRoleTable/>
			</Stack>
		</ChakraProvider>
	);
};

export default App;
