import React from 'react'
import { HashRouter } from 'react-router-dom'
import { Container, ChakraProvider } from '@chakra-ui/react'
import Theme from './Theme/Theme'
import Router from './Router/Router'
import { Header } from './components'

const App = () => {
	return (
		<HashRouter>
			<ChakraProvider theme={Theme}>
				<Header />
				<Container maxW='container.xl'>
					<Router />
				</Container>
			</ChakraProvider>
		</HashRouter>
	)
}

export default App
