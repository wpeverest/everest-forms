/**
 * Style Customizer v2 — hover/focus tooltip.
 *
 * A self-dismissing info bubble: opens on pointer-enter / keyboard focus of its trigger and
 * closes on pointer-leave, blur, scroll, resize or Escape. Portaled into the panel root so it
 * inherits the panel tokens and clears the sidebar's overflow, positioned against the trigger.
 */
import React from 'react';
import { createPortal } from 'react-dom';

type Placement = 'top' | 'bottom';

function tipHost(): HTMLElement {
	return document.getElementById( 'everest-forms-panel-style' ) || document.body;
}

export function HoverTip( {
	tip,
	label,
	className,
	children,
}: {
	tip: React.ReactNode;
	label: string;
	className?: string;
	children: React.ReactNode;
} ) {
	const triggerRef = React.useRef< HTMLButtonElement >( null );
	const tipRef = React.useRef< HTMLDivElement >( null );
	const [ open, setOpen ] = React.useState( false );
	const [ pos, setPos ] = React.useState< { left: number; top: number; placement: Placement } >( {
		left: -9999,
		top: -9999,
		placement: 'bottom',
	} );

	React.useLayoutEffect( () => {
		if ( ! open ) {
			return;
		}
		const trigger = triggerRef.current;
		const el = tipRef.current;
		if ( ! trigger || ! el ) {
			return;
		}
		const r = trigger.getBoundingClientRect();
		const w = el.offsetWidth;
		const h = el.offsetHeight;
		let left = r.left + r.width / 2 - w / 2;
		left = Math.min( Math.max( 8, left ), window.innerWidth - w - 8 );
		let top = r.bottom + 8;
		let placement: Placement = 'bottom';
		if ( top + h > window.innerHeight - 8 ) {
			top = r.top - h - 8;
			placement = 'top';
		}
		setPos( { left, top: Math.max( 8, top ), placement } );
	}, [ open, tip ] );

	React.useEffect( () => {
		if ( ! open ) {
			return;
		}
		const close = () => setOpen( false );
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				setOpen( false );
			}
		};
		window.addEventListener( 'scroll', close, true );
		window.addEventListener( 'resize', close );
		document.addEventListener( 'keydown', onKey );
		return () => {
			window.removeEventListener( 'scroll', close, true );
			window.removeEventListener( 'resize', close );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open ] );

	return (
		<>
			<button
				ref={ triggerRef }
				type="button"
				className={ className }
				aria-label={ label }
				onMouseEnter={ () => setOpen( true ) }
				onMouseLeave={ () => setOpen( false ) }
				onFocus={ () => setOpen( true ) }
				onBlur={ () => setOpen( false ) }
			>
				{ children }
			</button>
			{ open &&
				createPortal(
					<div
						ref={ tipRef }
						className={ 'scv2-hovertip is-' + pos.placement }
						role="tooltip"
						style={ { left: pos.left, top: pos.top } }
					>
						{ tip }
					</div>,
					tipHost()
				) }
		</>
	);
}
