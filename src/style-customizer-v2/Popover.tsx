/**
 * Style Customizer v2 — floating popover.
 *
 * A single popover rendered inside the app tree (position:fixed, scoped under `.evfscv2`).
 * Used for the responsive dev-badge, the palette grid, section reset confirms and info tips.
 * Positioned relative to an anchor rect, clamped to the viewport, and closed on outside
 * click / Escape / scroll.
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
	const [ pos, setPos ] = React.useState< { left: number; top: number; width?: number } >( {
		left: -9999,
		top: -9999,
	} );

	// Position after mount/content change, once the popover has measurable size.
	React.useLayoutEffect( () => {
		const el = ref.current;
		if ( ! el ) {
			return;
		}
		const r = state.anchor.getBoundingClientRect();
		const w = el.offsetWidth;
		const h = el.offsetHeight;
		let left = state.matchWidth ? r.left : Math.min( Math.max( 8, r.left ), window.innerWidth - w - 8 );
		left = Math.min( Math.max( 8, left ), window.innerWidth - w - 8 );
		let top = r.bottom + 6;
		if ( top + h > window.innerHeight - 8 ) {
			top = r.top - h - 6;
		}
		setPos( { left, top: Math.max( 8, top ), width: state.matchWidth ? r.width : undefined } );
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

	// Move focus INTO the popover on every open — to the close button when there is one, else to
	// the panel itself (tabIndex=-1 below) — so a keyboard user never has to Tab through the rest
	// of the page to reach content that just opened elsewhere in the DOM. Previously this only
	// fired when `closable` was true, which nothing ever set — so keyboard focus stayed on the
	// trigger button while e.g. the palette grid or a responsive-override popover opened next to
	// it, with no efficient way in.
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
 * Confirm modal — a real centered/backdropped dialog, distinct from the
 * anchored Popover above. Used for destructive actions (e.g. "Reset all
 * styles") that deserve an explicit are-you-sure step even though most of
 * them are also undoable.
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
