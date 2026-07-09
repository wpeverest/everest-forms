<?php
/**
 * Golden VR harness — corpus provisioning helper.
 *
 * Builds an EPHEMERAL migration-fidelity corpus from real styled forms on a dev site, without
 * ever mutating the real forms: for each source form id, duplicates it (post + postmeta), then
 * migrates the DUPLICATE's style record to v2 (Migrator::migrate_record()) and stores it under
 * the duplicate's id. The duplicate renders through the legacy v1 engine until `EVF_STYLE_V2`
 * is enabled — so `capture.mjs --label baseline` (flag off) captures the true "before", and
 * `capture.mjs --label current` (flag on) captures the "after", on data with a real, known-good
 * "before" to diff against.
 *
 * Deliberately NOT run automatically / NOT wired into CI as-is: it writes directly to the WP
 * database via mysqli (no WP bootstrap — this project's plugin stack times out under `wp eval`,
 * see the project's local-wpcli memory note) and is meant to be run against a disposable dev/CI
 * database, then torn down with teardown-corpus.php. Never point SRC_FORM_IDS at production.
 *
 * Usage:
 *   php provision-corpus.php   # prints forms.json to stdout AND writes it to forms.json
 *   node capture.mjs --label baseline --config forms.json
 *   php -r "require 'toggle-engine-flag.php'; enable();"   # or your own mu-plugin toggle
 *   node capture.mjs --label current --config forms.json
 *   node compare.mjs --baseline baseline --current current
 *   php teardown-corpus.php
 *
 * Configure via environment variables (all have dev-site defaults below).
 */

$DB_HOST      = getenv( 'VR_DB_HOST' ) ?: '127.0.0.1';
$DB_PORT      = (int) ( getenv( 'VR_DB_PORT' ) ?: 10024 );
$DB_USER      = getenv( 'VR_DB_USER' ) ?: 'root';
$DB_PASS      = getenv( 'VR_DB_PASS' ) ?: 'root';
$DB_NAME      = getenv( 'VR_DB_NAME' ) ?: 'local';
$BASE_URL     = getenv( 'VR_BASE_URL' ) ?: 'http://evf.local';
$SRC_FORM_IDS = array_filter( array_map( 'trim', explode( ',', getenv( 'VR_SRC_FORM_IDS' ) ?: '1223,1225,1229' ) ) );
$MIGRATOR_DIR = getenv( 'VR_MIGRATOR_DIR' ) ?: dirname( __DIR__, 2 ); // .../V2

$m = new mysqli( $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT );
if ( $m->connect_errno ) {
	fwrite( STDERR, "DB connect failed: {$m->connect_error}\n" );
	exit( 1 );
}

// Minimal WP-function stubs so the engine classes load standalone (same recipe as
// tests/test-style-v2.php). pro_active is forced true so migration keeps every real value —
// the corpus is about migration fidelity, not the tier split (that's covered by test-style-v2.php).
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

function dup_form( mysqli $m, int $src_id, string $slug ): int {
	$m->query( "DELETE FROM wp_posts WHERE post_name='" . $m->real_escape_string( $slug ) . "'" );
	$ok = $m->query(
		"INSERT INTO wp_posts (post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,post_status,comment_status,ping_status,post_password,post_name,to_ping,pinged,post_modified,post_modified_gmt,post_content_filtered,post_parent,guid,menu_order,post_type,post_mime_type,comment_count)
		 SELECT post_author,NOW(),NOW(),post_content,'VR Corpus $slug','','publish','closed','closed','','$slug','','',NOW(),NOW(),'',0,'',0,'everest_form','',0
		 FROM wp_posts WHERE ID=$src_id"
	);
	if ( ! $ok ) {
		throw new RuntimeException( "duplicate failed for $src_id: {$m->error}" );
	}
	$new_id = $m->insert_id;
	$row    = $m->query( "SELECT post_content FROM wp_posts WHERE ID=$new_id" )->fetch_assoc();
	$content = preg_replace( '/"id":"' . $src_id . '"/', '"id":"' . $new_id . '"', $row['post_content'], 1 );
	$m->query( "UPDATE wp_posts SET post_content='" . $m->real_escape_string( $content ) . "', guid='" . $m->real_escape_string( "?post_type=everest_form&p=$new_id" ) . "' WHERE ID=$new_id" );
	$m->query( "INSERT INTO wp_postmeta (post_id,meta_key,meta_value) SELECT $new_id,meta_key,meta_value FROM wp_postmeta WHERE post_id=$src_id" );
	return $new_id;
}

$row = $m->query( "SELECT option_value FROM wp_options WHERE option_name='everest_forms_styles'" )->fetch_assoc();
$all = unserialize( $row['option_value'] );

$manifest = array();
$dup_ids  = array();
foreach ( $SRC_FORM_IDS as $src_id ) {
	$src_id = (int) $src_id;
	$slug   = "zz-vr-corpus-src-$src_id";
	$dup_id = dup_form( $m, $src_id, $slug );
	$dup_ids[] = $dup_id;

	// Baseline record = an EXACT copy of the source's current (legacy) record — renders
	// identically to the source until migrated below, so capturing it now with the flag off
	// gives the "before" that the "after" (same id, post-migration) must match.
	$all[ $dup_id ] = $all[ $src_id ] ?? array();

	$manifest[] = array( 'src' => $src_id, 'dup' => $dup_id, 'slug' => $slug );
}

$ser = $m->real_escape_string( serialize( $all ) );
$m->query( "UPDATE wp_options SET option_value='$ser' WHERE option_name='everest_forms_styles'" ) or die( $m->error );

// One shared page embedding every SOURCE (real, already has a legacy-engine compiled CSS
// file on disk from whenever it was actually styled) AND every duplicate (starts as an exact
// copy of the source's option row, but — critically — has NO compiled CSS file of its own,
// since it never went through a real Customizer save; only a v2 record renders correctly for
// it, because v2 compiles inline on every request instead of reading a static file). This is
// why baseline must screenshot the SOURCE id and current must screenshot the DUPLICATE id
// (post-migration) — comparing a duplicate's before/after would just be "unstyled vs styled".
$shortcodes  = implode( '', array_map( static fn( $r ) => '[everest_form id="' . $r['src'] . '"]', $manifest ) );
$shortcodes .= implode( '', array_map( static fn( $r ) => '[everest_form id="' . $r['dup'] . '"]', $manifest ) );
$m->query( "DELETE FROM wp_posts WHERE post_name='zz-vr-corpus-page'" );
$m->query(
	"INSERT INTO wp_posts (post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,post_status,comment_status,ping_status,post_password,post_name,to_ping,pinged,post_modified,post_modified_gmt,post_content_filtered,post_parent,guid,menu_order,post_type,post_mime_type,comment_count)
	 VALUES (1,NOW(),NOW(),'" . $m->real_escape_string( $shortcodes ) . "','VR Corpus Page','','publish','closed','closed','','zz-vr-corpus-page','','',NOW(),NOW(),'',0,'',0,'page','',0)"
) or die( $m->error );
$page_id = $m->insert_id;

file_put_contents( __DIR__ . '/corpus-manifest.json', json_encode( array( 'page_id' => $page_id, 'forms' => $manifest ), JSON_PRETTY_PRINT ) );

$breakpoints = array( 'desktop' => 1280, 'tablet' => 768, 'mobile' => 480 );

// baseline: screenshot the SOURCE id (real legacy CSS file, untouched, engine flag irrelevant).
file_put_contents(
	__DIR__ . '/forms.baseline.json',
	json_encode(
		array(
			'baseUrl'     => $BASE_URL,
			'breakpoints' => $breakpoints,
			'forms'       => array_map( static fn( $r ) => array( 'id' => $r['src'], 'file' => $r['src'], 'url' => "/?page_id=$page_id" ), $manifest ),
		),
		JSON_PRETTY_PRINT
	)
);

// current: screenshot the DUPLICATE id (after migrate-corpus.php + enabling EVF_STYLE_V2), but
// name the output file after the SOURCE id so compare.mjs pairs it with the right baseline shot.
file_put_contents(
	__DIR__ . '/forms.current.json',
	json_encode(
		array(
			'baseUrl'     => $BASE_URL,
			'breakpoints' => $breakpoints,
			'forms'       => array_map( static fn( $r ) => array( 'id' => $r['dup'], 'file' => $r['src'], 'url' => "/?page_id=$page_id" ), $manifest ),
		),
		JSON_PRETTY_PRINT
	)
);

echo "Provisioned page #$page_id with " . count( $manifest ) . " source+duplicate pair(s):\n";
foreach ( $manifest as $r ) {
	echo "  src #{$r['src']} -> dup #{$r['dup']}\n";
}
echo "Wrote forms.baseline.json + forms.current.json + corpus-manifest.json.\n";
echo "Next: node capture.mjs --label baseline --config forms.baseline.json\n";
echo "      php migrate-corpus.php && enable EVF_STYLE_V2\n";
echo "      node capture.mjs --label current --config forms.current.json\n";

$m->close();
