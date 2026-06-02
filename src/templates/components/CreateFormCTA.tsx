import React from 'react';
import { Box, Button, HStack, Heading, Icon, Text, VStack } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { FiZap, FiPlus } from 'react-icons/fi';

interface CreateFormCTAProps {
	onCreateWithAI?: () => void;
	onCreateBlank?: () => void;
}

const CreateFormCTA: React.FC<CreateFormCTAProps> = ({
	onCreateWithAI,
	onCreateBlank,
}) => {
	return (
		<Box
			bg="white"
			borderRadius="13px"
			p="32px"
			mb="32px"
			border="1px solid #e1e1e1"
			position="relative"
			overflow="hidden"
			sx={{
				'::before': {
					content: '""',
					position: 'absolute',
					inset: '0',
					bg: 'radial-gradient(ellipse 60% 120% at 100% 50%, rgba(96, 64, 240, 0.07) 0%, transparent 70%), radial-gradient(ellipse 40% 80% at 80% 20%, rgba(61, 126, 245, 0.06) 0%, transparent 60%)',
					pointerEvents: 'none',
				},
			}}
		>
			<VStack align="start" spacing="24px" position="relative" zIndex={1}>
				<VStack align="start" spacing="8px">
					<Heading
						as="h2"
						fontSize="28px"
						fontWeight="700"
						color="#0f0f1a"
						lineHeight="1.2"
						letterSpacing="-0.5px"
						margin="0"
					>
						{__('Create Your Next Form', 'everest-forms')}
					</Heading>
					<Text
						fontSize="16px"
						color="#6b6b85"
						lineHeight="1.6"
						margin="0"
						maxW="600px"
					>
						{__('Start with AI-powered assistance or build from scratch with our beautiful templates.', 'everest-forms')}
					</Text>
				</VStack>

				<HStack spacing="16px" width={{ base: 'full', md: 'auto' }}>
					<Button
						leftIcon={<Icon as={FiZap} boxSize={5} />}
						colorScheme="purple"
						size="lg"
						fontSize="16px"
						fontWeight="600"
						height="48px"
						px="24px"
						borderRadius="8px"
						onClick={onCreateWithAI}
						_hover={{
							transform: 'translateY(-2px)',
							boxShadow: '0 12px 24px rgba(117, 69, 187, 0.3)',
						}}
						transition="all 0.2s"
					>
						{__('✨ Create with AI', 'everest-forms')}
					</Button>

					<Button
						leftIcon={<Icon as={FiPlus} boxSize={5} />}
						variant="outline"
						colorScheme="gray"
						size="lg"
						fontSize="16px"
						fontWeight="600"
						height="48px"
						px="24px"
						borderRadius="8px"
						borderColor="#e1e1e1"
						color="#0f0f1a"
						bg="white"
						onClick={onCreateBlank}
						_hover={{
							bg: '#f9f9f9',
							borderColor: '#d0d0d0',
							transform: 'translateY(-2px)',
						}}
						transition="all 0.2s"
					>
						{__('Start Blank', 'everest-forms')}
					</Button>
				</HStack>
			</VStack>
		</Box>
	);
};

export default CreateFormCTA;
