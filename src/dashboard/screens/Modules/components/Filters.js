/**
 * External Dependencies
 */
import { CloseIcon } from "@chakra-ui/icons";
import {
	Box,
	HStack,
	IconButton,
	Input,
	InputGroup,
	InputLeftElement,
	InputRightElement,
	Stack,
	Tooltip
} from "@chakra-ui/react";
import { Select } from "chakra-react-select";
import { FaUndo } from "react-icons/fa";
import { Search } from "../../../components/Icon/Icon";

const Filters = ({
	sortOptions,
	statusOptions,
	planOptions,
	selectedSortValue,
	selectedStatusValue,
	selectedPlanValue,
	onSortChange,
	onStatusChange,
	onPlanChange,
	searchValue,
	onSearchChange,
	onReset
}) => {

	const selectStyles = {
		control: (base) => ({
			...base,
			backgroundColor: 'white',
			borderColor: '#E5E7EB',
			width: '140px',
			minHeight: '38px',
			cursor: 'pointer',
			borderRadius: '8px',
			fontSize: '14px',
			pointerEvents: 'auto',
			boxShadow: 'none',
			'&:hover': {
				borderColor: '#D1D5DB',
			},
		}),
		valueContainer: (base) => ({
			...base,
			cursor: 'pointer',
			pointerEvents: 'auto',
			padding: '2px 12px',
		}),
		input: (base) => ({
			...base,
			cursor: 'pointer',
			pointerEvents: 'auto',
		}),
		placeholder: (base) => ({
			...base,
			color: '#9CA3AF',
			fontSize: '14px',
		}),
		singleValue: (base) => ({
			...base,
			color: '#374151',
			fontSize: '14px',
			fontWeight: '500',
		}),
		indicatorSeparator: () => ({
			display: 'none',
		}),
		dropdownIndicator: (base) => ({
			...base,
			color: '#6B7280',
			padding: '8px',
			cursor: 'pointer',
			'&:hover': {
				color: '#374151',
			},
		}),
		menu: (base) => ({
			...base,
			borderRadius: '8px',
			boxShadow: 'none',
			border: '1px solid #E5E7EB',
			overflow: 'hidden',
		}),
		menuList: (base) => ({
			...base,
			padding: '4px',
		}),
		option: (base, state) => ({
			...base,
			fontSize: '14px',
			cursor: 'pointer',
			backgroundColor: state.isSelected
				? '#EEF2FF'
				: state.isFocused
					? '#F9FAFB'
					: 'white',
			color: state.isSelected ? '#7545bb' : '#374151',
			fontWeight: state.isSelected ? '600' : '400',
			borderRadius: '4px',
			margin: '2px 0',
			'&:active': {
				backgroundColor: '#EEF2FF',
			},
		}),
	};

	return (
		<Box bg="white" borderRadius="lg" p={{ base: '4', md: '5' }} mb="4">
			<Stack
				direction={{ base: 'column', lg: 'row' }}
				justify={{ base: 'flex-start', lg: 'space-between' }}
				align={{ base: 'stretch', lg: 'center' }}
				spacing={{ base: '3', md: '4' }}
			>
				<HStack
					spacing={{ base: '2', sm: '3' }}
					flexWrap={{ base: 'wrap', sm: 'nowrap' }}
					w={{ base: 'full', lg: 'auto' }}
				>
					<Box w={{ base: 'calc(50% - 4px)', sm: '140px' }}>
						<Select
							instanceId="plan-select"
							options={planOptions}
							value={selectedPlanValue}
							placeholder="All Plans"
							isSearchable={false}
							isClearable={false}
							onChange={onPlanChange}
							menuPortalTarget={document.body}
							menuPosition="fixed"
							menuShouldBlockScroll={false}
							chakraStyles={selectStyles}
						/>
					</Box>
					<Box w={{ base: 'calc(50% - 4px)', sm: '140px' }}>
						<Select
							instanceId="sort-select"
							options={sortOptions}
							value={selectedSortValue}
							placeholder="All"
							isSearchable={false}
							isClearable={false}
							onChange={onSortChange}
							menuPortalTarget={document.body}
							menuPosition="fixed"
							menuShouldBlockScroll={false}
							chakraStyles={selectStyles}
						/>
					</Box>
					<Box w={{ base: 'calc(50% - 4px)', sm: '140px' }}>
						<Select
							instanceId="status-select"
							options={statusOptions}
							value={selectedStatusValue}
							placeholder="All Status"
							isSearchable={false}
							isClearable={false}
							onChange={onStatusChange}
							menuPortalTarget={document.body}
							menuPosition="fixed"
							menuShouldBlockScroll={false}
							chakraStyles={selectStyles}
						/>
					</Box>
				</HStack>

				<HStack spacing="3" w={{ base: 'full', lg: 'auto' }}>
					<Tooltip
						label="Reset all filters"
						placement="top"
						hasArrow
						bg="gray.700"
						color="white"
						fontSize="xs"
						borderRadius="md"
						px="3"
						py="2"
					>
						<IconButton
							aria-label="Reset filters"
							icon={<FaUndo />}
							size="sm"
							variant="outline"
							bg="white"
							borderColor="#E5E7EB"
							color="gray.600"
							borderRadius="8px"
							height="38px"
							width="38px"
							minW="38px"
							flexShrink={0}
							_hover={{
								bg: 'gray.50',
								borderColor: '#D1D5DB',
								color: 'gray.700',
							}}
							_focus={{
								borderColor: '#7545bb',
							}}
							onClick={onReset}
						/>
					</Tooltip>

					<InputGroup
						maxW={{ base: 'full', lg: '320px' }}
						w={{ base: 'full', lg: '320px' }}
						flex={{ base: '1', lg: '0 0 auto' }}
					>
						<InputLeftElement pointerEvents="none" h="38px">
							<Search h="4" w="4" color="gray.400" />
						</InputLeftElement>
						<Input
							key="search-input"
							placeholder="Search modules..."
							value={searchValue}
							onChange={onSearchChange}
							bg="white"
							borderColor="#E5E7EB"
							borderRadius="8px"
							height="38px"
							fontSize="14px"
							_hover={{
								borderColor: '#D1D5DB',
							}}
							_focus={{
								borderColor: '#7545bb',
								outline: 'none',
							}}
							_placeholder={{
								color: '#9CA3AF',
								fontSize: '14px',
							}}
						/>
						{searchValue && (
							<InputRightElement h="38px">
								<IconButton
									aria-label="Clear search"
									icon={<CloseIcon />}
									size="xs"
									variant="ghost"
									color="gray.400"
									_hover={{
										color: 'gray.600',
										bg: 'transparent',
									}}
									onClick={() => onSearchChange({ target: { value: '' } })}
								/>
							</InputRightElement>
						)}
					</InputGroup>
				</HStack>
			</Stack>
		</Box>
	);
};

export default Filters;
