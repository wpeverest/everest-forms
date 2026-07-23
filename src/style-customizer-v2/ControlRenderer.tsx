/**
 * Style Customizer v2 — one component per schema control type (slider, color, box4, select,
 * align, fontstyle, media, toggle), each reading/writing through the store.
 */
import React from 'react';
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

/** Close a dropdown on outside click / Escape — shared by every custom select below. */
function useDismiss( open: boolean, rootRef: React.RefObject< HTMLElement >, onDismiss: () => void ) {
	React.useEffect( () => {
		if ( ! open ) {
			return;
		}
		const onDown = ( e: MouseEvent ) => {
			if ( rootRef.current && ! rootRef.current.contains( e.target as Node ) ) {
				onDismiss();
			}
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

	const hint = themeFont
		? __( 'Using your theme’s font. Turn off “Use theme fonts” to choose one.', 'everest-forms' )
		: depHint || '';

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
