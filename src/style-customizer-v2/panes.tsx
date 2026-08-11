/**
 * Style Customizer v2 — panel panes: DesignList, ElementSlate (schema-driven drill-down),
 * TemplatesPane and CustomCssPane. All read/write the store.
 */
import React from 'react';
import { ColorPickerField, ControlRenderer } from './ControlRenderer';
import { SECTION_ICONS, SECTION_SUBTITLES, STATE_LABELS } from './constants';
import { HoverTip } from './HoverTip';
import { useStore } from './store';
import { BoxValue, DeviceBag, Section, Template, Token } from './types';
// The same "PRO" crown badge the builder's Fields sidebar uses.
import proIconUrl from '../../assets/images/icons/everest-form-pro-icon.png';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;
export const UPGRADE_URL = 'https://everestforms.net/pricing/?utm_source=style-customizer&utm_medium=panel';

function Icon( { inner }: { inner: string } ) {
	return (
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } dangerouslySetInnerHTML={ { __html: inner } } />
	);
}

/** The small "(i)" glyph the design's advisory banners lead with. */
function InfoNoteIcon() {
	return (
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
			<circle cx="12" cy="12" r="10" />
			<path d="M12 16v-4" />
			<path d="M12 8h.01" />
		</svg>
	);
}

/** "PRO" marker — the builder's own locked-field crown badge. */
export function ProCrown() {
	return <img className="pro-crown" src={ proIconUrl } alt="" aria-hidden="true" />;
}

/** Human label for a paletteMap slot key — shared by "Your Palette"'s edit rows. */
export function paletteSlotLabel( slot: string ): string {
	switch ( slot ) {
		case 'form_background':
			return __( 'Form background', 'everest-forms' );
		case 'field_background':
			return __( 'Field background', 'everest-forms' );
		case 'field_label':
			return __( 'Label', 'everest-forms' );
		case 'field_sublabel':
			return __( 'Sublabel', 'everest-forms' );
		case 'button_text':
			return __( 'Button text', 'everest-forms' );
		case 'button_background':
			return __( 'Button background', 'everest-forms' );
		default:
			return slot;
	}
}

/** One "Your Palette" edit row — the same swatch + hex + alpha-picker popover every element
 *  color control uses, so editing a slot feels identical to editing any other color. */
export function PaletteColorRow( {
	label,
	value,
	onChange,
	gradientable,
}: {
	label: string;
	value: string;
	onChange: ( color: string ) => void;
	gradientable?: boolean;
} ) {
	return (
		<div className="pal-edit-row">
			<span className="pal-edit-label">{ label }</span>
			<ColorPickerField label={ label } value={ value } onChange={ onChange } gradientable={ gradientable } />
		</div>
	);
}

/* --------------------------------------------------------------------- *
 * Colors (browse) — "Your Palette" (live, editable, Pro-tier) + presets +
 * conditional "Your palettes" custom list (view/apply/delete only).
 * --------------------------------------------------------------------- */

interface ColorsToast {
	msg: string;
	kind?: 'success' | 'info';
	actLabel?: string;
	onAct?: () => void;
}

export function ColorsPane( {
	onToast,
	onPreviewPalette,
	onClearPreview,
}: {
	onToast: ( t: ColorsToast ) => void;
	onPreviewPalette: ( colors: Record< string, string > ) => void;
	onClearPreview: () => void;
} ) {
	const store = useStore();
	const [ editing, setEditing ] = React.useState( false );
	const [ editSnapshot, setEditSnapshot ] = React.useState< { palette: string; colors: Record< string, string > } | null >( null );
	const [ confirmId, setConfirmId ] = React.useState< string | null >( null );
	const [ busy, setBusy ] = React.useState( false );

	const pro = store.proActive;
	const slots = Object.keys( store.paletteMap );
	const custom = store.customPalettes();
	const builtin = store.builtinPalettes();
	const palettesBase = store.settings.restBase.replace( /\/styles$/, '/style-palettes' );

	// Re-reads on every store version bump (useStore()), so this always reflects live edits —
	// the same mechanism that already keeps every other control in sync.
	const currentColors = store.currentPaletteColors();
	const appliedPaletteId = store.appliedPaletteId();
	const originPaletteId = store.originPaletteId();
	const matchedPaletteId = appliedPaletteId || originPaletteId;
	const matchedPalette = matchedPaletteId ? store.palettes.find( ( p ) => p.id === matchedPaletteId ) : null;
	// Also flag "Modified" when the colours never matched a named palette AT ALL but have drifted
	// from the raw schema defaults (e.g. hand-picked colours, or a Template's own colour set).
	const paletteModified = ! appliedPaletteId && ( !! originPaletteId || ! store.paletteAtDefault() );

	// Opening the editor snapshots the current state so "Cancel" can restore it exactly — every
	// other control in the panel is "live, undo to fix", but a dedicated Cancel button here (per
	// the design) needs a real, precise revert rather than N separate undo steps.
	const openEditor = () => {
		setEditSnapshot( { palette: store.palette, colors: { ...currentColors } } );
		setEditing( true );
	};
	const closeEditorKeep = () => {
		setEditing( false );
		setEditSnapshot( null );
	};
	const cancelEditor = () => {
		if ( editSnapshot ) {
			if ( editSnapshot.palette ) {
				store.applyPalette( editSnapshot.palette );
			} else {
				slots.forEach( ( slot ) => {
					store.setPaletteSlotColor( slot, editSnapshot.colors[ slot ] || '#ffffff', paletteSlotLabel( slot ), false );
				} );
			}
		}
		setEditing( false );
		setEditSnapshot( null );
	};

	const swatch = ( colors: Record< string, string > ) => (
		<span className="sw" aria-hidden="true">
			{ slots.map( ( slot ) => (
				<i key={ slot } style={ { background: colors[ slot ] } } />
			) ) }
		</span>
	);

	const applyPreset = ( p: ReturnType< typeof store.customPalettes >[ number ] ) => {
		if ( p.is_pro && ! pro ) {
			window.open( UPGRADE_URL, '_blank' );
			return;
		}
		store.applyPalette( p.id );
		onToast( {
			kind: 'success',
			msg: `${ __( 'Applied palette', 'everest-forms' ) } “${ p.name }”`,
			actLabel: __( 'Undo', 'everest-forms' ),
			onAct: () => store.undo(),
		} );
	};

	const deletePalette = async ( id: string ) => {
		setConfirmId( null );
		if ( ! apiFetch ) {
			return;
		}
		setBusy( true );
		try {
			const res = await apiFetch( { path: `${ palettesBase }/${ id }`, method: 'DELETE' } );
			store.setCustomPalettes( ( res && res.palettes ) || custom.filter( ( p ) => p.id !== id ) );
			onToast( { msg: __( 'Custom palette deleted.', 'everest-forms' ) } );
		} catch ( e ) {
			onToast( { msg: __( 'Could not delete the palette.', 'everest-forms' ) } );
		} finally {
			setBusy( false );
		}
	};

	const renderCard = ( p: ReturnType< typeof store.customPalettes >[ number ] ) => {
		const applied = p.id === appliedPaletteId;
		const modified = p.id === originPaletteId;
		const applyLocked = p.is_pro && ! pro;
		const canDelete = p.is_custom && pro;
		return (
			<div
				key={ p.id }
				className={
					'pal-card pal-card--wrap' +
					( confirmId === p.id ? ' is-confirming' : '' )
				}
			>
				<button
					type="button"
					className="pal-card-apply"
					role="option"
					aria-selected={ applied }
					title={ p.name }
					onMouseEnter={ () => onPreviewPalette( p.colors ) }
					onMouseLeave={ onClearPreview }
					onClick={ () => applyPreset( p ) }
				>
					<span className="pal-card-thumb">
						{ swatch( p.colors ) }
					</span>
					<span className="cap">
						<span className="cap-name">{ p.name }</span>
						{ p.is_custom && (
							<span className="predef-badge predef-badge--custom">{ __( 'Custom', 'everest-forms' ) }</span>
						) }
						{ ( applied || modified ) && (
							<span className="predef-badge predef-badge--base">{ __( 'Base', 'everest-forms' ) }</span>
						) }
						{ applyLocked && (
							<span className="pro" aria-label={ __( 'Pro', 'everest-forms' ) }>
								<ProCrown />
							</span>
						) }
					</span>
				</button>
				<span className="pal-card-tools">
					{ canDelete && (
						<button
							type="button"
							className="pal-tool pal-tool--danger"
							aria-label={ `${ __( 'Delete', 'everest-forms' ) } ${ p.name }` }
							title={ __( 'Delete palette', 'everest-forms' ) }
							onClick={ () => setConfirmId( p.id ) }
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
								<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
							</svg>
						</button>
					) }
				</span>
				{ confirmId === p.id && (
					<div className="pal-card-confirm" role="alertdialog" aria-label={ __( 'Delete palette?', 'everest-forms' ) }>
						<span>{ __( 'Delete?', 'everest-forms' ) }</span>
						<button type="button" className="pal-confirm-yes" onClick={ () => deletePalette( p.id ) } disabled={ busy }>
							{ __( 'Delete', 'everest-forms' ) }
						</button>
						<button type="button" className="pal-confirm-no" onClick={ () => setConfirmId( null ) }>
							{ __( 'Cancel', 'everest-forms' ) }
						</button>
					</div>
				) }
			</div>
		);
	};

	return (
		<div className="slate-anim">
			<p className="pane-note">
				<InfoNoteIcon />
				<span>{ __( 'For advanced customization, go to Elements or click the live preview.', 'everest-forms' ) }</span>
			</p>

			<div className="current-block">
				<div className="block-title">{ __( 'Your Palette', 'everest-forms' ) }</div>

				<div className={ 'pal-editor-card' + ( editing ? ' is-editing' : '' ) }>
					<div className="pal-editor-head">
						{ swatch( currentColors ) }
						{ pro ? (
							<button
								type="button"
								className="pal-editor-toggle"
								aria-label={ editing ? __( 'Close palette editor', 'everest-forms' ) : __( 'Edit palette', 'everest-forms' ) }
								title={ editing ? __( 'Close palette editor', 'everest-forms' ) : __( 'Edit palette', 'everest-forms' ) }
								onClick={ () => ( editing ? closeEditorKeep() : openEditor() ) }
							>
								{ editing ? (
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
										<path d="m18 15-6-6-6 6" />
									</svg>
								) : (
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
										<path d="M12 20h9" />
										<path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
									</svg>
								) }
							</button>
						) : (
							<span className="pro-badge" aria-label={ __( 'Pro', 'everest-forms' ) }>
								<ProCrown />
							</span>
						) }
					</div>

					{ pro && editing && (
						<>
							<div className="pal-edit-rows">
								{ slots.map( ( slot ) => (
									<PaletteColorRow
										key={ slot }
										label={ paletteSlotLabel( slot ) }
										value={ currentColors[ slot ] || '#ffffff' }
										onChange={ ( color ) => store.setPaletteSlotColor( slot, color, paletteSlotLabel( slot ) ) }
										gradientable={ store.slotGradientable( slot ) }
									/>
								) ) }
							</div>
							<div className="pal-edit-actions">
								<button type="button" className="pal-edit-save" onClick={ closeEditorKeep }>
									{ __( 'Save', 'everest-forms' ) }
								</button>
								<button type="button" className="pal-edit-cancel" onClick={ cancelEditor }>
									{ __( 'Cancel', 'everest-forms' ) }
								</button>
							</div>
						</>
					) }
				</div>

				<div className="pal-current-caption">
					<span className="pal-current-name">
						{ matchedPalette ? matchedPalette.name : __( 'Default', 'everest-forms' ) }
					</span>
					{ paletteModified && (
						<span className="predef-badge">{ __( 'Modified', 'everest-forms' ) }</span>
					) }
					{ !! appliedPaletteId && (
						<span className="predef-badge predef-badge--base">{ __( 'Base', 'everest-forms' ) }</span>
					) }
				</div>
			</div>

			<div className="block-title">{ __( 'Presets', 'everest-forms' ) }</div>
			<div className="pal-pop-grid" role="listbox" aria-label={ __( 'Preset palettes', 'everest-forms' ) }>
				{ [ ...custom, ...builtin ].map( renderCard ) }
			</div>
		</div>
	);
}

/* --------------------------------------------------------------------- *
 * Design list (home)
 * --------------------------------------------------------------------- */

export function DesignList( {
	sections,
	onOpen,
	onNavigateTemplates,
	onNavigateColors,
	onNavigateCss,
	onResetAll,
	onUndo,
	onRedo,
	canUndo,
	canRedo,
	undoLabel,
	redoLabel,
}: {
	sections: Section[];
	onOpen: ( key: string ) => void;
	onNavigateTemplates: () => void;
	onNavigateColors: () => void;
	onNavigateCss: () => void;
	onResetAll: () => void;
	onUndo: () => void;
	onRedo: () => void;
	canUndo: boolean;
	canRedo: boolean;
	undoLabel: string;
	redoLabel: string;
} ) {
	const store = useStore();

	// "Your Template"/"Your Palette" summary — value-driven off live store state (same pattern
	// TemplatesPane already uses for its ✓/"Modified" badges), so these never go stale.
	const appliedId = store.appliedTemplateId();
	const originId = store.originTemplateId();
	const matchedTplId = appliedId || originId;
	const matchedTpl = matchedTplId ? store.allTemplates().find( ( t ) => t.id === matchedTplId ) : null;
	const templateLabel = matchedTpl ? matchedTpl.name : __( 'Default', 'everest-forms' );
	// Also flag "Modified" when nothing ever matched a named template but styles have drifted
	// from the raw schema defaults.
	const templateModified = ! appliedId && ( !! originId || ! store.isAtSchemaDefault() );
	const templateIsBase = !! appliedId && ! templateModified;

	const appliedPaletteId = store.appliedPaletteId();
	const originPaletteId = store.originPaletteId();
	const matchedPaletteId = appliedPaletteId || originPaletteId;
	const matchedPalette = matchedPaletteId ? store.palettes.find( ( p ) => p.id === matchedPaletteId ) : null;
	const paletteLabel = matchedPalette ? matchedPalette.name : __( 'Default', 'everest-forms' );
	const paletteModified = ! appliedPaletteId && ( !! originPaletteId || ! store.paletteAtDefault() );
	const paletteIsBase = !! appliedPaletteId && ! paletteModified;
	const paletteColors = store.currentPaletteColors();

	return (
		<div className="slate-anim">
			<div className="uxrow">
				<div className="uxrow-history">
				<button
					type="button"
					className="uxbtn"
					disabled={ ! canUndo }
					aria-label={ canUndo ? `${ __( 'Undo', 'everest-forms' ) }: ${ undoLabel }` : __( 'Undo', 'everest-forms' ) }
					title={
						( canUndo
							? `${ __( 'Undo', 'everest-forms' ) }: ${ undoLabel }`
							: __( 'Undo', 'everest-forms' ) ) + ' (Ctrl+Z)'
					}
					onClick={ onUndo }
				>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 } aria-hidden="true">
						<path d="M3 10h10a5 5 0 0 1 0 10H7" />
						<path d="M7 6 3 10l4 4" />
					</svg>
				</button>
				<button
					type="button"
					className="uxbtn"
					disabled={ ! canRedo }
					aria-label={ canRedo ? `${ __( 'Redo', 'everest-forms' ) }: ${ redoLabel }` : __( 'Redo', 'everest-forms' ) }
					title={
						( canRedo
							? `${ __( 'Redo', 'everest-forms' ) }: ${ redoLabel }`
							: __( 'Redo', 'everest-forms' ) ) + ' (Ctrl+Shift+Z / Ctrl+Y)'
					}
					onClick={ onRedo }
				>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 } aria-hidden="true">
						<path d="M21 10H11a5 5 0 0 0 0 10h6" />
						<path d="m17 6 4 4-4 4" />
					</svg>
				</button>
				</div>
				<span className="uxrow-divider" aria-hidden="true" />
				<button
					type="button"
					className="uxbtn"
					title={ __( 'Reset all styles', 'everest-forms' ) }
					aria-label={ __( 'Reset all styles', 'everest-forms' ) }
					onClick={ onResetAll }
				>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
						<path d="M3 12a9 9 0 1 0 3-6.7" />
						<path d="M3 4v5h5" />
					</svg>
				</button>
			</div>
			<div className="block-title">{ __( 'Pre-defined', 'everest-forms' ) }</div>

			<div className="predefined-row">
				<button type="button" className="predef-card" onClick={ onNavigateTemplates }>
					<span className="predef-body">
						<span className="predef-kicker">{ __( 'Template', 'everest-forms' ) }</span>
						<span className="predef-name">
							{ templateLabel }
							{ templateModified && (
								<span className="predef-badge">{ __( 'Modified', 'everest-forms' ) }</span>
							) }
							{ templateIsBase && (
								<span className="predef-badge predef-badge--base">{ __( 'Base', 'everest-forms' ) }</span>
							) }
						</span>
					</span>
					<span className="predef-thumb">
						<TemplateThumb
							tpl={ { id: '__current__', name: templateLabel, image: '', palette: store.palette, tokens: store.tokens } }
						/>
					</span>
				</button>

				<button type="button" className="predef-card" onClick={ onNavigateColors }>
					<span className="predef-body">
						<span className="predef-kicker">{ __( 'Colors', 'everest-forms' ) }</span>
						<span className="predef-name">
							{ paletteLabel }
							{ paletteModified && (
								<span className="predef-badge">{ __( 'Modified', 'everest-forms' ) }</span>
							) }
							{ paletteIsBase && (
								<span className="predef-badge predef-badge--base">{ __( 'Base', 'everest-forms' ) }</span>
							) }
						</span>
					</span>
					<span className="sw predef-swatch" aria-hidden="true">
						{ Object.keys( store.paletteMap ).map( ( slot ) => (
							<i key={ slot } style={ { background: paletteColors[ slot ] } } />
						) ) }
					</span>
				</button>
			</div>

			<div className="theme-style-row">
				<span className="tsr-text">
					<b>
						{ __( 'Apply Theme Style', 'everest-forms' ) }
						<HoverTip
							className="info"
							label={ __( 'About Apply Theme Style', 'everest-forms' ) }
							tip={ __(
								'Matches only your active theme’s font. Colors, borders, and spacing always stay as you set them below, either way. Turn off to use your own Font family choice instead.',
								'everest-forms'
							) }
						>
							<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
								<path
									fillRule="evenodd"
									clipRule="evenodd"
									d="M21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21C16.9706 21 21 16.9706 21 12ZM23 12C23 18.0751 18.0751 23 12 23C5.92487 23 1 18.0751 1 12C1 5.92487 5.92487 1 12 1C18.0751 1 23 5.92487 23 12Z"
								/>
								<path d="M11 16V12C11 11.4477 11.4477 11 12 11C12.5523 11 13 11.4477 13 12V16C13 16.5523 12.5523 17 12 17C11.4477 17 11 16.5523 11 16Z" />
								<path d="M12.0098 7C12.5621 7 13.0098 7.44772 13.0098 8C13.0098 8.55228 12.5621 9 12.0098 9H12C11.4477 9 11 8.55228 11 8C11 7.44772 11 7 12 7H12.0098Z" />
							</svg>
						</HoverTip>
					</b>
				</span>
				<button
					type="button"
					className="switch"
					role="switch"
					aria-checked={ store.applyThemeStyle }
					aria-label={ __( 'Apply Theme Style', 'everest-forms' ) }
					onClick={ () => store.setApplyThemeStyle( ! store.applyThemeStyle ) }
				/>
			</div>

			<div className="block-title">{ __( 'Elements', 'everest-forms' ) }</div>
			<p className="hintline">
				<Icon inner='<path d="M3 3l7 17 2-7 7-2z"/>' />
				<span>
					{ __( 'Tip: pick an element to style it, or', 'everest-forms' ) }{ ' ' }
					<b>{ __( 'click it in the live preview.', 'everest-forms' ) }</b>
				</span>
			</p>
			<div className="ellist">
				{ sections.map( ( s ) => {
					const locked = s.tier === 'pro' && ! store.proActive;
					return (
						<button
							key={ s.key }
							type="button"
							className={ 'elrow' + ( store.changedInSection( s.key ) ? ' dirty' : '' ) + ( locked ? ' locked' : '' ) }
							onClick={ () => onOpen( s.key ) }
						>
							<span className="ic">
								<Icon inner={ SECTION_ICONS[ s.key ] || '' } />
							</span>
							<span className="tx">
								<b>{ s.title }</b>
								<small>{ SECTION_SUBTITLES[ s.key ] || s.hint }</small>
							</span>
							{ locked ? (
								<span className="pro-badge" aria-label={ __( 'Pro feature', 'everest-forms' ) }>
									<ProCrown />
								</span>
							) : (
								<span className="dot" aria-hidden="true" />
							) }
							<span className="chev" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 }>
									<path d="m9 6 6 6-6 6" />
								</svg>
							</span>
						</button>
					);
				} ) }
			</div>

			<div className="block-title">{ __( 'Advanced', 'everest-forms' ) }</div>
			<div className="ellist">
				<button type="button" className="elrow" onClick={ onNavigateCss }>
					<span className="ic">
						<Icon inner='<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>' />
					</span>
					<span className="tx">
						<b>{ __( 'Custom CSS', 'everest-forms' ) }</b>
						<small>{ __( 'Add your own CSS styles', 'everest-forms' ) }</small>
					</span>
					<span className="chev" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 }>
							<path d="m9 6 6 6-6 6" />
						</svg>
					</span>
				</button>
			</div>
		</div>
	);
}

/* --------------------------------------------------------------------- *
 * Element slate (drill-down body)
 * --------------------------------------------------------------------- */

interface GroupedTokens {
	heading: string;
	tokens: Token[];
}

function groupTokens( tokens: Token[] ): GroupedTokens[] {
	const out: GroupedTokens[] = [];
	let current: string | null = null;
	tokens.forEach( ( t ) => {
		if ( t.group !== current ) {
			current = t.group;
			out.push( { heading: current, tokens: [] } );
		}
		out[ out.length - 1 ].tokens.push( t );
	} );
	return out;
}

export function ElementSlate( {
	section,
	activeState,
	onChangeState,
	pulse,
}: {
	section: Section;
	activeState: string | null;
	onChangeState: ( id: string ) => void;
	pulse: number;
} ) {
	const store = useStore();
	const bodyRef = React.useRef< HTMLDivElement >( null );
	const tabs = section.states || section.variants || null;
	const first = tabs ? tabs[ 0 ] : null;
	const act = activeState || first;

	// When opened via a preview click (pulse changes), scroll the panel up and briefly flash.
	React.useEffect( () => {
		const el = bodyRef.current;
		if ( ! el || ! pulse ) {
			return;
		}
		const scroller = el.closest( '.panel-scroll' );
		if ( scroller ) {
			scroller.scrollTop = 0;
		}
		el.classList.remove( 'flash' );
		// Force reflow so the animation restarts on repeated selections.
		void el.offsetWidth;
		el.classList.add( 'flash' );
	}, [ pulse ] );

	// Background sub-options only matter once an image is set.
	const bgImageSet = !! ( store.tokens[ 'wrap.bgImage' ] && store.tokens[ 'wrap.bgImage' ].desktop );

	const visible = store.schema.filter(
		( t ) => t.section === section.key && ! t.hidden && ( ! t.show_when_image || bgImageSet )
	);

	// On a state/variant tab, show only the controls that belong to that state; shared controls
	// live on the first tab.
	const enabledByState = tabs
		? visible.filter( ( t ) => ( t.state ? t.state === act : act === first ) )
		: visible;

	// Dependency dimming: a border-style set to "none" disables its width/colour deps.
	const dimmedByDep = new Set< string >();
	const depHints: Record< string, string > = {};
	visible.forEach( ( t ) => {
		if ( t.deps && String( store.resolve( t.key ) ) === 'none' ) {
			t.deps.forEach( ( k ) => dimmedByDep.add( k ) );
			depHints[ t.key ] = __( 'Width & color have no effect while the border style is None.', 'everest-forms' );
		}
	} );

	const locked = ( t: Token ) => t.tier === 'pro' && ! store.proActive;
	const hasLocked = visible.some( locked );

	// A whole Pro section on a free site: render a locked upgrade teaser instead of an empty slate.
	const sectionLocked = section.tier === 'pro' && ! store.proActive;

	const renderControl = ( t: Token ) => (
		<ControlRenderer
			key={ t.key }
			token={ t }
			store={ store }
			dimmed={ dimmedByDep.has( t.key ) || locked( t ) }
			depHint={ depHints[ t.key ] }
		/>
	);

	const renderGroups = ( tokens: Token[] ) =>
		groupTokens( tokens ).map( ( g, i ) => (
			<div className="grp" key={ i }>
				{ g.heading && <div className="grp-h">{ g.heading }</div> }
				{ section.key === 'form' && g.heading === 'Background' && (
					<p className="hintline">
						<Icon inner='<circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8h.01"/>' />
						{ __( 'Background color comes from your color palette.', 'everest-forms' ) }
					</p>
				) }
				{ g.tokens.map( renderControl ) }
			</div>
		) );

	if ( sectionLocked ) {
		return (
			<div id="elBody" className="slate-anim" ref={ bodyRef }>
				<div className="slate-title-row">
					<div className="slate-title-main">
						<span className="slate-title-ic">
							<Icon inner={ SECTION_ICONS[ section.key ] || '' } />
						</span>
						<h2 className="slate-title">{ section.title }</h2>
					</div>
				</div>
				<ProSectionTeaser section={ section } />
			</div>
		);
	}

	return (
		<div id="elBody" className="slate-anim" ref={ bodyRef }>
			<div className="slate-title-row">
				<div className="slate-title-main">
					<span className="slate-title-ic">
						<Icon inner={ SECTION_ICONS[ section.key ] || '' } />
					</span>
					<h2 className="slate-title">{ section.title }</h2>
				</div>
				<button type="button" className="uxbtn" title={ __( 'Reset this section', 'everest-forms' ) } aria-label={ __( 'Reset this section', 'everest-forms' ) + ' — ' + section.title } onClick={ () => store.resetSection( section.key ) }>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
						<path d="M3 12a9 9 0 1 0 3-6.7" />
						<path d="M3 4v5h5" />
					</svg>
				</button>
			</div>

			{ hasLocked && (
				<div className="pro-lock">
					<span className="pro-lock-ic" aria-hidden="true">
						<Icon inner='<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>' />
					</span>
					<span>
						{ __( 'This is a Pro feature.', 'everest-forms' ) }{ ' ' }
						<a href={ UPGRADE_URL } target="_blank" rel="noreferrer">
							{ __( 'Upgrade to Pro', 'everest-forms' ) }
						</a>{ ' ' }
						{ __( 'to unlock it.', 'everest-forms' ) }
					</span>
				</div>
			) }

			{ tabs && (
				<div
					className="state-seg"
					role="tablist"
					aria-label={ section.title + ' ' + __( 'variants', 'everest-forms' ) }
					onKeyDown={ ( e ) => {
						if ( e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' ) {
							return;
						}
						e.preventDefault();
						const idx = tabs.indexOf( act as string );
						const next = tabs[ ( idx + ( e.key === 'ArrowRight' ? 1 : tabs.length - 1 ) ) % tabs.length ];
						onChangeState( next );
						document.getElementById( `scv2-state-${ section.key }-${ next }` )?.focus();
					} }
				>
					{ tabs.map( ( id ) => (
						<button
							key={ id }
							id={ `scv2-state-${ section.key }-${ id }` }
							type="button"
							role="tab"
							aria-selected={ id === act }
							aria-controls={ `scv2-state-panel-${ section.key }` }
							tabIndex={ id === act ? 0 : -1 }
							onClick={ () => onChangeState( id ) }
						>
							{ STATE_LABELS[ id ] || id }
						</button>
					) ) }
				</div>
			) }

			<div id={ `scv2-state-panel-${ section.key }` } role={ tabs ? 'tabpanel' : undefined } aria-labelledby={ tabs ? `scv2-state-${ section.key }-${ act }` : undefined }>
				{ renderGroups( enabledByState ) }
			</div>
		</div>
	);
}

/** Locked upgrade teaser — shown wherever a Pro feature is surfaced on a free site. */
export function ProTeaser( { title, text }: { title: string; text: string } ) {
	return (
		<div className="pro-teaser">
			<span className="pro-teaser-ic" aria-hidden="true">
				<Icon inner='<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>' />
			</span>
			<h4 className="pro-teaser-title">{ title }</h4>
			<p className="pro-teaser-text">{ text }</p>
			<a className="pro-teaser-btn" href={ UPGRADE_URL } target="_blank" rel="noreferrer">
				{ __( 'Upgrade to Pro', 'everest-forms' ) }
			</a>
		</div>
	);
}

/** Section-level teaser (a whole Pro design section opened on free). */
function ProSectionTeaser( { section }: { section: Section } ) {
	return (
		<ProTeaser
			// translators: %s: section name, e.g. "Messages".
			title={ ( __( '%s styling is a Pro feature', 'everest-forms' ) as string ).replace( '%s', section.title ) }
			text={ section.hint }
		/>
	);
}

/* --------------------------------------------------------------------- *
 * Templates
 * --------------------------------------------------------------------- */

/** The desktop value of a token bag, for hover-preview + thumbnails. */
function desktopOf( bag: DeviceBag | undefined ): any {
	return bag && bag.desktop !== undefined ? bag.desktop : undefined;
}

/**
 * Flatten a template's per-device token bags into a { key: desktopValue } map covering every
 * schema key (not just the ones the template sets), mirroring what `store.applyTemplate()` does
 * on click so hovering previews exactly what clicking would produce.
 */
function flattenForPreview( tokens: Record< string, DeviceBag >, schema: Token[] ): Record< string, any > {
	const out: Record< string, any > = {};
	schema.forEach( ( t ) => {
		out[ t.key ] = tokens && tokens[ t.key ] !== undefined ? desktopOf( tokens[ t.key ] ) : t.default;
	} );
	return out;
}

function radiusOf( bag: DeviceBag | undefined, fallback: number ): number {
	const v = desktopOf( bag ) as BoxValue | undefined;
	return v && typeof v === 'object' ? Number( v.top ) || 0 : fallback;
}

/** Template thumbnail: real screenshot when it loads, else a live token-driven mini-form. */
function TemplateThumb( { tpl }: { tpl: Template } ) {
	const [ imgOk, setImgOk ] = React.useState( !! tpl.image );
	const th = templateThumb( tpl );
	if ( tpl.image && imgOk ) {
		return (
			<span className="thumb thumb-has-img">
				<img src={ tpl.image } alt="" loading="lazy" onError={ () => setImgOk( false ) } />
			</span>
		);
	}
	return (
		<span className="thumb" style={ th.thumb }>
			<span className="l" style={ th.line } />
			<span className="f" style={ th.field } />
			<span className="l l2" style={ th.line } />
			<span className="f f2" style={ th.field } />
			<span className="b" style={ th.button } />
		</span>
	);
}

/** Build a small live thumbnail from a template's migrated tokens — a mini "Name / Message /
 *  Submit" mock-up in the template's own colours, so a custom template reads as recognizably
 *  itself in the grid, the same way a built-in's screenshot does. */
function templateThumb( tpl: Template ) {
	const t = tpl.tokens;
	const bg = desktopOf( t[ 'wrap.bg' ] ) || '#ffffff';
	const btnBg = desktopOf( t[ 'btn.bg' ] ) || '#3b82f6';
	const btnText = desktopOf( t[ 'btn.color' ] ) || '#ffffff';
	const inputBg = desktopOf( t[ 'input.bg' ] ) || '#ffffff';
	const border = desktopOf( t[ 'input.borderC' ] ) || '#d8dae2';
	const label = desktopOf( t[ 'label.color' ] ) || '#1f2433';
	const fieldRadius = radiusOf( t[ 'input.radius' ], 6 );
	const btnRadius = radiusOf( t[ 'btn.radius' ], 6 );
	return {
		thumb: { background: bg } as React.CSSProperties,
		line: { background: label } as React.CSSProperties,
		field: { background: inputBg, borderColor: border, borderRadius: fieldRadius } as React.CSSProperties,
		button: { background: btnBg, color: btnText, borderRadius: btnRadius } as React.CSSProperties,
	};
}

/** One template card — thumbnail, name, applied/locked/delete affordances. Used for both the
 *  "Your templates" and built-in grids so the two stay visually identical. */
function TemplateCard( {
	tpl,
	applied,
	modified,
	basedOn,
	locked,
	disabled,
	onPreview,
	onClearPreview,
	onApply,
	onExport,
	confirmingDelete,
	onRequestDelete,
	onConfirmDelete,
	onCancelDelete,
}: {
	tpl: Template;
	applied: boolean;
	/** The form was applied FROM this template but has since been edited — show an honest hint
	 *  instead of a ✓ (its styles no longer exactly match this template). */
	modified?: boolean;
	/** Name of the built-in template this (custom) template exactly derives from, if any. */
	basedOn?: string;
	locked: boolean;
	/** True while a DIFFERENT template is being edited — every action on this card is inert. */
	disabled?: boolean;
	onPreview: () => void;
	onClearPreview: () => void;
	onApply: () => void;
	/** Downloads this template's palette + tokens as a .json file — e.g. so a template built on
	 *  one site can be handed off and added as a bundled default elsewhere, with no server/DB
	 *  access needed. User templates only (see its call site). */
	onExport?: () => void;
	confirmingDelete?: boolean;
	onRequestDelete?: () => void;
	onConfirmDelete?: () => void;
	onCancelDelete?: () => void;
} ) {
	return (
		<div className={ 'tpl-wrap' + ( confirmingDelete ? ' is-confirming' : '' ) }>
			<button
				type="button"
				className={
					'tpl' +
					( tpl.custom ? ' tpl-user' : '' ) +
					( locked ? ' tpl-locked' : '' ) +
					( applied ? ' tpl-applied' : '' ) +
					( modified ? ' tpl-modified' : '' ) +
					( disabled ? ' tpl-disabled' : '' )
				}
				aria-pressed={ applied }
				aria-disabled={ disabled }
				onMouseEnter={ onPreview }
				onMouseLeave={ onClearPreview }
				onFocus={ onPreview }
				onBlur={ onClearPreview }
				onClick={ disabled ? undefined : onApply }
			>
				<span className="tpl-thumb-box">
					{ locked && (
						<span className="tpl-pro" aria-label={ __( 'Pro template', 'everest-forms' ) }>
							<ProCrown />
						</span>
					) }
					{ locked && (
						<span className="tpl-lock-veil" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 }>
								<rect x="4" y="11" width="16" height="9" rx="2" />
								<path d="M8 11V7a4 4 0 0 1 8 0v4" />
							</svg>
						</span>
					) }
					<TemplateThumb tpl={ tpl } />
				</span>
				<span className="cap">
					<span className="cap-name">{ tpl.name }</span>
					{ tpl.custom && <span className="tpl-mod tpl-mod--custom">{ __( 'Custom', 'everest-forms' ) }</span> }
					{ ( applied || modified ) && <span className="tpl-mod tpl-mod--base">{ __( 'Base', 'everest-forms' ) }</span> }
				</span>
				{ basedOn && (
					<span className="tpl-parent">
						{ /* translators: %s: the built-in template this custom one derives from. */ }
						{ ( __( 'Based on %s', 'everest-forms' ) as string ).replace( '%s', basedOn ) }
					</span>
				) }
			</button>
			<span className="tpl-card-tools">
				{ onExport && (
					<button
						type="button"
						className="tpl-tool"
						disabled={ disabled }
						aria-label={ `${ __( 'Export', 'everest-forms' ) } ${ tpl.name }` }
						title={ __( 'Export as .json', 'everest-forms' ) }
						onClick={ ( e ) => {
							e.stopPropagation();
							onExport();
						} }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<path d="M12 3v12" />
							<path d="m7 10 5 5 5-5" />
							<path d="M5 21h14" />
						</svg>
					</button>
				) }
				{ onRequestDelete && (
					<button
						type="button"
						className="tpl-tool tpl-tool--danger"
						disabled={ disabled }
						aria-label={ `${ __( 'Delete', 'everest-forms' ) } ${ tpl.name }` }
						title={ __( 'Delete template', 'everest-forms' ) }
						onClick={ ( e ) => {
							e.stopPropagation();
							onRequestDelete();
						} }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
						</svg>
					</button>
				) }
			</span>
			{ confirmingDelete && (
				<div className="tpl-card-confirm" role="alertdialog" aria-label={ __( 'Delete template?', 'everest-forms' ) }>
					<span>{ __( 'Delete?', 'everest-forms' ) }</span>
					<button type="button" className="tpl-confirm-yes" onClick={ onConfirmDelete }>
						{ __( 'Delete', 'everest-forms' ) }
					</button>
					<button type="button" className="tpl-confirm-no" onClick={ onCancelDelete }>
						{ __( 'Cancel', 'everest-forms' ) }
					</button>
				</div>
			) }
		</div>
	);
}

export function TemplatesPane( {
	onPreview,
	onClearPreview,
	onApplied,
}: {
	onPreview: ( overrides: Record< string, any > ) => void;
	onClearPreview: () => void;
	onApplied: ( name: string ) => void;
} ) {
	const store = useStore();
	const [ confirmDeleteId, setConfirmDeleteId ] = React.useState< string | null >( null );

	const templates = store.allTemplates();
	// Memoize the flattened override maps so hover doesn't re-flatten on every mouse event.
	const flat = React.useMemo(
		() => Object.fromEntries( templates.map( ( tpl ) => [ tpl.id, flattenForPreview( tpl.tokens, store.schema ) ] ) ),
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ templates, store ]
	);

	// Value-driven template state, recomputed whenever the store version bumps.
	const ver = store.getVersion();
	const appliedId = React.useMemo( () => store.appliedTemplateId(), [ store, ver ] );
	const originId = React.useMemo( () => store.originTemplateId(), [ store, ver ] );
	const parentNames = React.useMemo( () => {
		const map: Record< string, string > = {};
		templates.forEach( ( tpl ) => {
			const pid = store.templateParentId( tpl );
			if ( pid ) {
				const parent = store.templates.find( ( b ) => b.id === pid );
				if ( parent ) {
					map[ tpl.id ] = parent.name;
				}
			}
		} );
		return map;
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ templates, store, ver ] );

	// "Your Template" card — same value-driven match as the ✓/"Modified" badges above, fed into
	// the existing TemplateThumb live-mockup renderer via a synthetic, always-current object.
	const matchedTplId = appliedId || originId;
	const matchedTpl = matchedTplId ? templates.find( ( t ) => t.id === matchedTplId ) : null;
	const yourTemplateName = matchedTpl ? matchedTpl.name : __( 'Default', 'everest-forms' );
	const yourTemplateModified = ! appliedId && ( !! originId || ! store.isAtSchemaDefault() );
	const yourTemplateIsBase = !! appliedId && ! yourTemplateModified;

	const templatesBase = store.settings.restBase.replace( /\/styles$/, '/style-templates' );

	// Downloads a "Your templates" entry as a .json file — palette + tokens, already in the
	// exact shape a bundled default template needs (see Templates::V2_JSON_PATH), so handing
	// this file to a developer is a straight paste-in, no manual v1 conversion or DB/CLI access
	// required on either side.
	const exportTemplate = ( tpl: Template ) => {
		const payload = {
			name: tpl.name,
			palette: tpl.palette || '',
			tokens: tpl.tokens,
		};
		const blob = new Blob( [ JSON.stringify( payload, null, 2 ) ], { type: 'application/json' } );
		const url  = URL.createObjectURL( blob );
		const slug = tpl.name.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ) || 'template';
		const link = document.createElement( 'a' );
		link.href     = url;
		link.download = `${ slug }.json`;
		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
		URL.revokeObjectURL( url );
	};

	const deleteTemplate = async ( id: string ) => {
		setConfirmDeleteId( null );
		if ( ! apiFetch ) {
			return;
		}
		store.removeUserTemplate( id ); // optimistic
		try {
			await apiFetch( { path: `${ templatesBase }/${ id }`, method: 'DELETE' } );
		} catch ( e ) {
			// The list already reflects the removal; a reload restores it if the server rejected.
		}
	};

	const applyTpl = ( tpl: Template, locked: boolean ) => {
		if ( locked ) {
			window.open( UPGRADE_URL, '_blank' );
			return;
		}
		store.applyTemplate( tpl.id, tpl.tokens, tpl.palette );
		onApplied( tpl.name );
	};

	const renderGrid = ( list: Template[] ) => (
		<div className="tpls">
			{ list.map( ( tpl ) => {
				const locked = !! tpl.is_pro && ! store.proActive;
				const withDelete = !! tpl.custom;
				return (
					<TemplateCard
						key={ tpl.id }
						tpl={ tpl }
						applied={ appliedId === tpl.id }
						modified={ originId === tpl.id }
						basedOn={ parentNames[ tpl.id ] }
						locked={ locked }
						onPreview={ () => onPreview( flat[ tpl.id ] ) }
						onClearPreview={ onClearPreview }
						onApply={ () => applyTpl( tpl, locked ) }
						onExport={ withDelete ? () => exportTemplate( tpl ) : undefined }
						confirmingDelete={ confirmDeleteId === tpl.id }
						onRequestDelete={ withDelete ? () => setConfirmDeleteId( tpl.id ) : undefined }
						onConfirmDelete={ withDelete ? () => deleteTemplate( tpl.id ) : undefined }
						onCancelDelete={ withDelete ? () => setConfirmDeleteId( null ) : undefined }
					/>
				);
			} ) }
		</div>
	);

	return (
		<div className="slate-anim">
			<div className="current-block">
				<div className="block-title">{ __( 'Your Template', 'everest-forms' ) }</div>
				<div className="tpls">
					<div className="tpl-wrap tpl-current">
						<span className="tpl tpl-static" aria-label={ __( 'Your current form style', 'everest-forms' ) }>
							<span className="tpl-thumb-box">
								<TemplateThumb
									tpl={ { id: '__current__', name: yourTemplateName, image: '', palette: store.palette, tokens: store.tokens } }
								/>
							</span>
							<span className="cap">
								<span className="cap-name">{ yourTemplateName }</span>
								{ yourTemplateModified && <span className="tpl-mod">{ __( 'Modified', 'everest-forms' ) }</span> }
								{ yourTemplateIsBase && <span className="tpl-mod tpl-mod--base">{ __( 'Base', 'everest-forms' ) }</span> }
							</span>
						</span>
					</div>
				</div>
			</div>

			<div className="block-title">{ __( 'Presets', 'everest-forms' ) }</div>
			<p className="pane-note">
				<b>{ __( 'Hover a card to preview it live', 'everest-forms' ) }</b>{ ' ' }
				— { __( 'click to apply (you can always undo).', 'everest-forms' ) }
			</p>
			{ /* Legacy set stays in store.templates (see Templates::load_legacy()) for badge matching above; just not offered here. */ }
			{ renderGrid(
				[ ...store.userTemplates, ...store.templates.filter( ( tpl ) => ! tpl.legacy ) ].sort( ( a, b ) => {
					if ( !! a.custom !== !! b.custom ) {
						return a.custom ? -1 : 1;
					}
					return ( a.is_pro ? 1 : 0 ) - ( b.is_pro ? 1 : 0 );
				} )
			) }
		</div>
	);
}

/* --------------------------------------------------------------------- *
 * Custom CSS
 * --------------------------------------------------------------------- */

const CSS_CHIPS = [
	'.evf-container',
	'.evf-submit-container button',
	'input, textarea, select',
	'label.evf-field-label',
	'.everest-forms-notice--success',
];

const CSS_EXAMPLE = `.evf-submit-container button {
	letter-spacing: .04em;
	text-transform: uppercase;
}`;

export function CustomCssPane() {
	const store = useStore();
	const ref = React.useRef< HTMLTextAreaElement >( null );

	const insert = ( snippet: string ) => {
		const el = ref.current;
		if ( ! el ) {
			return;
		}
		const start = el.selectionStart;
		const value = el.value.slice( 0, start ) + snippet + el.value.slice( el.selectionEnd );
		store.setCustomCss( value );
		el.value = value;
		el.focus();
	};

	const len = ( store.customCss || '' ).length;

	return (
		<div className="slate-anim">
			<div className="block-title">{ __( 'Custom CSS', 'everest-forms' ) }</div>
			<p className="pane-note">
				{ __( 'Applied live as you type —', 'everest-forms' ) } <b>{ __( 'scoped to this form', 'everest-forms' ) }</b>{ ' ' }
				{ __( 'on save, so nothing leaks elsewhere. Click a selector to insert it:', 'everest-forms' ) }
			</p>
			<div className="chips">
				{ CSS_CHIPS.map( ( c ) => (
					<button key={ c } type="button" onClick={ () => insert( c + ' {\n\t\n}\n' ) }>
						{ c }
					</button>
				) ) }
			</div>
			<textarea
				ref={ ref }
				className="csscode"
				spellCheck={ false }
				defaultValue={ store.customCss || '' }
				aria-label={ __( 'Custom CSS', 'everest-forms' ) }
				placeholder={ '.evf-submit-container button {\n\tletter-spacing: .04em;\n}' }
				onInput={ ( e ) => store.setCustomCss( ( e.target as HTMLTextAreaElement ).value ) }
			/>
			<div className="css-status">
				<span>{ len ? `${ len } ${ __( 'characters', 'everest-forms' ) }` : __( 'No custom CSS', 'everest-forms' ) }</span>
				<button type="button" className="css-example-btn" onClick={ () => insert( CSS_EXAMPLE ) }>
					{ __( 'Insert example', 'everest-forms' ) }
				</button>
			</div>
		</div>
	);
}
