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
$GLOBALS['evf_test_filters'] = array();
if ( ! function_exists( 'add_filter' ) ) {
	// Minimal real registry so the Palettes group can exercise the `evf_style_palettes` injection
	// end-to-end (Palettes::register() → inject → Schema::palettes() → Sanitizer id gate).
	function add_filter( $tag, $cb, $priority = 10, $args = 1 ) {
		$GLOBALS['evf_test_filters'][ $tag ][] = $cb;
		return true;
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	// Runs any registered callbacks (else returns the default), EXCEPT the `evf_style_v2_pro_active`
	// gate, which the test toggles via $GLOBALS['evf_test_pro_active'] to exercise both tiers.
	function apply_filters( $tag, $value ) {
		if ( 'evf_style_v2_pro_active' === $tag ) {
			return ! empty( $GLOBALS['evf_test_pro_active'] );
		}
		$extra = array_slice( func_get_args(), 2 );
		if ( ! empty( $GLOBALS['evf_test_filters'][ $tag ] ) ) {
			foreach ( $GLOBALS['evf_test_filters'][ $tag ] as $cb ) {
				$value = call_user_func_array( $cb, array_merge( array( $value ), $extra ) );
			}
		}
		return $value;
	}
}
$GLOBALS['evf_test_pro_active'] = false;
$GLOBALS['evf_test_options']    = array();
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $k, $default = false ) {
		return array_key_exists( $k, $GLOBALS['evf_test_options'] ) ? $GLOBALS['evf_test_options'][ $k ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $k, $v, $autoload = null ) { $GLOBALS['evf_test_options'][ $k ] = $v; return true; }
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $k ) { unset( $GLOBALS['evf_test_options'][ $k ] ); return true; }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $d ) { return json_encode( $d ); }
}
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $s, $start, $len = null ) { return null === $len ? substr( (string) $s, $start ) : substr( (string) $s, $start, $len ); }
}
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
if ( ! function_exists( 'wp_list_pluck' ) ) {
	function wp_list_pluck( $list, $field ) { return array_map( static function ( $el ) use ( $field ) { return is_array( $el ) && isset( $el[ $field ] ) ? $el[ $field ] : null; }, $list ); }
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); } // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $s ) { return preg_replace( '/[^a-z0-9\-]/', '', strtolower( str_replace( ' ', '-', (string) $s ) ) ); }
}
if ( ! function_exists( 'evf' ) ) {
	// Only Templates::image_url() needs this (for a plugin_url() prefix on built-in template
	// thumbnails) — the test never exercises that path's *output*, just needs it not to fatal.
	function evf() { return new class() { public function plugin_url() { return ''; } }; }
}

require dirname( __DIR__ ) . '/Schema.php';
require dirname( __DIR__ ) . '/Sanitizer.php';
require dirname( __DIR__ ) . '/Compiler.php';
require dirname( __DIR__ ) . '/Migrator.php';
require dirname( __DIR__ ) . '/Engine.php';
require dirname( __DIR__ ) . '/Palettes.php';
require dirname( __DIR__ ) . '/Templates.php';

use EverestForms\Addons\StyleCustomizer\V2\Schema;
use EverestForms\Addons\StyleCustomizer\V2\Sanitizer;
use EverestForms\Addons\StyleCustomizer\V2\Compiler;
use EverestForms\Addons\StyleCustomizer\V2\Migrator;
use EverestForms\Addons\StyleCustomizer\V2\Engine;
use EverestForms\Addons\StyleCustomizer\V2\Palettes;
use EverestForms\Addons\StyleCustomizer\V2\Templates;

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
// tier: the 27 Messages tokens are NOT present (they are injected by the Pro plugin via
// evf_style_schema only when Pro is active). Unlike Messages, all 11 palettes (2 free + 9 Pro)
// ship in free's own Schema so the panel can preview every one — see Schema::palettes().
group( 'Schema' );
ok( count( Schema::tokens() ) === 93, 'has 93 free tokens (Messages moved to Pro)' );
ok( Schema::version() === 1, 'version is 1' );
ok( count( Schema::palettes() ) === 11, 'has all 11 palettes (2 free + 9 Pro preview metadata)' );
$pro_palette_count = count( array_filter( Schema::palettes(), static function ( $p ) { return ! empty( $p['is_pro'] ); } ) );
ok( 9 === $pro_palette_count, 'exactly 9 palettes are Pro-tier' );
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

/* ------------------------------------------------------ Text-contrast guard (EVF-2668 follow-up) */
// A normal save (no $check_contrast opt-in) must never revert a deliberately-chosen colour, even
// a low-contrast one — the guard only ever runs for the AI style launcher.
$deliberate = Sanitizer::sanitize_record(
	array(
		'tokens' => array(
			'wrap.bg'     => array( 'desktop' => '#ffffff' ),
			'label.color' => array( 'desktop' => '#ffffff' ),
		),
	)
);
ok( $deliberate['tokens']['label.color']['desktop'] === '#ffffff', 'a normal save keeps white-on-white label.color (contrast guard is opt-in only)' );

// White label text on a white wrap.bg would vanish entirely — with the guard opted in (the AI
// launcher's path), it should drop it and fall back to the token's own (dark, coherent) default.
$vanishing = Sanitizer::sanitize_record(
	array(
		'tokens' => array(
			'wrap.bg'     => array( 'desktop' => '#ffffff' ),
			'label.color' => array( 'desktop' => '#ffffff' ),
		),
	),
	true
);
ok( ! isset( $vanishing['tokens']['label.color'] ), 'white-on-white label.color dropped when the AI contrast guard is opted in' );

// Same white label colour, but wrap.bgImage is set: the photo covers wrap.bg visually, so the
// guard can't judge contrast from wrap.bg alone and must leave the override alone.
$over_image = Sanitizer::sanitize_record(
	array(
		'tokens' => array(
			'wrap.bg'      => array( 'desktop' => '#ffffff' ),
			'wrap.bgImage' => array( 'desktop' => 'https://example.test/bg.png' ),
			'label.color'  => array( 'desktop' => '#ffffff' ),
		),
	),
	true
);
ok( $over_image['tokens']['label.color']['desktop'] === '#ffffff', 'white label.color kept when wrap.bgImage is set' );

/* ------------------------------------------------------ Pro tier enforcement */
// Switch to the FREE path (Pro inactive): a crafted request that includes pro-tier values must
// not be able to persist them — the free/pro boundary must be non-bypassable. This covers a
// granular pro token that IS in the free schema (input.size), a Pro-only token that is
// physically absent (msg.*), a Pro palette id, AND (EVF-2708) a free-tier token's raw value:
// wrap.bg/btn.bg/label.color etc. are only ever DERIVED from a registered free palette id, never
// trusted raw — otherwise a crafted request (no valid free palette, just a hand-picked colour)
// could paint an arbitrary custom scheme through keys that exist only to let the picker render.
$GLOBALS['evf_test_pro_active'] = false;
group( 'Pro tier enforcement (Pro inactive)' );
ok( Engine::pro_active() === false, 'pro_active is false without Pro' );
$attack = Sanitizer::sanitize_record(
	array(
		'tokens'  => array(
			'wrap.bg'        => array( 'desktop' => '#123456' ), // free-tier, but no valid palette below → dropped
			'input.size'     => array( 'desktop' => 20 ),        // granular pro token → dropped
			'input.pad'      => array( 'desktop' => array( 'top' => 5, 'right' => 5, 'bottom' => 5, 'left' => 5 ) ), // pro → dropped
			'msg.success.bg' => array( 'desktop' => '#00ff00' ), // Pro-only token → dropped
		),
		'palette' => 'midnight', // a Pro palette id → must be rejected
	)
);
ok( ! isset( $attack['tokens']['wrap.bg'] ), 'free-tier token\'s raw crafted value NOT trusted without a valid free palette (EVF-2708)' );
ok( ! isset( $attack['tokens']['input.size'] ), 'granular pro token stripped on save' );
ok( ! isset( $attack['tokens']['input.pad'] ), 'granular pro box token stripped on save' );
ok( ! isset( $attack['tokens']['msg.success.bg'] ), 'Pro-only Messages token stripped on save' );
ok( '' === $attack['palette'], 'pro palette id rejected on save' );

// The legitimate counterpart: WITH a valid free palette id, the free-tier token IS populated —
// but from the palette's own colour, never the crafted raw value riding alongside it.
$legit = Sanitizer::sanitize_record(
	array(
		'tokens'  => array( 'wrap.bg' => array( 'desktop' => '#123456' ) ), // ignored — not derived from this
		'palette' => 'classic',
	)
);
ok( '#ffffff' === $legit['tokens']['wrap.bg']['desktop'], 'with a valid free palette, wrap.bg comes from the palette, not the crafted raw value' );

// A pro CUSTOMISATION can never reach the compiled CSS while Pro is inactive: the compiler emits
// the pro token's DEFAULT (so the form isn't broken by unset vars), never the crafted value.
$attack_css = Compiler::compile( array( 'tokens' => array( 'input.size' => array( 'desktop' => 20 ), 'wrap.bg' => array( 'desktop' => '#123456' ) ) ), 7 );
ok( strpos( $attack_css, '--evf-input-size:20px' ) === false, 'free: crafted pro value NOT compiled' );
ok( strpos( $attack_css, '--evf-input-size:14px' ) !== false, 'free: pro token renders at default (form not broken)' );
ok( strpos( $attack_css, '--evf-wrap-bg:#123456' ) !== false, 'Compiler trusts its input and renders whatever tokens it is given — the gate is at sanitize/save time, not render time' );
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

// EVF-2659: the background-image opacity setting was var-less, so it compiled to nothing and did
// nothing. It must reach the CSS as a var (frontend.css applies it to the image layer only).
ok( ! empty( Schema::get( 'wrap.bgOpacity' )['var'] ), 'EVF-2659: wrap.bgOpacity has a CSS var' );
$bgop = Compiler::compile(
	array( 'tokens' => array( 'wrap.bgImage' => array( 'desktop' => 'https://example.test/bg.png' ), 'wrap.bgOpacity' => array( 'desktop' => 0.4 ) ) ),
	7
);
ok( strpos( $bgop, '--evf-wrap-bg-opacity:0.4' ) !== false, 'EVF-2659: background image opacity compiles to a var' );

// EVF-2671: the label's font-style vars (weight/style/decoration/transform) must be scoped to the
// nested `.evf-label` span, never the outer `<label>` — the required-field `<abbr>` and any
// tooltip icon render as SIBLINGS of `.evf-label` inside that same `<label>`
// (class-evf-shortcode-form.php), so an underline/bold/etc. set on the whole label paints across
// them too. Guards the static rule template (Compiler only emits the vars; frontend.css/
// PreviewBridge apply them — see the EVF-2659 note above), since this class of regression isn't
// otherwise caught by any Compiler-level test.
$frontend_css = file_get_contents( dirname( __DIR__ ) . '/assets/css/frontend.css' );
ok( false !== strpos( $frontend_css, 'label.evf-field-label .evf-label {' ), 'label font-style rule targets the nested .evf-label span (EVF-2671)' );
preg_match( '/label\.evf-field-label\s*\{([^}]*)\}/', $frontend_css, $outer_label_rule );
ok( isset( $outer_label_rule[1] ) && false === strpos( $outer_label_rule[1], 'text-decoration' ), 'text-decoration is NOT on the outer <label> rule (EVF-2671)' );

// Defense-in-depth: even an UNSANITIZED breakout value can't escape the declaration.
$evil = Compiler::compile( array( 'tokens' => array( 'wrap.borderC' => array( 'desktop' => 'red;}body{display:none}' ) ) ), 7 );
ok( strpos( $evil, '}body' ) === false && strpos( $evil, '{display' ) === false, 'compiler css_safe blocks CSS breakout' );

$themed = Compiler::compile(
	array( 'tokens' => array( 'fonts.theme' => array( 'desktop' => true ), 'fonts.family' => array( 'desktop' => 'Poppins' ) ) ),
	7
);
ok( strpos( $themed, '--evf-font:inherit' ) !== false, '"use theme fonts" → --evf-font:inherit' );

// A `}` inside a quoted string (e.g. a `content` value) must not close the rule block early.
$scoped_css = Compiler::scope_custom_css( '.a::before{content:"}";color:red}', 7 );
ok( strpos( $scoped_css, '#evf-7 .a::before{content:"}";color:red}' ) !== false, 'scope_custom_css keeps a rule intact when its body contains a quoted }' );

// Reviewer-reported (PR #1612 review): a `}` inside a string didn't just corrupt its own rule, it
// de-scoped every rule AFTER it too — a form's Custom CSS leaking site-wide, not a contained bug.
$leak_css = Compiler::scope_custom_css( ".a::before{content:\"}\"}\n.b{color:red}", 6 );
ok( strpos( $leak_css, '#evf-6 .a::before{content:"}"}' ) !== false, 'first rule with a quoted } is scoped and uncorrupted' );
ok( strpos( $leak_css, '#evf-6 .b{color:red}' ) !== false, 'the rule AFTER it is still scoped, not leaked site-wide' );

// A `/* ... */` comment inside a rule body must be skipped the same way as a quoted string.
$comment_css = Compiler::scope_custom_css( ".c{color:red;/* a } fake brace */margin:0}\n.d{color:green}", 6 );
ok( strpos( $comment_css, '#evf-6 .c{color:red;/* a } fake brace */margin:0}' ) !== false, 'a } inside a comment does not close the rule early' );
ok( strpos( $comment_css, '#evf-6 .d{color:green}' ) !== false, 'the rule after a comment-embedded } is still scoped' );

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
// choice.checked is NOT palette-driven during migration (EVF-2669) — v1 rendered it from its own
// independent checkbox_radio_checked_color setting, never the palette; this legacy record never set
// it, so it must land on the (legacy-parity) schema default, not the palette's button_background.
ok( $v2['tokens']['choice.checked']['desktop'] === '#575757', 'no explicit checked-colour → schema default, NOT palette button_background (EVF-2669)' );
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

// EVF-2668: bundled templates carry their palette-driven fills in typography.* (keys v1 rendered
// only via the palette) with no color_palette of their own; migrate field bg + button bg/text to
// input.bg / btn.bg / btn.color when no palette provides them, but never over a real palette value.
$fbg1 = Migrator::migrate_record( array( 'typography' => array( 'field_styles_background_color' => '#edeef5', 'button_background_color' => '#26262e', 'button_font_color' => '#ffffff' ) ) );
ok( $fbg1['tokens']['input.bg']['desktop'] === '#edeef5', 'field_styles_background_color → input.bg when no palette (EVF-2668)' );
ok( $fbg1['tokens']['btn.bg']['desktop'] === '#26262e', 'button_background_color → btn.bg when no palette (EVF-2668)' );
ok( $fbg1['tokens']['btn.color']['desktop'] === '#ffffff', 'button_font_color → btn.color when no palette (EVF-2668)' );
$fbg2 = Migrator::migrate_record( array( 'typography' => array( 'field_styles_background_color' => '#ffffff', 'button_background_color' => '#26262e', 'button_font_color' => '#eeeeee' ), 'color_palette' => array( 'color_2' => array( 'field_background' => '#222222', 'button_background' => '#333333', 'button_text' => '#444444' ) ) ) );
ok( $fbg2['tokens']['input.bg']['desktop'] === '#222222', 'palette field_background wins over typography field bg (EVF-2668)' );
ok( $fbg2['tokens']['btn.bg']['desktop'] === '#333333', 'palette button_background wins over typography button bg (EVF-2668)' );
ok( $fbg2['tokens']['btn.color']['desktop'] === '#444444', 'palette button_text wins over typography button font color (EVF-2668)' );

// EVF-2669: file.icon, choice.checked and input.focusBorder each rendered from their OWN independent
// v1 setting unconditionally (scss.php:172,187,460) — never from the palette, unlike button_background
// itself. Schema::palette_map() spreads button_background onto all three as a "recolour everything"
// convenience for the LIVE v2 panel, but that must NOT leak into migrating an old legacy record: a
// legacy record with a palette but no explicit value for one of these must land on the (now
// legacy-parity) schema default, not an invented colour v1 never showed on that element.
$fi1 = Migrator::migrate_record( array( 'color_palette' => array( 'color_3' => array( 'button_background' => '#111111' ) ) ) );
ok( $fi1['tokens']['file.icon']['desktop'] === '#494d50', 'no explicit icon colour → schema default, NOT palette button_background (EVF-2669)' );
ok( $fi1['tokens']['choice.checked']['desktop'] === '#575757', 'no explicit checked colour → schema default, NOT palette button_background (EVF-2669)' );
ok( $fi1['tokens']['input.focusBorder']['desktop'] === '#7ca8eb', 'no explicit focus-border colour → schema default, NOT palette button_background (EVF-2669)' );
ok( $fi1['tokens']['btn.bg']['desktop'] === '#111111', 'btn.bg itself STAYS palette-derived (v1 always read button bg from the palette)' );
// Explicit legacy values still win over both the palette AND the new default-reassert.
$fi2 = Migrator::migrate_record(
	array(
		'typography'    => array(
			'file_upload_icon_color'          => '#00ff00',
			'checkbox_radio_checked_color'    => '#ff00ff',
			'field_styles_border_focus_color' => '#0000ff',
		),
		'color_palette' => array( 'color_3' => array( 'button_background' => '#111111' ) ),
	)
);
ok( $fi2['tokens']['file.icon']['desktop'] === '#00ff00', 'explicit file_upload_icon_color wins over palette (EVF-2669)' );
ok( $fi2['tokens']['choice.checked']['desktop'] === '#ff00ff', 'explicit checkbox_radio_checked_color wins over palette (EVF-2669)' );
ok( $fi2['tokens']['input.focusBorder']['desktop'] === '#0000ff', 'explicit field_styles_border_focus_color wins over palette (EVF-2669)' );

// EVF-2670: v1's Image Position is a two-axis background_position_x/y grid; v2's wrap.bgPosition is
// a single 5-value enum. The old mapping read x ONLY, so a form customized on the y axis alone (the
// common case: x left at its 'center' default, y set to 'top'/'bottom') silently migrated to
// 'center' instead of the real position. Migrator::migrate_background_position() combines both axes.
$bp = function ( $x, $y ) {
	$legacy = array( 'form_container' => array( 'background_image' => 'https://example.com/bg.jpg' ) );
	if ( null !== $x ) {
		$legacy['form_container']['background_position_x'] = $x;
	}
	if ( null !== $y ) {
		$legacy['form_container']['background_position_y'] = $y;
	}
	$r = Migrator::migrate_record( $legacy );
	return isset( $r['tokens']['wrap.bgPosition'] ) ? $r['tokens']['wrap.bgPosition']['desktop'] : null;
};
ok( $bp( 'center', 'top' ) === 'top', 'x=center,y=top → top, NOT dropped to center (EVF-2670)' );
ok( $bp( 'center', 'bottom' ) === 'bottom', 'x=center,y=bottom → bottom (EVF-2670)' );
ok( $bp( 'left', 'center' ) === 'left', 'x=left,y=center → left (EVF-2670)' );
ok( $bp( 'right', 'center' ) === 'right', 'x=right,y=center → right (EVF-2670)' );
ok( $bp( 'center', 'center' ) === 'center', 'x=center,y=center → center (EVF-2670)' );
ok( $bp( 'left', null ) === 'left', 'x only, no y key at all → x still honoured (EVF-2670)' );
ok( $bp( null, null ) === null, 'neither axis set → token left unset, schema default applies (EVF-2670)' );

// File-upload schema defaults must equal v1's declared config defaults (evf-style-customizer-form-
// wrapper-configs.php) — audited alongside the icon-colour fix since every one of these keys is
// ALSO absent on every real legacy record on this site (none of the 52 forms audited ever set them),
// so any mismatch here silently drifts on migration exactly like file.icon did.
ok( Schema::get( 'file.color' )['default'] === '#494d50', 'file.color default = legacy file_upload_font_color (EVF-2669)' );
ok( Schema::get( 'file.bg' )['default'] === 'rgba(255,255,255,0.99)', 'file.bg default = legacy file_upload_background_color (EVF-2669)' );
ok( Schema::get( 'file.iconBg' )['default'] === 'rgba(255,255,255,0.99)', 'file.iconBg default = legacy file_upload_icon_background_color (EVF-2669)' );
ok( Schema::get( 'file.border' )['default'] === '#8e98a2', 'file.border default = legacy file_upload_border_color (EVF-2669)' );
// box4 defaults are normalized to the associative {top,right,bottom,left} shape (Schema::normalize()).
ok( Schema::get( 'file.margin' )['default'] === array( 'top' => 0, 'right' => 0, 'bottom' => 10, 'left' => 0 ), 'file.margin default = legacy file_upload_margin (EVF-2669)' );
ok( Schema::get( 'file.pad' )['default'] === array( 'top' => 6, 'right' => 12, 'bottom' => 6, 'left' => 12 ), 'file.pad default = legacy file_upload_padding (EVF-2669)' );

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
// The tier split must be migration-SAFE on a free site. sanitize_record() ALONE only derives a
// free-tier (palette-driven) token from a REGISTERED free palette id — never a raw value, see
// EVF-2708 — so on its own it drops a v1 form's base colours whenever the migrated palette
// matches neither of the 2 free presets. That's a real case: v1 had no tiers at all, so a site
// that's always been free could easily have picked what's now a Pro-only preset, or gone fully
// custom. This is exactly why save_item() never calls sanitize_record() alone — it always chains
// preserve_stale_pro_tokens() with the pre-save record (see that method's own doc comment): it
// re-attaches ANY token, free- or pro-tier, the old record already had, so a one-time migration
// is lossless. A stale PRO-tier value riding along this way is harmless — Compiler::compile()
// independently re-checks tier at render time, so it sits inert in storage but never actually
// renders without Pro (the compiled-CSS assertions below are the guarantee that actually
// matters, not what's merely sitting in the stored record).
$GLOBALS['evf_test_pro_active'] = false;
group( 'Migration compatibility (Pro inactive)' );
$clean_free = Sanitizer::sanitize_record( $v2 );
$clean_free = Sanitizer::preserve_stale_pro_tokens( $clean_free, $legacy );
ok( $clean_free['tokens']['wrap.bg']['desktop'] === '#111111', 'free: migrated palette form bg preserved' );
ok( $clean_free['tokens']['btn.bg']['desktop'] === '#ff0000', 'free: migrated palette button bg preserved' );
ok( $clean_free['tokens']['label.color']['desktop'] === '#eeeeee', 'free: migrated palette label colour preserved' );
ok( isset( $clean_free['tokens']['label.size'] ), 'free: stale pro-tier value rides along in storage (harmless — gated again at render, see below)' );
$compiled_free = Compiler::compile( $clean_free, 7 );
ok( strpos( $compiled_free, '--evf-wrap-bg:#111111' ) !== false, 'free: migrated palette colour still renders' );
ok( strpos( $compiled_free, '--evf-label-size:20px' ) === false, 'free: migrated granular Pro value (20px) does NOT render, even though it sits in storage' );
ok( strpos( $compiled_free, '--evf-label-size:14px' ) !== false, 'free: label size renders at default (form not broken)' );

// Regression guard: without the chained preserve_stale_pro_tokens() call, sanitize_record() ALONE
// is lossy for free-tier colours too — confirms WHY save_item() must never drop that call, not
// just that the full pipeline happens to pass today.
$clean_free_alone = Sanitizer::sanitize_record( $v2 );
ok( ! isset( $clean_free_alone['tokens']['wrap.bg'] ), 'sanitize_record() ALONE drops an unmatched free palette colour (regression guard)' );
$GLOBALS['evf_test_pro_active'] = true;

/* ------------------------------------- Preserve stale pro tokens (EVF-2685) */
// A routine save made while Pro is merely undetected (e.g. right after a manual ZIP update)
// must not permanently erase pro-tier customisation the form already had — Sanitizer::
// sanitize_record() alone has no memory of history (confirmed by the group above dropping
// msg.success.bg every time), so RestController's save path also calls preserve_stale_pro_tokens()
// with the record as it was stored BEFORE this save.
group( 'Preserve stale pro tokens (EVF-2685)' );
$GLOBALS['evf_test_pro_active'] = true;
ok(
	Sanitizer::preserve_stale_pro_tokens( array( 'tokens' => array() ), array( 'schema_version' => 1, 'tokens' => array( 'msg.success.bg' => array( 'desktop' => '#00ff00' ) ) ) )
		=== array( 'tokens' => array() ),
	'Pro active: no-op (nothing stale to preserve)'
);

$GLOBALS['evf_test_pro_active'] = false;

// Old record already v2 shape (a prior save was made while Pro WAS active) — restore straight
// from its tokens.
$restored_v2 = Sanitizer::preserve_stale_pro_tokens(
	array( 'tokens' => array( 'wrap.bg' => array( 'desktop' => '#123456' ) ) ),
	array( 'schema_version' => 1, 'tokens' => array( 'msg.success.bg' => array( 'desktop' => '#00ff00' ) ) )
);
ok( $restored_v2['tokens']['msg.success.bg']['desktop'] === '#00ff00', 'v2 old record: stale pro token restored' );
ok( $restored_v2['tokens']['wrap.bg']['desktop'] === '#123456', 'v2 old record: the new save\'s own tokens are untouched' );

// Old record still LEGACY shape (the very first save right after migrating) — re-derive via the
// same lossless Migrator rather than only reading an already-v2 record.
$restored_legacy = Sanitizer::preserve_stale_pro_tokens(
	array( 'tokens' => array() ),
	array(
		'success_message' => array( 'background_color' => '#4fc66b' ),
		'typography'       => array(),
	)
);
ok( $restored_legacy['tokens']['msg.success.bg']['desktop'] === '#4fc66b', 'legacy old record: stale pro token migrated + restored' );

// The new save's OWN value for a key always wins — never clobbered by the stale old value.
$no_clobber = Sanitizer::preserve_stale_pro_tokens(
	array( 'tokens' => array( 'msg.success.bg' => array( 'desktop' => '#ffffff' ) ) ),
	array( 'schema_version' => 1, 'tokens' => array( 'msg.success.bg' => array( 'desktop' => '#00ff00' ) ) )
);
ok( $no_clobber['tokens']['msg.success.bg']['desktop'] === '#ffffff', 'the new save\'s own value is never overwritten by the stale one' );

// Nothing to preserve (never styled before, or nothing pro-tier in the old record) is a no-op.
ok(
	Sanitizer::preserve_stale_pro_tokens( array( 'tokens' => array( 'wrap.bg' => array( 'desktop' => '#fff' ) ) ), array() )
		=== array( 'tokens' => array( 'wrap.bg' => array( 'desktop' => '#fff' ) ) ),
	'empty old record: no-op'
);
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
ok( Engine::enabled() === true, 'enabled by default (no constant/flag needed)' );
add_filter( 'evf_style_v2_enabled', static function () { return false; } );
ok( Engine::enabled() === false, 'evf_style_v2_enabled filter can force it off' );
$GLOBALS['evf_test_filters']['evf_style_v2_enabled'] = array();
ok( Engine::enabled() === true, 'back on once the off-switch filter is removed' );
ok( Engine::is_v2_record( array( 'schema_version' => 1 ) ) === true, 'record with schema_version is v2' );
ok( Engine::is_v2_record( array( 'font' => array() ) ) === false, 'legacy record is not v2' );

/* ---------------------------------------------------------------- Palettes */
// Reusable custom colour palettes (EVF-2657): storage/CRUD, v1 carry-over, colour sanitization,
// the evf_style_palettes injection, and the Pro gate on selecting a custom palette.
group( 'Palettes (custom)' );
$brand = array(
	'form_background'   => '#FAFAFA',
	'field_background'  => '#EEEEEE',
	'field_label'       => '#111111',
	'field_sublabel'    => '#333333',
	'button_text'       => '#FFFFFF',
	'button_background' => '#1155CC',
);

// sanitize_colors: exactly six slots, lowercased, unknown dropped, invalid/missing → slot default.
$san = Palettes::sanitize_colors( array( 'form_background' => '#FFF', 'evil' => '<script>', 'button_background' => 'notacolor' ) );
ok( count( $san ) === 6, 'sanitize_colors returns exactly six slots' );
ok( ! isset( $san['evil'] ), 'sanitize_colors drops unknown slots' );
ok( $san['form_background'] === '#fff', 'sanitize_colors lowercases a valid hex' );
ok( $san['button_background'] === '#3951a5', 'sanitize_colors falls back to the slot default for an invalid colour' );
ok( $san['field_label'] === '#333333', 'sanitize_colors fills a missing slot with its default' );

// create + all_custom
$GLOBALS['evf_test_options'] = array();
$created = Palettes::create( 'My Brand', $brand );
ok( strpos( $created['id'], 'pal-' ) === 0, 'create returns a pal- id' );
ok( true === $created['is_custom'] && true === $created['is_pro'], 'created palette is flagged custom + pro' );
ok( $created['colors']['form_background'] === '#fafafa', 'created colours are sanitized (lowercased)' );
$allc = Palettes::all_custom();
ok( count( $allc ) === 1, 'all_custom lists the created palette' );
ok( $allc[0]['id'] === $created['id'], 'all_custom is newest-first' );

// update
$upd = Palettes::update( $created['id'], 'Renamed', array_merge( $brand, array( 'button_background' => '#000000' ) ) );
ok( false !== $upd && 'Renamed' === $upd['name'], 'update renames a palette' );
ok( $upd['colors']['button_background'] === '#000000', 'update changes a palette colour' );
ok( Palettes::update( 'pal-doesnotexist', 'x', $brand ) === false, 'update on an unknown id returns false' );

// delete
ok( Palettes::delete( $created['id'] ) === true, 'delete removes the palette' );
ok( count( Palettes::all_custom() ) === 0, 'all_custom is empty after delete' );
ok( Palettes::delete( $created['id'] ) === false, 'delete on an unknown id returns false' );

// v1 carry-over: an existing v1 custom palette surfaces live, editable + deletable in place.
$GLOBALS['evf_test_options']['everest_forms_custom_color_palettes'] = array(
	array( 'label' => 'Old Brand', 'colors' => $brand, 'is_pro' => true, 'is_custom' => true ),
);
$legacy = Palettes::all_custom();
ok( count( $legacy ) === 1, 'v1 custom palette surfaces in all_custom' );
ok( $legacy[0]['id'] === 'legacy-palette-0', 'v1 palette gets a legacy-palette-0 id' );
ok( $legacy[0]['name'] === 'Old Brand', 'v1 label carried across as the name' );
ok( true === $legacy[0]['is_custom'], 'v1 palette flagged custom' );
ok( $legacy[0]['colors']['button_background'] === '#1155cc', 'v1 colours sanitized on read' );
$lupd = Palettes::update( 'legacy-palette-0', 'Old Renamed', $brand );
ok( false !== $lupd && $GLOBALS['evf_test_options']['everest_forms_custom_color_palettes'][0]['label'] === 'Old Renamed', 'update on a legacy id edits the v1 option in place' );
ok( Palettes::delete( 'legacy-palette-0' ) === true, 'delete on a legacy id removes it from the v1 option' );
ok( empty( $GLOBALS['evf_test_options']['everest_forms_custom_color_palettes'] ), 'v1 option is emptied after a legacy delete' );

// inject: the evf_style_palettes target prepends custom palettes to the built-in list.
$GLOBALS['evf_test_options'] = array();
$c2       = Palettes::create( 'Injected', $brand );
$injected = Palettes::inject( array( array( 'id' => 'classic', 'name' => 'Classic', 'is_pro' => false, 'colors' => array() ) ) );
ok( count( $injected ) === 2, 'inject prepends custom palettes to the built-ins' );
ok( $injected[0]['id'] === $c2['id'], 'inject places custom palettes first' );

// End-to-end tier gate: with the filter registered, a custom palette id only persists under Pro
// (Sanitizer::sanitize_palette_id, reached via the public sanitize_record).
Palettes::register();
$c3                             = Palettes::create( 'Gated', $brand );
$GLOBALS['evf_test_pro_active'] = true;
$rec_pro                        = Sanitizer::sanitize_record( array( 'tokens' => array(), 'palette' => $c3['id'] ) );
ok( $rec_pro['palette'] === $c3['id'], 'custom palette id persists when Pro is active' );
$GLOBALS['evf_test_pro_active'] = false;
$rec_free                       = Sanitizer::sanitize_record( array( 'tokens' => array(), 'palette' => $c3['id'] ) );
ok( $rec_free['palette'] === '', 'custom palette id is stripped without Pro' );

/* --------------------------------------------------------------- Templates */
// User-created style templates (create/update/delete) + legacy (v1 `evf_style_templates`)
// carry-over. The critical guarantee here is migration-safety: a legacy template is FORK-ONLY —
// update_user_template() must never touch the v1 option (unlike Palettes, whose flat 6-colour
// shape round-trips trivially; a template's v1 shape does not — see the method's doc comment).
group( 'Templates (custom)' );
$GLOBALS['evf_test_options'] = array();
$tpl_record                  = array(
	'tokens'         => array( 'wrap.bg' => array( 'desktop' => '#123456' ) ),
	'palette'        => 'classic',
	'schema_version' => 1,
);
$saved = Templates::save_user_template( 'My Template', $tpl_record );
ok( strpos( $saved['id'], 'user-' ) === 0, 'save_user_template returns a user- id' );
ok( true === $saved['custom'] && '' === $saved['image'], 'saved template flagged custom, no image' );
ok( $saved['tokens']['wrap.bg']['desktop'] === '#123456', 'saved template keeps its tokens' );
$stored_entry = $GLOBALS['evf_test_options'][ Templates::USER_OPTION ][0];
ok( 1 === $stored_entry['schema_version'], 'schema_version stamped on create' );
ok( is_int( $stored_entry['created_at'] ) && $stored_entry['created_at'] > 0, 'created_at stamped on create' );
$created_at = $stored_entry['created_at'];

// update in place
$upd_record = array( 'tokens' => array( 'wrap.bg' => array( 'desktop' => '#654321' ) ), 'palette' => '', 'schema_version' => 1 );
$updated    = Templates::update_user_template( $saved['id'], 'Renamed Template', $upd_record );
ok( false !== $updated && 'Renamed Template' === $updated['name'], 'update_user_template renames in place' );
ok( $updated['tokens']['wrap.bg']['desktop'] === '#654321', 'update_user_template changes tokens' );
ok( $GLOBALS['evf_test_options'][ Templates::USER_OPTION ][0]['created_at'] === $created_at, 'update_user_template leaves created_at untouched' );
ok( Templates::update_user_template( 'user-doesnotexist', 'x', $upd_record ) === false, 'update on an unknown id returns false' );

// Legacy templates are fork-only: update_user_template() must reject ANY legacy- id outright,
// without even looking at the v1 option — the one thing that must never regress.
ok( Templates::update_user_template( 'legacy-anything', 'x', $upd_record ) === false, 'update_user_template rejects a legacy- id outright' );

// v1 carry-over: a real custom template saved via the old (pre-v2) "Create Style Template" UI,
// stored as a JSON *string* keyed by slug in evf_style_templates (built-ins share this option
// but are skipped by name-match against Templates::all()).
$legacy_slug                                         = 'my-old-template';
$GLOBALS['evf_test_options']['evf_style_templates'] = wp_json_encode(
	array(
		$legacy_slug => array(
			'name' => 'My Old Template',
			'data' => array( 'wrapper' => array( 'background_color' => '#ababab' ) ),
		),
	)
);
$user_templates = Templates::user_templates();
$legacy_tpl      = null;
foreach ( $user_templates as $t ) {
	if ( 'My Old Template' === $t['name'] ) {
		$legacy_tpl = $t;
	}
}
ok( null !== $legacy_tpl, 'v1 custom template surfaces in user_templates' );
ok( null !== $legacy_tpl && 0 === strpos( $legacy_tpl['id'], 'legacy-' ), 'v1 template gets a legacy- id' );
ok( null !== $legacy_tpl && true === $legacy_tpl['custom'], 'v1 template flagged custom' );

// The real safety test: attempting to edit that REAL legacy id in place must fail AND must not
// touch evf_style_templates at all.
$before_raw = $GLOBALS['evf_test_options']['evf_style_templates'];
ok( false === Templates::update_user_template( $legacy_tpl['id'], 'Hacked', $upd_record ), 'update_user_template rejects the real legacy id too' );
ok( $GLOBALS['evf_test_options']['evf_style_templates'] === $before_raw, 'evf_style_templates option untouched after a rejected legacy update' );

// delete on a legacy id still routes to the v1 option (pre-existing behaviour, unaffected by the
// new update path) — confirms update and delete genuinely take different, deliberate paths.
ok( true === Templates::delete_user_template( $legacy_tpl['id'] ), 'delete_user_template still removes a legacy id from the v1 option' );
$after_raw = json_decode( $GLOBALS['evf_test_options']['evf_style_templates'], true );
ok( ! isset( $after_raw[ $legacy_slug ] ), 'legacy template actually removed from evf_style_templates after delete' );

/* ------------------------------------------------------------------ Result */
echo "\n----------------------------------------\n";
echo "$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
