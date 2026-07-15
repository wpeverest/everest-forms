/**
 * Style Customizer v2 — panel app.
 *
 * Mounts inside the builder's native sidebar and renders the controls (sub-tabs →
 * Design drill-down / Templates / Custom CSS). The live preview is portaled into the builder's
 * content area so the panel matches the builder's layout exactly. Styles are saved through the
 * builder's own Save button — there is no separate save control.
 */
import React from 'react';
import { createPortal } from 'react-dom';
import { AiAssistant } from './AiAssistant';
import { CustomCssPane, DesignList, ElementSlate, ProCrown, TemplatesPane, UPGRADE_URL } from './panes';
import { ConfirmModal, ConfirmState, Popover, PopoverState } from './Popover';
import { PreviewPane } from './PreviewPane';
import { getActiveBridge, SelectionInfo } from './PreviewBridge';
import { DEVICE_LABELS, SECTION_ICONS, STATE_FORCE } from './constants';
import { useStore } from './store';
import { Section, Token } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;

type SubPane = 'design' | 'templates' | 'css';
const SUBPANES: SubPane[] = [ 'design', 'templates', 'css' ];

interface Toast {
	msg: string;
	actLabel?: string;
	onAct?: () => void;
	kind?: 'success' | 'info';
}

export function App() {
	const store = useStore();
	const [ subPane, setSubPane ] = React.useState< SubPane >( 'design' );
	const [ curSection, setCurSection ] = React.useState< string | null >( null );
	const [ activeState, setActiveState ] = React.useState< Record< string, string > >( {} );
	const [ popover, setPopover ] = React.useState< PopoverState | null >( null );
	const [ confirm, setConfirm ] = React.useState< ConfirmState | null >( null );
	const [ toast, setToast ] = React.useState< Toast | null >( null );
	const [ saving, setSaving ] = React.useState( false );
	const [ saveError, setSaveError ] = React.useState( '' );
	const [ saveErrorConflict, setSaveErrorConflict ] = React.useState( false );
	const [ selectPulse, setSelectPulse ] = React.useState( 0 );
	const toastTimer = React.useRef< ReturnType< typeof setTimeout > | null >( null );

	const sections: Section[] = React.useMemo(
		() => Object.keys( store.sections ).map( ( key ) => ( { key, ...store.sections[ key ] } ) ),
		[ store.sections ]
	);

	const dirty = store.isDirty();
	const closePopover = React.useCallback( () => setPopover( null ), [] );

	const showToast = React.useCallback( ( t: Toast ) => {
		setToast( t );
		if ( toastTimer.current ) {
			clearTimeout( toastTimer.current );
		}
		// Toasts with an action button are NOT auto-dismissed on a bare timer while the pointer/
		// focus is on them (see pauseToast/resumeToast below) — this only starts the clock, which
		// those restart from wherever the user left off. WCAG 2.2.1 (Timing Adjustable): a toast
		// hosting real functionality (Undo) must not vanish on a fixed timer a user can't extend.
		toastTimer.current = setTimeout( () => setToast( null ), 4200 );
	}, [] );

	const pauseToast = React.useCallback( () => {
		if ( toastTimer.current ) {
			clearTimeout( toastTimer.current );
			toastTimer.current = null;
		}
	}, [] );

	const resumeToast = React.useCallback( () => {
		if ( toastTimer.current ) {
			clearTimeout( toastTimer.current );
		}
		toastTimer.current = setTimeout( () => setToast( null ), 4200 );
	}, [] );

	// Tell the user when an edit silently detaches the active palette (store.ts setTokenValue) —
	// previously this happened with zero notice, and the "Color palette" row would then show
	// stale/placeholder swatches with no explanation.
	React.useEffect( () => {
		store.onPaletteUnlinked = ( name: string ) => {
			showToast( {
				msg: `${ __( 'This customization is no longer linked to the', 'everest-forms' ) } “${ name }” ${ __( 'palette.', 'everest-forms' ) }`,
			} );
		};
		return () => {
			store.onPaletteUnlinked = null;
		};
	}, [ store, showToast ] );

	/* ---- navigation ---- */
	const openSection = ( key: string ) => {
		setSubPane( 'design' );
		setCurSection( key );
	};
	const backToList = () => {
		setCurSection( null );
		getActiveBridge()?.clearSelection();
	};

	// Switching to Templates/Custom CSS and leaving a Design slate open behind it is legitimate
	// (e.g. checking a template for reference mid-edit) — but landing back on "Design" later and
	// silently reopening that slate instead of the section list reads as a stuck/broken tab, not
	// intentional persistence. Reset only when leaving Design, so returning to it always starts
	// at the list; deep-linking in via a row click or a preview click still works as before.
	const changeSubPane = ( id: SubPane ) => {
		setSubPane( id );
		if ( id !== 'design' ) {
			setCurSection( null );
		}
	};

	const inSlate = subPane === 'design' && !! curSection;
	const section = curSection ? sections.find( ( s ) => s.key === curSection ) : null;

	const tabsFor = ( s: Section | null ) => ( s ? s.states || s.variants || null : null );
	const currentStateFor = ( s: Section ): string | null => {
		const tabs = tabsFor( s );
		if ( ! tabs ) {
			return null;
		}
		return activeState[ s.key ] || tabs[ 0 ];
	};

	const forceClass = ( () => {
		if ( ! inSlate || ! section ) {
			return null;
		}
		const st = currentStateFor( section );
		return st ? STATE_FORCE[ st ] || null : null;
	} )();

	/* ---- click-to-edit: a preview element was clicked ---- */
	const onSelectElement = React.useCallback( ( info: SelectionInfo ) => {
		setSubPane( 'design' );
		setCurSection( info.section );
		if ( info.variant ) {
			setActiveState( ( m ) => ( { ...m, [ info.section ]: info.variant as string } ) );
		}
		setSelectPulse( ( n ) => n + 1 ); // re-flash the slate even if the section was already open.
	}, [] );

	/* ---- save (invoked by the builder's Save button) ---- */
	const save = React.useCallback( async () => {
		if ( ! apiFetch || ! store.isDirty() ) {
			return;
		}
		setSaving( true );
		setSaveError( '' );
		setSaveErrorConflict( false );
		try {
			const data: Record< string, unknown > = {
				record: store.toRecord(),
				base_updated_at: store.baseUpdatedAt,
			};
			// "Apply Theme Style" persists to a separate per-form meta (not the record). Only sent
			// when the user actually touched the toggle this session — the legacy `?evf_preview`
			// page has its OWN toggle+Save for this same meta (still live in another tab), so
			// always resending the value cached at page-load would silently revert a change made
			// there. Omitting the key is a true no-op server-side (see RestController::save_item()'s
			// `null !== $apply_theme` guard).
			if ( store.applyThemeStyleTouched ) {
				data.apply_theme_style = store.applyThemeStyle;
			}
			const res = await apiFetch( {
				path: `${ store.settings.restBase }/${ store.settings.formId }`,
				method: 'POST',
				data,
			} );
			store.markSaved( res.record );
		} catch ( e: any ) {
			const status = e && e.data && e.data.status;
			setSaveErrorConflict( status === 409 );
			setSaveError(
				status === 409
					? __( 'These styles changed elsewhere — reload the builder before saving.', 'everest-forms' )
					: ( e && e.message ) || __( 'Failed to save styles.', 'everest-forms' )
			);
		} finally {
			setSaving( false );
		}
	}, [ store ] );

	const saveRef = React.useRef( save );
	saveRef.current = save;

	// Piggyback on the builder's own Save button (always-on, delegated click).
	React.useEffect( () => {
		const onClick = ( e: MouseEvent ) => {
			const target = e.target as HTMLElement;
			if ( target && target.closest && target.closest( '.everest-forms-save-button' ) ) {
				saveRef.current();
			}
		};
		document.addEventListener( 'click', onClick, true );
		return () => document.removeEventListener( 'click', onClick, true );
	}, [] );

	// Warn before leaving with unsaved style changes (the builder has its own guard too).
	React.useEffect( () => {
		const handler = ( e: BeforeUnloadEvent ) => {
			if ( store.isDirty() ) {
				e.preventDefault();
				e.returnValue = '';
			}
		};
		window.addEventListener( 'beforeunload', handler );
		return () => window.removeEventListener( 'beforeunload', handler );
	}, [ store ] );

	// Keyboard undo/redo.
	React.useEffect( () => {
		const onKey = ( e: KeyboardEvent ) => {
			const target = e.target as HTMLElement;
			if ( target && /^(INPUT|TEXTAREA|SELECT)$/.test( target.tagName ) ) {
				return;
			}
			if ( ( e.ctrlKey || e.metaKey ) && e.key.toLowerCase() === 'z' ) {
				e.preventDefault();
				if ( e.shiftKey ) {
					store.redo();
				} else {
					store.undo();
				}
			}
		};
		window.addEventListener( 'keydown', onKey );
		return () => window.removeEventListener( 'keydown', onKey );
	}, [ store ] );

	/* ---- popovers ---- */
	const openInfo = ( anchor: HTMLElement, text: string ) => {
		setPopover( { anchor, render: () => <div className="pop-body">{ text }</div> } );
	};

	const openBadge = ( token: Token, anchor: HTMLElement ) => {
		const device = store.device;
		const override = store.isOverride( token.key );
		setPopover( {
			anchor,
			render: () => (
				<div>
					<div className="pop-title">
						<b>{ token.label }</b> — { __( 'responsive control', 'everest-forms' ) }
					</div>
					{ device === 'desktop' ? (
						<div className="pop-body">
							{ __(
								'You’re editing the Desktop base value. Switch to tablet or mobile to set a per-device override.',
								'everest-forms'
							) }
						</div>
					) : (
						<>
							<div className="pop-body">
								{ __( 'Editing', 'everest-forms' ) } <b>{ DEVICE_LABELS[ device ] }</b> —{ ' ' }
								{ override
									? __( 'this device has its own value.', 'everest-forms' )
									: __( 'currently inheriting the Desktop value.', 'everest-forms' ) }
							</div>
							{ override && (
								<>
									<div className="pop-sep" />
									<button
										type="button"
										className="pop-item danger"
										onClick={ () => {
											store.removeOverride( token.key, device );
											closePopover();
										} }
									>
										{ __( 'Remove', 'everest-forms' ) } { DEVICE_LABELS[ device ].toLowerCase() }{ ' ' }
										{ __( 'override', 'everest-forms' ) }
									</button>
								</>
							) }
						</>
					) }
				</div>
			),
		} );
	};

	const paletteOpen = popover?.kind === 'palette';

	const openPalette = ( anchor: HTMLElement ) => {
		// Reliable toggle: a second click on the same control closes the popover.
		if ( popover?.kind === 'palette' ) {
			setPopover( null );
			return;
		}
		setPopover( {
			anchor,
			matchWidth: true,
			kind: 'palette',
			title: __( 'Choose a colour palette', 'everest-forms' ),
			closable: true,
			render: () => (
				<div>
					<div className="pal-pop-grid" role="listbox" aria-label={ __( 'Colour palettes', 'everest-forms' ) }>
						{ store.palettes.map( ( p ) => {
							const locked = p.is_pro && ! store.proActive;
							return (
								<button
									key={ p.id }
									type="button"
									className="pal-card"
									role="option"
									aria-selected={ p.id === store.palette }
									title={ p.name }
									onMouseEnter={ () => getActiveBridge()?.previewPalette( p.colors ) }
									onMouseLeave={ () => getActiveBridge()?.revert() }
									onClick={ () => {
										if ( locked ) {
											window.open( UPGRADE_URL, '_blank' );
											return;
										}
										store.applyPalette( p.id );
										closePopover();
										showToast( {
											kind: 'success',
											msg: `${ __( 'Applied palette', 'everest-forms' ) } “${ p.name }”`,
											actLabel: __( 'Undo', 'everest-forms' ),
											onAct: () => store.undo(),
										} );
									} }
								>
									<span className="sw" aria-hidden="true">
										{ Object.keys( store.paletteMap ).map( ( slot ) => (
											<i key={ slot } style={ { background: p.colors[ slot ] } } />
										) ) }
									</span>
									<span className="cap">
										{ p.name }
										{ locked && (
											<span className="pro" aria-label={ __( 'Pro', 'everest-forms' ) }>
												<ProCrown />
											</span>
										) }
									</span>
								</button>
							);
						} ) }
					</div>
				</div>
			),
		} );
	};

	/* ---- render ---- */
	const previewHost = document.getElementById( 'evf-scv2-preview' );

	return (
		<div className="scv2-panel">
			<div className="subtabs">
				<div
					className="subtabs-main"
					role="tablist"
					aria-label={ __( 'Style panel sections', 'everest-forms' ) }
					onKeyDown={ ( e ) => {
						if ( e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' ) {
							return;
						}
						e.preventDefault();
						const idx = SUBPANES.indexOf( subPane );
						const next = SUBPANES[ ( idx + ( e.key === 'ArrowRight' ? 1 : SUBPANES.length - 1 ) ) % SUBPANES.length ];
						changeSubPane( next );
						document.getElementById( `scv2-tab-${ next }` )?.focus();
					} }
				>
					{ SUBPANES.map( ( id ) => (
						<button
							key={ id }
							id={ `scv2-tab-${ id }` }
							type="button"
							role="tab"
							className="subtab"
							aria-selected={ subPane === id }
							aria-controls="scv2-tabpanel"
							tabIndex={ subPane === id ? 0 : -1 }
							onClick={ () => changeSubPane( id ) }
						>
							{ id === 'design'
								? __( 'Design', 'everest-forms' )
								: id === 'templates'
								? __( 'Templates', 'everest-forms' )
								: __( 'Custom CSS', 'everest-forms' ) }
						</button>
					) ) }
				</div>
				<div className="subtabs-actions">
					<button
						type="button"
						className="uxbtn"
						disabled={ ! store.canUndo() }
						aria-label={ __( 'Undo', 'everest-forms' ) }
						title={ __( 'Undo', 'everest-forms' ) + ' (Ctrl+Z)' }
						onClick={ () => store.undo() }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<path d="M3 10h10a5 5 0 0 1 0 10H7" />
							<path d="M7 6 3 10l4 4" />
						</svg>
					</button>
					<button
						type="button"
						className="uxbtn"
						disabled={ ! store.canRedo() }
						aria-label={ __( 'Redo', 'everest-forms' ) }
						title={ __( 'Redo', 'everest-forms' ) + ' (Ctrl+Shift+Z)' }
						onClick={ () => store.redo() }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<path d="M21 10H11a5 5 0 0 0 0 10h6" />
							<path d="m17 6 4 4-4 4" />
						</svg>
					</button>
				</div>
			</div>

			{ inSlate && section && (
				<div className="navback">
					<button type="button" className="bk" onClick={ backToList }>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 }>
							<path d="m15 18-6-6 6-6" />
						</svg>
						<span className="ic">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth={ 2 }
								dangerouslySetInnerHTML={ { __html: SECTION_ICONS[ section.key ] || '' } }
							/>
						</span>
						<span>{ section.title }</span>
					</button>
				</div>
			) }

			{ store.device !== 'desktop' && (
				<div className="ctxbar">
					<span>
						{ __( 'Editing', 'everest-forms' ) }{ ' ' }
						<b>
							{ DEVICE_LABELS[ store.device ] } · ≤ { store.breakpoints[ store.device ] }px
						</b>{ ' ' }
						— { __( 'inherits Desktop', 'everest-forms' ) }
					</span>
					<button type="button" className="back" onClick={ () => store.setDevice( 'desktop' ) }>
						{ __( 'Back to Desktop', 'everest-forms' ) }
					</button>
				</div>
			) }

			<div
				className="panel-scroll"
				id="scv2-tabpanel"
				role="tabpanel"
				aria-labelledby={ `scv2-tab-${ subPane }` }
			>
				{ subPane === 'design' &&
					( inSlate && section ? (
						<ElementSlate
							key={ section.key }
							section={ section }
							activeState={ currentStateFor( section ) }
							onChangeState={ ( id ) => setActiveState( ( m ) => ( { ...m, [ section.key ]: id } ) ) }
							onBadgeClick={ openBadge }
							pulse={ selectPulse }
						/>
					) : (
						<DesignList
							sections={ sections }
							onOpen={ openSection }
							onOpenPalette={ openPalette }
							paletteOpen={ paletteOpen }
							onResetAll={ () =>
								setConfirm( {
									title: __( 'Reset all styles?', 'everest-forms' ),
									message: __(
										'Every element goes back to its default — palette, fonts, spacing, everything. You can still undo it right after.',
										'everest-forms'
									),
									confirmLabel: __( 'Reset all', 'everest-forms' ),
									danger: true,
									onConfirm: () => {
										store.resetAll();
										showToast( {
											msg: __( 'All styles reset to default.', 'everest-forms' ),
											actLabel: __( 'Undo', 'everest-forms' ),
											onAct: () => store.undo(),
										} );
									},
								} )
							}
						/>
					) ) }

				{ subPane === 'templates' && (
					<TemplatesPane
						onPreview={ ( ov ) => getActiveBridge()?.previewValues( ov ) }
						onClearPreview={ () => getActiveBridge()?.revert() }
						onApplied={ ( name ) =>
							showToast( {
								kind: 'success',
								msg: `${ __( 'Applied template', 'everest-forms' ) } “${ name }”`,
								actLabel: __( 'Undo', 'everest-forms' ),
								onAct: () => store.undo(),
							} )
						}
					/>
				) }

				{ subPane === 'css' && <CustomCssPane /> }
			</div>

			{ popover && <Popover state={ popover } onClose={ closePopover } /> }
			{ confirm && <ConfirmModal state={ confirm } onClose={ () => setConfirm( null ) } /> }

			{ previewHost &&
				createPortal(
					<PreviewPane
						forceClass={ forceClass }
						saving={ saving }
						dirty={ dirty }
						saveError={ saveError }
						saveErrorConflict={ saveErrorConflict }
						onInfo={ openInfo }
						onSelect={ onSelectElement }
						onIframeClick={ closePopover }
						toast={ toast }
						onToastPause={ pauseToast }
						onToastResume={ resumeToast }
					/>,
					previewHost
				) }

			{ /* Portaled to <body> — matches builder-ai/BuilderAIChat.tsx's own root, so this
			     fixed-position launcher is never mis-anchored by a transformed ancestor
			     somewhere in the builder's sidebar/panel chain. */ }
			{ createPortal( <AiAssistant />, document.body ) }
		</div>
	);
}
