/**
 * Style Customizer v2 — panel app. Renders the sub-tabs (Design / Templates / Custom CSS)
 * and portals the live preview into the builder's content area.
 */
import React from 'react';
import { createPortal } from 'react-dom';
import { AiAssistant } from './AiAssistant';
import { ColorsPane, CustomCssPane, DesignList, ElementSlate, TemplatesPane } from './panes';
import { ConfirmModal, ConfirmState } from './Popover';
import { PreviewPane } from './PreviewPane';
import { getActiveBridge, SelectionInfo } from './PreviewBridge';
import { DEVICE_LABELS, SECTION_ICONS, STATE_FORCE } from './constants';
import { useStore } from './store';
import { Section } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;

/** Single-list navigation: 'list' is the home screen (PRE-DEFINED + Elements + Advanced);
 *  the rest are drill-downs reached from it, each with its own "← Back" header. */
type View = 'list' | 'element' | 'templates' | 'colors' | 'css';

interface Toast {
	msg: string;
	actLabel?: string;
	onAct?: () => void;
	kind?: 'success' | 'info';
}

export function App() {
	const store = useStore();
	const [ view, setView ] = React.useState< View >( 'list' );
	const [ curSection, setCurSection ] = React.useState< string | null >( null );
	const [ activeState, setActiveState ] = React.useState< Record< string, string > >( {} );
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
	// Lets the AI assistant panel dismiss itself — a click here may be about to open a native
	// <select>, which always paints above fixed UI (see EVF-2698).
	const onPreviewClick = React.useCallback( () => {
		store.notePreviewInteraction();
	}, [ store ] );

	const showToast = React.useCallback( ( t: Toast ) => {
		setToast( t );
		if ( toastTimer.current ) {
			clearTimeout( toastTimer.current );
		}
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

	React.useEffect( () => {
		store.onPaletteUnlinked = ( name: string ) => {
			showToast( {
				msg: `${ __( 'Unlinked from the', 'everest-forms' ) } “${ name }” ${ __( 'palette after your edit.', 'everest-forms' ) }`,
			} );
		};
		return () => {
			store.onPaletteUnlinked = null;
		};
	}, [ store, showToast ] );

	/* ---- navigation ---- */
	const openSection = ( key: string ) => {
		setView( 'element' );
		setCurSection( key );
	};
	const backToList = () => {
		setCurSection( null );
		setView( 'list' );
		getActiveBridge()?.clearSelection();
	};

	const inSlate = view === 'element' && !! curSection;
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
		setView( 'element' );
		setCurSection( info.section );
		if ( info.variant ) {
			setActiveState( ( m ) => ( { ...m, [ info.section ]: info.variant as string } ) );
		}
		setSelectPulse( ( n ) => n + 1 );
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
			// Only sent when touched this session, to avoid clobbering the legacy preview page's own toggle for the same meta.
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
					? __( 'These styles changed elsewhere — reload before saving.', 'everest-forms' )
					: ( e && e.message ) || __( 'Failed to save styles.', 'everest-forms' )
			);
		} finally {
			setSaving( false );
		}
	}, [ store ] );

	const saveRef = React.useRef( save );
	saveRef.current = save;

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

	/* ---- undo/redo: confirm what just happened, with a one-click reverse (mirrors the
	 *  "Applied palette X [Undo]" pattern already used for apply-palette/reset-all/apply-template) ---- */
	const handleUndo = React.useCallback( () => {
		if ( ! store.canUndo() ) {
			return;
		}
		const label = store.undoLabel();
		store.undo();
		showToast( {
			msg: `${ __( 'Undid:', 'everest-forms' ) } ${ label }`,
			actLabel: __( 'Redo', 'everest-forms' ),
			onAct: () => store.redo(),
		} );
	}, [ store, showToast ] );

	const handleRedo = React.useCallback( () => {
		if ( ! store.canRedo() ) {
			return;
		}
		const label = store.redoLabel();
		store.redo();
		showToast( {
			msg: `${ __( 'Redid:', 'everest-forms' ) } ${ label }`,
			actLabel: __( 'Undo', 'everest-forms' ),
			onAct: () => store.undo(),
		} );
	}, [ store, showToast ] );

	React.useEffect( () => {
		const onKey = ( e: KeyboardEvent ) => {
			const target = e.target as HTMLElement;
			if ( target && /^(INPUT|TEXTAREA|SELECT)$/.test( target.tagName ) ) {
				return;
			}
			if ( ( e.ctrlKey || e.metaKey ) && e.key.toLowerCase() === 'z' ) {
				e.preventDefault();
				if ( e.shiftKey ) {
					handleRedo();
				} else {
					handleUndo();
				}
				return;
			}
			// Ctrl+Y: the conventional Windows/Linux redo shortcut, alongside Ctrl+Shift+Z. Not
			// Cmd+Y (macOS binds that to the browser's own History, which this shouldn't swallow).
			if ( e.ctrlKey && ! e.metaKey && e.key.toLowerCase() === 'y' ) {
				e.preventDefault();
				handleRedo();
			}
		};
		window.addEventListener( 'keydown', onKey );
		return () => window.removeEventListener( 'keydown', onKey );
	}, [ handleUndo, handleRedo ] );

	/* ---- render ---- */
	const previewHost = document.getElementById( 'evf-scv2-preview' );

	// Same "← [icon] Title" back header shape as the element drill-down (below), so every
	// drill-down in the panel reads as one consistent pattern.
	const browseMeta: Record< string, { title: string; icon: string } > = {
		templates: {
			title: __( 'Templates', 'everest-forms' ),
			icon: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
		},
		colors: {
			title: __( 'Colors', 'everest-forms' ),
			icon: '<circle cx="8" cy="9" r="3"/><circle cx="16" cy="9" r="3"/><circle cx="12" cy="17" r="3"/>',
		},
		css: {
			title: __( 'Custom CSS', 'everest-forms' ),
			icon: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
		},
	};

	return (
		<div className="scv2-panel">
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

			{ ( view === 'templates' || view === 'colors' || view === 'css' ) && (
				<div className="navback">
					<button type="button" className="bk" onClick={ () => setView( 'list' ) }>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 }>
							<path d="m15 18-6-6 6-6" />
						</svg>
						<span className="ic">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth={ 2 }
								dangerouslySetInnerHTML={ { __html: browseMeta[ view ].icon } }
							/>
						</span>
						<span>{ browseMeta[ view ].title }</span>
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

			<div className="panel-scroll">
				{ view === 'element' && section ? (
					<ElementSlate
						key={ section.key }
						section={ section }
						activeState={ currentStateFor( section ) }
						onChangeState={ ( id ) => setActiveState( ( m ) => ( { ...m, [ section.key ]: id } ) ) }
						pulse={ selectPulse }
					/>
				) : view === 'list' ? (
					<DesignList
						sections={ sections }
						onOpen={ openSection }
						onNavigateTemplates={ () => setView( 'templates' ) }
						onNavigateColors={ () => setView( 'colors' ) }
						onNavigateCss={ () => setView( 'css' ) }
						onUndo={ handleUndo }
						onRedo={ handleRedo }
						canUndo={ store.canUndo() }
						canRedo={ store.canRedo() }
						undoLabel={ store.undoLabel() }
						redoLabel={ store.redoLabel() }
						onResetAll={ () =>
							setConfirm( {
								title: __( 'Reset all styles?', 'everest-forms' ),
								message: __(
									'Resets every element to default — palette, fonts, spacing, and more. You can undo right after.',
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
				) : view === 'templates' ? (
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
				) : view === 'colors' ? (
					<ColorsPane
						onToast={ showToast }
						onPreviewPalette={ ( colors ) => getActiveBridge()?.previewPalette( colors ) }
						onClearPreview={ () => getActiveBridge()?.revert() }
					/>
				) : (
					<CustomCssPane />
				) }
			</div>

			{ confirm && <ConfirmModal state={ confirm } onClose={ () => setConfirm( null ) } /> }

			{ previewHost &&
				createPortal(
					<PreviewPane
						forceClass={ forceClass }
						saving={ saving }
						dirty={ dirty }
						saveError={ saveError }
						saveErrorConflict={ saveErrorConflict }
						onSelect={ onSelectElement }
						onIframeClick={ onPreviewClick }
						toast={ toast }
						onToastPause={ pauseToast }
						onToastResume={ resumeToast }
					/>,
					previewHost
				) }

			{ /* Portaled to <body> so position:fixed isn't trapped by a transformed ancestor. */ }
			{ createPortal( <AiAssistant />, document.body ) }
		</div>
	);
}
