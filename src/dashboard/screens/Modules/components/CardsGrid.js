/**
 * External Dependencies
 */
import {
	Box,
	Divider,
	HStack,
	Heading,
	SimpleGrid,
	Text,
} from '@chakra-ui/react';
import AddonCard from './AddonCard';

const CardsGrid = ({ modules, selectedCategory, showToast }) => {
	const getModulesByCategory = () => {
		const modulesByCategory = new Map();

		// Group modules by category
		modules.forEach((module) => {
			const category = module.category || 'Others';
			if (!modulesByCategory.has(category)) {
				modulesByCategory.set(category, []);
			}
			modulesByCategory.get(category).push(module);
		});

		// Define category order for Everest Forms
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

		// Sort categories based on defined order
		const sortedCategories = [];

		// First, add categories in the defined order
		categoryOrder.forEach((categoryName) => {
			if (modulesByCategory.has(categoryName)) {
				sortedCategories.push([
					categoryName,
					modulesByCategory.get(categoryName),
				]);
			}
		});

		// Then add any remaining categories that weren't in the order
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
							mb="6"
							bg="white"
							p={{ base: '6', md: '8' }}
							borderRadius="lg"
							boxShadow="sm"
						>
							<HStack justify="space-between" mb="4">
								<Heading size="md" color="gray.800" fontWeight="600">
									{displayName}
								</Heading>
								<Text fontSize="sm" color="gray.500" fontWeight="500">
									{categoryModules.length}{' '}
									{categoryModules.length === 1 ? 'Item' : 'Items'}
								</Text>
							</HStack>
							<Divider mb="6" borderColor="gray.200" />

							<SimpleGrid columns={{ base: 1, md: 2, lg: 3 }} spacing="6">
								{categoryModules.map((addon) => (
									<AddonCard
										key={addon.slug}
										addon={addon}
										showToast={showToast}
									/>
								))}
							</SimpleGrid>
						</Box>
					),
				)}
			</Box>
		);
	}

	// Single category view
	const categoryName =
		selectedCategory !== 'All'
			? selectedCategory
			: modules.length > 0
				? modules[0].category || 'Others'
				: 'Others';

	return (
		<Box>
			<Box
				mb="6"
				bg="white"
				p={{ base: '6', md: '8' }}
				borderRadius="lg"
				boxShadow="sm"
			>
				<HStack justify="space-between" mb="4">
					<Heading size="md" color="gray.800" fontWeight="600">
						{categoryName}
					</Heading>
					<Text fontSize="sm" color="gray.500" fontWeight="500">
						{modules.length} {modules.length === 1 ? 'Item' : 'Items'}
					</Text>
				</HStack>
				<Divider mb="6" borderColor="gray.200" />

				{modules.length > 0 ? (
					<SimpleGrid columns={{ base: 1, md: 2, lg: 3 }} spacing="6">
						{modules.map((addon) => (
							<AddonCard key={addon.slug} addon={addon} showToast={showToast} />
						))}
					</SimpleGrid>
				) : (
					<Box
						display="flex"
						justifyContent="center"
						flexDirection="column"
						padding={{ base: '40px', md: '60px' }}
						gap="3"
						alignItems="center"
						textAlign="center"
					>
						<Text fontSize="16px" fontWeight="500" color="gray.600">
							No modules found
						</Text>
						<Text fontSize="14px" color="gray.500">
							No addons are available in the {categoryName} category.
						</Text>
					</Box>
				)}
			</Box>
		</Box>
	);
};

export default CardsGrid;
