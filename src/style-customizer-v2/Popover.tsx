/**
 * Style Customizer v2 — floating popover.
 *
 * A single popover rendered inside the app tree (position:fixed, scoped under `.evfscv2`).
 * Used for the responsive dev-badge, the palette grid, section reset confirms and info tips.
 * Positioned relative to an anchor rect, clamped to the viewport, and closed on outside
 * click / Escape / scroll.
 */
import React from 'react';

export interface PopoverState {
	anchor: HTMLElement;
	render: () => React.ReactNode;
	matchWidth?: boolean;
	/** Optional identity so the opener can toggle a specific popover (e.g. the palette grid). */
	kind?: string;
}

export function Popover( { state, onClose }: { state: PopoverState; onClose: () => void } ) {
	const ref = React.useRef< HTMLDivElement >( null );
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

	return (
		<div
			ref={ ref }
			className="scv2-pop"
			role="dialog"
			style={ {
				left: pos.left,
				top: pos.top,
				width: pos.width,
				minWidth: pos.width,
				maxWidth: pos.width || undefined,
			} }
		>
			{ state.render() }
		</div>
	);
}
