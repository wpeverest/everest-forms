import { Box, HStack, Icon, SimpleGrid, Spinner, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { FiArrowRight } from 'react-icons/fi';
import { LuPencilLine, LuSparkles } from 'react-icons/lu';
import { templatesScriptData } from '../utils/global';

interface CreateFormCTAProps {
	onCreateWithAI?: () => void;
	onCreateBlank?: () => void;
	isCreatingBlank?: boolean;
}

// "Create with AI" is disabled (shown greyed-out, not clickable) on local /
// development sites where the AI gateway is unavailable.
const AI_ENABLED = !!templatesScriptData?.aiEnabled;

const CreateFormCTA: React.FC<CreateFormCTAProps> = ({
	onCreateWithAI,
	onCreateBlank,
	isCreatingBlank = false,
}) => {
	return (
		<SimpleGrid columns={{ base: 1, md: 2 }} spacing="32px">
			{/* AI Card */}
			<Box
				position="relative"
				overflow="hidden"
				bg="white"
				borderRadius="16px"
				border="1px solid #e2e8f0"
				p="32px"
				display="flex"
				flexDirection="column"
				cursor={AI_ENABLED ? 'pointer' : 'not-allowed'}
				opacity={AI_ENABLED ? 1 : 0.6}
				onClick={() => AI_ENABLED && onCreateWithAI && onCreateWithAI()}
				transition="border-color 0.2s, box-shadow 0.2s"
				_hover={AI_ENABLED ? {
					borderColor: 'rgba(117,69,187,0.4)',
					boxShadow: '0 10px 30px -15px rgba(117,69,187,0.25)',
				} : {}}
			>
				{/* Gradient overlay decorations */}
				<Box
					position="absolute"
					inset="0"
					bgGradient="linear(to-br, rgba(117,69,187,0.08), rgba(117,69,187,0.03), transparent)"
					pointerEvents="none"
				/>
				<Box
					position="absolute"
					top="-96px"
					right="-96px"
					w="288px"
					h="288px"
					bg="rgba(117,69,187,0.09)"
					borderRadius="full"
					filter="blur(48px)"
					pointerEvents="none"
				/>
				<Box
					position="absolute"
					bottom="-64px"
					left="-64px"
					w="224px"
					h="224px"
					bg="rgba(117,69,187,0.06)"
					borderRadius="full"
					filter="blur(48px)"
					pointerEvents="none"
				/>

				{/* Icon + NEW badge row */}
				<HStack justify="space-between" align="center" mb="16px" position="relative">
					<Box
						w="48px"
						h="48px"
						borderRadius="12px"
						bgGradient="linear(135deg, #7545BB 0%, #9660db 100%)"
						display="flex"
						alignItems="center"
						justifyContent="center"
						boxShadow="0 6px 20px -6px rgba(117,69,187,0.45)"
					>
						<Icon as={LuSparkles} boxSize="5" color="white" />
					</Box>
					<Box
						as="span"
						display="inline-flex"
						alignItems="center"
						bg="#28af60"
						color="white"
						fontSize="10px"
						fontWeight="700"
						textTransform="uppercase"
						letterSpacing="0.08em"
						py="4px"
						px="10px"
						borderRadius="6px"
					>
						{__('New', 'everest-forms')}
					</Box>
				</HStack>

				{/* Title */}
				<Text
					as="h3"
					fontSize="18px"
					fontWeight="600"
					color="#0e0e0e"
					lineHeight="1.4"
					mb="6px"
					position="relative"
				>
					{__('Create Form Using AI', 'everest-forms')}
				</Text>

				{/* Description */}
				<Text
					fontSize="14px"
					color="#6b6b6b"
					lineHeight="1.6"
					mb="24px"
					position="relative"
					flex="1"
				>
					{__('Describe your form in plain words and let AI build the fields for you in seconds.', 'everest-forms')}
				</Text>

				{/* CTA link */}
				<HStack
					spacing="6px"
					color={AI_ENABLED ? '#7545BB' : '#9ca3af'}
					fontSize="14px"
					fontWeight="500"
					position="relative"
					mt="auto"
				>
					{AI_ENABLED ? (
						<>
							<Text margin="0">{__('Get Started', 'everest-forms')}</Text>
							<Icon as={FiArrowRight} boxSize="4" />
						</>
					) : (
						<Text margin="0">{__('AI service is currently unavailable for local and staging site.', 'everest-forms')}</Text>
					)}
				</HStack>
			</Box>

			{/* Scratch Card */}
			<Box
				bg="white"
				borderRadius="16px"
				border="1px solid #e2e8f0"
				p="32px"
				display="flex"
				flexDirection="column"
				cursor={isCreatingBlank ? 'default' : 'pointer'}
				onClick={isCreatingBlank ? undefined : onCreateBlank}
				transition="border-color 0.2s, box-shadow 0.2s"
				_hover={{
					borderColor: isCreatingBlank ? '#e2e8f0' : 'rgba(117,69,187,0.4)',
					boxShadow: isCreatingBlank ? 'none' : '0 10px 30px -15px rgba(117,69,187,0.18)',
				}}
				opacity={isCreatingBlank ? 0.6 : 1}
			>
				{/* Icon */}
				<Box
					w="48px"
					h="48px"
					borderRadius="12px"
					bg="rgba(117,69,187,0.1)"
					display="flex"
					alignItems="center"
					justifyContent="center"
					mb="20px"
				>
					{isCreatingBlank
						? <Spinner size="sm" color="#7545BB" thickness="2px" speed="0.65s" />
						: <Icon as={LuPencilLine} boxSize="5" color="#7545BB" />
					}
				</Box>

				{/* Title */}
				<Text
					as="h3"
					fontSize="18px"
					fontWeight="600"
					color="#0e0e0e"
					lineHeight="1.4"
					mb="6px"
				>
					{__('Create from Scratch', 'everest-forms')}
				</Text>

				{/* Description */}
				<Text
					fontSize="14px"
					color="#6b6b6b"
					lineHeight="1.6"
					mb="24px"
					flex="1"
				>
					{__('Start with a blank canvas and design your form field by field with full control.', 'everest-forms')}
				</Text>

				{/* CTA link */}
				<HStack
					spacing="6px"
					color={isCreatingBlank ? '#aaa' : '#7545BB'}
					fontSize="14px"
					fontWeight="500"
				>
					<Text margin="0">{__('Continue', 'everest-forms')}</Text>
					<Icon as={FiArrowRight} boxSize="4" opacity={isCreatingBlank ? 0 : 1} />
				</HStack>
			</Box>
		</SimpleGrid>
	);
};

export default CreateFormCTA;
