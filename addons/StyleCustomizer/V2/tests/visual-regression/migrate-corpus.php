<?php
/**
 * Golden VR harness — migrates every duplicate form in corpus-manifest.json (written by
 * provision-corpus.php) from its legacy record to a v2 record in place, using the SAME
 * Migrator the product uses. Run this between the "baseline" and "current" capture.mjs runs.
 *
 * Also remember to enable the `EVF_STYLE_V2` engine flag before the "current" capture — this
 * script only migrates the DATA; FrontendEnqueue only serves the v2 pipeline when the flag is
 * on (see Engine::enabled()).
 */

$DB_HOST = getenv( 'VR_DB_HOST' ) ?: '127.0.0.1';
$DB_PORT = (int) ( getenv( 'VR_DB_PORT' ) ?: 10024 );
$DB_USER = getenv( 'VR_DB_USER' ) ?: 'root';
$DB_PASS = getenv( 'VR_DB_PASS' ) ?: 'root';
$DB_NAME = getenv( 'VR_DB_NAME' ) ?: 'local';
$MIGRATOR_DIR = getenv( 'VR_MIGRATOR_DIR' ) ?: dirname( __DIR__, 2 );

define( 'ABSPATH', __DIR__ );
function __( $s, $d = null ) { return $s; }
function apply_filters( $t, $v ) { return 'evf_style_v2_pro_active' === $t ? true : $v; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_url_raw( $u ) { return (string) $u; }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }

require "$MIGRATOR_DIR/Schema.php";
require "$MIGRATOR_DIR/Sanitizer.php";
require "$MIGRATOR_DIR/Compiler.php";
require "$MIGRATOR_DIR/Migrator.php";
require "$MIGRATOR_DIR/Engine.php";

use EverestForms\Addons\StyleCustomizer\V2\Migrator;

$manifest = json_decode( file_get_contents( __DIR__ . '/corpus-manifest.json' ), true );
if ( ! $manifest ) {
	fwrite( STDERR, "corpus-manifest.json missing/invalid — run provision-corpus.php first.\n" );
	exit( 1 );
}

$m = new mysqli( $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT );
if ( $m->connect_errno ) {
	fwrite( STDERR, "DB connect failed: {$m->connect_error}\n" );
	exit( 1 );
}

$row = $m->query( "SELECT option_value FROM wp_options WHERE option_name='everest_forms_styles'" )->fetch_assoc();
$all = unserialize( $row['option_value'] );

foreach ( $manifest['forms'] as $r ) {
	$dup_id = $r['dup'];
	$legacy = $all[ $dup_id ] ?? array();
	$v2     = Migrator::migrate_record( $legacy );
	$all[ $dup_id ] = $v2;
	echo "migrated dup #$dup_id: " . count( $v2['tokens'] ) . " tokens\n";
}

$ser = $m->real_escape_string( serialize( $all ) );
$m->query( "UPDATE wp_options SET option_value='$ser' WHERE option_name='everest_forms_styles'" ) or die( $m->error );

echo "Done. Enable EVF_STYLE_V2 now, then run: node capture.mjs --label current --config forms.json\n";
$m->close();
