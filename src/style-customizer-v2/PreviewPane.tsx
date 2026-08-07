/**
 * Style Customizer v2 — live preview pane. Owns the `?evf_preview` iframe and its bridge,
 * plus the device switcher, save-lifecycle state, skeleton loader, and error card.
 */
import React from 'react';
import { DEVICE_ICONS, DEVICE_LABELS } from './constants';
import { HoverTip } from './HoverTip';
import { PreviewBridge, SelectionInfo, setActiveBridge } from './PreviewBridge';
import { BuilderSync, getActiveSync, setActiveSync } from './BuilderSync';
import { getStore, useStore } from './store';
import { Device } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

type PreviewStatus = 'loading' | 'ready' | 'error';

const MIGRATION_DISMISS_PREFIX = 'evf_scv2_migration_dismissed_';

/** Notice that this form's legacy styles were auto-migrated. Dismisses once per form. */
function MigrationNotice() {
	const store = useStore();
	const dismissKey = MIGRATION_DISMISS_PREFIX + store.settings.formId;
	const [ dismissed, setDismissed ] = React.useState( () => {
		try {
			return window.localStorage.getItem( dismissKey ) === '1';
		} catch ( e ) {
			return false;
		}
	} );

	if ( ! store.migration?.just_migrated || dismissed ) {
		return null;
	}

	return (
		<div className="pv-migration" role="status">
			<span className="pv-migration-ic" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 }>
					<circle cx="12" cy="12" r="9" />
					<path d="M12 8h.01M11 12h1v4h1" />
				</svg>
			</span>
			<span className="pv-migration-text">
				<b>{ __( 'Styles upgraded from the legacy editor.', 'everest-forms' ) }</b>{ ' ' }
				{ __(
					'Nothing should look different — review the preview below, then hit Save to keep it.',
					'everest-forms'
				) }
			</span>
			<button
				type="button"
				aria-label={ __( 'Dismiss', 'everest-forms' ) }
				onClick={ () => {
					setDismissed( true );
					try {
						window.localStorage.setItem( dismissKey, '1' );
					} catch ( e ) {
						// Best-effort only — worst case it reappears next visit.
					}
				} }
			>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
					<path d="M18 6 6 18M6 6l12 12" />
				</svg>
			</button>
		</div>
	);
}

/** Notice that the Fields or Settings tab has changes not yet saved — shows/hides live as that changes. */
function UnsavedFieldsNotice() {
	const store = useStore();
	const [ dismissed, setDismissed ] = React.useState( false );

	React.useEffect( () => {
		if ( store.hasUnsavedFieldChanges ) {
			setDismissed( false );
		}
	}, [ store.hasUnsavedFieldChanges ] );

	if ( ! store.hasUnsavedFieldChanges || dismissed ) {
		return null;
	}

	return (
		<div className="pv-migration pv-unsaved-fields" role="status">
			<span className="pv-migration-ic" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 }>
					<circle cx="12" cy="12" r="9" />
					<path d="M12 8h.01M11 12h1v4h1" />
				</svg>
			</span>
			<span className="pv-migration-text">
				<b>{ __( 'Previewing unsaved changes.', 'everest-forms' ) }</b>{ ' ' }
				{ __( 'From the Fields or Settings tab.', 'everest-forms' ) }
			</span>
			<button
				type="button"
				aria-label={ __( 'Dismiss', 'everest-forms' ) }
				onClick={ () => setDismissed( true ) }
			>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
					<path d="M18 6 6 18M6 6l12 12" />
				</svg>
			</button>
		</div>
	);
}

/** Preview loader (skeleton form + spinner), shared with the panel bootstrap in index.tsx. */
export function PreviewSkeleton( { note }: { note?: string } ) {
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
				<span className="spin" /> { note || __( 'Loading your live preview…', 'everest-forms' ) }
			</span>
		</div>
	);
}

const DEVICE_ORDER: Device[] = [ 'desktop', 'tablet', 'mobile' ];

/** Content width per device: desktop = full width (null), tablet/mobile = a fixed px below the breakpoint. */
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
	saveErrorConflict,
	onSelect,
	onIframeClick,
	toast,
	onToastPause,
	onToastResume,
}: {
	forceClass: string | null;
	saving: boolean;
	dirty: boolean;
	saveError: string;
	saveErrorConflict: boolean;
	onSelect: ( info: SelectionInfo ) => void;
	onIframeClick: () => void;
	toast: { msg: string; actLabel?: string; onAct?: () => void; kind?: 'success' | 'info' } | null;
	onToastPause: () => void;
	onToastResume: () => void;
} ) {
	const store = useStore();
	const iframeRef = React.useRef< HTMLIFrameElement >( null );
	const bridgeRef = React.useRef< PreviewBridge | null >( null );
	const [ status, setStatus ] = React.useState< PreviewStatus >( 'loading' );
	const [ reloadKey, setReloadKey ] = React.useState( 0 );
	// Whether the live-edit bridge is actually wired (click-to-edit, hover) — separate from
	// `status`, which can be force-flipped to 'ready' even when the bridge never finishes.
	const [ interactive, setInteractive ] = React.useState( false );
	const [ interactiveStalled, setInteractiveStalled ] = React.useState( false );

	// Always call the latest onSelect/onIframeClick without re-creating the bridge.
	const onSelectRef = React.useRef( onSelect );
	onSelectRef.current = onSelect;
	const onIframeClickRef = React.useRef( onIframeClick );
	onIframeClickRef.current = onIframeClick;

	// Force the iframe to the current window's exact origin, so a host/scheme mismatch with the
	// server-computed preview URL never makes the iframe cross-origin (contentDocument would throw).
	const previewSrc = React.useMemo( () => {
		try {
			const url = new URL( store.settings.previewUrl, window.location.href );
			url.protocol = window.location.protocol;
			url.host = window.location.host;
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
		setInteractive( false );
		setInteractiveStalled( false );
		const s = getStore();
		const bridge = new PreviewBridge( iframe, s, {
			onReady: () => {
				setStatus( 'ready' );
				setInteractive( true );
				setInteractiveStalled( false );
				getActiveSync()?.onBridgeReady();
			},
			onError: () => setStatus( ( prev ) => ( prev === 'ready' ? prev : 'error' ) ),
			onSelect: ( info ) => onSelectRef.current( info ),
			onIframeClick: () => onIframeClickRef.current(),
		} );
		bridgeRef.current = bridge;
		setActiveBridge( bridge );
		bridge.attach();

		// Some Chrome setups fail to flip status via the load event/poll — force a reveal so the
		// preview is always shown, with or without the live-edit bridge.
		const revealTimer = window.setTimeout( () => {
			setStatus( ( prev ) => ( prev === 'loading' ? 'ready' : prev ) );
		}, 2500 );

		const interactiveStallTimer = window.setTimeout( () => {
			setInteractive( ( cur ) => {
				if ( ! cur ) {
					setInteractiveStalled( true );
				}
				return cur;
			} );
		}, 16000 );

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
			window.clearTimeout( interactiveStallTimer );
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
		<>
			<MigrationNotice />
			<UnsavedFieldsNotice />
			<section className="preview" aria-label={ __( 'Live preview', 'everest-forms' ) }>
			<div className="pv-bar">
				<span className="ttl">
					{ __( 'Live preview', 'everest-forms' ) }
					<HoverTip
						className="info"
						label={ __( 'About the preview', 'everest-forms' ) }
						tip={ __(
							'This renders your real form through your active theme (the front-end preview route), so it is the truth — click any element to jump to its styles.',
							'everest-forms'
						) }
					>
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
							<path
								fillRule="evenodd"
								clipRule="evenodd"
								d="M21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21C16.9706 21 21 16.9706 21 12ZM23 12C23 18.0751 18.0751 23 12 23C5.92487 23 1 18.0751 1 12C1 5.92487 5.92487 1 12 1C18.0751 1 23 5.92487 23 12Z"
							/>
							<path d="M11 16V12C11 11.4477 11.4477 11 12 11C12.5523 11 13 11.4477 13 12V16C13 16.5523 12.5523 17 12 17C11.4477 17 11 16.5523 11 16Z" />
							<path d="M12.0098 7C12.5621 7 13.0098 7.44772 13.0098 8C13.0098 8.55228 12.5621 9 12.0098 9H12C11.4477 9 11 8.55228 11 8C11 7.44772 11 7 12 7H12.0098Z" />
						</svg>
					</HoverTip>
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
						<>
							<svg className="save-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.5 } aria-hidden="true">
								<path d="m5 13 4 4 10-10" />
							</svg>
							{ __( 'All changes saved', 'everest-forms' ) }
						</>
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

			{ status === 'ready' && interactiveStalled && ! interactive && (
				<div className="pv-noninteractive" role="status">
					<span>
						{ __(
							'Click-to-edit isn’t responding in this preview — the form itself still renders correctly.',
							'everest-forms'
						) }
					</span>
					<button type="button" onClick={ retry }>
						{ __( 'Retry', 'everest-forms' ) }
					</button>
				</div>
			) }

			<div className="pv-canvas">
				<div className="pv-frame">
					<iframe
						key={ reloadKey }
						ref={ iframeRef }
						className={ 'pv-iframe' + ( status === 'ready' ? ' is-ready' : '' ) }
						title={ __( 'Form preview', 'everest-forms' ) }
						src={ previewSrc }
						onLoad={ () => {
							// Small delay lets the bridge hide the preview chrome first, so it never flashes.
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
									'We couldn’t load the live preview — a security or caching plugin may be blocking it. Your edits are still saved.',
									'everest-forms'
								) }
							</p>
							<div className="pv-error-actions">
								<button type="button" className="btn-primary" onClick={ retry }>
									{ __( 'Retry', 'everest-forms' ) }
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
				<div className="toast is-error" role="alert">
					<span className="toast-ic" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 }>
							<circle cx="12" cy="12" r="9" />
							<path d="M12 8v5M12 16h.01" />
						</svg>
					</span>
					<span>{ saveError }</span>
					{ saveErrorConflict && (
						<button type="button" onClick={ () => window.location.reload() }>
							{ __( 'Reload', 'everest-forms' ) }
						</button>
					) }
				</div>
			) }

			{ toast && (
				<div
					className={ 'toast' + ( toast.kind ? ` is-${ toast.kind }` : '' ) }
					role="status"
					onMouseEnter={ onToastPause }
					onMouseLeave={ onToastResume }
					onFocus={ onToastPause }
					onBlur={ onToastResume }
				>
					{ toast.kind === 'success' && (
						<span className="toast-ic" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 }>
								<circle cx="12" cy="12" r="9" />
								<path d="m8.5 12.5 2.5 2.5 4.5-5" />
							</svg>
						</span>
					) }
					<span>{ toast.msg }</span>
					{ toast.actLabel && toast.onAct && (
						<button type="button" onClick={ toast.onAct }>
							{ toast.actLabel }
						</button>
					) }
				</div>
			) }
			</section>
		</>
	);
}
