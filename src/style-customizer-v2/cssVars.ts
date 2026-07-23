/**
 * Style Customizer v2 — token → CSS custom properties. Client mirror of Compiler.php; keep
 * this in lockstep with Compiler::declarations()/format().
 */
import { BoxValue, DeviceBag, Device, FontStyleValue, ScalarValue, Token } from './types';

const SIDES: Array<keyof BoxValue> = [ 'top', 'right', 'bottom', 'left' ];

/** Resolves a device bag to a concrete value for a device. Mirrors Compiler::resolve(). */
export function resolveValue( bag: DeviceBag | undefined, token: Token, device: Device ): ScalarValue {
	if ( bag && bag[ device ] !== undefined && bag[ device ] !== '' ) {
		return bag[ device ] as ScalarValue;
	}
	if ( bag && bag.desktop !== undefined && bag.desktop !== '' ) {
		return bag.desktop;
	}
	return token.default;
}

/** Is this device explicitly overriding the desktop base for a responsive token? */
export function isOverride( bag: DeviceBag | undefined, token: Token, device: Device ): boolean {
	return !! ( token.responsive && device !== 'desktop' && bag && bag[ device ] !== undefined );
}

/** Belt-and-suspenders: strip anything that could break out of a CSS declaration. */
function cssSafe( value: string ): string {
	return value.replace( /[<>{};\\]|\/\*|\*\//g, '' );
}

/** Format a single value into its CSS string. Mirrors Compiler::format(). */
export function formatValue( token: Token, value: ScalarValue ): string {
	switch ( token.type ) {
		case 'slider': {
			if ( value === '' || value === null || value === undefined || Number.isNaN( Number( value ) ) ) {
				return '';
			}
			const unit = token.unit !== undefined ? token.unit : 'px';
			return `${ value }${ unit }`;
		}
		case 'box4':
			return formatBox( token, value as BoxValue );
		case 'media':
			return value ? `url("${ String( value ).replace( /"/g, '' ) }")` : 'none';
		case 'color':
		case 'select':
		default:
			return value === '' || value === null || value === undefined ? '' : cssSafe( String( value ) );
	}
}

function formatBox( token: Token, value: BoxValue ): string {
	if ( ! value || typeof value !== 'object' ) {
		return '';
	}
	let unit = 'px';
	if ( token.units && token.units.length ) {
		unit = value.unit && token.units.indexOf( value.unit ) !== -1 ? value.unit : token.units[ 0 ];
	}
	const out: string[] = [];
	for ( const side of SIDES ) {
		if ( value[ side ] === undefined || value[ side ] === null ) {
			return '';
		}
		out.push( `${ parseInt( String( value[ side ] ), 10 ) || 0 }${ unit }` );
	}
	return out.join( ' ' );
}

/** The `[cssVar, value]` declarations a token contributes for one resolved value. Mirrors Compiler::declarations(). */
export function tokenDeclarations(
	token: Token,
	value: ScalarValue,
	themeFont: boolean
): Array<[ string, string ]> {
	if ( value === null || value === undefined ) {
		return [];
	}

	if ( token.type === 'fontstyle' && token.vars ) {
		const v = ( value || {} ) as Partial<FontStyleValue>;
		return [
			[ token.vars.weight, v.bold ? '700' : String( token.neutral_weight || '400' ) ],
			[ token.vars.style, v.italic ? 'italic' : 'normal' ],
			[ token.vars.decoration, v.underline ? 'underline' : 'none' ],
			[ token.vars.transform, v.uppercase ? 'uppercase' : 'none' ],
		];
	}

	if ( ! token.var ) {
		return [];
	}

	if ( token.key === 'fonts.family' && themeFont ) {
		return [ [ token.var, 'inherit' ] ];
	}

	const formatted = formatValue( token, value );
	if ( formatted === '' ) {
		return [];
	}
	return [ [ token.var, formatted ] ];
}
