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
import { CustomCssPane, DesignList, ElementSlate, TemplatesPane } from './panes';
import { Popover, PopoverState } from './Popover';
import { PreviewPane } from './PreviewPane';
import { getActiveBridge, SelectionInfo } from './PreviewBridge';
import { DEVICE_LABELS, SECTION_ICONS, STATE_FORCE } from './constants';
import { useStore } from './store';
import { Section, Token } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;

type SubPane = 'design' | 'templates' | 'css';

interface Toast {
	msg: string;
	actLabel?: string;
	onAct?: () => void;
}

export function App() {
	const store = useStore();
	const [ subPane, setSubPane ] = React.useState< SubPane >( 'design' );
	const [ curSection, setCurSection ] = React.useState< string | null >( null );
	const [ activeState, setActiveState ] = React.useState< Record< string, string > >( {} );
	const [ popover, setPopover ] = React.useState< PopoverState | null >( null );
	const [ toast, setToast ] = React.useState< Toast | null >( null );
	const [ saving, setSaving ] = React.useState( false );
	const [ saveError, setSaveError ] = React.useState( '' );
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
		toastTimer.current = setTimeout( () => setToast( null ), 4200 );
	}, [] );

	/* ---- navigation ---- */
	const openSection = ( key: string ) => {
		setSubPane( 'design' );
		setCurSection( key );
	};
	const backToList = () => {
		setCurSection( null );
		getActiveBridge()?.clearSelection();
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
		try {
			const res = await apiFetch( {
				path: `${ store.settings.restBase }/${ store.settings.formId }`,
				method: 'POST',
				data: {
					record: store.toRecord(),
					base_updated_at: store.baseUpdatedAt,
					// "Apply Theme Style" persists to a separate per-form meta (not the record).
					apply_theme_style: store.applyThemeStyle,
				},
			} );
			store.markSaved( res.record );
		} catch ( e: any ) {
			const status = e && e.data && e.data.status;
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
			render: () => (
				<div>
					<div className="pop-title">{ __( 'Choose a colour palette', 'everest-forms' ) }</div>
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
									aria-pressed={ p.id === store.palette }
									title={ p.name }
									onMouseEnter={ () => getActiveBridge()?.previewPalette( p.colors ) }
									onMouseLeave={ () => getActiveBridge()?.revert() }
									onClick={ () => {
										if ( locked ) {
											window.open(
												'https://everestforms.net/pricing/?utm_source=style-customizer',
												'_blank'
											);
											return;
										}
										store.applyPalette( p.id );
										closePopover();
										showToast( {
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
										{ locked && <span className="pro">PRO</span> }
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
			<div className="subtabs" role="tablist" aria-label={ __( 'Style panel sections', 'everest-forms' ) }>
				{ ( [ 'design', 'templates', 'css' ] as SubPane[] ).map( ( id ) => (
					<button
						key={ id }
						type="button"
						role="tab"
						className="subtab"
						aria-selected={ subPane === id }
						onClick={ () => setSubPane( id ) }
					>
						{ id === 'design'
							? __( 'Design', 'everest-forms' )
							: id === 'templates'
							? __( 'Templates', 'everest-forms' )
							: __( 'Custom CSS', 'everest-forms' ) }
					</button>
				) ) }
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

			<div className="panel-scroll">
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
						/>
					) ) }

				{ subPane === 'templates' && (
					<TemplatesPane
						onPreview={ ( ov ) => getActiveBridge()?.previewValues( ov ) }
						onClearPreview={ () => getActiveBridge()?.revert() }
					/>
				) }

				{ subPane === 'css' && <CustomCssPane /> }
			</div>

			{ popover && <Popover state={ popover } onClose={ closePopover } /> }

			{ previewHost &&
				createPortal(
					<PreviewPane
						forceClass={ forceClass }
						saving={ saving }
						dirty={ dirty }
						saveError={ saveError }
						onInfo={ openInfo }
						onSelect={ onSelectElement }
						toast={ toast }
					/>,
					previewHost
				) }
		</div>
	);
}
