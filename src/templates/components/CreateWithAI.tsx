import {
	Box,
	Flex,
	HStack,
	Heading,
	Icon,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	Popover,
	PopoverArrow,
	PopoverBody,
	PopoverContent,
	PopoverTrigger,
	SimpleGrid,
	Spinner,
	Text,
	Textarea,
	VStack,
	keyframes,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { __, sprintf } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { BsStars } from 'react-icons/bs';
import {
	FiArrowLeft,
	FiArrowUp,
	FiCheck,
	FiEdit3,
	FiRefreshCw,
} from 'react-icons/fi';
import { templatesScriptData } from '../utils/global';

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

const INSPIRATION_CARDS = [
	{
		title: __('Online store checkout', 'everest-forms'),
		description: __(
			'Product selection with prices, quantity, coupon code, order total, and payment gateway.',
			'everest-forms',
		),
		prompt:
			'An online store checkout form with customer name and email, product selection as payment radio buttons with prices, quantity field, coupon code field, order subtotal display, order total display, shipping address, and a payment gateway section.',
	},
	{
		title: __('Subscription sign-up', 'everest-forms'),
		description: __(
			'Subscription plan selection, billing details, and payment gateway for a recurring membership.',
			'everest-forms',
		),
		prompt:
			'A subscription membership sign-up form with first name, last name, email, phone number, subscription plan selection with monthly and annual pricing options, billing address, and payment gateway fields.',
	},
	{
		title: __('Event ticket purchase', 'everest-forms'),
		description: __(
			'Ticket type with pricing, quantity, coupon code, total, and payment gateway.',
			'everest-forms',
		),
		prompt:
			'An event ticket purchase form with attendee name and email, ticket type as payment radio buttons with prices (general admission, VIP, student), ticket quantity field, coupon code field, order subtotal and total display, and payment gateway fields.',
	},
	{
		title: __('Freelance contract & NDA', 'everest-forms'),
		description: __(
			'Client details, project scope, agreed rate, contract terms, and digital signature.',
			'everest-forms',
		),
		prompt:
			'A freelance contract and NDA form with client name, company, email, project title text field, project scope textarea, deliverables textarea, agreed rate number field, project start date, contract terms and conditions textarea, and a digital signature field for client approval.',
	},
	{
		title: __('Health & wellness check-in', 'everest-forms'),
		description: __(
			'Pain, energy, and stress levels via range sliders, symptoms checklist, and notes.',
			'everest-forms',
		),
		prompt:
			'A daily health and wellness check-in form with patient name, date, pain level range slider (0 to 10), energy level range slider (0 to 10), stress level range slider (0 to 10), sleep hours number field, symptoms checkboxes (headache, fatigue, nausea, dizziness), and a notes textarea for additional observations.',
	},
	{
		title: __('User account registration', 'everest-forms'),
		description: __(
			'Username, email, password with confirmation, profile photo, and preferences.',
			'everest-forms',
		),
		prompt:
			'A user account registration form with first name, last name, username text field, email address, password field, confirm password field, profile photo file upload, date of birth, country dropdown, and a newsletter subscription checkbox.',
	},
];

const GEN_STEPS = [
	__('Understanding your prompt', 'everest-forms'),
	__('Designing field structure', 'everest-forms'),
	__('Configuring validation & logic', 'everest-forms'),
	__('Finalizing your form', 'everest-forms'),
];

const MAX_CHARS = 500;

// Matches Style Customizer v2's own `.pv-iframe { min-height: 480px }` (style.scss) — the two
// AI-preview surfaces should feel like the same feature, not a taller one and a cramped one.
const MIN_PREVIEW_HEIGHT = 480;

// Style Customizer v2's PreviewPane forces its preview visible after a short deadline no matter
// what ("Force the reveal after a short deadline so the preview is ALWAYS shown" — PreviewPane.tsx)
// so a slow/blocked load never leaves the user staring at a skeleton forever. Mirrored here.
const PREVIEW_REVEAL_DEADLINE = 2500;

const UPGRADE_URL =
	'https://everestforms.net/upgrade/?utm_source=evf-free&utm_medium=ai-form-builder&utm_campaign=ai-rate-limit&utm_content=Upgrade+to+Pro';

const SkeletonField: React.FC<{ delay?: string }> = ({ delay = '0s' }) => (
	<Box sx={{ animation: `${fadeUp} 0.4s ease ${delay} both` }}>
		<Box
			h="12px"
			w="30%"
			mb="8px"
			borderRadius="4px"
			sx={{
				background:
					'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)',
				backgroundSize: '400px 100%',
				animation: `${shimmer} 1.6s ease-in-out infinite`,
			}}
		/>
		<Box
			h="36px"
			borderRadius="6px"
			sx={{
				background:
					'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)',
				backgroundSize: '400px 100%',
				animation: `${shimmer} 1.6s ease-in-out infinite`,
			}}
		/>
	</Box>
);

const PageShell: React.FC<{
	onBack: () => void;
	backLabel?: string;
	headerRight?: React.ReactNode;
	children: React.ReactNode;
}> = ({ onBack, backLabel, headerRight, children }) => {
	useEffect(() => {
		const prev = document.body.style.overflow;
		document.body.style.overflow = 'hidden';
		return () => {
			document.body.style.overflow = prev;
		};
	}, []);
	return (
		<Flex
			position="fixed"
			top="0"
			left="0"
			right="0"
			bottom="0"
			zIndex={100000}
			direction="column"
			overflow="hidden"
			bg="#f6f6f8"
		>
			<Box h="4px" bg="#7545BB" flexShrink={0} />
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
						w="32px"
						h="32px"
						display="inline-flex"
						alignItems="center"
						justifyContent="center"
						borderRadius="8px"
						color="#383838"
						bg="transparent"
						border="none"
						cursor="pointer"
						onClick={onBack}
						_hover={{ bg: '#f8fafc' }}
						transition="background 0.2s"
					>
						<Icon as={FiArrowLeft} boxSize="4" />
					</Box>
					<Heading
						as="h1"
						fontSize="16px"
						fontWeight="500"
						color="#0e0e0e"
						m="0"
					>
						{backLabel || __('Create with AI', 'everest-forms')}
					</Heading>
				</HStack>
				{headerRight}
			</Flex>
			<Box flex={1} overflowY="auto" display="flex" flexDirection="column">
				{children}
			</Box>
		</Flex>
	);
};

interface CreateWithAIProps {
	onBack: () => void;
	initialFormId?: number;
	initialTitle?: string;
}

interface ChatMessage {
	role: 'user' | 'assistant';
	text: string;
	loading?: boolean;
	error?: boolean;
	notice?: boolean;
	noticeUrl?: string;
}

const { homeUrl, ajaxUrl, aiNonce } = templatesScriptData;

/** Normalize a same-site URL to the CURRENT window's exact origin (protocol + host + port),
 *  keeping only the path/query — mirrors src/style-customizer-v2/PreviewPane.tsx's
 *  `previewSrc`. Without this, a proxy/alias/scheme mismatch between the admin page's
 *  actual origin and what home_url() computes server-side can make the iframe genuinely
 *  cross-origin (or trigger a mixed-content block), rendering it blank. */
const sameOriginUrl = (raw: string): string => {
	try {
		const url = new URL(raw, window.location.href);
		url.protocol = window.location.protocol;
		url.host = window.location.host;
		return url.href;
	} catch (e) {
		return raw;
	}
};

const callAi = async (
	action: string,
	data: Record<string, string | number> = {},
): Promise<any> => {
	const body = new URLSearchParams();
	body.append('action', action);
	body.append('nonce', aiNonce);
	Object.entries(data).forEach(([key, value]) =>
		body.append(key, String(value)),
	);

	const response = await fetch(ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: body.toString(),
	});

	return response.json();
};

const CreateWithAI: React.FC<CreateWithAIProps> = ({
	onBack,
	initialFormId,
	initialTitle,
}) => {
	const toast = useToast();
	const [prompt, setPrompt] = useState('');
	const [isRateLimited, setIsRateLimited] = useState(false);
	const [genState, setGenState] = useState<'idle' | 'generating' | 'generated'>(
		'idle',
	);
	const [genStep, setGenStep] = useState(-1);
	const [hint, setHint] = useState({ show: false, x: 0, y: 0 });
	const [isRegenerating, setIsRegenerating] = useState(false);
	const [isCreatingForm, setIsCreatingForm] = useState(false);
	// Real front-end preview (iframe of ?form_id=&evf_preview=true) — bumping the key forces a
	// fresh remount/reload after every successful generate/refine, since the src string itself
	// doesn't change. See render of the "generated" screen below for the iframe + overlay.
	const [previewReloadKey, setPreviewReloadKey] = useState(0);
	const [isPreviewLoading, setIsPreviewLoading] = useState(false);
	// Auto-sized to the iframe's actual content height (see onLoad below) so the WHOLE
	// form is visible and the surrounding page scrolls normally — no inner iframe
	// scrollbar, which is easy to miss/awkward to use nested inside the admin page.
	const [previewHeight, setPreviewHeight] = useState(MIN_PREVIEW_HEIGHT);
	const previewResizeObserverRef = React.useRef<ResizeObserver | null>(null);
	const [formId, setFormId] = useState(0);
	const [formTitle, setFormTitle] = useState(initialTitle || '');
	const [editUrl, setEditUrl] = useState('');
	const [refinePrompt, setRefinePrompt] = useState('');
	const [messages, setMessages] = useState<ChatMessage[]>([]);
	// "New Prompt" while a generated-but-never-activated draft exists would otherwise abandon
	// it silently (see EVF_AI_Form_Builder::create_form — every Generate is a real draft post).
	// This confirms first and offers an actual discard, not just a warning.
	const {
		isOpen: isDiscardOpen,
		onOpen: openDiscardModal,
		onClose: closeDiscardModal,
	} = useDisclosure();
	const [isDiscarding, setIsDiscarding] = useState(false);
	// True only for a form created by THIS generate flow — never for one loaded via
	// initialFormId (an existing/duplicated form the user picked, not a throwaway draft).
	// Only a fresh draft is ever offered for discard.
	const [isFreshDraft, setIsFreshDraft] = useState(false);
	const previewHintTimer = React.useRef<ReturnType<typeof setTimeout> | null>(
		null,
	);
	const aiResponseRef = React.useRef<any>(null);
	const promptInputRef = React.useRef<HTMLTextAreaElement | null>(null);

	useEffect(() => {
		return () => previewResizeObserverRef.current?.disconnect();
	}, []);

	// Never leave the user staring at the skeleton forever if the iframe's `load` event is slow
	// or never fires (a security/caching plugin blocking the preview route, a very slow theme) —
	// mirrors PreviewPane.tsx's identical "force the reveal after a short deadline" safety net.
	useEffect(() => {
		if (!isPreviewLoading) return;
		const t = setTimeout(
			() => setIsPreviewLoading(false),
			PREVIEW_REVEAL_DEADLINE,
		);
		return () => clearTimeout(t);
	}, [isPreviewLoading, previewReloadKey]);

	useEffect(() => {
		if (genState !== 'idle') return;
		const t = setTimeout(() => promptInputRef.current?.focus(), 50);
		return () => clearTimeout(t);
	}, [genState]);

	useEffect(() => {
		if (typeof initialFormId !== 'number' || initialFormId <= 0) return;
		setFormId(initialFormId);
		setPrompt(initialTitle || __('this form', 'everest-forms'));
		setMessages([
			{
				role: 'assistant',
				text: initialTitle
					? /* translators: %s: template name. */
						sprintf(
							__(
								'Loaded the “%s” template. Tell me what to change — add fields, reword labels, or anything else.',
								'everest-forms',
							),
							initialTitle,
						)
					: __('Loaded your form. Tell me what to change.', 'everest-forms'),
			},
		]);
		setGenState('generated');
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [initialFormId]);

	const handleUseThisForm = async () => {
		if (isCreatingForm || !formId) return;
		setIsCreatingForm(true);
		try {
			const res = await callAi('evf_ai_activate_form', { form_id: formId });

			if (res?.success && res.data?.edit_url) {
				window.location.href = res.data.edit_url;
			} else if (editUrl) {
				window.location.href = editUrl;
			} else {
				throw new Error(res?.data?.message || 'Unexpected response');
			}
		} catch (e: any) {
			setIsCreatingForm(false);
			toast({
				title: __('Error', 'everest-forms'),
				description:
					e?.message ||
					__('Could not open the form. Please try again.', 'everest-forms'),
				status: 'error',
				position: 'bottom',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		}
	};

	const resetToNewPrompt = () => {
		setGenState('idle');
		setFormId(0);
		setIsFreshDraft(false);
		setFormTitle('');
		setEditUrl('');
		setMessages([]);
		setPrompt('');
		setRefinePrompt('');
	};

	// "New Prompt" back button — confirm first when it would silently abandon a fresh,
	// never-activated draft; otherwise there's nothing to lose, so just go.
	const handleBackToNewPrompt = () => {
		if (isFreshDraft && formId) {
			openDiscardModal();
		} else {
			resetToNewPrompt();
		}
	};

	const handleKeepAsDraft = () => {
		closeDiscardModal();
		resetToNewPrompt();
	};

	const handleDiscardConfirm = async () => {
		if (!formId || isDiscarding) return;
		setIsDiscarding(true);
		try {
			await callAi('evf_ai_discard_form', { form_id: formId });
		} catch (e) {
			// Best-effort — the draft is abandoned either way once we navigate on, so don't
			// block the user over a failed trash call.
		} finally {
			setIsDiscarding(false);
			closeDiscardModal();
			resetToNewPrompt();
		}
	};

	const resolveLoading = (
		text: string,
		isError = false,
		isNotice = false,
		noticeUrl = '',
	) =>
		setMessages((m) => {
			const next = [...m];
			for (let i = next.length - 1; i >= 0; i--) {
				if (next[i].role === 'assistant' && next[i].loading) {
					next[i] = {
						role: 'assistant',
						text,
						error: isError,
						notice: isNotice,
						noticeUrl,
					};
					break;
				}
			}
			return next;
		});

	const handleUpdate = async (refinePromptText: string = '') => {
		if (isRegenerating || !formId || !prompt.trim()) return;
		const refine = (refinePromptText || '').trim();
		const userText = refine || __('Regenerate the form', 'everest-forms');

		setMessages((m) => [
			...m,
			{ role: 'user', text: userText },
			{ role: 'assistant', text: '', loading: true },
		]);
		setRefinePrompt('');
		setIsRegenerating(true);
		try {
			const res = await callAi('evf_ai_update_form', {
				form_id: formId,
				prompt,
				refine_prompt: refine,
			});
			if (res?.success) {
				if (res.data?.form_title) setFormTitle(res.data.form_title);
				const notice = res.data?.notice || '';
				const noticeUrl = res.data?.notice_url || '';
				const doneText = refine
					? __(
							"Done — I've updated your form. Check the preview on the right.",
							'everest-forms',
						)
					: __("Here's a fresh version of your form.", 'everest-forms');

				if (notice && !messages.some((m) => m.notice && m.text === notice)) {
					setMessages((m) => {
						const next = [...m];
						for (let i = next.length - 1; i >= 0; i--) {
							if (next[i].role === 'assistant' && next[i].loading) {
								next[i] = { role: 'assistant', text: doneText };
								break;
							}
						}
						return [
							...next,
							{ role: 'assistant', text: notice, notice: true, noticeUrl },
						];
					});
				} else {
					resolveLoading(doneText, false, false, '');
				}
				setIsPreviewLoading(true);
				setPreviewReloadKey((k) => k + 1);
			} else {
				if (res?.data?.code === 'daily_limit_reached') {
					setIsRateLimited(true);
				}
				throw new Error(
					res?.data?.message ||
						__('Could not update the form. Please try again.', 'everest-forms'),
				);
			}
		} catch (e: any) {
			const message =
				e?.message ||
				__('Could not update the form. Please try again.', 'everest-forms');
			resolveLoading(message, true);
		} finally {
			setIsRegenerating(false);
		}
	};

	const handleRegenerate = () => handleUpdate();

	const handleFieldClick = (e: React.MouseEvent) => {
		const { clientX, clientY } = e;
		setHint({ show: true, x: clientX, y: clientY });
		if (previewHintTimer.current) clearTimeout(previewHintTimer.current);
		previewHintTimer.current = setTimeout(
			() => setHint((h) => ({ ...h, show: false })),
			2600,
		);
	};

	const hasPrompt = prompt.trim().length > 0;

	useEffect(() => {
		if (genState !== 'generating') return;
		setGenStep(-1);
		aiResponseRef.current = null;

		let cancelled = false;
		let intervalId: ReturnType<typeof setInterval> | null = null;

		const showError = (message: string, isRateLimit = false) => {
			if (cancelled) return;
			if (intervalId) clearInterval(intervalId);
			if (isRateLimit) setIsRateLimited(true);
			setGenState('idle');
			toast({
				title: isRateLimit
					? __('Daily limit reached', 'everest-forms')
					: __('AI generation failed', 'everest-forms'),
				description: isRateLimit ? (
					<Box>
						<Text mb={1}>{message}</Text>
						<Box
							as="a"
							href={UPGRADE_URL}
							target="_blank"
							rel="noopener noreferrer"
							fontWeight="600"
							textDecoration="underline"
							_hover={{ opacity: 0.8 }}
						>
							{__('Upgrade to Pro →', 'everest-forms')}
						</Box>
					</Box>
				) : (
					message
				),
				status: 'error',
				position: 'bottom',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		};

		callAi('evf_ai_generate_form', { prompt })
			.then((res: any) => {
				if (res?.success && res.data?.form_id) {
					aiResponseRef.current = {
						ok: true,
						formId: res.data.form_id,
						formTitle: res.data.form_title || '',
						editUrl: res.data.edit_url || '',
						notice: res.data.notice || '',
						noticeUrl: res.data.notice_url || '',
					};
				} else {
					showError(
						res?.data?.message ||
							__('Something went wrong. Please try again.', 'everest-forms'),
						res?.data?.code === 'daily_limit_reached',
					);
				}
			})
			.catch(() => {
				showError(
					__(
						'Could not reach the AI service. Please try again.',
						'everest-forms',
					),
				);
			});

		let step = -1;
		intervalId = setInterval(() => {
			step += 1;
			setGenStep(step);
			if (step >= GEN_STEPS.length - 1) {
				if (intervalId) clearInterval(intervalId);
				const finish = () => {
					if (cancelled) return;
					const result = aiResponseRef.current;
					if (!result) {
						setTimeout(finish, 200);
						return;
					}
					if (result.ok) {
						setFormId(result.formId);
						setIsFreshDraft(true);
						setFormTitle(result.formTitle || '');
						setEditUrl(result.editUrl);
						setIsPreviewLoading(true);
						setPreviewReloadKey((k) => k + 1);
						setMessages([
							{ role: 'user', text: prompt },
							{
								role: 'assistant',
								text:
									result.notice ||
									__(
										'Here\'s your form! Review the preview on the right and click "Use This Form" when you\'re happy with it.',
										'everest-forms',
									),
								notice: !!result.notice,
								noticeUrl: result.noticeUrl || '',
							},
						]);
						setGenState('generated');
					}
				};
				setTimeout(finish, 700);
			}
		}, 950);

		return () => {
			cancelled = true;
			if (intervalId) clearInterval(intervalId);
		};
	}, [genState]);

	// Real front-end preview URL — assembled once formId is known; normalized to the
	// current origin (see sameOriginUrl) and cache-busted by remounting on previewReloadKey
	// change (the <iframe key={previewReloadKey}> below), not by varying this string.
	const previewSrc = React.useMemo(() => {
		if (!formId) return '';
		const url = new URL(homeUrl, window.location.href);
		url.searchParams.set('form_id', String(formId));
		url.searchParams.set('evf_preview', 'true');
		// Embed mode — just the form, no toolbar/device-switcher/side panel (this screen
		// already renders its own chrome around the iframe). See evf-form-preview-embed.php.
		url.searchParams.set('evf_preview_mode', 'embed');
		return sameOriginUrl(url.href);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [formId]);

	const handleGenerate = () => {
		if (!hasPrompt) return;
		setGenState('generating');
	};

	if (genState === 'generating') {
		return (
			<PageShell onBack={onBack}>
				<Flex
					flex="1"
					overflow="hidden"
					sx={{ animation: `${fadeUp} 0.3s ease` }}
				>
					<Flex
						w="480px"
						flexShrink={0}
						bg="white"
						borderRight="1px solid #e2e8f0"
						direction="column"
						align="center"
						justify="center"
						px="36px"
						py="40px"
						gap="0"
					>
						<Box
							w="60px"
							h="60px"
							borderRadius="full"
							background="linear-gradient(135deg, #9c6de8 0%, #7545BB 100%)"
							display="flex"
							alignItems="center"
							justifyContent="center"
							mb="20px"
							sx={{ animation: `${pulseGlow} 2s ease-in-out infinite` }}
						>
							<Icon as={BsStars} boxSize={6} color="white" />
						</Box>

						<VStack spacing="4px" textAlign="center" mb="24px">
							<Heading
								as="h2"
								fontSize="20px"
								fontWeight="700"
								color="#0e0e0e"
								margin="0"
								letterSpacing="-0.3px"
							>
								{__('Building your form…', 'everest-forms')}
							</Heading>
							<Text fontSize="13px" color="#9a9a9a" margin="0">
								{__('This usually takes a few seconds', 'everest-forms')}
							</Text>
						</VStack>

						<Box
							bg="#faf9ff"
							borderRadius="12px"
							border="1px solid #ede8f8"
							p="20px 24px"
							width="100%"
							mb="20px"
						>
							<VStack align="stretch" spacing="14px">
								{GEN_STEPS.map((step, i) => {
									const isDone = i < genStep;
									const isActive = i === genStep;
									const isPending = i > genStep;
									return (
										<HStack
											key={i}
											spacing="12px"
											opacity={isPending ? 0.3 : 1}
											transition="opacity 0.4s"
										>
											<Flex
												w="20px"
												h="20px"
												borderRadius="full"
												flexShrink={0}
												bg={isDone ? '#7545BB' : 'transparent'}
												border={isDone ? 'none' : '2px solid'}
												borderColor={isActive ? '#7545BB' : '#ddd'}
												align="center"
												justify="center"
												transition="all 0.35s"
											>
												{isDone && (
													<Icon
														as={FiCheck}
														color="white"
														sx={{ width: '9px', height: '9px', strokeWidth: 3 }}
													/>
												)}
												{isActive && (
													<Box
														w="6px"
														h="6px"
														borderRadius="full"
														bg="#7545BB"
														sx={{
															animation: `${dotBounce} 1.1s ease-in-out infinite`,
														}}
													/>
												)}
											</Flex>
											<Text
												flex={1}
												fontSize="13px"
												margin="0"
												fontWeight={isActive ? '600' : isDone ? '500' : '400'}
												color={
													isActive ? '#7545BB' : isDone ? '#1a1a1a' : '#c0c0cc'
												}
												transition="all 0.35s"
											>
												{step}
											</Text>
											{isActive && (
												<HStack spacing="3px">
													{[0, 1, 2].map((d) => (
														<Box
															key={d}
															w="4px"
															h="4px"
															borderRadius="full"
															bg="#7545BB"
															sx={{
																animation: `${dotBounce} 1.1s ease-in-out ${d * 0.18}s infinite`,
															}}
														/>
													))}
												</HStack>
											)}
										</HStack>
									);
								})}
							</VStack>
						</Box>
					</Flex>

					<Box flex={1} bg="#f6f6f8" overflowY="auto" p="24px">
						<Box
							bg="white"
							border="1px solid #e2e8f0"
							borderRadius="16px"
							overflow="hidden"
							sx={{ animation: `${fadeUp} 0.4s ease 0.15s both` }}
						>
							<Flex
								align="center"
								px="24px"
								py="16px"
								borderBottom="1px solid #e2e8f0"
								gap="10px"
							>
								<Box
									w="32px"
									h="32px"
									borderRadius="8px"
									sx={{
										background:
											'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)',
										backgroundSize: '400px 100%',
										animation: `${shimmer} 1.6s ease-in-out infinite`,
									}}
								/>
								<Box
									h="14px"
									w="40%"
									borderRadius="4px"
									sx={{
										background:
											'linear-gradient(90deg, #eeeeef 25%, #e4e4e8 50%, #eeeeef 75%)',
										backgroundSize: '400px 100%',
										animation: `${shimmer} 1.6s ease-in-out infinite`,
									}}
								/>
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

	if (genState === 'generated') {
		let lastAssistantIdx = -1;
		let useThisFormIdx = -1;
		messages.forEach((m, i) => {
			if (m.role === 'assistant' && !m.loading) {
				lastAssistantIdx = i;
				if (!m.error) useThisFormIdx = i;
			}
		});
		return (
			<PageShell
				onBack={handleBackToNewPrompt}
				backLabel={__('New Prompt', 'everest-forms')}
				headerRight={
					<Box
						as="button"
						display="inline-flex"
						alignItems="center"
						gap="6px"
						h="32px"
						px="14px"
						borderRadius="8px"
						border="none"
						bg={isCreatingForm ? '#9660db' : '#7545BB'}
						color="white"
						fontSize="13px"
						fontWeight="600"
						cursor={isCreatingForm ? 'not-allowed' : 'pointer'}
						opacity={isCreatingForm ? 0.85 : 1}
						onClick={handleUseThisForm}
						_hover={{ bg: isCreatingForm ? '#9660db' : '#6a3daa' }}
						transition="background 0.2s, opacity 0.2s"
					>
						{isCreatingForm && (
							<Spinner size="xs" color="white" thickness="2px" speed="0.65s" />
						)}
						{isCreatingForm
							? __('Creating…', 'everest-forms')
							: __('Use This Form', 'everest-forms')}
					</Box>
				}
			>
				<Modal
					isCentered
					isOpen={isDiscardOpen}
					onClose={isDiscarding ? () => {} : closeDiscardModal}
					size="sm"
				>
					{/* PageShell is a position:fixed zIndex 100000 overlay. ModalContent renders inside
					    Chakra's own fixed, z-index:1400 .chakra-modal__content-container — a zIndex prop
					    on ModalContent itself only wins WITHIN that container, not against PageShell, so
					    the container itself needs overriding via containerProps (confirmed via computed
					    styles: <Modal zIndex> does NOT reach it). Matches the zIndex={200000} already
					    used on this screen's Popovers for the same PageShell-overlay reason. */}
					<ModalOverlay bg="rgba(0,0,0,0.4)" backdropFilter="blur(2px)" zIndex={200000} />
					<ModalContent
						borderRadius="12px"
						mx="16px"
						containerProps={{ zIndex: 200000 }}
					>
						<ModalHeader fontSize="16px" fontWeight="600" pb="4px">
							{__('Discard this draft?', 'everest-forms')}
						</ModalHeader>
						{!isDiscarding && <ModalCloseButton />}
						<ModalBody pb="20px">
							<Text fontSize="14px" color="#555" lineHeight="1.6" margin="0">
								{__(
									"This form hasn't been used yet — it's saved as a draft. You can keep it and find it later in All Forms, or discard it now.",
									'everest-forms',
								)}
							</Text>
						</ModalBody>
						<ModalFooter gap="8px" pt="0">
							<Box
								as="button"
								onClick={isDiscarding ? undefined : handleKeepAsDraft}
								px="14px"
								h="36px"
								borderRadius="8px"
								border="1px solid #e2e8f0"
								bg="white"
								fontSize="13px"
								fontWeight="600"
								color="#383838"
								cursor={isDiscarding ? 'not-allowed' : 'pointer'}
								opacity={isDiscarding ? 0.6 : 1}
								_hover={isDiscarding ? {} : { bg: '#f8fafc' }}
								transition="background 0.2s"
							>
								{__('Keep as draft', 'everest-forms')}
							</Box>
							<Box
								as="button"
								onClick={handleDiscardConfirm}
								display="inline-flex"
								alignItems="center"
								gap="6px"
								px="14px"
								h="36px"
								borderRadius="8px"
								border="none"
								bg="#e53e3e"
								color="white"
								fontSize="13px"
								fontWeight="600"
								cursor={isDiscarding ? 'not-allowed' : 'pointer'}
								opacity={isDiscarding ? 0.75 : 1}
								_hover={isDiscarding ? {} : { bg: '#c53030' }}
								transition="background 0.2s, opacity 0.2s"
							>
								{isDiscarding && (
									<Spinner size="xs" color="white" thickness="2px" speed="0.65s" />
								)}
								{isDiscarding
									? __('Discarding…', 'everest-forms')
									: __('Discard', 'everest-forms')}
							</Box>
						</ModalFooter>
					</ModalContent>
				</Modal>

				{hint.show && (
					<Box
						position="fixed"
						top={`${hint.y + 14}px`}
						left={`${hint.x}px`}
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
						<Text
							fontSize="12px"
							fontWeight="600"
							margin="0 0 3px"
							lineHeight="1.45"
						>
							{__('This is just a preview of your form.', 'everest-forms')}
						</Text>
						<Text
							fontSize="12px"
							color="rgba(255,255,255,0.65)"
							margin="0"
							lineHeight="1.45"
						>
							{__('Click "Use This Form" to start editing.', 'everest-forms')}
						</Text>
					</Box>
				)}

				<Flex
					flex="1"
					overflow="hidden"
					sx={{ animation: `${fadeUp} 0.35s ease` }}
				>
					<Flex
						w="480px"
						flexShrink={0}
						bg="white"
						borderRight="1px solid #e2e8f0"
						direction="column"
						overflow="hidden"
					>
						<Box flex={1} p="20px" overflowY="auto">
							{messages.map((msg, idx) => {
								if (msg.role === 'user') {
									return (
										<Flex key={idx} justify="flex-end" mb="16px">
											<Box
												bg="#7545BB"
												color="white"
												borderRadius="16px 16px 4px 16px"
												px="14px"
												py="10px"
												fontSize="14px"
												lineHeight="1.6"
												maxW="340px"
												boxShadow="0 2px 8px rgba(117,69,187,0.2)"
											>
												{msg.text}
											</Box>
										</Flex>
									);
								}

								const isLastAssistant = idx === lastAssistantIdx;
								const isUseThisFormMsg = idx === useThisFormIdx;
								return (
									<HStack key={idx} align="flex-start" spacing="10px" mb="16px">
										<Flex
											w="28px"
											h="28px"
											borderRadius="full"
											bg="rgba(117,69,187,0.1)"
											align="center"
											justify="center"
											flexShrink={0}
											mt="2px"
										>
											<Icon as={BsStars} boxSize="13px" color="#7545BB" />
										</Flex>

										<Box
											flex={1}
											bg={msg.error || msg.notice ? '#fff8f8' : '#faf9ff'}
											border={
												msg.error || msg.notice
													? '1px solid #fcd5d5'
													: '1px solid #ede8f8'
											}
											borderRadius="4px 16px 16px 16px"
											p="16px"
											boxShadow={
												msg.error || msg.notice
													? 'none'
													: '0 2px 10px rgba(117,69,187,0.06)'
											}
										>
											{msg.loading ? (
												<HStack spacing="8px">
													<Spinner
														size="xs"
														color="#7545BB"
														thickness="2px"
														speed="0.65s"
													/>
													<Text
														fontSize="14px"
														color="#7545BB"
														margin="0"
														fontWeight="500"
													>
														{__('Updating your form…', 'everest-forms')}
													</Text>
												</HStack>
											) : (
												<>
													<Text
														fontSize="14px"
														color={msg.error || msg.notice ? '#c0392b' : '#444'}
														lineHeight="1.65"
														margin={
															isUseThisFormMsg
																? '0 0 14px'
																: msg.noticeUrl
																	? '0 0 10px'
																	: '0'
														}
													>
														{msg.text}
													</Text>

													{msg.noticeUrl && (
														<Box
															as="a"
															href={msg.noticeUrl}
															target="_blank"
															rel="noopener noreferrer"
															display="inline-block"
															mb={isUseThisFormMsg ? '10px' : '0'}
															color="#7545BB"
															fontSize="12px"
															fontWeight="600"
															_hover={{ textDecoration: 'underline' }}
														>
															{__('Upgrade to Pro →', 'everest-forms')}
														</Box>
													)}

													{isUseThisFormMsg && (
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
															cursor={
																isCreatingForm ? 'not-allowed' : 'pointer'
															}
															mb="12px"
															opacity={isCreatingForm ? 0.85 : 1}
															onClick={handleUseThisForm}
															_hover={{
																bg: isCreatingForm ? '#9660db' : '#6a3daa',
															}}
															transition="background 0.2s, opacity 0.2s"
														>
															{isCreatingForm && (
																<Spinner
																	size="xs"
																	color="white"
																	thickness="2px"
																	speed="0.65s"
																/>
															)}
															{isCreatingForm
																? __('Creating…', 'everest-forms')
																: __('Use This Form', 'everest-forms')}
														</Box>
													)}

													<HStack justify="flex-end" pt="2px">
														{isRateLimited ? (
															<Popover trigger="hover" placement="top" isLazy>
																<PopoverTrigger>
																	<HStack
																		spacing="5px"
																		cursor="not-allowed"
																		opacity={0.5}
																	>
																		<Icon
																			as={FiRefreshCw}
																			boxSize="12px"
																			color="#9ca3af"
																		/>
																		<Text
																			fontSize="12px"
																			color="#9ca3af"
																			margin="0"
																			fontWeight="500"
																		>
																			{__('Redo', 'everest-forms')}
																		</Text>
																	</HStack>
																</PopoverTrigger>
																<PopoverContent
																	w="auto"
																	maxW="220px"
																	zIndex={200000}
																	_focus={{ outline: 'none' }}
																>
																	<PopoverArrow />
																	<PopoverBody p={3}>
																		<Text
																			fontSize="12px"
																			color="#555"
																			mb={2}
																			lineHeight="1.5"
																		>
																			{__(
																				"You've reached your daily free limit.",
																				'everest-forms',
																			)}
																		</Text>
																		<Box
																			as="a"
																			href={UPGRADE_URL}
																			target="_blank"
																			rel="noopener noreferrer"
																			color="#7545BB"
																			fontSize="12px"
																			fontWeight="600"
																			_hover={{ textDecoration: 'underline' }}
																		>
																			{__('Upgrade to Pro →', 'everest-forms')}
																		</Box>
																	</PopoverBody>
																</PopoverContent>
															</Popover>
														) : (
															<HStack
																spacing="5px"
																cursor={
																	isRegenerating ? 'not-allowed' : 'pointer'
																}
																opacity={isRegenerating ? 0.5 : 1}
																_hover={{
																	opacity: isRegenerating ? 0.5 : 0.65,
																}}
																onClick={
																	isRegenerating ? undefined : handleRegenerate
																}
															>
																{isRegenerating && isLastAssistant ? (
																	<Spinner
																		size="xs"
																		color="#9ca3af"
																		thickness="2px"
																		speed="0.65s"
																	/>
																) : (
																	<Icon
																		as={FiRefreshCw}
																		boxSize="12px"
																		color="#9ca3af"
																	/>
																)}
																<Text
																	fontSize="12px"
																	color="#9ca3af"
																	margin="0"
																	fontWeight="500"
																>
																	{__('Redo', 'everest-forms')}
																</Text>
															</HStack>
														)}
													</HStack>
												</>
											)}
										</Box>
									</HStack>
								);
							})}
						</Box>

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
									value={refinePrompt}
									isDisabled={isRegenerating}
									onChange={(e) =>
										setRefinePrompt(e.target.value.slice(0, MAX_CHARS))
									}
									onKeyDown={(e) => {
										if (e.key === 'Enter' && !e.shiftKey && !isRateLimited) {
											e.preventDefault();
											handleUpdate(refinePrompt);
										}
									}}
								/>
								<Flex justify="flex-end" px="12px" pb="10px">
									{isRateLimited ? (
										<Popover trigger="hover" placement="top" isLazy>
											<PopoverTrigger>
												<Box
													as="button"
													w="28px"
													h="28px"
													bg="#c9bce4"
													borderRadius="6px"
													display="inline-flex"
													alignItems="center"
													justifyContent="center"
													border="none"
													cursor="not-allowed"
													transition="background 0.2s"
												>
													<Icon as={FiArrowUp} boxSize="3.5" color="white" />
												</Box>
											</PopoverTrigger>
											<PopoverContent
												w="auto"
												maxW="220px"
												zIndex={200000}
												_focus={{ outline: 'none' }}
											>
												<PopoverArrow />
												<PopoverBody p={3}>
													<Text
														fontSize="12px"
														color="#555"
														mb={2}
														lineHeight="1.5"
													>
														{__(
															"You've reached your daily free limit.",
															'everest-forms',
														)}
													</Text>
													<Box
														as="a"
														href={UPGRADE_URL}
														target="_blank"
														rel="noopener noreferrer"
														color="#7545BB"
														fontSize="12px"
														fontWeight="600"
														_hover={{ textDecoration: 'underline' }}
													>
														{__('Upgrade to Pro →', 'everest-forms')}
													</Box>
												</PopoverBody>
											</PopoverContent>
										</Popover>
									) : (
										<Box
											as="button"
											w="28px"
											h="28px"
											bg={
												refinePrompt.trim() && !isRegenerating
													? '#7545BB'
													: '#c9bce4'
											}
											borderRadius="6px"
											display="inline-flex"
											alignItems="center"
											justifyContent="center"
											border="none"
											cursor={
												refinePrompt.trim() && !isRegenerating
													? 'pointer'
													: 'not-allowed'
											}
											_hover={{
												bg:
													refinePrompt.trim() && !isRegenerating
														? '#6a3daa'
														: '#c9bce4',
											}}
											transition="background 0.2s"
											onClick={() => handleUpdate(refinePrompt)}
										>
											{isRegenerating ? (
												<Spinner
													size="xs"
													color="white"
													thickness="2px"
													speed="0.65s"
												/>
											) : (
												<Icon as={FiArrowUp} boxSize="3.5" color="white" />
											)}
										</Box>
									)}
								</Flex>
							</Box>
						</Box>
					</Flex>

					<Box
						flex={1}
						bg="#f6f6f8"
						display="flex"
						flexDirection="column"
						overflow="hidden"
						opacity={isRegenerating ? 0.5 : 1}
						transition="opacity 0.3s"
					>
						<Box flex={1} overflowY="auto" p="24px">
							<Box
								bg="white"
								border="1px solid #e2e8f0"
								borderRadius="16px"
								overflow="hidden"
								mb="0"
								position="relative"
								transition="border-color 0.3s"
							>
								<Flex
									align="center"
									px="24px"
									py="16px"
									borderBottom="1px solid #e2e8f0"
									justify="space-between"
								>
									<HStack spacing="10px">
										<Box
											w="32px"
											h="32px"
											bg="rgba(117,69,187,0.1)"
											borderRadius="8px"
											display="flex"
											alignItems="center"
											justifyContent="center"
										>
											<Icon as={FiEdit3} boxSize="15px" color="#7545BB" />
										</Box>
										<Text
											fontSize="16px"
											fontWeight="600"
											color="#0e0e0e"
											margin="0"
										>
											{formTitle || __('AI Generated Form', 'everest-forms')}
										</Text>
									</HStack>
									{isRegenerating && (
										<HStack
											spacing="5px"
											sx={{ animation: `${fadeUp} 0.2s ease` }}
										>
											{[0, 1, 2].map((d) => (
												<Box
													key={d}
													w="5px"
													h="5px"
													borderRadius="full"
													bg="#7545BB"
													sx={{
														animation: `${dotBounce} 1.1s ease-in-out ${d * 0.2}s infinite`,
													}}
												/>
											))}
											<Text
												fontSize="11px"
												color="#7545BB"
												margin="0"
												fontWeight="500"
											>
												{__('Regenerating', 'everest-forms')}
											</Text>
										</HStack>
									)}
								</Flex>

								<Box
									position="relative"
									minH={`${MIN_PREVIEW_HEIGHT}px`}
									pointerEvents={
										isRegenerating || isCreatingForm ? 'none' : 'auto'
									}
								>
									{isPreviewLoading && (
										<Box
											position="absolute"
											inset="0"
											bg="white"
											zIndex={1}
											p="24px"
										>
											<VStack spacing="20px" align="stretch">
												{Array.from({ length: 5 }).map((_, idx) => (
													<SkeletonField key={idx} delay={`${idx * 0.1}s`} />
												))}
											</VStack>
										</Box>
									)}
									{previewSrc && (
										<iframe
											key={previewReloadKey}
											src={previewSrc}
											title={__('Form preview', 'everest-forms')}
											onLoad={(e) => {
												setIsPreviewLoading(false);
												previewResizeObserverRef.current?.disconnect();
												try {
													const doc = (e.target as HTMLIFrameElement)
														.contentDocument;
													if (!doc) return;
													const measure = () =>
														setPreviewHeight(
															Math.max(
																MIN_PREVIEW_HEIGHT,
																doc.documentElement.scrollHeight,
															),
														);
													measure();
													// Catch late layout shifts (web fonts, images,
													// conditional-logic field toggles) — same-origin,
													// so this is safe DOM access, not a postMessage bridge.
													const ro = new ResizeObserver(measure);
													ro.observe(doc.documentElement);
													previewResizeObserverRef.current = ro;
												} catch (err) {
													// Cross-origin or blocked — keep the default height.
												}
											}}
											style={{
												display: 'block',
												width: '100%',
												height: `${previewHeight}px`,
												border: 'none',
											}}
										/>
									)}
									{/* Real front-end render — deliberately "look, don't touch": this
									    overlay blocks interaction with the iframe's actual form inputs
									    and shows the same "just a preview" tooltip clicking always did. */}
									<Box
										position="absolute"
										inset="0"
										onClick={handleFieldClick}
										cursor="default"
									/>
								</Box>
							</Box>
						</Box>
					</Box>
				</Flex>
			</PageShell>
		);
	}

	return (
		<PageShell onBack={onBack}>
			<Flex
				flex="1"
				direction="column"
				align="center"
				justify="center"
				px="24px"
				py="16px"
			>
				<Box width="100%" maxW="896px">
					<VStack spacing="8px" mb="14px" textAlign="center">
						<Heading
							as="h1"
							fontSize={{ base: '24px', md: '32px' }}
							fontWeight="600"
							color="#0e0e0e"
							lineHeight="1.15"
							m="0"
							letterSpacing="-0.02em"
						>
							{__('What should we build today?', 'everest-forms')}
						</Heading>
						<Text fontSize="15px" color="#6b6b6b" m="0" lineHeight="1.55">
							{__(
								"Describe your form in your own words. We'll handle the fields, logic and layout — in seconds.",
								'everest-forms',
							)}
						</Text>
					</VStack>

					<Box
						borderRadius="16px"
						border="1px solid #e2e8f0"
						bg="white"
						boxShadow="0 1px 2px rgba(15,15,15,0.04)"
						mb="8px"
					>
						<Flex align="flex-start" gap="12px" px="20px" pt="20px">
							<Icon
								as={BsStars}
								boxSize="5"
								color="#c5c0d0"
								mt="2px"
								flexShrink={0}
							/>
							<Textarea
								ref={promptInputRef}
								autoFocus
								value={prompt}
								onChange={(e) => setPrompt(e.target.value.slice(0, MAX_CHARS))}
								placeholder={__(
									'A feedback form to learn how onboarding felt, with a 1–5 rating and one optional comment…',
									'everest-forms',
								)}
								border="none"
								resize="none"
								fontSize="15px"
								lineHeight="1.6"
								color="#181818"
								p="0"
								minH="90px"
								bg="transparent"
								_focus={{ boxShadow: 'none', outline: 'none', border: 'none' }}
								_placeholder={{ color: '#9a9a9a' }}
							/>
						</Flex>

						<Box mx="20px" borderTop="1px solid #f1f5f9" />

						<Flex align="center" justify="flex-end" px="16px" py="12px">
							{isRateLimited ? (
								<Popover trigger="hover" placement="top" isLazy>
									<PopoverTrigger>
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
											bg="#e6e3ee"
											color="#9a9a9a"
											cursor="not-allowed"
											border="none"
											transition="background 0.2s"
										>
											<Text margin="0" color="#9a9a9a">
												{__('Generate', 'everest-forms')}
											</Text>
											<Box
												w="24px"
												h="24px"
												borderRadius="6px"
												display="inline-flex"
												alignItems="center"
												justifyContent="center"
												bg="#d5d0e0"
											>
												<Icon as={FiArrowUp} boxSize="3.5" color="#9a9a9a" />
											</Box>
										</Box>
									</PopoverTrigger>
									<PopoverContent
										w="auto"
										maxW="220px"
										zIndex={200000}
										_focus={{ outline: 'none' }}
									>
										<PopoverArrow />
										<PopoverBody p={3}>
											<Text
												fontSize="12px"
												color="#555"
												mb={2}
												lineHeight="1.5"
											>
												{__(
													"You've reached your daily free limit.",
													'everest-forms',
												)}
											</Text>
											<Box
												as="a"
												href={UPGRADE_URL}
												target="_blank"
												rel="noopener noreferrer"
												color="#7545BB"
												fontSize="12px"
												fontWeight="600"
												_hover={{ textDecoration: 'underline' }}
											>
												{__('Upgrade to Pro →', 'everest-forms')}
											</Box>
										</PopoverBody>
									</PopoverContent>
								</Popover>
							) : (
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
										<Icon
											as={FiArrowUp}
											boxSize="3.5"
											color={hasPrompt ? 'white' : '#9a9a9a'}
										/>
									</Box>
								</Box>
							)}
						</Flex>
					</Box>

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
									_hover={{
										borderColor: 'rgba(117,69,187,0.4)',
										bg: 'rgba(117,69,187,0.02)',
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
									<Text
										fontSize="12.5px"
										color="#6b6b6b"
										m="0"
										lineHeight="1.4"
									>
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
