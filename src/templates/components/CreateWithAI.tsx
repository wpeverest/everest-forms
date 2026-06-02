import {
	Box,
	Button,
	Divider,
	Flex,
	HStack,
	Heading,
	Icon,
	Input,
	SimpleGrid,
	Text,
	Textarea,
	VStack,
	keyframes,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { BsCalculator, BsGraduationCap, BsStars } from 'react-icons/bs';
import {
	FiArrowLeft,
	FiArrowUp,
	FiCalendar,
	FiCheck,
	FiCreditCard,
	FiFileText,
	FiHeart,
	FiLayout,
	FiList,
	FiMessageSquare,

	FiEdit3,
	FiRefreshCw,
	FiThumbsDown,
	FiThumbsUp,
} from 'react-icons/fi';

// ─── animations ──────────────────────────────────────────────────────────────

const pulseGlow = keyframes`
  0%   { box-shadow: 0 0 0 0 rgba(117,69,187,0.3); transform: scale(1); }
  50%  { box-shadow: 0 0 28px 10px rgba(117,69,187,0.12); transform: scale(1.06); }
  100% { box-shadow: 0 0 0 0 rgba(117,69,187,0.3); transform: scale(1); }
`;

const dotBounce = keyframes`
  0%, 80%, 100% { transform: scale(0.35); opacity: 0.3; }
  40%            { transform: scale(1);    opacity: 1;   }
`;

const fadeUp = keyframes`
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0);    }
`;

const shimmer = keyframes`
  0%   { background-position: -400px 0; }
  100% { background-position:  400px 0; }
`;

// ─── constants ───────────────────────────────────────────────────────────────

const TYPE_CHIPS = [
	{ label: 'Survey',         icon: FiFileText      },
	{ label: 'Payment',        icon: FiCreditCard    },
	{ label: 'Calculator',     icon: BsCalculator    },
	{ label: 'Conversational', icon: FiMessageSquare },
	{ label: 'Quiz',           icon: FiList          },
	{ label: 'Application',    icon: FiLayout        },
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

const GEN_STEPS = [
	__('Understanding your prompt',         'everest-forms'),
	__('Designing field structure',          'everest-forms'),
	__('Configuring validation & logic',     'everest-forms'),
	__('Finalizing your form',               'everest-forms'),
];

const MOCK_FIELDS = [
	{ id: 1, type: 'name',     label: 'Full Name',                  required: true  },
	{ id: 2, type: 'email',    label: 'Email Address',              required: true  },
	{ id: 3, type: 'rating',   label: 'Overall Rating',             required: false },
	{ id: 4, type: 'textarea', label: 'Your Feedback',              required: false },
	{ id: 5, type: 'select',   label: 'How did you hear about us?', required: false },
];

const MAX_CHARS = 500;

// ─── field preview — exact Everest Forms builder styling ─────────────────────

// Exact values from computed builder styles:
//   input:    border 1px solid #e1e1e1, borderRadius 3px, height 38px, padding 4px 10px
//   textarea: border 1px solid #cdd0d8, borderRadius 2px, height 120px, padding 8px 12px
//   label:    fontSize 14px, fontWeight 500, color #222

const EVFLabel: React.FC<{ text: string; required?: boolean }> = ({ text, required }) => (
	<Box mb="5px">
		<Text as="span" fontSize="14px" fontWeight="500" color="#222">{text}</Text>
		{required && <Text as="span" color="red.500" ml="2px" fontSize="13px"> *</Text>}
	</Box>
);

const EVFInput = () => (
	<Box h="38px" bg="white" border="1px solid #e1e1e1" borderRadius="3px" />
);

const FieldPreview: React.FC<{ field: any }> = ({ field }) => {
	if (field.type === 'name') {
		return (
			<Box>
				<EVFLabel text={field.label} />
				<HStack spacing="10px">
					{['First', 'Last'].map(sub => (
						<Box key={sub} flex={1}>
							<EVFInput />
							<Text fontSize="11px" color="#999" margin="3px 0 0">{sub}</Text>
						</Box>
					))}
				</HStack>
			</Box>
		);
	}

	if (field.type === 'textarea') {
		return (
			<Box>
				<EVFLabel text={field.label} />
				<Box
					h="120px" bg="white"
					border="1px solid #cdd0d8"
					borderRadius="2px"
				/>
			</Box>
		);
	}

	if (field.type === 'rating') {
		return (
			<Box>
				<EVFLabel text={field.label} />
				<HStack spacing="3px">
					{[1, 2, 3, 4, 5].map(n => (
						<Text key={n} fontSize="24px" color={n <= 4 ? '#f59e0b' : '#e0e0e8'} lineHeight="1" margin="0">
							★
						</Text>
					))}
				</HStack>
			</Box>
		);
	}

	if (field.type === 'select') {
		return (
			<Box>
				<EVFLabel text={field.label} />
				<Flex
					h="38px" bg="white"
					border="1px solid #e1e1e1"
					borderRadius="3px"
					px="10px" align="center" justify="space-between"
				>
					<Text fontSize="14px" color="#bbb" margin="0">---</Text>
					<Text fontSize="11px" color="#bbb" margin="0">▾</Text>
				</Flex>
			</Box>
		);
	}

	return (
		<Box>
			<EVFLabel text={field.label} required={field.required} />
			<EVFInput />
		</Box>
	);
};

// ─── skeleton field (generating preview) ─────────────────────────────────────

const SkeletonField: React.FC<{ delay?: string }> = ({ delay = '0s' }) => (
	<Box sx={{ animation: `${fadeUp} 0.4s ease ${delay} both` }}>
		<Box
			h="12px" w="30%" mb="8px" borderRadius="4px"
			sx={{
				background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)',
				backgroundSize: '400px 100%',
				animation: `${shimmer} 1.6s ease-in-out infinite`,
			}}
		/>
		<Box
			h="36px" borderRadius="6px"
			sx={{
				background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)',
				backgroundSize: '400px 100%',
				animation: `${shimmer} 1.6s ease-in-out infinite`,
			}}
		/>
	</Box>
);

// ─── main component ───────────────────────────────────────────────────────────

interface CreateWithAIProps {
	onBack: () => void;
}

const CreateWithAI: React.FC<CreateWithAIProps> = ({ onBack }) => {
	const [prompt, setPrompt] = useState('');
	const [genState, setGenState] = useState<'idle' | 'generating' | 'generated'>('idle');
	const [genStep, setGenStep] = useState(-1);
	const [showPreviewHint, setShowPreviewHint] = useState(false);
	const [hintPos, setHintPos] = useState({ x: 0, y: 0 });
	const previewHintTimer = React.useRef<ReturnType<typeof setTimeout> | null>(null);

	const handleFieldClick = (e: React.MouseEvent) => {
		setHintPos({ x: e.clientX, y: e.clientY });
		setShowPreviewHint(true);
		if (previewHintTimer.current) clearTimeout(previewHintTimer.current);
		previewHintTimer.current = setTimeout(() => setShowPreviewHint(false), 2600);
	};
	const hasPrompt = prompt.trim().length > 0;

	useEffect(() => {
		if (genState !== 'generating') return;
		setGenStep(-1);
		let step = -1;
		const id = setInterval(() => {
			step += 1;
			setGenStep(step);
			if (step >= GEN_STEPS.length - 1) {
				clearInterval(id);
				setTimeout(() => setGenState('generated'), 700);
			}
		}, 950);
		return () => clearInterval(id);
	}, [genState]);

	const handleGenerate = () => {
		if (!hasPrompt) return;
		setGenState('generating');
	};

	// ── shared top bar (idle + generating only) ──────────────────────────────
	const topBar = (
		<Flex align="center" px="32px" pt="10px" pb="6px" flexShrink={0}>
			<HStack
				spacing="6px"
				cursor="pointer"
				onClick={onBack}
				role="button"
				_hover={{ opacity: 0.7 }}
				transition="opacity 0.2s"
			>
				<Icon as={FiArrowLeft} boxSize={4} color="#555" />
				<Text fontSize="13px" color="#555" margin="0">
					{__('Back', 'everest-forms')}
				</Text>
			</HStack>
		</Flex>
	);

	// ── generating state ──────────────────────────────────────────────────────
	if (genState === 'generating') {
		return (
			<Flex height="100vh" overflow="hidden">

				{/* Left panel — same width as aside in generated state */}
				<Flex
					w="440px" flexShrink={0}
					bg="white" borderRight="1px solid #ebebf0"
					direction="column" align="center" justify="center"
					px="36px" py="40px" gap="0"
				>
					{/* Orb */}
					<Box
						w="64px" h="64px" borderRadius="full"
						background="linear-gradient(135deg, #9c6de8 0%, #7545BB 100%)"
						display="flex" alignItems="center" justifyContent="center"
						mb="20px"
						sx={{ animation: `${pulseGlow} 2s ease-in-out infinite` }}
					>
						<Icon as={BsStars} boxSize={6} color="white" />
					</Box>

					{/* Headline */}
					<VStack spacing="5px" textAlign="center" mb="28px">
						<Heading as="h2" fontSize="22px" fontWeight="700" color="#0f0f1a" margin="0" letterSpacing="-0.3px">
							{__('Building your form…', 'everest-forms')}
						</Heading>
						<Text fontSize="13px" color="#a0a0b0" margin="0">
							{__('This usually takes a few seconds', 'everest-forms')}
						</Text>
					</VStack>

					{/* Steps */}
					<Box
						bg="#faf9ff" borderRadius="12px" border="1px solid #ede8f8"
						p="20px 24px" width="100%" mb="20px"
					>
						<VStack align="stretch" spacing="14px">
							{GEN_STEPS.map((step, i) => {
								const isDone    = i < genStep;
								const isActive  = i === genStep;
								const isPending = i > genStep;
								return (
									<HStack key={i} spacing="12px" opacity={isPending ? 0.3 : 1} transition="opacity 0.4s">
										<Flex
											w="20px" h="20px" borderRadius="full" flexShrink={0}
											bg={isDone ? '#7545BB' : 'transparent'}
											border={isDone ? 'none' : '2px solid'}
											borderColor={isActive ? '#7545BB' : '#ddd'}
											align="center" justify="center"
											transition="all 0.35s"
										>
											{isDone && <Icon as={FiCheck} color="white" sx={{ width: '9px', height: '9px', strokeWidth: 3 }} />}
											{isActive && (
												<Box w="6px" h="6px" borderRadius="full" bg="#7545BB"
													sx={{ animation: `${dotBounce} 1.1s ease-in-out infinite` }}
												/>
											)}
										</Flex>
										<Text
											flex={1} fontSize="13px" margin="0"
											fontWeight={isActive ? '600' : isDone ? '500' : '400'}
											color={isActive ? '#7545BB' : isDone ? '#2a2a3a' : '#c0c0cc'}
											transition="all 0.35s"
										>
											{step}
										</Text>
										{isActive && (
											<HStack spacing="3px">
												{[0, 1, 2].map(d => (
													<Box key={d} w="4px" h="4px" borderRadius="full" bg="#7545BB"
														sx={{ animation: `${dotBounce} 1.1s ease-in-out ${d * 0.18}s infinite` }}
													/>
												))}
											</HStack>
										)}
									</HStack>
								);
							})}
						</VStack>
					</Box>

					{/* Prompt echo */}
					<Text fontSize="12px" color="#c0c0cc" margin="0" textAlign="center" noOfLines={1} isTruncated width="100%">
						"{prompt}"
					</Text>
				</Flex>

				{/* Right panel — skeleton form preview, same bg as generated */}
				<Box flex={1} bg="#f1f1f1" overflowY="auto" p="20px 24px">
					<Box
						bg="white" border="1px solid #e1e1e1" borderRadius="6px" overflow="hidden" mb="14px"
						sx={{ animation: `${fadeUp} 0.4s ease 0.15s both` }}
					>
						{/* Title bar skeleton */}
						<Flex align="center" px="20px" py="12px" borderBottom="1px solid #e1e1e1" gap="10px">
							<Box h="14px" w="14px" borderRadius="2px"
								sx={{ background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)', backgroundSize: '400px 100%', animation: `${shimmer} 1.6s ease-in-out infinite` }}
							/>
							<Box h="14px" w="40%" borderRadius="3px"
								sx={{ background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)', backgroundSize: '400px 100%', animation: `${shimmer} 1.6s ease-in-out infinite` }}
							/>
						</Flex>
						{/* Fields skeleton */}
						<Box p="20px 24px">
							<VStack spacing="14px" align="stretch">
								<SkeletonField delay="0.1s" />
								<SkeletonField delay="0.22s" />
								<SkeletonField delay="0.34s" />
								<SkeletonField delay="0.46s" />
								<SkeletonField delay="0.58s" />
							</VStack>
						</Box>
					</Box>
				</Box>
			</Flex>
		);
	}

	// ── generated state ───────────────────────────────────────────────────────
	if (genState === 'generated') {
		return (
			<>
			{/* Cursor-following preview tooltip — fixed, outside all scroll containers */}
			{showPreviewHint && (
				<Box
					position="fixed"
					top={`${hintPos.y + 14}px`}
					left={`${hintPos.x}px`}
					transform="translateX(-50%)"
					bg="rgba(18,18,18,0.93)"
					color="white"
					borderRadius="8px"
					px="14px"
					py="10px"
					textAlign="center"
					pointerEvents="none"
					zIndex={9999}
					boxShadow="0 4px 20px rgba(0,0,0,0.2)"
					maxW="260px"
					sx={{ animation: `${fadeUp} 0.15s ease` }}
				>
					{/* Arrow */}
					<Box
						position="absolute"
						top="-5px"
						left="50%"
						transform="translateX(-50%)"
						width="0"
						height="0"
						borderLeft="5px solid transparent"
						borderRight="5px solid transparent"
						borderBottom="5px solid rgba(18,18,18,0.93)"
					/>
					<Text fontSize="12px" fontWeight="600" margin="0 0 3px" lineHeight="1.45">
						{__('This is just a preview of your form.', 'everest-forms')}
					</Text>
					<Text fontSize="12px" color="rgba(255,255,255,0.65)" margin="0" lineHeight="1.45">
						{__('Click "Use This Form" to start editing.', 'everest-forms')}
					</Text>
				</Box>
			)}

			<Flex
				height="100vh"
				overflow="hidden"
				sx={{ animation: `${fadeUp} 0.4s ease` }}
			>
				{/* ── Left: conversation panel ── */}
				<Flex
					w="440px" flexShrink={0}
					bg="white" borderRight="1px solid #ebebf0"
					direction="column"
				>
					{/* New Prompt button at top of aside */}
					<Flex
						px="16px" py="12px"
						borderBottom="1px solid #f0f0f4"
						flexShrink={0}
					>
						<HStack
							spacing="6px"
							cursor="pointer"
							onClick={() => setGenState('idle')}
							_hover={{ opacity: 0.7 }}
							transition="opacity 0.2s"
						>
							<Icon as={FiArrowLeft} boxSize={4} color="#555" />
							<Text fontSize="13px" color="#555" margin="0" fontWeight="500">
								{__('New Prompt', 'everest-forms')}
							</Text>
						</HStack>
					</Flex>

					{/* Chat history */}
					<Box flex={1} p="20px" overflowY="auto">

							{/* User bubble */}
							<Flex justify="flex-end" mb="18px">
								<Box
									bg="#7545BB" color="white"
									borderRadius="14px 14px 4px 14px"
									px="13px" py="10px"
									fontSize="13px" lineHeight="1.55"
									maxW="220px"
								>
									{prompt}
								</Box>
							</Flex>

							{/* AI response */}
							<HStack align="flex-start" spacing="10px">
								<Flex
									w="26px" h="26px" borderRadius="full"
									bg="#f0ecfa" align="center" justify="center"
									flexShrink={0} mt="2px"
								>
									<Icon as={BsStars} boxSize="12px" color="#7545BB" />
								</Flex>

								<Box
									flex={1}
									bg="#faf9ff"
									border="1px solid #ede8f8"
									borderRadius="4px 14px 14px 14px"
									p="14px"
									boxShadow="0 2px 10px rgba(117,69,187,0.06)"
								>
									<Text fontSize="13px" color="#444" lineHeight="1.65" margin="0 0 14px">
										{__("Here's your form! It includes name, email, a 5-star rating, open feedback, and a source question. Ready to use it?", 'everest-forms')}
									</Text>

									<Button
										bg="#7545BB" color="white"
										size="sm" borderRadius="7px"
										fontSize="13px" fontWeight="600"
										_hover={{ bg: '#6a3daa' }}
										width="100%" height="32px"
										mb="10px"
									>
										{__('Use This Form', 'everest-forms')}
									</Button>

									<HStack justify="space-between" pt="2px">
										<HStack spacing="10px">
											<Icon as={FiThumbsUp}   boxSize="14px" color="#c8c8d4" cursor="pointer" _hover={{ color: '#7545BB'  }} />
											<Icon as={FiThumbsDown} boxSize="14px" color="#c8c8d4" cursor="pointer" _hover={{ color: '#e05050'  }} />
										</HStack>
										<HStack
											spacing="4px" cursor="pointer"
											_hover={{ opacity: 0.65 }}
											onClick={() => setGenState('generating')}
										>
											<Icon as={FiRefreshCw} boxSize="12px" color="#aaa" />
											<Text fontSize="12px" color="#aaa" margin="0">
												{__('Regenerate', 'everest-forms')}
											</Text>
										</HStack>
									</HStack>
								</Box>
							</HStack>
						</Box>

						{/* Follow-up input */}
						<Box borderTop="1px solid #f0f0f4" p="12px 14px">
							<Box
								border="1px solid #e8e8f0" borderRadius="10px"
								bg="white" px="12px" pt="10px" pb="8px"
								position="relative"
								transition="border-color 0.2s"
								_focusWithin={{ borderColor: '#7545BB' }}
							>
								<Textarea
									placeholder={__('Refine or follow up…', 'everest-forms')}
									border="none" fontSize="13px" color="#333"
									_focus={{ boxShadow: 'none' }}
									p="0" minHeight="60px" resize="none"
									_placeholder={{ color: '#c8c8d4' }}
								/>
								<Flex justify="flex-end" mt="6px">
									<Icon as={FiArrowUp} boxSize={4} color="#7545BB" cursor="pointer" />
								</Flex>
							</Box>
						</Box>
					</Flex>

					{/* ── Right: form preview panel ── */}
					<Box flex={1} bg="#f1f1f1" overflowY="auto" p="20px 24px">

						{/* EVF builder-style canvas card */}
						<Box
							bg="white"
							border="1px solid #e1e1e1"
							borderRadius="6px"
							overflow="hidden"
							mb="14px"
						>
							{/* Form title bar — matches builder */}
							<Flex
								align="center" px="20px" py="12px"
								borderBottom="1px solid #e1e1e1"
								gap="8px"
							>
								<Icon as={FiEdit3} boxSize="15px" color="#7545BB" />
								<Text fontSize="16px" fontWeight="600" color="#383838" margin="0">
									Customer Feedback Survey
								</Text>
							</Flex>

							{/* Fields section */}
							<Box p="20px 24px">
								<VStack spacing="14px" align="stretch">
									{MOCK_FIELDS.map(f => (
										<Box
											key={f.id}
											cursor="default"
											onClick={(e) => handleFieldClick(e)}
										>
											<FieldPreview field={f} />
										</Box>
									))}
								</VStack>

								{/* Submit button — matches builder purple style */}
								<Button
									bg="#7545BB" color="white"
									borderRadius="3px" fontSize="14px"
									fontWeight="500" px="20px" height="38px"
									mt="20px" _hover={{ bg: '#6a3daa' }}
								>
									{__('Submit', 'everest-forms')}
								</Button>
							</Box>
						</Box>

						{/* Open in Builder CTA */}
						<Flex
							bg="white" borderRadius="6px"
							border="1px solid #e1e1e1"
							p="12px 16px"
							align="center" justify="space-between" gap="16px"
						>
							<Box>
								<Text fontSize="14px" fontWeight="600" color="#1a1a1a" margin="0 0 2px">
									{__('Happy with this form?', 'everest-forms')}
								</Text>
								<Text fontSize="13px" color="#a0a0b0" margin="0">
									{__('Open it in the builder to customize fields and settings.', 'everest-forms')}
								</Text>
							</Box>
							<Button
								bg="#7545BB" color="white"
								borderRadius="3px" fontSize="13px"
								fontWeight="500" px="20px" height="36px"
								flexShrink={0} _hover={{ bg: '#6a3daa' }}
							>
								{__('Open in Builder', 'everest-forms')}
							</Button>
						</Flex>
					</Box>
				</Flex>
		</>
		);
	}

	// ── idle state ────────────────────────────────────────────────────────────
	return (
		<Box bg="#f3f3f5" minHeight="100vh">
			{topBar}

			<Box maxW="920px" mx="auto" px="24px" pt="16px" pb="20px">
				{/* Hero */}
				<VStack spacing="10px" mb="20px" textAlign="center">
					<Heading
						as="h1" fontSize="38px" fontWeight="800"
						color="#0f0f1a" lineHeight="1.15" margin="0" letterSpacing="-0.5px"
					>
						{__('What should we build today?', 'everest-forms')}
					</Heading>
					<Text fontSize="16px" color="#6b7280" margin="0" lineHeight="1.65" maxW="520px">
						{__("Describe your form in your own words. We'll handle the fields, logic and layout — in seconds.", 'everest-forms')}
					</Text>
				</VStack>

				{/* Prompt box */}
				<Box bg="white" borderRadius="16px" border="1px solid #e2e2e2" overflow="hidden" mb="8px">
					<Box position="relative" p="20px 20px 12px">
						<Icon as={BsStars} boxSize={4} color="#c8c8d0" position="absolute" top="22px" left="20px" />
						<Textarea
							value={prompt}
							onChange={(e) => setPrompt(e.target.value.slice(0, MAX_CHARS))}
							placeholder={__('A feedback form to learn how onboarding felt, with a 1–5 rating and one optional comment…', 'everest-forms')}
							border="none" resize="none" fontSize="15px" color="#1a1a1a"
							pl="28px" pr="4px" pt="0" pb="0" minHeight="220px"
							_focus={{ boxShadow: 'none', border: 'none', outline: 'none' }}
							_placeholder={{ color: '#c0c0cc' }}
						/>
					</Box>

					{/* Action bar */}
					<Flex px="16px" py="12px" align="center" justify="flex-end" borderTop="1px solid #f0f0f0">
						<Button
							
							bg={hasPrompt ? '#7545BB' : '#e8e8ea'}
							color={hasPrompt ? 'white' : '#aaaaaa'}
							borderRadius="8px" fontSize="14px" fontWeight="600"
							px="20px" height="40px"
							cursor={hasPrompt ? 'pointer' : 'default'}
							_hover={{ bg: hasPrompt ? '#6a3daa' : '#e8e8ea' }}
							rightIcon={<Icon as={FiArrowUp} boxSize={4} />}
							onClick={handleGenerate}
						>
							{__('Generate', 'everest-forms')}
						</Button>
					</Flex>
				</Box>

				{/* Char counter */}
				<Flex justify="flex-end" mb="16px">
					<Text fontSize="12px" color="#b0b0b8" margin="0">{prompt.length}/{MAX_CHARS}</Text>
				</Flex>

				{/* Inspiration */}
				<Box>
					<Flex justify="space-between" align="center" mb="12px">
						<Heading as="h3" fontSize="20px" fontWeight="700" color="#0f0f1a" margin="0">
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
								bg="white" borderRadius="12px" border="1px solid #e8e8e8"
								p="24px" cursor="pointer"
								transition="border-color 0.2s ease"
								_hover={{ borderColor: '#b89ee0' }}
								onClick={() => setPrompt(card.prompt)}
							>
								<Box
									bg="#ede8ff" borderRadius="10px" p="10px"
									display="inline-flex" alignItems="center" justifyContent="center" mb="16px"
								>
									<Icon as={card.icon} boxSize={5} color="#7545BB" />
								</Box>
								<Heading as="h4" fontSize="15px" fontWeight="700" color="#0f0f1a" margin="0 0 8px" lineHeight="1.4">
									{card.title}
								</Heading>
								<Text fontSize="13px" color="#777" margin="0" lineHeight="1.6">
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
