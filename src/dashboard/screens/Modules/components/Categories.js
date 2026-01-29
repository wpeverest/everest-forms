/**
 * External Dependencies
 */
import { ChevronLeftIcon, ChevronRightIcon } from '@chakra-ui/icons';
import { Box, HStack, IconButton, Text } from '@chakra-ui/react';
import { useEffect, useRef, useState } from 'react';

const Categories = ({
	categories,
	selectedCategory,
	highlightedCategories = [],
	onCategoryChange
}) => {
	const scrollContainerRef = useRef(null);
	const [showLeftArrow, setShowLeftArrow] = useState(false);
	const [showRightArrow, setShowRightArrow] = useState(false);


	const checkArrows = () => {
		const container = scrollContainerRef.current;
		if (!container) return;

		const hasOverflow = container.scrollWidth > container.clientWidth;
		const isAtStart = container.scrollLeft <= 1;
		const isAtEnd =
			container.scrollLeft + container.clientWidth >= container.scrollWidth - 1;

		setShowLeftArrow(hasOverflow && !isAtStart);
		setShowRightArrow(hasOverflow && !isAtEnd);
	};

	useEffect(() => {

		const timer = setTimeout(() => {
			const container = scrollContainerRef.current;
			if (container) {

				container.scrollLeft = 0;
				checkArrows();
			}
		}, 100);

		const handleResize = () => {
			requestAnimationFrame(checkArrows);
		};

		window.addEventListener('resize', handleResize);

		return () => {
			clearTimeout(timer);
			window.removeEventListener('resize', handleResize);
		};
	}, [categories]);

	
	const scrollLeft = () => {
		const container = scrollContainerRef.current;
		if (container) {
			container.scrollBy({ left: -250, behavior: 'smooth' });
			setTimeout(checkArrows, 350);
		}
	};

	const scrollRight = () => {
		const container = scrollContainerRef.current;
		if (container) {
			container.scrollBy({ left: 250, behavior: 'smooth' });
			setTimeout(checkArrows, 350);
		}
	};

	return (
		<Box
			bg="white"
			borderRadius="lg"
			p="6"
			mb="4"
			boxShadow="sm"
			position="relative"
			minH="80px"
		>
			{/* Left Arrow with gradient fade effect */}
			{showLeftArrow && (
				<>
					{/* Gradient overlay */}
					<Box
						position="absolute"
						left="0"
						top="0"
						bottom="0"
						width="70px"
						bgGradient="linear(to-r, white 40%, transparent)"
						zIndex="1"
						pointerEvents="none"
						borderRadius="lg 0 0 lg"
					/>
					<IconButton
						icon={<ChevronLeftIcon boxSize={6} />}
						position="absolute"
						left="3"
						top="50%"
						transform="translateY(-50%)"
						zIndex="3"
						size="sm"
						variant="solid"
						bg="white"
						border="1px solid"
						borderColor="#E5E7EB"
						color="#6B7280"
						boxShadow="0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)"
						borderRadius="full"
						w="36px"
						h="36px"
						minW="36px"
						_hover={{
							bg: '#F9FAFB',
							borderColor: '#D1D5DB',
							color: '#374151',
							transform: 'translateY(-50%) scale(1.05)',
						}}
						_active={{
							bg: '#F3F4F6',
							transform: 'translateY(-50%) scale(0.95)',
						}}
						transition="all 0.2s ease"
						onClick={scrollLeft}
						aria-label="Scroll left"
					/>
				</>
			)}

			{/* Scrollable Container */}
			<Box
				ref={scrollContainerRef}
				overflowX="auto"
				overflowY="hidden"
				onScroll={checkArrows}
				pl={showLeftArrow ? '16' : '2'}
				pr={showRightArrow ? '16' : '2'}
				css={{
					'&::-webkit-scrollbar': {
						display: 'none',
					},
					scrollbarWidth: 'none',
					msOverflowStyle: 'none',
					scrollBehavior: 'smooth',
				}}
			>
				<HStack
					spacing="6"
					align="center"
					position="relative"
					flexShrink={0}
					minW="max-content"
					py="2"
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
								pb="3"
								px="2"
								flexShrink={0}
								onClick={() =>
									onCategoryChange(category.value, category.internalValue)
								}
								transition="all 0.2s ease"
								role="button"
								tabIndex={0}
								onKeyDown={(e) => {
									if (e.key === 'Enter' || e.key === ' ') {
										e.preventDefault();
										onCategoryChange(category.value, category.internalValue);
									}
								}}
								_hover={{
									'& > div:first-of-type': {
										color: shouldHighlight ? '#4263EB' : '#1a202c',
										transform: 'translateY(-1px)',
									},
								}}
							>
								<Text
									fontSize="15px"
									fontWeight={shouldHighlight ? '600' : '500'}
									color={shouldHighlight ? '#4263EB' : '#6B7280'}
									transition="all 0.2s ease"
									whiteSpace="nowrap"
									userSelect="none"
									lineHeight="1.5"
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
										borderRadius="3px 3px 0 0"
										transition="all 0.2s ease"
										boxShadow="0 -1px 0 0 #4263EB"
									/>
								)}
							</Box>
						);
					})}
				</HStack>
			</Box>

			{/* Right Arrow with gradient fade effect */}
			{showRightArrow && (
				<>
					{/* Gradient overlay */}
					<Box
						position="absolute"
						right="0"
						top="0"
						bottom="0"
						width="70px"
						bgGradient="linear(to-l, white 40%, transparent)"
						zIndex="1"
						pointerEvents="none"
						borderRadius="0 lg lg 0"
					/>
					<IconButton
						icon={<ChevronRightIcon boxSize={6} />}
						position="absolute"
						right="3"
						top="50%"
						transform="translateY(-50%)"
						zIndex="3"
						size="sm"
						variant="solid"
						bg="white"
						border="1px solid"
						borderColor="#E5E7EB"
						color="#6B7280"
						boxShadow="0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)"
						borderRadius="full"
						w="36px"
						h="36px"
						minW="36px"
						_hover={{
							bg: '#F9FAFB',
							borderColor: '#D1D5DB',
							color: '#374151',
							transform: 'translateY(-50%) scale(1.05)',
						}}
						_active={{
							bg: '#F3F4F6',
							transform: 'translateY(-50%) scale(0.95)',
						}}
						transition="all 0.2s ease"
						onClick={scrollRight}
						aria-label="Scroll right"
					/>
				</>
			)}

			{/* Bottom border line */}
			<Box
				position="absolute"
				bottom="0"
				left="6"
				right="6"
				height="1px"
				bg="gray.200"
			/>
		</Box>
	);
};

export default Categories;
