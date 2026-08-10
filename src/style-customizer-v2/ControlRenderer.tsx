/**
 * Style Customizer v2 — one component per schema control type (slider, color, box4, select,
 * align, fontstyle, media, toggle), each reading/writing through the store.
 */
import React from 'react';
import { createPortal } from 'react-dom';
import { HexAlphaColorPicker } from 'react-colorful';
import {
	ALIGN_ICONS,
	DEVICE_ICONS,
	DEVICE_LABELS,
	FONTSTYLE_BUTTONS,
	clone,
} from './constants';
import { HoverTip } from './HoverTip';
import { StyleStore } from './store';
import { BoxValue, FontStyleValue, Token } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

/** Same portal target HoverTip uses — inside the panel root (so scoped `#everest-forms-panel-style`
 *  CSS still applies) but outside any scrollable ancestor that would otherwise clip a popover. */
function popoverHost(): HTMLElement {
	return document.getElementById( 'everest-forms-panel-style' ) || document.body;
}

interface ControlProps {
	token: Token;
	store: StyleStore;
	dimmed?: boolean;
}

/* --------------------------------------------------------------------- *
 * Small helpers
 * --------------------------------------------------------------------- */

/** Keep an uncontrolled input's DOM value in sync with state, but never while it's focused. */
function useSyncedInput< T extends HTMLInputElement >(
	ref: React.RefObject< T >,
	display: string
) {
	React.useEffect( () => {
		const el = ref.current;
		if ( el && el.ownerDocument.activeElement !== el ) {
			el.value = display;
		}
	} );
}

const clampNumber = ( n: number, min: number, max: number ) => Math.min( max, Math.max( min, n ) );
const isFloatStep = ( token: Token ) => !! ( token.step && token.step < 1 );
const roundToStep = ( n: number, token: Token ) => ( isFloatStep( token ) ? Math.round( n * 10 ) / 10 : Math.round( n ) );

/** Parse any Sanitizer-accepted colour (#rgb, #rrggbb, #rrggbbaa, rgb()/rgba()) into a 6-digit hex + 0-100 alpha. */
function parseColor( value: string ): { hex: string; alpha: number } {
	const v = value.trim();
	const rgbaMatch = v.match( /^rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+%?))?\s*\)$/i );
	if ( rgbaMatch ) {
		const toHex = ( n: string ) => clampNumber( Math.round( Number( n ) ), 0, 255 ).toString( 16 ).padStart( 2, '0' );
		const hex = '#' + toHex( rgbaMatch[ 1 ] ) + toHex( rgbaMatch[ 2 ] ) + toHex( rgbaMatch[ 3 ] );
		const rawAlpha = rgbaMatch[ 4 ];
		const alpha = rawAlpha === undefined
			? 100
			: clampNumber( Math.round( rawAlpha.endsWith( '%' ) ? parseFloat( rawAlpha ) : parseFloat( rawAlpha ) * 100 ), 0, 100 );
		return { hex, alpha };
	}
	if ( /^#[0-9a-f]{3}$/i.test( v ) ) {
		return { hex: '#' + v.slice( 1 ).split( '' ).map( ( c ) => c + c ).join( '' ), alpha: 100 };
	}
	if ( /^#[0-9a-f]{6}$/i.test( v ) ) {
		return { hex: v.toLowerCase(), alpha: 100 };
	}
	if ( /^#[0-9a-f]{8}$/i.test( v ) ) {
		const alpha = clampNumber( Math.round( ( parseInt( v.slice( 7, 9 ), 16 ) / 255 ) * 100 ), 0, 100 );
		return { hex: v.slice( 0, 7 ).toLowerCase(), alpha };
	}
	return { hex: '#000000', alpha: 100 };
}

/** Recompose a 6-digit hex + 0-100 alpha back into the stored value (plain hex when fully opaque). */
function composeColor( hex: string, alpha: number ): string {
	if ( alpha >= 100 ) {
		return hex;
	}
	const alphaHex = clampNumber( Math.round( ( alpha / 100 ) * 255 ), 0, 255 ).toString( 16 ).padStart( 2, '0' );
	return hex + alphaHex;
}

/** Always-8-digit form, for react-colorful's `HexAlphaColorPicker` (its value/onChange contract is always `#rrggbbaa`). */
function toHex8( hex: string, alpha: number ): string {
	const alphaHex = clampNumber( Math.round( ( alpha / 100 ) * 255 ), 0, 255 ).toString( 16 ).padStart( 2, '0' );
	return hex + alphaHex;
}

/** 6-digit hex -> 0-255 RGB triple. */
function hexToRgb( hex: string ): { r: number; g: number; b: number } {
	const n = parseInt( hex.slice( 1 ), 16 ) || 0;
	return { r: ( n >> 16 ) & 255, g: ( n >> 8 ) & 255, b: n & 255 };
}

/** 0-255 RGB triple -> 6-digit hex. */
function rgbToHex( r: number, g: number, b: number ): string {
	const c = ( n: number ) => clampNumber( Math.round( n ), 0, 255 ).toString( 16 ).padStart( 2, '0' );
	return '#' + c( r ) + c( g ) + c( b );
}

/** 0-255 RGB triple -> {h: 0-360, s/l: 0-100}. */
function rgbToHsl( r: number, g: number, b: number ): { h: number; s: number; l: number } {
	r /= 255; g /= 255; b /= 255;
	const max = Math.max( r, g, b );
	const min = Math.min( r, g, b );
	const l = ( max + min ) / 2;
	const d = max - min;
	let h = 0;
	let s = 0;
	if ( d !== 0 ) {
		s = d / ( 1 - Math.abs( 2 * l - 1 ) );
		switch ( max ) {
			case r:
				h = 60 * ( ( ( g - b ) / d ) % 6 );
				break;
			case g:
				h = 60 * ( ( b - r ) / d + 2 );
				break;
			default:
				h = 60 * ( ( r - g ) / d + 4 );
		}
	}
	if ( h < 0 ) {
		h += 360;
	}
	return { h: Math.round( h ), s: Math.round( s * 100 ), l: Math.round( l * 100 ) };
}

/** {h: 0-360, s/l: 0-100} -> 0-255 RGB triple. */
function hslToRgb( h: number, s: number, l: number ): { r: number; g: number; b: number } {
	h = ( ( h % 360 ) + 360 ) % 360;
	const sf = clampNumber( s, 0, 100 ) / 100;
	const lf = clampNumber( l, 0, 100 ) / 100;
	const c = ( 1 - Math.abs( 2 * lf - 1 ) ) * sf;
	const x = c * ( 1 - Math.abs( ( ( h / 60 ) % 2 ) - 1 ) );
	const m = lf - c / 2;
	let r = 0;
	let g = 0;
	let b = 0;
	if ( h < 60 ) {
		r = c; g = x; b = 0;
	} else if ( h < 120 ) {
		r = x; g = c; b = 0;
	} else if ( h < 180 ) {
		r = 0; g = c; b = x;
	} else if ( h < 240 ) {
		r = 0; g = x; b = c;
	} else if ( h < 300 ) {
		r = x; g = 0; b = c;
	} else {
		r = c; g = 0; b = x;
	}
	return { r: ( r + m ) * 255, g: ( g + m ) * 255, b: ( b + m ) * 255 };
}

/** One compact "label + number input(+suffix)" field — shared by the RGB/HSL rows below. */
function NumField( {
	label,
	ariaLabel,
	value,
	min,
	max,
	suffix,
	inputRef,
	onCommit,
}: {
	label: string;
	ariaLabel: string;
	value: number;
	min: number;
	max: number;
	suffix?: string;
	inputRef: React.RefObject< HTMLInputElement >;
	onCommit: ( n: number ) => void;
} ) {
	return (
		<div className="cpf-num">
			<span className="cpf-label">{ label }</span>
			<div className="num">
				<input
					ref={ inputRef }
					inputMode="numeric"
					defaultValue={ String( value ) }
					aria-label={ ariaLabel }
					onInput={ ( e ) => {
						const n = Number( ( e.target as HTMLInputElement ).value );
						if ( ! Number.isNaN( n ) ) {
							onCommit( clampNumber( n, min, max ) );
						}
					} }
					onBlur={ () => {
						if ( inputRef.current ) {
							inputRef.current.value = String( value );
						}
					} }
				/>
				{ suffix && <span>{ suffix }</span> }
			</div>
		</div>
	);
}

/** Curated quick-pick row inside every color popover — neutrals, the panel's own accent, and a
 *  handful of common brand/UI colours, so a common choice never requires touching the wheel. */
const PRESET_SWATCHES = [
	'#ffffff', '#f8fafc', '#e5e7eb', '#9ca3af', '#4b5563', '#1f2433', '#111111', '#000000',
	'#7545bb', '#3b82f6', '#0ea5e9', '#16a34a', '#f59e0b', '#f97316', '#dc2626', '#ec4899',
];

function Svg( { inner, className }: { inner: string; className?: string } ) {
	return (
		<svg
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth={ 2 }
			className={ className }
			dangerouslySetInnerHTML={ { __html: inner } }
		/>
	);
}

/** Shared chevron-down glyph for every custom dropdown trigger (selects). */
function ChevronDownIcon() {
	return (
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
			<path d="m6 9 6 6 6-6" />
		</svg>
	);
}

/** Shared selected-item checkmark for every custom dropdown list. */
function CheckIcon() {
	return (
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.5 } aria-hidden="true">
			<path d="M20 6 9 17l-5-5" />
		</svg>
	);
}

/** Close a dropdown on outside click / Escape — shared by every custom select below.
 *  `extraRef` covers content that lives outside `rootRef` in the DOM (e.g. a portaled popover),
 *  so a click inside IT doesn't count as "outside" either. */
function useDismiss(
	open: boolean,
	rootRef: React.RefObject< HTMLElement >,
	onDismiss: () => void,
	extraRef?: React.RefObject< HTMLElement >
) {
	React.useEffect( () => {
		if ( ! open ) {
			return;
		}
		const onDown = ( e: MouseEvent ) => {
			const target = e.target as Node;
			if ( rootRef.current && rootRef.current.contains( target ) ) {
				return;
			}
			if ( extraRef?.current && extraRef.current.contains( target ) ) {
				return;
			}
			onDismiss();
		};
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				onDismiss();
			}
		};
		document.addEventListener( 'mousedown', onDown );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDown );
			document.removeEventListener( 'keydown', onKey );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ open ] );
}

/* --------------------------------------------------------------------- *
 * Shared label row + shell
 * --------------------------------------------------------------------- */

/** Body of the device-badge hover tooltip: explains the per-device state of a responsive token. */
function ResponsiveTip( { token, store, override }: { token: Token; store: StyleStore; override: boolean } ) {
	return (
		<>
			<div className="hovertip-title">
				<b>{ token.label }</b> — { __( 'responsive control', 'everest-forms' ) }
			</div>
			<div className="hovertip-body">
				{ store.device === 'desktop' ? (
					__(
						'You’re editing the Desktop base value. Switch to tablet or mobile to set a per-device override.',
						'everest-forms'
					)
				) : (
					<>
						{ __( 'Editing', 'everest-forms' ) } <b>{ DEVICE_LABELS[ store.device ] }</b> —{ ' ' }
						{ override
							? __( 'this device has its own value. Use the reset button to remove it.', 'everest-forms' )
							: __( 'currently inheriting the Desktop value.', 'everest-forms' ) }
					</>
				) }
			</div>
		</>
	);
}

function ControlShell( {
	token,
	store,
	right,
	inlineRight,
	children,
	dimmed,
}: ControlProps & { right?: React.ReactNode; inlineRight?: React.ReactNode; children: React.ReactNode } ) {
	const changed = store.isChanged( token.key );
	const inherited = token.responsive && store.device !== 'desktop' && ! store.isOverride( token.key );
	const override = store.isOverride( token.key );

	const classNames = [ 'ctrl' ];
	if ( changed ) {
		classNames.push( 'changed' );
	}
	if ( inherited ) {
		classNames.push( 'inherited' );
	}
	if ( dimmed ) {
		classNames.push( 'shared-dim' );
	}

	return (
		<div className={ classNames.join( ' ' ) } data-k={ token.key }>
			<div className="ctrl-lab">
				<span className="lab-left">
					<label>{ token.label }</label>
					{ token.responsive && store.device !== 'desktop' && (
						<HoverTip
							className={ 'dev-badge' + ( override ? ' override' : '' ) }
							label={ token.label + ' — ' + __( 'responsive options', 'everest-forms' ) }
							tip={ <ResponsiveTip token={ token } store={ store } override={ override } /> }
						>
							<Svg inner={ DEVICE_ICONS[ store.device ] } />
						</HoverTip>
					) }
					{ inlineRight }
				</span>
				<span className="lab-right">
					<button
						type="button"
						className="prop-reset"
						title={
							token.responsive && store.device !== 'desktop'
								? __( 'Remove override', 'everest-forms' )
								: __( 'Reset to default', 'everest-forms' )
						}
						aria-label={ __( 'Reset', 'everest-forms' ) + ' ' + token.label }
						onClick={ () => store.resetToken( token.key ) }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<path d="M3 12a9 9 0 1 0 3-6.7" />
							<path d="M3 4v5h5" />
						</svg>
					</button>
					{ right }
				</span>
			</div>
			{ children }
		</div>
	);
}

/* --------------------------------------------------------------------- *
 * Controls
 * --------------------------------------------------------------------- */

function SliderControl( props: ControlProps ) {
	const { token, store } = props;
	const value = Number( store.resolve( token.key ) );
	const unit = token.unit !== undefined ? token.unit : 'px';
	const min = token.min ?? 0;
	const max = token.max ?? 300;
	const numRef = React.useRef< HTMLInputElement >( null );
	useSyncedInput( numRef, String( value ) );

	const commit = ( raw: number, gesture: boolean ) => {
		if ( Number.isNaN( raw ) ) {
			return;
		}
		store.setTokenValue( token.key, roundToStep( clampNumber( raw, min, max ), token ), gesture );
	};

	// The native thumb travels inset by half its own width from each track edge (matches CSS).
	const THUMB = 15;
	const pct = max > min ? clampNumber( ( ( value - min ) / ( max - min ) ) * 100, 0, 100 ) : 0;

	return (
		<ControlShell { ...props }>
			<div className="slider">
				<div className="slider-track">
					<input
						type="range"
						min={ min }
						max={ max }
						step={ token.step || 1 }
						value={ value }
						aria-label={ token.label }
						aria-valuetext={ `${ value }${ unit }` }
						style={ { '--fill': `${ pct }%` } as React.CSSProperties }
						onChange={ ( e ) => commit( Number( e.target.value ), true ) }
					/>
					<span
						className="slider-tip"
						aria-hidden="true"
						style={ { left: `calc((100% - ${ THUMB }px) * ${ pct / 100 } + ${ THUMB / 2 }px)` } }
					>
						{ value }{ unit }
					</span>
				</div>
				<div className="num">
					<input
						ref={ numRef }
						inputMode="decimal"
						defaultValue={ String( value ) }
						aria-label={ token.label + ' value' }
						onInput={ ( e ) => {
							const n = isFloatStep( token )
								? parseFloat( ( e.target as HTMLInputElement ).value )
								: parseInt( ( e.target as HTMLInputElement ).value, 10 );
							commit( n, true );
						} }
						onBlur={ () => {
							if ( numRef.current ) {
								numRef.current.value = String( store.resolve( token.key ) );
							}
						} }
						onKeyDown={ ( e ) => {
							if ( e.key !== 'ArrowUp' && e.key !== 'ArrowDown' ) {
								return;
							}
							e.preventDefault();
							const base = token.step || 1;
							const step = e.shiftKey ? base * 10 : base;
							commit( Number( store.resolve( token.key ) ) + ( e.key === 'ArrowUp' ? step : -step ), true );
							( e.currentTarget as HTMLInputElement ).value = String( store.resolve( token.key ) );
						} }
					/>
					{ unit && <span>{ unit }</span> }
				</div>
			</div>
		</ControlShell>
	);
}

/**
 * Swatch + hex box that opens a popover with a full saturation/hue/alpha picker and an
 * "Opacity %" field — the one color-editing surface every part of the panel should share
 * (element controls, "Your Palette" slots, anywhere else a raw color needs editing).
 * Store/token-agnostic on purpose: the caller decides what `value` means and what `onChange`
 * does with the recomposed color string.
 */
export function ColorPickerField( {
	label,
	value,
	onChange,
}: {
	label: string;
	value: string;
	onChange: ( color: string ) => void;
} ) {
	const parsed = parseColor( value );
	const hexRef = React.useRef< HTMLInputElement >( null );
	const popHexRef = React.useRef< HTMLInputElement >( null );
	const popAlphaNumRef = React.useRef< HTMLInputElement >( null );
	const rootRef = React.useRef< HTMLDivElement >( null );
	const swatchRef = React.useRef< HTMLButtonElement >( null );
	const popRef = React.useRef< HTMLDivElement >( null );
	const [ invalid, setInvalid ] = React.useState( false );
	const [ pickerOpen, setPickerOpen ] = React.useState( false );
	const [ pos, setPos ] = React.useState< { left: number; top: number } | null >( null );
	const [ format, setFormat ] = React.useState< 'hex' | 'rgb' | 'hsl' >( 'hex' );
	const hasEyeDropper = typeof ( window as any ).EyeDropper !== 'undefined';
	useSyncedInput( hexRef, parsed.hex.toUpperCase() );
	useSyncedInput( popHexRef, parsed.hex.toUpperCase() );
	useSyncedInput( popAlphaNumRef, String( parsed.alpha ) );
	useDismiss( pickerOpen, rootRef, () => setPickerOpen( false ), popRef );

	const rgb = hexToRgb( parsed.hex );
	const hsl = rgbToHsl( rgb.r, rgb.g, rgb.b );
	const rRef = React.useRef< HTMLInputElement >( null );
	const gRef = React.useRef< HTMLInputElement >( null );
	const bRef = React.useRef< HTMLInputElement >( null );
	const hRef = React.useRef< HTMLInputElement >( null );
	const sRef = React.useRef< HTMLInputElement >( null );
	const lRef = React.useRef< HTMLInputElement >( null );
	useSyncedInput( rRef, String( rgb.r ) );
	useSyncedInput( gRef, String( rgb.g ) );
	useSyncedInput( bRef, String( rgb.b ) );
	useSyncedInput( hRef, String( hsl.h ) );
	useSyncedInput( sRef, String( hsl.s ) );
	useSyncedInput( lRef, String( hsl.l ) );

	// Portaled + position:fixed (see below) so the popover can never be clipped by a scrolling
	// ancestor (the panel sidebar, a palette's own scrollable row list, etc.) — same escape-hatch
	// HoverTip already uses for its own tooltip.
	const updatePos = React.useCallback( () => {
		const trigger = swatchRef.current;
		if ( ! trigger ) {
			return;
		}
		const r = trigger.getBoundingClientRect();
		const width = popRef.current?.offsetWidth || 264;
		const height = popRef.current?.offsetHeight || 0;
		let left = r.left;
		left = Math.min( Math.max( 8, left ), window.innerWidth - width - 8 );
		let top = r.bottom + 6;
		if ( height && top + height > window.innerHeight - 8 ) {
			top = r.top - height - 6;
		}
		setPos( { left, top: Math.max( 8, top ) } );
	}, [] );

	React.useLayoutEffect( () => {
		if ( pickerOpen ) {
			updatePos();
		} else {
			setPos( null );
		}
	}, [ pickerOpen, updatePos ] );

	React.useEffect( () => {
		if ( ! pickerOpen ) {
			return;
		}
		window.addEventListener( 'scroll', updatePos, true );
		window.addEventListener( 'resize', updatePos );
		return () => {
			window.removeEventListener( 'scroll', updatePos, true );
			window.removeEventListener( 'resize', updatePos );
		};
	}, [ pickerOpen, updatePos ] );

	const commitHex = ( hex: string ) => onChange( composeColor( hex, parsed.alpha ) );
	const commitAlpha = ( alpha: number ) => onChange( composeColor( parsed.hex, clampNumber( alpha, 0, 100 ) ) );
	const commitHex8 = ( hex8: string ) => {
		const p = parseColor( hex8 );
		onChange( composeColor( p.hex, p.alpha ) );
	};
	const commitRgb = ( r: number, g: number, b: number ) => commitHex( rgbToHex( r, g, b ) );
	const commitHsl = ( h: number, s: number, l: number ) => {
		const c = hslToRgb( h, s, l );
		commitHex( rgbToHex( c.r, c.g, c.b ) );
	};

	const onHex = ( e: React.FormEvent< HTMLInputElement > ) => {
		let t = ( e.target as HTMLInputElement ).value.trim();
		if ( t && t[ 0 ] !== '#' ) {
			t = '#' + t;
		}
		if ( /^#[0-9a-f]{3}$/i.test( t ) ) {
			t = '#' + t.slice( 1 ).split( '' ).map( ( c ) => c + c ).join( '' );
		}
		if ( /^#[0-9a-f]{6}$/i.test( t ) ) {
			setInvalid( false );
			commitHex( t.toLowerCase() );
		} else {
			setInvalid( true );
		}
	};

	/* Chromium's EyeDropper API — sample any pixel on screen (including outside the browser
	 * window) straight into this field. Feature-detected: the tool button simply doesn't render
	 * in browsers without it (Firefox, Safari at time of writing). */
	const pickFromScreen = async () => {
		try {
			const ED = ( window as any ).EyeDropper;
			const result = await new ED().open();
			if ( result?.sRGBHex ) {
				commitHex( result.sRGBHex.toLowerCase() );
			}
		} catch {
			// User cancelled (Escape) — nothing to do.
		}
	};

	return (
		<div className={ 'color' + ( invalid ? ' invalid' : '' ) } ref={ rootRef }>
			<button
				ref={ swatchRef }
				type="button"
				className="swatch"
				style={ { '--swatch': composeColor( parsed.hex, parsed.alpha ) } as React.CSSProperties }
				aria-haspopup="true"
				aria-expanded={ pickerOpen }
				aria-label={ label }
				onClick={ () => setPickerOpen( ( o ) => ! o ) }
			/>
			<input
				ref={ hexRef }
				className="hex"
				spellCheck={ false }
				defaultValue={ parsed.hex.toUpperCase() }
				aria-label={ label + ' hex value' }
				onInput={ onHex }
				onBlur={ () => {
					setInvalid( false );
					if ( hexRef.current ) {
						hexRef.current.value = parseColor( value ).hex.toUpperCase();
					}
				} }
			/>
			{ invalid && <span className="err">{ __( 'Invalid color', 'everest-forms' ) }</span> }
			{ pickerOpen &&
				createPortal(
					<div
						ref={ popRef }
						className="color-pop"
						style={ { left: ( pos || { left: -9999, top: -9999 } ).left, top: ( pos || { left: -9999, top: -9999 } ).top } }
					>
						<div className="color-pop-head">
							<span>{ label }</span>
							{ hasEyeDropper && (
								<button
									type="button"
									className="eyedrop-btn"
									aria-label={ __( 'Pick color from screen', 'everest-forms' ) }
									title={ __( 'Pick color from screen', 'everest-forms' ) }
									onClick={ pickFromScreen }
								>
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
										<path d="m2 22 1-4 9.5-9.5" />
										<path d="M14.5 6.5 18 3a2.12 2.12 0 0 1 3 3l-3.5 3.5" />
										<path d="m11.5 8.5 4 4" />
										<path d="M3 21h4l9.5-9.5-4-4L3 17z" />
									</svg>
								</button>
							) }
						</div>
						<HexAlphaColorPicker
							color={ toHex8( parsed.hex, parsed.alpha ) }
							onChange={ commitHex8 }
						/>
						<div className="cpf-format" role="tablist" aria-label={ __( 'Color format', 'everest-forms' ) }>
							{ ( [ 'hex', 'rgb', 'hsl' ] as const ).map( ( f ) => (
								<button
									key={ f }
									type="button"
									role="tab"
									aria-selected={ format === f }
									className={ 'cpf-format-btn' + ( format === f ? ' is-active' : '' ) }
									onClick={ () => setFormat( f ) }
								>
									{ f.toUpperCase() }
								</button>
							) ) }
						</div>
						<div className="color-pop-fields">
							{ format === 'hex' && (
								<div className="cpf-hex">
									<span className="cpf-label">{ __( 'Hex', 'everest-forms' ) }</span>
									<div className="num">
										<input
											ref={ popHexRef }
											spellCheck={ false }
											defaultValue={ parsed.hex.toUpperCase() }
											aria-label={ label + ' ' + __( 'hex value', 'everest-forms' ) }
											onInput={ onHex }
											onBlur={ () => {
												if ( popHexRef.current ) {
													popHexRef.current.value = parseColor( value ).hex.toUpperCase();
												}
											} }
										/>
									</div>
								</div>
							) }
							{ format === 'rgb' && (
								<>
									<NumField
										label="R"
										ariaLabel={ label + ' ' + __( 'red value', 'everest-forms' ) }
										value={ rgb.r }
										min={ 0 }
										max={ 255 }
										inputRef={ rRef }
										onCommit={ ( n ) => commitRgb( n, rgb.g, rgb.b ) }
									/>
									<NumField
										label="G"
										ariaLabel={ label + ' ' + __( 'green value', 'everest-forms' ) }
										value={ rgb.g }
										min={ 0 }
										max={ 255 }
										inputRef={ gRef }
										onCommit={ ( n ) => commitRgb( rgb.r, n, rgb.b ) }
									/>
									<NumField
										label="B"
										ariaLabel={ label + ' ' + __( 'blue value', 'everest-forms' ) }
										value={ rgb.b }
										min={ 0 }
										max={ 255 }
										inputRef={ bRef }
										onCommit={ ( n ) => commitRgb( rgb.r, rgb.g, n ) }
									/>
								</>
							) }
							{ format === 'hsl' && (
								<>
									<NumField
										label="H"
										ariaLabel={ label + ' ' + __( 'hue value', 'everest-forms' ) }
										value={ hsl.h }
										min={ 0 }
										max={ 360 }
										inputRef={ hRef }
										onCommit={ ( n ) => commitHsl( n, hsl.s, hsl.l ) }
									/>
									<NumField
										label="S"
										ariaLabel={ label + ' ' + __( 'saturation value', 'everest-forms' ) }
										value={ hsl.s }
										min={ 0 }
										max={ 100 }
										suffix="%"
										inputRef={ sRef }
										onCommit={ ( n ) => commitHsl( hsl.h, n, hsl.l ) }
									/>
									<NumField
										label="L"
										ariaLabel={ label + ' ' + __( 'lightness value', 'everest-forms' ) }
										value={ hsl.l }
										min={ 0 }
										max={ 100 }
										suffix="%"
										inputRef={ lRef }
										onCommit={ ( n ) => commitHsl( hsl.h, hsl.s, n ) }
									/>
								</>
							) }
							<div className="cpf-alpha">
								<span className="cpf-label">{ __( 'Opacity', 'everest-forms' ) }</span>
								<div className="num">
									<input
										ref={ popAlphaNumRef }
										inputMode="numeric"
										defaultValue={ String( parsed.alpha ) }
										aria-label={ label + ' ' + __( 'opacity value', 'everest-forms' ) }
										onInput={ ( e ) => {
											const n = Number( ( e.target as HTMLInputElement ).value );
											if ( ! Number.isNaN( n ) ) {
												commitAlpha( n );
											}
										} }
										onBlur={ () => {
											if ( popAlphaNumRef.current ) {
												popAlphaNumRef.current.value = String( parseColor( value ).alpha );
											}
										} }
									/>
									<span>%</span>
								</div>
							</div>
						</div>
						<div className="color-pop-swatches" role="group" aria-label={ __( 'Preset colors', 'everest-forms' ) }>
							{ PRESET_SWATCHES.map( ( c ) => (
								<button
									key={ c }
									type="button"
									className="cps-swatch"
									style={ { background: c } }
									aria-label={ c }
									title={ c }
									onClick={ () => commitHex( c ) }
								/>
							) ) }
						</div>
					</div>,
					popoverHost()
				) }
		</div>
	);
}

function ColorControl( props: ControlProps ) {
	const { token, store } = props;
	const value = String( store.resolve( token.key ) );
	return (
		<ControlShell { ...props }>
			<ColorPickerField
				label={ token.label }
				value={ value }
				onChange={ ( color ) => store.setTokenValue( token.key, color, true ) }
			/>
		</ControlShell>
	);
}

const SIDE_LABELS = [ 'Top', 'Right', 'Bottom', 'Left' ] as const;
const CORNER_LABELS = [ 'Top-left', 'Top-right', 'Bottom-right', 'Bottom-left' ] as const;
const SIDE_ABBR = [ 'T', 'R', 'B', 'L' ];
const CORNER_ABBR = [ 'TL', 'TR', 'BR', 'BL' ];
const BOX_KEYS: Array< keyof BoxValue > = [ 'top', 'right', 'bottom', 'left' ];

/** Are all four box sides currently equal? Seeds the initial "link sides" state. */
function allSidesEqual( v: BoxValue ): boolean {
	return v.top === v.right && v.right === v.bottom && v.bottom === v.left;
}

function Box4Control( props: ControlProps ) {
	const { token, store } = props;
	const value = clone( store.resolve( token.key ) ) as BoxValue;
	const cellRefs = [
		React.useRef< HTMLInputElement >( null ),
		React.useRef< HTMLInputElement >( null ),
		React.useRef< HTMLInputElement >( null ),
		React.useRef< HTMLInputElement >( null ),
	];
	const [ linked, setLinked ] = React.useState( () => allSidesEqual( value ) );

	const min = token.min ?? ( token.key.indexOf( 'margin' ) !== -1 ? -1000 : 0 );
	const max = token.max ?? 1000;
	const abbr = token.corners ? CORNER_ABBR : SIDE_ABBR;
	const labels = token.corners ? CORNER_LABELS : SIDE_LABELS;
	const unit = token.units && token.units.length ? value.unit || token.units[ 0 ] : null;

	React.useEffect( () => {
		cellRefs.forEach( ( ref, i ) => {
			const el = ref.current;
			if ( el && el.ownerDocument.activeElement !== el ) {
				el.value = String( value[ BOX_KEYS[ i ] ] ?? 0 );
			}
		} );
	} );

	const commit = ( index: number, raw: number ) => {
		if ( Number.isNaN( raw ) ) {
			return;
		}
		const n = clampNumber( raw, min, max );
		const next = clone( store.resolve( token.key ) ) as BoxValue;
		if ( linked ) {
			BOX_KEYS.forEach( ( k ) => ( next[ k ] = n ) );
			cellRefs.forEach( ( r ) => r.current && ( r.current.value = String( n ) ) );
		} else {
			next[ BOX_KEYS[ index ] ] = n;
		}
		store.setTokenValue( token.key, next, true );
	};

	const toggleUnit = () => {
		if ( ! token.units || token.units.length < 2 ) {
			return;
		}
		const next = clone( store.resolve( token.key ) ) as BoxValue;
		const cur = next.unit || token.units[ 0 ];
		next.unit = token.units[ ( token.units.indexOf( cur ) + 1 ) % token.units.length ];
		store.setTokenValue( token.key, next, false );
	};

	return (
		<ControlShell { ...props } right={ ! token.units ? <span className="px-hint">px</span> : undefined }>
			<div className="box4">
				<div className="box4-cells">
					<div className="box4-inputs">
						{ BOX_KEYS.map( ( _k, i ) => (
							<div className="cell" key={ i }>
								<div className="num">
									<input
										ref={ cellRefs[ i ] }
										inputMode="numeric"
										defaultValue={ String( value[ BOX_KEYS[ i ] ] ?? 0 ) }
										aria-label={ token.label + ' ' + labels[ i ] }
										onInput={ ( e ) => commit( i, parseInt( ( e.target as HTMLInputElement ).value, 10 ) ) }
										onBlur={ ( e ) => {
											( e.target as HTMLInputElement ).value = String(
												( clone( store.resolve( token.key ) ) as BoxValue )[ BOX_KEYS[ i ] ] ?? 0
											);
										} }
										onKeyDown={ ( e ) => {
											if ( e.key !== 'ArrowUp' && e.key !== 'ArrowDown' ) {
												return;
											}
											e.preventDefault();
											const cur = ( clone( store.resolve( token.key ) ) as BoxValue )[ BOX_KEYS[ i ] ] ?? 0;
											const step = e.shiftKey ? 10 : 1;
											commit( i, Number( cur ) + ( e.key === 'ArrowUp' ? step : -step ) );
											( e.currentTarget as HTMLInputElement ).value = String(
												( clone( store.resolve( token.key ) ) as BoxValue )[ BOX_KEYS[ i ] ] ?? 0
											);
										} }
									/>
								</div>
							</div>
						) ) }
					</div>
					<div className="box4-abbr" aria-hidden="true">
						{ BOX_KEYS.map( ( _k, i ) => (
							<small key={ i }>{ abbr[ i ] }</small>
						) ) }
					</div>
				</div>
				<button
					type="button"
					className="link"
					aria-pressed={ linked }
					aria-label={ token.label + ' — ' + __( 'link sides', 'everest-forms' ) }
					title={ __( 'Link sides', 'everest-forms' ) }
					onClick={ () => setLinked( ! linked ) }
				>
					<Svg inner='<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>' />
				</button>
				{ unit && (
					<button
						type="button"
						className="unit-tgl"
						aria-label={
							token.label + ' — ' + __( 'unit', 'everest-forms' ) + ': ' + unit + '. ' +
							__( 'Click to change.', 'everest-forms' )
						}
						onClick={ toggleUnit }
					>
						{ unit }
					</button>
				) }
			</div>
		</ControlShell>
	);
}

/** Custom dropdown select — a styled replacement for the native `<select>`. */
function SelectControl( props: ControlProps & { depHint?: string } ) {
	const { token, store, depHint } = props;
	const value = String( store.resolve( token.key ) );
	const options = token.options || [];
	const current = options.find( ( o ) => o.value === value );
	const [ open, setOpen ] = React.useState( false );
	const rootRef = React.useRef< HTMLDivElement >( null );

	useDismiss( open, rootRef, () => setOpen( false ) );

	const choose = ( v: string ) => {
		store.setTokenValue( token.key, v, false );
		setOpen( false );
	};

	return (
		<ControlShell { ...props }>
			<div className="dsel" ref={ rootRef }>
				<button
					type="button"
					className="dsel-trigger"
					aria-haspopup="listbox"
					aria-expanded={ open }
					aria-label={ token.label }
					onClick={ () => setOpen( ( o ) => ! o ) }
				>
					<span className="dsel-val">{ current ? current.label : value }</span>
					<span className="dsel-chev">
						<ChevronDownIcon />
					</span>
				</button>
				{ open && (
					<div className="dsel-pop">
						<div className="dsel-list" role="listbox" aria-label={ token.label }>
							{ options.map( ( o ) => (
								<button
									key={ o.value }
									type="button"
									role="option"
									aria-selected={ o.value === value }
									className={ 'dsel-opt' + ( o.value === value ? ' sel' : '' ) }
									onClick={ () => choose( o.value ) }
								>
									<span className="dsel-check">{ o.value === value && <CheckIcon /> }</span>
									{ o.label }
								</button>
							) ) }
						</div>
					</div>
				) }
			</div>
			{ depHint && <div className="dep-hint">{ depHint }</div> }
		</ControlShell>
	);
}

/** Searchable font-family picker (combobox) for the ~1000-entry Google Fonts list. */
function FontSelectControl( props: ControlProps & { depHint?: string } ) {
	const { token, store, depHint } = props;
	const value = String( store.resolve( token.key ) );
	const themeFont = store.themeFont();
	const disabled = themeFont;

	const THEME_DEFAULT = __( 'Theme default', 'everest-forms' );
	const [ open, setOpen ] = React.useState( false );
	const [ query, setQuery ] = React.useState( '' );
	const [ active, setActive ] = React.useState( 0 );
	const rootRef = React.useRef< HTMLDivElement >( null );
	const listRef = React.useRef< HTMLDivElement >( null );
	const searchRef = React.useRef< HTMLInputElement >( null );

	const allOptions = React.useMemo( () => {
		const base = [ { value: '', label: THEME_DEFAULT } ];
		( store.googleFonts || [] ).forEach( ( f ) => base.push( { value: f, label: f } ) );
		return base;
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ store.googleFonts ] );

	const filtered = React.useMemo( () => {
		const q = query.trim().toLowerCase();
		return q ? allOptions.filter( ( o ) => o.label.toLowerCase().indexOf( q ) !== -1 ) : allOptions;
	}, [ query, allOptions ] );

	useDismiss( open, rootRef, () => setOpen( false ) );

	React.useEffect( () => {
		if ( ! open ) {
			return;
		}
		setQuery( '' );
		const idx = allOptions.findIndex( ( o ) => o.value === value );
		setActive( idx >= 0 ? idx : 0 );
		const t = window.setTimeout( () => searchRef.current && searchRef.current.focus(), 0 );
		return () => window.clearTimeout( t );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ open ] );

	React.useEffect( () => {
		if ( ! open || ! listRef.current ) {
			return;
		}
		const el = listRef.current.children[ active ] as HTMLElement | undefined;
		if ( el && el.scrollIntoView ) {
			el.scrollIntoView( { block: 'nearest' } );
		}
	}, [ active, open ] );

	const choose = ( v: string ) => {
		store.setTokenValue( token.key, v, false );
		setOpen( false );
	};

	const onKeyDown = ( e: React.KeyboardEvent ) => {
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			setActive( ( a ) => Math.min( filtered.length - 1, a + 1 ) );
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			setActive( ( a ) => Math.max( 0, a - 1 ) );
		} else if ( e.key === 'Enter' ) {
			e.preventDefault();
			if ( filtered[ active ] ) {
				choose( filtered[ active ].value );
			}
		} else if ( e.key === 'Escape' ) {
			e.preventDefault();
			setOpen( false );
		}
	};

	const hint = ! themeFont
		? depHint || ''
		: store.applyThemeStyle
			? __( 'Using your theme’s font — controlled by “Apply Theme Style” above.', 'everest-forms' )
			: __( 'Using your theme’s font. Turn off “Use theme fonts” to choose one.', 'everest-forms' );

	return (
		<ControlShell { ...props }>
			<div className="dsel" ref={ rootRef }>
				<button
					type="button"
					className="dsel-trigger"
					disabled={ disabled }
					aria-haspopup="listbox"
					aria-expanded={ open }
					aria-label={ token.label }
					onClick={ () => ! disabled && setOpen( ( o ) => ! o ) }
				>
					<span className="dsel-val">{ value || THEME_DEFAULT }</span>
					<span className="dsel-chev">
						<ChevronDownIcon />
					</span>
				</button>
				{ open && (
					<div className="dsel-pop">
						<div className="dsel-search-wrap">
							<svg className="dsel-search-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
								<circle cx="11" cy="11" r="7" />
								<path d="m21 21-4.35-4.35" />
							</svg>
							<input
								ref={ searchRef }
								type="text"
								className="dsel-search"
								placeholder={ __( 'Search fonts…', 'everest-forms' ) }
								value={ query }
								aria-label={ __( 'Search fonts', 'everest-forms' ) }
								onChange={ ( e ) => {
									setQuery( e.target.value );
									setActive( 0 );
								} }
								onKeyDown={ onKeyDown }
							/>
						</div>
						<div className="dsel-list" ref={ listRef } role="listbox" aria-label={ token.label }>
							{ filtered.length === 0 ? (
								<div className="dsel-empty">{ __( 'No fonts found', 'everest-forms' ) }</div>
							) : (
								filtered.map( ( o, i ) => (
									<button
										key={ o.value || '__default' }
										type="button"
										role="option"
										aria-selected={ o.value === value }
										className={
											'dsel-opt' +
											( i === active ? ' active' : '' ) +
											( o.value === value ? ' sel' : '' )
										}
										onMouseEnter={ () => setActive( i ) }
										onClick={ () => choose( o.value ) }
									>
										<span className="dsel-check">{ o.value === value && <CheckIcon /> }</span>
										{ o.label }
									</button>
								) )
							) }
						</div>
					</div>
				) }
			</div>
			{ hint && <div className="dep-hint">{ hint }</div> }
		</ControlShell>
	);
}

function AlignControl( props: ControlProps ) {
	const { token, store } = props;
	const value = String( store.resolve( token.key ) );
	return (
		<ControlShell { ...props }>
			<div className="iconset" role="group" aria-label={ token.label }>
				{ [ 'left', 'center', 'right' ].map( ( a ) => (
					<button
						key={ a }
						type="button"
						aria-pressed={ value === a }
						aria-label={ __( 'Align', 'everest-forms' ) + ' ' + a }
						onClick={ () => store.setTokenValue( token.key, a, false ) }
					>
						<Svg inner={ ALIGN_ICONS[ a ] } />
					</button>
				) ) }
			</div>
		</ControlShell>
	);
}

function FontStyleControl( props: ControlProps ) {
	const { token, store } = props;
	const value = ( store.resolve( token.key ) || {} ) as FontStyleValue;
	const weightOptions = token.weight_options || [];
	return (
		<ControlShell { ...props }>
			<div className="fstylewrap">
				{ weightOptions.length > 0 && (
					<select
						className="fweight-select"
						aria-label={ __( 'Font weight', 'everest-forms' ) }
						value={ value.weight || '' }
						onChange={ ( e ) => {
							const next = clone( store.resolve( token.key ) ) as FontStyleValue;
							next.weight = e.target.value;
							store.setTokenValue( token.key, next, false );
						} }
					>
						{ weightOptions.map( ( o ) => (
							<option key={ o.value } value={ o.value }>{ o.label }</option>
						) ) }
					</select>
				) }
				<div className="fstyleset" role="group" aria-label={ token.label }>
					{ FONTSTYLE_BUTTONS.map( ( [ flag, glyph, title ] ) => (
						<button
							key={ flag }
							type="button"
							aria-pressed={ !! value[ flag ] }
							title={ title }
							aria-label={ title }
							dangerouslySetInnerHTML={ { __html: glyph } }
							onClick={ () => {
								const next = clone( store.resolve( token.key ) ) as FontStyleValue;
								next[ flag ] = ! next[ flag ];
								store.setTokenValue( token.key, next, false );
							} }
						/>
					) ) }
				</div>
			</div>
		</ControlShell>
	);
}

function MediaControl( props: ControlProps ) {
	const { token, store } = props;
	const value = String( store.resolve( token.key ) || '' );

	const openPicker = () => {
		const media = ( window as any ).wp?.media;
		if ( ! media ) {
			return;
		}
		const frame = media( {
			title: __( 'Select background image', 'everest-forms' ),
			multiple: false,
			library: { type: 'image' },
		} );
		frame.on( 'select', () => {
			const att = frame.state().get( 'selection' ).first().toJSON();
			if ( att && att.url ) {
				store.setTokenValue( token.key, att.url, false );
			}
		} );
		frame.open();
	};

	return (
		<ControlShell { ...props }>
			<div className="mediactrl">
				<button type="button" className="mediabtn" onClick={ openPicker }>
					{ value ? __( 'Change image', 'everest-forms' ) : __( 'Select image', 'everest-forms' ) }
				</button>
				{ value && (
					<button
						type="button"
						className="mediaclear"
						aria-label={ __( 'Remove image', 'everest-forms' ) }
						onClick={ () => store.setTokenValue( token.key, '', false ) }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<path d="M18 6 6 18M6 6l12 12" />
						</svg>
					</button>
				) }
			</div>
		</ControlShell>
	);
}

function ToggleControl( props: ControlProps ) {
	const { token, store } = props;
	// The global "Apply Theme Style" toggle forces fonts.theme on (see store.themeFont()) — disable
	// this switch rather than leave it clickable-but-inert, and say why.
	const forcedByGlobal = token.key === 'fonts.theme' && store.applyThemeStyle;
	const value = forcedByGlobal || store.resolve( token.key ) === true;
	const hint = forcedByGlobal
		? __( 'Forced on by the global “Apply Theme Style” setting above.', 'everest-forms' )
		: '';
	return (
		<ControlShell
			{ ...props }
			right={
				<button
					type="button"
					className="switch"
					role="switch"
					disabled={ forcedByGlobal }
					aria-checked={ value }
					aria-label={ token.label }
					onClick={ () => store.setTokenValue( token.key, ! value, false ) }
				/>
			}
		>
			{ hint && <div className="dep-hint">{ hint }</div> }
		</ControlShell>
	);
}

export function ControlRenderer( props: ControlProps & { depHint?: string } ) {
	switch ( props.token.type ) {
		case 'slider':
			return <SliderControl { ...props } />;
		case 'color':
			return <ColorControl { ...props } />;
		case 'box4':
			return <Box4Control { ...props } />;
		case 'select':
			return props.token.source === 'google_fonts' ? (
				<FontSelectControl { ...props } />
			) : (
				<SelectControl { ...props } />
			);
		case 'align':
			return <AlignControl { ...props } />;
		case 'fontstyle':
			return <FontStyleControl { ...props } />;
		case 'media':
			return <MediaControl { ...props } />;
		case 'toggle':
			return <ToggleControl { ...props } />;
		default:
			return null;
	}
}
