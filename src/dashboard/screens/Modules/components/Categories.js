import { ChevronLeftIcon, ChevronRightIcon } from '@chakra-ui/icons';
import { Box, HStack, IconButton, Text } from '@chakra-ui/react';
import { useEffect, useRef, useState } from 'react';

const Categories = ({
	categories,
	selectedCategory,
	highlightedCategories = [],
	onCategoryChange,
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
			checkArrows();
		}
	};

	const scrollRight = () => {
		const container = scrollContainerRef.current;
		if (container) {
			container.scrollBy({ left: 250, behavior: 'smooth' });
			checkArrows();
		}
	};

	return (
		<Box
			bg="white"
			borderRadius="lg"
			p={{ base: '3', sm: '4' }}
			mb="4"
			position="relative"
			minH={{ base: '52px', sm: '56px' }}
			overflow="hidden"
		>
			{showLeftArrow && (
				<>
					<Box
						position="absolute"
						left="0"
						top="0"
						bottom="0"
						width={{ base: '50px', sm: '60px', md: '70px' }}
						bgGradient="linear(to-r, white 40%, transparent)"
						zIndex="1"
						pointerEvents="none"
						borderRadius="lg 0 0 lg"
					/>
					<IconButton
						icon={<ChevronLeftIcon boxSize={{ base: 5, sm: 6 }} />}
						position="absolute"
						left={{ base: '2', sm: '3' }}
						top="50%"
						transform="translateY(-50%)"
						zIndex="1"
						size="sm"
						variant="solid"
						bg="white"
						border="1px solid"
						borderColor="#E5E7EB"
						color="#6B7280"
						borderRadius="full"
						w={{ base: '32px', sm: '36px' }}
						h={{ base: '32px', sm: '36px' }}
						minW={{ base: '32px', sm: '36px' }}
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
						pointerEvents="auto"
					/>
				</>
			)}

			<Box
				ref={scrollContainerRef}
				overflowX="auto"
				overflowY="hidden"
				onScroll={checkArrows}
				pl={showLeftArrow ? { base: '12', sm: '14', md: '16' } : '2'}
				pr={showRightArrow ? { base: '12', sm: '14', md: '16' } : '2'}
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
					spacing={{ base: '4', sm: '5', md: '6' }}
					align="center"
					position="relative"
					flexShrink={0}
					minW="max-content"
					py="1"
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
								py={{ base: '1.5', sm: '2' }}
								px={{ base: '1.5', sm: '2' }}
								flexShrink={0}
								display="flex"
								alignItems="center"
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
										color: shouldHighlight ? '#7545bb' : '#1a202c',
										transform: 'translateY(-1px)',
									},
								}}
							>
								<Text
									fontSize={{ base: '14px', sm: '15px' }}
									fontWeight={shouldHighlight ? '600' : '500'}
									color={shouldHighlight ? '#7545bb' : '#6B7280'}
									transition="all 0.2s ease"
									whiteSpace="nowrap"
									userSelect="none"
									lineHeight="1.5"
								>
									{category.label}
								</Text>

								{shouldHighlight && (
									<Box
										position="absolute"
										bottom="0"
										left="0"
										right="0"
										height="3px"
										bg="#7545bb"
										borderRadius="3px 3px 0 0"
										transition="all 0.2s ease"
									/>
								)}
							</Box>
						);
					})}
				</HStack>
			</Box>

			{showRightArrow && (
				<>
					<Box
						position="absolute"
						right="0"
						top="0"
						bottom="0"
						width={{ base: '50px', sm: '60px', md: '70px' }}
						bgGradient="linear(to-l, white 40%, transparent)"
						zIndex="1"
						pointerEvents="none"
						borderRadius="0 lg lg 0"
					/>
					<IconButton
						icon={<ChevronRightIcon boxSize={{ base: 5, sm: 6 }} />}
						position="absolute"
						right={{ base: '2', sm: '3' }}
						top="50%"
						transform="translateY(-50%)"
						zIndex="1"
						size="sm"
						variant="solid"
						bg="white"
						border="1px solid"
						borderColor="#E5E7EB"
						color="#6B7280"
						borderRadius="full"
						w={{ base: '32px', sm: '36px' }}
						h={{ base: '32px', sm: '36px' }}
						minW={{ base: '32px', sm: '36px' }}
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
						pointerEvents="auto"
					/>
				</>
			)}
		</Box>
	);
};

export default Categories;
