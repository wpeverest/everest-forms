/**
 * Style Customizer v2 — builder panel entry.
 *
 * Mounts into the "Style" builder tab and renders the two-pane panel (controls + live preview).
 * The token contract is never hardcoded — it comes from the server.
 *
 * Every value the panel needs on first paint (schema, sections, palettes, templates, fonts, the
 * saved record) is fully knowable in PHP at the moment the builder page renders, so
 * `BuilderPanel::enqueue()` localizes the SAME shape the REST GET would return directly into the
 * page (`evfStyleV2.payload`) — see RestController::build_payload(). The store initializes from
 * it synchronously below, with no network round-trip and therefore no loading state in the
 * common path. The REST `apiFetch` GET stays as a defensive fallback for the rare case the
 * localized payload is missing or malformed.
 */
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createPortal } from 'react-dom';
import './style.scss';
import { App } from './App';
import { PreviewSkeleton } from './PreviewPane';
import { initStore } from './store';
import { BootstrapSettings, StylePayload } from './types';

const apiFetch = ( window as any ).wp?.apiFetch;
const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

const rawSettings: BootstrapSettings | undefined = ( window as any ).evfStyleV2;

const settings: BootstrapSettings = rawSettings || {
	restBase: '',
	formId: 0,
	formTitle: '',
	previewUrl: '',
	frontendCssUrl: '',
	wrapperId: '',
	markerClass: 'evf-style-v2',
	previewSession: '',
};

// Synchronous init from the localized payload, at module load — before Bootstrap even mounts.
// Wrapped defensively: if the payload were ever missing a field, fall through to the network
// fetch below rather than crash the panel.
//
// EXCEPT when this specific read just migrated the form (`migration.just_migrated`): the
// localized payload is computed once, synchronously, at the moment the builder page rendered
// (see BuilderPanel::enqueue()) — reliable for an already-v2 form, but for a freshly-migrated one
// we deliberately do NOT trust it as the final word. Instead we hold off mounting the panel,
// show an explicit "Migrating your styles…" transition (see MigratingCard below), and re-fetch
// via REST — a fresh, authoritative read a moment later — before ever initializing the store.
// This is what guarantees the panel always opens already showing the migrated result with no
// manual refresh required, and turns what would otherwise be a silent, easy-to-miss swap into a
// deliberate, visible step.
let readyFromPayload = false;
let migrationPending = false;
if ( rawSettings && rawSettings.payload && settings.formId ) {
	if ( rawSettings.payload.migration && rawSettings.payload.migration.just_migrated ) {
		migrationPending = true;
	} else {
		try {
			initStore( rawSettings.payload, settings );
			readyFromPayload = true;
		} catch ( e ) {
			readyFromPayload = false;
		}
	}
}

/** Long enough to read as a deliberate, reassuring step rather than a flash. */
const MIGRATION_MIN_VISIBLE_MS = 900;

function Bootstrap() {
	const [ state, setState ] = React.useState< 'loading' | 'migrating' | 'ready' | 'error' >(
		readyFromPayload ? 'ready' : migrationPending ? 'migrating' : 'loading'
	);
	const [ message, setMessage ] = React.useState( '' );

	React.useEffect( () => {
		if ( readyFromPayload ) {
			return; // Already initialized synchronously above — no fetch needed.
		}

		const finish = ( payload: StylePayload ) => {
			try {
				initStore( payload, settings );
			} catch ( e ) {
				setMessage( __( 'The style customizer could not start.', 'everest-forms' ) );
				setState( 'error' );
				return;
			}
			setState( 'ready' );
		};

		if ( migrationPending ) {
			const startedAt = Date.now();
			const fresh = apiFetch && settings.formId
				? apiFetch( { path: `${ settings.restBase }/${ settings.formId }` } )
				: Promise.reject( new Error( 'apiFetch unavailable' ) );

			// Best-effort re-validation: on failure, fall back to the already-embedded payload
			// rather than blocking the panel entirely — no worse than the pre-existing behaviour.
			fresh
				.catch( () => ( rawSettings as { payload: StylePayload } ).payload )
				.then( ( payload: StylePayload ) => {
					const remaining = MIGRATION_MIN_VISIBLE_MS - ( Date.now() - startedAt );
					if ( remaining > 0 ) {
						window.setTimeout( () => finish( payload ), remaining );
					} else {
						finish( payload );
					}
				} );
			return;
		}

		if ( ! apiFetch || ! settings.formId ) {
			setMessage( __( 'The style customizer could not start.', 'everest-forms' ) );
			setState( 'error' );
			return;
		}
		apiFetch( { path: `${ settings.restBase }/${ settings.formId }` } )
			.then( ( payload: StylePayload ) => finish( payload ) )
			.catch( ( e: any ) => {
				setMessage( ( e && e.message ) || __( 'Failed to load styles.', 'everest-forms' ) );
				setState( 'error' );
			} );
	}, [] );

	if ( state === 'loading' ) {
		// Render the panel shell (controls skeleton) instantly, and paint the preview area with
		// its own loader — so the sidebar never waits on the network and the preview never flashes
		// blank or a premature "unavailable" state while the schema loads.
		return <BootLoading />;
	}
	if ( state === 'migrating' ) {
		return <BootLoading migrating />;
	}
	if ( state === 'error' ) {
		return (
			<div className="evfscv2-boot notice notice-error" style={ { margin: 20 } }>
				<p>{ message }</p>
			</div>
		);
	}
	return <App />;
}

/** Sidebar controls skeleton (subtabs + a few shimmering rows), shown while the schema loads. */
function SidebarSkeleton() {
	return (
		<div className="scv2-skel-side" aria-hidden="true">
			<div className="scv2-skel-tabs">
				<span />
				<span />
				<span />
			</div>
			<div className="scv2-skel-body">
				<div className="skel-bar" style={ { width: '40%' } } />
				<div className="skel-bar" style={ { width: '100%', height: 46 } } />
				<div className="skel-bar" style={ { width: '52%', marginTop: 18 } } />
				<div className="skel-bar" style={ { width: '100%', height: 56 } } />
				<div className="skel-bar" style={ { width: '100%', height: 56 } } />
				<div className="skel-bar" style={ { width: '100%', height: 56 } } />
			</div>
		</div>
	);
}

/**
 * Loading state: sidebar skeleton in the controls mount + the preview loader in the content
 * mount. The SAME preview skeleton renders for both the plain "schema is loading" case and the
 * "this form's legacy styles just migrated, re-confirming" case (`migrating`) — only the caption
 * changes — so a freshly-migrated form never hands off from one loader design to a visually
 * different one the instant the panel finishes mounting.
 */
function BootLoading( { migrating = false }: { migrating?: boolean } ) {
	const host = document.getElementById( 'evf-scv2-preview' );
	return (
		<>
			<SidebarSkeleton />
			{ host &&
				createPortal(
					<section
						className="preview"
						aria-label={ migrating ? __( 'Migrating your styles', 'everest-forms' ) : __( 'Live preview', 'everest-forms' ) }
					>
						<PreviewSkeleton note={ migrating ? __( 'Migrating your styles…', 'everest-forms' ) : undefined } />
					</section>,
					host
				) }
		</>
	);
}

const mount = () => {
	// The controls root lives in the builder's native sidebar; <App/> portals the preview into
	// the content area (#evf-scv2-preview).
	const container = document.getElementById( 'evf-scv2-controls' );
	if ( ! container ) {
		return;
	}
	createRoot( container ).render( <Bootstrap /> );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
