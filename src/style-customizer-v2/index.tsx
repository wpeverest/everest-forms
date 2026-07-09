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
let readyFromPayload = false;
if ( rawSettings && rawSettings.payload && settings.formId ) {
	try {
		initStore( rawSettings.payload, settings );
		readyFromPayload = true;
	} catch ( e ) {
		readyFromPayload = false;
	}
}

function Bootstrap() {
	const [ state, setState ] = React.useState< 'loading' | 'ready' | 'error' >(
		readyFromPayload ? 'ready' : 'loading'
	);
	const [ message, setMessage ] = React.useState( '' );

	React.useEffect( () => {
		if ( readyFromPayload ) {
			return; // Already initialized synchronously above — no fetch needed.
		}
		if ( ! apiFetch || ! settings.formId ) {
			setMessage( __( 'The style customizer could not start.', 'everest-forms' ) );
			setState( 'error' );
			return;
		}
		apiFetch( { path: `${ settings.restBase }/${ settings.formId }` } )
			.then( ( payload: StylePayload ) => {
				initStore( payload, settings );
				setState( 'ready' );
			} )
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
function BootLoading() {
	const host = document.getElementById( 'evf-scv2-preview' );
	return (
		<>
			<SidebarSkeleton />
			{ host &&
				createPortal(
					<section className="preview" aria-label={ __( 'Live preview', 'everest-forms' ) }>
						<PreviewSkeleton />
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
