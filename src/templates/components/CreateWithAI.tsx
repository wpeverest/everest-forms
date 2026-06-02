import {
	Box,
	Button,
	Flex,
	HStack,
	Heading,
	Icon,
	SimpleGrid,
	Text,
	Textarea,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { BsCalculator, BsGraduationCap, BsStars } from 'react-icons/bs';
import {
	FiArrowLeft,
	FiArrowUp,
	FiCalendar,
	FiCreditCard,
	FiFileText,
	FiHeart,
	FiLayout,
	FiList,
	FiMessageSquare,
	FiMic,
	FiPaperclip,
} from 'react-icons/fi';

interface CreateWithAIProps {
	onBack: () => void;
}

const TYPE_CHIPS = [
	{ label: 'Survey', icon: FiFileText },
	{ label: 'Payment', icon: FiCreditCard },
	{ label: 'Calculator', icon: BsCalculator },
	{ label: 'Conversational', icon: FiMessageSquare },
	{ label: 'Quiz', icon: FiList },
	{ label: 'Application', icon: FiLayout },
];

const INSPIRATION_CARDS = [
	{
		icon: FiHeart,
		title: __('Customer feedback survey', 'everest-forms'),
		description: __('Rating scale, NPS, and open feedback for product teams.', 'everest-forms'),
		prompt: 'A customer feedback survey with rating scale, NPS score, and open feedback fields for product teams.',
	},
	{
		icon: FiCalendar,
		title: __('Appointment booking', 'everest-forms'),
		description: __('Date, time, service type and contact details.', 'everest-forms'),
		prompt: 'An appointment booking form with date picker, time slots, service type selection and contact details.',
	},
	{
		icon: BsGraduationCap,
		title: __('Course registration', 'everest-forms'),
		description: __('Personal info, course selection and payment.', 'everest-forms'),
		prompt: 'A course registration form with personal info, course selection and payment fields.',
	},
];

const MAX_CHARS = 500;

const CreateWithAI: React.FC<CreateWithAIProps> = ({ onBack }) => {
	const [prompt, setPrompt] = useState('');
	const [selectedType, setSelectedType] = useState<string | null>(null);
	const hasPrompt = prompt.trim().length > 0;

	return (
		<Box bg="#f3f3f5" minHeight="600px">
			{/* Top bar: back arrow (left) + generation counter (right) */}
			<Flex justify="space-between" align="center" px="32px" pt="20px">
				<HStack
					spacing="6px"
					cursor="pointer"
					onClick={onBack}
					role="button"
					aria-label="Go back"
					_hover={{ opacity: 0.7 }}
				>
					<Icon as={FiArrowLeft} boxSize={4} color="#555" />
					<Text fontSize="13px" color="#555" margin="0">
						{__('Back', 'everest-forms')}
					</Text>
				</HStack>
				<HStack
					bg="white"
					border="1px solid #e4e4e4"
					borderRadius="20px"
					px="14px"
					py="6px"
					spacing="8px"
				>
					<Flex
						bg="#e8e8e8"
						borderRadius="full"
						w="20px"
						h="20px"
						align="center"
						justify="center"
						fontSize="11px"
						fontWeight="700"
						color="#555"
					>
						5
					</Flex>
					<Text fontSize="13px" color="#555555" margin="0">
						{__('5 of 5 generations', 'everest-forms')}
					</Text>
				</HStack>
			</Flex>

			{/* Main content */}
			<Box maxW="920px" mx="auto" px="24px" pt="44px" pb="60px">
				{/* Hero */}
				<VStack spacing="14px" mb="44px" textAlign="center">
					<Heading
						as="h1"
						fontSize="48px"
						fontWeight="800"
						color="#0f0f1a"
						lineHeight="1.15"
						margin="0"
						letterSpacing="-0.5px"
					>
						{__('What should we build today?', 'everest-forms')}
					</Heading>
					<Text fontSize="16px" color="#6b7280" margin="0" lineHeight="1.65" maxW="520px">
						{__("Describe your form in your own words. We'll handle the fields, logic and layout — in seconds.", 'everest-forms')}
					</Text>
				</VStack>

				{/* Prompt box */}
				<Box
					bg="white"
					borderRadius="16px"
					border="1px solid #e2e2e2"
					overflow="hidden"
					mb="8px"
				>
					{/* Textarea area */}
					<Box position="relative" p="20px 20px 12px">
						<Icon
							as={BsStars}
							boxSize={4}
							color="#c8c8d0"
							position="absolute"
							top="22px"
							left="20px"
						/>
						<Textarea
							value={prompt}
							onChange={(e) => setPrompt(e.target.value.slice(0, MAX_CHARS))}
							placeholder={__('A feedback form to learn how onboarding felt, with a 1–5 rating and one optional comment...', 'everest-forms')}
							border="none"
							resize="none"
							fontSize="15px"
							color="#1a1a1a"
							pl="28px"
							pr="4px"
							pt="0"
							pb="0"
							minHeight="170px"
							_focus={{ boxShadow: 'none', border: 'none', outline: 'none' }}
							_placeholder={{ color: '#c0c0cc' }}
						/>
					</Box>

					{/* Bottom bar row 1: paperclip + mic + generate */}
					<Flex
						px="16px"
						py="10px"
						align="center"
						justify="space-between"
						borderTop="1px solid #f0f0f0"
					>
						<Icon
							as={FiPaperclip}
							boxSize={4}
							color="#aaaaaa"
							cursor="pointer"
							_hover={{ color: '#777' }}
						/>
						<HStack spacing="10px">
							<Icon
								as={FiMic}
								boxSize={4}
								color="#aaaaaa"
								cursor="pointer"
								_hover={{ color: '#777' }}
							/>
							<Button
								size="sm"
								bg={hasPrompt ? '#1a1a1a' : '#e8e8ea'}
								color={hasPrompt ? 'white' : '#aaaaaa'}
								borderRadius="8px"
								fontSize="13px"
								fontWeight="600"
								px="14px"
								height="32px"
								cursor={hasPrompt ? 'pointer' : 'default'}
								_hover={{ bg: hasPrompt ? '#333333' : '#e8e8ea' }}
								rightIcon={<Icon as={FiArrowUp} boxSize={3} />}
							>
								{__('Generate', 'everest-forms')}
							</Button>
						</HStack>
					</Flex>

					{/* Bottom bar row 2: TYPE chips */}
					<Flex
						px="16px"
						py="10px"
						align="center"
						borderTop="1px solid #f0f0f0"
						gap="8px"
						flexWrap="wrap"
					>
						<Text
							fontSize="11px"
							fontWeight="700"
							color="#aaaaaa"
							textTransform="uppercase"
							letterSpacing="0.6px"
							margin="0"
							mr="2px"
						>
							{__('TYPE', 'everest-forms')}
						</Text>
						{TYPE_CHIPS.map((chip) => {
							const isSelected = selectedType === chip.label;
							return (
								<HStack
									key={chip.label}
									as="button"
									spacing="5px"
									px="10px"
									py="5px"
									borderRadius="6px"
									border={isSelected ? '1px solid #7545BB' : '1px solid #e0e0e0'}
									bg={isSelected ? '#f5f0ff' : 'white'}
									cursor="pointer"
									onClick={() => setSelectedType(isSelected ? null : chip.label)}
									transition="all 0.15s"
									_hover={{ borderColor: '#7545BB' }}
								>
									<Icon
										as={chip.icon}
										boxSize={3}
										color={isSelected ? '#7545BB' : '#666666'}
									/>
									<Text
										fontSize="12px"
										fontWeight="500"
										color={isSelected ? '#7545BB' : '#555555'}
										margin="0"
									>
										{__(chip.label, 'everest-forms')}
									</Text>
								</HStack>
							);
						})}
					</Flex>
				</Box>

				{/* Char counter */}
				<Flex justify="flex-end" mb="48px">
					<Text fontSize="12px" color="#b0b0b8" margin="0">
						{prompt.length}/{MAX_CHARS}
					</Text>
				</Flex>

				{/* Inspiration section */}
				<Box>
					<Flex justify="space-between" align="center" mb="20px">
						<Heading
							as="h3"
							fontSize="20px"
							fontWeight="700"
							color="#0f0f1a"
							margin="0"
						>
							{__('Need inspiration?', 'everest-forms')}
						</Heading>
						<Text fontSize="13px" color="#9999aa" margin="0">
							{__('Tap a card to use as starting point', 'everest-forms')}
						</Text>
					</Flex>

					<SimpleGrid columns={3} spacing="16px">
						{INSPIRATION_CARDS.map((card) => (
							<Box
								key={card.title}
								bg="white"
								borderRadius="12px"
								border="1px solid #e8e8e8"
								p="24px"
								cursor="pointer"
								transition="border-color 0.2s ease"
								_hover={{ borderColor: '#b89ee0' }}
								onClick={() => setPrompt(card.prompt)}
							>
								<Box
									bg="#ede8ff"
									borderRadius="10px"
									p="10px"
									display="inline-flex"
									alignItems="center"
									justifyContent="center"
									mb="16px"
								>
									<Icon as={card.icon} boxSize={5} color="#7545BB" />
								</Box>
								<Heading
									as="h4"
									fontSize="15px"
									fontWeight="700"
									color="#0f0f1a"
									margin="0 0 8px"
									lineHeight="1.4"
								>
									{card.title}
								</Heading>
								<Text fontSize="13px" color="#777777" margin="0" lineHeight="1.6">
									{card.description}
								</Text>
							</Box>
						))}
					</SimpleGrid>
				</Box>
			</Box>
		</Box>
	);
};

export default CreateWithAI;
