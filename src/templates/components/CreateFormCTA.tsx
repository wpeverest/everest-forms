import { Box, HStack, VStack, Text, Heading, Badge, Icon } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { FiZap, FiPenTool } from 'react-icons/fi';

interface CreateFormCTAProps {
	onCreateWithAI?: () => void;
	onCreateBlank?: () => void;
}

const CreateFormCTA: React.FC<CreateFormCTAProps> = ({
	onCreateWithAI,
	onCreateBlank,
}) => {
	return (
		<Box p="24px 0" mb="24px">
			<HStack spacing="24px" width="100%" align="stretch">
				{/* Create with AI Card - Larger */}
				<Box
					bg="#f7f5fb"
					borderRadius="20px"
					p="40px"
					flex="2"
					cursor="pointer"
					onClick={onCreateWithAI}
				>
					<VStack align="flex-start" spacing="20px" height="100%">
						<HStack spacing="16px" align="flex-start" width="100%">
							<Box
								bg="#7545BB"
								borderRadius="14px"
								p="14px"
								display="flex"
								alignItems="center"
								justifyContent="center"
								flexShrink="0"
							>
								<Icon as={FiZap} boxSize={7} color="white" />
							</Box>
							<Badge
								bg="#c0f0d9"
								color="#1f6651"
								fontSize="11px"
								fontWeight="700"
								px="10px"
								py="5px"
								borderRadius="5px"
								textTransform="uppercase"
								letterSpacing="0.4px"
								mt="4px"
							>
								{__('New', 'everest-forms')}
							</Badge>
						</HStack>

						<VStack align="flex-start" spacing="12px" flex="1">
							<Heading
								as="h3"
								fontSize="20px"
								fontWeight="700"
								color="#1a1a1a"
								lineHeight="1.3"
								margin="0"
							>
								{__('Create Form Using AI', 'everest-forms')}
							</Heading>
							<Text
								fontSize="15px"
								color="#666666"
								lineHeight="1.6"
								margin="0"
							>
								{__('Describe your form in plain words and let AI build the fields for you in seconds.', 'everest-forms')}
							</Text>
						</VStack>

						<Box
							as="button"
							color="#7545BB"
							fontSize="15px"
							fontWeight="600"
							mt="auto"
							cursor="pointer"
							bg="transparent"
							border="none"
							p="0"
							display="flex"
							alignItems="center"
							gap="6px"
							_active={{ opacity: 0.8 }}
						>
							{__('Get Started', 'everest-forms')} →
						</Box>
					</VStack>
				</Box>

				{/* Create from Scratch Card - Smaller */}
				<Box
					bg="white"
					borderRadius="20px"
					p="40px"
					flex="1"
					cursor="pointer"
					onClick={onCreateBlank}
					border="1px solid #e8e8e8"
				>
					<VStack align="flex-start" spacing="20px" height="100%">
						<Box
							bg="#e8dff5"
							borderRadius="14px"
							p="14px"
							display="flex"
							alignItems="center"
							justifyContent="center"
							flexShrink="0"
						>
							<Icon as={FiPenTool} boxSize={7} color="#7545BB" />
						</Box>

						<VStack align="flex-start" spacing="12px" flex="1">
							<Heading
								as="h3"
								fontSize="20px"
								fontWeight="700"
								color="#1a1a1a"
								lineHeight="1.3"
								margin="0"
							>
								{__('Create from Scratch', 'everest-forms')}
							</Heading>
							<Text
								fontSize="15px"
								color="#666666"
								lineHeight="1.6"
								margin="0"
							>
								{__('Start with a blank canvas and design your form field by field with full control.', 'everest-forms')}
							</Text>
						</VStack>

						<Box
							as="button"
							color="#7545BB"
							fontSize="15px"
							fontWeight="600"
							mt="auto"
							cursor="pointer"
							bg="transparent"
							border="none"
							p="0"
							display="flex"
							alignItems="center"
							gap="6px"
							_active={{ opacity: 0.8 }}
						>
							{__('Continue', 'everest-forms')} →
						</Box>
					</VStack>
				</Box>
			</HStack>
		</Box>
	);
};

export default CreateFormCTA;
