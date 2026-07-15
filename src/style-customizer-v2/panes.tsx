/**
 * Style Customizer v2 — panel panes.
 *
 * DesignList (element rows + palette select), ElementSlate (the schema-driven drill-down body
 * with variant/state tabs + grouped cards), TemplatesPane and CustomCssPane. All read/write
 * the store; the ElementSlate is the heart of Phase 2 (every control rendered from schema).
 */
import React from 'react';
import { ControlRenderer } from './ControlRenderer';
import { SECTION_ICONS, SECTION_SUBTITLES, STATE_LABELS } from './constants';
import { useStore } from './store';
import { BoxValue, DeviceBag, Section, Template, Token } from './types';
// The exact same "PRO" crown badge the builder's Fields sidebar uses (not a redrawn approximation)
// — file-loader (webpack.config.js) emits it and returns its built URL, same pattern already used
// by src/dashboard/screens/Analytics/Analytics.js for analytics-preview.png.
import proIconUrl from '../../assets/images/icons/everest-form-pro-icon.png';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;
export const UPGRADE_URL = 'https://everestforms.net/pricing/?utm_source=style-customizer&utm_medium=panel';

function Icon( { inner }: { inner: string } ) {
	return (
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } dangerouslySetInnerHTML={ { __html: inner } } />
	);
}

/**
 * "PRO" marker — the builder's own locked-field crown badge (the Fields sidebar's corner icon),
 * not text, so a locked item reads as the exact same marker everywhere in Everest Forms.
 */
export function ProCrown() {
	return <img className="pro-crown" src={ proIconUrl } alt="" aria-hidden="true" />;
}

/** The six palette-slot colours in a fixed order, for swatch rendering. */
function paletteSwatchColors( colors: Record< string, string >, paletteMap: Record< string, string[] > ): string[] {
	return Object.keys( paletteMap ).map( ( slot ) => colors[ slot ] || '#e5e7eb' );
}

/* --------------------------------------------------------------------- *
 * Design list (home)
 * --------------------------------------------------------------------- */

export function DesignList( {
	sections,
	onOpen,
	onOpenPalette,
	paletteOpen,
	onResetAll,
}: {
	sections: Section[];
	onOpen: ( key: string ) => void;
	onOpenPalette: ( anchor: HTMLElement ) => void;
	paletteOpen: boolean;
	onResetAll: () => void;
} ) {
	const store = useStore();
	const activePalette = store.palettes.find( ( p ) => p.id === store.palette );
	// No active palette (custom, or detached by a manual edit — see store.ts setTokenValue): show
	// the REAL current colour for each palette slot (its first driven token), not a static
	// placeholder — otherwise this row can visibly lie about the form's actual current colours.
	const swatches = activePalette
		? paletteSwatchColors( activePalette.colors, store.paletteMap )
		: Object.values( store.paletteMap ).map( ( keys ) =>
			keys && keys[ 0 ] ? String( store.resolve( keys[ 0 ] ) ) : '#e5e7eb'
		  );

	return (
		<div className="slate-anim">
			<div className="block-title">{ __( 'Color palette', 'everest-forms' ) }</div>
			<button
				type="button"
				className={ 'pal-select' + ( paletteOpen ? ' is-open' : '' ) }
				aria-haspopup="dialog"
				aria-expanded={ paletteOpen }
				aria-label={
					paletteOpen
						? __( 'Close colour palette', 'everest-forms' )
						: __( 'Choose colour palette', 'everest-forms' )
				}
				onClick={ ( e ) => onOpenPalette( e.currentTarget ) }
			>
				<span className="sw" aria-hidden="true">
					{ swatches.map( ( c, i ) => (
						<i key={ i } style={ { background: c } } />
					) ) }
				</span>
				<span className="nm">{ activePalette ? activePalette.name : __( 'Custom', 'everest-forms' ) }</span>
				{ /* When the popover is open, the opening chevron becomes a close (×) affordance —
				     clicking the same trigger toggles the popover shut. */ }
				<span className={ 'chev' + ( paletteOpen ? ' as-close' : '' ) } aria-hidden="true">
					{ paletteOpen ? (
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 }>
							<path d="M18 6 6 18M6 6l12 12" />
						</svg>
					) : (
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 }>
							<path d="m6 9 6 6 6-6" />
						</svg>
					) }
				</span>
			</button>

			<div className="block-title">{ __( 'Elements', 'everest-forms' ) }</div>
			<p className="hintline">
				<Icon inner='<path d="M3 3l7 17 2-7 7-2z"/>' />
				{ __( 'Tip: pick an element to style it, or click it in the live preview.', 'everest-forms' ) }
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

			{ /* Form-wide baseline: a secondary, rarely-changed setting → kept at the bottom so the
			     palette + elements (the primary actions) lead the panel. */ }
			<div className="theme-style-row">
				<span className="tsr-text">
					<b>{ __( 'Apply Theme Style', 'everest-forms' ) }</b>
					<small>
						{ __(
							'Let your active theme style this form. Turn off to use Everest Forms’ default form style.',
							'everest-forms'
						) }{ ' ' }
						{ __( 'Your colours, spacing and fonts above always apply on top of either.', 'everest-forms' ) }
					</small>
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

			<button type="button" className="reset-all-link" onClick={ onResetAll }>
				{ __( 'Reset all styles', 'everest-forms' ) }
			</button>
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
	onBadgeClick,
	pulse,
}: {
	section: Section;
	activeState: string | null;
	onChangeState: ( id: string ) => void;
	onBadgeClick: ( token: Token, anchor: HTMLElement ) => void;
	pulse: number;
} ) {
	const store = useStore();
	const bodyRef = React.useRef< HTMLDivElement >( null );
	const tabs = section.states || section.variants || null;
	const first = tabs ? tabs[ 0 ] : null;
	const act = activeState || first;
	const [ filter, setFilter ] = React.useState( '' );

	// Reset the in-slate filter whenever a different section opens (a fresh `key` remounts this
	// component, but this also covers any future path that reuses the instance).
	React.useEffect( () => {
		setFilter( '' );
	}, [ section.key ] );

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

	// On a state/variant tab, show ONLY the controls that belong to that state. Shared
	// (stateless) controls live on the first tab (Normal / Label) so a state tab such as
	// Focus or Hover stays context-clean — no greyed-out "shared" controls that read as
	// disabled and don't apply to the state.
	const enabledByState = tabs
		? visible.filter( ( t ) => ( t.state ? t.state === act : act === first ) )
		: visible;

	// A lightweight in-slate filter — only shown on the two large sections (Text/Messages:
	// ~27-28 controls across 3-4 tabs) where scrolling a flat list is genuinely long. A much
	// smaller re-add than the global command-palette search that was deliberately cut. Inputs
	// (`fields`) is excluded by design — its Normal/Focus tabs stay short enough to scan directly.
	const SHOW_FILTER_ABOVE = 12;
	const showFilter = section.key !== 'fields' && enabledByState.length > SHOW_FILTER_ABOVE;
	const q = filter.trim().toLowerCase();
	const enabled = q ? enabledByState.filter( ( t ) => t.label.toLowerCase().indexOf( q ) !== -1 ) : enabledByState;

	// The 6 palette-slot colours (form/field bg, label/sublabel, button text/bg) render as
	// hidden tokens with no direct picker (deliberate v1-parity choice) — tell the user where to
	// find them instead of the slate silently having no colour control at all.
	const hasHiddenPaletteColor = store.schema.some( ( t ) => t.section === section.key && t.hidden );

	// Dependency dimming: a border-style set to "none" disables its width/colour deps.
	const dimmedByDep = new Set< string >();
	const depHints: Record< string, string > = {};
	visible.forEach( ( t ) => {
		if ( t.deps && String( store.resolve( t.key ) ) === 'none' ) {
			t.deps.forEach( ( k ) => dimmedByDep.add( k ) );
			depHints[ t.key ] = __( 'Width & colour have no effect while border style is None.', 'everest-forms' );
		}
	} );

	const locked = ( t: Token ) => t.tier === 'pro' && ! store.proActive;
	const hasLocked = visible.some( locked );

	// A whole Pro section (e.g. Messages) on a free site: its controls live in the Pro plugin and
	// are absent here, so render a locked upgrade teaser instead of an empty slate. The server
	// authoritatively rejects any pro value regardless of the UI (Sanitizer/Compiler).
	const sectionLocked = section.tier === 'pro' && ! store.proActive;

	const renderControl = ( t: Token ) => (
		<ControlRenderer
			key={ t.key }
			token={ t }
			store={ store }
			onBadgeClick={ onBadgeClick }
			dimmed={ dimmedByDep.has( t.key ) || locked( t ) }
			depHint={ depHints[ t.key ] }
		/>
	);

	const renderGroups = ( tokens: Token[] ) =>
		groupTokens( tokens ).map( ( g, i ) => (
			<div className="grp" key={ i }>
				{ g.heading && <div className="grp-h">{ g.heading }</div> }
				{ g.tokens.map( renderControl ) }
			</div>
		) );

	if ( sectionLocked ) {
		return (
			<div id="elBody" className="slate-anim" ref={ bodyRef }>
				<h2 className="slate-title">{ section.title }</h2>
				{ section.hint && <p className="sec-hint">{ section.hint }</p> }
				<ProSectionTeaser section={ section } />
			</div>
		);
	}

	return (
		<div id="elBody" className="slate-anim" ref={ bodyRef }>
			<div className="slate-title-row">
				<h2 className="slate-title">{ section.title }</h2>
				<button type="button" className="uxbtn" title={ __( 'Reset this section', 'everest-forms' ) } aria-label={ __( 'Reset this section', 'everest-forms' ) + ' — ' + section.title } onClick={ () => store.resetSection( section.key ) }>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
						<path d="M3 12a9 9 0 1 0 3-6.7" />
						<path d="M3 4v5h5" />
					</svg>
				</button>
			</div>
			{ section.hint && <p className="sec-hint">{ section.hint }</p> }
			{ hasHiddenPaletteColor && (
				<p className="hintline">
					<Icon inner='<circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8h.01"/>' />
					{ __( 'Background & text colours here come from your colour palette.', 'everest-forms' ) }
				</p>
			) }

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

			{ showFilter && (
				<div className="slate-filter">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
						<circle cx="11" cy="11" r="7" />
						<path d="m21 21-4.35-4.35" />
					</svg>
					<input
						type="text"
						value={ filter }
						placeholder={ __( 'Filter controls…', 'everest-forms' ) }
						aria-label={ __( 'Filter', 'everest-forms' ) + ' ' + section.title + ' ' + __( 'controls', 'everest-forms' ) }
						onChange={ ( e ) => setFilter( e.target.value ) }
					/>
				</div>
			) }

			<div id={ `scv2-state-panel-${ section.key }` } role={ tabs ? 'tabpanel' : undefined } aria-labelledby={ tabs ? `scv2-state-${ section.key }-${ act }` : undefined }>
				{ enabled.length ? renderGroups( enabled ) : (
					<p className="slate-empty">{ __( 'No controls match your filter.', 'everest-forms' ) }</p>
				) }
			</div>
		</div>
	);
}

/**
 * Locked upgrade teaser — shown wherever a Pro feature is surfaced on a free site (a whole
 * design section, or the Templates tab). Explains the feature and links to upgrade; the actual
 * controls/data are not shipped in free and the server rejects any pro value regardless.
 */
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

/** Flatten a template's per-device token bags into a { key: desktopValue } map. */
function flattenDesktop( tokens: Record< string, DeviceBag > ): Record< string, any > {
	const out: Record< string, any > = {};
	Object.keys( tokens || {} ).forEach( ( k ) => ( out[ k ] = desktopOf( tokens[ k ] ) ) );
	return out;
}

function radiusOf( bag: DeviceBag | undefined, fallback: number ): number {
	const v = desktopOf( bag ) as BoxValue | undefined;
	return v && typeof v === 'object' ? Number( v.top ) || 0 : fallback;
}

/**
 * Template thumbnail: shows the real screenshot image when it loads, and falls back to a live
 * token-driven mini-form if the image is missing/blocked — so a thumbnail always renders.
 */
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
			<span className="b" style={ th.button } />
		</span>
	);
}

/** Build a small live thumbnail from a template's migrated tokens. */
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
	onPreview,
	onClearPreview,
	onApply,
	onDelete,
}: {
	tpl: Template;
	applied: boolean;
	/** The form was applied FROM this template but has since been edited — show an honest hint
	 *  instead of a ✓ (its styles no longer exactly match this template). */
	modified?: boolean;
	/** Name of the built-in template this (custom) template exactly derives from, if any. */
	basedOn?: string;
	locked: boolean;
	onPreview: () => void;
	onClearPreview: () => void;
	onApply: () => void;
	onDelete?: () => void;
} ) {
	return (
		<button
			type="button"
			className={
				'tpl' +
				( tpl.custom ? ' tpl-user' : '' ) +
				( locked ? ' tpl-locked' : '' ) +
				( applied ? ' tpl-applied' : '' ) +
				( modified ? ' tpl-modified' : '' )
			}
			aria-pressed={ applied }
			onMouseEnter={ onPreview }
			onMouseLeave={ onClearPreview }
			onFocus={ onPreview }
			onBlur={ onClearPreview }
			onClick={ onApply }
		>
			{ applied && (
				<span className="tpl-check" aria-label={ __( 'Currently applied', 'everest-forms' ) }>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 3 }>
						<path d="m5 13 4 4 10-10" />
					</svg>
				</span>
			) }
			{ ! applied && modified && (
				<span className="tpl-mod" title={ __( 'Started from this template, then edited', 'everest-forms' ) }>
					{ __( 'Modified', 'everest-forms' ) }
				</span>
			) }
			{ locked && (
				<span className="tpl-pro" aria-label={ __( 'Pro template', 'everest-forms' ) }>
					<ProCrown />
				</span>
			) }
			{ onDelete && (
				<span
					className="tpl-del"
					role="button"
					tabIndex={ 0 }
					aria-label={ __( 'Delete template', 'everest-forms' ) }
					title={ __( 'Delete template', 'everest-forms' ) }
					onClick={ ( e ) => {
						e.stopPropagation();
						onDelete();
					} }
					onKeyDown={ ( e ) => {
						if ( e.key === 'Enter' || e.key === ' ' ) {
							e.stopPropagation();
							e.preventDefault();
							onDelete();
						}
					} }
				>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 } aria-hidden="true">
						<path d="M18 6 6 18M6 6l12 12" />
					</svg>
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
			<span className="cap">{ tpl.name }</span>
			{ basedOn && (
				<span className="tpl-parent">
					{ /* translators: %s: the built-in template this custom one derives from. */ }
					{ ( __( 'Based on %s', 'everest-forms' ) as string ).replace( '%s', basedOn ) }
				</span>
			) }
		</button>
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
	const [ name, setName ] = React.useState( '' );
	const [ busy, setBusy ] = React.useState( false );
	const [ error, setError ] = React.useState( '' );
	// The create-template form starts collapsed — a trigger row opens it — so the tab's default
	// view is the template grids, not an always-open input+button most visits never touch.
	const [ showCreateForm, setShowCreateForm ] = React.useState( false );
	const createInputRef = React.useRef< HTMLInputElement >( null );

	const templates = store.allTemplates();
	// Memoize the flattened override maps so hover doesn't re-flatten on every mouse event.
	const flat = React.useMemo(
		() => Object.fromEntries( templates.map( ( tpl ) => [ tpl.id, flattenDesktop( tpl.tokens ) ] ) ),
		[ templates ]
	);

	// Value-driven template state (recomputed whenever the store version bumps): the ✓ "Applied"
	// badge reflects whether the form's ACTUAL styles match a template — never a stale slug — so it
	// can't disagree with the live preview. `originId` is the template the form was applied FROM but
	// has since been edited (an honest "Modified" hint). `parentNames` maps each CUSTOM template to
	// the built-in it exactly derives from (its inferred "parent"). See the store methods.
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

	const templatesBase = store.settings.restBase.replace( /\/styles$/, '/style-templates' );

	const createTemplate = async () => {
		const trimmed = name.trim();
		if ( ! apiFetch || ! trimmed || busy ) {
			return;
		}
		setBusy( true );
		setError( '' );
		try {
			const res = await apiFetch( {
				path: templatesBase,
				method: 'POST',
				data: { name: trimmed, record: store.toRecord() },
			} );
			if ( res && res.template ) {
				store.addUserTemplate( res.template );
			}
			setName( '' );
			setShowCreateForm( false );
		} catch ( e: any ) {
			setError( ( e && e.message ) || __( 'Could not save the template.', 'everest-forms' ) );
		} finally {
			setBusy( false );
		}
	};

	const deleteTemplate = async ( id: string ) => {
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

	// Saving your own template is a Pro feature (the server also enforces it on the endpoint).
	const canCreate = store.proActive;

	const applyTpl = ( tpl: Template, locked: boolean ) => {
		if ( locked ) {
			window.open( UPGRADE_URL, '_blank' );
			return;
		}
		store.applyTemplate( tpl.id, tpl.tokens, tpl.palette );
		onApplied( tpl.name );
	};

	const renderGrid = ( list: Template[], withDelete: boolean ) => (
		<div className="tpls">
			{ list.map( ( tpl ) => {
				const locked = !! tpl.is_pro && ! store.proActive;
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
						onDelete={ withDelete ? () => deleteTemplate( tpl.id ) : undefined }
					/>
				);
			} ) }
		</div>
	);

	return (
		<div className="slate-anim">
			{ canCreate ? (
				showCreateForm ? (
					<div className="tpl-create">
						<span className="tpl-create-ic" aria-hidden="true">
							<Icon inner='<path d="M12 5v14M5 12h14"/>' />
						</span>
						<div className="tpl-create-fields">
							<div className="tpl-create-title">{ __( 'Create Style Template', 'everest-forms' ) }</div>
							<p className="tpl-create-sub">{ __( 'Save the current styles as a reusable template.', 'everest-forms' ) }</p>
							<input
								ref={ createInputRef }
								type="text"
								className="tpl-create-input"
								value={ name }
								placeholder={ __( 'Template name', 'everest-forms' ) }
								onChange={ ( e ) => setName( e.target.value ) }
								onKeyDown={ ( e ) => {
									if ( e.key === 'Enter' ) {
										createTemplate();
									} else if ( e.key === 'Escape' ) {
										setShowCreateForm( false );
										setError( '' );
									}
								} }
							/>
							<div className="tpl-create-row">
								{ error && <span className="tpl-create-err">{ error }</span> }
								<button
									type="button"
									className="tpl-create-cancel"
									onClick={ () => {
										setShowCreateForm( false );
										setError( '' );
									} }
								>
									{ __( 'Cancel', 'everest-forms' ) }
								</button>
								<button
									type="button"
									className="tpl-create-btn"
									disabled={ ! name.trim() || busy }
									onClick={ createTemplate }
								>
									{ busy ? __( 'Saving…', 'everest-forms' ) : __( 'Create', 'everest-forms' ) }
								</button>
							</div>
						</div>
					</div>
				) : (
					<button
						type="button"
						className="tpl-create tpl-create-trigger"
						onClick={ () => {
							setShowCreateForm( true );
							window.setTimeout( () => createInputRef.current?.focus(), 0 );
						} }
					>
						<span className="tpl-create-ic" aria-hidden="true">
							<Icon inner='<path d="M12 5v14M5 12h14"/>' />
						</span>
						<div className="tpl-create-fields">
							<div className="tpl-create-title">{ __( 'Create Style Template', 'everest-forms' ) }</div>
							<p className="tpl-create-sub">{ __( 'Save the current styles as a reusable template.', 'everest-forms' ) }</p>
						</div>
					</button>
				)
			) : (
				<a className="tpl-create tpl-create-locked" href={ UPGRADE_URL } target="_blank" rel="noreferrer">
					<span className="tpl-create-ic" aria-hidden="true">
						<Icon inner='<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>' />
					</span>
					<div className="tpl-create-fields">
						<span className="tpl-create-title">
							{ __( 'Create Style Template', 'everest-forms' ) }
							<span className="pro-badge" aria-label={ __( 'Pro', 'everest-forms' ) }>
								<ProCrown />
							</span>
						</span>
						<p className="tpl-create-sub">
							{ __( 'Save your current styles as a reusable template with Pro.', 'everest-forms' ) }
						</p>
					</div>
				</a>
			) }

			<p className="pane-note">
				<b>{ __( 'Hover a card to preview it live', 'everest-forms' ) }</b>{ ' ' }
				— { __( 'click to apply (you can always undo).', 'everest-forms' ) }
			</p>

			{ !! store.userTemplates.length ? (
				<>
					<div className="block-title">{ __( 'Your templates', 'everest-forms' ) }</div>
					{ renderGrid( store.userTemplates, true ) }
				</>
			) : (
				canCreate && (
					<>
						<div className="block-title">{ __( 'Your templates', 'everest-forms' ) }</div>
						<div className="empty-state">
							<Icon inner='<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 15h6M9 11h6"/>' />
							{ __( 'No saved templates yet — create one above.', 'everest-forms' ) }
						</div>
					</>
				)
			) }

			<div className="block-title">{ __( 'Built-in templates', 'everest-forms' ) }</div>
			{ renderGrid( store.templates, false ) }

			<p className="tpl-hint">
				{ __( 'Templates set every element at once. Fine-tune afterwards from the Design tab.', 'everest-forms' ) }
			</p>
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
				{ __( 'Applied live as you type, and', 'everest-forms' ) } <b>{ __( 'auto-scoped to this form', 'everest-forms' ) }</b>{ ' ' }
				{ __( 'on save so nothing leaks to the rest of your site. Click a selector to insert it:', 'everest-forms' ) }
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
