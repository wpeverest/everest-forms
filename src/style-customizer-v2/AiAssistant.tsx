/**
 * Style Customizer v2 — floating "Style with AI" launcher that turns a prompt into style
 * tokens via `POST {restBase}/{formId}/ai`.
 */
import React, { useEffect, useRef, useState } from 'react';
import { LuSend, LuSparkles, LuX } from 'react-icons/lu';
import { UPGRADE_URL } from './panes';
import { useStore } from './store';
import { Template } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;

/** Matches a prompt against known template names; longest match wins. */
function findTemplateMatch( prompt: string, templates: Template[] ): Template | null {
	const normalize = ( s: string ) => s.toLowerCase().replace( /[^a-z0-9\s]/g, ' ' ).replace( /\s+/g, ' ' ).trim();
	const p = normalize( prompt );
	let best: Template | null = null;
	let bestLen = 0;
	templates.forEach( ( tpl ) => {
		const name = normalize( tpl.name );
		if ( name.length >= 4 && p.includes( name ) && name.length > bestLen ) {
			best = tpl;
			bestLen = name.length;
		}
	} );
	return best;
}

const STYLE_SUGGESTIONS = [
	__( 'Modern & minimal', 'everest-forms' ),
	__( 'Bold & colorful', 'everest-forms' ),
	__( 'Elegant dark theme', 'everest-forms' ),
	__( 'Warm & friendly', 'everest-forms' ),
	__( 'Corporate & professional', 'everest-forms' ),
	__( 'Playful & rounded', 'everest-forms' ),
];

const GREETING = __(
	"Hi! Describe a look and I'll style your form — or pick a suggestion below.",
	'everest-forms'
);

// Discovery hint for anyone who hasn't opened Style with AI before — shown once,
// dismissed forever (per-user, via EVF_AI_Ajax::dismiss_hint()) either by closing it
// or by actually opening the panel.
const AI_HINT_NAME = 'style';

interface AiStyleResult {
	tokens: Record< string, any >;
	palette: string;
	summary: string;
	/** True when the request was ENTIRELY Pro-blocked — nothing was applied this turn. */
	notice: boolean;
	noticeUrl: string;
}

interface Message {
	role: 'user' | 'assistant';
	text: string;
	loading?: boolean;
	notice?: boolean;
	noticeUrl?: string;
	canUndo?: boolean;
	/** Store version this turn's undo is valid for. */
	undoVersion?: number;
}

async function requestAiStyle(
	restBase: string,
	formId: number,
	prompt: string,
	refinePrompt: string,
	currentRecord: { tokens: Record< string, unknown >; palette: string },
	history: Array< { role: 'user' | 'assistant'; text: string } >,
	lastChangedKeys: string[]
): Promise<
	| { ok: true; data: AiStyleResult }
	| { ok: false; message: string; noticeUrl?: string }
> {
	if ( ! apiFetch ) {
		return { ok: false, message: __( 'AI styling is unavailable on this screen.', 'everest-forms' ) };
	}
	try {
		const data = ( await apiFetch( {
			path: `${ restBase }/${ formId }/ai`,
			method: 'POST',
			data: {
				prompt,
				refine_prompt: refinePrompt,
				current_record: refinePrompt ? currentRecord : undefined,
				// Conversation memory for a refine call — see class-evf-ai-api.php::style_form()
				// for why a bare "current_record" token dump isn't enough context on its own.
				history: refinePrompt ? history : undefined,
				last_changed_keys: refinePrompt ? lastChangedKeys : undefined,
			},
		} ) ) as any;
		return {
			ok: true,
			data: {
				tokens: data.tokens || {},
				palette: data.palette || '',
				summary: data.summary || '',
				notice: !! data.notice,
				noticeUrl: data.notice_url || '',
			},
		};
	} catch ( e: any ) {
		const code = e && e.code;
		const tier = e && e.data && e.data.tier;
		// "daily_limit_reached" is today's hard cap (Free AND Pro both have one, Pro's is far
		// higher) — worth its own message and, for Free only, an upgrade link. A plain
		// "rate_limit" (the transient per-minute/per-hour throttle) still gets the gateway's
		// own specific message ("Too many requests…" / "Request limit reached — wait a
		// moment…") rather than a made-up generic string — same as every other error case.
		if ( 'daily_limit_reached' === code ) {
			return {
				ok: false,
				message: ( e && e.message ) || __( 'Request limit reached. Please try again later.', 'everest-forms' ),
				noticeUrl: 'pro' === tier ? undefined : UPGRADE_URL,
			};
		}
		const message =
			( e && e.message ) || __( 'Could not reach the AI service. Please try again.', 'everest-forms' );
		return { ok: false, message };
	}
}

export function AiAssistant() {
	const store = useStore();
	const [ open, setOpen ] = useState( false );
	const [ messages, setMessages ] = useState< Message[] >( [ { role: 'assistant', text: GREETING } ] );
	const [ input, setInput ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ originalPrompt, setOriginalPrompt ] = useState( '' );
	// Schema key(s) the AI's own last turn changed — sent back on the next refine so a follow-up
	// like "increase it to 200px" stays anchored to that same property (see class-evf-ai-api.php).
	const [ lastChangedKeys, setLastChangedKeys ] = useState< string[] >( [] );
	const [ onStyleTab, setOnStyleTab ] = useState( true );
	const [ buttonHovered, setButtonHovered ] = useState( false );
	const [ tooltipHovered, setTooltipHovered ] = useState( false );
	const showTooltip = ! open && ( buttonHovered || tooltipHovered );
	// The builder shell shows its own loading overlay (`.everest-forms-overlay`, faded out on
	// window `load` — see form-builder.js) while fields/canvas are still booting. Stay hidden
	// until then so this floating button doesn't sit on top of that loading screen.
	const [ builderLoaded, setBuilderLoaded ] = useState( () => 'complete' === document.readyState );
	const [ hintDismissed, setHintDismissed ] = useState( !! store.settings.aiHintDismissed );
	const dismissHint = () => {
		if ( hintDismissed ) return;
		setHintDismissed( true );
		if ( ! store.settings.ajaxUrl ) return;
		const body = new URLSearchParams();
		body.append( 'action', 'evf_ai_dismiss_hint' );
		body.append( 'hint', AI_HINT_NAME );
		body.append( 'nonce', store.settings.aiNonce );
		fetch( store.settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} ).catch( () => {
			// Best-effort only — worst case the hint reappears next visit.
		} );
	};
	const showHint = ! open && ! hintDismissed;
	const messagesEndRef = useRef< HTMLDivElement >( null );
	const inputRef = useRef< HTMLTextAreaElement >( null );

	useEffect( () => {
		const panel = document.getElementById( 'everest-forms-panel-style' );
		const read = () => setOnStyleTab( panel ? panel.classList.contains( 'active' ) : true );
		read();
		if ( ! panel ) return;
		const observer = new MutationObserver( read );
		observer.observe( panel, { attributes: true, attributeFilter: [ 'class' ] } );
		return () => observer.disconnect();
	}, [] );

	useEffect( () => {
		if ( builderLoaded ) return;
		const onLoad = () => setBuilderLoaded( true );
		window.addEventListener( 'load', onLoad );
		return () => window.removeEventListener( 'load', onLoad );
	}, [ builderLoaded ] );

	useEffect( () => {
		if ( ! onStyleTab ) setOpen( false );
	}, [ onStyleTab ] );

	// A click inside the preview iframe may be about to open a native <select> there — those
	// always paint above fixed page UI, so close the panel first rather than let it get covered.
	useEffect( () => {
		setOpen( false );
	}, [ store.previewInteractionCount ] );

	useEffect( () => {
		if ( open ) messagesEndRef.current?.scrollIntoView( { behavior: 'smooth' } );
	}, [ messages, open ] );

	useEffect( () => {
		if ( open ) setTimeout( () => inputRef.current?.focus(), 120 );
	}, [ open ] );

	if ( ! store.settings.aiEnabled || ! onStyleTab || ! builderLoaded ) {
		return null;
	}

	const send = async ( text: string ) => {
		if ( ! text.trim() || loading ) return;
		const userText = text.trim();
		const isRefine = '' !== originalPrompt;
		setInput( '' );

		setMessages( ( prev ) => [ ...prev, { role: 'user', text: userText } ] );

		const matchedTpl = findTemplateMatch( userText, store.allTemplates() );
		if ( matchedTpl ) {
			const locked = !! matchedTpl.is_pro && ! store.proActive;
			if ( locked ) {
				window.open( UPGRADE_URL, '_blank' );
				setMessages( ( prev ) => [
					...prev,
					{
						role: 'assistant',
						text: `"${ matchedTpl.name }" is a Pro template — upgrade to use it.`,
						notice: true,
					},
				] );
				return;
			}
			store.applyTemplate( matchedTpl.id, matchedTpl.tokens, matchedTpl.palette );
			setMessages( ( prev ) => {
				const cleared = prev.map( ( m ) => ( m.canUndo ? { ...m, canUndo: false } : m ) );
				return [
					...cleared,
					{
						role: 'assistant',
						text: `Applied the "${ matchedTpl.name }" template.`,
						canUndo: true,
						undoVersion: store.getVersion(),
					},
				];
			} );
			return;
		}

		setLoading( true );
		setMessages( ( prev ) => [ ...prev, { role: 'assistant', text: '', loading: true } ] );

		// Snapshot of the dialogue BEFORE this turn's user message — real conversation history,
		// not just the very first prompt ever typed (see class-evf-ai-api.php::style_form()).
		const history = messages
			.filter( ( m ) => ! m.loading )
			.map( ( m ) => ( { role: m.role, text: m.text } ) );

		const result = await requestAiStyle(
			store.settings.restBase,
			store.settings.formId,
			isRefine ? originalPrompt : userText,
			isRefine ? userText : '',
			{ tokens: store.tokens, palette: store.palette },
			history,
			lastChangedKeys
		);

		if ( ! isRefine ) setOriginalPrompt( userText );

		if ( result.ok ) {
			store.applyAiRecord( result.data.tokens, result.data.palette || undefined );
			setLastChangedKeys( Object.keys( result.data.tokens || {} ) );
		}

		setMessages( ( prev ) => {
			const cleared = prev.map( ( m ) => ( m.canUndo ? { ...m, canUndo: false } : m ) );
			const next = [ ...cleared ];
			next[ next.length - 1 ] = result.ok
				? result.data.notice
					? // Entirely Pro-blocked — nothing applied, so no Undo affordance to offer.
					  {
							role: 'assistant',
							text: result.data.summary || __( "That's a Pro feature.", 'everest-forms' ),
							notice: true,
							noticeUrl: result.data.noticeUrl,
					  }
					: {
							role: 'assistant',
							text: result.data.summary || __( 'Applied your style.', 'everest-forms' ),
							canUndo: true,
							undoVersion: store.getVersion(),
					  }
				: { role: 'assistant', text: result.message, notice: true, noticeUrl: result.noticeUrl };
			return next;
		} );
		setLoading( false );
	};

	const handleUndo = () => {
		store.undo();
		setMessages( ( prev ) => {
			const idx = prev.length - 1;
			if ( ! prev[ idx ]?.canUndo ) return prev;
			const next = [ ...prev ];
			next[ idx ] = { ...next[ idx ], canUndo: false };
			return next;
		} );
	};

	const handleKeyDown = ( e: React.KeyboardEvent< HTMLTextAreaElement > ) => {
		if ( 'Enter' === e.key && ! e.shiftKey ) {
			e.preventDefault();
			send( input );
		}
	};

	const hasStarted = messages.length > 1;

	const BTN_SIZE = 55;
	const BTN_RIGHT = 22;
	const BTN_BOTTOM = 22;
	const MODAL_BOTTOM = BTN_BOTTOM + BTN_SIZE + 8;

	return (
		<>
			<button
				type="button"
				aria-label={ __( 'Style with AI', 'everest-forms' ) }
				aria-expanded={ open }
				onClick={ () => {
					setOpen( ( o ) => ! o );
					dismissHint();
				} }
				onMouseEnter={ ( e ) => {
					setButtonHovered( true );
					( e.currentTarget as HTMLButtonElement ).style.transform = 'scale(1.08)';
					( e.currentTarget as HTMLButtonElement ).style.boxShadow = '0 6px 22px rgba(117,69,187,.55)';
				} }
				onMouseLeave={ ( e ) => {
					setButtonHovered( false );
					( e.currentTarget as HTMLButtonElement ).style.transform = 'scale(1)';
					( e.currentTarget as HTMLButtonElement ).style.boxShadow = '0 4px 16px rgba(117,69,187,.45)';
				} }
				style={ {
					position: 'fixed',
					bottom: BTN_BOTTOM,
					right: BTN_RIGHT,
					width: BTN_SIZE,
					height: BTN_SIZE,
					borderRadius: '50%',
					background: open
						? 'linear-gradient(135deg,#5c329c 0%,#7545BB 100%)'
						: 'linear-gradient(135deg,#7545BB 0%,#9660db 100%)',
					border: 'none',
					cursor: 'pointer',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					boxShadow: '0 4px 16px rgba(117,69,187,.45)',
					// Above .select2-dropdown (999999) — see .evf-subscription-expiry-calendar
					// for the same precedent — so field-setting dropdowns never cover the button.
					zIndex: 1000001,
					transition: 'transform .2s,box-shadow .2s,background .2s',
				} }
			>
				{ open ? <LuX size={ 22 } color="white" /> : <LuSparkles size={ 24 } color="white" /> }
			</button>

			{ showTooltip && ! showHint && (
				<div
					onMouseEnter={ () => setTooltipHovered( true ) }
					onMouseLeave={ () => setTooltipHovered( false ) }
					style={ {
						position: 'fixed',
						bottom: BTN_BOTTOM + BTN_SIZE + 8,
						right: BTN_RIGHT + Math.round( BTN_SIZE / 2 ),
						transform: 'translateX(50%)',
						pointerEvents: 'none',
						zIndex: 1000002,
					} }
				>
					<div
						style={ {
							background: '#fff',
							border: '0.8px solid #e1e1e1',
							borderRadius: 3,
							padding: '16px 20px',
							fontSize: 13,
							color: '#222',
							whiteSpace: 'nowrap',
							position: 'relative',
						} }
					>
						{ __( 'Style with AI', 'everest-forms' ) }
					</div>
					<div
						style={ {
							position: 'absolute',
							bottom: -8,
							left: '50%',
							marginLeft: -7,
							width: 0,
							height: 0,
							borderLeft: '7px solid transparent',
							borderRight: '7px solid transparent',
							borderTop: '8px solid #e1e1e1',
						} }
					/>
					<div
						style={ {
							position: 'absolute',
							bottom: -6,
							left: '50%',
							marginLeft: -6,
							width: 0,
							height: 0,
							borderLeft: '6px solid transparent',
							borderRight: '6px solid transparent',
							borderTop: '7px solid #fff',
						} }
					/>
				</div>
			) }

			{ showHint && (
				<div
					style={ {
						position: 'fixed',
						bottom: BTN_BOTTOM + BTN_SIZE + 10,
						right: BTN_RIGHT,
						width: 272,
						zIndex: 1000002,
					} }
				>
					<div
						style={ {
							position: 'relative',
							background: '#fff',
							border: '1px solid #e9e2f3',
							borderRadius: 14,
							padding: '14px 16px',
							boxShadow: '0 10px 30px rgba(88,45,163,.22), 0 2px 8px rgba(20,23,40,.08)',
						} }
					>
						<button
							type="button"
							aria-label={ __( 'Dismiss', 'everest-forms' ) }
							onClick={ dismissHint }
							style={ {
								position: 'absolute',
								top: 8,
								right: 8,
								border: 0,
								background: 'none',
								color: '#9a95a8',
								cursor: 'pointer',
								width: 22,
								height: 22,
								borderRadius: '50%',
								display: 'grid',
								placeItems: 'center',
							} }
							onMouseEnter={ ( e ) => {
								e.currentTarget.style.background = 'rgba(117,69,187,.12)';
								e.currentTarget.style.color = '#7545BB';
							} }
							onMouseLeave={ ( e ) => {
								e.currentTarget.style.background = 'none';
								e.currentTarget.style.color = '#9a95a8';
							} }
						>
							<LuX size={ 13 } />
						</button>
						<div
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: 5,
								fontSize: 11,
								fontWeight: 700,
								letterSpacing: '.03em',
								textTransform: 'uppercase',
								color: '#7545BB',
								marginBottom: 6,
							} }
						>
							<LuSparkles size={ 12 } />
							{ __( 'New', 'everest-forms' ) }
						</div>
						<div style={ { fontSize: 13, lineHeight: 1.5, color: '#383838', paddingRight: 14 } }>
							{ __(
								'Describe the look you want, and I’ll style your form for you.',
								'everest-forms'
							) }
						</div>
						<div
							style={ {
								position: 'absolute',
								bottom: -8,
								right: 24,
								width: 0,
								height: 0,
								borderLeft: '8px solid transparent',
								borderRight: '8px solid transparent',
								borderTop: '8px solid #e9e2f3',
							} }
						/>
						<div
							style={ {
								position: 'absolute',
								bottom: -6,
								right: 25,
								width: 0,
								height: 0,
								borderLeft: '7px solid transparent',
								borderRight: '7px solid transparent',
								borderTop: '7px solid #fff',
							} }
						/>
					</div>
				</div>
			) }

			{ open && (
				<div
					role="dialog"
					aria-label={ __( 'Style with AI', 'everest-forms' ) }
					style={ {
						position: 'fixed',
						bottom: MODAL_BOTTOM,
						right: BTN_RIGHT,
						width: 440,
						height: 520,
						maxHeight: `calc(100vh - ${ MODAL_BOTTOM + 40 }px)`,
						borderRadius: 16,
						background: '#fff',
						boxShadow: '0 8px 40px rgba(0,0,0,.18)',
						border: '1px solid #e2e8f0',
						display: 'flex',
						flexDirection: 'column',
						overflow: 'hidden',
						zIndex: 1000000,
					} }
				>
					<div
						style={ {
							display: 'flex',
							alignItems: 'center',
							gap: 10,
							padding: '0 16px',
							height: 52,
							background: 'linear-gradient(135deg,#7545BB 0%,#9660db 100%)',
							flexShrink: 0,
						} }
					>
						<div
							style={ {
								width: 28,
								height: 28,
								borderRadius: '50%',
								background: 'rgba(255,255,255,.15)',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
								flexShrink: 0,
							} }
						>
							<LuSparkles size={ 14 } color="white" />
						</div>
						<div style={ { flex: 1, minWidth: 0 } }>
							<div style={ { fontSize: 14, fontWeight: 600, color: '#fff', lineHeight: 1.2 } }>
								{ __( 'Style with AI', 'everest-forms' ) }
							</div>
							<div style={ { fontSize: 11, color: 'rgba(255,255,255,.7)', lineHeight: 1.2 } }>
								{ __( 'Powered by AI', 'everest-forms' ) }
							</div>
						</div>
					</div>

					<div
						className="scv2-ai-messages"
						style={ {
							flex: 1,
							overflowY: 'auto',
							padding: '16px 14px',
							display: 'flex',
							flexDirection: 'column',
							gap: 10,
						} }
					>
						{ messages.map( ( msg, i ) => (
							<div
								key={ i }
								style={ {
									display: 'flex',
									flexDirection: 'user' === msg.role ? 'row-reverse' : 'row',
									alignItems: 'flex-end',
									gap: 8,
								} }
							>
								{ 'assistant' === msg.role && (
									<div
										style={ {
											width: 26,
											height: 26,
											borderRadius: '50%',
											background: 'rgba(117,69,187,.1)',
											display: 'flex',
											alignItems: 'center',
											justifyContent: 'center',
											flexShrink: 0,
										} }
									>
										<LuSparkles size={ 13 } color="#7545BB" />
									</div>
								) }

								<div
									style={ {
										maxWidth: '82%',
										padding: '9px 12px',
										borderRadius: 'user' === msg.role ? '14px 14px 4px 14px' : '4px 14px 14px 14px',
										background: 'user' === msg.role ? '#7545BB' : msg.notice ? '#fff8f8' : '#f4f0fb',
										color: 'user' === msg.role ? '#fff' : msg.notice ? '#c0392b' : '#1a1a2e',
										border: msg.notice ? '1px solid #fca5a5' : 'none',
										fontSize: 13,
										lineHeight: 1.55,
										boxShadow: 'user' === msg.role ? '0 2px 8px rgba(117,69,187,.2)' : 'none',
									} }
								>
									{ msg.loading ? (
										<div style={ { display: 'flex', gap: 4, padding: '2px 0' } }>
											{ [ 0, 1, 2 ].map( ( d ) => (
												<span
													key={ d }
													style={ {
														width: 6,
														height: 6,
														borderRadius: '50%',
														background: '#9660db',
														display: 'inline-block',
														animation: `scv2-ai-dot 1.1s ease-in-out ${ d * 0.18 }s infinite`,
													} }
												/>
											) ) }
										</div>
									) : (
										<>
											{ msg.text }
											{ msg.canUndo && msg.undoVersion === store.getVersion() && (
												<div style={ { marginTop: 6 } }>
													<a
														href="#"
														onClick={ ( e ) => {
															e.preventDefault();
															handleUndo();
														} }
														style={ {
															color: '#7545BB',
															fontWeight: 600,
															fontSize: 12,
															textDecoration: 'underline',
															cursor: 'pointer',
														} }
													>
														{ __( 'Undo ↺', 'everest-forms' ) }
													</a>
												</div>
											) }
											{ msg.notice && msg.noticeUrl && (
												<div style={ { marginTop: 6 } }>
													<a
														href={ msg.noticeUrl }
														target="_blank"
														rel="noopener noreferrer"
														style={ {
															color: '#c0392b',
															fontWeight: 600,
															fontSize: 12,
															textDecoration: 'underline',
														} }
													>
														{ __( 'Upgrade to Pro ↗', 'everest-forms' ) }
													</a>
												</div>
											) }
										</>
									) }
								</div>
							</div>
						) ) }
						<div ref={ messagesEndRef } />
					</div>

					<div
						className="scv2-ai-suggestions"
						style={ {
							padding: '0 14px 10px',
							display: 'flex',
							gap: 6,
							overflowX: 'auto',
							flexShrink: 0,
							scrollbarWidth: 'thin',
							scrollbarColor: '#d4c5f0 transparent',
						} }
					>
						{ STYLE_SUGGESTIONS.map( ( s ) => (
							<button
								type="button"
								key={ s }
								onClick={ () => send( s ) }
								style={ {
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
								} }
								onMouseEnter={ ( e ) => {
									( e.currentTarget as HTMLButtonElement ).style.background = '#f0ebfa';
									( e.currentTarget as HTMLButtonElement ).style.borderColor = '#b89ee0';
								} }
								onMouseLeave={ ( e ) => {
									( e.currentTarget as HTMLButtonElement ).style.background = '#faf9ff';
									( e.currentTarget as HTMLButtonElement ).style.borderColor = '#e2e8f0';
								} }
							>
								{ s }
							</button>
						) ) }
					</div>

					<div
						style={ {
							padding: '10px 14px 14px',
							borderTop: '1px solid #f1f5f9',
							flexShrink: 0,
						} }
					>
						<div
							style={ {
								display: 'flex',
								alignItems: 'flex-end',
								gap: 8,
								border: '1.5px solid #e2e8f0',
								borderRadius: 12,
								padding: '8px 10px 8px 14px',
								background: '#fff',
								transition: 'border-color .2s',
							} }
						>
							<textarea
								ref={ inputRef }
								value={ input }
								maxLength={ 500 }
								onChange={ ( e ) => setInput( e.target.value ) }
								onKeyDown={ handleKeyDown }
								placeholder={
									hasStarted
										? __( 'Refine it…', 'everest-forms' )
										: __( 'Describe a look…', 'everest-forms' )
								}
								rows={ 1 }
								style={ {
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
								} }
							/>
							<button
								type="button"
								onClick={ () => send( input ) }
								disabled={ ! input.trim() || loading }
								style={ {
									width: 32,
									height: 32,
									borderRadius: 8,
									border: 'none',
									background: input.trim() && ! loading ? '#7545BB' : '#e6e3ee',
									cursor: input.trim() && ! loading ? 'pointer' : 'not-allowed',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
									flexShrink: 0,
									transition: 'background .2s',
								} }
							>
								<LuSend size={ 15 } color={ input.trim() && ! loading ? '#fff' : '#9a9a9a' } />
							</button>
						</div>
						<p style={ { fontSize: 11, color: '#9ca3af', margin: '6px 0 0', textAlign: 'center' } }>
							{ __( 'Your style updates live — Save when you’re happy.', 'everest-forms' ) }
						</p>
					</div>
				</div>
			) }

			<style>{ `
				@keyframes scv2-ai-dot {
					0%,80%,100%{transform:scale(.4);opacity:.4}
					40%{transform:scale(1);opacity:1}
				}
				.scv2-ai-messages::-webkit-scrollbar,
				.scv2-ai-suggestions::-webkit-scrollbar {
					width: 4px;
					height: 4px;
				}
				.scv2-ai-messages::-webkit-scrollbar-track,
				.scv2-ai-suggestions::-webkit-scrollbar-track {
					background: transparent;
				}
				.scv2-ai-messages::-webkit-scrollbar-thumb,
				.scv2-ai-suggestions::-webkit-scrollbar-thumb {
					background: #d4c5f0;
					border-radius: 4px;
				}
				.scv2-ai-messages::-webkit-scrollbar-thumb:hover,
				.scv2-ai-suggestions::-webkit-scrollbar-thumb:hover {
					background: #b89ee0;
				}
			` }</style>
		</>
	);
}
