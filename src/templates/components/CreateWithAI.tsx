import {
	Box,
	Flex,
	HStack,
	Heading,
	Icon,
	SimpleGrid,
	Spinner,
	Text,
	Textarea,
	VStack,
	keyframes,
	useToast,
} from '@chakra-ui/react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { templatesScriptData } from '../utils/global';
import { BsStars } from 'react-icons/bs';
import {
	FiArrowLeft,
	FiArrowUp,
	FiCheck,
	FiEdit3,
	FiRefreshCw,
	FiThumbsDown,
	FiThumbsUp,
} from 'react-icons/fi';

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

const regenSweep = keyframes`
  0%   { top: -40%; opacity: 0.9; }
  80%  { opacity: 0.9; }
  100% { top: 110%;   opacity: 0; }
`;

const INSPIRATION_CARDS = [
	{
		title: __('Customer feedback survey', 'everest-forms'),
		description: __('A 1–5 rating, NPS question, and one open comment field for product teams.', 'everest-forms'),
		prompt: 'A customer feedback survey with a 1-5 rating scale, NPS question, and one open comment field for product teams.',
	},
	{
		title: __('Appointment booking', 'everest-forms'),
		description: __('Name, contact, preferred date and time, and service type selection.', 'everest-forms'),
		prompt: 'An appointment booking form with name, contact details, preferred date and time, and service type selection.',
	},
	{
		title: __('Course registration', 'everest-forms'),
		description: __('Student details, course selection, and payment information in one flow.', 'everest-forms'),
		prompt: 'A course registration form with student details, course selection, and payment information in one flow.',
	},
	{
		title: __('Event RSVP', 'everest-forms'),
		description: __('Guest name, attendance, plus-ones, and dietary preferences.', 'everest-forms'),
		prompt: 'An event RSVP form with guest name, attendance confirmation, plus-ones count, and dietary preferences.',
	},
	{
		title: __('Job application', 'everest-forms'),
		description: __('Personal info, resume upload, work experience, and a short cover letter.', 'everest-forms'),
		prompt: 'A job application form with personal info, resume upload, work experience, and a short cover letter.',
	},
	{
		title: __('Product order form', 'everest-forms'),
		description: __('Product selection, quantity, shipping address, and payment details.', 'everest-forms'),
		prompt: 'A product order form with product selection, quantity, shipping address, and payment details.',
	},
];

const GEN_STEPS = [
	__('Understanding your prompt', 'everest-forms'),
	__('Designing field structure', 'everest-forms'),
	__('Configuring validation & logic', 'everest-forms'),
	__('Finalizing your form', 'everest-forms'),
];

// ── AI field type used by the mock response and create-from-ai endpoint ──────
interface AIField {
	type: string;
	label: string;
	required: boolean;
	placeholder?: string;
}

interface AIGenerateResponse {
	title: string;
	fields: AIField[];
}

// Field presets keyed by keyword → used by the mock to return realistic fields.
const FIELD_PRESETS: Record<string, AIField[]> = {
	feedback: [
		{ type: 'name',     label: 'Full Name',                  required: true  },
		{ type: 'email',    label: 'Email Address',              required: true  },
		{ type: 'rating',   label: 'Overall Rating',             required: false },
		{ type: 'textarea', label: 'Your Feedback',              required: false },
		{ type: 'select',   label: 'How did you hear about us?', required: false },
	],
	survey: [
		{ type: 'name',     label: 'Name',              required: true  },
		{ type: 'email',    label: 'Email',             required: true  },
		{ type: 'radio',    label: 'Satisfaction Level', required: true  },
		{ type: 'rating',   label: 'Rating',             required: false },
		{ type: 'textarea', label: 'Comments',           required: false },
	],
	appointment: [
		{ type: 'name',     label: 'Full Name',     required: true  },
		{ type: 'email',    label: 'Email',         required: true  },
		{ type: 'phone',    label: 'Phone Number',  required: true  },
		{ type: 'date',     label: 'Preferred Date', required: true  },
		{ type: 'select',   label: 'Service Type',  required: true  },
		{ type: 'textarea', label: 'Notes',          required: false },
	],
	booking: [
		{ type: 'name',     label: 'Full Name',     required: true  },
		{ type: 'email',    label: 'Email',         required: true  },
		{ type: 'phone',    label: 'Phone Number',  required: true  },
		{ type: 'date',     label: 'Check-in Date', required: true  },
		{ type: 'date',     label: 'Check-out Date', required: true  },
		{ type: 'number',   label: 'Number of Guests', required: true },
	],
	registration: [
		{ type: 'name',     label: 'Full Name',     required: true  },
		{ type: 'email',    label: 'Email Address', required: true  },
		{ type: 'phone',    label: 'Phone Number',  required: false },
		{ type: 'select',   label: 'Course / Event', required: true  },
		{ type: 'textarea', label: 'Special Requirements', required: false },
	],
	contact: [
		{ type: 'name',     label: 'Full Name',    required: true  },
		{ type: 'email',    label: 'Email',        required: true  },
		{ type: 'phone',    label: 'Phone',        required: false },
		{ type: 'text',     label: 'Subject',      required: true  },
		{ type: 'textarea', label: 'Message',      required: true  },
	],
	job: [
		{ type: 'name',     label: 'Full Name',          required: true  },
		{ type: 'email',    label: 'Email Address',      required: true  },
		{ type: 'phone',    label: 'Phone Number',       required: true  },
		{ type: 'text',     label: 'Position Applied For', required: true },
		{ type: 'textarea', label: 'Cover Letter',       required: false },
	],
	order: [
		{ type: 'name',     label: 'Full Name',       required: true  },
		{ type: 'email',    label: 'Email Address',   required: true  },
		{ type: 'phone',    label: 'Phone Number',    required: true  },
		{ type: 'select',   label: 'Product',         required: true  },
		{ type: 'number',   label: 'Quantity',        required: true  },
		{ type: 'address',  label: 'Shipping Address', required: true  },
	],
	default: [
		{ type: 'name',     label: 'Full Name',    required: true  },
		{ type: 'email',    label: 'Email Address', required: true  },
		{ type: 'textarea', label: 'Message',       required: true  },
	],
};

// Demo form used by the mock: a deliberate mix of FREE and PRO/addon fields so
// the locked-field upsell (PRO badge + locked settings) can be verified end to
// end. Types are real EVF field-type slugs — the create-from-ai / ai-preview
// endpoints map any registered type directly, and Pro types render locked.
const DEMO_FIELDS: AIField[] = [
	// ── Free fields ──────────────────────────────────────────────
	{ type: 'first-name',   label: 'Full Name',                  required: true  },
	{ type: 'email',        label: 'Email Address',              required: true  },
	{ type: 'phone',        label: 'Phone Number',               required: false },
	{ type: 'textarea',     label: 'Your Message',               required: false },
	{ type: 'select',       label: 'How did you hear about us?', required: false },
	// ── Survey fields (free in core, owned by the Survey/Polls/Quiz addon) ──
	{ type: 'rating',       label: 'Overall Rating',             required: false },
	{ type: 'yes-no',       label: 'Would you recommend us?',    required: false },
	{ type: 'likert',       label: 'Rate the following',         required: false },
	{ type: 'scale-rating', label: 'Satisfaction (1–10)',        required: false },
	// ── Pro / advanced fields → render LOCKED ────────────────────
	{ type: 'signature',                  label: 'Signature',          required: false },
	{ type: 'range-slider',               label: 'Budget Range',       required: false },
	{ type: 'color',                      label: 'Favourite Colour',   required: false },
	{ type: 'password',                   label: 'Create Password',    required: false },
	{ type: 'lookup',                     label: 'Lookup',             required: false },
	{ type: 'progress',                   label: 'Progress',           required: false },
	{ type: 'reset',                      label: 'Reset',              required: false },
	{ type: 'repeater-fields',            label: 'Repeater',           required: false },
	{ type: 'captcha',                    label: 'Captcha',            required: false },
	// ── Pro / payment fields → render LOCKED ─────────────────────
	{ type: 'payment-single',             label: 'Single Item',        required: false },
	{ type: 'payment-multiple',           label: 'Multiple Items',     required: false },
	{ type: 'payment-checkbox',           label: 'Checkbox Items',     required: false },
	{ type: 'payment-quantity',           label: 'Quantity',           required: false },
	{ type: 'payment-subtotal',           label: 'Subtotal',           required: false },
	{ type: 'payment-total',              label: 'Total',              required: false },
	{ type: 'payment-subscription-plan',  label: 'Subscription Plan',  required: false },
	{ type: 'payment-coupon',             label: 'Coupon',             required: false },
	{ type: 'credit-card',                label: 'Credit Card',        required: false },
	{ type: 'authorize-net',              label: 'Authorize.Net',      required: false },
];

/**
 * Mock call to the Python AI backend.
 * Replace the URL/logic here when the real endpoint is ready.
 *
 * For now it returns a demo form containing both free and Pro/addon fields so
 * the locked-field upsell can be verified. (FIELD_PRESETS is retained for the
 * eventual keyword-based behaviour.)
 */
const mockAIGenerateForm = (userPrompt: string): Promise<AIGenerateResponse> => {
	const fields = DEMO_FIELDS;

	// Derive a clean title from the prompt (first 60 chars, capitalized)
	const raw = userPrompt.trim().replace(/[^a-zA-Z0-9 ]/g, '').slice(0, 60);
	const title = raw.charAt(0).toUpperCase() + raw.slice(1) || 'AI Generated Form';

	// Simulate network latency (~3.5 s, matching the generation animation)
	return new Promise(resolve =>
		setTimeout(() => resolve({ title, fields }), 3500)
	);
};

const MAX_CHARS = 500;

// The form preview is rendered server-side via the templates/ai-preview REST
// endpoint, which returns the builder's own field markup (output_fields_preview).
// This guarantees the preview is pixel-identical to the builder shown after
// import — so there is intentionally no parallel React field-preview component.

const SkeletonField: React.FC<{ delay?: string }> = ({ delay = '0s' }) => (
	<Box sx={{ animation: `${fadeUp} 0.4s ease ${delay} both` }}>
		<Box h="12px" w="30%" mb="8px" borderRadius="4px" sx={{ background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)', backgroundSize: '400px 100%', animation: `${shimmer} 1.6s ease-in-out infinite` }} />
		<Box h="36px" borderRadius="6px" sx={{ background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)', backgroundSize: '400px 100%', animation: `${shimmer} 1.6s ease-in-out infinite` }} />
	</Box>
);

// ── Shared page shell ─────────────────────────────────────────────────────────

const PageShell: React.FC<{ onBack: () => void; backLabel?: string; children: React.ReactNode }> = ({
	onBack, backLabel, children,
}) => (
	<Flex direction="column" height="100vh" overflow="hidden" bg="#f6f6f8">
		{/* Purple accent line */}
		<Box h="4px" bg="#7545BB" flexShrink={0} />
		{/* Header */}
		<Flex
			as="header"
			align="center"
			h="56px"
			bg="white"
			borderBottom="1px solid #e2e8f0"
			px="24px"
			flexShrink={0}
		>
			<HStack spacing="12px" flex="1">
				<Box
					as="button"
					w="32px" h="32px"
					display="inline-flex" alignItems="center" justifyContent="center"
					borderRadius="8px"
					color="#383838" bg="transparent" border="none"
					cursor="pointer"
					onClick={onBack}
					_hover={{ bg: '#f8fafc' }}
					transition="background 0.2s"
				>
					<Icon as={FiArrowLeft} boxSize="4" />
				</Box>
				<Heading as="h1" fontSize="16px" fontWeight="500" color="#0e0e0e" m="0">
					{backLabel || __('Create with AI', 'everest-forms')}
				</Heading>
			</HStack>
		</Flex>
		{children}
	</Flex>
);

// ── Main component ────────────────────────────────────────────────────────────

interface CreateWithAIProps {
	onBack: () => void;
}

const { restURL, security } = templatesScriptData;

const CreateWithAI: React.FC<CreateWithAIProps> = ({ onBack }) => {
	const toast = useToast();
	const [prompt, setPrompt] = useState('');
	const [genState, setGenState] = useState<'idle' | 'generating' | 'generated'>('idle');
	const [genStep, setGenStep] = useState(-1);
	const [hint, setHint] = useState({ show: false, x: 0, y: 0 });
	const [isRegenerating, setIsRegenerating] = useState(false);
	const [isCreatingForm, setIsCreatingForm] = useState(false);
	const [generatedFields, setGeneratedFields] = useState<AIField[]>([]);
	const [generatedTitle, setGeneratedTitle] = useState('');
	// Server-rendered builder-canvas HTML for the preview (guarantees parity with
	// the builder shown after import). Empty until the ai-preview endpoint responds.
	const [previewHTML, setPreviewHTML] = useState('');
	const [isPreviewLoading, setIsPreviewLoading] = useState(false);
	const previewHintTimer = React.useRef<ReturnType<typeof setTimeout> | null>(null);
	const regenTimer = React.useRef<ReturnType<typeof setTimeout> | null>(null);
	const aiResponseRef = React.useRef<AIGenerateResponse | null>(null);

	// Send to EVF builder using the fields returned by the AI mock/API.
	const handleUseThisForm = async () => {
		if (isCreatingForm) return;
		setIsCreatingForm(true);
		const fields = generatedFields.length > 0 ? generatedFields : FIELD_PRESETS['default'];
		const title  = generatedTitle || prompt || __('AI Generated Form', 'everest-forms');
		try {
			const response = (await apiFetch({
				path: `${restURL}everest-forms/v1/templates/create-from-ai`,
				method: 'POST',
				data: { title, fields },
				headers: { 'X-WP-Nonce': security },
			})) as { success: boolean; data?: { id: number; redirect: string } };

			if (response.success && response.data?.redirect) {
				window.location.href = response.data.redirect;
			} else {
				throw new Error('Unexpected response');
			}
		} catch {
			setIsCreatingForm(false);
			toast({
				title: __('Error', 'everest-forms'),
				description: __('Could not create the form. Please try again.', 'everest-forms'),
				status: 'error',
				position: 'bottom-right',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		}
	};

	const handleRegenerate = () => {
		setIsRegenerating(true);
		if (regenTimer.current) clearTimeout(regenTimer.current);
		regenTimer.current = setTimeout(() => setIsRegenerating(false), 2400);
	};

	const handleFieldClick = (e: React.MouseEvent) => {
		const { clientX, clientY } = e;
		setHint({ show: true, x: clientX, y: clientY });
		if (previewHintTimer.current) clearTimeout(previewHintTimer.current);
		previewHintTimer.current = setTimeout(() => setHint(h => ({ ...h, show: false })), 2600);
	};

	const hasPrompt = prompt.trim().length > 0;

	useEffect(() => {
		if (genState !== 'generating') return;
		setGenStep(-1);
		aiResponseRef.current = null;

		// Fire the mock AI call concurrently with the progress animation.
		// Replace mockAIGenerateForm() with the real Python API URL when ready.
		mockAIGenerateForm(prompt).then(data => {
			aiResponseRef.current = data;
		}).catch(() => {
			// Fallback: use default fields if the API fails.
			aiResponseRef.current = { title: prompt, fields: FIELD_PRESETS['default'] };
		});

		let step = -1;
		const id = setInterval(() => {
			step += 1;
			setGenStep(step);
			if (step >= GEN_STEPS.length - 1) {
				clearInterval(id);
				// Wait for the AI response before transitioning, then store fields.
				const finish = () => {
					if (aiResponseRef.current) {
						setGeneratedFields(aiResponseRef.current.fields);
						setGeneratedTitle(aiResponseRef.current.title);
						setGenState('generated');
					} else {
						setTimeout(finish, 200);
					}
				};
				setTimeout(finish, 700);
			}
		}, 950);
		return () => clearInterval(id);
	}, [genState]);

	// Fetch the real builder-canvas preview HTML whenever the generated fields
	// change. Rendering the server markup (rather than a parallel React preview)
	// guarantees the preview matches the builder pixel-for-pixel, including the
	// PRO badge on locked fields.
	useEffect(() => {
		if (genState !== 'generated') return;
		const fields = generatedFields.length > 0 ? generatedFields : FIELD_PRESETS['default'];
		const title  = generatedTitle || prompt || __('AI Generated Form', 'everest-forms');
		let cancelled = false;
		setIsPreviewLoading(true);
		apiFetch({
			path: `${restURL}everest-forms/v1/templates/ai-preview`,
			method: 'POST',
			data: { title, fields },
			headers: { 'X-WP-Nonce': security },
		})
			.then((res: any) => {
				if (!cancelled && res?.success && res?.data?.html) {
					setPreviewHTML(res.data.html);
				}
			})
			.catch(() => { /* Falls back to the loading state; non-fatal. */ })
			.finally(() => { if (!cancelled) setIsPreviewLoading(false); });
		return () => { cancelled = true; };
	}, [genState, generatedFields, generatedTitle]);

	const handleGenerate = () => {
		if (!hasPrompt) return;
		setGenState('generating');
	};

	// ── Generating state ──────────────────────────────────────────────────────
	if (genState === 'generating') {
		return (
			<PageShell onBack={onBack}>
				<Flex flex="1" overflow="hidden" sx={{ animation: `${fadeUp} 0.3s ease` }}>
					{/* Left: progress panel */}
					<Flex
						w="400px" flexShrink={0}
						bg="white" borderRight="1px solid #e2e8f0"
						direction="column" align="center" justify="center"
						px="36px" py="40px" gap="0"
					>
						<Box
							w="60px" h="60px" borderRadius="full"
							background="linear-gradient(135deg, #9c6de8 0%, #7545BB 100%)"
							display="flex" alignItems="center" justifyContent="center"
							mb="20px"
							sx={{ animation: `${pulseGlow} 2s ease-in-out infinite` }}
						>
							<Icon as={BsStars} boxSize={6} color="white" />
						</Box>

						<VStack spacing="4px" textAlign="center" mb="24px">
							<Heading as="h2" fontSize="20px" fontWeight="700" color="#0e0e0e" margin="0" letterSpacing="-0.3px">
								{__('Building your form…', 'everest-forms')}
							</Heading>
							<Text fontSize="13px" color="#9a9a9a" margin="0">
								{__('This usually takes a few seconds', 'everest-forms')}
							</Text>
						</VStack>

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
												{isActive && <Box w="6px" h="6px" borderRadius="full" bg="#7545BB" sx={{ animation: `${dotBounce} 1.1s ease-in-out infinite` }} />}
											</Flex>
											<Text
												flex={1} fontSize="13px" margin="0"
												fontWeight={isActive ? '600' : isDone ? '500' : '400'}
												color={isActive ? '#7545BB' : isDone ? '#1a1a1a' : '#c0c0cc'}
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

						<Text fontSize="12px" color="#c0c0cc" margin="0" textAlign="center" noOfLines={1} isTruncated width="100%">
							"{prompt}"
						</Text>
					</Flex>

					{/* Right: skeleton preview */}
					<Box flex={1} bg="#f6f6f8" overflowY="auto" p="24px">
						<Box
							bg="white" border="1px solid #e2e8f0" borderRadius="16px" overflow="hidden"
							sx={{ animation: `${fadeUp} 0.4s ease 0.15s both` }}
						>
							<Flex align="center" px="24px" py="16px" borderBottom="1px solid #e2e8f0" gap="10px">
								<Box w="32px" h="32px" borderRadius="8px" sx={{ background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)', backgroundSize: '400px 100%', animation: `${shimmer} 1.6s ease-in-out infinite` }} />
								<Box h="14px" w="40%" borderRadius="4px" sx={{ background: 'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)', backgroundSize: '400px 100%', animation: `${shimmer} 1.6s ease-in-out infinite` }} />
							</Flex>
							<Box p="24px">
								<VStack spacing="16px" align="stretch">
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
			</PageShell>
		);
	}

	// ── Generated state ───────────────────────────────────────────────────────
	if (genState === 'generated') {
		return (
			<PageShell onBack={() => setGenState('idle')} backLabel={__('New Prompt', 'everest-forms')}>
				{/* Hint tooltip */}
				{hint.show && (
					<Box
						position="fixed"
						top={`${hint.y + 14}px`}
						left={`${hint.x}px`}
						transform="translateX(-50%)"
						bg="rgba(18,18,18,0.93)"
						color="white"
						borderRadius="8px"
						px="14px" py="10px"
						textAlign="center"
						pointerEvents="none"
						zIndex={9999}
						boxShadow="0 4px 20px rgba(0,0,0,0.2)"
						maxW="260px"
						sx={{ animation: `${fadeUp} 0.15s ease` }}
					>
						<Box position="absolute" top="-5px" left="50%" transform="translateX(-50%)" width="0" height="0" borderLeft="5px solid transparent" borderRight="5px solid transparent" borderBottom="5px solid rgba(18,18,18,0.93)" />
						<Text fontSize="12px" fontWeight="600" margin="0 0 3px" lineHeight="1.45">
							{__('This is just a preview of your form.', 'everest-forms')}
						</Text>
						<Text fontSize="12px" color="rgba(255,255,255,0.65)" margin="0" lineHeight="1.45">
							{__('Click "Use This Form" to start editing.', 'everest-forms')}
						</Text>
					</Box>
				)}

				<Flex flex="1" overflow="hidden" sx={{ animation: `${fadeUp} 0.35s ease` }}>

					{/* Left: chat conversation panel */}
					<Flex
						w="380px" flexShrink={0}
						bg="white" borderRight="1px solid #e2e8f0"
						direction="column"
						overflow="hidden"
					>
						{/* Conversation area */}
						<Box flex={1} p="20px" overflowY="auto">
							{/* User prompt bubble */}
							<Flex justify="flex-end" mb="16px">
								<Box
									bg="#7545BB" color="white"
									borderRadius="16px 16px 4px 16px"
									px="14px" py="10px"
									fontSize="14px" lineHeight="1.6"
									maxW="260px"
									boxShadow="0 2px 8px rgba(117,69,187,0.2)"
								>
									{prompt}
								</Box>
							</Flex>

							{/* AI response bubble */}
							<HStack align="flex-start" spacing="10px">
								<Flex
									w="28px" h="28px" borderRadius="full"
									bg="rgba(117,69,187,0.1)" align="center" justify="center"
									flexShrink={0} mt="2px"
								>
									<Icon as={BsStars} boxSize="13px" color="#7545BB" />
								</Flex>

								<Box
									flex={1}
									bg="#faf9ff"
									border="1px solid #ede8f8"
									borderRadius="4px 16px 16px 16px"
									p="16px"
									boxShadow="0 2px 10px rgba(117,69,187,0.06)"
								>
									<Text fontSize="14px" color="#444" lineHeight="1.65" margin="0 0 14px">
										{__("Here's your form! It includes name, email, a 5-star rating, open feedback, and a source question. Ready to use it?", 'everest-forms')}
									</Text>

									{/* Use This Form button */}
									<Box
										as="button"
										w="100%"
										display="flex"
										alignItems="center"
										justifyContent="center"
										gap="8px"
										h="36px"
										borderRadius="8px"
										bg={isCreatingForm ? '#9660db' : '#7545BB'}
										color="white"
										fontSize="13px"
										fontWeight="600"
										border="none"
										cursor={isCreatingForm ? 'not-allowed' : 'pointer'}
										mb="12px"
										opacity={isCreatingForm ? 0.85 : 1}
										onClick={handleUseThisForm}
										_hover={{ bg: isCreatingForm ? '#9660db' : '#6a3daa' }}
										transition="background 0.2s, opacity 0.2s"
									>
										{isCreatingForm && <Spinner size="xs" color="white" thickness="2px" speed="0.65s" />}
										{isCreatingForm
											? __('Creating…', 'everest-forms')
											: __('Use This Form', 'everest-forms')
										}
									</Box>

									{/* Feedback row */}
									<HStack justify="space-between" pt="2px">
										<HStack spacing="10px">
											<Icon as={FiThumbsUp}   boxSize="14px" color="#c8c8d4" cursor="pointer" _hover={{ color: '#7545BB' }} />
											<Icon as={FiThumbsDown} boxSize="14px" color="#c8c8d4" cursor="pointer" _hover={{ color: '#e05050' }} />
										</HStack>
										<HStack spacing="5px" cursor="pointer" _hover={{ opacity: 0.65 }} onClick={handleRegenerate}>
											<Icon as={FiRefreshCw} boxSize="12px" color="#9ca3af" />
											<Text fontSize="12px" color="#9ca3af" margin="0" fontWeight="500">
												{__('Regenerate', 'everest-forms')}
											</Text>
										</HStack>
									</HStack>
								</Box>
							</HStack>
						</Box>

						{/* Refine input */}
						<Box borderTop="1px solid #e2e8f0" p="16px">
							<Box
								border="1px solid #e2e8f0"
								borderRadius="12px"
								bg="white"
								overflow="hidden"
								transition="border-color 0.2s"
								_focusWithin={{ borderColor: '#7545BB' }}
							>
								<Textarea
									placeholder={__('Refine or follow up…', 'everest-forms')}
									border="none"
									fontSize="14px"
									color="#333"
									_focus={{ boxShadow: 'none' }}
									p="12px 16px"
									minHeight="56px"
									resize="none"
									_placeholder={{ color: '#c0c0cc' }}
								/>
								<Flex justify="flex-end" px="12px" pb="10px">
									<Box
										w="28px" h="28px"
										bg="#7545BB"
										borderRadius="6px"
										display="inline-flex"
										alignItems="center"
										justifyContent="center"
										cursor="pointer"
										_hover={{ bg: '#6a3daa' }}
										transition="background 0.2s"
									>
										<Icon as={FiArrowUp} boxSize="3.5" color="white" />
									</Box>
								</Flex>
							</Box>
						</Box>
					</Flex>

					{/* Right: form preview panel */}
					<Box flex={1} bg="#f6f6f8" overflowY="auto" p="24px">

						{/* Form preview card */}
						<Box
							bg="white"
							border={isRegenerating ? '1px solid rgba(117,69,187,0.4)' : '1px solid #e2e8f0'}
							borderRadius="16px"
							overflow="hidden"
							mb="16px"
							position="relative"
							transition="border-color 0.3s"
						>
							{/* Regen sweep overlay */}
							{isRegenerating && (
								<Box position="absolute" inset="0" pointerEvents="none" zIndex={5} overflow="hidden" borderRadius="16px">
									<Box
										position="absolute" left="0" right="0" h="60%"
										sx={{
											background: 'linear-gradient(to bottom, transparent 0%, rgba(117,69,187,0.05) 40%, rgba(117,69,187,0.09) 50%, rgba(117,69,187,0.05) 60%, transparent 100%)',
											animation: `${regenSweep} 1.1s ease-in-out infinite`,
										}}
									/>
								</Box>
							)}

							{/* Card header */}
							<Flex align="center" px="24px" py="16px" borderBottom="1px solid #e2e8f0" justify="space-between">
								<HStack spacing="10px">
									<Box
										w="32px" h="32px"
										bg="rgba(117,69,187,0.1)"
										borderRadius="8px"
										display="flex"
										alignItems="center"
										justifyContent="center"
									>
										<Icon as={FiEdit3} boxSize="15px" color="#7545BB" />
									</Box>
									<Text fontSize="16px" fontWeight="600" color="#0e0e0e" margin="0">
										Customer Feedback Survey
									</Text>
								</HStack>
								{isRegenerating && (
									<HStack spacing="5px" sx={{ animation: `${fadeUp} 0.2s ease` }}>
										{[0, 1, 2].map(d => (
											<Box key={d} w="5px" h="5px" borderRadius="full" bg="#7545BB"
												sx={{ animation: `${dotBounce} 1.1s ease-in-out ${d * 0.2}s infinite` }}
											/>
										))}
										<Text fontSize="11px" color="#7545BB" margin="0" fontWeight="500">
											{__('Regenerating', 'everest-forms')}
										</Text>
									</HStack>
								)}
							</Flex>

							{/* Form fields — server-rendered builder-canvas HTML so the preview
							    matches the builder pixel-for-pixel (locked Pro fields included). */}
							<Box p="24px" opacity={isRegenerating ? 0.45 : 1} transition="opacity 0.3s">
								{previewHTML ? (
									<Box
										className="evf-ai-preview-canvas"
										onClick={(e) => handleFieldClick(e)}
										dangerouslySetInnerHTML={{ __html: previewHTML }}
									/>
								) : (
									<VStack spacing="16px" align="stretch">
										{(isPreviewLoading ? Array.from({ length: 4 }) : []).map((_, idx) => (
											<Box key={idx} h="56px" bg="#eef0f4" borderRadius="6px"
												sx={{ animation: `${fadeUp} 0.3s ease` }} />
										))}
									</VStack>
								)}

								{/* Submit button */}
								<Box
									as="button"
									mt="24px"
									display="inline-flex"
									alignItems="center"
									justifyContent="center"
									px="24px"
									h="40px"
									borderRadius="8px"
									bg="#7545BB"
									color="white"
									fontSize="14px"
									fontWeight="500"
									border="none"
									cursor="pointer"
									_hover={{ bg: '#6a3daa' }}
									transition="background 0.2s"
								>
									{__('Submit', 'everest-forms')}
								</Box>
							</Box>
						</Box>

						{/* "Happy with this form?" CTA */}
						<Flex
							bg="white"
							borderRadius="12px"
							border="1px solid #e2e8f0"
							p="20px 24px"
							align="center"
							justify="space-between"
							gap="16px"
						>
							<Box>
								<Text fontSize="15px" fontWeight="600" color="#0e0e0e" margin="0 0 4px">
									{__('Happy with this form?', 'everest-forms')}
								</Text>
								<Text fontSize="13px" color="#6b7280" margin="0" lineHeight="1.5">
									{__('Open it in the builder to customize fields and settings.', 'everest-forms')}
								</Text>
							</Box>
							<Box
								as="button"
								display="inline-flex"
								alignItems="center"
								justifyContent="center"
								px="20px"
								h="40px"
								borderRadius="8px"
								bg="#7545BB"
								color="white"
								fontSize="14px"
								fontWeight="500"
								border="none"
								cursor="pointer"
								flexShrink={0}
								_hover={{ bg: '#6a3daa' }}
								transition="background 0.2s"
							>
								{__('Open in Builder', 'everest-forms')}
							</Box>
						</Flex>
					</Box>
				</Flex>
			</PageShell>
		);
	}

	// ── Idle state — pixel-perfect match to reference, no scroll ─────────────
	return (
		<PageShell onBack={onBack}>
			{/* Main content — vertically centered, no scroll */}
			<Flex
				flex="1"
				direction="column"
				align="center"
				justify="center"
				px="24px"
				py="16px"
				overflow="hidden"
			>
				<Box width="100%" maxW="896px">

					{/* Title + subtitle */}
					<VStack spacing="8px" mb="14px" textAlign="center">
						<Heading
							as="h1"
							fontSize={{ base: '32px', md: '44px' }}
							fontWeight="600"
							color="#0e0e0e"
							lineHeight="1.08"
							m="0"
							letterSpacing="-0.02em"
						>
							{__('What should we build today?', 'everest-forms')}
						</Heading>
						<Text fontSize="15px" color="#6b6b6b" m="0" lineHeight="1.55">
							{__("Describe your form in your own words. We'll handle the fields, logic and layout — in seconds.", 'everest-forms')}
						</Text>
					</VStack>

					{/* Textarea card */}
					<Box
						borderRadius="16px"
						border="1px solid #e2e8f0"
						bg="white"
						boxShadow="0 1px 2px rgba(15,15,15,0.04)"
						mb="8px"
					>
						{/* Input area with sparkles icon */}
						<Flex align="flex-start" gap="12px" px="20px" pt="20px">
							<Icon
								as={BsStars}
								boxSize="5"
								color="#c5c0d0"
								mt="2px"
								flexShrink={0}
							/>
							<Textarea
								value={prompt}
								onChange={(e) => setPrompt(e.target.value.slice(0, MAX_CHARS))}
								placeholder={__('A feedback form to learn how onboarding felt, with a 1–5 rating and one optional comment…', 'everest-forms')}
								border="none"
								resize="none"
								fontSize="15px"
								lineHeight="1.6"
								color="#181818"
								p="0"
								minH="130px"
								bg="transparent"
								_focus={{ boxShadow: 'none', outline: 'none', border: 'none' }}
								_placeholder={{ color: '#9a9a9a' }}
							/>
						</Flex>

						{/* Divider */}
						<Box mx="20px" borderTop="1px solid #f1f5f9" />

						{/* Toolbar — NO mic/media icons, just Generate */}
						<Flex align="center" justify="flex-end" px="16px" py="12px">
							<Box
								as="button"
								display="inline-flex"
								alignItems="center"
								gap="8px"
								fontSize="14px"
								fontWeight="500"
								pl="16px"
								pr="8px"
								py="8px"
								borderRadius="8px"
								bg={hasPrompt ? '#7545BB' : '#e6e3ee'}
								color={hasPrompt ? 'white' : '#9a9a9a'}
								cursor={hasPrompt ? 'pointer' : 'not-allowed'}
								border="none"
								onClick={hasPrompt ? handleGenerate : undefined}
								_hover={hasPrompt ? { bg: '#6a3daa' } : {}}
								transition="background 0.2s"
							>
								<Text margin="0" color={hasPrompt ? 'white' : '#9a9a9a'}>
									{__('Generate', 'everest-forms')}
								</Text>
								<Box
									w="24px"
									h="24px"
									borderRadius="6px"
									display="inline-flex"
									alignItems="center"
									justifyContent="center"
									bg={hasPrompt ? '#6a3daa' : '#d5d0e0'}
								>
									<Icon as={FiArrowUp} boxSize="3.5" color={hasPrompt ? 'white' : '#9a9a9a'} />
								</Box>
							</Box>
						</Flex>
					</Box>

					{/* Character count */}
					<Text
						textAlign="right"
						fontSize="11px"
						color="#9a9a9a"
						m="0"
						pr="4px"
						mb="14px"
					>
						{prompt.length}/{MAX_CHARS}
					</Text>

					{/* Inspiration section */}
					<Box>
						<Flex align="flex-end" justify="space-between" mb="10px">
							<Heading
								as="h2"
								fontSize="20px"
								fontWeight="600"
								color="#0e0e0e"
								m="0"
								letterSpacing="-0.01em"
							>
								{__('Need inspiration?', 'everest-forms')}
							</Heading>
							<Text fontSize="13px" fontWeight="500" color="#9a9a9a" m="0">
								{__('Tap a card to use as starting point', 'everest-forms')}
							</Text>
						</Flex>

						{/* 2-column grid — NO icons, just title + description */}
						<SimpleGrid columns={{ base: 1, sm: 2 }} spacing="8px">
							{INSPIRATION_CARDS.map((card) => (
								<Box
									key={card.title}
									as="button"
									textAlign="left"
									display="block"
									width="100%"
									border="1px solid rgba(226,232,240,0.8)"
									bg="white"
									px="16px"
									py="12px"
									borderRadius="8px"
									cursor="pointer"
									onClick={() => setPrompt(card.prompt)}
									transition="all 0.2s"
									_hover={{
										borderColor: 'rgba(117,69,187,0.4)',
										bg: 'rgba(117,69,187,0.02)',
										transform: 'translateY(-1px)',
									}}
								>
									<Text
										fontSize="14px"
										fontWeight="600"
										color="#0e0e0e"
										m="0 0 4px"
										lineHeight="1.4"
									>
										{card.title}
									</Text>
									<Text fontSize="12.5px" color="#6b6b6b" m="0" lineHeight="1.4">
										{card.description}
									</Text>
								</Box>
							))}
						</SimpleGrid>
					</Box>
				</Box>
			</Flex>
		</PageShell>
	);
};

export default CreateWithAI;
