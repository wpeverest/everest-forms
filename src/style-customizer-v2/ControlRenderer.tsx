/**
 * Style Customizer v2 — ControlRenderer + control types.
 *
 * One component per schema control type (slider, color, box4, select, align, fontstyle,
 * media, toggle). Every control reads/writes through the store (the single guarded write
 * path) and renders the shared label row (label · responsive badge · reset).
 *
 * Text inputs are uncontrolled + synced imperatively so React re-renders never fight the
 * caret while typing — mirroring the prototype's paint() model.
 */
import React from 'react';
import {
	ALIGN_ICONS,
	DEVICE_ICONS,
	FONTSTYLE_BUTTONS,
	clone,
} from './constants';
import { StyleStore } from './store';
import { BoxValue, FontStyleValue, Token } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

interface ControlProps {
	token: Token;
	store: StyleStore;
	onBadgeClick: ( token: Token, anchor: HTMLElement ) => void;
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

/* --------------------------------------------------------------------- *
 * Shared label row + shell
 * --------------------------------------------------------------------- */

function ControlShell( {
	token,
	store,
	onBadgeClick,
	right,
	inlineRight,
	children,
	dimmed,
}: ControlProps & { right?: React.ReactNode; inlineRight?: React.ReactNode; children: React.ReactNode } ) {
	const badgeRef = React.useRef< HTMLButtonElement >( null );
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
					{ token.responsive && (
						<button
							ref={ badgeRef }
							type="button"
							className={ 'dev-badge' + ( override ? ' override' : '' ) }
							aria-label={ __( 'Responsive options', 'everest-forms' ) }
							onClick={ () => badgeRef.current && onBadgeClick( token, badgeRef.current ) }
						>
							<Svg inner={ DEVICE_ICONS[ store.device ] } />
						</button>
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
						↺
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

	return (
		<ControlShell { ...props }>
			<div className="slider">
				<input
					type="range"
					min={ min }
					max={ max }
					step={ token.step || 1 }
					value={ value }
					aria-label={ token.label }
					onChange={ ( e ) => commit( Number( e.target.value ), true ) }
				/>
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
						} }
					/>
					<span>{ unit }</span>
				</div>
			</div>
		</ControlShell>
	);
}

function ColorControl( props: ControlProps ) {
	const { token, store } = props;
	const value = String( store.resolve( token.key ) );
	const hexRef = React.useRef< HTMLInputElement >( null );
	const [ invalid, setInvalid ] = React.useState( false );
	useSyncedInput( hexRef, value.toUpperCase() );

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
			store.setTokenValue( token.key, t.toLowerCase(), true );
		} else {
			setInvalid( true );
		}
	};

	return (
		<ControlShell { ...props }>
			<div className={ 'color' + ( invalid ? ' invalid' : '' ) }>
				<input
					type="color"
					value={ /^#[0-9a-f]{6}$/i.test( value ) ? value : '#000000' }
					aria-label={ token.label }
					onChange={ ( e ) => {
						setInvalid( false );
						store.setTokenValue( token.key, e.target.value, true );
					} }
				/>
				<input
					ref={ hexRef }
					className="hex"
					spellCheck={ false }
					defaultValue={ value.toUpperCase() }
					aria-label={ token.label + ' hex value' }
					onInput={ onHex }
					onBlur={ () => {
						setInvalid( false );
						if ( hexRef.current ) {
							hexRef.current.value = String( store.resolve( token.key ) ).toUpperCase();
						}
					} }
				/>
				{ invalid && <span className="err">{ __( 'Invalid color', 'everest-forms' ) }</span> }
			</div>
		</ControlShell>
	);
}

const SIDE_LABELS = [ 'Top', 'Right', 'Bottom', 'Left' ] as const;
const CORNER_LABELS = [ 'Top-left', 'Top-right', 'Bottom-right', 'Bottom-left' ] as const;
const SIDE_ABBR = [ 'T', 'R', 'B', 'L' ];
const CORNER_ABBR = [ 'TL', 'TR', 'BR', 'BL' ];
const BOX_KEYS: Array< keyof BoxValue > = [ 'top', 'right', 'bottom', 'left' ];

function Box4Control( props: ControlProps ) {
	const { token, store } = props;
	const value = clone( store.resolve( token.key ) ) as BoxValue;
	const cellRefs = [
		React.useRef< HTMLInputElement >( null ),
		React.useRef< HTMLInputElement >( null ),
		React.useRef< HTMLInputElement >( null ),
		React.useRef< HTMLInputElement >( null ),
	];
	const [ linked, setLinked ] = React.useState( true );

	const min = token.key.indexOf( 'margin' ) !== -1 ? -1000 : 0;
	const max = token.max ?? 1000;
	const abbr = token.corners ? CORNER_ABBR : SIDE_ABBR;
	const labels = token.corners ? CORNER_LABELS : SIDE_LABELS;
	const unit = token.units && token.units.length ? value.unit || token.units[ 0 ] : null;

	// Sync cells imperatively so typing isn't interrupted.
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
				{ BOX_KEYS.map( ( _k, i ) => (
					<div className="cell" key={ i }>
						<div className="num">
							<input
								ref={ cellRefs[ i ] }
								inputMode="numeric"
								defaultValue={ String( value[ BOX_KEYS[ i ] ] ?? 0 ) }
								aria-label={ labels[ i ] }
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
								} }
							/>
						</div>
						<small>{ abbr[ i ] }</small>
					</div>
				) ) }
				<button
					type="button"
					className="link"
					aria-pressed={ linked }
					aria-label={ __( 'Link sides', 'everest-forms' ) }
					title={ __( 'Link sides', 'everest-forms' ) }
					onClick={ () => setLinked( ! linked ) }
				>
					<Svg inner='<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>' />
				</button>
				{ unit && (
					<button type="button" className="unit-tgl" aria-label={ __( 'Unit', 'everest-forms' ) } onClick={ toggleUnit }>
						{ unit }
					</button>
				) }
			</div>
		</ControlShell>
	);
}

function SelectControl( props: ControlProps & { depHint?: string } ) {
	const { token, store, depHint } = props;
	const value = String( store.resolve( token.key ) );
	const themeFont = store.themeFont();
	const disabled = token.key === 'fonts.family' && themeFont;
	const hint =
		token.key === 'fonts.family' && themeFont
			? __( 'Using your theme’s font. Turn off “Use theme fonts” to choose one.', 'everest-forms' )
			: depHint || '';

	return (
		<ControlShell { ...props }>
			<select
				className="inp"
				value={ value }
				disabled={ disabled }
				aria-label={ token.label }
				onChange={ ( e ) => store.setTokenValue( token.key, e.target.value, false ) }
			>
				{ ( token.options || [] ).map( ( o ) => (
					<option key={ o.value } value={ o.value }>
						{ o.label }
					</option>
				) ) }
			</select>
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
	return (
		<ControlShell { ...props }>
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
						✕
					</button>
				) }
			</div>
		</ControlShell>
	);
}

function ToggleControl( props: ControlProps ) {
	const { token, store } = props;
	const value = store.resolve( token.key ) === true;
	return (
		<ControlShell
			{ ...props }
			right={
				<button
					type="button"
					className="switch"
					role="switch"
					aria-checked={ value }
					aria-label={ token.label }
					onClick={ () => store.setTokenValue( token.key, ! value, false ) }
				/>
			}
		>
			{ null }
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
			return <SelectControl { ...props } />;
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
