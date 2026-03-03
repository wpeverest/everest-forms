import {
	Box,
	Divider,
	HStack,
	Heading,
	SimpleGrid,
	Text,
} from '@chakra-ui/react';
import AddonCard from './AddonCard';

const CardsGrid = ({ modules, selectedCategory, showToast, onModuleToggle }) => {
	const getModulesByCategory = () => {
		const modulesByCategory = new Map();

		modules.forEach((module) => {
			const category = module.category || 'Others';
			if (!modulesByCategory.has(category)) {
				modulesByCategory.set(category, []);
			}
			modulesByCategory.get(category).push(module);
		});

		const categoryOrder = [
			'Form Elements',
			'Payment Gateways',
			'Email Marketing',
			'CRM Integrations',
			'Marketing & Analytics',
			'Integrations',
			'E-Commerce',
			'Page Builders',
			'Security',
			'Cloud & Storage',
		];

		const sortedCategories = [];

		categoryOrder.forEach((categoryName) => {
			if (modulesByCategory.has(categoryName)) {
				sortedCategories.push([
					categoryName,
					modulesByCategory.get(categoryName),
				]);
			}
		});

		modulesByCategory.forEach((categoryModules, category) => {
			if (!categoryOrder.includes(category)) {
				sortedCategories.push([category, categoryModules]);
			}
		});

		return sortedCategories.map(([category, categoryModules]) => ({
			category,
			displayName: category,
			modules: categoryModules,
		}));
	};

	if (selectedCategory === 'All') {
		const categoriesData = getModulesByCategory();

		return (
			<Box>
				{categoriesData.map(
					({ category, displayName, modules: categoryModules }) => (
						<Box
							key={category}
							mb={{ base: '4', sm: '5', md: '6' }}
							bg="white"
							p={{ base: '4', sm: '5', md: '6', lg: '8' }}
							borderRadius="lg"
						>
							<HStack
								justify="space-between"
								mb={{ base: '3', sm: '4' }}
								flexWrap="wrap"
								gap="2"
							>
								<Heading
									size={{ base: 'sm', sm: 'md' }}
									color="gray.800"
									fontWeight="600"
									fontSize={{ base: '16px', sm: '18px', md: '20px' }}
								>
									{displayName}
								</Heading>
								<Text
									fontSize={{ base: '12px', sm: '13px', md: '14px' }}
									color="gray.500"
									fontWeight="500"
									flexShrink={0}
								>
									{categoryModules.length}{' '}
									{categoryModules.length === 1 ? 'Item' : 'Items'}
								</Text>
							</HStack>
							<Divider
								mb={{ base: '4', sm: '5', md: '6' }}
								borderColor="gray.200"
							/>

							<SimpleGrid
								columns={{ base: 1, md: 2, lg: 3 }}
								spacing={{ base: '4', sm: '5', md: '6' }}
							>
								{categoryModules.map((addon) => (
									<AddonCard
										key={addon.slug}
										addon={addon}
										showToast={showToast}
										onModuleToggle={onModuleToggle}
									/>
								))}
							</SimpleGrid>
						</Box>
					),
				)}
			</Box>
		);
	}

	const categoryName =
		selectedCategory !== 'All'
			? selectedCategory
			: modules.length > 0
				? modules[0].category || 'Others'
				: 'Others';

	return (
		<Box>
			<Box
				mb={{ base: '4', sm: '5', md: '6' }}
				bg="white"
				p={{ base: '4', sm: '5', md: '6', lg: '8' }}
				borderRadius="lg"
			>
				<HStack
					justify="space-between"
					mb={{ base: '3', sm: '4' }}
					flexWrap="wrap"
					gap="2"
				>
					<Heading
						size={{ base: 'sm', sm: 'md' }}
						color="gray.800"
						fontWeight="600"
						fontSize={{ base: '16px', sm: '18px', md: '20px' }}
					>
						{categoryName}
					</Heading>
					<Text
						fontSize={{ base: '12px', sm: '13px', md: '14px' }}
						color="gray.500"
						fontWeight="500"
						flexShrink={0}
					>
						{modules.length} {modules.length === 1 ? 'Item' : 'Items'}
					</Text>
				</HStack>
				<Divider mb={{ base: '4', sm: '5', md: '6' }} borderColor="gray.200" />

				{modules.length > 0 ? (
					<SimpleGrid
						columns={{ base: 1, md: 2, lg: 3 }}
						spacing={{ base: '4', sm: '5', md: '6' }}
					>
						{modules.map((addon) => (
							<AddonCard
								key={addon.slug}
								addon={addon}
								showToast={showToast}
								onModuleToggle={onModuleToggle}
							/>
						))}
					</SimpleGrid>
				) : (
					<Box
						display="flex"
						justifyContent="center"
						flexDirection="column"
						padding={{ base: '30px 16px', sm: '40px 20px', md: '60px' }}
						gap={{ base: '2', sm: '3' }}
						alignItems="center"
						textAlign="center"
					>
						<Text
							fontSize={{ base: '14px', sm: '15px', md: '16px' }}
							fontWeight="500"
							color="gray.600"
						>
							No modules found
						</Text>
						<Text
							fontSize={{ base: '13px', sm: '14px' }}
							color="gray.500"
							px={{ base: '2', sm: '4' }}
						>
							No addons are available in the {categoryName} category.
						</Text>
					</Box>
				)}
			</Box>
		</Box>
	);
};

export default CardsGrid;
