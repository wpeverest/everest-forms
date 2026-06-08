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
import { __, sprintf } from '@wordpress/i18n';
import React, { useEffect, useRef, useState } from 'react';
import { templatesScriptData } from '../utils/global';
import { BsStars } from 'react-icons/bs';
import {
	FiArrowLeft,
	FiArrowUp,
	FiCheck,
	FiEdit3,
	FiRefreshCw,
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


const INSPIRATION_CARDS = [
	{
		title: __('Online store checkout', 'everest-forms'),
		description: __('Product selection with prices, quantity, coupon code, order total, and credit card payment.', 'everest-forms'),
		prompt: 'An online store checkout form with customer name and email, product selection as payment radio buttons with prices, quantity field, coupon code field, order subtotal display, order total display, shipping address, and a credit card payment section.',
	},
	{
		title: __('Subscription sign-up', 'everest-forms'),
		description: __('Subscription plan selection, billing details, and credit card payment for a recurring membership.', 'everest-forms'),
		prompt: 'A subscription membership sign-up form with first name, last name, email, phone number, subscription plan selection with monthly and annual pricing options, billing address, and credit card payment fields.',
	},
	{
		title: __('Event ticket purchase', 'everest-forms'),
		description: __('Ticket type with pricing, quantity, coupon code, total, and credit card payment.', 'everest-forms'),
		prompt: 'An event ticket purchase form with attendee name and email, ticket type as payment radio buttons with prices (general admission, VIP, student), ticket quantity field, coupon code field, order subtotal and total display, and credit card payment fields.',
	},
	{
		title: __('Freelance contract & NDA', 'everest-forms'),
		description: __('Client details, project scope, agreed rate, contract terms, and digital signature.', 'everest-forms'),
		prompt: 'A freelance contract and NDA form with client name, company, email, project title text field, project scope textarea, deliverables textarea, agreed rate number field, project start date, contract terms and conditions textarea, and a digital signature field for client approval.',
	},
	{
		title: __('Health & wellness check-in', 'everest-forms'),
		description: __('Pain, energy, and stress levels via range sliders, symptoms checklist, and notes.', 'everest-forms'),
		prompt: 'A daily health and wellness check-in form with patient name, date, pain level range slider (0 to 10), energy level range slider (0 to 10), stress level range slider (0 to 10), sleep hours number field, symptoms checkboxes (headache, fatigue, nausea, dizziness), and a notes textarea for additional observations.',
	},
	{
		title: __('User account registration', 'everest-forms'),
		description: __('Username, email, password with confirmation, profile photo, and preferences.', 'everest-forms'),
		prompt: 'A user account registration form with first name, last name, username text field, email address, password field, confirm password field, profile photo file upload, date of birth, country dropdown, and a newsletter subscription checkbox.',
	},
];

const GEN_STEPS = [
	__('Understanding your prompt', 'everest-forms'),
	__('Designing field structure', 'everest-forms'),
	__('Configuring validation & logic', 'everest-forms'),
	__('Finalizing your form', 'everest-forms'),
];


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
	// When opened from a template's "Edit with AI", the draft form to load
	// straight into the refine/preview (generated) state.
	initialFormId?: number;
	initialTitle?: string;
}

// A single entry in the preview-sidebar chat history.
interface ChatMessage {
	role: 'user' | 'assistant';
	text: string;
	loading?: boolean;
}

const { restURL, security, ajaxUrl, aiNonce } = templatesScriptData;

/**
 * Call a ThemeGrill AI Cloud (Python gateway) action via admin-ajax.
 * Mirrors the builder modal's $.post( evfAI.ajaxUrl, { action, nonce, ... } ).
 */
const callAi = async (
	action: string,
	data: Record<string, string | number> = {},
): Promise<any> => {
	const body = new URLSearchParams();
	body.append('action', action);
	body.append('nonce', aiNonce);
	Object.entries(data).forEach(([key, value]) => body.append(key, String(value)));

	const response = await fetch(ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: body.toString(),
	});

	return response.json();
};

const CreateWithAI: React.FC<CreateWithAIProps> = ({ onBack, initialFormId, initialTitle }) => {
	const toast = useToast();
	const [prompt, setPrompt] = useState('');
	const [genState, setGenState] = useState<'idle' | 'generating' | 'generated'>('idle');
	const [genStep, setGenStep] = useState(-1);
	const [hint, setHint] = useState({ show: false, x: 0, y: 0 });
	const [isRegenerating, setIsRegenerating] = useState(false);
	const [isCreatingForm, setIsCreatingForm] = useState(false);
	// Server-rendered builder-canvas HTML for the preview (guarantees parity with
	// the builder shown after import). Empty until the ai-preview endpoint responds.
	const [previewHTML, setPreviewHTML] = useState('');
	const [isPreviewLoading, setIsPreviewLoading] = useState(false);
	// The AI gateway creates a DRAFT form on generate; we preview it by id and
	// publish it on "Use This Form".
	const [formId, setFormId] = useState(0);
	const [formTitle, setFormTitle] = useState(initialTitle || '');
	const [editUrl, setEditUrl] = useState('');
	const [multiPartSteps, setMultiPartSteps] = useState<string[]>([]);
	const [activePartTab, setActivePartTab] = useState(0);
	// Follow-up / refine prompt shown in the preview sidebar.
	const [refinePrompt, setRefinePrompt] = useState('');
	// Bumped after an update so the preview re-fetches for the same form id.
	const [previewVersion, setPreviewVersion] = useState(0);
	// Running chat history for the current session (prompt + each regenerate/refine).
	const [messages, setMessages] = useState<ChatMessage[]>([]);
	const previewHintTimer = React.useRef<ReturnType<typeof setTimeout> | null>(null);
	const aiResponseRef = React.useRef<any>(null);
	const promptInputRef = React.useRef<HTMLTextAreaElement | null>(null);
	const canvasRef = useRef<HTMLDivElement | null>(null);
	// Tracks a pre-fetch of the preview HTML that starts as soon as the AI
	// returns a form_id (while the progress animation is still playing).
	// previewHTMLRef mirrors the previewHTML state for effect-safe reads.
	const previewHTMLRef = React.useRef('');
	const previewFetchStartedRef = React.useRef(false);

	// Show only the active part's rows when multi-part tabs are used.
	// PHP stamps data-part-id="N" (1-based) on each .evf-admin-row.
	useEffect(() => {
		if (!canvasRef.current || multiPartSteps.length === 0) return;
		const rows = canvasRef.current.querySelectorAll<HTMLElement>('.evf-admin-row[data-part-id]');
		const target = activePartTab + 1; // 1-based
		rows.forEach(row => {
			const pid = parseInt(row.dataset.partId || '1', 10);
			row.style.display = pid === target ? '' : 'none';
		});
	}, [activePartTab, previewHTML, multiPartSteps]);

	// Focus the prompt input on the "What should we build today?" screen (on mount
	// and whenever we return to the idle state).
	useEffect(() => {
		if (genState !== 'idle') return;
		const t = setTimeout(() => promptInputRef.current?.focus(), 50);
		return () => clearTimeout(t);
	}, [genState]);

	// Opened from a template's "Edit with AI": load the draft form straight into
	// the generated (preview + refine) state so the user can change it via prompts.
	useEffect(() => {
		// Only preload when a real form id is provided; otherwise stay on the
		// blank prompt screen (Create with AI).
		if (typeof initialFormId !== 'number' || initialFormId <= 0) return;
		setFormId(initialFormId);
		// The gateway's update endpoint needs a non-empty original prompt as context.
		setPrompt(initialTitle || __('this form', 'everest-forms'));
		setMessages([
			{
				role: 'assistant',
				text: initialTitle
					/* translators: %s: template name. */
					? sprintf( __( 'Loaded the “%s” template. Tell me what to change — add fields, reword labels, or anything else.', 'everest-forms' ), initialTitle )
					: __( 'Loaded your form. Tell me what to change.', 'everest-forms' ),
			},
		]);
		setGenState('generated');
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [initialFormId]);

	// Publish the AI-generated draft form and open it in the builder.
	const handleUseThisForm = async () => {
		if (isCreatingForm || ! formId) return;
		setIsCreatingForm(true);
		try {
			const res = await callAi('evf_ai_activate_form', { form_id: formId });

			if (res?.success && res.data?.edit_url) {
				window.location.href = res.data.edit_url;
			} else if (editUrl) {
				// Fallback: the draft already exists, open it directly.
				window.location.href = editUrl;
			} else {
				throw new Error(res?.data?.message || 'Unexpected response');
			}
		} catch (e: any) {
			setIsCreatingForm(false);
			toast({
				title: __('Error', 'everest-forms'),
				description: e?.message || __('Could not open the form. Please try again.', 'everest-forms'),
				status: 'error',
				position: 'bottom-right',
				duration: 5000,
				isClosable: true,
				variant: 'subtle',
			});
		}
	};

	// Regenerate / refine the current draft via the AI update endpoint, then
	// re-fetch the preview. Used by the "Regenerate" link (original prompt) and
	// the "Refine or follow up…" input (follow-up prompt).
	//
	// mode="regenerate": give the form a fresh take based on the original prompt.
	//   AI may freely add/remove/improve fields to enhance the form.
	// mode="refine": apply a specific user instruction on top of the current form.
	//   AI changes only what was asked, keeps everything else.
	// refinePromptText: extra instruction typed by user (empty string for Regenerate).
	// Gateway decides mode from this: non-empty → refine, empty → regenerate.
	// Replace the trailing loading placeholder with a final assistant reply.
	const resolveLoading = (text: string) =>
		setMessages(m => {
			const next = [...m];
			for (let i = next.length - 1; i >= 0; i--) {
				if (next[i].role === 'assistant' && next[i].loading) {
					next[i] = { role: 'assistant', text };
					break;
				}
			}
			return next;
		});

	const handleUpdate = async (refinePromptText: string = '') => {
		if (isRegenerating || ! formId || ! prompt.trim()) return;
		const refine   = (refinePromptText || '').trim();
		const userText = refine || __('Regenerate the form', 'everest-forms');

		// Append the new turn to the chat history + a loading placeholder.
		setMessages(m => [...m, { role: 'user', text: userText }, { role: 'assistant', text: '', loading: true }]);
		setRefinePrompt('');
		setIsRegenerating(true);
		try {
			const res = await callAi('evf_ai_update_form', {
				form_id:       formId,
				prompt,                  // always the original prompt
				refine_prompt: refine,   // extra instruction or '' (regenerate)
			});
			if (res?.success) {
				if (res.data?.form_title) setFormTitle(res.data.form_title);
				if (res.data?.multi_part_steps) { setMultiPartSteps(res.data.multi_part_steps); setActivePartTab(0); }
				resolveLoading(
					refine
						? __( "Done — I've updated your form. Check the preview on the right.", 'everest-forms' )
						: __( "Here's a fresh version of your form.", 'everest-forms' )
				);
				// Re-render the preview for the (same) updated draft.
				setPreviewVersion(v => v + 1);
			} else {
				throw new Error(res?.data?.message || __('Could not update the form. Please try again.', 'everest-forms'));
			}
		} catch (e: any) {
			const message = e?.message || __('Could not update the form. Please try again.', 'everest-forms');
			resolveLoading(message);
			toast({
				title: __('Update failed', 'everest-forms'),
				description: message,
				status: 'error',
				position: 'bottom-right',
				duration: 6000,
				isClosable: true,
				variant: 'subtle',
			});
		} finally {
			setIsRegenerating(false);
		}
	};

	// "Regenerate" — no extra instruction, gateway uses original prompt + current form.
	const handleRegenerate = () => handleUpdate();

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
		// Reset pre-fetch state for this generation run.
		previewHTMLRef.current = '';
		previewFetchStartedRef.current = false;
		setPreviewHTML('');

		let cancelled = false;
		let intervalId: ReturnType<typeof setInterval> | null = null;

		// Bail out of the "Building your form…" screen immediately and surface the
		// error — no waiting for the progress animation to finish.
		const showError = (message: string) => {
			if (cancelled) return;
			if (intervalId) clearInterval(intervalId);
			setGenState('idle');
			toast({
				title: __('AI generation failed', 'everest-forms'),
				description: message,
				status: 'error',
				position: 'bottom-right',
				duration: 6000,
				isClosable: true,
				variant: 'subtle',
			});
		};

		// Fire the real AI generation (ThemeGrill AI Cloud / Python gateway) via
		// admin-ajax, concurrently with the progress animation. The gateway builds
		// the form server-side and returns its draft id + field summary.
		callAi('evf_ai_generate_form', { prompt })
			.then((res: any) => {
				if (res?.success && res.data?.form_id) {
					aiResponseRef.current = {
						ok: true,
						formId: res.data.form_id,
						formTitle: res.data.form_title || '',
						editUrl: res.data.edit_url || '',
						multiPartSteps: res.data.multi_part_steps || [],
					};
					// Pre-fetch the preview while the progress animation is still
					// playing so the preview is ready (or nearly ready) the moment
					// the user lands on the generated screen.
					const fid = res.data.form_id;
					previewFetchStartedRef.current = true;
					setIsPreviewLoading(true);
					apiFetch({
						path: `${restURL}everest-forms/v1/templates/ai-preview`,
						method: 'POST',
						data: { form_id: fid },
						headers: { 'X-WP-Nonce': security },
					})
						.then((previewRes: any) => {
							if (previewRes?.success && previewRes?.data?.html) {
								previewHTMLRef.current = previewRes.data.html;
								setPreviewHTML(previewRes.data.html);
								setIsPreviewLoading(false);
							} else {
								// Non-fatal: let the generated-state useEffect retry.
								previewFetchStartedRef.current = false;
								setIsPreviewLoading(false);
							}
						})
						.catch(() => {
							previewFetchStartedRef.current = false;
							setIsPreviewLoading(false);
						});
				} else {
					showError( res?.data?.message || __('Something went wrong. Please try again.', 'everest-forms') );
				}
			})
			.catch(() => {
				showError( __('Could not reach the AI service. Please try again.', 'everest-forms') );
			});

		let step = -1;
		intervalId = setInterval(() => {
			step += 1;
			setGenStep(step);
			if (step >= GEN_STEPS.length - 1) {
				if (intervalId) clearInterval(intervalId);
				// Animation done — transition once a successful response is in.
				// (Errors are handled instantly by showError, not here.)
				const finish = () => {
					if (cancelled) return;
					const result = aiResponseRef.current;
					if (! result) {
						setTimeout(finish, 200);
						return;
					}
					if (result.ok) {
						setFormId(result.formId);
						setFormTitle(result.formTitle || '');
						setEditUrl(result.editUrl);
						setMultiPartSteps(result.multiPartSteps || []);
						setActivePartTab(0);
						// Seed the chat history with this session's first turn.
						setMessages([
							{ role: 'user', text: prompt },
							{ role: 'assistant', text: __( "Here's your form! Review the preview on the right and click \"Use This Form\" when you're happy with it.", 'everest-forms' ) },
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

	// Fetch the real builder-canvas preview HTML whenever the generated fields
	// change. Rendering the server markup (rather than a parallel React preview)
	// guarantees the preview matches the builder pixel-for-pixel, including the
	// PRO badge on locked fields.
	//
	// For the initial render (previewVersion === 0), a pre-fetch was already
	// started during the generating animation — skip the duplicate request if
	// it succeeded or is still in flight.
	useEffect(() => {
		if (genState !== 'generated' || ! formId) return;
		// Pre-fetch already returned HTML, or is still in progress — don't duplicate.
		if (previewVersion === 0 && (previewHTMLRef.current || previewFetchStartedRef.current)) return;
		let cancelled = false;
		setIsPreviewLoading(true);
		// Preview the actual AI-generated draft form (by id) so the preview is
		// byte-identical to the form that gets published on "Use This Form".
		apiFetch({
			path: `${restURL}everest-forms/v1/templates/ai-preview`,
			method: 'POST',
			data: { form_id: formId },
			headers: { 'X-WP-Nonce': security },
		})
			.then((res: any) => {
				if (!cancelled && res?.success && res?.data?.html) {
					previewHTMLRef.current = res.data.html;
					setPreviewHTML(res.data.html);
				}
			})
			.catch(() => { /* Falls back to the loading state; non-fatal. */ })
			.finally(() => { if (!cancelled) setIsPreviewLoading(false); });
		return () => { cancelled = true; };
	}, [genState, formId, previewVersion]);

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
						w="480px" flexShrink={0}
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
		// Index of the most recent assistant reply (gets the "Use This Form" button).
		let lastAssistantIdx = -1;
		messages.forEach((m, i) => { if (m.role === 'assistant') lastAssistantIdx = i; });
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
						w="480px" flexShrink={0}
						bg="white" borderRight="1px solid #e2e8f0"
						direction="column"
						overflow="hidden"
					>
						{/* Conversation area */}
						<Box flex={1} p="20px" overflowY="auto">
							{/* Conversation history for the current session */}
								{messages.map((msg, idx) => {
									if (msg.role === 'user') {
										return (
											<Flex key={idx} justify="flex-end" mb="16px">
												<Box
													bg="#7545BB" color="white"
													borderRadius="16px 16px 4px 16px"
													px="14px" py="10px"
													fontSize="14px" lineHeight="1.6"
													maxW="340px"
													boxShadow="0 2px 8px rgba(117,69,187,0.2)"
												>
													{msg.text}
												</Box>
											</Flex>
										);
									}

									const isLastAssistant = idx === lastAssistantIdx;
									return (
										<HStack key={idx} align="flex-start" spacing="10px" mb="16px">
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
												{msg.loading ? (
													<HStack spacing="8px">
														<Spinner size="xs" color="#7545BB" thickness="2px" speed="0.65s" />
														<Text fontSize="14px" color="#7545BB" margin="0" fontWeight="500">
															{__('Updating your form…', 'everest-forms')}
														</Text>
													</HStack>
												) : (
													<>
														<Text fontSize="14px" color="#444" lineHeight="1.65" margin={isLastAssistant ? '0 0 14px' : '0'}>
															{msg.text}
														</Text>

														{isLastAssistant && (
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
														)}

														<HStack justify="flex-end" pt="2px">
															<HStack
																spacing="5px"
																cursor={isRegenerating ? 'not-allowed' : 'pointer'}
																opacity={isRegenerating ? 0.5 : 1}
																_hover={{ opacity: isRegenerating ? 0.5 : 0.65 }}
																onClick={isRegenerating ? undefined : handleRegenerate}
															>
																{isRegenerating && isLastAssistant
																	? <Spinner size="xs" color="#9ca3af" thickness="2px" speed="0.65s" />
																	: <Icon as={FiRefreshCw} boxSize="12px" color="#9ca3af" />
																}
																<Text fontSize="12px" color="#9ca3af" margin="0" fontWeight="500">
																	{__('Redo', 'everest-forms')}
																</Text>
															</HStack>
														</HStack>
													</>
												)}
											</Box>
										</HStack>
									);
								})}
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
									value={refinePrompt}
									isDisabled={isRegenerating}
									onChange={(e) => setRefinePrompt(e.target.value.slice(0, MAX_CHARS))}
									onKeyDown={(e) => {
										if (e.key === 'Enter' && !e.shiftKey) {
											e.preventDefault();
											handleUpdate(refinePrompt);
										}
									}}
								/>
								<Flex justify="flex-end" px="12px" pb="10px">
									<Box
										as="button"
										w="28px" h="28px"
										bg={refinePrompt.trim() && !isRegenerating ? '#7545BB' : '#c9bce4'}
										borderRadius="6px"
										display="inline-flex"
										alignItems="center"
										justifyContent="center"
										border="none"
										cursor={refinePrompt.trim() && !isRegenerating ? 'pointer' : 'not-allowed'}
										_hover={{ bg: refinePrompt.trim() && !isRegenerating ? '#6a3daa' : '#c9bce4' }}
										transition="background 0.2s"
										onClick={() => handleUpdate(refinePrompt)}
									>
										{isRegenerating
											? <Spinner size="xs" color="white" thickness="2px" speed="0.65s" />
											: <Icon as={FiArrowUp} boxSize="3.5" color="white" />
										}
									</Box>
								</Flex>
							</Box>
						</Box>
					</Flex>

					{/* Right: form preview panel — flex column so tab bar is always pinned to bottom */}
					<Box flex={1} bg="#f6f6f8" display="flex" flexDirection="column" overflow="hidden">

					{/* Scrollable area — grows to fill, form card scrolls within it */}
					<Box flex={1} overflowY="auto" p="24px" pb={multiPartSteps.length > 0 ? '0' : '24px'}>

						{/* Form preview card — bottom radius removed when multi-part tab bar is shown */}
						<Box
							bg="white"
							border="1px solid #e2e8f0"
							borderRadius={multiPartSteps.length > 0 ? '16px 16px 0 0' : '16px'}
							borderBottom={multiPartSteps.length > 0 ? 'none' : '1px solid #e2e8f0'}
							overflow="hidden"
							mb="0"
							position="relative"
							transition="border-color 0.3s"
						>
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
										{formTitle || __('AI Generated Form', 'everest-forms')}
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
							    matches the builder pixel-for-pixel (locked Pro fields included).
							    PHP stamps data-part-id="N" (1-based) on every .evf-admin-row.
							    The <style> below hides all rows except the active part;
							    the canvasRef useEffect does the same imperatively so both
							    fire regardless of rendering order. */}
							{multiPartSteps.length > 0 && (
								<style dangerouslySetInnerHTML={{ __html: `
									.evf-ai-preview-canvas .evf-admin-row[data-part-id] { display: none !important; }
									.evf-ai-preview-canvas .evf-admin-row[data-part-id="${activePartTab + 1}"] { display: flex !important; margin-bottom: 15px !important; }
								`}} />
							)}
							<Box p="0" opacity={isRegenerating ? 0.7 : 1} transition="opacity 0.3s" pointerEvents={isRegenerating || isCreatingForm ? 'none' : 'auto'}>
								{previewHTML ? (
									<div
										ref={canvasRef}
										className="evf-ai-preview-canvas"
										onClick={(e) => handleFieldClick(e)}
										dangerouslySetInnerHTML={{ __html: previewHTML }}
									/>
								) : (
									<Box p="24px">
										<VStack spacing="20px" align="stretch">
											{Array.from({ length: 5 }).map((_, idx) => (
												<SkeletonField key={idx} delay={`${idx * 0.1}s`} />
											))}
										</VStack>
									</Box>
								)}
							</Box>
						</Box>

					</Box>{/* end scrollable inner area */}

					{/* Multi-part tab bar — natural flex child at bottom of the column,
					    always visible regardless of scroll. Pixel-perfect match to the
					    builder's .everest-forms-multi-part-tabs. */}
					{multiPartSteps.length > 0 && (
						<Box
							bg="#fafafa"
							borderTop="1px solid #d5d9e2"
							borderLeft="1px solid #d5d9e2"
							borderRight="1px solid #d5d9e2"
							borderBottom="1px solid #d5d9e2"
							borderRadius="0 0 16px 16px"
							display="flex"
							alignItems="stretch"
							h="42px"
							mx="24px"
							mb="24px"
							overflow="hidden"
							flexShrink={0}
							pointerEvents={isRegenerating || isCreatingForm ? 'none' : 'auto'}
						>
								{/* Tab list — matches .everest-forms-tabs ul */}
								<Box
									as="ul"
									display="flex"
									listStyleType="none"
									m="0"
									p="0"
									flex="1"
									h="41px"
									alignSelf="flex-start"
									overflow="hidden"
									borderBottom="1px solid #cccccc"
								>
									{multiPartSteps.map((step, idx) => (
										<Box
											as="li"
											key={idx}
											display="block"
											flexShrink={0}
											h="40px"
											bg={activePartTab === idx ? '#eeeeee' : '#f7f7f7'}
											borderRadius={idx === 0 ? '0' : idx === multiPartSteps.length - 1 ? '0 0 16px 0' : '0'}
											cursor="pointer"
											onClick={() => setActivePartTab(idx)}
										>
											<Box
												as="a"
												href="#"
												onClick={(e: React.MouseEvent) => e.preventDefault()}
												display="inline-flex"
												alignItems="center"
												h="40px"
												px="10px"
												fontSize="13px"
												fontWeight="600"
												lineHeight="1"
												color={activePartTab === idx ? '#7e3bd0' : '#555555'}
												textDecoration="none"
												_hover={{ color: activePartTab === idx ? '#7e3bd0' : '#333333', textDecoration: 'none' }}
											>
												<Text margin="0" fontSize="13px" fontWeight="600" color="inherit">
													{step}
												</Text>
											</Box>
										</Box>
									))}
								</Box>
							</Box>
						)}

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
								ref={promptInputRef}
								autoFocus
								value={prompt}
								onChange={(e) => setPrompt(e.target.value.slice(0, MAX_CHARS))}
								placeholder={__('A feedback form to learn how onboarding felt, with a 1–5 rating and one optional comment…', 'everest-forms')}
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
