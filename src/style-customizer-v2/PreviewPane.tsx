/**
 * Style Customizer v2 — live preview pane.
 *
 * Owns the `?evf_preview` iframe and its bridge. Subscribes to the store so every edit paints
 * live onto the iframe wrapper. Hosts the device switcher, the save lifecycle state, a polished
 * skeleton loader that fades out when the frame is ready, and a graceful (retryable) error card
 * if the preview genuinely can't be bridged.
 */
import React from 'react';
import { DEVICE_ICONS, DEVICE_LABELS } from './constants';
import { PreviewBridge, SelectionInfo, setActiveBridge } from './PreviewBridge';
import { getStore, useStore } from './store';
import { Device } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

type PreviewStatus = 'loading' | 'ready' | 'error';

const DEVICE_ORDER: Device[] = [ 'desktop', 'tablet', 'mobile' ];

/**
 * Content width per device: desktop = full width (null), tablet/mobile = a fixed px that sits
 * just below the compiled breakpoint. Only the FORM content inside the iframe is constrained —
 * the outer preview pane always stays full-width.
 */
function deviceContentWidth( device: Device, breakpoints: Record< string, number > ): number | null {
	if ( device === 'desktop' ) {
		return null;
	}
	const bp = breakpoints[ device ];
	return device === 'mobile' ? Math.min( 400, bp || 480 ) : Math.min( 768, bp || 768 );
}

function deviceRuler( device: Device, breakpoints: Record< string, number > ): string {
	if ( device === 'desktop' ) {
		return __( 'Desktop · full width', 'everest-forms' );
	}
	return `${ DEVICE_LABELS[ device ] } · ${ __( 'applies ≤', 'everest-forms' ) } ${ breakpoints[ device ] }px`;
}

export function PreviewPane( {
	forceClass,
	saving,
	dirty,
	saveError,
	onInfo,
	onSelect,
	toast,
}: {
	forceClass: string | null;
	saving: boolean;
	dirty: boolean;
	saveError: string;
	onInfo: ( anchor: HTMLElement, text: string ) => void;
	onSelect: ( info: SelectionInfo ) => void;
	toast: { msg: string; actLabel?: string; onAct?: () => void } | null;
} ) {
	const store = useStore();
	const iframeRef = React.useRef< HTMLIFrameElement >( null );
	const bridgeRef = React.useRef< PreviewBridge | null >( null );
	const [ status, setStatus ] = React.useState< PreviewStatus >( 'loading' );
	const [ reloadKey, setReloadKey ] = React.useState( 0 );

	// Always call the latest onSelect without re-creating the bridge.
	const onSelectRef = React.useRef( onSelect );
	onSelectRef.current = onSelect;

	// Create the bridge once the iframe is mounted; subscribe the store to live edits.
	React.useEffect( () => {
		const iframe = iframeRef.current;
		if ( ! iframe ) {
			return;
		}
		setStatus( 'loading' );
		const s = getStore();
		const bridge = new PreviewBridge( iframe, s, {
			// The bridge fully wired up (chrome hidden, rules injected) — reveal immediately.
			onReady: () => setStatus( 'ready' ),
			// Bridging failed (couldn't script the frame) — do NOT hide the iframe; the native
			// load event still reveals it, so the form stays visible even without live editing.
			onError: () => setStatus( ( prev ) => ( prev === 'ready' ? prev : 'error' ) ),
			onSelect: ( info ) => onSelectRef.current( info ),
		} );
		bridgeRef.current = bridge;
		setActiveBridge( bridge );
		bridge.attach();

		const unsubscribe = s.subscribe( () => {
			const affected = s.affected;
			if ( affected === null ) {
				bridge.applyAll();
			} else if ( affected.length === 0 ) {
				bridge.applyCustomCss(); // custom-css / saved marker — no token vars moved.
			} else {
				bridge.applyKeys( affected );
			}
		} );

		return () => {
			unsubscribe();
			bridge.detach();
			setActiveBridge( null );
			bridgeRef.current = null;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ reloadKey ] );

	// Reflect the active force-state (focus/hover/message) onto the preview.
	React.useEffect( () => {
		if ( status === 'ready' && bridgeRef.current ) {
			bridgeRef.current.setForceClass( forceClass );
		}
	}, [ forceClass, status ] );

	// Constrain the iframe's form content to the active device width (outer pane stays full).
	const contentWidth = deviceContentWidth( store.device, store.breakpoints );
	React.useEffect( () => {
		if ( status === 'ready' && bridgeRef.current ) {
			bridgeRef.current.setDeviceWidth( contentWidth );
		}
	}, [ contentWidth, status ] );

	const retry = () => {
		setStatus( 'loading' );
		setReloadKey( ( k ) => k + 1 ); // Remount the iframe → fresh load + bridge.
	};

	return (
		<section className="preview" aria-label={ __( 'Live preview', 'everest-forms' ) }>
			<div className="pv-bar">
				<span className="ttl">
					{ __( 'Live preview', 'everest-forms' ) }
					<small>— { __( 'exactly as visitors will see it', 'everest-forms' ) }</small>
					<button
						type="button"
						className="info"
						aria-label={ __( 'About the preview', 'everest-forms' ) }
						onClick={ ( e ) =>
							onInfo(
								e.currentTarget,
								__(
									'This renders your real form through your active theme (the front-end preview route), so it is the truth — click any element to jump to its styles.',
									'everest-forms'
								)
							)
						}
					>
						ⓘ
					</button>
				</span>

				<div className="devs" role="group" aria-label={ __( 'Preview device', 'everest-forms' ) }>
					{ DEVICE_ORDER.map( ( d ) => (
						<button
							key={ d }
							type="button"
							aria-pressed={ store.device === d }
							aria-label={ DEVICE_LABELS[ d ] }
							title={ DEVICE_LABELS[ d ] }
							onClick={ () => store.setDevice( d ) }
						>
							<svg
								viewBox="0 0 24 24"
								stroke="currentColor"
								strokeWidth={ 2 }
								fill="none"
								dangerouslySetInnerHTML={ { __html: DEVICE_ICONS[ d ] } }
							/>
						</button>
					) ) }
				</div>

				<span className={ 'pv-savestate' + ( dirty || saving ? ' is-dirty' : '' ) } aria-live="polite">
					{ saving ? (
						<>
							<span className="spin" aria-hidden="true" /> { __( 'Saving…', 'everest-forms' ) }
						</>
					) : dirty ? (
						<>
							<span className="save-dot" aria-hidden="true" /> { __( 'Unsaved — hit Save above', 'everest-forms' ) }
						</>
					) : (
						__( 'All changes saved', 'everest-forms' )
					) }
				</span>
			</div>

			<div className="pv-canvas">
				<div className="pv-frame">
					<div className="pv-ruler">{ deviceRuler( store.device, store.breakpoints ) }</div>
					<iframe
						key={ reloadKey }
						ref={ iframeRef }
						className={ 'pv-iframe' + ( status === 'ready' ? ' is-ready' : '' ) }
						title={ __( 'Form preview', 'everest-forms' ) }
						src={ store.settings.previewUrl }
						onLoad={ () => {
							// Strict cross-browser visibility: reveal the iframe on its native load
							// event no matter what the bridge does. The bridge's own load handler
							// hides the preview chrome first; the short beat lets that apply so the
							// raw page never flashes.
							window.setTimeout( () => setStatus( 'ready' ), 180 );
						} }
					/>
				</div>

				{ status === 'loading' && (
					<div className="pv-skel" aria-hidden="true">
						<div className="skel-card">
							<div className="skel-bar" style={ { width: '38%' } } />
							<div className="skel-bar" style={ { width: '100%', height: 38 } } />
							<div className="skel-bar" style={ { width: '62%' } } />
							<div className="skel-bar" style={ { width: '100%', height: 38 } } />
							<div className="skel-bar" style={ { width: '30%', height: 34, marginBottom: 0 } } />
						</div>
						<span className="skel-note">
							<span className="spin" /> { __( 'Loading your live preview…', 'everest-forms' ) }
						</span>
					</div>
				) }

				{ status === 'error' && (
					<div className="pv-error">
						<div className="card">
							<h4>{ __( 'Preview is taking a moment', 'everest-forms' ) }</h4>
							<p>
								{ __(
									'We couldn’t load the live preview here — a security or caching plugin may be blocking the preview route. Your edits are still saved.',
									'everest-forms'
								) }
							</p>
							<div className="pv-error-actions">
								<button type="button" className="btn-primary" onClick={ retry }>
									{ __( 'Try again', 'everest-forms' ) }
								</button>
								<a href={ store.settings.previewUrl } target="_blank" rel="noreferrer">
									{ __( 'Open in a new tab ↗', 'everest-forms' ) }
								</a>
							</div>
						</div>
					</div>
				) }
			</div>

			{ saveError && (
				<div className="toast show" role="status">
					<span>{ saveError }</span>
				</div>
			) }

			{ toast && (
				<div className="toast show" role="status">
					<span>{ toast.msg }</span>
					{ toast.actLabel && toast.onAct && (
						<button type="button" onClick={ toast.onAct }>
							{ toast.actLabel }
						</button>
					) }
				</div>
			) }
		</section>
	);
}
