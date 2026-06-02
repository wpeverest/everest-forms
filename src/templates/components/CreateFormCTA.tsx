import React from 'react';
import { Box, Button, HStack, Icon } from '@chakra-ui/react';
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
			p="24px 0"
			mb="24px"
		>
			<HStack spacing="16px" width="100%">
				<Button
					leftIcon={<Icon as={FiZap} boxSize={6} />}
					colorScheme="purple"
					size="lg"
					fontSize="17px"
					fontWeight="600"
					height="56px"
					px="32px"
					borderRadius="8px"
					onClick={onCreateWithAI}
					flex={1}
					_hover={{
						opacity: 0.9,
						boxShadow: '0 8px 16px rgba(117, 69, 187, 0.2)',
					}}
					transition="all 0.2s"
				>
					{__('✨ Create with AI', 'everest-forms')}
				</Button>

				<Button
					leftIcon={<Icon as={FiPlus} boxSize={6} />}
					variant="outline"
					colorScheme="gray"
					size="lg"
					fontSize="17px"
					fontWeight="600"
					height="56px"
					px="32px"
					borderRadius="8px"
					borderColor="#d0d0d0"
					color="#0f0f1a"
					bg="white"
					onClick={onCreateBlank}
					flex={1}
					_hover={{
						bg: '#f5f5f5',
						borderColor="#b0b0b0",
					}}
					transition="all 0.2s"
				>
					{__('Start Blank', 'everest-forms')}
				</Button>
			</HStack>
		</Box>
	);
};

export default CreateFormCTA;
