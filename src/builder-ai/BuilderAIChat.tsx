import React, { useEffect, useRef, useState } from 'react';
import { LuSparkles, LuX, LuSend } from 'react-icons/lu';

// ── Types ─────────────────────────────────────────────────────────────────────

interface Message {
	role: 'user' | 'assistant';
	text: string;
	loading?: boolean;
	notice?: boolean;
	noticeUrl?: string;
	reload?: boolean;
}

// Edit-form prompt suggestions shown inside the chat panel.
const EDIT_SUGGESTIONS = [
	'Add a phone number field',
	'Make all fields required',
	'Add a date picker field',
	'Insert a file upload field',
	'Add a dropdown with Yes / No options',
	'Remove the last field',
	'Add an address section',
	'Insert a multi-line text field',
];

// Subtle intro message from the AI assistant.
const GREETING =
	"Hi! I'm your AI form assistant. Tell me how to improve this form — or pick a suggestion below.";

// Builder context (form id + nonce) localized by class-evf-admin-assets.php.
interface BuilderAIConfig {
	ajaxUrl?: string;
	nonce?: string;
	formId?: number;
	formTitle?: string;
	aiDisabled?: boolean;
}
const cfg: BuilderAIConfig = ( window as any ).evfBuilderAI || {};

// On local / development sites the AI gateway is unavailable — the assistant is
// shown but disabled (greyed trigger, opens nothing, explains why on hover).
const AI_DISABLED = !! cfg.aiDisabled;

// Edit the current builder form via the ThemeGrill AI Cloud (Python) gateway.
const editFormViaAi = async (
	instruction: string,
): Promise<{ ok: boolean; message: string; isNotice?: boolean; noticeUrl?: string; needsReload?: boolean }> => {
	if ( ! cfg.ajaxUrl || ! cfg.nonce || ! cfg.formId ) {
		return { ok: false, message: 'AI assistant is unavailable on this screen.' };
	}

	const body = new URLSearchParams();
	body.append( 'action', 'evf_ai_update_form' );
	body.append( 'nonce', cfg.nonce );
	body.append( 'form_id', String( cfg.formId ) );
	body.append( 'prompt', cfg.formTitle || 'Edit this form' );
	body.append( 'refine_prompt', instruction );

	try {
		const resp = await fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} );
		const json = await resp.json();
		if ( json?.success ) {
			return {
				ok:          true,
				message:     json?.data?.notice || "Done — I've updated your form. Refreshing the canvas…",
				isNotice:    !! json?.data?.notice,
				noticeUrl:   json?.data?.notice_url || '',
				needsReload: !! json?.data?.needs_reload,
			};
		}
		return {
			ok:        false,
			message:   json?.data?.message || 'Sorry, I could not update the form. Please try again.',
			noticeUrl: json?.data?.code === 'rate_limited'
				? 'https://everestforms.net/upgrade/?utm_source=evf-free&utm_medium=ai-chat&utm_campaign=daily-limit&utm_content=Upgrade+to+Pro'
				: '',
		};
	} catch {
		return { ok: false, message: 'Could not reach the AI service. Please try again.' };
	}
};

// ── Component ─────────────────────────────────────────────────────────────────

const BuilderAIChat: React.FC = () => {
	const [open, setOpen]             = useState(false);
	const [input, setInput]           = useState('');
	const [messages, setMessages]     = useState<Message[]>([
		{ role: 'assistant', text: GREETING },
	]);
	const [loading, setLoading]         = useState(false);
	const [buttonHovered, setButtonHovered]   = useState(false);
	const [tooltipHovered, setTooltipHovered] = useState(false);
	const [rateLimited, setRateLimited] = useState(false);
	const [upgradeUrl, setUpgradeUrl]   = useState('');
	const showTooltip = !open && (buttonHovered || tooltipHovered);
	// Read the customizer button's actual CSS bottom so we stack correctly even
	// in multi-part mode (where the customizer moves up to 62px). Falls back to
	// null when the addon is not active — AI button then sits at bottom: 22px.
	const [customizerBottom, setCustomizerBottom] = useState<number | null>(null);
	// Show the assistant only on the Builder (Fields) tab — mirror the Style
	// Customizer button, which lives inside the Fields panel and is hidden when
	// other tabs (Settings, Integrations, …) are active.
	const [onBuilderTab, setOnBuilderTab] = useState(true);
	const messagesEndRef = useRef<HTMLDivElement>(null);
	const inputRef       = useRef<HTMLTextAreaElement>(null);

	useEffect(() => {
		const read = () => {
			const el = document.querySelector<HTMLElement>('.everest-forms-designer-icon');
			if (el) {
				const v = parseInt(window.getComputedStyle(el).bottom, 10);
				setCustomizerBottom(isNaN(v) ? null : v);
			} else {
				setCustomizerBottom(null);
			}
		};
		read();
		// Re-read when builder classes change (multi-part toggle adds/removes class).
		const observer = new MutationObserver(read);
		const builder = document.getElementById('everest-forms-builder');
		if (builder) observer.observe(builder, { attributes: true, attributeFilter: ['class'] });
		return () => observer.disconnect();
	}, []);

	// Track the active builder tab. Switching tabs toggles the `active` class on
	// the Fields panel; we only render the assistant while that panel is active.
	useEffect(() => {
		const panel = document.getElementById('everest-forms-panel-fields');
		const read = () => setOnBuilderTab(panel ? panel.classList.contains('active') : true);
		read();
		if (!panel) return;
		const observer = new MutationObserver(read);
		observer.observe(panel, { attributes: true, attributeFilter: ['class'] });
		return () => observer.disconnect();
	}, []);

	// Close the chat panel when navigating away from the Builder tab.
	useEffect(() => {
		if (!onBuilderTab) setOpen(false);
	}, [onBuilderTab]);

	// Auto-scroll to latest message.
	useEffect(() => {
		if (open) messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
	}, [messages, open]);

	// Focus input when panel opens.
	useEffect(() => {
		if (open) setTimeout(() => inputRef.current?.focus(), 120);
	}, [open]);

	const sendMessage = async (text: string) => {
		if (!text.trim() || loading) return;
		const userText = text.trim();
		setInput('');

		setMessages(prev => [...prev, { role: 'user', text: userText }]);
		setLoading(true);
		setMessages(prev => [...prev, { role: 'assistant', text: '', loading: true }]);

		const result = await editFormViaAi(userText);

		// Track rate limit so the trigger button tooltip updates.
		if (!result.ok && result.noticeUrl) {
			setRateLimited(true);
			setUpgradeUrl(result.noticeUrl);
		}

		// Show the notice at most once per chat session.
		if (result.isNotice && messages.some(m => m.notice)) {
			result.isNotice = false;
			result.noticeUrl = '';
			result.message = "Done — I've updated your form. Refreshing the canvas…";
		}

		// When settings changed (redirect, email, message, etc.) set a clean done text.
		// The reload link is rendered inside the bubble; no auto-reload happens.
		if (result.ok && result.needsReload && !result.isNotice) {
			result.message = "Done — your form settings have been updated.";
		}

		setMessages(prev => {
			const copy = [...prev];
			const last = copy[copy.length - 1];
			if (!last?.loading) return copy;

			if (result.ok && result.isNotice) {
				// Edit succeeded but there's a Pro/addon notice — show "Done" first,
				// then a separate notice bubble below so the user knows the edit applied.
				copy[copy.length - 1] = {
					role: 'assistant',
					text: result.needsReload
						? "Done — your form settings have been updated."
						: "Done — I've updated your form. Refreshing the canvas…",
				};
				copy.push({
					role:      'assistant',
					text:      result.message,
					notice:    true,
					noticeUrl: result.noticeUrl || '',
					reload:    !! result.needsReload,
				});
			} else {
				copy[copy.length - 1] = {
					role:      'assistant',
					text:      result.message,
					notice:    result.isNotice || ! result.ok,
					noticeUrl: result.noticeUrl || '',
					reload:    result.ok && !! result.needsReload,
				};
			}
			return copy;
		});
		setLoading(false);

		if (result.ok) {
			const w = window as any;
			if (result.needsReload) {
				// Settings changed — don't auto-reload; the bubble shows a manual
				// "Refresh the page" link so the user can reload when ready.
			} else if (typeof w.evfReloadBuilderFields === 'function' && cfg.formId && cfg.nonce) {
				w.evfReloadBuilderFields(cfg.formId, cfg.nonce, () => {});
			} else {
				setTimeout(() => window.location.reload(), 1500);
			}
		}
	};

	const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			sendMessage(input);
		}
	};

	// Bottom offset of the trigger button.
	// When the customizer is active we sit 8px above its top edge;
	// otherwise we share the same bottom baseline (22px).
	const BTN_SIZE     = 55;
	const BTN_RIGHT    = 22;
	const BTN_BOTTOM   = customizerBottom !== null ? customizerBottom + BTN_SIZE + 8 : 22;
	// Modal sits 8px above the top edge of the trigger button.
	const MODAL_BOTTOM = BTN_BOTTOM + BTN_SIZE + 8;

	// ── Render ──────────────────────────────────────────────────────────────

	// Hidden outside the Builder (Fields) tab.
	if (!onBuilderTab) return null;

	return (
		<>
			{/* ── Floating trigger button (always rendered) ────────────────────
			     Shows sparkles when closed, X when open.
			     zIndex sits above the chat panel so it's always clickable. ── */}
			<button
				onClick={() => { if (!AI_DISABLED) setOpen(o => !o); }}
				style={{
					position: 'fixed',
					bottom: BTN_BOTTOM,
					right: BTN_RIGHT,
					width: BTN_SIZE,
					height: BTN_SIZE,
					borderRadius: '50%',
					background: AI_DISABLED
						? 'linear-gradient(135deg,#b4a8cc 0%,#c8bce0 100%)'
						: open
						? 'linear-gradient(135deg,#5c329c 0%,#7545BB 100%)'
						: 'linear-gradient(135deg,#7545BB 0%,#9660db 100%)',
					border: 'none',
					cursor: AI_DISABLED ? 'not-allowed' : 'pointer',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					boxShadow: '0 4px 16px rgba(117,69,187,.45)',
					zIndex: 10000,
					transition: 'transform .2s,box-shadow .2s,background .2s',
				}}
				onMouseEnter={e => {
					setButtonHovered(true);
					if (AI_DISABLED) return;
					(e.currentTarget as HTMLButtonElement).style.transform = 'scale(1.08)';
					(e.currentTarget as HTMLButtonElement).style.boxShadow = '0 6px 22px rgba(117,69,187,.55)';
				}}
				onMouseLeave={e => {
					setButtonHovered(false);
					(e.currentTarget as HTMLButtonElement).style.transform = 'scale(1)';
					(e.currentTarget as HTMLButtonElement).style.boxShadow = '0 4px 16px rgba(117,69,187,.45)';
				}}
			>
				{open ? <LuX size={22} color="white" /> : <LuSparkles size={24} color="white" />}
			</button>

			{/* ── Tooltip — matches tooltipster style exactly, appears above the button ── */}
			{showTooltip && (
				<div
					onMouseEnter={() => setTooltipHovered(true)}
					onMouseLeave={() => setTooltipHovered(false)}
					style={{
						position: 'fixed',
						// Sit 8px above the trigger button top edge
						bottom: BTN_BOTTOM + BTN_SIZE + 8,
						// Place right edge at button center, then translateX(50%) to center tooltip over button
						right: BTN_RIGHT + Math.round(BTN_SIZE / 2),
						transform: 'translateX(50%)',
						pointerEvents: rateLimited ? 'auto' : 'none',
						zIndex: 10001,
					}}
				>
					{/* Box — matches tooltipster-box */}
					<div style={{
						background: '#fff',
						border: '0.8px solid #e1e1e1',
						borderRadius: 3,
						padding: '16px 20px',
						fontSize: 13,
						color: '#222',
						whiteSpace: 'nowrap',
						position: 'relative',
					}}>
						{rateLimited ? (
							<>
								<div style={{ marginBottom: 8 }}>You've reached your daily free limit.</div>
								<a
									href={upgradeUrl}
									target="_blank"
									rel="noopener noreferrer"
									style={{ color: '#7545BB', fontWeight: 600, fontSize: 12, textDecoration: 'none' }}
									onMouseEnter={e => (e.currentTarget.style.textDecoration = 'underline')}
									onMouseLeave={e => (e.currentTarget.style.textDecoration = 'none')}
								>
									Upgrade to Pro →
								</a>
							</>
						) : AI_DISABLED ? (
							'Not available on local sites'
						) : (
							'AI Form Assistant'
						)}
					</div>
					{/* Down-pointing arrow centered under tooltip, pointing to button */}
					{/* Outer arrow — border colour */}
					<div style={{
						position: 'absolute',
						bottom: -8,
						left: '50%',
						marginLeft: -7,
						width: 0, height: 0,
						borderLeft: '7px solid transparent',
						borderRight: '7px solid transparent',
						borderTop: '8px solid #e1e1e1',
					}} />
					{/* Inner arrow — white fill */}
					<div style={{
						position: 'absolute',
						bottom: -6,
						left: '50%',
						marginLeft: -6,
						width: 0, height: 0,
						borderLeft: '6px solid transparent',
						borderRight: '6px solid transparent',
						borderTop: '7px solid #fff',
					}} />
				</div>
			)}

			{/* ── Chat panel ── */}
			{open && (
				<div
					style={{
						position: 'fixed',
						bottom: MODAL_BOTTOM,
						right: BTN_RIGHT,
						width: 440,
						height: 520,
						maxHeight: `calc(100vh - ${MODAL_BOTTOM + 40}px)`,
						borderRadius: 16,
						background: '#fff',
						boxShadow: '0 8px 40px rgba(0,0,0,.18)',
						border: '1px solid #e2e8f0',
						display: 'flex',
						flexDirection: 'column',
						overflow: 'hidden',
						zIndex: 9999,
					}}
				>
					{/* Header */}
					<div
						style={{
							display: 'flex',
							alignItems: 'center',
							gap: 10,
							padding: '0 16px',
							height: 52,
							background: 'linear-gradient(135deg,#7545BB 0%,#9660db 100%)',
							flexShrink: 0,
						}}
					>
						<div
							style={{
								width: 28,
								height: 28,
								borderRadius: '50%',
								background: 'rgba(255,255,255,.15)',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
								flexShrink: 0,
							}}
						>
							<LuSparkles size={14} color="white" />
						</div>
						<div style={{ flex: 1, minWidth: 0 }}>
							<div style={{ fontSize: 14, fontWeight: 600, color: '#fff', lineHeight: 1.2 }}>
								AI Form Assistant
							</div>
							<div style={{ fontSize: 11, color: 'rgba(255,255,255,.7)', lineHeight: 1.2 }}>
								Powered by AI
							</div>
						</div>
					</div>

					{/* Messages */}
					<div
						className="evf-ai-messages"
						style={{
							flex: 1,
							overflowY: 'auto',
							padding: '16px 14px',
							display: 'flex',
							flexDirection: 'column',
							gap: 10,
						}}
					>
						{messages.map((msg, i) => (
							<div
								key={i}
								style={{
									display: 'flex',
									flexDirection: msg.role === 'user' ? 'row-reverse' : 'row',
									alignItems: 'flex-end',
									gap: 8,
								}}
							>
								{msg.role === 'assistant' && (
									<div
										style={{
											width: 26,
											height: 26,
											borderRadius: '50%',
											background: 'rgba(117,69,187,.1)',
											display: 'flex',
											alignItems: 'center',
											justifyContent: 'center',
											flexShrink: 0,
										}}
									>
										<LuSparkles size={13} color="#7545BB" />
									</div>
								)}

								<div
									style={{
										maxWidth: '82%',
										padding: '9px 12px',
										borderRadius:
											msg.role === 'user'
												? '14px 14px 4px 14px'
												: '4px 14px 14px 14px',
										background:
											msg.role === 'user' ? '#7545BB' : msg.notice ? '#fff8f8' : '#f4f0fb',
										color: msg.role === 'user' ? '#fff' : msg.notice ? '#c0392b' : '#1a1a2e',
										border: msg.notice ? '1px solid #fca5a5' : 'none',
										fontSize: 13,
										lineHeight: 1.55,
										boxShadow:
											msg.role === 'user'
												? '0 2px 8px rgba(117,69,187,.2)'
												: 'none',
									}}
								>
									{msg.loading ? (
										<div style={{ display: 'flex', gap: 4, padding: '2px 0' }}>
											{[0, 1, 2].map(d => (
												<span
													key={d}
													style={{
														width: 6,
														height: 6,
														borderRadius: '50%',
														background: '#9660db',
														display: 'inline-block',
														animation: `evf-ai-dot 1.1s ease-in-out ${d * 0.18}s infinite`,
													}}
												/>
											))}
										</div>
									) : (
										<>
											{msg.text}
											{msg.reload && (
												<div style={{ marginTop: 6 }}>
													<a
														href="#"
														onClick={e => { e.preventDefault(); window.location.reload(); }}
														style={{ color: '#7545BB', fontWeight: 600, fontSize: 12, textDecoration: 'underline', cursor: 'pointer' }}
													>
														Refresh the page ↻
													</a>
												</div>
											)}
											{msg.notice && msg.noticeUrl && (
												<div style={{ marginTop: 6 }}>
													<a
														href={msg.noticeUrl}
														target="_blank"
														rel="noopener noreferrer"
														style={{ color: '#c0392b', fontWeight: 600, fontSize: 12, textDecoration: 'underline' }}
													>
														Upgrade to Pro ↗
													</a>
												</div>
											)}
										</>
									)}
								</div>
							</div>
						))}
						<div ref={messagesEndRef} />
					</div>

					{/* Suggestions strip */}
					<div
						className="evf-ai-suggestions"
						style={{
							padding: '0 14px 10px',
							display: 'flex',
							gap: 6,
							overflowX: 'auto',
							flexShrink: 0,
							scrollbarWidth: 'thin',
							scrollbarColor: '#d4c5f0 transparent',
						}}
					>
						{EDIT_SUGGESTIONS.map(s => (
							<button
								key={s}
								onClick={() => sendMessage(s)}
								style={{
									flexShrink: 0,
									padding: '5px 10px',
									borderRadius: 20,
									border: '1px solid #e2e8f0',
									background: '#faf9ff',
									color: '#7545BB',
									fontSize: 11.5,
									fontWeight: 500,
									cursor: 'pointer',
									whiteSpace: 'nowrap',
									transition: 'background .15s,border-color .15s',
								}}
								onMouseEnter={e => {
									(e.currentTarget as HTMLButtonElement).style.background = '#f0ebfa';
									(e.currentTarget as HTMLButtonElement).style.borderColor = '#b89ee0';
								}}
								onMouseLeave={e => {
									(e.currentTarget as HTMLButtonElement).style.background = '#faf9ff';
									(e.currentTarget as HTMLButtonElement).style.borderColor = '#e2e8f0';
								}}
							>
								{s}
							</button>
						))}
					</div>

					{/* Input bar */}
					<div
						style={{
							padding: '10px 14px 14px',
							borderTop: '1px solid #f1f5f9',
							flexShrink: 0,
						}}
					>
						<div
							style={{
								display: 'flex',
								alignItems: 'flex-end',
								gap: 8,
								border: '1.5px solid #e2e8f0',
								borderRadius: 12,
								padding: '8px 10px 8px 14px',
								background: '#fff',
								transition: 'border-color .2s',
							}}
						>
							<textarea
								ref={inputRef}
								value={input}
								onChange={e => setInput(e.target.value)}
								onKeyDown={handleKeyDown}
								placeholder="Describe what to change…"
								rows={1}
								style={{
									flex: 1,
									border: 'none',
									outline: 'none',
									resize: 'none',
									fontSize: 13,
									color: '#1a1a2e',
									background: 'transparent',
									lineHeight: 1.5,
									maxHeight: 80,
									overflowY: 'auto',
									fontFamily: 'inherit',
								}}
							/>
							<button
								onClick={() => sendMessage(input)}
								disabled={!input.trim() || loading}
								style={{
									width: 32,
									height: 32,
									borderRadius: 8,
									border: 'none',
									background: input.trim() && !loading ? '#7545BB' : '#e6e3ee',
									cursor: input.trim() && !loading ? 'pointer' : 'not-allowed',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
									flexShrink: 0,
									transition: 'background .2s',
								}}
							>
								<LuSend
									size={15}
									color={input.trim() && !loading ? '#fff' : '#9a9a9a'}
								/>
							</button>
						</div>
						<p style={{ fontSize: 11, color: '#9ca3af', margin: '6px 0 0', textAlign: 'center' }}>
							AI edits update your form and refresh the canvas.
						</p>
					</div>
				</div>
			)}

			{/* Dot-bounce keyframes + minimal scrollbar styles */}
			<style>{`
				@keyframes evf-ai-dot {
					0%,80%,100%{transform:scale(.4);opacity:.4}
					40%{transform:scale(1);opacity:1}
				}
				.evf-ai-messages::-webkit-scrollbar,
				.evf-ai-suggestions::-webkit-scrollbar {
					width: 4px;
					height: 4px;
				}
				.evf-ai-messages::-webkit-scrollbar-track,
				.evf-ai-suggestions::-webkit-scrollbar-track {
					background: transparent;
				}
				.evf-ai-messages::-webkit-scrollbar-thumb,
				.evf-ai-suggestions::-webkit-scrollbar-thumb {
					background: #d4c5f0;
					border-radius: 4px;
				}
				.evf-ai-messages::-webkit-scrollbar-thumb:hover,
				.evf-ai-suggestions::-webkit-scrollbar-thumb:hover {
					background: #b89ee0;
				}
			`}</style>
		</>
	);
};

export default BuilderAIChat;
