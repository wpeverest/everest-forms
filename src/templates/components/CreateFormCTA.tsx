import { Badge, Box, HStack, Heading, Icon, Text, VStack } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { FiArrowRight, FiPenTool, FiZap } from 'react-icons/fi';

interface CreateFormCTAProps {
	onCreateWithAI?: () => void;
	onCreateBlank?: () => void;
}

const CreateFormCTA: React.FC<CreateFormCTAProps> = ({
	onCreateWithAI,
	onCreateBlank,
}) => {
	return (
		<Box p="4px 0" mb="0">
			<HStack spacing="20px" width="100%" align="stretch">
				{/* Create with AI Card */}
				<Box
					bg="#f7f4fc"
					borderRadius="16px"
					p="20px 24px"
					flex="2"
					cursor="pointer"
					onClick={onCreateWithAI}
					border="1px solid #e5daf5"
					transition="border-color 0.2s ease"
					_hover={{
						borderColor: '#a87de0',
					}}
				>
					<VStack align="flex-start" spacing="12px" height="100%">
						<HStack width="100%" justify="space-between" align="center">
							<Box
								bg="#ede5f8"
								borderRadius="12px"
								p="10px"
								display="flex"
								alignItems="center"
								justifyContent="center"
							>
								<Icon as={FiZap} boxSize={5} color="#7545BB" />
							</Box>
							<Badge
								bg="#16a34a"
								color="white"
								fontSize="11px"
								fontWeight="700"
								px="10px"
								py="5px"
								borderRadius="5px"
								textTransform="uppercase"
								letterSpacing="0.6px"
							>
								{__('New', 'everest-forms')}
							</Badge>
						</HStack>

						<VStack align="flex-start" spacing="6px" flex="1">
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="700"
								color="#1a1a1a"
								lineHeight="1.4"
								margin="0"
							>
								{__('Create Form Using AI', 'everest-forms')}
							</Heading>
							<Text
								fontSize="14px"
								color="#6b6b6b"
								lineHeight="1.65"
								margin="0"
							>
								{__('Describe your form in plain words and let AI build the fields for you in seconds.', 'everest-forms')}
							</Text>
						</VStack>

						<HStack width="100%" justify="flex-end">
							<HStack
								as="button"
								color="#7545BB"
								fontSize="13px"
								fontWeight="600"
								cursor="pointer"
								bg="transparent"
								border="none"
								p="0"
								spacing="4px"
								_hover={{ opacity: 0.75 }}
								_active={{ opacity: 0.6 }}
							>
								<Text margin="0">{__('Get Started', 'everest-forms')}</Text>
								<Icon as={FiArrowRight} boxSize={3.5} />
							</HStack>
						</HStack>
					</VStack>
				</Box>

				{/* Create from Scratch Card */}
				<Box
					bg="white"
					borderRadius="16px"
					p="20px 24px"
					flex="1"
					cursor="pointer"
					onClick={onCreateBlank}
					border="1px solid #e8e8e8"
					transition="border-color 0.2s ease"
					_hover={{
						borderColor: '#b0b0b0',
					}}
				>
					<VStack align="flex-start" spacing="12px" height="100%">
						<Box
							bg="#f0ecfa"
							borderRadius="12px"
							p="10px"
							display="flex"
							alignItems="center"
							justifyContent="center"
						>
							<Icon as={FiPenTool} boxSize={5} color="#7545BB" />
						</Box>

						<VStack align="flex-start" spacing="6px" flex="1">
							<Heading
								as="h3"
								fontSize="16px"
								fontWeight="700"
								color="#1a1a1a"
								lineHeight="1.4"
								margin="0"
							>
								{__('Create from Scratch', 'everest-forms')}
							</Heading>
							<Text
								fontSize="14px"
								color="#6b6b6b"
								lineHeight="1.65"
								margin="0"
							>
								{__('Start with a blank canvas and design your form field by field with full control.', 'everest-forms')}
							</Text>
						</VStack>

						<HStack width="100%" justify="flex-end">
							<HStack
								as="button"
								color="#7545BB"
								fontSize="13px"
								fontWeight="600"
								cursor="pointer"
								bg="transparent"
								border="none"
								p="0"
								spacing="4px"
								_hover={{ opacity: 0.75 }}
								_active={{ opacity: 0.6 }}
							>
								<Text margin="0">{__('Continue', 'everest-forms')}</Text>
								<Icon as={FiArrowRight} boxSize={3.5} />
							</HStack>
						</HStack>
					</VStack>
				</Box>
			</HStack>
		</Box>
	);
};

export default CreateFormCTA;
