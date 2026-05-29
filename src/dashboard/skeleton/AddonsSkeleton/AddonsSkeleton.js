/**
 *  External Dependencies
 */
import {
	Box,
	HStack,
	SimpleGrid,
	Skeleton,
	SkeletonCircle,
	SkeletonText,
	Stack,
	VStack,
} from '@chakra-ui/react';

const AddonsSkeleton = () => {
	return (
		<>
			{/* Filters Section Skeleton */}
			<Box bg="white" borderRadius="lg" p="5" mb="4" boxShadow="sm">
				<Stack direction="row" justify="space-between" align="center">
					<HStack spacing="3">
						{/* Plan Select Skeleton */}
						<Skeleton height="38px" width="140px" borderRadius="8px" />
						{/* Sort Select Skeleton */}
						<Skeleton height="38px" width="140px" borderRadius="8px" />
						{/* Status Select Skeleton */}
						<Skeleton height="38px" width="140px" borderRadius="8px" />
					</HStack>

					<HStack spacing="3">
						{/* Reset Button Skeleton */}
						<Skeleton height="38px" width="38px" borderRadius="8px" />
						{/* Search Input Skeleton */}
						<Skeleton height="38px" width="320px" borderRadius="8px" />
					</HStack>
				</Stack>
			</Box>

			{/* Categories Section Skeleton */}
			<Box
				bg="white"
				borderRadius="lg"
				p="4"
				mb="4"
				boxShadow="sm"
				position="relative"
				minH="56px"
			>
				<HStack spacing="6" py="1">
					{Array(6)
						.fill(0)
						.map((_, i) => (
							<Skeleton
								key={i}
								height="24px"
								width={`${60 + Math.random() * 40}px`}
								borderRadius="4px"
							/>
						))}
				</HStack>
			</Box>

			{/* Cards Grid Skeleton */}
			<SimpleGrid columns={{ base: 1, md: 2, lg: 3 }} spacing="6">
				{Array(9)
					.fill(0)
					.map((_, i) => (
						<Box
							key={i}
							bg="white"
							borderRadius="lg"
							border="1px solid"
							borderColor="gray.200"
							p="6"
							transition="all 0.2s"
							height="100%"
							display="flex"
							flexDirection="column"
						>
							{/* Main Content Layout */}
							<HStack align="start" spacing="4" flex="1" mb="6">
								{/* Icon Skeleton */}
								<SkeletonCircle size="10" flexShrink={0} />

								{/* Title and Badge Section */}
								<VStack align="start" spacing="3" flex="1" w="full">
									<HStack justify="space-between" w="full" align="start">
										{/* Title Skeleton */}
										<Skeleton height="20px" width="60%" />
										{/* Badge Skeleton */}
										<Skeleton height="20px" width="45px" borderRadius="4px" />
									</HStack>

									{/* Description Skeleton */}
									<SkeletonText noOfLines={2} spacing="2" width="100%" />
								</VStack>
							</HStack>

							{/* Footer Section */}
							<HStack
								justify="space-between"
								align="center"
								mt="auto"
								pt="3"
								borderTop="1px solid"
								borderColor="gray.100"
							>
								<HStack spacing="2">
									{/* Docs Link Skeleton */}
									<Skeleton height="16px" width="40px" />
									<Skeleton height="16px" width="4px" />
									{/* Video Button Skeleton */}
									<Skeleton height="20px" width="20px" borderRadius="4px" />
								</HStack>
								{/* Toggle/Button Skeleton */}
								<Skeleton height="24px" width="44px" borderRadius="full" />
							</HStack>
						</Box>
					))}
			</SimpleGrid>
		</>
	);
};

export default AddonsSkeleton;
