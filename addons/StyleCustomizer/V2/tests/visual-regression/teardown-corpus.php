<?php
/**
 * Golden VR harness — removes everything provision-corpus.php created: the duplicate form
 * posts/postmeta, their style records, and the shared comparison page. Leaves the real source
 * forms and every other option untouched. Safe to run even if provisioning partially failed.
 */

$DB_HOST = getenv( 'VR_DB_HOST' ) ?: '127.0.0.1';
$DB_PORT = (int) ( getenv( 'VR_DB_PORT' ) ?: 10024 );
$DB_USER = getenv( 'VR_DB_USER' ) ?: 'root';
$DB_PASS = getenv( 'VR_DB_PASS' ) ?: 'root';
$DB_NAME = getenv( 'VR_DB_NAME' ) ?: 'local';

$manifestPath = __DIR__ . '/corpus-manifest.json';
if ( ! file_exists( $manifestPath ) ) {
	echo "No corpus-manifest.json — nothing to tear down.\n";
	exit( 0 );
}
$manifest = json_decode( file_get_contents( $manifestPath ), true );

$m = new mysqli( $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT );
if ( $m->connect_errno ) {
	fwrite( STDERR, "DB connect failed: {$m->connect_error}\n" );
	exit( 1 );
}

$dup_ids = array_map( static fn( $r ) => (int) $r['dup'], $manifest['forms'] ?? array() );
$ids     = array_merge( $dup_ids, array( (int) ( $manifest['page_id'] ?? 0 ) ) );
$ids     = array_filter( $ids );

if ( $ids ) {
	$in = implode( ',', $ids );
	$m->query( "DELETE FROM wp_postmeta WHERE post_id IN ($in)" );
	$m->query( "DELETE FROM wp_posts WHERE ID IN ($in)" );
}

$row = $m->query( "SELECT option_value FROM wp_options WHERE option_name='everest_forms_styles'" )->fetch_assoc();
$all = unserialize( $row['option_value'] );
foreach ( $dup_ids as $id ) {
	unset( $all[ $id ] );
}
$ser = $m->real_escape_string( serialize( $all ) );
$m->query( "UPDATE wp_options SET option_value='$ser' WHERE option_name='everest_forms_styles'" ) or die( $m->error );

echo 'Removed ' . count( $ids ) . " post(s) and their style records.\n";
echo 'Remaining styled form ids: ' . implode( ', ', array_keys( $all ) ) . "\n";

unlink( $manifestPath );
@unlink( __DIR__ . '/forms.json' );
$m->close();
