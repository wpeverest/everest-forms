<?php
/**
 * Standalone smoke test for the Style Customizer v2 engine.
 *
 * Runs the pure logic (Schema / Sanitizer / Compiler / Migrator / Engine) without a full
 * WordPress bootstrap by stubbing the handful of WP functions they call. Intended for a
 * fast local sanity check; the formal suite will live in the plugin's PHPUnit setup.
 *
 * Usage:  php addons/StyleCustomizer/V2/tests/test-style-v2.php
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 */

if ( PHP_SAPI !== 'cli' ) {
	return; // Never execute in a web request.
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}
if ( ! function_exists( '__' ) ) {
	function __( $s, $d = null ) { return $s; }
}
if ( ! function_exists( 'apply_filters' ) ) {
	// Minimal stub: returns the default, except the `evf_style_v2_pro_active` gate, which the test
	// toggles via $GLOBALS['evf_test_pro_active'] to exercise both the Pro-active and free paths.
	function apply_filters( $t, $v ) {
		if ( 'evf_style_v2_pro_active' === $t ) {
			return ! empty( $GLOBALS['evf_test_pro_active'] );
		}
		return $v;
	}
}
$GLOBALS['evf_test_pro_active'] = false;
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $u ) { return (string) $u; }
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
}

require dirname( __DIR__ ) . '/Schema.php';
require dirname( __DIR__ ) . '/Sanitizer.php';
require dirname( __DIR__ ) . '/Compiler.php';
require dirname( __DIR__ ) . '/Migrator.php';
require dirname( __DIR__ ) . '/Engine.php';

use EverestForms\Addons\StyleCustomizer\V2\Schema;
use EverestForms\Addons\StyleCustomizer\V2\Sanitizer;
use EverestForms\Addons\StyleCustomizer\V2\Compiler;
use EverestForms\Addons\StyleCustomizer\V2\Migrator;
use EverestForms\Addons\StyleCustomizer\V2\Engine;

$pass = 0;
$fail = 0;
function ok( $cond, $msg ) {
	global $pass, $fail;
	if ( $cond ) {
		$pass++;
		echo "  [ok] $msg\n";
	} else {
		$fail++;
		echo "  [FAIL] $msg\n";
	}
}
function group( $name ) {
	echo "\n== $name ==\n";
}

/* ------------------------------------------------------------------ Schema */
// This suite runs WITHOUT the Pro plugin (EFP_PLUGIN_FILE undefined), so it exercises the FREE
// tier: the 27 Messages tokens and the 9 Pro palettes are NOT present (they are injected by the
// Pro plugin via evf_style_schema / evf_style_palettes only when Pro is active).
group( 'Schema' );
ok( count( Schema::tokens() ) === 93, 'has 93 free tokens (Messages moved to Pro)' );
ok( Schema::version() === 1, 'version is 1' );
ok( count( Schema::palettes() ) === 2, 'has 2 free palettes (9 Pro palettes moved to Pro)' );
$vars = array_values( Schema::css_var_map() );
ok( count( $vars ) === count( array_unique( $vars ) ), 'no duplicate CSS vars' );
ok( Schema::get( 'wrap.pad' )['responsive'] === true, 'wrap.pad is responsive' );
ok( Schema::get( 'wrap.width' )['responsive'] === false, 'wrap.width is NOT responsive' );
ok( Schema::get( 'btn.bg' )['hidden'] === true, 'btn.bg is hidden (palette-driven)' );
ok( null === Schema::get( 'msg.success.bg' ), 'Messages tokens absent from free schema (Pro-only)' );
// Free tiering: ALL design sections are Pro (locked teasers); only the palette-driven colour
// tokens are tier=free (so the 2 free palettes recolour), everything else is Pro.
ok( Schema::sections()['messages']['tier'] === 'pro', 'Messages section is Pro' );
ok( Schema::sections()['form']['tier'] === 'pro', 'Form section is Pro' );
ok( Schema::get( 'wrap.bg' )['tier'] === 'free', 'palette-driven wrap.bg is free' );
ok( Schema::get( 'choice.checked' )['tier'] === 'free', 'palette-driven choice.checked is free' );
ok( Schema::get( 'btn.bgHover' )['tier'] === 'free', 'derived btn.bgHover is free' );
ok( Schema::get( 'wrap.pad' )['tier'] === 'pro', 'granular control wrap.pad is Pro' );
ok( Schema::get( 'input.size' )['tier'] === 'pro', 'granular control input.size is Pro' );
ok( Schema::get( 'fonts.family' )['tier'] === 'pro', 'font family is Pro' );
$free_count = count( array_filter( Schema::tokens(), static function ( $t ) { return 'free' === $t['tier']; } ) );
ok( 12 === $free_count, 'exactly 12 free (palette-driven) tokens' );

// Legacy-parity DEFAULTS: an UNcustomized token must default to the legacy config's declared
// default, so a form that never touched it migrates pixel-identically (regression guard for the
// default-alignment pass surfaced by the 10-form migration audit — all confirmed against
// evf-style-customizer-form-wrapper-configs.php's declared 'default's).
$def = static function ( $key ) { return Schema::get( $key )['default']; };
ok( $def( 'input.ph' ) === '#c6ccd7', 'input.ph default matches legacy (#c6ccd7)' );
ok( $def( 'choice.size' ) === 16, 'choice.size default matches legacy (16)' );
ok( $def( 'choice.fsize' ) === 14, 'choice.fsize default matches legacy (14)' );
ok( $def( 'choice.color' ) === '#575757', 'choice.color default matches legacy (#575757)' );
ok( $def( 'desc.size' ) === 14, 'desc.size default matches legacy (14)' );
ok( abs( (float) $def( 'desc.line' ) - 1.7 ) < 0.001, 'desc.line default matches legacy (1.7)' );
ok( $def( 'desc.color' ) === '#575757', 'desc.color default matches legacy (#575757)' );
ok( $def( 'desc.margin' ) === array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ), 'desc.margin default matches legacy (bottom 10)' );
ok( $def( 'sub.margin' ) === array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ), 'sub.margin default matches legacy (bottom 10)' );
ok( $def( 'title.color' ) === '#575757', 'title.color default matches legacy (#575757)' );
ok( $def( 'title.pad' ) === array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ), 'title.pad default matches legacy (0)' );
ok( $def( 'title.size' ) === 16, 'title.size default matches legacy (16)' );
ok( abs( (float) $def( 'title.line' ) - 1.5 ) < 0.001, 'title.line default matches legacy (1.5)' );
ok( $def( 'title.margin' ) === array( 'top' => 25, 'right' => 0, 'bottom' => 25, 'left' => 0 ), 'title.margin default matches legacy (25/25)' );
// Match what the legacy COMPILER renders (`margin:0px 0px 5px` on `.evf-field-radio ul li`), not
// the config's declared right=20 which the compiler never emits — the render is the source of truth.
ok( $def( 'choice.margin' ) === array( 'top' => 0, 'right' => 0, 'bottom' => 5, 'left' => 0 ), 'choice.margin default matches legacy RENDER (bottom 5 only)' );

/* --------------------------------------------------------------- Sanitizer */
// The control-logic assertions below exercise Pro-tier tokens (sliders, box4, fontstyle, …), so
// run them with Pro ACTIVE — otherwise the tier gate would (correctly) strip them.
$GLOBALS['evf_test_pro_active'] = true;
group( 'Sanitizer' );
$dirty = array(
	'tokens' => array(
		'input.size'   => array( 'desktop' => 999, 'tablet' => 18 ), // over max + tablet on a NON-responsive token
		'wrap.borderC' => array( 'desktop' => 'not-a-color' ),
		'wrap.margin'  => array( 'desktop' => array( 'top' => 0, 'right' => 0, 'bottom' => 30, 'left' => 0 ), 'tablet' => array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ) ),
		'input.align'  => array( 'desktop' => 'justify' ), // invalid choice
		'label.fstyle' => array( 'desktop' => array( 'bold' => 1, 'italic' => 0 ) ),
	),
	'palette'    => 'classic',
	'custom_css' => '.ev-submit{color:red} @import url(evil.css); a{background:url(javascript:alert(1))}',
);
$clean = Sanitizer::sanitize_record( $dirty );
ok( $clean['schema_version'] === 1, 'stamps schema_version' );
ok( $clean['tokens']['input.size']['desktop'] === 22, 'slider clamped to max (22)' );
ok( ! isset( $clean['tokens']['input.size']['tablet'] ), 'tablet dropped on non-responsive token' );
ok( $clean['tokens']['wrap.borderC']['desktop'] === '#ececf1', 'invalid colour → default' );
ok( isset( $clean['tokens']['wrap.margin']['tablet'] ), 'tablet kept on responsive token' );
ok( $clean['tokens']['input.align']['desktop'] === 'left', 'invalid choice → default' );
ok( $clean['tokens']['label.fstyle']['desktop'] === array( 'bold' => true, 'italic' => false, 'underline' => false, 'uppercase' => false ), 'fontstyle normalized to booleans' );
ok( strpos( $clean['custom_css'], '@import' ) === false, 'custom CSS strips @import' );
ok( strpos( $clean['custom_css'], 'javascript:' ) === false, 'custom CSS strips javascript: url' );

// Font family (source=google_fonts): a real family is kept as-is (NOT whitelisted against the
// tiny fallback option list), and a CSS-breakout attempt is stripped.
$fonts = Sanitizer::sanitize_record(
	array(
		'tokens' => array(
			'fonts.family' => array( 'desktop' => 'Open Sans' ),
		),
	)
);
ok( $fonts['tokens']['fonts.family']['desktop'] === 'Open Sans', 'google-font family kept as-is' );
$evil_font = Sanitizer::sanitize_record(
	array(
		'tokens' => array(
			'fonts.family' => array( 'desktop' => 'Roboto;}body{display:none}' ),
		),
	)
);
ok( strpos( $evil_font['tokens']['fonts.family']['desktop'], '{' ) === false && strpos( $evil_font['tokens']['fonts.family']['desktop'], ';' ) === false, 'font family strips CSS breakout chars' );

/* ------------------------------------------------------ Pro tier enforcement */
// Switch to the FREE path (Pro inactive): a crafted request that includes pro-tier values must
// not be able to persist them — the free/pro boundary must be non-bypassable. This covers both
// a granular pro token that IS in the free schema (input.size) and a Pro-only token that is
// physically absent (msg.*), plus a Pro palette id.
$GLOBALS['evf_test_pro_active'] = false;
group( 'Pro tier enforcement (Pro inactive)' );
ok( Engine::pro_active() === false, 'pro_active is false without Pro' );
$attack = Sanitizer::sanitize_record(
	array(
		'tokens'  => array(
			'wrap.bg'        => array( 'desktop' => '#123456' ), // free palette token → kept
			'input.size'     => array( 'desktop' => 20 ),        // granular pro token → dropped
			'input.pad'      => array( 'desktop' => array( 'top' => 5, 'right' => 5, 'bottom' => 5, 'left' => 5 ) ), // pro → dropped
			'msg.success.bg' => array( 'desktop' => '#00ff00' ), // Pro-only token → dropped
		),
		'palette' => 'midnight', // a Pro palette id → must be rejected
	)
);
ok( isset( $attack['tokens']['wrap.bg'] ), 'free palette token persists' );
ok( ! isset( $attack['tokens']['input.size'] ), 'granular pro token stripped on save' );
ok( ! isset( $attack['tokens']['input.pad'] ), 'granular pro box token stripped on save' );
ok( ! isset( $attack['tokens']['msg.success.bg'] ), 'Pro-only Messages token stripped on save' );
ok( '' === $attack['palette'], 'pro palette id rejected on save' );
// A pro CUSTOMISATION can never reach the compiled CSS while Pro is inactive: the compiler emits
// the pro token's DEFAULT (so the form isn't broken by unset vars), never the crafted value.
$attack_css = Compiler::compile( array( 'tokens' => array( 'input.size' => array( 'desktop' => 20 ), 'wrap.bg' => array( 'desktop' => '#123456' ) ) ), 7 );
ok( strpos( $attack_css, '--evf-input-size:20px' ) === false, 'free: crafted pro value NOT compiled' );
ok( strpos( $attack_css, '--evf-input-size:14px' ) !== false, 'free: pro token renders at default (form not broken)' );
ok( strpos( $attack_css, '--evf-wrap-bg:#123456' ) !== false, 'free palette token still compiled without Pro' );
// Restore Pro-active for the remaining control-logic suites (Compiler/Migrator).
$GLOBALS['evf_test_pro_active'] = true;

/* ---------------------------------------------------------------- Compiler */
group( 'Compiler' );
$css = Compiler::compile( $clean, 7 );
ok( strpos( $css, '#evf-7{' ) === 0, 'scopes to #evf-7 (real wrapper)' );
ok( strpos( $css, '--evf-input-size:22px' ) !== false, 'emits clamped slider with unit' );
ok( strpos( $css, '--evf-wrap-margin:0px 0px 30px 0px' ) !== false, 'emits box4 shorthand (desktop)' );
ok( strpos( $css, '@media (max-width:768px)' ) !== false, 'emits tablet media query for responsive override' );
ok( strpos( $css, '--evf-label-weight:700' ) !== false, 'fontstyle bold → weight 700 var' );

// Defense-in-depth: even an UNSANITIZED breakout value can't escape the declaration.
$evil = Compiler::compile( array( 'tokens' => array( 'wrap.borderC' => array( 'desktop' => 'red;}body{display:none}' ) ) ), 7 );
ok( strpos( $evil, '}body' ) === false && strpos( $evil, '{display' ) === false, 'compiler css_safe blocks CSS breakout' );

$themed = Compiler::compile(
	array( 'tokens' => array( 'fonts.theme' => array( 'desktop' => true ), 'fonts.family' => array( 'desktop' => 'Poppins' ) ) ),
	7
);
ok( strpos( $themed, '--evf-font:inherit' ) !== false, '"use theme fonts" → --evf-font:inherit' );

/* ---------------------------------------------------------------- Migrator */
group( 'Migrator' );
ok( Migrator::normalize_dimension( array( 'top' => 1, 'right' => 2, 'bottom' => 3, 'left' => 4 ) ) === array( 'top' => 1, 'right' => 2, 'bottom' => 3, 'left' => 4 ), 'normalize_dimension keeps associative shape (identity)' );
$rd = Migrator::responsive_dimension( array( 'desktop' => array( 'top' => 0, 'right' => 0, 'bottom' => 30, 'left' => 0 ), 'tablet' => array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ) ) );
ok( $rd['desktop'] === array( 'top' => 0, 'right' => 0, 'bottom' => 30, 'left' => 0 ) && $rd['tablet'] === array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ), 'responsive_dimension keeps desktop+tablet, associative (lossless)' );

$legacy = array(
	'font'            => array( 'show_theme_font' => false, 'font_family' => 'Poppins' ),
	'form_container'  => array(
		'width'   => '80',
		'opacity' => '0.5',
		'margin'  => array( 'desktop' => array( 'top' => 0, 'right' => 0, 'bottom' => 30, 'left' => 0 ), 'tablet' => array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ) ),
	),
	'typography'      => array(
		'field_labels_font_size'  => '20',
		'field_labels_font_style' => array( 'bold' => true ),
	),
	'color_palette'   => array( 'color_2' => array( 'form_background' => '#111111', 'field_background' => '#222222', 'field_label' => '#eeeeee', 'field_sublabel' => '#cccccc', 'button_text' => '#000000', 'button_background' => '#ff0000' ) ),
	'success_message' => array( 'background_color' => '#00ff00' ),
);
$v2 = Migrator::migrate_record( $legacy );
ok( $v2['schema_version'] === 1, 'migrated record stamped v1' );
ok( $v2['tokens']['fonts.family']['desktop'] === 'Poppins', 'font_family migrated' );
ok( $v2['tokens']['wrap.bgOpacity']['desktop'] === '0.5', 'opacity migrated as-is (0–1, no rescale)' );
ok( $v2['tokens']['wrap.margin']['tablet'] === array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ), 'responsive margin migrated with tablet (associative)' );
ok( $v2['tokens']['label.size']['desktop'] === '20', 'field label size migrated' );
ok( $v2['tokens']['label.fstyle']['desktop']['bold'] === true, 'label font-style migrated' );
ok( $v2['tokens']['wrap.bg']['desktop'] === '#111111', 'palette form_background → wrap.bg' );
ok( $v2['tokens']['btn.bg']['desktop'] === '#ff0000', 'palette button_background → btn.bg' );
ok( $v2['tokens']['choice.checked']['desktop'] === '#ff0000', 'palette button_background spreads → choice.checked' );
// btn.bgHover isn't in Schema::palette_map() (it needs a darken transform, not a direct copy) —
// migrate_palette() derives it separately. Regression test for a real bug: the derivation was
// documented in this file's own docblock but never implemented, so every migrated form with a
// palette silently got the schema DEFAULT hover colour instead of one matching its button.
ok( $v2['tokens']['btn.bgHover']['desktop'] === '#db0000', 'palette button_background → derived btn.bgHover (14% toward black)' );
ok( $v2['tokens']['msg.success.bg']['desktop'] === '#00ff00', 'success message background migrated' );

// --- Regression: label/sublabel COLOUR is palette-driven, NEVER typography (scss.php:154,160). ---
// v1 renders label/sublabel colour only from the color_palette field_label/field_sublabel slots;
// typography.field_labels_font_color / field_sublabels_font_color are dead keys it never reads, yet
// the bundled templates carry them. Migrating them onto label.color/sub.color injected a colour v1
// never showed (surfaced by the 10-template migration audit). The palette value must win; desc/title
// colour, which IS typography-driven in v1 (scss.php:190,196), must still migrate from typography.
$dead = array(
	'typography'    => array( 'field_labels_font_color' => '#ffffff', 'field_sublabels_font_color' => '#ffffff', 'field_description_font_color' => '#123456', 'section_title_font_color' => '#654321' ),
	'color_palette' => array( 'color_2' => array( 'field_label' => '#eeeeee', 'field_sublabel' => '#cccccc' ) ),
);
$dv2 = Migrator::migrate_record( $dead );
ok( $dv2['tokens']['label.color']['desktop'] === '#eeeeee', 'label.color from PALETTE slot, not dead typography.field_labels_font_color' );
ok( $dv2['tokens']['sub.color']['desktop'] === '#cccccc', 'sub.color from PALETTE slot, not dead typography.field_sublabels_font_color' );
ok( $dv2['tokens']['desc.color']['desktop'] === '#123456', 'desc.color IS typography-driven in v1 (kept)' );
ok( $dv2['tokens']['title.color']['desktop'] === '#654321', 'title.color IS typography-driven in v1 (kept)' );

// No-palette form: the palette-driven colour tokens fall to their schema default, which MUST equal
// v1's default-palette fallback (scss.php:104-107) so a no-palette form migrates faithfully.
ok( Schema::get( 'label.color' )['default'] === '#333333', 'label.color default = v1 no-palette field_label (#333333)' );
ok( Schema::get( 'sub.color' )['default'] === '#666666', 'sub.color default = v1 no-palette field_sublabel (#666666)' );
ok( Schema::get( 'btn.bg' )['default'] === '#0073aa', 'btn.bg default = v1 no-palette button_background (#0073aa)' );

// End-to-end (Pro ACTIVE): a migrated record survives sanitize + compile with full fidelity —
// palette colour AND the now-pro granular values (label size, message colour) all persist.
$clean_pro = Sanitizer::sanitize_record( $v2 );
$compiled  = Compiler::compile( $clean_pro, 7 );
ok( strpos( $compiled, '--evf-wrap-bg:#111111' ) !== false, 'migrated → sanitized → compiled CSS carries palette colour' );
ok( isset( $clean_pro['tokens']['label.size'] ), 'Pro active: migrated granular value kept' );
// Messages tokens live in the Pro plugin (not loaded in this standalone suite), so the migrated
// message colour is dropped here regardless of the flag — it persists only when the Pro module
// injects the msg.* tokens (verified by the offline pro/free harness).
ok( ! isset( $clean_pro['tokens']['msg.success.bg'] ), 'Messages require the Pro module (absent here)' );

/* ------------------------------------------ Migration compatibility (free) */
// The tier split must be migration-SAFE on a free site: an existing v1-styled form migrates
// without corruption, its palette colours (free tokens) are preserved, and its now-Pro styling
// (granular controls, message colours) is dropped rather than rendered — a clean downgrade, not
// a break. (The legacy record is also backed up once on first v2 save; see RestController.)
$GLOBALS['evf_test_pro_active'] = false;
group( 'Migration compatibility (Pro inactive)' );
$clean_free = Sanitizer::sanitize_record( $v2 );
ok( $clean_free['tokens']['wrap.bg']['desktop'] === '#111111', 'free: migrated palette form bg preserved' );
ok( $clean_free['tokens']['btn.bg']['desktop'] === '#ff0000', 'free: migrated palette button bg preserved' );
ok( $clean_free['tokens']['label.color']['desktop'] === '#eeeeee', 'free: migrated palette label colour preserved' );
ok( ! isset( $clean_free['tokens']['label.size'] ), 'free: migrated granular label size dropped (Pro)' );
ok( ! isset( $clean_free['tokens']['label.fstyle'] ), 'free: migrated label font-style dropped (Pro)' );
ok( ! isset( $clean_free['tokens']['msg.success.bg'] ), 'free: migrated message colour dropped (Pro)' );
$compiled_free = Compiler::compile( $clean_free, 7 );
ok( strpos( $compiled_free, '--evf-wrap-bg:#111111' ) !== false, 'free: migrated palette colour still renders' );
ok( strpos( $compiled_free, '--evf-label-size:20px' ) === false, 'free: migrated granular Pro value (20px) does NOT render' );
ok( strpos( $compiled_free, '--evf-label-size:14px' ) !== false, 'free: label size renders at default (form not broken)' );
$GLOBALS['evf_test_pro_active'] = true;

/* ------------------------------------ v0 (old standalone-plugin) shape migration */
// A record still in the OLD standalone "Style Customizer" plugin shape (top-level `wrapper`,
// `field_label`, `checkbox_radio_styles`, flat `field_styles.*` typography) must migrate
// faithfully — real sites got this v0→v1 conversion from the one-shot evfsc_migration(), but a
// record that predates/skipped it would otherwise migrate to all-defaults, silently losing every
// custom colour/size (this was the one documented, unfixed migration gap — see plan §17.6 #1).
group( 'v0 (standalone-plugin) shape migration' );
$v0 = array(
	'wrapper'               => array( 'font_family' => 'Montserrat', 'background_color' => '#1d2d4f', 'padding' => array( 'desktop' => array( 'top' => 30, 'right' => 30, 'bottom' => 30, 'left' => 30 ) ) ),
	'field_label'           => array( 'font_color' => '#fcdab7', 'font_size' => '18' ),
	'field_sublabel'        => array( 'font_color' => '#aabbcc' ),
	'field_styles'          => array( 'font_size' => '16', 'font_color' => '#fcdab7', 'background_color' => '#1d2d4f', 'border_color' => '#fcdab7', 'border_radius' => array( 'top' => 4, 'right' => 4, 'bottom' => 4, 'left' => 4, 'unit' => 'px' ) ),
	'checkbox_radio_styles' => array( 'size' => '20', 'checked_color' => '#00ff00' ),
	'button'                => array( 'font_color' => '#ffffff', 'background_color' => '#0073aa', 'alignment' => 'center', 'border_radius' => array( 'top' => 3, 'right' => 3, 'bottom' => 3, 'left' => 3, 'unit' => 'px' ) ),
	'success_message'       => array( 'background_color' => '#4fc66b' ),
);
$mv0 = Migrator::migrate_record( $v0 );
ok( $mv0['schema_version'] === 1, 'v0: migrated record stamped v1' );
ok( $mv0['tokens']['fonts.family']['desktop'] === 'Montserrat', 'v0: wrapper.font_family → fonts.family' );
ok( $mv0['tokens']['wrap.pad']['desktop'] === array( 'top' => 30, 'right' => 30, 'bottom' => 30, 'left' => 30 ), 'v0: wrapper.padding → wrap.pad' );
ok( $mv0['tokens']['wrap.bg']['desktop'] === '#1d2d4f', 'v0: wrapper.background_color → palette form_background → wrap.bg' );
ok( $mv0['tokens']['input.bg']['desktop'] === '#1d2d4f', 'v0: field_styles.background_color → palette field_background → input.bg' );
ok( $mv0['tokens']['label.color']['desktop'] === '#fcdab7', 'v0: field_label.font_color → palette field_label → label.color' );
ok( $mv0['tokens']['label.size']['desktop'] === '18', 'v0: field_label.font_size → label.size' );
ok( $mv0['tokens']['input.color']['desktop'] === '#fcdab7', 'v0: field_styles.font_color (trailing-space bug CORRECTED) → input.color' );
ok( $mv0['tokens']['input.radius']['desktop']['top'] === 4, 'v0: field_styles.border_radius → input.radius' );
ok( $mv0['tokens']['choice.size']['desktop'] === '20', 'v0: checkbox_radio_styles.size → choice.size' );
ok( $mv0['tokens']['choice.checked']['desktop'] === '#00ff00', 'v0: checkbox_radio_styles.checked_color → choice.checked' );
ok( $mv0['tokens']['btn.bg']['desktop'] === '#0073aa', 'v0: button.background_color → palette button_background → btn.bg' );
ok( $mv0['tokens']['btn.align']['desktop'] === 'center', 'v0: button.alignment (button_button_alignment typo CORRECTED) → btn.align' );
ok( $mv0['tokens']['btn.radius']['desktop']['top'] === 3, 'v0: button.border_radius → btn.radius' );
ok( $mv0['tokens']['msg.success.bg']['desktop'] === '#4fc66b', 'v0: success_message.background_color → msg.success.bg' );
// A v2 record handed back to the migrator must NOT be re-migrated into a defaults-only record.
$already = array( 'schema_version' => 1, 'tokens' => array( 'wrap.width' => array( 'desktop' => 77 ) ), 'palette' => 'ocean' );
$passthru = Migrator::migrate_record( $already );
ok( $passthru['tokens']['wrap.width']['desktop'] === 77, 'v2 passthrough: existing tokens preserved, not wiped to defaults' );

/* ------------------------------------------------------------------ Engine */
group( 'Engine' );
ok( Engine::enabled() === false, 'disabled by default (no EVF_STYLE_V2)' );
ok( Engine::is_v2_record( array( 'schema_version' => 1 ) ) === true, 'record with schema_version is v2' );
ok( Engine::is_v2_record( array( 'font' => array() ) ) === false, 'legacy record is not v2' );

/* ------------------------------------------------------------------ Result */
echo "\n----------------------------------------\n";
echo "$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
