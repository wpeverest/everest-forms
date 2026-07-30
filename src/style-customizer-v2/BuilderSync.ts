/**
 * Style Customizer v2 — Fields/Settings ↔ Style live synchronisation. Serialises the builder's
 * current form (fields, layout, settings) to the `preview-draft` endpoint, applies any section/
 * schema visibility the response recomputed, and reloads the preview iframe when it changes.
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
	/** Structural style-token overrides (see syncStyleToken()) waiting to go out on the next POST. */
	private pendingStyleTokens: Record< string, unknown > = {};

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
		this.lastSignature = this.serialize().signature;
		this.active = this.isStyleTabActive();

		if ( this.stylePanel ) {
			this.panelObserver = new MutationObserver( () => this.onPanelToggle() );
			this.panelObserver.observe( this.stylePanel, { attributes: true, attributeFilter: [ 'class' ] } );
		}

		document.addEventListener( 'input', this.onBuilderInput, true );
		document.addEventListener( 'change', this.onBuilderInput, true );

		const wrapper = document.querySelector( FIELD_WRAPPER_SELECTOR );
		if ( wrapper ) {
			this.fieldObserver = new MutationObserver( () => this.onFieldMutation() );
			this.fieldObserver.observe( wrapper, { childList: true, subtree: true } );
		}

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
		// The Style panel's own controls live inside the builder form and fire input/change too;
		// ignore those, they change styles, not field structure.
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
		if ( this.active ) {
			this.syncPreview( true );
		} else {
			this.lastSignature = null; // force a refresh on the next Style-tab open.
		}
	};

	/**
	 * Push a "structural" style-token value (one with no CSS-variable fast path — e.g.
	 * Pagination's indicator TYPE, whose themes render genuinely different markup, not just a
	 * class/variable — see PreviewBridge.ts's applyKeys()) to the server and reload the preview
	 * once it's re-rendered with it. Queued on the instance so it survives a busy/not-ready sync
	 * being retried, and rides along with whatever POST goes out next.
	 */
	syncStyleToken( key: string, value: unknown ) {
		this.pendingStyleTokens[ key ] = value;
		this.syncPreview( false );
	}

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
			this.pendingSync = true;
			return;
		}

		const bridge = getActiveBridge();
		if ( ! bridge || ! bridge.isReady() ) {
			this.pendingSync = true;
			return;
		}

		const { json, signature } = this.serialize();
		if ( ! json ) {
			return;
		}
		const styleTokens = this.pendingStyleTokens;
		const hasStyleTokens = Object.keys( styleTokens ).length > 0;
		if ( ! force && ! hasStyleTokens && signature === this.lastSignature ) {
			return;
		}

		if ( ! apiFetch ) {
			return;
		}

		this.syncing = true;
		try {
			const data: Record< string, unknown > = { form_data: json, session: this.store.settings.previewSession };
			if ( hasStyleTokens ) {
				data.style_tokens = styleTokens;
			}
			const res = await apiFetch( {
				path: `${ this.store.settings.restBase }/${ this.store.settings.formId }/preview-draft`,
				method: 'POST',
				data,
			} );
			this.lastSignature = signature;
			this.pendingStyleTokens = {};
			// The server recomputes section/token visibility against this same draft (e.g. a
			// conditional section a Pro addon just became eligible/ineligible for) — apply it
			// before the reload so the panel's sidebar is in sync with what the new iframe renders.
			if ( res && res.sections && Array.isArray( res.schema ) ) {
				this.store.setVisibility( res.sections, res.schema );
			}
			getActiveBridge()?.reload();
		} catch ( err ) {
			// eslint-disable-next-line no-console
			if ( ( window as any ).console ) {
				// eslint-disable-next-line no-console
				console.warn( 'EVF Style: preview sync failed', err );
			}
		} finally {
			this.syncing = false;
			if ( this.pendingSync ) {
				this.pendingSync = false;
				this.scheduleSync();
			}
		}
	}

	/* --------------------------------------------------------------------- *
	 * Serialisation (mirrors the builder's own save payload)
	 * --------------------------------------------------------------------- */

	/** Serialises the builder form the same way the real save AJAX does. */
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
	 * Signature of only the render-relevant inputs: fields, layout, AND settings — settings are
	 * included because a Pro addon's conditional section/group (e.g. Multi-Part's "Enable
	 * Multi-Part form" toggle gating the Pagination section) can depend on one, not just on
	 * fields/structure.
	 */
	private signatureFrom( all: SerializedItem[] ): string {
		const relevant = all.filter(
			( i ) =>
				i.name.indexOf( 'form_fields[' ) === 0 ||
				i.name.indexOf( 'structure[' ) === 0 ||
				i.name.indexOf( 'settings[' ) === 0
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
