/**
 * Migration audit — measurement step.
 *
 * Reads audit-spec.json (written by migration-audit.php), loads the page it built (source form
 * + migrated duplicate side by side), and for every check reads the REAL computed CSS value on
 * both, side by side. Reports a clear per-token pass/fail table, not just an aggregate score.
 *
 * Usage: node migration-audit.mjs
 */

import { chromium } from 'playwright';
import fs from 'node:fs';

const spec = JSON.parse( fs.readFileSync( 'audit-spec.json', 'utf8' ) );

const BOX4_SIDES = {
	margin: [ 'marginTop', 'marginRight', 'marginBottom', 'marginLeft' ],
	padding: [ 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft' ],
	'border-width': [ 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth' ],
	'border-radius': [ 'borderTopLeftRadius', 'borderTopRightRadius', 'borderBottomRightRadius', 'borderBottomLeftRadius' ],
};

const NUMERIC_TOLERANCE_PX = 0.5;

function parsePx( v ) {
	const m = /^(-?[\d.]+)px$/.exec( v );
	return m ? parseFloat( m[ 1 ] ) : null;
}

function valuesMatch( a, b ) {
	if ( a === b ) return true;
	const na = parsePx( a );
	const nb = parsePx( b );
	if ( na !== null && nb !== null ) return Math.abs( na - nb ) <= NUMERIC_TOLERANCE_PX;
	return false;
}

const browser = await chromium.launch();
const page = await browser.newPage();
await page.setViewportSize( { width: 1280, height: 900 } );
await page.goto( spec.baseUrl + spec.pageUrl, { waitUntil: 'networkidle' } );
await page.evaluate( () => document.fonts.ready );
await page.addStyleTag( { content: '*,*::before,*::after{transition:none!important;animation:none!important}' } );

const srcRoot = `#evf-${ spec.sourceId }`;
const dupRoot = `#evf-${ spec.dupId }`;

// Inject synthetic message markup so Messages tokens (never visible without a real submission)
// can still be measured — the CSS is purely class-selector driven, so a bare div with the right
// class is sufficient to read its computed style.
const MESSAGE_CLASSES = {
	'.everest-forms-notice--success': 'everest-forms-notice everest-forms-notice--success',
	'.everest-forms-notice--error': 'everest-forms-notice everest-forms-notice--error',
	'.evf-error': 'evf-error',
};
async function injectMessage( root, selector ) {
	const cls = MESSAGE_CLASSES[ selector ];
	if ( ! cls ) return;
	await page.evaluate( ( { root, cls, selector } ) => {
		const parent = document.querySelector( root );
		if ( ! parent || parent.querySelector( selector ) ) return;
		const div = document.createElement( 'div' );
		div.className = cls;
		div.textContent = 'audit probe';
		div.setAttribute( 'data-migration-audit', '1' );
		parent.appendChild( div );
	}, { root, cls, selector } );
}

const needsInject = spec.checks.some( ( c ) => c.inject );
if ( needsInject ) {
	for ( const selector of new Set( spec.checks.filter( ( c ) => c.inject ).map( ( c ) => c.selector ) ) ) {
		await injectMessage( srcRoot, selector );
		await injectMessage( dupRoot, selector );
	}
}

async function readProps( root, check ) {
	const selector = `${ root } ${ check.selector }`;
	const el = await page.$( selector );
	if ( ! el ) return { found: false };

	if ( check.pseudo === 'focus' ) await el.focus().catch( () => {} );
	if ( check.pseudo === 'hover' ) await el.hover().catch( () => {} );
	if ( check.pseudo === 'checked' ) await el.check().catch( () => {} );

	if ( check.kind === 'box4' ) {
		const sides = BOX4_SIDES[ check.family ];
		const values = await page.evaluate( ( { selector, sides } ) => {
			const el = document.querySelector( selector );
			const cs = getComputedStyle( el );
			return sides.map( ( s ) => cs[ s ] );
		}, { selector, sides } );
		return { found: true, values, labels: sides };
	}

	const pseudoElt = check.pseudo === 'placeholder' ? '::placeholder' : null;
	const value = await page.evaluate( ( { selector, property, pseudoElt } ) => {
		const el = document.querySelector( selector );
		const cs = getComputedStyle( el, pseudoElt );
		return cs[ property ];
	}, { selector, property: check.property, pseudoElt } );
	return { found: true, values: [ value ], labels: [ check.property ] };
}

const results = { pass: [], fail: [], skipped_no_element: [] };

for ( const check of spec.checks ) {
	const src = await readProps( srcRoot, check );
	const dup = await readProps( dupRoot, check );

	if ( ! src.found || ! dup.found ) {
		results.skipped_no_element.push( { token: check.token, reason: ! src.found ? 'element not found on SOURCE form' : 'element not found on DUPLICATE form', selector: check.selector } );
		continue;
	}

	const mismatches = [];
	src.values.forEach( ( v, i ) => {
		if ( ! valuesMatch( v, dup.values[ i ] ) ) {
			mismatches.push( { prop: src.labels[ i ], source: v, migrated: dup.values[ i ] } );
		}
	} );

	if ( mismatches.length ) {
		results.fail.push( { token: check.token, selector: check.selector, mismatches } );
	} else {
		results.pass.push( check.token );
	}
}

console.log( `\n=== Migration audit: form #${ spec.sourceId } -> duplicate #${ spec.dupId } ===` );
if ( spec.alreadyV2 === true ) console.log( 'NOTE: source is already v2 — comparing v2-vs-v2 (should be identical).' );
if ( spec.alreadyV2 === null ) console.log( 'NOTE: source was never styled — comparing default-vs-default (should be identical).' );

console.log( `\n${ results.pass.length } PASS, ${ results.fail.length } FAIL, ${ results.skipped_no_element.length } skipped (no matching element on this form), ${ spec.skipped.length } skipped (no coverage yet)\n` );

if ( results.fail.length ) {
	console.log( '--- FAILURES ---' );
	for ( const f of results.fail ) {
		console.log( `[FAIL] ${ f.token }  (${ f.selector })` );
		for ( const m of f.mismatches ) {
			console.log( `        ${ m.prop }: source="${ m.source }"  migrated="${ m.migrated }"` );
		}
	}
	console.log();
}

if ( results.skipped_no_element.length ) {
	console.log( '--- SKIPPED (element not present on this form) ---' );
	for ( const s of results.skipped_no_element ) {
		console.log( `  ${ s.token } — ${ s.reason } (${ s.selector })` );
	}
	console.log();
}

if ( spec.skipped.length ) {
	console.log( '--- NOT YET COVERED BY THIS TOOL ---' );
	for ( const s of spec.skipped ) {
		console.log( `  ${ s.token } — ${ s.reason }` );
	}
	console.log();
}

await browser.close();
process.exit( results.fail.length ? 1 : 0 );
