/**
 * Style Customizer v2 — builder panel entry.
 *
 * Mounts into the "Style" builder tab, fetches the schema + saved record from the v2 REST
 * endpoint, initialises the store, and renders the two-pane panel (controls + live preview).
 * The token contract is never hardcoded — it comes from the server on load.
 */
import React from 'react';
import { createRoot } from 'react-dom/client';
import './style.scss';
import { App } from './App';
import { initStore } from './store';
import { BootstrapSettings, StylePayload } from './types';

const apiFetch = ( window as any ).wp?.apiFetch;
const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

const settings: BootstrapSettings = ( window as any ).evfStyleV2 || {
	restBase: '',
	formId: 0,
	formTitle: '',
	previewUrl: '',
	frontendCssUrl: '',
	wrapperId: '',
	markerClass: 'evf-style-v2',
};

function Bootstrap() {
	const [ state, setState ] = React.useState< 'loading' | 'ready' | 'error' >( 'loading' );
	const [ message, setMessage ] = React.useState( '' );

	React.useEffect( () => {
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
		return (
			<div className="evfscv2-boot" style={ { padding: 40, color: '#6b7280', fontSize: 13 } }>
				{ __( 'Loading style customizer…', 'everest-forms' ) }
			</div>
		);
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
