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

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;
const UPGRADE_URL = 'https://everestforms.net/pricing/?utm_source=style-customizer&utm_medium=panel';

function Icon( { inner }: { inner: string } ) {
	return (
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } dangerouslySetInnerHTML={ { __html: inner } } />
	);
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
}: {
	sections: Section[];
	onOpen: ( key: string ) => void;
	onOpenPalette: ( anchor: HTMLElement ) => void;
	paletteOpen: boolean;
} ) {
	const store = useStore();
	const activePalette = store.palettes.find( ( p ) => p.id === store.palette );
	const swatches = activePalette
		? paletteSwatchColors( activePalette.colors, store.paletteMap )
		: [ '#e9eaf0', '#d7d9e3', '#c3c6d4', '#b3b7c7', '#a3a8bd', '#cfd2de' ];

	return (
		<div>
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
									{ __( 'PRO', 'everest-forms' ) }
								</span>
							) : (
								<span className="dot" aria-hidden="true" />
							) }
							<span className="chev" aria-hidden="true">›</span>
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
						) }
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
	const enabled = tabs
		? visible.filter( ( t ) => ( t.state ? t.state === act : act === first ) )
		: visible;

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
			<div id="elBody" ref={ bodyRef }>
				{ section.hint && <p className="sec-hint">{ section.hint }</p> }
				<ProSectionTeaser section={ section } />
			</div>
		);
	}

	return (
		<div id="elBody" ref={ bodyRef }>
			{ section.hint && <p className="sec-hint">{ section.hint }</p> }

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
				<div className="state-seg" role="tablist" aria-label={ section.title + ' ' + __( 'variants', 'everest-forms' ) }>
					{ tabs.map( ( id ) => (
						<button
							key={ id }
							type="button"
							role="tab"
							aria-selected={ id === act }
							onClick={ () => onChangeState( id ) }
						>
							{ STATE_LABELS[ id ] || id }
						</button>
					) ) }
				</div>
			) }

			{ renderGroups( enabled ) }
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
	locked,
	onPreview,
	onClearPreview,
	onApply,
	onDelete,
}: {
	tpl: Template;
	applied: boolean;
	locked: boolean;
	onPreview: () => void;
	onClearPreview: () => void;
	onApply: () => void;
	onDelete?: () => void;
} ) {
	return (
		<button
			type="button"
			className={ 'tpl' + ( tpl.custom ? ' tpl-user' : '' ) + ( locked ? ' tpl-locked' : '' ) + ( applied ? ' tpl-applied' : '' ) }
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
			{ locked && (
				<span className="tpl-pro" aria-label={ __( 'Pro template', 'everest-forms' ) }>
					{ __( 'PRO', 'everest-forms' ) }
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
					✕
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
		</button>
	);
}

export function TemplatesPane( {
	onPreview,
	onClearPreview,
}: {
	onPreview: ( overrides: Record< string, any > ) => void;
	onClearPreview: () => void;
} ) {
	const store = useStore();
	const [ name, setName ] = React.useState( '' );
	const [ busy, setBusy ] = React.useState( false );
	const [ error, setError ] = React.useState( '' );

	const templates = store.allTemplates();
	// Memoize the flattened override maps so hover doesn't re-flatten on every mouse event.
	const flat = React.useMemo(
		() => Object.fromEntries( templates.map( ( tpl ) => [ tpl.id, flattenDesktop( tpl.tokens ) ] ) ),
		[ templates ]
	);

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
	};

	const renderGrid = ( list: Template[], withDelete: boolean ) => (
		<div className="tpls">
			{ list.map( ( tpl ) => {
				const locked = !! tpl.is_pro && ! store.proActive;
				return (
					<TemplateCard
						key={ tpl.id }
						tpl={ tpl }
						applied={ store.template === tpl.id }
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
		<div>
			{ canCreate ? (
				<div className="tpl-create">
					<span className="tpl-create-ic" aria-hidden="true">
						<Icon inner='<path d="M12 5v14M5 12h14"/>' />
					</span>
					<div className="tpl-create-fields">
						<div className="tpl-create-title">{ __( 'Create Style Template', 'everest-forms' ) }</div>
						<p className="tpl-create-sub">{ __( 'Save the current styles as a reusable template.', 'everest-forms' ) }</p>
						<input
							type="text"
							className="tpl-create-input"
							value={ name }
							placeholder={ __( 'Template name', 'everest-forms' ) }
							onChange={ ( e ) => setName( e.target.value ) }
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' ) {
									createTemplate();
								}
							} }
						/>
						<div className="tpl-create-row">
							{ error && <span className="tpl-create-err">{ error }</span> }
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
				<a className="tpl-create tpl-create-locked" href={ UPGRADE_URL } target="_blank" rel="noreferrer">
					<span className="tpl-create-ic" aria-hidden="true">
						<Icon inner='<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>' />
					</span>
					<div className="tpl-create-fields">
						<span className="tpl-create-title">
							{ __( 'Create Style Template', 'everest-forms' ) }
							<span className="pro-badge">{ __( 'PRO', 'everest-forms' ) }</span>
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

			{ !! store.userTemplates.length && (
				<>
					<div className="block-title">{ __( 'Your templates', 'everest-forms' ) }</div>
					{ renderGrid( store.userTemplates, true ) }
				</>
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
		<div>
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
