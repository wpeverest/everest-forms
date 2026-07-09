/**
 * Golden visual-regression harness — capture step.
 *
 * Screenshots every configured form at every breakpoint into a labelled folder.
 * Run it once for the BASELINE (before the CSS-variable refactor / a migration), then
 * again for CURRENT (after), then diff with compare.mjs.
 *
 * This is Phase 0's single highest-leverage safety net (STYLE-CUSTOMIZER-V2-PLAN.md §4):
 * it proves a legacy form re-rendered through the new pipeline is pixel-identical.
 *
 * Usage:
 *   npm i playwright pixelmatch pngjs
 *   node capture.mjs --label baseline --config forms.json
 *   # ...make the change / migrate...
 *   node capture.mjs --label current  --config forms.json
 *   node compare.mjs  --baseline baseline --current current
 *
 * forms.json shape:
 *   {
 *     "baseUrl": "https://evf.local",
 *     "cookie": "wordpress_logged_in_...=...",   // optional, for previewing drafts
 *     "breakpoints": { "desktop": 1280, "tablet": 768, "mobile": 480 },
 *     "forms": [ { "id": 7, "url": "/?evf_preview=1&form_id=7" }, ... ]
 *   }
 */

import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

function arg( name, fallback ) {
	const i = process.argv.indexOf( `--${ name }` );
	return i > -1 ? process.argv[ i + 1 ] : fallback;
}

const label      = arg( 'label', 'baseline' );
const configPath = arg( 'config', 'forms.json' );
const config     = JSON.parse( fs.readFileSync( configPath, 'utf8' ) );
const outDir     = path.join( 'screens', label );

fs.mkdirSync( outDir, { recursive: true } );

const breakpoints = config.breakpoints || { desktop: 1280, tablet: 768, mobile: 480 };

const browser = await chromium.launch();
const context = await browser.newContext( {
	deviceScaleFactor: 1, // CSS pixels — stable across machines.
} );

if ( config.cookie ) {
	const [ name, value ] = config.cookie.split( '=' );
	const { hostname } = new URL( config.baseUrl );
	await context.addCookies( [ { name, value, domain: hostname, path: '/' } ] );
}

let shots = 0;
for ( const form of config.forms ) {
	const url = config.baseUrl + form.url;
	for ( const [ device, width ] of Object.entries( breakpoints ) ) {
		const page = await context.newPage();
		await page.setViewportSize( { width: Number( width ), height: 900 } );
		await page.goto( url, { waitUntil: 'networkidle' } );
		// Freeze animations/carets so diffs stay deterministic.
		await page.addStyleTag( { content: '*,*::before,*::after{transition:none!important;animation:none!important;caret-color:transparent!important}' } );
		const target = ( await page.$( `#evf-${ form.id }` ) ) || page;
		await target.screenshot( { path: path.join( outDir, `form-${ form.id }-${ device }.png` ) } );
		await page.close();
		shots++;
	}
}

await browser.close();
console.log( `Captured ${ shots } screenshots into ${ outDir }` );
