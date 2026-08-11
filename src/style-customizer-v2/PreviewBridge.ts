/**
 * Style Customizer v2 — live preview bridge. Owns the `?evf_preview` iframe: tags the form
 * wrapper, injects the rule template, writes token values as CSS variables, and maps clicks
 * inside the iframe back to their style section for click-to-edit.
 */
import { getActiveSync } from './BuilderSync';
import { resolveValue, tokenDeclarations } from './cssVars';
import { ALL_FORCE_CLASSES, PREVIEW_TARGETS } from './constants';
import { StyleStore } from './store';
import { Token } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

const CUSTOM_STYLE_ID = 'evf-scv2-custom-css';
const TEMPLATE_STYLE_ID = 'evf-scv2-rule-template';
const CHROME_STYLE_ID = 'evf-scv2-chrome';
const SELECT_STYLE_ID = 'evf-scv2-select';
const DEVICE_STYLE_ID = 'evf-scv2-device';
const FONT_LINK_ID = 'evf-scv2-font';

/** Legacy `?evf_preview` theme-toggle class: adding it applies theme styling, removing it applies EVF's default. */
const PREVIEW_THEME_CLASS = 'evf-frontend-form-preview';

const HOVER_CLASS = 'evf-scv2-hover';
const SELECTED_CLASS = 'evf-scv2-selected';

/** Mirrors FrontendEnqueue::container_class()'s `evf-choice-{variation}` classes. */
const CHOICE_VARIATION_CLASSES = [ 'evf-choice-outline', 'evf-choice-filled' ];

/** Mirrors FrontendEnqueue::container_class()'s `evf-choice-align-{center|right}` classes. */
const CHOICE_ALIGN_CLASSES = [ 'evf-choice-align-center', 'evf-choice-align-right' ];

/** Mirrors EverestForms_MultiPart::field_submit_visibility_class()'s `everest-forms-nav-align--{value}` class. */
const PAGINATION_NAV_ALIGN_CLASSES = [
	'everest-forms-nav-align--left',
	'everest-forms-nav-align--right',
	'everest-forms-nav-align--center',
	'everest-forms-nav-align--split',
];

// indicatorType can't be live-patched client-side — its themes render genuinely different child
// DOM per value (progress bar vs. an <ol>/<ul> of steps, see
// EverestForms_MultiPart::output_part_indicator()), so it needs the same server-reload path a
// Fields-tab edit uses. Every other pagination.* token (color/margin) has a real CSS var the
// static everest-forms-multi-part.css now reads directly, so those preview instantly like any
// other color/box4 token — no special-casing needed.
const PAGINATION_STRUCTURAL_KEYS = [ 'pagination.indicatorType' ];

/** Mirrors FrontendEnqueue::container_class()'s `evf-btn-width-fill` class. */
const BTN_WIDTH_FILL_CLASS = 'evf-btn-width-fill';

/** How long to keep polling for the form wrapper before giving up (ms). */
const READY_DEADLINE = 15000;
/** Poll interval while waiting for the wrapper (ms). */
const POLL_INTERVAL = 200;

export interface SelectionInfo {
	section: string;
	variant?: string;
	label: string;
}

interface JQueryValidateLike {
	fn: {
		valid?: ( () => boolean ) & { evfScv2Patched?: boolean };
	};
}

interface BridgeHandlers {
	onReady: () => void;
	onError: () => void;
	onSelect?: ( info: SelectionInfo ) => void;
	/** Any click inside the iframe's own document — used to close an open panel popover. */
	onIframeClick?: () => void;
	/** Ctrl/Cmd+Z and Ctrl+Shift+Z / Ctrl+Y pressed inside the iframe's own document — keyboard
	 *  undo/redo would otherwise only work while focus is in the panel, never the preview. */
	onUndo?: () => void;
	onRedo?: () => void;
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
	private onIframeClick?: () => void;
	private onUndo?: () => void;
	private onRedo?: () => void;
	private deadline = 0;
	private pollTimer: ReturnType< typeof setTimeout > | null = null;
	private selectedEl: HTMLElement | null = null;
	private hoverEl: HTMLElement | null = null;
	private previewedKeys: Set< string > = new Set();
	private deviceWidth: number | null = null;
	private currentForceClass: string | null = null;
	private dummyMessageEl: HTMLElement | null = null;
	private mutationObserver: MutationObserver | null = null;
	private observedDoc: Document | null = null;
	private mutationScheduled = false;
	/** Last value actually sent to the server per PAGINATION_STRUCTURAL_KEYS key — lets applyKeys()
	 *  skip the resync when the value didn't really change (e.g. a template hover/revert cycle,
	 *  which always re-applies the whole schema including these keys, but never touches the store). */
	private lastSyncedStructural: Record< string, unknown > = {};

	constructor( iframe: HTMLIFrameElement, store: StyleStore, handlers: BridgeHandlers ) {
		this.iframe = iframe;
		this.store = store;
		this.onReady = handlers.onReady;
		this.onError = handlers.onError;
		this.onSelect = handlers.onSelect;
		this.onIframeClick = handlers.onIframeClick;
		this.onUndo = handlers.onUndo;
		this.onRedo = handlers.onRedo;
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
		this.stopWatching();
		this.iframe.removeEventListener( 'load', this.handleLoad );
		this.teardownSelection();
	}

	private handleLoad = () => {
		// A fresh navigation inside the frame — re-arm and re-detect the wrapper.
		this.ready = false;
		this.wrapper = null;
		this.stopWatching();
		this.deadline = Date.now() + READY_DEADLINE;
		this.poll();
	};

	/** Resolves the form wrapper by id, falling back to the base plugin's `.evf-container` div. */
	private findWrapper( doc: Document ): HTMLElement | null {
		const byId = doc.getElementById( this.store.settings.wrapperId );
		if ( byId ) {
			return byId;
		}
		const fallback = doc.querySelector( '.evf-container' ) as HTMLElement | null;
		if ( fallback ) {
			fallback.id = this.store.settings.wrapperId;
			return fallback;
		}
		return null;
	}

	/** Watches the iframe document for the wrapper being inserted, faster than the fixed-interval poll. */
	private watchForWrapper( doc: Document ) {
		if ( this.observedDoc === doc && this.mutationObserver ) {
			return;
		}
		this.stopWatching();
		if ( ! doc.documentElement || typeof MutationObserver === 'undefined' ) {
			return;
		}
		this.observedDoc = doc;
		this.mutationObserver = new MutationObserver( () => this.onMutation( doc ) );
		this.mutationObserver.observe( doc.documentElement, { childList: true, subtree: true } );
	}

	/** Coalesce bursts of mutations (a full page render fires many) into one check per frame. */
	private onMutation( doc: Document ) {
		if ( this.destroyed || this.ready || this.mutationScheduled ) {
			return;
		}
		this.mutationScheduled = true;
		requestAnimationFrame( () => {
			this.mutationScheduled = false;
			if ( this.destroyed || this.ready ) {
				return;
			}
			try {
				const wrapper = this.findWrapper( doc );
				if ( wrapper ) {
					this.bootstrap( doc, wrapper );
				}
			} catch ( e ) {
				// Swallow — a monkey-patched DOM API (browser extensions) shouldn't wedge detection.
			}
		} );
	}

	private stopWatching() {
		if ( this.mutationObserver ) {
			this.mutationObserver.disconnect();
			this.mutationObserver = null;
		}
		this.observedDoc = null;
	}

	/** Reload the preview page inside the iframe (used when the builder's form structure changes). */
	reload() {
		if ( this.destroyed ) {
			return;
		}
		this.ready = false;
		this.wrapper = null;
		try {
			const win = this.iframe.contentWindow;
			if ( win ) {
				win.location.reload();
				return;
			}
		} catch ( e ) {
			// fall through to the src reset below.
		}
		// eslint-disable-next-line no-self-assign
		this.iframe.src = this.iframe.src;
	}

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
			try {
				this.hideChrome( doc );
				this.watchForWrapper( doc );

				const wrapper = this.findWrapper( doc );
				if ( wrapper ) {
					this.bootstrap( doc, wrapper );
					return;
				}
			} catch ( e ) {
				// Swallow — a monkey-patched DOM API (browser extensions) shouldn't kill wrapper detection.
			}
		}

		if ( Date.now() >= this.deadline ) {
			if ( ! this.ready ) {
				this.onError();
			}
			// Stop polling but leave the MutationObserver attached — a late wrapper still self-heals.
			return;
		}
		this.pollTimer = setTimeout( this.poll, POLL_INTERVAL );
	};

	/** Wrapper found — tag it, inject rules, paint variables, wire selection. */
	private bootstrap( doc: Document, wrapper: HTMLElement ) {
		if ( this.pollTimer ) {
			clearTimeout( this.pollTimer );
			this.pollTimer = null;
		}
		this.stopWatching();
		// A previous dummy message element lived in the old (reloaded) document; it's already gone.
		this.dummyMessageEl = null;
		this.wrapper = wrapper;
		wrapper.classList.add( this.store.settings.markerClass );
		this.disableLegacySheet( doc );
		this.injectRuleTemplate( doc, () => {
			if ( this.destroyed ) {
				return;
			}
			// Extensions can throw mid-sequence; never leave the bridge stuck "not responding".
			try {
				this.applyAll();
				this.applyCustomCss();
				this.applyDeviceWidth();
				this.applyForceClass();
				this.injectSelectionStyles( doc );
				this.setupSelection( doc );
			} catch ( e ) {
				// swallow — see comment above.
			}
			this.ready = true;
			this.onReady();
		} );
	}

	/** Neutralise the legacy per-form compiled stylesheet so v2 tokens always win. */
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

	/** Hides the `?evf_preview` route's page chrome so only the form fills the frame. */
	private hideChrome( doc: Document ) {
		if ( ! doc.head || doc.getElementById( CHROME_STYLE_ID ) ) {
			return;
		}
		const css = `
			html {
				margin-top: 0 !important;
				/* Reserve the scrollbar's width in the layout up front, so a border on a
				   full-width child never falls short of (or is pushed past) the true edge once
				   content grows taller than the iframe's viewport. */
				scrollbar-gutter: stable;
			}
			*, *::before, *::after { box-sizing: border-box; }
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
			/* Below 992px (evf-form-preview.scss) .evf-form-preview-overlay grows a ::after dark
			   scrim (originally the "side panel is open, dim the content behind it" backdrop) —
			   and the template always renders BOTH classes combined on the same element, so this
			   is not conditional at all. The BUILDER'S iframe is very often narrower than 992px on
			   its own, so this triggered on nearly every device/window size — overriding the
			   parent's background above does nothing to it since it is a separate
			   absolutely-positioned pseudo-element box. */
			.evf-form-preview-overlay::after { display: none !important; }
			/* .evf-preview-content only — NOT .everest-forms.evf-frontend-form-preview, which used to
			   be grouped in here too. That rule zeroed the form's own 24px preview-card padding
			   specifically when "Apply Theme Style" was on, making the toggle look like it changes
			   the form's spacing. It doesn't: the real (non-preview) frontend has no such rule tied
			   to that toggle at all (see everest-forms-default-frontend.css) — this 24px is purely
			   this admin preview card's own decoration, unrelated to theme-style. */
			.evf-preview-content { padding: 0 !important; }
			.evf-form-preview-form {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			.evf-preview-content {
				width: 100% !important;
				max-width: 100% !important;
			}`;
		const style = doc.createElement( 'style' );
		style.id = CHROME_STYLE_ID;
		style.textContent = css;
		doc.head.appendChild( style );
	}

	/** Injects the shared rule template, ID-scoped to the wrapper so v2 tokens always win. */
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
				// Last-resort fallback — ensure done() still fires so bootstrap() doesn't stall.
				try {
					const link = doc.createElement( 'link' );
					link.id = TEMPLATE_STYLE_ID;
					link.rel = 'stylesheet';
					link.href = this.store.settings.frontendCssUrl;
					link.onload = () => done();
					link.onerror = () => done();
					doc.head.appendChild( link );
				} catch ( e ) {
					done();
				}
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
		this.ensureFont();
		this.applyThemeStyle();
		// Custom CSS lives outside the schema token loop above — without this, Reset/Undo/Redo
		// (all of which notify(null) via resetAll()/applySnapshot()) leave a stale <style> tag in
		// the iframe even after store.customCss has already changed.
		this.applyCustomCss();
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
		// The font family/theme-font toggle needs the Google-font stylesheet (re)loaded.
		if ( keys.indexOf( 'fonts.family' ) !== -1 || keys.indexOf( 'fonts.theme' ) !== -1 ) {
			this.ensureFont();
		}
		// Ask the server to re-render the pagination indicator, same as a Fields-tab edit does —
		// see PAGINATION_STRUCTURAL_KEYS. Scoped to this targeted-edit path only (never
		// applyAll()'s bulk re-apply, which also runs on device switch/undo/bootstrap) so this
		// can't loop or fire needlessly.
		PAGINATION_STRUCTURAL_KEYS.forEach( ( key ) => {
			if ( keys.indexOf( key ) === -1 ) {
				return;
			}
			const token = this.store.byKey[ key ];
			if ( token ) {
				const value = resolveValue( this.store.tokens[ token.key ], token, this.store.device );
				// applyKeys() also runs on a template hover/revert cycle (previewedKeys always covers
				// the whole schema), which never touches the store — skip the round-trip when the
				// server already has this exact value, so hovering a template can't spam reloads.
				if ( this.lastSyncedStructural[ key ] === value ) {
					return;
				}
				this.lastSyncedStructural[ key ] = value;
				getActiveSync()?.syncStyleToken( token.key, value );
			}
		} );
	}

	/**
	 * Loads (or removes) the selected Google font inside the preview. A template hover passes its
	 * own not-yet-committed family/theme-font instead of reading the store — otherwise the CSS var
	 * flips to the template's font correctly (see previewValues()) but the webfont file is never
	 * fetched, so the browser silently falls back to a system font and the hover looks like it did
	 * nothing.
	 */
	private ensureFont( overrideFamily?: string, overrideThemeFont?: boolean ) {
		const wrapper = this.wrapper;
		if ( ! wrapper ) {
			return;
		}
		const doc = wrapper.ownerDocument;
		const token = this.store.byKey[ 'fonts.family' ];
		const family =
			overrideFamily !== undefined
				? overrideFamily.trim()
				: token
					? String( resolveValue( this.store.tokens[ 'fonts.family' ], token, 'desktop' ) || '' ).trim()
					: '';
		const themeFont = overrideThemeFont !== undefined ? overrideThemeFont : this.store.themeFont();
		let link = doc.getElementById( FONT_LINK_ID ) as HTMLLinkElement | null;

		if ( themeFont || ! family ) {
			if ( link ) {
				link.remove();
			}
			return;
		}
		// Explicit weights, matching Schema::weight_options() (EVF-2721) — otherwise Google Fonts
		// only serves the family's single default face and every other Font Style weight falls
		// back to inconsistent browser synthesis.
		const href = 'https://fonts.googleapis.com/css?family=' + encodeURIComponent( family ) + ':300,400,700';
		if ( ! link ) {
			link = doc.createElement( 'link' );
			link.id = FONT_LINK_ID;
			link.rel = 'stylesheet';
			doc.head.appendChild( link );
		}
		if ( link.href !== href ) {
			link.href = href;
		}
	}

	/** Reflects "Apply Theme Style" live, mirroring the legacy `?evf_preview` toggle. */
	private applyThemeStyle() {
		const wrapper = this.wrapper;
		if ( ! wrapper ) {
			return;
		}
		const outer = ( wrapper.closest( '.everest-forms' ) as HTMLElement | null ) || wrapper.parentElement || wrapper;
		outer.classList.toggle( PREVIEW_THEME_CLASS, this.store.applyThemeStyle );
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

		// choice.variation is a "meta" token (no CSS var) — it drives a wrapper class instead.
		if ( token.key === 'choice.variation' ) {
			CHOICE_VARIATION_CLASSES.forEach( ( c ) => wrapper.classList.remove( c ) );
			if ( value === 'outline' || value === 'filled' ) {
				wrapper.classList.add( `evf-choice-${ value }` );
			}
		}

		// choice.align DOES have a CSS var (used by every other choice type's inherited
		// text-align), but the Subscription Plan card's name/price row can't be reached by
		// text-align at all (it's a fixed `space-between` flex row) — mirror the same class
		// bridge as choice.variation, purely as a supplementary hook for that one field.
		if ( token.key === 'choice.align' ) {
			CHOICE_ALIGN_CLASSES.forEach( ( c ) => wrapper.classList.remove( c ) );
			if ( value === 'center' || value === 'right' ) {
				wrapper.classList.add( `evf-choice-align-${ value }` );
			}
		}

		// pagination.navAlign is a Multi-Part "meta" token (no CSS var) — it's pure positioning on
		// markup that already exists regardless of value, so (unlike indicatorType, whose themes
		// render genuinely different child DOM) a class toggle is enough for live preview. Applied
		// to the nav container itself, matching EverestForms_MultiPart::field_submit_visibility_class().
		if ( token.key === 'pagination.navAlign' ) {
			const nav = wrapper.querySelector( '.everest-forms-multi-part-actions' );
			if ( nav ) {
				PAGINATION_NAV_ALIGN_CLASSES.forEach( ( c ) => nav.classList.remove( c ) );
				if ( typeof value === 'string' && value ) {
					nav.classList.add( `everest-forms-nav-align--${ value }` );
				}
			}
		}

		// btn.widthMode is another "meta" token (no CSS var) — same class-bridge pattern.
		if ( token.key === 'btn.widthMode' ) {
			wrapper.classList.remove( BTN_WIDTH_FILL_CLASS );
			if ( value === 'fill' ) {
				wrapper.classList.add( BTN_WIDTH_FILL_CLASS );
			}
		}
	}

	/** Every CSS var a token can set (font-style expands to four). */
	private varsFor( token: Token ): string[] {
		if ( token.type === 'fontstyle' && token.vars ) {
			return Object.values( token.vars );
		}
		return token.var ? [ token.var ] : [];
	}

	/**
	 * Constrain the form's content width inside the iframe to simulate a device.
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
		style.textContent = `.evf-form-preview-form,.evf-preview-content{max-width:${ this.deviceWidth }px!important;margin-left:auto!important;margin-right:auto!important;transition:max-width .25s ease;}`;
	}

	/** Toggle a single force-state class (focus/hover/message) for state previews. */
	setForceClass( cls: string | null ) {
		this.currentForceClass = cls;
		this.applyForceClass();
	}

	/** Re-applies the currently active force-state class (also called on every bootstrap after a reload). */
	private applyForceClass() {
		if ( ! this.wrapper ) {
			return;
		}
		ALL_FORCE_CLASSES.forEach( ( c ) => this.wrapper!.classList.remove( c ) );
		if ( this.currentForceClass ) {
			this.wrapper.classList.add( this.currentForceClass );
		}
		this.setDummyMessage( this.currentForceClass );
	}

	/** Injects a throwaway instance of EVF's real notice/error markup so its styling can be previewed live. */
	private setDummyMessage( cls: string | null ) {
		if ( this.dummyMessageEl ) {
			this.dummyMessageEl.remove();
			this.dummyMessageEl = null;
		}
		const wrapper = this.wrapper;
		if ( ! wrapper || ! cls ) {
			return;
		}
		const doc = wrapper.ownerDocument;
		let el: HTMLElement | null = null;

		if ( 'force-msg-success' === cls ) {
			el = doc.createElement( 'div' );
			el.className = 'everest-forms-notice everest-forms-notice--success';
			el.setAttribute( 'role', 'alert' );
			el.textContent = __( 'Thanks! Your submission has been received.', 'everest-forms' );
		} else if ( 'force-msg-error' === cls ) {
			el = doc.createElement( 'div' );
			el.className = 'everest-forms-notice everest-forms-notice--error';
			el.setAttribute( 'role', 'alert' );
			el.textContent = __( 'There was a problem with your submission. Please review the fields below.', 'everest-forms' );
		} else if ( 'force-msg-validation' === cls ) {
			el = doc.createElement( 'label' );
			el.className = 'everest-forms-error evf-error';
			el.textContent = __( 'This field is required.', 'everest-forms' );
		}
		if ( ! el ) {
			return;
		}
		el.setAttribute( 'data-evf-scv2-dummy', '1' );

		if ( 'force-msg-validation' === cls ) {
			// Mirrors the real inline-validation placement (assets/js/frontend/ajax-submission.js):
			// the error label lands right after the field's input (or its .input-wrapper), inside
			// the FIRST field itself. `.evf-frontend-row` is an ANCESTOR of `.evf-field` (a row can
			// hold several fields side by side via a grid), so a combined `.evf-field, .evf-frontend-row`
			// selector matched the row first in document order — landing the message after the whole
			// row, or the whole form when a row wrapper wasn't found at all.
			const field = wrapper.querySelector( '.evf-field' );
			const control = field ? field.querySelector( '.input-wrapper, input, select, textarea' ) : null;
			if ( control ) {
				control.insertAdjacentElement( 'afterend', el );
			} else {
				( field || wrapper ).appendChild( el );
			}
		} else {
			wrapper.insertBefore( el, wrapper.firstChild );
		}
		this.dummyMessageEl = el;
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

	/** Temporarily paints token values onto the preview without touching the store (template hover previews). */
	previewValues( overrides: Record< string, unknown > ) {
		if ( ! this.wrapper ) {
			return;
		}
		// Use THIS preview's own fonts.theme (a template hover always carries one — see
		// flattenForPreview() in panes.tsx) rather than the store's current, not-yet-applied value —
		// otherwise a template that turns theme-font off still previews with it forced on, because
		// clicking would change fonts.theme but hovering never touches the store at all.
		const themeFont = this.store.themeFont( overrides[ 'fonts.theme' ] as boolean | undefined );
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
		// The var above is enough for every other property, but a font also needs its webfont
		// FILE loaded — without this the var flips correctly but the browser has nothing to render
		// it with and silently falls back to a system font, looking like the hover did nothing.
		if ( 'fonts.family' in overrides || 'fonts.theme' in overrides ) {
			this.ensureFont( overrides[ 'fonts.family' ] as string | undefined, themeFont );
		}
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
		// pointer-events guards against a theme/plugin reset rule disabling hit-testing in the preview.
		const css = `
			#${ this.store.settings.wrapperId }, #${ this.store.settings.wrapperId } * { pointer-events: auto !important; }
			#${ this.store.settings.wrapperId } * { transition: outline-color .12s ease, outline-offset .12s ease; }
			.${ HOVER_CLASS } { outline: 1px dashed rgba(117,69,187,.35) !important; outline-offset: 2px !important; border-radius: 3px; }
			.${ SELECTED_CLASS } { outline: 1.5px solid rgba(117,69,187,.5) !important; outline-offset: 2px !important; border-radius: 3px; }
			#${ this.store.settings.wrapperId } * { cursor: default; }
			@media (prefers-reduced-motion: reduce) {
				#${ this.store.settings.wrapperId } * { transition: none !important; }
			}`;
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
		doc.addEventListener( 'keydown', this.onDocKeyDown, true );
		this.neutralizeMultiPartValidation( doc );
	}

	/** Ctrl/Cmd+Z and Ctrl+Shift+Z / Ctrl+Y — mirrors the panel's own shortcut so undo/redo works
	 *  no matter which side (panel or preview) currently has focus. */
	private onDocKeyDown = ( e: KeyboardEvent ) => {
		const target = e.target as HTMLElement;
		if ( target && /^(INPUT|TEXTAREA|SELECT)$/.test( target.tagName ) ) {
			return;
		}
		if ( ( e.ctrlKey || e.metaKey ) && e.key.toLowerCase() === 'z' ) {
			e.preventDefault();
			if ( e.shiftKey ) {
				this.onRedo?.();
			} else {
				this.onUndo?.();
			}
			return;
		}
		if ( e.ctrlKey && ! e.metaKey && e.key.toLowerCase() === 'y' ) {
			e.preventDefault();
			this.onRedo?.();
		}
	};

	/** Patches jQuery-validate's `.valid()` to always pass, so Multi-Part's "Next" works without filling required fields. */
	private neutralizeMultiPartValidation( doc: Document ) {
		const $ = ( doc.defaultView as ( Window & { jQuery?: JQueryValidateLike } ) | null )?.jQuery;
		if ( ! $ || typeof $.fn.valid !== 'function' || $.fn.valid.evfScv2Patched ) {
			return;
		}
		const alwaysValid = () => true;
		alwaysValid.evfScv2Patched = true;
		$.fn.valid = alwaysValid;
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
		doc.removeEventListener( 'keydown', this.onDocKeyDown, true );
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
		const navBtn = el.closest( '.everest-forms-part-button' );
		if ( navBtn && wrapper.contains( navBtn ) ) {
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
		// Fires for every click, even ones that don't resolve to a style target.
		if ( this.onIframeClick ) {
			this.onIframeClick();
		}
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
