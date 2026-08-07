/**
 * Style Customizer v2 — static UI maps.
 *
 * Section icons, human labels for state/variant ids, click-to-edit routing, device metadata.
 * The token/section DATA all comes from the server; these are pure presentation.
 */
import { Device } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );

export const clone = < T >( v: T ): T =>
	typeof structuredClone === 'function' ? structuredClone( v ) : JSON.parse( JSON.stringify( v ) );

/**
 * Structural value-equality for token values / device bags — order-independent for object keys
 * (so it doesn't depend on insertion order the way a JSON.stringify compare would). Used to test
 * whether a form's current styles EXACTLY equal a given template's (see store.appliedTemplateId).
 */
export function deepEqual( a: unknown, b: unknown ): boolean {
	if ( a === b ) {
		return true;
	}
	if ( a === null || b === null || typeof a !== 'object' || typeof b !== 'object' ) {
		return false;
	}
	const aArr = Array.isArray( a );
	if ( aArr !== Array.isArray( b ) ) {
		return false;
	}
	const ak = Object.keys( a as Record< string, unknown > );
	const bk = Object.keys( b as Record< string, unknown > );
	if ( ak.length !== bk.length ) {
		return false;
	}
	return ak.every(
		( k ) =>
			Object.prototype.hasOwnProperty.call( b, k ) &&
			deepEqual( ( a as Record< string, unknown > )[ k ], ( b as Record< string, unknown > )[ k ] )
	);
}

/** Section icon inner-SVG (rendered inside a 24×24 stroke viewBox). */
export const SECTION_ICONS: Record<string, string> = {
	form: '<rect x="3" y="3" width="18" height="18" rx="3"/>',
	text: '<path d="M4 7V5h16v2M9 5v14M9 19h6"/>',
	fields: '<rect x="3" y="5" width="18" height="6" rx="2"/><rect x="3" y="14" width="12" height="6" rx="2"/>',
	choice: '<circle cx="7" cy="12" r="3"/><rect x="14" y="9" width="6" height="6" rx="1.5"/>',
	button: '<rect x="3" y="8" width="18" height="9" rx="4"/>',
	messages: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
	pagination: '<circle cx="5" cy="12" r="2.5"/><circle cx="12" cy="12" r="2.5"/><circle cx="19" cy="12" r="2.5"/><path d="M7.5 12h2M14.5 12h2"/>',
};

/** One-line sub-label shown under each element row. */
export const SECTION_SUBTITLES: Record<string, string> = {
	form: __( 'Background, corners, font, width', 'everest-forms' ),
	text: __( 'Labels, sublabels, descriptions, section titles', 'everest-forms' ),
	fields: __( 'Text boxes, dropdowns, upload area', 'everest-forms' ),
	choice: __( 'Radio, checkbox, ratings & more', 'everest-forms' ),
	button: __( 'Submit & navigation buttons', 'everest-forms' ),
	messages: __( 'Success, error & validation banners', 'everest-forms' ),
	pagination: __( 'Progress indicator & part navigation', 'everest-forms' ),
};

/** Human labels for the variant/state ids the schema ships. */
export const STATE_LABELS: Record<string, string> = {
	label: 'Label',
	sublabel: 'Sublabel',
	desc: 'Description',
	title: 'Section Title',
	normal: 'Normal',
	focus: 'Focus',
	hover: 'Hover',
	success: 'Success',
	error: 'Error',
	validation: 'Validation',
};

/** Preview force-classes the bridge toggles on the iframe wrapper for non-"normal" states. */
export const STATE_FORCE: Record<string, string> = {
	focus: 'force-focus',
	hover: 'force-hover',
	success: 'force-msg-success',
	error: 'force-msg-error',
	validation: 'force-msg-validation',
};

export const ALL_FORCE_CLASSES = [
	'force-focus',
	'force-hover',
	'force-msg-success',
	'force-msg-error',
	'force-msg-validation',
];

export const DEVICE_LABELS: Record<Device, string> = {
	desktop: 'Desktop',
	tablet: 'Tablet',
	mobile: 'Mobile',
};

/** Preview frame widths per device. Tablet/mobile widths sit just below the breakpoints. */
export const DEVICE_WIDTHS: Record<Device, number> = {
	desktop: 760,
	tablet: 760,
	mobile: 390,
};

export const DEVICE_ICONS: Record<Device, string> = {
	desktop: '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8"/>',
	tablet: '<rect x="6" y="3" width="12" height="18" rx="2"/>',
	mobile: '<rect x="8" y="3" width="8" height="18" rx="2"/>',
};

export const ALIGN_ICONS: Record<string, string> = {
	left: '<path d="M4 6h16M4 12h10M4 18h13"/>',
	center: '<path d="M4 6h16M7 12h10M5 18h14"/>',
	right: '<path d="M4 6h16M10 12h10M7 18h13"/>',
};

/** Font-style buttons: [flag, glyph-html, title]. */
export const FONTSTYLE_BUTTONS: Array<[ keyof import('./types').FontStyleValue, string, string ]> = [
	[ 'bold', '<b>B</b>', 'Bold' ],
	[ 'italic', '<i>I</i>', 'Italic' ],
	[ 'underline', '<u>U</u>', 'Underline' ],
	[ 'uppercase', 'TT', 'Uppercase' ],
];

/** Click-to-edit resolver, ordered most-specific → least-specific; the bridge walks up from a
 *  clicked element to the first matching target. */
export interface PreviewTarget {
	match: string;
	section: string;
	variant?: string;
	label: string;
}

export const PREVIEW_TARGETS: PreviewTarget[] = [
	{
		match:
			'.evf-submit-container button, .evf-submit-container input[type="submit"], .everest-forms-multi-part-actions button, .everest-forms-multi-part-actions input[type="submit"], .everest-forms-part-button, .everest-forms-submit-button',
		section: 'button',
		label: 'Button',
	},
	{
		match: '.everest-forms-multi-part-indicator',
		section: 'pagination',
		label: 'Pagination',
	},
	{
		match: '.everest-forms-notice--success',
		section: 'messages',
		variant: 'success',
		label: 'Success message',
	},
	{
		match: '.everest-forms-notice--error',
		section: 'messages',
		variant: 'error',
		label: 'Error message',
	},
	{
		match: '.evf-error',
		section: 'messages',
		variant: 'validation',
		label: 'Validation message',
	},
	{ match: '.evf-field-title, .evf-field-title h3', section: 'text', variant: 'title', label: 'Section title' },
	{ match: '.evf-field-description', section: 'text', variant: 'desc', label: 'Description' },
	{ match: 'label.everest-forms-field-sublabel', section: 'text', variant: 'sublabel', label: 'Sublabel' },
	{ match: 'label.evf-field-label', section: 'text', variant: 'label', label: 'Label' },
	{ match: '.everest-forms-uploader', section: 'fields', label: 'Upload area' },
	{
		// Choice-type fields: radio, checkbox, image-choice, likert, ratings, payment pickers, yes/no, privacy policy, subscription plan.
		match:
			'.evf-field-radio, .evf-field-checkbox, .evf-field-payment-multiple, .evf-field-payment-checkbox, .evf-field-privacy-policy, .evf-field-image-choice, .evf-field-likert, .evf-field-rating, .evf-field-scale-rating, .evf-field-payment-single, .evf-field-yes-no, .evf-field-payment-subscription-plan',
		section: 'choice',
		label: 'Choice field',
	},
	{
		match:
			'input[type="text"], input[type="email"], input[type="url"], input[type="tel"], input[type="number"], input[type="password"], input[type="date"], input[type="time"], input[type="datetime-local"], textarea, select, .StripeElement, canvas.evf-signature-canvas',
		section: 'fields',
		label: 'Input field',
	},
	{ match: '.evf-field, .evf-frontend-row', section: 'fields', label: 'Field' },
	{ match: '.evf-container', section: 'form', label: 'Form container' },
];

/**
 * Best-effort #rrggbb for any CSS colour a "color" token might hold. Token values are usually
 * plain hex, but {@see Sanitizer::sanitize_color()} also accepts (and a couple of schema defaults,
 * e.g. `file.bg`, actually use) `rgba()` — kept for legacy v1 alpha borders/backgrounds. Neither the
 * native `<input type="color">` swatch nor a hex textbox understands rgba, so callers use this to
 * get something displayable instead of silently rendering black; alpha is dropped since there's no
 * alpha control here. Returns null only for a genuinely unparseable value (a CSS keyword/var).
 */
export function toDisplayHex( value: string ): string | null {
	const v = String( value ).trim();
	if ( /^#[0-9a-f]{3}$/i.test( v ) ) {
		return ( '#' + v.slice( 1 ).split( '' ).map( ( c ) => c + c ).join( '' ) ).toLowerCase();
	}
	if ( /^#[0-9a-f]{6}$/i.test( v ) ) {
		return v.toLowerCase();
	}
	if ( /^#[0-9a-f]{8}$/i.test( v ) ) {
		return v.slice( 0, 7 ).toLowerCase();
	}
	const m = v.match( /^rgba?\(\s*([\d.]+%?)\s*[,\s]+\s*([\d.]+%?)\s*[,\s]+\s*([\d.]+%?)/i );
	if ( m ) {
		const hex = m.slice( 1, 4 ).map( ( raw ) => {
			const num = parseFloat( raw );
			const c = Math.max( 0, Math.min( 255, Math.round( raw.endsWith( '%' ) ? num * 2.55 : num ) ) );
			return c.toString( 16 ).padStart( 2, '0' );
		} );
		return '#' + hex.join( '' );
	}
	return null;
}

/** Mix two hex colours by ratio t (0..1). Used to derive the button hover from a palette. */
export function mixHex( a: string, b: string, t: number ): string {
	const parse = ( h: string ): number[] => {
		let s = String( h ).replace( '#', '' );
		if ( s.length === 3 ) {
			s = s
				.split( '' )
				.map( ( c ) => c + c )
				.join( '' );
		}
		return [ parseInt( s.slice( 0, 2 ), 16 ), parseInt( s.slice( 2, 4 ), 16 ), parseInt( s.slice( 4, 6 ), 16 ) ];
	};
	const A = parse( a );
	const B = parse( b );
	return (
		'#' +
		A.map( ( x, i ) => Math.round( x + ( B[ i ] - x ) * t ).toString( 16 ).padStart( 2, '0' ) ).join( '' )
	);
}
