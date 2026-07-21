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

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

const CUSTOM_STYLE_ID = 'evf-scv2-custom-css';
const TEMPLATE_STYLE_ID = 'evf-scv2-rule-template';
const CHROME_STYLE_ID = 'evf-scv2-chrome';
const SELECT_STYLE_ID = 'evf-scv2-select';
const DEVICE_STYLE_ID = 'evf-scv2-device';
const FONT_LINK_ID = 'evf-scv2-font';

/**
 * The legacy `?evf_preview` theme-toggle class. `evf-form-preview.css` (already loaded on that
 * route) styles the form with Everest Forms' default look via
 * `.everest-forms:not(.evf-frontend-form-preview) …`, so ADDING this class = theme styling,
 * REMOVING it = EVF default styling — exactly the v1 preview behaviour.
 */
const PREVIEW_THEME_CLASS = 'evf-frontend-form-preview';

const HOVER_CLASS = 'evf-scv2-hover';
const SELECTED_CLASS = 'evf-scv2-selected';

/** Mirrors FrontendEnqueue::container_class()'s `evf-choice-{variation}` classes (EVF-2675). */
const CHOICE_VARIATION_CLASSES = [ 'evf-choice-outline', 'evf-choice-filled' ];

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
	/** Any click inside the iframe's OWN document (which the parent document's own outside-click
	 *  listeners never see, since an iframe has its own separate document) — used to close any
	 *  open panel popover, which otherwise stayed open forever once you clicked into the preview. */
	onIframeClick?: () => void;
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

	constructor( iframe: HTMLIFrameElement, store: StyleStore, handlers: BridgeHandlers ) {
		this.iframe = iframe;
		this.store = store;
		this.onReady = handlers.onReady;
		this.onError = handlers.onError;
		this.onSelect = handlers.onSelect;
		this.onIframeClick = handlers.onIframeClick;
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
		// A fresh navigation inside the frame (reload on a builder-structure change, or rare theme
		// redirects) — re-arm and re-detect, then re-bootstrap (vars, custom CSS, selection). The
		// old document's observer (if any) is now moot — a new one is attached for the new doc.
		this.ready = false;
		this.wrapper = null;
		this.stopWatching();
		this.deadline = Date.now() + READY_DEADLINE;
		this.poll();
	};

	/**
	 * Resolve the form wrapper: the exact `#evf-{id}` id the compiler scopes variables to, or —
	 * if that id is missing for any reason (markup variance, a slow/late write) — the base
	 * plugin's `.evf-container` wrapper div (see class-evf-shortcode-form.php), which every
	 * rendered form has exactly one of. Falling back keeps the live-edit bridge working instead
	 * of declaring it unavailable purely because of an id mismatch.
	 */
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

	/**
	 * Watch the iframe document for the wrapper being inserted, as a supplement to (and faster
	 * than) the fixed-interval poll below. Crucially, this observer is left running even past the
	 * poll's own deadline (until `bootstrap()` or `detach()` stops it) — so a wrapper that shows up
	 * late (a slow theme script, an unusually heavy page) still self-heals into a working live-edit
	 * bridge instead of a permanent failure, which previously required a manual retry.
	 */
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
				// A same-origin document is fair game for OTHER extensions' content scripts too
				// (ad blockers, password managers, …), which can monkey-patch/break DOM APIs we
				// depend on here. Swallow it — the fixed-interval poll (below) gets another shot
				// on its own next tick rather than this observer permanently wedging detection.
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

	/**
	 * Reload the preview page inside the iframe. Used when the builder's form STRUCTURE changes
	 * (fields added/removed/reordered, labels/placeholders edited) so the server re-renders the
	 * current form; the live style variables are re-applied automatically once the fresh page
	 * loads (via {@see handleLoad}). Falls back to re-assigning `src` if the frame can't be
	 * scripted for any reason.
	 */
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
			// Fall through to the src reset below.
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
				// Hide the preview chrome as early as possible so the frame never flashes the full
				// front-end page or a dark overlay while we wait for the wrapper.
				this.hideChrome( doc );
				// Instant-detection supplement to this timer (see watchForWrapper docblock) — also
				// the self-healing path once the deadline below is reached.
				this.watchForWrapper( doc );

				const wrapper = this.findWrapper( doc );
				if ( wrapper ) {
					this.bootstrap( doc, wrapper );
					return;
				}
			} catch ( e ) {
				// A browser extension's content script running in this SAME-ORIGIN document (ad
				// blockers, password managers, corporate security tools, …) can throw from a
				// monkey-patched DOM API on any given tick. One bad tick must not permanently kill
				// wrapper detection — swallow it and let the next poll (below) try again.
			}
		}

		if ( Date.now() >= this.deadline ) {
			if ( ! this.ready ) {
				this.onError();
			}
			// Stop the fixed-interval timer, but leave the MutationObserver attached (if the doc
			// was ever reached) — a late-appearing wrapper still bootstraps successfully instead
			// of the live-edit bridge staying permanently unavailable.
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
		// A fresh document (reload) makes any previously-injected dummy message element a dead
		// reference — it lives in the OLD document and is already gone.
		this.dummyMessageEl = null;
		this.wrapper = wrapper;
		wrapper.classList.add( this.store.settings.markerClass );
		this.disableLegacySheet( doc );
		this.injectRuleTemplate( doc, () => {
			if ( this.destroyed ) {
				return;
			}
			// A same-origin document is fair game for OTHER extensions' content scripts too (ad
			// blockers, password managers, corporate security tools, …), which routinely inject
			// into every frame and can throw from a monkey-patched DOM API partway through this
			// sequence. Never let that leave the bridge permanently stuck "not responding" —
			// whichever step failed, still flip ready/onReady so click-to-edit (and whatever DID
			// apply) survive rather than nothing at all.
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
			.evf-preview-content,
			.everest-forms.evf-frontend-form-preview { padding: 0 !important; }
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
				// Last-resort fallback — if even THIS throws (a DOM API corrupted badly enough
				// that neither the fetch+inline-style path nor a plain <link> can be created),
				// still call `done()` so bootstrap() unblocks instead of stalling forever.
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
	}

	/**
	 * Load (or remove) the selected Google font inside the preview so the chosen family actually
	 * renders — mirrors the front-end `evfsc_enqueue_fonts()`. No custom font while "use theme
	 * fonts" is on or no family is set.
	 */
	private ensureFont() {
		const wrapper = this.wrapper;
		if ( ! wrapper ) {
			return;
		}
		const doc = wrapper.ownerDocument;
		const token = this.store.byKey[ 'fonts.family' ];
		const family = token
			? String( resolveValue( this.store.tokens[ 'fonts.family' ], token, 'desktop' ) || '' ).trim()
			: '';
		let link = doc.getElementById( FONT_LINK_ID ) as HTMLLinkElement | null;

		if ( this.store.themeFont() || ! family ) {
			if ( link ) {
				link.remove();
			}
			return;
		}
		const href = 'https://fonts.googleapis.com/css?family=' + encodeURIComponent( family );
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

	/**
	 * Reflect "Apply Theme Style" live in the preview, mirroring the legacy `?evf_preview` toggle
	 * exactly: the class goes on the OUTER `.everest-forms` ancestor (not the `#evf-{id}`
	 * container), and `evf-form-preview.css` — already loaded on this route — provides the default
	 * (non-theme) look via `.everest-forms:not(.evf-frontend-form-preview)`. So ON (theme) adds the
	 * class (the active theme styles the form) and OFF removes it (EVF default rules apply). The v2
	 * token rules are ID-scoped, so they still win over whichever baseline is active.
	 */
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

		// choice.variation has no CSS var (a "meta" token, per Compiler::declarations()) — it
		// drives a wrapper class instead, matching FrontendEnqueue::container_class() on the real
		// frontend: legacy only gave radio/checkbox a custom appearance (and so only ever showed
		// the selected-colour/unselected-border settings) for outline/filled, never default.
		if ( token.key === 'choice.variation' ) {
			CHOICE_VARIATION_CLASSES.forEach( ( c ) => wrapper.classList.remove( c ) );
			if ( value === 'outline' || value === 'filled' ) {
				wrapper.classList.add( `evf-choice-${ value }` );
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
		this.currentForceClass = cls;
		this.applyForceClass();
	}

	/** Re-apply the currently active force-state class — called from setForceClass AND from every
	 *  bootstrap(), so a reload (e.g. BuilderSync's post-save refresh) doesn't silently drop an
	 *  active Messages/focus/hover preview the way applyAll/applyDeviceWidth already don't. */
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

	/**
	 * Messages (success/error/field-validation) only ever exist in the DOM after a real form
	 * submission — there is no other way to preview their styling. While a Messages state tab is
	 * active, inject a real, throwaway instance of the exact markup EVF's own templates render
	 * (templates/notices/success.php, error.php; the per-field validation `<label>` from
	 * class-evf-form-fields.php) so its computed style is actually visible and editable live.
	 * Swapped out the instant the state tab changes; never touches real submission data.
	 */
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
			// A field-level error reads as real only sitting inside an actual field, right after
			// its input — not floating at the top of the form.
			const field = wrapper.querySelector( '.evf-field, .evf-frontend-row' );
			( field || wrapper ).appendChild( el );
		} else {
			// Success/error banners render above the fields, exactly as a real submission would.
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
		// Subtle, non-alarming selection affordance: a thin dashed hover hint and a soft, low-
		// opacity selected ring (not a bold solid outline). The `pointer-events` assertion guards
		// against any theme/plugin reset rule disabling hit-testing inside the preview — harmless
		// here since this stylesheet only ever loads on the style-panel preview route, never the
		// real front end.
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
		this.neutralizeMultiPartValidation( doc );
	}

	/**
	 * The Multi-Part Forms addon's "Next" handler silently no-ops if any visible required field
	 * in the current part fails jQuery-validate's `.valid()` — correct on the real front-end,
	 * but it would force typing dummy data into every required field just to preview a later
	 * part's styling. Real submission is already sandboxed (see blockEvent above), so
	 * always-valid is safe here too.
	 */
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
		// Fires for EVERY click in the iframe, regardless of whether it resolves to a style
		// target below — a popover open in the parent panel has no other way to learn about this.
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
