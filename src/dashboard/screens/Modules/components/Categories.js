/**
 * External Dependencies
 */
import { Box, HStack, Text } from "@chakra-ui/react";

const Categories = ({
	categories,
	selectedCategory,
	highlightedCategories = [],
	onCategoryChange
}) => {
	return (
		<Box bg="white" borderRadius="lg" p="6" mb="4" boxShadow="sm">
			<Box display="flex" justifyContent="center" alignItems="center" position="relative">
				<HStack
					spacing="8"
					align="center"
					position="relative"
					_after={{
						content: '""',
						position: "absolute",
						bottom: "-24px",
						left: 0,
						right: 0,
						height: "1px",
						bg: "gray.200"
					}}
				>
					{categories.map((category, index) => {
						const isSelected = selectedCategory === category.value;
						const isHighlighted =
							highlightedCategories.length > 0 &&
							highlightedCategories.includes(category.internalValue);

						const shouldHighlight =
							highlightedCategories.length > 0 ? isHighlighted : isSelected;

						return (
							<Box
								key={`${category.value}-${category.internalValue}-${index}`}
								position="relative"
								cursor="pointer"
								pb="6"
								onClick={() =>
									onCategoryChange(category.value, category.internalValue)
								}
								transition="all 0.2s ease"
								_hover={{
									"& > div": {
										color: shouldHighlight ? "#4263EB" : "#1a202c"
									}
								}}
							>
								<Text
									fontSize="14px"
									fontWeight={shouldHighlight ? "600" : "500"}
									color={shouldHighlight ? "#4263EB" : "#6B7280"}
									transition="all 0.2s ease"
									whiteSpace="nowrap"
									userSelect="none"
								>
									{category.label}
								</Text>

								{/* Active/Highlighted indicator underline */}
								{shouldHighlight && (
									<Box
										position="absolute"
										bottom="0"
										left="0"
										right="0"
										height="3px"
										bg="#4263EB"
										borderRadius="2px 2px 0 0"
										transition="all 0.2s ease"
									/>
								)}
							</Box>
						);
					})}
				</HStack>
			</Box>
		</Box>
	);
};

export default Categories;
