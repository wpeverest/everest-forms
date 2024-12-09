import React from "react";
import { ChakraProvider, Stack } from "@chakra-ui/react";
import RoleBased from "./components/RoleBased";

const App = () => {
	return (
		<ChakraProvider>
			<Stack padding={"28px 32px"} backgroundColor={"#FFFFFF"} borderRadius={"7px"} gap={"24px"}>
				<RoleBased/>
			</Stack>
		</ChakraProvider>
	);
};

export default App;
