/**
 * Style Customizer v2 — live preview bridge.
 *
 * Owns the `?evf_preview` iframe. On load it reaches into the same-origin document, tags the
 * form wrapper with the v2 marker class, injects the shared rule template (ID-scoped so it
 * reliably beats the preview chrome + any legacy CSS), and writes the current token values as
 * CSS custom properties onto the wrapper. Thereafter every store change re-applies only the
 * variables that moved — no reload, no recompile.
 *
 * Robustness (plan risk §9.3 + cross-browser): the wrapper may not be present the instant the
 * `load` event fires (theme scripts, redirects, Chrome timing), so we poll for it up to a
 * deadline before surfacing the "open in new tab" fallback — and we hide the preview chrome
 * immediately, so the frame never flashes the full front-end page / a dark overlay.
 *
 * Element selection: a delegated click/hover handler inside the iframe maps any clicked form
 * element to its style section (+ variant) and reports it back, so the panel opens the right
 * controls — a modern visual-editor interaction. Same-origin, so no postMessage is needed.
 */
import { resolveValue, tokenDeclarations } from './cssVars';
import { ALL_FORCE_CLASSES, PREVIEW_TARGETS } from './constants';
import { StyleStore } from './store';
import { Token } from './types';

const CUSTOM_STYLE_ID = 'evf-scv2-custom-css';
const TEMPLATE_STYLE_ID = 'evf-scv2-rule-template';
const CHROME_STYLE_ID = 'evf-scv2-chrome';
const SELECT_STYLE_ID = 'evf-scv2-select';
const DEVICE_STYLE_ID = 'evf-scv2-device';

const HOVER_CLASS = 'evf-scv2-hover';
const SELECTED_CLASS = 'evf-scv2-selected';

/** How long to keep polling for the form wrapper before giving up (ms). */
const READY_DEADLINE = 15000;
/** Poll interval while waiting for the wrapper (ms). */
const POLL_INTERVAL = 200;

export interface SelectionInfo {
	section: string;
	variant?: string;
	label: string;
}

interface BridgeHandlers {
	onReady: () => void;
	onError: () => void;
	onSelect?: ( info: SelectionInfo ) => void;
}

/** Module-level cache of the fetched rule-template CSS text, keyed by URL. */
const cssTextCache: Record< string, Promise< string > > = {};

function fetchCss( url: string ): Promise< string > {
	if ( ! cssTextCache[ url ] ) {
		cssTextCache[ url ] = fetch( url, { credentials: 'same-origin' } ).then( ( r ) => {
			if ( ! r.ok ) {
				throw new Error( 'css ' + r.status );
			}
			return r.text();
		} );
	}
	return cssTextCache[ url ];
}

export class PreviewBridge {
	private store: StyleStore;
	private iframe: HTMLIFrameElement;
	private wrapper: HTMLElement | null = null;
	private ready = false;
	private destroyed = false;
	private onReady: () => void;
	private onError: () => void;
	private onSelect?: ( info: SelectionInfo ) => void;
	private deadline = 0;
	private pollTimer: ReturnType< typeof setTimeout > | null = null;
	private selectedEl: HTMLElement | null = null;
	private hoverEl: HTMLElement | null = null;
	private previewedKeys: Set< string > = new Set();
	private deviceWidth: number | null = null;

	constructor( iframe: HTMLIFrameElement, store: StyleStore, handlers: BridgeHandlers ) {
		this.iframe = iframe;
		this.store = store;
		this.onReady = handlers.onReady;
		this.onError = handlers.onError;
		this.onSelect = handlers.onSelect;
	}

	/** Wire onto the iframe's load event and begin polling for the wrapper. */
	attach() {
		this.deadline = Date.now() + READY_DEADLINE;
		this.iframe.addEventListener( 'load', this.handleLoad );
		// The frame may already be (or become) ready before/around listener attach.
		this.poll();
	}

	detach() {
		this.destroyed = true;
		if ( this.pollTimer ) {
			clearTimeout( this.pollTimer );
			this.pollTimer = null;
		}
		this.iframe.removeEventListener( 'load', this.handleLoad );
		this.teardownSelection();
	}

	private handleLoad = () => {
		// A fresh navigation inside the frame (rare) — re-arm and re-detect.
		this.ready = false;
		this.wrapper = null;
		this.deadline = Date.now() + READY_DEADLINE;
		this.poll();
	};

	/** Poll for the wrapper until found or the deadline passes. */
	private poll = () => {
		if ( this.destroyed || this.ready ) {
			return;
		}

		let doc: Document | null = null;
		try {
			doc = this.iframe.contentDocument;
		} catch ( e ) {
			doc = null; // Transient during navigation, or cross-origin.
		}

		if ( doc ) {
			// Hide the preview chrome as early as possible so the frame never flashes the full
			// front-end page or a dark overlay while we wait for the wrapper.
			this.hideChrome( doc );

			const wrapper = doc.getElementById( this.store.settings.wrapperId );
			if ( wrapper ) {
				this.bootstrap( doc, wrapper );
				return;
			}
		}

		if ( Date.now() >= this.deadline ) {
			if ( ! this.ready ) {
				this.onError();
			}
			return;
		}
		this.pollTimer = setTimeout( this.poll, POLL_INTERVAL );
	};

	/** Wrapper found — tag it, inject rules, paint variables, wire selection. */
	private bootstrap( doc: Document, wrapper: HTMLElement ) {
		this.wrapper = wrapper;
		wrapper.classList.add( this.store.settings.markerClass );
		this.disableLegacySheet( doc );
		this.injectRuleTemplate( doc, () => {
			if ( this.destroyed ) {
				return;
			}
			this.applyAll();
			this.applyCustomCss();
			this.applyDeviceWidth();
			this.injectSelectionStyles( doc );
			this.setupSelection( doc );
			this.ready = true;
			this.onReady();
		} );
	}

	/**
	 * Neutralise the legacy per-form compiled stylesheet inside the preview. For a MIGRATED
	 * (v2) form the server already dequeues it (FrontendEnqueue), but a not-yet-saved legacy
	 * form being edited still ships its `everest-forms-{id}.css` (ID-scoped, so a specificity
	 * peer of our injected rules). Disabling it guarantees the v2 tokens win regardless of
	 * source order — the panel is the single source of truth while editing.
	 */
	private disableLegacySheet( doc: Document ) {
		const id = this.store.settings.formId;
		if ( ! id ) {
			return;
		}
		const needle = `everest_forms_styles/everest-forms-${ id }.css`;
		doc.querySelectorAll( 'link[rel="stylesheet"]' ).forEach( ( node ) => {
			const link = node as HTMLLinkElement;
			if ( link.href && link.href.indexOf( needle ) !== -1 ) {
				link.disabled = true;
				link.remove();
			}
		} );
	}

	/**
	 * The `?evf_preview` route renders the whole page: the WP admin bar, the EVF preview
	 * toolbar, a side panel, an overlay. We only want the form, so inject CSS that hides that
	 * chrome and lets the form fill the frame. Preview-only, idempotent.
	 */
	private hideChrome( doc: Document ) {
		if ( ! doc.head || doc.getElementById( CHROME_STYLE_ID ) ) {
			return;
		}
		const css = `
			html { margin-top: 0 !important; }
			body { margin-top: 0 !important; padding-top: 0 !important; }
			#wpadminbar,
			#nav-menu-header,
			.major-publishing-actions,
			.evf-form-preview-dropdown-container,
			.evf-form-preview-devices,
			.evf-form-preview-sidepanel-toggler,
			.evf-form-side-panel { display: none !important; }
			body.evf-multi-device-form-preview { background: #fff !important; }
			.evf-form-preview-main-content,
			.evf-form-preview-overlay {
				display: block !important;
				position: static !important;
				inset: auto !important;
				margin: 0 !important;
				padding: 12px !important;
				width: 100% !important;
				max-width: 100% !important;
				min-height: 0 !important;
				height: auto !important;
				box-shadow: none !important;
				background: transparent !important;
			}
			.evf-preview-content,
			.everest-forms.evf-frontend-form-preview { padding: 0 !important; }
			.evf-form-preview-form {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
			}`;
		const style = doc.createElement( 'style' );
		style.id = CHROME_STYLE_ID;
		style.textContent = css;
		doc.head.appendChild( style );
	}

	/**
	 * Inject the shared rule template (the selectors that READ the vars) ID-scoped to the
	 * wrapper. Scoping every `.evf-style-v2` selector with the form's `#id` raises specificity
	 * above the preview-chrome stylesheet and any stale legacy CSS, so v2 tokens always win.
	 * Fetched once and cached; falls back to a class-scoped `<link>` if the fetch fails.
	 */
	private injectRuleTemplate( doc: Document, done: () => void ) {
		if ( doc.getElementById( TEMPLATE_STYLE_ID ) ) {
			done();
			return;
		}
		const id = this.store.settings.wrapperId;
		fetchCss( this.store.settings.frontendCssUrl )
			.then( ( text ) => {
				if ( this.destroyed || doc.getElementById( TEMPLATE_STYLE_ID ) ) {
					done();
					return;
				}
				// `.evf-style-v2` (not followed by a name char) → `.evf-style-v2#evf-{id}`.
				const scoped = text.replace( /\.evf-style-v2(?![\w-])/g, `.evf-style-v2#${ id }` );
				const style = doc.createElement( 'style' );
				style.id = TEMPLATE_STYLE_ID;
				style.textContent = scoped;
				doc.head.appendChild( style );
				done();
			} )
			.catch( () => {
				if ( this.destroyed || doc.getElementById( TEMPLATE_STYLE_ID ) ) {
					done();
					return;
				}
				const link = doc.createElement( 'link' );
				link.id = TEMPLATE_STYLE_ID;
				link.rel = 'stylesheet';
				link.href = this.store.settings.frontendCssUrl;
				link.onload = () => done();
				link.onerror = () => done();
				doc.head.appendChild( link );
			} );
	}

	/* --------------------------------------------------------------------- *
	 * Variable application
	 * --------------------------------------------------------------------- */

	/** Re-apply everything (device switch / palette / undo / initial load). */
	applyAll() {
		if ( ! this.wrapper ) {
			return;
		}
		const themeFont = this.store.themeFont();
		this.store.schema.forEach( ( token ) => this.applyToken( token, themeFont ) );
	}

	/** Apply only the given token keys (a targeted live edit). */
	applyKeys( keys: string[] ) {
		if ( ! this.wrapper || ! keys.length ) {
			return;
		}
		const themeFont = this.store.themeFont();
		keys.forEach( ( key ) => {
			const token = this.store.byKey[ key ];
			if ( token ) {
				this.applyToken( token, themeFont );
			}
		} );
	}

	private applyToken( token: Token, themeFont: boolean ) {
		const wrapper = this.wrapper;
		if ( ! wrapper ) {
			return;
		}
		const value = resolveValue( this.store.tokens[ token.key ], token, this.store.device );
		const decls = tokenDeclarations( token, value, themeFont );

		// Clear this token's variables first so an emptied value doesn't leave a stale one.
		this.varsFor( token ).forEach( ( v ) => wrapper.style.removeProperty( v ) );
		decls.forEach( ( [ name, val ] ) => wrapper.style.setProperty( name, val ) );
	}

	/** Every CSS var a token can set (font-style expands to four). */
	private varsFor( token: Token ): string[] {
		if ( token.type === 'fontstyle' && token.vars ) {
			return Object.values( token.vars );
		}
		return token.var ? [ token.var ] : [];
	}

	/**
	 * Constrain the FORM content width inside the iframe to simulate a device, WITHOUT resizing
	 * the outer preview pane (per the device-switch UX): the pane/toolbar stay full-width; only
	 * the rendered form narrows + centers. `null` (desktop) removes the constraint. Responsive
	 * spacing still previews because the bridge re-writes the per-device vars on device change.
	 *
	 * @param width Device content width in px, or null for full width.
	 */
	setDeviceWidth( width: number | null ) {
		this.deviceWidth = width && width > 0 ? width : null;
		this.applyDeviceWidth();
	}

	private applyDeviceWidth() {
		const doc = this.wrapper ? this.wrapper.ownerDocument : null;
		if ( ! doc || ! doc.head ) {
			return;
		}
		let style = doc.getElementById( DEVICE_STYLE_ID ) as HTMLStyleElement | null;
		if ( this.deviceWidth === null ) {
			if ( style ) {
				style.remove();
			}
			return;
		}
		if ( ! style ) {
			style = doc.createElement( 'style' );
			style.id = DEVICE_STYLE_ID;
			doc.head.appendChild( style );
		}
		// The preview route always wraps the form in `.evf-form-preview-form`; constraining it
		// (with the container as a fallback) narrows + centers the rendered form.
		style.textContent = `.evf-form-preview-form,.evf-preview-content{max-width:${ this.deviceWidth }px!important;margin-left:auto!important;margin-right:auto!important;transition:max-width .25s ease;}`;
	}

	/** Toggle a single force-state class (focus/hover/message) for state previews. */
	setForceClass( cls: string | null ) {
		if ( ! this.wrapper ) {
			return;
		}
		ALL_FORCE_CLASSES.forEach( ( c ) => this.wrapper!.classList.remove( c ) );
		if ( cls ) {
			this.wrapper.classList.add( cls );
		}
	}

	/** Live-apply the current custom CSS into the iframe (save-time scoping happens server-side). */
	applyCustomCss() {
		const doc = this.wrapper ? this.wrapper.ownerDocument : null;
		if ( ! doc ) {
			return;
		}
		let style = doc.getElementById( CUSTOM_STYLE_ID ) as HTMLStyleElement | null;
		const css = this.store.customCss || '';
		if ( ! css ) {
			if ( style ) {
				style.remove();
			}
			return;
		}
		if ( ! style ) {
			style = doc.createElement( 'style' );
			style.id = CUSTOM_STYLE_ID;
			doc.head.appendChild( style );
		}
		style.textContent = css;
	}

	/**
	 * Temporarily paint a set of token values (by key) onto the preview WITHOUT touching the
	 * store — used for template hover previews. `revert()` restores committed state, touching
	 * only the keys that were previewed (fast).
	 */
	previewValues( overrides: Record< string, unknown > ) {
		if ( ! this.wrapper ) {
			return;
		}
		const themeFont = this.store.themeFont();
		Object.entries( overrides ).forEach( ( [ key, value ] ) => {
			const token = this.store.byKey[ key ];
			if ( ! token ) {
				return;
			}
			this.previewedKeys.add( key );
			this.varsFor( token ).forEach( ( v ) => this.wrapper!.style.removeProperty( v ) );
			tokenDeclarations( token, value as never, themeFont ).forEach( ( [ name, val ] ) =>
				this.wrapper!.style.setProperty( name, val )
			);
		} );
	}

	/** Preview a palette's colours live (hover) without committing to the store. */
	previewPalette( colors: Record< string, string > ) {
		if ( ! this.wrapper ) {
			return;
		}
		Object.entries( this.store.paletteMap ).forEach( ( [ slot, keys ] ) => {
			const color = colors[ slot ];
			if ( color === undefined ) {
				return;
			}
			keys.forEach( ( key ) => {
				const token = this.store.byKey[ key ];
				if ( token && token.var ) {
					this.previewedKeys.add( key );
					this.wrapper!.style.setProperty( token.var, color );
				}
			} );
		} );
	}

	/** Restore the committed store state after a hover preview (only the previewed keys). */
	revert() {
		if ( ! this.wrapper || ! this.previewedKeys.size ) {
			return;
		}
		const keys = Array.from( this.previewedKeys );
		this.previewedKeys.clear();
		this.applyKeys( keys );
	}

	/* --------------------------------------------------------------------- *
	 * Element selection (click-to-edit)
	 * --------------------------------------------------------------------- */

	private injectSelectionStyles( doc: Document ) {
		if ( doc.getElementById( SELECT_STYLE_ID ) ) {
			return;
		}
		const css = `
			.${ HOVER_CLASS } { outline: 1.5px solid rgba(117,69,187,.45) !important; outline-offset: 2px !important; border-radius: 3px; }
			.${ SELECTED_CLASS } { outline: 2px solid rgba(117,69,187,.85) !important; outline-offset: 2px !important; border-radius: 3px; }
			#${ this.store.settings.wrapperId } * { cursor: default; }`;
		const style = doc.createElement( 'style' );
		style.id = SELECT_STYLE_ID;
		style.textContent = css;
		doc.head.appendChild( style );
	}

	private setupSelection( doc: Document ) {
		doc.addEventListener( 'click', this.onDocClick, true );
		doc.addEventListener( 'mouseover', this.onDocOver, true );
		doc.addEventListener( 'mouseout', this.onDocOut, true );
		// Block real form submission / link navigation inside the style preview.
		doc.addEventListener( 'submit', this.blockEvent, true );
	}

	private teardownSelection() {
		let doc: Document | null = null;
		try {
			doc = this.iframe.contentDocument;
		} catch ( e ) {
			doc = null;
		}
		if ( ! doc ) {
			return;
		}
		doc.removeEventListener( 'click', this.onDocClick, true );
		doc.removeEventListener( 'mouseover', this.onDocOver, true );
		doc.removeEventListener( 'mouseout', this.onDocOut, true );
		doc.removeEventListener( 'submit', this.blockEvent, true );
	}

	private blockEvent = ( e: Event ) => {
		e.preventDefault();
		e.stopPropagation();
	};

	/** Resolve a clicked element to its style target by walking the ordered selector list. */
	private resolveTarget( el: Element ): { info: SelectionInfo; el: HTMLElement } | null {
		const wrapper = this.wrapper;
		if ( ! wrapper ) {
			return null;
		}
		for ( const target of PREVIEW_TARGETS ) {
			const match = el.closest( target.match ) as HTMLElement | null;
			if ( match && wrapper.contains( match ) ) {
				return { info: { section: target.section, variant: target.variant, label: target.label }, el: match };
			}
		}
		if ( wrapper.contains( el ) ) {
			return { info: { section: 'form', label: 'Form container' }, el: wrapper };
		}
		return null;
	}

	private onDocClick = ( e: MouseEvent ) => {
		const target = e.target as Element | null;
		if ( ! target || ! this.wrapper ) {
			return;
		}
		const resolved = this.resolveTarget( target );
		if ( ! resolved ) {
			return;
		}
		// Never let the preview navigate away or submit.
		e.preventDefault();
		e.stopPropagation();
		this.setSelected( resolved.el );
		if ( this.onSelect ) {
			this.onSelect( resolved.info );
		}
	};

	private onDocOver = ( e: MouseEvent ) => {
		const target = e.target as Element | null;
		if ( ! target || ! this.wrapper ) {
			return;
		}
		const resolved = this.resolveTarget( target );
		if ( resolved && resolved.el !== this.selectedEl ) {
			this.setHover( resolved.el );
		} else {
			this.setHover( null );
		}
	};

	private onDocOut = () => {
		this.setHover( null );
	};

	private setHover( el: HTMLElement | null ) {
		if ( this.hoverEl === el ) {
			return;
		}
		if ( this.hoverEl ) {
			this.hoverEl.classList.remove( HOVER_CLASS );
		}
		this.hoverEl = el;
		if ( el && el !== this.selectedEl ) {
			el.classList.add( HOVER_CLASS );
		}
	}

	private setSelected( el: HTMLElement | null ) {
		if ( this.selectedEl ) {
			this.selectedEl.classList.remove( SELECTED_CLASS );
		}
		this.selectedEl = el;
		if ( el ) {
			el.classList.remove( HOVER_CLASS );
			el.classList.add( SELECTED_CLASS );
		}
	}

	/** Clear any selection outline (e.g. when the panel navigates back to the list). */
	clearSelection() {
		this.setSelected( null );
		this.setHover( null );
	}

	isReady(): boolean {
		return this.ready;
	}

	getWrapper(): HTMLElement | null {
		return this.wrapper;
	}
}

let active: PreviewBridge | null = null;

export function setActiveBridge( bridge: PreviewBridge | null ) {
	active = bridge;
}

export function getActiveBridge(): PreviewBridge | null {
	return active;
}
