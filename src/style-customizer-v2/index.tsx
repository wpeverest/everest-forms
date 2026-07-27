/**
 * Style Customizer v2 — builder panel entry. Mounts into the "Style" builder tab and renders
 * the two-pane panel (controls + live preview), initializing the store from the localized
 * payload (`evfStyleV2.payload`) with a REST `apiFetch` fallback.
 */
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createPortal } from 'react-dom';
import './style.scss';
import { App } from './App';
import { PreviewSkeleton } from './PreviewPane';
import { initStore, getStore } from './store';
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
	aiEnabled: false,
};

// Bridge so the (legacy jQuery) Fields tab can flag unsaved changes without importing the bundle.
( window as any ).evfScv2SetFormDirty = ( dirty: boolean ) => {
	try {
		getStore().setUnsavedFieldChanges( !! dirty );
	} catch ( e ) {
		// Store not initialized yet — the builder will call again on the next interaction.
	}
};

// Reverse bridge: lets the (legacy jQuery) tab-switcher ask "would leaving the Style tab
// right now lose anything?" before it switches away — the existing `beforeunload` guard
// only protects a real page close/reload, not this in-app SPA tab switch.
( window as any ).evfScv2IsDirty = (): boolean => {
	try {
		return getStore().isDirty();
	} catch ( e ) {
		return false; // Store not initialized yet — nothing to lose.
	}
};

const MIGRATION_SEEN_PREFIX = 'evf_scv2_migration_seen_';

/** Whether this browser has already sat through the migrating transition for this form once. */
function hasSeenMigration( formId: number ): boolean {
	try {
		return window.localStorage.getItem( MIGRATION_SEEN_PREFIX + formId ) === '1';
	} catch ( e ) {
		return false;
	}
}

function markMigrationSeen( formId: number ) {
	try {
		window.localStorage.setItem( MIGRATION_SEEN_PREFIX + formId, '1' );
	} catch ( e ) {
		// Best-effort only — worst case the transition shows again next visit.
	}
}

// Synchronous init from the localized payload, at module load — before Bootstrap even mounts.
// Except the very first time this form is seen freshly migrated, which shows a one-time
// "Migrating your styles…" transition and re-fetches via REST instead.
let readyFromPayload = false;
let migrationPending = false;
if ( rawSettings && rawSettings.payload && settings.formId ) {
	const justMigrated = !! ( rawSettings.payload.migration && rawSettings.payload.migration.just_migrated );
	if ( justMigrated && ! hasSeenMigration( settings.formId ) ) {
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
			markMigrationSeen( settings.formId ); // Never show this transition again for this form.
			const startedAt = Date.now();
			const fresh = apiFetch && settings.formId
				? apiFetch( { path: `${ settings.restBase }/${ settings.formId }` } )
				: Promise.reject( new Error( 'apiFetch unavailable' ) );

			// On failure, fall back to the already-embedded payload rather than blocking the panel.
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

/** Loading state: sidebar skeleton in the controls mount + the preview loader in the content mount. */
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
	// The controls root lives in the builder's native sidebar; <App/> portals the preview into #evf-scv2-preview.
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
