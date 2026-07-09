<?php
/**
 * Migration audit — setup step.
 *
 * Given ONE real form id, builds an exhaustive, per-token pass/fail report of whether that
 * form's legacy (v1) rendering and its migrated (v2) rendering agree — checking every schema
 * token's actual CSS property via getComputedStyle, not just an aggregate pixel diff. This is
 * the general-purpose "run it on any form" migration checker (companion to the pixel-diff VR
 * harness, which answers "does it look the same overall"; this answers "which SPECIFIC setting,
 * if any, doesn't match").
 *
 * Never touches the real form: duplicates it (post + postmeta + style record), migrates ONLY the
 * duplicate, and leaves the original untouched. Run teardown-corpus.php afterward to clean up.
 *
 * Usage:
 *   php migration-audit.php --form=1223
 *   node migration-audit.mjs
 *   php teardown-corpus.php
 */

$form_id = null;
foreach ( $argv as $arg ) {
	if ( preg_match( '/^--form=(\d+)$/', $arg, $m ) ) {
		$form_id = (int) $m[1];
	}
}
if ( ! $form_id ) {
	fwrite( STDERR, "Usage: php migration-audit.php --form=<id>\n" );
	exit( 1 );
}

$DB_HOST      = getenv( 'VR_DB_HOST' ) ?: '127.0.0.1';
$DB_PORT      = (int) ( getenv( 'VR_DB_PORT' ) ?: 10024 );
$DB_USER      = getenv( 'VR_DB_USER' ) ?: 'root';
$DB_PASS      = getenv( 'VR_DB_PASS' ) ?: 'root';
$DB_NAME      = getenv( 'VR_DB_NAME' ) ?: 'local';
$BASE_URL     = getenv( 'VR_BASE_URL' ) ?: 'http://evf.local';
$MIGRATOR_DIR = getenv( 'VR_MIGRATOR_DIR' ) ?: dirname( __DIR__, 2 );

define( 'ABSPATH', __DIR__ );
function __( $s, $d = null ) { return $s; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_url_raw( $u ) { return (string) $u; }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }

// Minimal real filter registry (not just a passthrough stub) so Pro's Schema::register() can
// actually layer in the Messages tokens via evf_style_schema/evf_style_palettes — without this,
// Schema::tokens() never includes msg.* at all and message-weight/color fixes go untested.
$GLOBALS['__filters'] = array();
function add_filter( $tag, $cb, $priority = 10 ) { $GLOBALS['__filters'][ $tag ][] = $cb; }
function apply_filters( $tag, $value ) {
	if ( 'evf_style_v2_pro_active' === $tag ) {
		return true;
	}
	foreach ( $GLOBALS['__filters'][ $tag ] ?? array() as $cb ) {
		$value = $cb( $value );
	}
	return $value;
}

require "$MIGRATOR_DIR/Schema.php";
require "$MIGRATOR_DIR/Sanitizer.php";
require "$MIGRATOR_DIR/Compiler.php";
require "$MIGRATOR_DIR/Migrator.php";
require "$MIGRATOR_DIR/Engine.php";

// Layer in Pro's Messages tokens (msg.*) the same way a real Pro-active site does, so this tool
// can actually check message-weight/colour — otherwise Schema::tokens() never includes them.
$pro_schema = getenv( 'VR_PRO_SCHEMA' ) ?: dirname( $MIGRATOR_DIR, 4 ) . '/everest-forms-pro/src/StyleCustomizer/Schema.php';
if ( is_readable( $pro_schema ) ) {
	require $pro_schema;
	\EverestForms\Pro\StyleCustomizer\Schema::register();
}

use EverestForms\Addons\StyleCustomizer\V2\Schema;
use EverestForms\Addons\StyleCustomizer\V2\Migrator;

/* --------------------------------------------------------------------- *
 * (section, state) -> where to find the real element + how to trigger its state.
 * Extracted directly from assets/css/frontend.css — keep in sync with it.
 * --------------------------------------------------------------------- */
$SELECTORS = array(
	'form|'            => array( 'selector' => '.evf-container', 'pseudo' => null ),
	'text|label'       => array( 'selector' => 'label.evf-field-label', 'pseudo' => null ),
	'text|sublabel'    => array( 'selector' => 'label.everest-forms-field-sublabel', 'pseudo' => null ),
	'text|desc'        => array( 'selector' => '.evf-field-description', 'pseudo' => null ),
	'text|title'       => array( 'selector' => '.evf-field-title h3', 'pseudo' => null ),
	'fields|'          => array( 'selector' => "input[type='text']", 'pseudo' => null ),
	'fields|focus'     => array( 'selector' => "input[type='text']", 'pseudo' => 'focus' ),
	// 'choice' is heterogeneous (3 different real elements share the section) — every one of
	// its tokens is overridden per-key below instead of using this generic fallback.
	'choice|'          => array( 'selector' => '.evf-field-radio ul li', 'pseudo' => null ),
	'button|'          => array( 'selector' => ".evf-submit-container button[type='submit']", 'pseudo' => null ),
	'button|hover'     => array( 'selector' => ".evf-submit-container button[type='submit']", 'pseudo' => 'hover' ),
	'messages|success' => array( 'selector' => '.everest-forms-notice--success', 'pseudo' => null, 'inject' => true ),
	'messages|error'   => array( 'selector' => '.everest-forms-notice--error', 'pseudo' => null, 'inject' => true ),
	'messages|validation' => array( 'selector' => '.evf-error', 'pseudo' => null, 'inject' => true ),
);

// Per-token-key overrides — take precedence over the (section,state) lookup above. Needed for
// tokens whose real element/state differs from the rest of their section (choice's 3 different
// sub-elements; choice.checked's :checked state).
$KEY_OVERRIDES = array(
	'choice.align'   => array( 'selector' => '.evf-field-radio ul li', 'pseudo' => null ),
	'choice.margin'  => array( 'selector' => '.evf-field-radio ul li', 'pseudo' => null ),
	'choice.size'    => array( 'selector' => '.evf-field-radio ul li input[type="radio"]', 'pseudo' => null ),
	'choice.border'  => array( 'selector' => '.evf-field-radio ul li input[type="radio"]', 'pseudo' => null ),
	'choice.checked' => array( 'selector' => '.evf-field-radio ul li input[type="radio"]', 'pseudo' => 'checked' ),
	'choice.fsize'   => array( 'selector' => '.evf-field-radio ul li input[type="radio"] + label', 'pseudo' => null ),
	'choice.color'   => array( 'selector' => '.evf-field-radio ul li input[type="radio"] + label', 'pseudo' => null ),
	// file.* tokens style the upload widget, NOT the generic text input the 'fields' section
	// otherwise maps to — without this override they'd silently measure the wrong element.
	'file.bg'          => array( 'selector' => '.everest-forms-uploader', 'pseudo' => null ),
	'file.borderStyle' => array( 'selector' => '.everest-forms-uploader', 'pseudo' => null ),
	'file.bw'          => array( 'selector' => '.everest-forms-uploader', 'pseudo' => null ),
	'file.border'      => array( 'selector' => '.everest-forms-uploader', 'pseudo' => null ),
	'file.radius'      => array( 'selector' => '.everest-forms-uploader', 'pseudo' => null ),
	'file.margin'      => array( 'selector' => '.everest-forms-uploader', 'pseudo' => null ),
	'file.pad'         => array( 'selector' => '.everest-forms-uploader', 'pseudo' => null ),
	'file.size'        => array( 'selector' => '.everest-forms-uploader .everest-forms-upload-title', 'pseudo' => null ),
	'file.color'       => array( 'selector' => '.everest-forms-uploader .everest-forms-upload-title', 'pseudo' => null ),
	'file.iconBg'      => array( 'selector' => '.everest-forms-uploader .dz-message > svg', 'pseudo' => null ),
	'file.icon'        => array( 'selector' => '.everest-forms-uploader .dz-message > svg', 'pseudo' => null ),
);

/* --------------------------------------------------------------------- *
 * CSS var suffix -> computed-style property (or properties). Extracted directly from
 * frontend.css. `box4` expands to the 4 longhand sides at compare time (in the .mjs).
 * --------------------------------------------------------------------- */
$VAR_PROPERTY = array(
	'-width'            => 'width',
	'-margin'           => array( 'kind' => 'box4', 'family' => 'margin' ),
	'-pad'              => array( 'kind' => 'box4', 'family' => 'padding' ),
	'-bg-size'          => 'backgroundSize',
	'-bg-position'      => 'backgroundPosition',
	'-bg-repeat'        => 'backgroundRepeat',
	'-bg-attachment'    => 'backgroundAttachment',
	'-bg'               => 'backgroundColor',
	'-border-style'     => 'borderStyle',
	'-bw'               => array( 'kind' => 'box4', 'family' => 'border-width' ),
	'-border-c-hover'   => 'borderColor',
	'-border-c'         => 'borderColor',
	'-border'           => 'borderColor', // file.border (no -c suffix)
	'-radius'           => array( 'kind' => 'box4', 'family' => 'border-radius' ),
	'-lh'               => 'lineHeight',
	'-align'            => 'textAlign',
	'-weight'           => 'fontWeight',
	'-fs'               => 'fontStyle',
	'-td'               => 'textDecorationLine',
	'-tt'               => 'textTransform',
	'-color-hover'      => 'color',
	'-color'            => 'color',
	'-size'             => 'fontSize',
	'-fsize'            => 'fontSize',
	'-bg-hover'         => 'backgroundColor',
	'-image'            => null, // media token — not a comparable computed property, skip.
);
// Overrides for vars that don't follow the generic suffix rule.
$VAR_OVERRIDE = array(
	'--evf-choice-size'        => array( 'width', 'height' ),
	'--evf-input-ph'           => array( 'property' => 'color', 'pseudo' => 'placeholder' ),
	'--evf-input-focus-border' => array( 'property' => 'borderColor', 'pseudo' => 'focus' ),
	'--evf-file-iconbg'        => array( 'property' => 'backgroundColor', 'pseudo' => null ),
	'--evf-file-icon'          => array( 'property' => 'fill', 'pseudo' => null ),
);

function resolve_property( $var, $VAR_PROPERTY ) {
	// Longest-suffix-first so `-border-c-hover` doesn't get caught by the shorter `-color` etc.
	$suffixes = array_keys( $VAR_PROPERTY );
	usort( $suffixes, static fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
	foreach ( $suffixes as $suffix ) {
		if ( substr( $var, -strlen( $suffix ) ) === $suffix ) {
			return $VAR_PROPERTY[ $suffix ];
		}
	}
	return null;
}

$m = new mysqli( $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT );
if ( $m->connect_errno ) {
	fwrite( STDERR, "DB connect failed: {$m->connect_error}\n" );
	exit( 1 );
}

$post = $m->query( "SELECT post_type, post_status FROM wp_posts WHERE ID=$form_id" )->fetch_assoc();
if ( ! $post || 'everest_form' !== $post['post_type'] ) {
	fwrite( STDERR, "Form #$form_id not found.\n" );
	exit( 1 );
}

// Duplicate (never mutate the real form).
$slug = "zz-migration-audit-$form_id";
$m->query( "DELETE FROM wp_posts WHERE post_name='" . $m->real_escape_string( $slug ) . "'" );
$m->query(
	"INSERT INTO wp_posts (post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,post_status,comment_status,ping_status,post_password,post_name,to_ping,pinged,post_modified,post_modified_gmt,post_content_filtered,post_parent,guid,menu_order,post_type,post_mime_type,comment_count)
	 SELECT post_author,NOW(),NOW(),post_content,'ZZ Migration Audit $form_id','','publish','closed','closed','','$slug','','',NOW(),NOW(),'',0,'',0,'everest_form','',0
	 FROM wp_posts WHERE ID=$form_id"
) or die( $m->error );
$dup_id = $m->insert_id;
$row    = $m->query( "SELECT post_content FROM wp_posts WHERE ID=$dup_id" )->fetch_assoc();
$content = preg_replace( '/"id":"' . $form_id . '"/', '"id":"' . $dup_id . '"', $row['post_content'], 1 );
$m->query( "UPDATE wp_posts SET post_content='" . $m->real_escape_string( $content ) . "', guid='?post_type=everest_form&p=$dup_id' WHERE ID=$dup_id" );
$m->query( "INSERT INTO wp_postmeta (post_id,meta_key,meta_value) SELECT $dup_id,meta_key,meta_value FROM wp_postmeta WHERE post_id=$form_id" );

// Migrate the DUPLICATE's record to v2 (source form's record is never touched).
$row = $m->query( "SELECT option_value FROM wp_options WHERE option_name='everest_forms_styles'" )->fetch_assoc();
$all = unserialize( $row['option_value'] );
if ( isset( $all[ $form_id ] ) && ! isset( $all[ $form_id ]['schema_version'] ) ) {
	$legacy         = $all[ $form_id ];
	$all[ $dup_id ] = Migrator::migrate_record( $legacy );
	$already_v2     = false;
} elseif ( isset( $all[ $form_id ]['schema_version'] ) ) {
	// Source is already v2 — nothing to migrate/compare; still proceed so the tool reports that.
	$all[ $dup_id ] = $all[ $form_id ];
	$already_v2     = true;
} else {
	$all[ $dup_id ] = array();
	$already_v2     = null; // never styled.
}
$ser = $m->real_escape_string( serialize( $all ) );
$m->query( "UPDATE wp_options SET option_value='$ser' WHERE option_name='everest_forms_styles'" ) or die( $m->error );

// One page embedding both the source and the duplicate.
$page_slug = "zz-migration-audit-page-$form_id";
$m->query( "DELETE FROM wp_posts WHERE post_name='" . $m->real_escape_string( $page_slug ) . "'" );
$page_content = "[everest_form id=\"$form_id\"][everest_form id=\"$dup_id\"]";
$m->query(
	"INSERT INTO wp_posts (post_author,post_date,post_date_gmt,post_content,post_title,post_excerpt,post_status,comment_status,ping_status,post_password,post_name,to_ping,pinged,post_modified,post_modified_gmt,post_content_filtered,post_parent,guid,menu_order,post_type,post_mime_type,comment_count)
	 VALUES (1,NOW(),NOW(),'" . $m->real_escape_string( $page_content ) . "','ZZ Migration Audit Page','','publish','closed','closed','','" . $m->real_escape_string( $page_slug ) . "','','',NOW(),NOW(),'',0,'',0,'page','',0)"
) or die( $m->error );
$page_id = $m->insert_id;
$m->query( "UPDATE wp_posts SET guid='?page_id=$page_id' WHERE ID=$page_id" );

// corpus-manifest.json — reuses teardown-corpus.php unchanged.
file_put_contents(
	__DIR__ . '/corpus-manifest.json',
	json_encode( array( 'page_id' => $page_id, 'forms' => array( array( 'src' => $form_id, 'dup' => $dup_id, 'slug' => $slug ) ) ), JSON_PRETTY_PRINT )
);

// Build the exhaustive per-token check list from the REAL, live schema.
$checks = array();
$skipped = array();
foreach ( Schema::tokens() as $token ) {
	if ( isset( $KEY_OVERRIDES[ $token['key'] ] ) ) {
		$loc = $KEY_OVERRIDES[ $token['key'] ];
	} else {
		$section   = $token['section'];
		$state     = $token['state'] ?? '';
		$lookupKey = "$section|$state";
		if ( ! isset( $SELECTORS[ $lookupKey ] ) ) {
			$skipped[] = array( 'token' => $token['key'], 'reason' => "no selector mapping for section=$section state=$state" );
			continue;
		}
		$loc = $SELECTORS[ $lookupKey ];
	}

	if ( 'fontstyle' === $token['type'] ) {
		foreach ( $token['vars'] as $sub => $var ) {
			$prop = resolve_property( $var, $VAR_PROPERTY );
			if ( ! $prop ) {
				continue;
			}
			$checks[] = array( 'token' => $token['key'] . ".$sub", 'selector' => $loc['selector'], 'pseudo' => $loc['pseudo'] ?? null, 'inject' => $loc['inject'] ?? false, 'kind' => 'single', 'property' => $prop, 'var' => $var );
		}
		continue;
	}

	if ( empty( $token['var'] ) ) {
		$skipped[] = array( 'token' => $token['key'], 'reason' => 'no CSS var (meta-only token)' );
		continue;
	}

	$override = $VAR_OVERRIDE[ $token['var'] ] ?? null;
	if ( isset( $override['property'] ) ) {
		$checks[] = array( 'token' => $token['key'], 'selector' => $loc['selector'], 'pseudo' => $override['pseudo'], 'inject' => $loc['inject'] ?? false, 'kind' => 'single', 'property' => $override['property'], 'var' => $token['var'] );
		continue;
	}
	if ( is_array( $override ) ) { // choice.size -> [width, height]
		foreach ( $override as $prop ) {
			$checks[] = array( 'token' => $token['key'] . ".$prop", 'selector' => $loc['selector'], 'pseudo' => $loc['pseudo'] ?? null, 'inject' => $loc['inject'] ?? false, 'kind' => 'single', 'property' => $prop, 'var' => $token['var'] );
		}
		continue;
	}

	$prop = resolve_property( $token['var'], $VAR_PROPERTY );
	if ( null === $prop ) {
		$skipped[] = array( 'token' => $token['key'], 'reason' => "var '{$token['var']}' has no mapped property (likely a media/background-image token)" );
		continue;
	}
	if ( is_array( $prop ) && 'box4' === ( $prop['kind'] ?? '' ) ) {
		$checks[] = array( 'token' => $token['key'], 'selector' => $loc['selector'], 'pseudo' => $loc['pseudo'] ?? null, 'inject' => $loc['inject'] ?? false, 'kind' => 'box4', 'family' => $prop['family'], 'var' => $token['var'] );
		continue;
	}
	$checks[] = array( 'token' => $token['key'], 'selector' => $loc['selector'], 'pseudo' => $loc['pseudo'] ?? null, 'inject' => $loc['inject'] ?? false, 'kind' => 'single', 'property' => $prop, 'var' => $token['var'] );
}

file_put_contents(
	__DIR__ . '/audit-spec.json',
	json_encode(
		array(
			'baseUrl'    => $BASE_URL,
			'pageUrl'    => "/?page_id=$page_id",
			'sourceId'   => $form_id,
			'dupId'      => $dup_id,
			'alreadyV2'  => $already_v2,
			'checks'     => $checks,
			'skipped'    => $skipped,
		),
		JSON_PRETTY_PRINT
	)
);

echo "Source form #$form_id -> duplicate #$dup_id, page #$page_id.\n";
if ( true === $already_v2 ) {
	echo "NOTE: source form is ALREADY a v2 record — there is no legacy baseline to compare against.\n";
} elseif ( null === $already_v2 ) {
	echo "NOTE: source form has never been styled — comparing default-vs-default (should always match).\n";
}
echo count( $checks ) . ' checks built, ' . count( $skipped ) . " tokens skipped (see audit-spec.json 'skipped').\n";
echo "Next: node migration-audit.mjs\n";
$m->close();
