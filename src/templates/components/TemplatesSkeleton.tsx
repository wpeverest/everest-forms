import {
	Box,
	SimpleGrid,
	Skeleton,
	SkeletonText,
	VStack,
} from '@chakra-ui/react';

const TemplatesSkeleton = () => {
	return (
		<Box>
			<Skeleton height="40px" width="200px" mb="32px" />

			<SimpleGrid columns={{ base: 1, md: 2, lg: 3, xl: 4 }} spacing={6}>
				{Array(8)
					.fill(1)
					.map((_, i) => (
						<Box
							key={i}
							borderWidth="2px"
							borderRadius="8px"
							borderColor="#F6F4FA"
							overflow="hidden"
							bg="white"
							p={0}
						>
							<Skeleton height="250px" width="100%" />

							<VStack padding="16px" align="start" spacing={2}>
								<Skeleton height="20px" width="80%" />
								<SkeletonText noOfLines={2} spacing={2} width="100%" />
							</VStack>
						</Box>
					))}
			</SimpleGrid>
		</Box>
	);
};

export default TemplatesSkeleton;
