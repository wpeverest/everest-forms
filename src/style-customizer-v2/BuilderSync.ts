/**
 * Style Customizer v2 — Fields ↔ Style live synchronisation.
 *
 * The style preview is the real front-end form rendered through `?evf_preview`, which reads the
 * SAVED form. So builder edits (labels, placeholders, descriptions, adding/deleting/duplicating/
 * reordering fields) wouldn't appear in the Style panel until the form is saved — the reported
 * sync gap.
 *
 * This controller closes it: whenever the builder's structure changes (the Style tab is opened,
 * a field is edited/added/removed, or the form is saved) it serialises the builder's CURRENT form
 * exactly as the save AJAX does, POSTs it to the `preview-draft` endpoint (cached as a per-user
 * transient by PreviewDraft.php) and reloads the preview iframe so the server re-renders the live
 * structure. The live CSS-variable edits are re-applied automatically once the frame reloads.
 *
 * It is event-driven and cheap:
 *  - it only reloads while the Style tab is actually showing (no work while editing elsewhere);
 *  - a structural signature skips redundant reloads (unchanged structure → no reload);
 *  - bursts of edits are debounced into a single refresh.
 *
 * Same-origin builder DOM, so it reads the live jQuery form directly — no postMessage needed.
 */
import { getActiveBridge } from './PreviewBridge';
import { StyleStore } from './store';

const apiFetch = ( window as any ).wp?.apiFetch;

const BUILDER_FORM_ID = 'everest-forms-builder-form';
const FIELD_WRAPPER_SELECTOR = '.evf-admin-field-wrapper';
const STYLE_PANEL_ID = 'everest-forms-panel-style';

/** Debounce for builder-change bursts (drag, typing) before a single preview refresh (ms). */
const CHANGE_DEBOUNCE = 500;

interface SerializedItem {
	name: string;
	value: string;
}

export class BuilderSync {
	private store: StyleStore;
	private stylePanel: HTMLElement | null = null;
	private active = false;
	private started = false;

	/** Signature of the structure currently rendered in the preview (baseline = the saved form). */
	private lastSignature: string | null = null;
	/** A sync was requested before the bridge was ready — run it once the frame loads. */
	private pendingSync = false;
	/** Coalesce a burst of builder changes into one refresh. */
	private changeTimer: ReturnType< typeof setTimeout > | null = null;
	/** Guards against overlapping draft POST + reload cycles. */
	private syncing = false;

	private fieldObserver: MutationObserver | null = null;
	private panelObserver: MutationObserver | null = null;

	constructor( store: StyleStore ) {
		this.store = store;
	}

	/* --------------------------------------------------------------------- *
	 * Lifecycle
	 * --------------------------------------------------------------------- */

	start() {
		if ( this.started ) {
			return;
		}
		this.started = true;

		this.stylePanel = document.getElementById( STYLE_PANEL_ID );
		// Baseline: the builder's structure at load == what the freshly-loaded iframe renders
		// (the saved form). Storing it now means the first Style-tab open with no edits is a no-op.
		this.lastSignature = this.serialize().signature;
		this.active = this.isStyleTabActive();

		// The single most important trigger: the Style tab becoming visible. A MutationObserver on
		// the panel's class catches EVERY activation path (tab click, URL, programmatic switch).
		if ( this.stylePanel ) {
			this.panelObserver = new MutationObserver( () => this.onPanelToggle() );
			this.panelObserver.observe( this.stylePanel, { attributes: true, attributeFilter: [ 'class' ] } );
		}

		// Belt-and-suspenders live sync: if the builder DOM/inputs change while the Style tab is
		// showing, refresh too (debounced). Field edits normally happen on the Fields tab, but this
		// keeps the preview honest for any flow that mutates the builder with Style open.
		document.addEventListener( 'input', this.onBuilderInput, true );
		document.addEventListener( 'change', this.onBuilderInput, true );

		const wrapper = document.querySelector( FIELD_WRAPPER_SELECTOR );
		if ( wrapper ) {
			this.fieldObserver = new MutationObserver( () => this.onFieldMutation() );
			this.fieldObserver.observe( wrapper, { childList: true, subtree: true } );
		}

		// After a real save the DB is authoritative — refresh to the saved truth (clears any draft
		// drift). jQuery event fired by the builder on save success.
		const jq = ( window as any ).jQuery;
		if ( jq ) {
			jq( document ).on( 'everest_forms_save_data.evfscv2', this.onSave );
		}
	}

	stop() {
		this.started = false;
		if ( this.changeTimer ) {
			clearTimeout( this.changeTimer );
			this.changeTimer = null;
		}
		if ( this.panelObserver ) {
			this.panelObserver.disconnect();
			this.panelObserver = null;
		}
		if ( this.fieldObserver ) {
			this.fieldObserver.disconnect();
			this.fieldObserver = null;
		}
		document.removeEventListener( 'input', this.onBuilderInput, true );
		document.removeEventListener( 'change', this.onBuilderInput, true );
		const jq = ( window as any ).jQuery;
		if ( jq ) {
			jq( document ).off( 'everest_forms_save_data.evfscv2', this.onSave );
		}
	}

	/** Called by the preview bridge once a (re)load has fully bridged — flush any pending sync. */
	onBridgeReady() {
		if ( this.pendingSync ) {
			this.pendingSync = false;
			this.syncPreview( false );
		}
	}

	/* --------------------------------------------------------------------- *
	 * Triggers
	 * --------------------------------------------------------------------- */

	private isStyleTabActive(): boolean {
		return !! this.stylePanel && this.stylePanel.classList.contains( 'active' );
	}

	private onPanelToggle() {
		const nowActive = this.isStyleTabActive();
		if ( nowActive === this.active ) {
			return;
		}
		this.active = nowActive;
		if ( nowActive ) {
			// Reflect whatever changed while we were away on another tab.
			this.syncPreview( false );
		}
	}

	private onBuilderInput = ( e: Event ) => {
		if ( ! this.active ) {
			return;
		}
		const target = e.target as HTMLElement | null;
		if ( ! target || ! target.closest ) {
			return;
		}
		// The Style panel lives INSIDE the builder form, so its own React controls (colour hex,
		// sliders, …) fire input/change too. Ignore those — they change styles (CSS variables),
		// never field structure, so they must not schedule a structure re-sync.
		if ( target.closest( '#' + STYLE_PANEL_ID ) ) {
			return;
		}
		if ( target.closest( '#' + BUILDER_FORM_ID ) ) {
			this.scheduleSync();
		}
	};

	private onFieldMutation() {
		if ( ! this.active ) {
			return;
		}
		this.scheduleSync();
	}

	private onSave = () => {
		// The builder just persisted; the preview must reflect the saved form. Only refresh if the
		// Style tab is showing — otherwise the next activation picks it up via the signature check.
		if ( this.active ) {
			this.syncPreview( true );
		} else {
			this.lastSignature = null; // Force a refresh on the next Style-tab open.
		}
	};

	private scheduleSync() {
		if ( this.changeTimer ) {
			clearTimeout( this.changeTimer );
		}
		this.changeTimer = setTimeout( () => {
			this.changeTimer = null;
			this.syncPreview( false );
		}, CHANGE_DEBOUNCE );
	}

	/* --------------------------------------------------------------------- *
	 * The sync itself
	 * --------------------------------------------------------------------- */

	/**
	 * Push the builder's current structure to the preview and reload the iframe.
	 *
	 * @param force Bypass the signature short-circuit (e.g. after save).
	 */
	private async syncPreview( force: boolean ) {
		if ( this.syncing ) {
			// A cycle is already in flight; let it finish, then re-evaluate.
			this.pendingSync = true;
			return;
		}

		const bridge = getActiveBridge();
		if ( ! bridge || ! bridge.isReady() ) {
			// The frame hasn't finished its first load — run once it has (onBridgeReady).
			this.pendingSync = true;
			return;
		}

		const { json, signature } = this.serialize();
		if ( ! json ) {
			return; // Nothing to render (no builder form / no fields).
		}
		if ( ! force && signature === this.lastSignature ) {
			return; // Structure unchanged since the preview last rendered.
		}

		if ( ! apiFetch ) {
			return;
		}

		this.syncing = true;
		try {
			await apiFetch( {
				path: `${ this.store.settings.restBase }/${ this.store.settings.formId }/preview-draft`,
				method: 'POST',
				data: { form_data: json, session: this.store.settings.previewSession },
			} );
			this.lastSignature = signature;
			// Server has cached the draft; reload so it re-renders the current structure.
			getActiveBridge()?.reload();
		} catch ( err ) {
			// Draft POST failed — leave the current preview as-is rather than reloading to a stale
			// state. The next trigger will retry.
			// eslint-disable-next-line no-console
			if ( ( window as any ).console ) {
				// eslint-disable-next-line no-console
				console.warn( 'EVF Style: preview sync failed', err );
			}
		} finally {
			this.syncing = false;
			if ( this.pendingSync ) {
				this.pendingSync = false;
				// A change landed mid-flight — re-run to catch it.
				this.scheduleSync();
			}
		}
	}

	/* --------------------------------------------------------------------- *
	 * Serialisation (mirrors the builder's own save payload)
	 * --------------------------------------------------------------------- */

	/**
	 * Serialise the builder form exactly as {@see EVFPanelBuilder.formSave}/`getStructure` do:
	 * the form inputs (`form_fields[…]`, `settings[…]`, …) plus the layout structure. The result
	 * feeds PreviewDraft::parse() server-side, which mirrors EVF_AJAX::save_form(), so the draft
	 * renders identically to a saved form.
	 */
	private serialize(): { json: string; signature: string } {
		const jq = ( window as any ).jQuery;
		if ( ! jq ) {
			return { json: '', signature: '' };
		}
		const form = jq( '#' + BUILDER_FORM_ID );
		if ( ! form.length ) {
			return { json: '', signature: '' };
		}

		const formData: SerializedItem[] = form.serializeArray();
		const structure = this.getStructure( jq );
		const all = formData.concat( structure );

		return { json: JSON.stringify( all ), signature: this.signatureFrom( all ) };
	}

	/** Replicate the builder's `getStructure()` (field layout: rows → grids → ordered field ids). */
	private getStructure( jq: any ): SerializedItem[] {
		const structure: SerializedItem[] = [];
		const wrapper = jq( FIELD_WRAPPER_SELECTOR );

		wrapper.find( '.evf-admin-row' ).each( function ( this: HTMLElement ) {
			const $row = jq( this );
			const rowId = $row.attr( 'data-row-id' );

			$row.find( '.evf-admin-grid' ).each( function ( this: HTMLElement ) {
				const $grid = jq( this );
				const gridId = $grid.attr( 'data-grid-id' );
				const $fields = $grid.find( '.everest-forms-field' );

				let index = 0;
				$fields.each( function ( this: HTMLElement ) {
					structure.push( {
						name: `structure[row_${ rowId }][grid_${ gridId }][${ index }]`,
						value: jq( this ).attr( 'data-field-id' ),
					} );
					index++;
				} );

				if ( $fields.length < 1 ) {
					structure.push( { name: `structure[row_${ rowId }][grid_${ gridId }]`, value: '' } );
				}
			} );
		} );

		return structure;
	}

	/**
	 * A signature of only the render-relevant inputs (field data + layout), so changes to
	 * unrelated builder settings (email, integrations, …) never trigger a wasteful preview reload.
	 */
	private signatureFrom( all: SerializedItem[] ): string {
		const relevant = all.filter(
			( i ) => i.name.indexOf( 'form_fields[' ) === 0 || i.name.indexOf( 'structure[' ) === 0
		);
		return JSON.stringify( relevant );
	}
}

let active: BuilderSync | null = null;

export function setActiveSync( sync: BuilderSync | null ) {
	active = sync;
}

export function getActiveSync(): BuilderSync | null {
	return active;
}
