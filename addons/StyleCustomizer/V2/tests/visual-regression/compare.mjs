/**
 * Golden visual-regression harness — compare step.
 *
 * Diffs every screenshot in --current against --baseline and fails if any form drifts
 * more than the threshold (default 0.5%, the Phase 0 exit criterion). Writes a *.diff.png
 * highlighting changed pixels for anything over threshold.
 *
 * Usage:
 *   node compare.mjs --baseline baseline --current current [--threshold 0.005]
 */

import fs from 'node:fs';
import path from 'node:path';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

function arg( name, fallback ) {
	const i = process.argv.indexOf( `--${ name }` );
	return i > -1 ? process.argv[ i + 1 ] : fallback;
}

const baseDir   = path.join( 'screens', arg( 'baseline', 'baseline' ) );
const curDir    = path.join( 'screens', arg( 'current', 'current' ) );
const threshold = Number( arg( 'threshold', '0.005' ) ); // 0.5%
const diffDir   = path.join( 'screens', 'diff' );
fs.mkdirSync( diffDir, { recursive: true } );

const files = fs.readdirSync( baseDir ).filter( ( f ) => f.endsWith( '.png' ) );
let worst = 0;
let failures = 0;

for ( const file of files ) {
	const basePath = path.join( baseDir, file );
	const curPath  = path.join( curDir, file );
	if ( ! fs.existsSync( curPath ) ) {
		console.log( `MISSING current: ${ file }` );
		failures++;
		continue;
	}
	const a = PNG.sync.read( fs.readFileSync( basePath ) );
	const b = PNG.sync.read( fs.readFileSync( curPath ) );
	if ( a.width !== b.width || a.height !== b.height ) {
		console.log( `SIZE MISMATCH ${ file }: ${ a.width }x${ a.height } vs ${ b.width }x${ b.height }` );
		failures++;
		continue;
	}
	const diff    = new PNG( { width: a.width, height: a.height } );
	const changed = pixelmatch( a.data, b.data, diff.data, a.width, a.height, { threshold: 0.1 } );
	const ratio   = changed / ( a.width * a.height );
	worst = Math.max( worst, ratio );
	const flag = ratio > threshold ? 'FAIL' : 'ok';
	if ( ratio > threshold ) {
		fs.writeFileSync( path.join( diffDir, file.replace( '.png', '.diff.png' ) ), PNG.sync.write( diff ) );
		failures++;
	}
	console.log( `[${ flag }] ${ file } — ${ ( ratio * 100 ).toFixed( 3 ) }% changed` );
}

console.log( `\nworst drift: ${ ( worst * 100 ).toFixed( 3 ) }%  ·  threshold: ${ ( threshold * 100 ).toFixed( 1 ) }%  ·  failures: ${ failures }` );
process.exit( failures ? 1 : 0 );
