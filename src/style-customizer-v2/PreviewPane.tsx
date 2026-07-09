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
import { BuilderSync, getActiveSync, setActiveSync } from './BuilderSync';
import { getStore, useStore } from './store';
import { Device } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

type PreviewStatus = 'loading' | 'ready' | 'error';

/**
 * Polished preview loader (skeleton form + spinner). Shared with the panel bootstrap so the
 * preview area shows the SAME loader from the instant the tab opens through until the iframe
 * is ready — no blank flash, no premature "unavailable".
 */
export function PreviewSkeleton() {
	return (
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
	);
}

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

	// Force the iframe protocol to match the parent page's. If the builder is served over https
	// but the preview URL is http (e.g. is_ssl() misreported behind a proxy), Chrome blocks the
	// mixed-content iframe and it renders blank — the reported "not visible in Chrome" bug. Same
	// host + scheme keeps it same-origin so the live-edit bridge can still script it.
	const previewSrc = React.useMemo( () => {
		try {
			const url = new URL( store.settings.previewUrl, window.location.href );
			url.protocol = window.location.protocol;
			return url.href;
		} catch ( e ) {
			return store.settings.previewUrl;
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Create the bridge once the iframe is mounted; subscribe the store to live edits.
	React.useEffect( () => {
		const iframe = iframeRef.current;
		if ( ! iframe ) {
			return;
		}
		setStatus( 'loading' );
		const s = getStore();
		const bridge = new PreviewBridge( iframe, s, {
			// The bridge fully wired up (chrome hidden, rules injected) — reveal immediately, and
			// let the builder-sync flush any structure change that was queued while (re)loading.
			onReady: () => {
				setStatus( 'ready' );
				getActiveSync()?.onBridgeReady();
			},
			// Bridging failed (couldn't script the frame) — do NOT hide the iframe; the native
			// load event still reveals it, so the form stays visible even without live editing.
			onError: () => setStatus( ( prev ) => ( prev === 'ready' ? prev : 'error' ) ),
			onSelect: ( info ) => onSelectRef.current( info ),
		} );
		bridgeRef.current = bridge;
		setActiveBridge( bridge );
		bridge.attach();

		// Chrome-safety: on some Chrome setups the iframe `load` event / bridge poll can fail to
		// flip the status (leaving the frame gated at opacity:0 forever, i.e. an invisible preview
		// — the exact symptom seen in Chrome but not Chromium/Brave). Force the reveal after a
		// short deadline so the preview is ALWAYS shown, with or without the live-edit bridge.
		const revealTimer = window.setTimeout( () => {
			setStatus( ( prev ) => ( prev === 'loading' ? 'ready' : prev ) );
		}, 2500 );

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
			window.clearTimeout( revealTimer );
			unsubscribe();
			bridge.detach();
			setActiveBridge( null );
			bridgeRef.current = null;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ reloadKey ] );

	// Fields ↔ Style live sync: keeps the preview rendering the builder's CURRENT structure
	// (unsaved edits included). Mounted once — it reads the active bridge on demand, so it
	// survives bridge remounts (retry/device switch) without being torn down.
	React.useEffect( () => {
		const sync = new BuilderSync( getStore() );
		setActiveSync( sync );
		sync.start();
		return () => {
			sync.stop();
			setActiveSync( null );
		};
	}, [] );

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

				{ /* Device switcher — pinned to the far right of the toolbar. */ }
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
			</div>

			<div className="pv-canvas">
				<div className="pv-frame">
					<iframe
						key={ reloadKey }
						ref={ iframeRef }
						className={ 'pv-iframe' + ( status === 'ready' ? ' is-ready' : '' ) }
						title={ __( 'Form preview', 'everest-forms' ) }
						src={ previewSrc }
						onLoad={ () => {
							// Strict cross-browser visibility: reveal the iframe on its native load
							// event no matter what the bridge does. The bridge's own load handler
							// hides the preview chrome first; the short beat lets that apply so the
							// raw page never flashes.
							window.setTimeout( () => setStatus( 'ready' ), 180 );
						} }
					/>
				</div>

				{ status === 'loading' && <PreviewSkeleton /> }

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
								<a href={ previewSrc } target="_blank" rel="noreferrer">
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
