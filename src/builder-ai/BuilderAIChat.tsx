import React, { useEffect, useRef, useState } from 'react';
import { LuSparkles, LuX, LuSend } from 'react-icons/lu';

// ── Types ─────────────────────────────────────────────────────────────────────

interface Message {
	role: 'user' | 'assistant';
	text: string;
	loading?: boolean;
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
}
const cfg: BuilderAIConfig = ( window as any ).evfBuilderAI || {};

// Edit the current builder form via the ThemeGrill AI Cloud (Python) gateway.
// Reuses the evf_ai_update_form action: the chat instruction is sent as the
// refine prompt along with the current form context; the gateway returns the
// updated form which is rebuilt in place server-side.
const editFormViaAi = async (
	instruction: string,
): Promise<{ ok: boolean; message: string }> => {
	if ( ! cfg.ajaxUrl || ! cfg.nonce || ! cfg.formId ) {
		return { ok: false, message: 'AI assistant is unavailable on this screen.' };
	}

	const body = new URLSearchParams();
	body.append( 'action', 'evf_ai_update_form' );
	body.append( 'nonce', cfg.nonce );
	body.append( 'form_id', String( cfg.formId ) );
	// Gateway requires a non-empty original prompt — use the form title as context.
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
				ok: true,
				message: "Done — I've updated your form. Refreshing the canvas…",
			};
		}
		return {
			ok: false,
			message: json?.data?.message || 'Sorry, I could not update the form. Please try again.',
		};
	} catch {
		return { ok: false, message: 'Could not reach the AI service. Please try again.' };
	}
};

// ── Component ─────────────────────────────────────────────────────────────────

const BuilderAIChat: React.FC = () => {
	const [open, setOpen]         = useState(false);
	const [input, setInput]       = useState('');
	const [messages, setMessages] = useState<Message[]>([
		{ role: 'assistant', text: GREETING },
	]);
	const [loading, setLoading]   = useState(false);
	const messagesEndRef = useRef<HTMLDivElement>(null);
	const inputRef       = useRef<HTMLTextAreaElement>(null);

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
		setMessages(prev => {
			const copy = [...prev];
			const last = copy[copy.length - 1];
			if (last?.loading) copy[copy.length - 1] = { role: 'assistant', text: result.message };
			return copy;
		});
		setLoading(false);

		// On success the form was rebuilt server-side. Refresh the builder canvas +
		// options in place (no full page reload) via the hook exposed by
		// form-builder.js; fall back to a reload if it isn't available.
		if (result.ok) {
			const w = window as any;
			if (typeof w.evfReloadBuilderFields === 'function' && cfg.formId && cfg.nonce) {
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

	// ── Render ──────────────────────────────────────────────────────────────

	return (
		<>
			{/* ── Floating trigger button ──
			     Customizer sits at bottom:65 / right:22 / 55×55px.
			     We place this button 8px above it: bottom = 65+55+8 = 128px.
			     Match width/height (55px) and right-alignment (22px). ── */}
			{!open && (
				<button
					onClick={() => setOpen(true)}
					title="AI Form Assistant"
					style={{
						position: 'fixed',
						bottom: 128,
						right: 22,
						width: 55,
						height: 55,
						borderRadius: '50%',
						background: 'linear-gradient(135deg,#7545BB 0%,#9660db 100%)',
						border: 'none',
						cursor: 'pointer',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						boxShadow: '0 4px 16px rgba(117,69,187,.45)',
						zIndex: 9999,
						transition: 'transform .2s,box-shadow .2s',
					}}
					onMouseEnter={e => {
						(e.currentTarget as HTMLButtonElement).style.transform = 'scale(1.08)';
						(e.currentTarget as HTMLButtonElement).style.boxShadow = '0 6px 22px rgba(117,69,187,.55)';
					}}
					onMouseLeave={e => {
						(e.currentTarget as HTMLButtonElement).style.transform = 'scale(1)';
						(e.currentTarget as HTMLButtonElement).style.boxShadow = '0 4px 16px rgba(117,69,187,.45)';
					}}
				>
					<LuSparkles size={24} color="white" />
				</button>
			)}

			{/* ── Chat panel ── */}
			{open && (
				<div
					style={{
						position: 'fixed',
						bottom: 128,
						right: 22,
						width: 440,
						height: 640,
						borderRadius: 16,
						background: '#fff',
						boxShadow: '0 8px 40px rgba(0,0,0,.18)',
						border: '1px solid #e2e8f0',
						display: 'flex',
						flexDirection: 'column',
						overflow: 'hidden',
						zIndex: 9999,
						transition: 'height .25s ease',
					}}
				>
					{/* Header */}
					<div
						style={{
							display: 'flex',
							alignItems: 'center',
							gap: 10,
							padding: '0 16px',
							height: 56,
							background: 'linear-gradient(135deg,#7545BB 0%,#9660db 100%)',
							flexShrink: 0,
						}}
					>
						<div
							style={{
								width: 30,
								height: 30,
								borderRadius: '50%',
								background: 'rgba(255,255,255,.15)',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
								flexShrink: 0,
							}}
						>
							<LuSparkles size={15} color="white" />
						</div>
						<div style={{ flex: 1, minWidth: 0 }}>
							<div style={{ fontSize: 14, fontWeight: 600, color: '#fff', lineHeight: 1.2 }}>
								AI Form Assistant
							</div>
							<div style={{ fontSize: 11, color: 'rgba(255,255,255,.7)', lineHeight: 1.2 }}>
								Powered by AI
							</div>
						</div>
						{/* X closes the panel — trigger button reappears */}
						<button
							onClick={() => setOpen(false)}
							style={{
								background: 'none',
								border: 'none',
								cursor: 'pointer',
								color: 'rgba(255,255,255,.8)',
								padding: 4,
								display: 'flex',
								alignItems: 'center',
								transition: 'color .15s',
							}}
							title="Close"
							onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.color = '#fff'; }}
							onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.color = 'rgba(255,255,255,.8)'; }}
						>
							<LuX size={18} />
						</button>
					</div>

					<>
							{/* Messages */}
							<div
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
										{/* Avatar for assistant */}
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
													msg.role === 'user' ? '#7545BB' : '#f4f0fb',
												color: msg.role === 'user' ? '#fff' : '#1a1a2e',
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
												msg.text
											)}
										</div>
									</div>
								))}
								<div ref={messagesEndRef} />
							</div>

							{/* Suggestions strip */}
							<div
								style={{
									padding: '0 14px 8px',
									display: 'flex',
									gap: 6,
									overflowX: 'auto',
									flexShrink: 0,
									scrollbarWidth: 'none',
								}}
							>
								{EDIT_SUGGESTIONS.slice(0, 4).map(s => (
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
									onFocus={() => {}}
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
						</>
					)}
				</div>
			)}

			{/* Dot-bounce keyframes */}
			<style>{`
				@keyframes evf-ai-dot {
					0%,80%,100%{transform:scale(.4);opacity:.4}
					40%{transform:scale(1);opacity:1}
				}
			`}</style>
		</>
	);
};

export default BuilderAIChat;
