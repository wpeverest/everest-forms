/**
 * Style Customizer v2 — floating popover (position:fixed), positioned relative to an anchor
 * rect and closed on outside click / Escape / scroll.
 */
import React from 'react';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

export interface PopoverState {
	anchor: HTMLElement;
	render: () => React.ReactNode;
	matchWidth?: boolean;
	/** Optional identity so the opener can toggle a specific popover (e.g. the palette grid). */
	kind?: string;
	/** Header title. When set (or `closable`), the popover shows a titled header with a × button. */
	title?: string;
	/** Show a close (×) button in the header. Implied when `title` is set. */
	closable?: boolean;
}

export function Popover( { state, onClose }: { state: PopoverState; onClose: () => void } ) {
	const ref = React.useRef< HTMLDivElement >( null );
	const closeRef = React.useRef< HTMLButtonElement >( null );
	const showHeader = !! ( state.title || state.closable );
	const showClose = !! state.closable;
	const [ pos, setPos ] = React.useState< { left: number; top: number; width?: number; maxHeight?: number } >( {
		left: -9999,
		top: -9999,
	} );

	React.useLayoutEffect( () => {
		const el = ref.current;
		if ( ! el ) {
			return;
		}

		const reposition = () => {
			const r = state.anchor.getBoundingClientRect();
			const w = el.offsetWidth;
			let left = state.matchWidth ? r.left : Math.min( Math.max( 8, r.left ), window.innerWidth - w - 8 );
			left = Math.min( Math.max( 8, left ), window.innerWidth - w - 8 );

			// scrollHeight (not offsetHeight) gives the true content height despite the CSS max-height clamp.
			const naturalH = el.scrollHeight;
			const desiredH = Math.min( naturalH, 460 );
			const GAP = 6;
			const MARGIN = 8;
			const MIN_H = 150;
			// The WP admin toolbar is `position:fixed` above everything in wp-admin — a popover that
			// grows tall enough (e.g. the "Create Custom Palette" editor) must not be pushed up under
			// it, or its top controls end up visually obstructed and unclickable (the toolbar intercepts
			// the click even though the popover paints on top of it).
			const adminBar = document.getElementById( 'wpadminbar' );
			const topBoundary = adminBar ? Math.max( MARGIN, adminBar.getBoundingClientRect().bottom + GAP ) : MARGIN;
			const spaceBelow = window.innerHeight - r.bottom - GAP - MARGIN;
			const spaceAbove = r.top - GAP - topBoundary;

			let top: number;
			let maxHeight: number;
			if ( desiredH <= spaceBelow ) {
				top = r.bottom + GAP;
				maxHeight = desiredH;
			} else if ( desiredH <= spaceAbove ) {
				maxHeight = desiredH;
				top = r.top - maxHeight - GAP;
			} else {
				// Neither side fits in full — use whichever has more room.
				maxHeight = Math.max( Math.max( spaceBelow, spaceAbove ), MIN_H );
				top = spaceBelow >= spaceAbove ? r.bottom + GAP : r.top - maxHeight - GAP;
			}
			setPos( { left, top: Math.max( topBoundary, top ), width: state.matchWidth ? r.width : undefined, maxHeight } );
		};

		reposition();

		// Content height can change after the initial layout (e.g. the palette popover growing
		// when it switches from the browse grid to the "Create Custom Palette" editor) — `state`
		// itself doesn't change in that case, so re-run the fit calc whenever the popover's own
		// size changes rather than only when it (re)opens.
		const ro = new ResizeObserver( reposition );
		ro.observe( el );
		return () => ro.disconnect();
	}, [ state ] );

	React.useEffect( () => {
		const onDown = ( e: MouseEvent ) => {
			const el = ref.current;
			if ( el && ! el.contains( e.target as Node ) && ! state.anchor.contains( e.target as Node ) ) {
				onClose();
			}
		};
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'pointerdown', onDown, true );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'pointerdown', onDown, true );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ state, onClose ] );

	// Move focus into the popover on open so keyboard users don't have to tab across the page.
	React.useEffect( () => {
		if ( showClose && closeRef.current ) {
			closeRef.current.focus();
		} else if ( ref.current ) {
			ref.current.focus();
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ state ] );

	return (
		<div
			ref={ ref }
			className="scv2-pop"
			role="dialog"
			aria-modal="false"
			aria-label={ state.title || undefined }
			tabIndex={ -1 }
			style={ {
				left: pos.left,
				top: pos.top,
				width: pos.width,
				minWidth: pos.width,
				maxWidth: pos.width || undefined,
				maxHeight: pos.maxHeight,
			} }
		>
			{ showHeader && (
				<div className="pop-head">
					{ state.title && <span className="pop-head-title">{ state.title }</span> }
					{ showClose && (
						<button
							ref={ closeRef }
							type="button"
							className="pop-close"
							aria-label={ __( 'Close', 'everest-forms' ) }
							onClick={ onClose }
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
								<path d="M18 6 6 18M6 6l12 12" />
							</svg>
						</button>
					) }
				</div>
			) }
			{ state.render() }
		</div>
	);
}

/* --------------------------------------------------------------------- *
 * Confirm modal — centered/backdropped dialog for destructive actions.
 * --------------------------------------------------------------------- */

export interface ConfirmState {
	title: string;
	message: string;
	confirmLabel?: string;
	/** Styles the confirm button as a destructive action. */
	danger?: boolean;
	onConfirm: () => void;
}

export function ConfirmModal( { state, onClose }: { state: ConfirmState; onClose: () => void } ) {
	const confirmRef = React.useRef< HTMLButtonElement >( null );

	React.useEffect( () => {
		confirmRef.current?.focus();
	}, [] );

	React.useEffect( () => {
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ onClose ] );

	return (
		<div
			className="scv2-modal-backdrop"
			onMouseDown={ ( e ) => {
				if ( e.target === e.currentTarget ) {
					onClose();
				}
			} }
		>
			<div className="scv2-modal" role="alertdialog" aria-modal="true" aria-labelledby="scv2-modal-title">
				<h3 id="scv2-modal-title" className="scv2-modal-title">
					{ state.title }
				</h3>
				<p className="scv2-modal-msg">{ state.message }</p>
				<div className="scv2-modal-actions">
					<button type="button" className="scv2-modal-cancel" onClick={ onClose }>
						{ __( 'Cancel', 'everest-forms' ) }
					</button>
					<button
						ref={ confirmRef }
						type="button"
						className={ 'scv2-modal-confirm' + ( state.danger ? ' danger' : '' ) }
						onClick={ () => {
							state.onConfirm();
							onClose();
						} }
					>
						{ state.confirmLabel || __( 'Confirm', 'everest-forms' ) }
					</button>
				</div>
			</div>
		</div>
	);
}
