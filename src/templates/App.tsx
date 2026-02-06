import { RepeatIcon } from '@chakra-ui/icons';
import {
	Box,
	Button,
	ChakraProvider,
	Heading,
	HStack,
	Icon,
	Link,
	Text,
	VStack,
} from '@chakra-ui/react';
import { useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { useState } from 'react';
import Theme from '../dashboard/Theme/Theme';
import Main from './components/Main';

const EVFIcon = (props) => (
	<Icon viewBox="0 0 24 24" {...props}>
		<path
			fill="currentColor"
			d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"
		/>
	</Icon>
);

const App = () => {
	const queryClient = useQueryClient();
	const [selectedTab, setSelectedTab] = useState<string>(
		__('All', 'everest-forms'),
	);

	const handleFilterChange = (filter: string) => {
		setSelectedTab(filter);
	};

	const handleRefreshTemplates = () => {
		queryClient.invalidateQueries(['templates']);
	};

	return (
		<ChakraProvider theme={Theme}>
			<Box
				bg="white"
				margin="20px"
				padding="0px 24px 50px"
				boxShadow="0px 4px 50px rgba(0, 0, 0, 0.06)"
			>
				<Box bg="white">
					<HStack
						justify="space-between"
						align="flex-start"
						padding="20px 0px 32px"
						gap="20px"
						flexWrap="wrap"
					>
						<VStack align="start" gap="8px" flex="1" minW="300px">
							<Heading
								as="h1"
								fontSize="32px"
								lineHeight="1.2"
								fontWeight="600"
								color="grey.400"
							>
								{__('Select a Template', 'everest-forms')}
							</Heading>
							<Text fontSize="14px" lineHeight="1.5" color="grey.300">
								{__(
									'Choose from our ready-made templates or start with a blank form',
									'everest-forms',
								)}
							</Text>
						</VStack>

						<HStack gap="12px" flexWrap="wrap">
							<Button
								size="md"
								variant={
									selectedTab === __('All', 'everest-forms')
										? 'solid'
										: 'outline'
								}
								colorScheme="primary"
								onClick={() => handleFilterChange(__('All', 'everest-forms'))}
								px="24px"
								py="10px"
								height="auto"
								fontSize="15px"
								fontWeight="500"
							>
								{__('All', 'everest-forms')}
							</Button>
							<Button
								size="md"
								variant={
									selectedTab === __('Free', 'everest-forms')
										? 'solid'
										: 'outline'
								}
								colorScheme="primary"
								onClick={() => handleFilterChange(__('Free', 'everest-forms'))}
								px="24px"
								py="10px"
								height="auto"
								fontSize="15px"
								fontWeight="500"
							>
								{__('Free', 'everest-forms')}
							</Button>
							<Button
								size="md"
								variant={
									selectedTab === __('Premium', 'everest-forms')
										? 'solid'
										: 'outline'
								}
								colorScheme="primary"
								onClick={() =>
									handleFilterChange(__('Premium', 'everest-forms'))
								}
								px="24px"
								py="10px"
								height="auto"
								fontSize="15px"
								fontWeight="500"
							>
								{__('Premium', 'everest-forms')}
							</Button>
							<Link
								color="primary.400"
								fontSize="15px"
								fontWeight="500"
								display="flex"
								alignItems="center"
								gap="6px"
								onClick={handleRefreshTemplates}
								cursor="pointer"
								_hover={{ color: 'primary.500' }}
							>
								<RepeatIcon />
								{__('Refresh Templates', 'everest-forms')}
							</Link>
						</HStack>
					</HStack>
					<Main filter={selectedTab} />
				</Box>
			</Box>
		</ChakraProvider>
	);
};

export default App;
