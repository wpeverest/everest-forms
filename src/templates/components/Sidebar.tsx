import { CloseIcon } from '@chakra-ui/icons';
import {
	Badge,
	Box,
	Button,
	Card,
	CardFooter,
	CardHeader,
	Heading,
	HStack,
	IconButton,
	Input,
	InputGroup,
	InputLeftElement,
	InputRightElement,
	Skeleton,
	Spacer,
	Text,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import debounce from 'lodash.debounce';
import React, { useCallback, useState } from 'react';
import { IoSearchOutline } from 'react-icons/io5';
interface SidebarProps {
	categories: { name: string; count: number }[];
	selectedCategory: string;
	onCategorySelect: (category: string) => void;
	onSearchChange: (searchTerm: string) => void;
	isLoading?: boolean;
}

const Sidebar: React.FC<SidebarProps> = React.memo(
	({
		categories,
		selectedCategory,
		onCategorySelect,
		onSearchChange,
		isLoading = false,
	}) => {
		const [searchTerm, setSearchTerm] = useState<string>('');

		const debouncedSearchChange = useCallback(
			debounce((value: string) => {
				onSearchChange(value);
			}, 300),
			[onSearchChange],
		);

		const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
			const value = e.target.value;
			setSearchTerm(value);
			debouncedSearchChange(value);
		};

		const handleClearSearch = () => {
			setSearchTerm('');
			onSearchChange('');
		};

		const favorites = categories.find((cat) => cat.name === 'Favorites');

		const orderedCategories =
			favorites && favorites.count > 0
				? [favorites, ...categories.filter((cat) => cat.name !== 'Favorites')]
				: categories;

		return (
			<Box>
				<InputGroup mb="26px">
					<InputLeftElement
						pointerEvents="none"
						padding="0px 0px 0px 8px"
						borderRadius="8px"
						borderColor="#B0B0B0"
					>
						<IoSearchOutline
							style={{ width: '18px', height: '18px' }}
							color="#737373"
						/>
					</InputLeftElement>
					<Input
						placeholder={__('Search Templates', 'everest-forms')}
						value={searchTerm}
						onChange={handleSearchChange}
						isDisabled={isLoading}
						_focus={{
							borderColor: '#7545BB',
							outline: 'none',
							boxShadow: 'none',
						}}
					/>
					{searchTerm && !isLoading && (
						<InputRightElement>
							<IconButton
								aria-label="Clear search"
								icon={<CloseIcon />}
								size="xs"
								variant="ghost"
								onClick={handleClearSearch}
								_hover={{ bg: 'gray.100' }}
							/>
						</InputRightElement>
					)}
				</InputGroup>

				<VStack align="stretch" gap="2px">
					{isLoading
						? Array(12)
								.fill(1)
								.map((_, i) => (
									<HStack key={i} p="12px" borderRadius="md">
										<Skeleton height="20px" width="60%" />
										<Spacer />
										<Skeleton height="32px" width="32px" borderRadius="8px" />
									</HStack>
								))
						: orderedCategories.map((category) => (
								<HStack
									key={category.name}
									p="12px"
									_hover={{
										bg: '#F7F4FB',
										'& > .badge': {
											bg:
												selectedCategory === category.name
													? '#FFFFFF'
													: '#FFFFFF',
										},
									}}
									borderRadius="md"
									cursor="pointer"
									bg={
										selectedCategory === category.name
											? '#F7F4FB'
											: 'transparent'
									}
									onClick={() => onCategorySelect(category.name)}
								>
									<Text
										color={selectedCategory === category.name ? '#7545BB' : ''}
										fontWeight="semibold"
										margin="0px"
									>
										{category.name}
									</Text>
									<Spacer />
									<Badge
										className="badge"
										display="flex"
										alignItems="center"
										justifyContent="center"
										width="32px"
										height="32px"
										padding="0px"
										borderRadius="8px"
										color={selectedCategory === category.name ? '#7545BB' : ''}
										bg={
											selectedCategory === category.name ? 'white' : '#F2F2F2'
										}
									>
										{category.count}
									</Badge>
								</HStack>
							))}
					<Card
						align="center"
						bg="linear-gradient(90.62deg, rgba(76, 21, 155, 0.7) 0.2%, rgba(76, 21, 155, 0.7) 0.21%, rgba(140, 100, 198, 0.7) 99.25%)"
						padding="40px 24px"
						marginTop="26px"
					>
						<CardHeader padding="0px">
							<Heading
								fontSize="18px"
								color="white"
								lineHeight="28px"
								padding="0px"
								margin="0px 0px 20px"
								textAlign="center"
							>
								{__("Can't Find The Form Template You Need?", 'everest-forms')}
							</Heading>
						</CardHeader>
						<CardFooter padding="0" width="100%">
							<a
								href="https://everestforms.net/request-template"
								target="_blank"
								rel="noopener noreferrer"
								style={{ width: 'inherit' }}
							>
								<Button
									backgroundColor="#FFFFFF"
									color="inherit"
									padding="12px 10px"
									borderRadius="4px"
									width="full"
									_hover={{
										color: 'white',
										bg: 'primary.600',
									}}
								>
									{__('Request Template', 'everest-forms')}
								</Button>
							</a>
						</CardFooter>
					</Card>
				</VStack>
			</Box>
		);
	},
);

export default Sidebar;
