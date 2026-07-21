<?php
/**
 * Style Customizer v2 — token Schema (single source of truth).
 *
 * This registry is the ONE contract that drives every part of the v2 style engine:
 *   1. React controls   — the panel renders itself from these tokens.
 *   2. CSS compiler      — each token maps to one `var(--evf-*)` custom property.
 *   3. Sanitizer         — `type` (+ `options`) define how a stored value is cleaned.
 *   4. Legacy migration  — the Migrator maps old customizer keys onto these token keys
 *                          (the human-readable mapping lives in STYLE-CUSTOMIZER-V2-PLAN.md §12).
 *
 * Design invariants (see STYLE-CUSTOMIZER-V2-PLAN.md §2 / §11):
 *   - Strict subset of the bundled customizer — 0 net-new user-facing settings.
 *   - The 6 palette colours (form bg, field bg, label, sublabel, button text, button bg)
 *     are palette-driven only: their tokens exist but are `hidden` (no direct picker).
 *   - Responsive (Desktop/Tablet/Mobile) applies to MARGIN & PADDING ONLY — exactly the
 *     ~17 dimension controls the old customizer marked `responsive`. Everything else is
 *     single-value.
 *
 * Nothing here calls `__()` at file scope — the schema is built on demand (after `init`)
 * so it never triggers the WP 6.7 "translation loaded too early" notice.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Style schema registry.
 */
final class Schema {

	/**
	 * Schema version. Persisted on each saved record as `schema_version`; the presence of
	 * that key is what flips a form onto the v2 engine (no separate option/meta).
	 */
	const VERSION = 1;

	/**
	 * Memoized, normalized + filtered token list.
	 *
	 * @var array|null
	 */
	protected static $tokens = null;

	/**
	 * Memoized key => token index.
	 *
	 * @var array|null
	 */
	protected static $index = null;

	/**
	 * Current schema version.
	 *
	 * @return int
	 */
	public static function version() {
		return self::VERSION;
	}

	/**
	 * Reset the memoized cache (tests / after a runtime filter change).
	 */
	public static function flush() {
		self::$tokens = null;
		self::$index  = null;
	}

	/* --------------------------------------------------------------------- *
	 * Public accessors
	 * --------------------------------------------------------------------- */

	/**
	 * Section metadata — drives the drill-down list order, titles and variant/state tabs.
	 *
	 * @return array
	 */
	public static function sections() {
		$sections = array(
			'form'     => array(
				'title' => __( 'Form', 'everest-forms' ),
				'hint'  => __( 'The canvas everything sits on — plus the form-wide font.', 'everest-forms' ),
			),
			'text'     => array(
				'title'    => __( 'Text', 'everest-forms' ),
				'hint'     => __( 'One typography system, four roles.', 'everest-forms' ),
				'variants' => array( 'label', 'sublabel', 'desc', 'title' ),
			),
			'fields'   => array(
				'title'  => __( 'Inputs', 'everest-forms' ),
				'hint'   => __( 'Text boxes, dropdowns, textareas, date pickers — and the upload area.', 'everest-forms' ),
				'states' => array( 'normal', 'focus' ),
			),
			'choice'   => array(
				'title' => __( 'Choices', 'everest-forms' ),
				'hint'  => __( 'One style set for every pick-a-choice field: radios, checkboxes, Yes/No, image choices, scale & star ratings, Likert rows and payment methods.', 'everest-forms' ),
			),
			'button'   => array(
				'title'  => __( 'Button', 'everest-forms' ),
				'hint'   => __( 'Submit — multi-part navigation and upload buttons follow it.', 'everest-forms' ),
				'states' => array( 'normal', 'hover' ),
			),
			'messages' => array(
				'title'  => __( 'Messages', 'everest-forms' ),
				'hint'   => __( 'The success, error and validation messages shown to visitors.', 'everest-forms' ),
				'states' => array( 'success', 'error', 'validation' ),
			),
		);

		// Product decision (free tiering): the FREE plugin exposes only palette-based recolouring
		// (2 palettes) and Custom CSS. Every per-element design SECTION is a Pro feature, so each
		// renders as a locked "Upgrade to Pro" teaser on a free site (the panel shows the teaser
		// from `tier`, not the controls) and is fully functional once Pro is active. The palette
		// picker and Custom CSS tab (both outside these sections) remain free.
		foreach ( $sections as &$section ) {
			$section['tier'] = 'pro';
		}
		unset( $section );

		/**
		 * Filter the style-customizer section metadata (order, titles, variant/state tabs, tier).
		 * Pro/add-ons can add sections or adjust an existing section's tier here.
		 *
		 * @param array $sections Section definitions keyed by section id.
		 */
		return apply_filters( 'evf_style_sections', $sections );
	}

	/**
	 * The full, normalized, filterable token list.
	 *
	 * Free/Pro tiers and any add-on sections are layered in via the `evf_style_schema`
	 * filter — the panel, compiler and sanitizer all read the result of this method.
	 *
	 * @return array List of token definition arrays.
	 */
	public static function tokens() {
		if ( null === self::$tokens ) {
			/**
			 * Filter the v2 style token registry.
			 *
			 * @param array $tokens  Raw token definitions.
			 * @param int   $version Schema version.
			 */
			$tokens       = apply_filters( 'evf_style_schema', self::raw_tokens(), self::VERSION );
			$tokens       = array_map( array( __CLASS__, 'normalize' ), $tokens );
			self::$tokens = self::apply_tier_policy( $tokens );
		}
		return self::$tokens;
	}

	/**
	 * Stamp each token's tier per the free/pro product policy: the FREE plugin only ships
	 * palette-based recolouring, so ONLY the palette-driven tokens (see {@see free_token_keys()})
	 * are tier=free; every other token — all the per-element controls (sizes, borders, spacing,
	 * fonts, alignment, messages, …) — is tier=pro. The sanitizer and compiler then refuse to
	 * persist/emit any pro token unless Pro is active ({@see Engine::pro_active()}), so a free
	 * site can only ever apply the two palettes (which write the free palette tokens) + Custom CSS.
	 *
	 * This is applied AFTER the `evf_style_schema` filter, so it also governs tokens contributed
	 * by Pro/add-ons (they are pro unless their key opts into the free set via the filter below).
	 *
	 * @param array $tokens Normalized tokens.
	 * @return array
	 */
	protected static function apply_tier_policy( $tokens ) {
		$free = self::free_token_keys();
		foreach ( $tokens as &$token ) {
			$token['tier'] = isset( $free[ $token['key'] ] ) ? 'free' : 'pro';
		}
		unset( $token );
		return $tokens;
	}

	/**
	 * The set of token keys that remain FREE — the palette-driven colours the two free palettes
	 * write (so palette recolouring works without Pro), plus the derived button-hover shade.
	 * Everything else is Pro. Keyed map (key => true) for O(1) lookup; filterable so the free
	 * surface can be adjusted without editing the tier stamping.
	 *
	 * @return array
	 */
	protected static function free_token_keys() {
		$keys = array();
		foreach ( self::palette_map() as $token_keys ) {
			foreach ( (array) $token_keys as $key ) {
				$keys[ $key ] = true;
			}
		}
		// The palettes also derive the button hover background from button_background.
		$keys['btn.bgHover'] = true;

		/**
		 * Filter the token keys that stay in the FREE tier (all others are Pro).
		 *
		 * @param array $keys Map of free token key => true.
		 */
		return apply_filters( 'evf_style_free_token_keys', $keys );
	}

	/**
	 * Look up a single token by key.
	 *
	 * @param string $key Token key (e.g. `wrap.pad`).
	 * @return array|null
	 */
	public static function get( $key ) {
		if ( null === self::$index ) {
			self::$index = array();
			foreach ( self::tokens() as $token ) {
				self::$index[ $token['key'] ] = $token;
			}
		}
		return isset( self::$index[ $key ] ) ? self::$index[ $key ] : null;
	}

	/**
	 * key => default value, for building an unstyled (default) record and for
	 * `array_replace_recursive`-style merges in the compiler.
	 *
	 * @return array
	 */
	public static function defaults() {
		$out = array();
		foreach ( self::tokens() as $token ) {
			$out[ $token['key'] ] = $token['default'];
		}
		return $out;
	}

	/**
	 * key => CSS custom property, for the compiler (skips meta-only tokens with no var).
	 *
	 * @return array
	 */
	public static function css_var_map() {
		$out = array();
		foreach ( self::tokens() as $token ) {
			if ( ! empty( $token['var'] ) ) {
				$out[ $token['key'] ] = $token['var'];
			}
		}
		return $out;
	}

	/**
	 * All 11 named colour palettes (2 free + 9 Pro), each defining the old customizer's 6
	 * colours. Unlike the Pro-tier design sections (which are absent from a free schema
	 * entirely), the 9 Pro palettes' PREVIEW metadata (name + colours) ships here in free too —
	 * mirroring how {@see Templates::all()} ships every built-in template with an `is_pro` flag
	 * — so the panel can render a real swatch + name for all 11 and show the Pro ones as locked
	 * upgrade teasers instead of not showing them at all. Applying one is still gated:
	 * {@see Sanitizer::sanitize_palette_id()} rejects a Pro palette id unless Pro is active,
	 * regardless of what this list contains.
	 *
	 * @return array
	 */
	public static function palettes() {
		$palettes = array(
			array( 'id' => 'classic',  'name' => __( 'Classic', 'everest-forms' ),          'is_pro' => false, 'colors' => array( 'form_background' => '#ffffff', 'field_background' => '#f6f6f6', 'field_label' => '#081d2b', 'field_sublabel' => '#0f3a57', 'button_text' => '#ffffff', 'button_background' => '#3951a5' ) ),
			array( 'id' => 'mono',     'name' => __( 'Monochrome', 'everest-forms' ),       'is_pro' => false, 'colors' => array( 'form_background' => '#ffffff', 'field_background' => '#f7f7f7', 'field_label' => '#262626', 'field_sublabel' => '#666666', 'button_text' => '#ffffff', 'button_background' => '#1a1a1a' ) ),
			array( 'id' => 'autumn',   'name' => __( 'Autumn Blaze', 'everest-forms' ),     'is_pro' => true,  'colors' => array( 'form_background' => '#fffafa', 'field_background' => '#fff5f5', 'field_label' => '#330300', 'field_sublabel' => '#4d0500', 'button_text' => '#ffffff', 'button_background' => '#ff5d52' ) ),
			array( 'id' => 'sunset',   'name' => __( 'Sunset Glow', 'everest-forms' ),      'is_pro' => true,  'colors' => array( 'form_background' => '#fffdfa', 'field_background' => '#fff9f0', 'field_label' => '#664000', 'field_sublabel' => '#805100', 'button_text' => '#ffffff', 'button_background' => '#ffa305' ) ),
			array( 'id' => 'majestic', 'name' => __( 'Majestic', 'everest-forms' ),         'is_pro' => true,  'colors' => array( 'form_background' => '#fcfbfe', 'field_background' => '#f7f4fb', 'field_label' => '#3a225d', 'field_sublabel' => '#5d3795', 'button_text' => '#ffffff', 'button_background' => '#7545bb' ) ),
			array( 'id' => 'greenery', 'name' => __( 'Fresh Greenery', 'everest-forms' ),   'is_pro' => true,  'colors' => array( 'form_background' => '#f9fdf6', 'field_background' => '#e9f6ea', 'field_label' => '#334745', 'field_sublabel' => '#557773', 'button_text' => '#ffffff', 'button_background' => '#405956' ) ),
			array( 'id' => 'cloudy',   'name' => __( 'Cloudy Sky', 'everest-forms' ),       'is_pro' => true,  'colors' => array( 'form_background' => '#f2f3f8', 'field_background' => '#e4e7f1', 'field_label' => '#252b41', 'field_sublabel' => '#2e3651', 'button_text' => '#ffffff', 'button_background' => '#445079' ) ),
			array( 'id' => 'earthy',   'name' => __( 'Earthy Warm', 'everest-forms' ),      'is_pro' => true,  'colors' => array( 'form_background' => '#f7f6f0', 'field_background' => '#f1efe4', 'field_label' => '#474648', 'field_sublabel' => '#616062', 'button_text' => '#ffffff', 'button_background' => '#463700' ) ),
			array( 'id' => 'blossom',  'name' => __( 'Blushing Blossom', 'everest-forms' ), 'is_pro' => true,  'colors' => array( 'form_background' => '#fdf7fa', 'field_background' => '#fbeff5', 'field_label' => '#532f42', 'field_sublabel' => '#824a68', 'button_text' => '#ffffff', 'button_background' => '#46102c' ) ),
			array( 'id' => 'thunder',  'name' => __( 'Thunder', 'everest-forms' ),          'is_pro' => true,  'colors' => array( 'form_background' => '#ededed', 'field_background' => '#f7f7f7', 'field_label' => '#333333', 'field_sublabel' => '#595959', 'button_text' => '#ffffff', 'button_background' => '#1a1a1a' ) ),
			array( 'id' => 'midnight', 'name' => __( 'Midnight Charm', 'everest-forms' ),   'is_pro' => true,  'colors' => array( 'form_background' => '#363636', 'field_background' => '#3d3d3d', 'field_label' => '#ffffff', 'field_sublabel' => '#f2f2f2', 'button_text' => '#1a1a1a', 'button_background' => '#ffffff' ) ),
		);

		/**
		 * Filter the available colour palettes.
		 *
		 * @param array $palettes Palette definitions.
		 */
		return apply_filters( 'evf_style_palettes', $palettes );
	}

	/**
	 * Palette slot => token key(s) it drives. Mirrors the old 6-colour model; the extra
	 * spread on `button_background` (focus/checked/file icon) matches the prototype's
	 * one-click recolour behaviour. `button_background` also derives `btn.bgHover`.
	 *
	 * @return array
	 */
	public static function palette_map() {
		return array(
			'form_background'   => array( 'wrap.bg' ),
			'field_background'  => array( 'input.bg' ),
			'field_label'       => array( 'label.color', 'title.color' ),
			'field_sublabel'    => array( 'sub.color', 'desc.color' ),
			'button_text'       => array( 'btn.color' ),
			'button_background' => array( 'btn.bg', 'input.focusBorder', 'choice.checked', 'file.icon' ),
		);
	}

	/* --------------------------------------------------------------------- *
	 * Token assembly
	 * --------------------------------------------------------------------- */

	/**
	 * Raw (un-normalized) token list, in panel display order.
	 *
	 * @return array
	 */
	protected static function raw_tokens() {
		$border = self::border_options();

		$form = array(
			// Form / field / button BASE colours are palette-driven only (hidden picker).
			array( 'key' => 'wrap.bg', 'section' => 'form', 'group' => 'Surface', 'label' => __( 'Background color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-wrap-bg', 'default' => '#ffffff', 'hidden' => true, 'keywords' => array( 'fill' ) ),
			array( 'key' => 'fonts.theme', 'section' => 'form', 'group' => 'Typography', 'label' => __( 'Use theme fonts', 'everest-forms' ), 'type' => 'toggle', 'default' => false, 'keywords' => array( 'inherit', 'typeface', 'theme font' ) ),
			array( 'key' => 'fonts.family', 'section' => 'form', 'group' => 'Typography', 'label' => __( 'Font family', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-font', 'default' => '', 'source' => 'google_fonts', 'options' => self::font_options(), 'keywords' => array( 'typeface', 'typography', 'google font' ) ),
			array( 'key' => 'wrap.bgImage', 'section' => 'form', 'group' => 'Background', 'label' => __( 'Background image', 'everest-forms' ), 'type' => 'media', 'var' => '--evf-wrap-image', 'default' => '', 'advanced' => true, 'keywords' => array( 'image', 'photo', 'picture' ) ),
			array( 'key' => 'wrap.bgPreset', 'section' => 'form', 'group' => 'Background', 'label' => __( 'Background preset', 'everest-forms' ), 'type' => 'select', 'default' => 'custom', 'options' => self::opts( array( 'custom' => __( 'Custom', 'everest-forms' ), 'fill' => __( 'Fill', 'everest-forms' ), 'fit' => __( 'Fit', 'everest-forms' ), 'repeat' => __( 'Repeat', 'everest-forms' ), 'center' => __( 'Center', 'everest-forms' ) ) ), 'advanced' => true, 'show_when_image' => true, 'keywords' => array( 'image', 'background' ) ),
			array( 'key' => 'wrap.bgSize', 'section' => 'form', 'group' => 'Background', 'label' => __( 'Image size', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-wrap-bg-size', 'default' => 'cover', 'options' => self::opts( array( 'cover' => __( 'Cover', 'everest-forms' ), 'contain' => __( 'Contain', 'everest-forms' ), 'auto' => __( 'Auto', 'everest-forms' ) ) ), 'advanced' => true, 'show_when_image' => true, 'keywords' => array( 'image', 'background' ) ),
			array( 'key' => 'wrap.bgPosition', 'section' => 'form', 'group' => 'Background', 'label' => __( 'Image position', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-wrap-bg-position', 'default' => 'center', 'options' => self::opts( array( 'center' => __( 'Center', 'everest-forms' ), 'top' => __( 'Top', 'everest-forms' ), 'bottom' => __( 'Bottom', 'everest-forms' ), 'left' => __( 'Left', 'everest-forms' ), 'right' => __( 'Right', 'everest-forms' ) ) ), 'advanced' => true, 'show_when_image' => true, 'keywords' => array( 'image', 'background' ) ),
			array( 'key' => 'wrap.bgRepeat', 'section' => 'form', 'group' => 'Background', 'label' => __( 'Repeat background image', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-wrap-bg-repeat', 'default' => 'no-repeat', 'options' => self::opts( array( 'no-repeat' => __( 'No repeat', 'everest-forms' ), 'repeat' => __( 'Repeat', 'everest-forms' ), 'repeat-x' => __( 'Repeat X', 'everest-forms' ), 'repeat-y' => __( 'Repeat Y', 'everest-forms' ) ) ), 'advanced' => true, 'show_when_image' => true, 'keywords' => array( 'image', 'tile' ) ),
			array( 'key' => 'wrap.bgAttachment', 'section' => 'form', 'group' => 'Background', 'label' => __( 'Scroll with page', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-wrap-bg-attachment', 'default' => 'scroll', 'options' => self::opts( array( 'scroll' => __( 'Scroll', 'everest-forms' ), 'fixed' => __( 'Fixed', 'everest-forms' ) ) ), 'advanced' => true, 'show_when_image' => true, 'keywords' => array( 'parallax', 'image' ) ),
			array( 'key' => 'wrap.bgOpacity', 'section' => 'form', 'group' => 'Background', 'label' => __( 'Image opacity', 'everest-forms' ), 'type' => 'slider', 'var' => '--evf-wrap-bg-opacity', 'default' => 1, 'min' => 0, 'max' => 1, 'step' => 0.1, 'unit' => '', 'advanced' => true, 'show_when_image' => true, 'keywords' => array( 'image', 'transparency' ) ),
			array( 'key' => 'wrap.width', 'section' => 'form', 'group' => 'Layout', 'label' => __( 'Width', 'everest-forms' ), 'type' => 'slider', 'var' => '--evf-wrap-width', 'default' => 100, 'min' => 40, 'max' => 100, 'step' => 1, 'unit' => '%', 'advanced' => true, 'keywords' => array( 'size', 'narrow' ) ),
			self::box( 'wrap.margin', __( 'Form margin', 'everest-forms' ), '--evf-wrap-margin', array( 0, 0, 0, 0 ), 'form', null, -100, 100 ),
			self::box( 'wrap.pad', __( 'Form padding', 'everest-forms' ), '--evf-wrap-pad', array( 32, 32, 32, 32 ), 'form', null, 0, 100 ),
			array( 'key' => 'wrap.borderStyle', 'section' => 'form', 'group' => 'Border', 'label' => __( 'Border type', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-wrap-border-style', 'default' => 'solid', 'options' => $border, 'deps' => array( 'wrap.bw', 'wrap.borderC' ), 'advanced' => true, 'keywords' => array( 'outline' ) ),
			self::bwidth( 'wrap.bw', '--evf-wrap-bw', 1, 'form', null, 'Border', true ),
			array( 'key' => 'wrap.borderC', 'section' => 'form', 'group' => 'Border', 'label' => __( 'Border color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-wrap-border-c', 'default' => '#ececf1', 'advanced' => true ),
			self::radius( 'wrap.radius', '--evf-wrap-radius', 14, 'form', null, 'Border', false ),
		);

		$fields = array(
			array( 'key' => 'input.bg', 'section' => 'fields', 'group' => 'Surface', 'label' => __( 'Background', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-input-bg', 'default' => '#ffffff', 'hidden' => true, 'keywords' => array( 'fill' ) ),
			// default '#969696': the legacy config's field_styles_border_color setting default
			// (confirmed via getComputedStyle on a real unstyled form) — legacy-parity baseline.
			array( 'key' => 'input.borderC', 'section' => 'fields', 'group' => 'Surface', 'label' => __( 'Border color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-input-border-c', 'default' => '#969696' ),
			// default 3: legacy config's field_styles border_radius default (everest_forms_general_field_styles).
			self::radius( 'input.radius', '--evf-input-radius', 3, 'fields', null, 'Surface', false ),
			// default 14: legacy config's field_styles_font_size default.
			array( 'key' => 'input.size', 'section' => 'fields', 'group' => 'Typography', 'label' => __( 'Font size', 'everest-forms' ), 'type' => 'slider', 'var' => '--evf-input-size', 'default' => 14, 'min' => 11, 'max' => 22, 'step' => 1, 'unit' => 'px', 'advanced' => true, 'keywords' => array( 'text' ) ),
			// default '#575757': the legacy config's field_styles_font_color setting default —
			// confirmed via getComputedStyle on a real unstyled form. Legacy-parity baseline.
			array( 'key' => 'input.color', 'section' => 'fields', 'group' => 'Typography', 'label' => __( 'Text color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-input-color', 'default' => '#575757', 'advanced' => true ),
			array( 'key' => 'input.ph', 'section' => 'fields', 'group' => 'Typography', 'label' => __( 'Placeholder color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-input-ph', 'default' => '#c6ccd7', 'advanced' => true, 'keywords' => array( 'hint' ) ),
			self::fstyle( 'input.fstyle', 'input', 'fields', null, '400' ),
			self::talign( 'input.align', '--evf-input-align', 'fields', null ),
			array( 'key' => 'input.borderStyle', 'section' => 'fields', 'group' => 'Border', 'label' => __( 'Border type', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-input-border-style', 'default' => 'solid', 'options' => $border, 'deps' => array( 'input.bw', 'input.borderC' ), 'advanced' => true ),
			self::bwidth( 'input.bw', '--evf-input-bw', 1, 'fields', null, 'Border', true ),
			// default 6/12/6/12: legacy config's field_styles_padding default.
			array( 'key' => 'input.pad', 'section' => 'fields', 'group' => 'Spacing', 'label' => __( 'Field padding', 'everest-forms' ), 'type' => 'box4', 'var' => '--evf-input-pad', 'default' => array( 6, 12, 6, 12 ), 'responsive' => true, 'advanced' => true, 'keywords' => array( 'inner' ) ),
			// default bottom=10: matches the legacy config's field_styles_margin default
			// (evf-style-customizer-form-wrapper-configs.php) — legacy-parity baseline.
			array( 'key' => 'field.margin', 'section' => 'fields', 'group' => 'Spacing', 'label' => __( 'Field margin', 'everest-forms' ), 'type' => 'box4', 'var' => '--evf-field-margin', 'default' => array( 0, 0, 10, 0 ), 'responsive' => true, 'advanced' => true, 'keywords' => array( 'gap', 'between' ) ),
			array( 'key' => 'file.size', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Font size', 'everest-forms' ), 'type' => 'slider', 'var' => '--evf-file-size', 'default' => 14, 'min' => 10, 'max' => 22, 'step' => 1, 'unit' => 'px', 'advanced' => true, 'keywords' => array( 'upload', 'file' ) ),
			array( 'key' => 'file.color', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Font color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-file-color', 'default' => '#5a6072', 'advanced' => true, 'keywords' => array( 'upload', 'file', 'text' ) ),
			array( 'key' => 'file.bg', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Background', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-file-bg', 'default' => '#f7f8fb', 'advanced' => true, 'keywords' => array( 'upload', 'dropzone', 'file' ) ),
			array( 'key' => 'file.iconBg', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Icon background', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-file-iconbg', 'default' => '#ede5ff', 'advanced' => true, 'keywords' => array( 'upload', 'file' ) ),
			array( 'key' => 'file.icon', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Icon color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-file-icon', 'default' => '#3b82f6', 'advanced' => true, 'keywords' => array( 'upload', 'file' ) ),
			array( 'key' => 'file.borderStyle', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Border type', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-file-border-style', 'default' => 'dashed', 'options' => $border, 'advanced' => true, 'keywords' => array( 'upload', 'file' ) ),
			self::bwidth( 'file.bw', '--evf-file-bw', 1, 'fields', null, 'Upload area', true ),
			array( 'key' => 'file.border', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Border color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-file-border', 'default' => '#d8dae2', 'advanced' => true, 'keywords' => array( 'upload', 'file' ) ),
			self::radius( 'file.radius', '--evf-file-radius', 0, 'fields', null, 'Upload area', true ),
			array( 'key' => 'file.margin', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Margin', 'everest-forms' ), 'type' => 'box4', 'var' => '--evf-file-margin', 'default' => array( 0, 0, 0, 0 ), 'responsive' => true, 'advanced' => true, 'keywords' => array( 'upload', 'file', 'spacing' ) ),
			array( 'key' => 'file.pad', 'section' => 'fields', 'group' => 'Upload area', 'label' => __( 'Padding', 'everest-forms' ), 'type' => 'box4', 'var' => '--evf-file-pad', 'default' => array( 14, 14, 14, 14 ), 'responsive' => true, 'advanced' => true, 'keywords' => array( 'upload', 'file', 'spacing', 'inner' ) ),
			// default '#7ca8eb': the legacy config's field_styles_border_focus_color setting
			// default, confirmed via getComputedStyle on a real unstyled/unpaletted form —
			// applies only when no palette is set (a palette still derives this per §12).
			array( 'key' => 'input.focusBorder', 'section' => 'fields', 'group' => '', 'label' => __( 'Focus border color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-input-focus-border', 'default' => '#7ca8eb', 'state' => 'focus', 'keywords' => array( 'outline', 'ring', 'active' ) ),
		);

		$choice = array(
			array( 'key' => 'choice.checked', 'section' => 'choice', 'group' => '', 'label' => __( 'Selected color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-choice-checked', 'default' => '#3b82f6', 'keywords' => array( 'checked', 'radio', 'checkbox', 'rating', 'scale', 'likert', 'payment', 'yes no', 'image choice', 'star' ) ),
			array( 'key' => 'choice.size', 'section' => 'choice', 'group' => '', 'label' => __( 'Mark size', 'everest-forms' ), 'type' => 'slider', 'var' => '--evf-choice-size', 'default' => 16, 'min' => 12, 'max' => 28, 'step' => 1, 'unit' => 'px', 'keywords' => array( 'radio', 'checkbox' ) ),
			array( 'key' => 'choice.border', 'section' => 'choice', 'group' => '', 'label' => __( 'Unselected border', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-choice-border', 'default' => '#d8dae2', 'keywords' => array( 'radio', 'checkbox', 'unchecked' ) ),
			array( 'key' => 'choice.variation', 'section' => 'choice', 'group' => '', 'label' => __( 'Style variation', 'everest-forms' ), 'type' => 'select', 'default' => 'default', 'options' => self::opts( array( 'default' => __( 'Default', 'everest-forms' ), 'filled' => __( 'Filled', 'everest-forms' ), 'outline' => __( 'Outline', 'everest-forms' ) ) ), 'advanced' => true, 'special' => 'cvar', 'keywords' => array( 'modern', 'inline' ) ),
			array( 'key' => 'choice.fsize', 'section' => 'choice', 'group' => '', 'label' => __( 'Option font size', 'everest-forms' ), 'type' => 'slider', 'var' => '--evf-choice-fsize', 'default' => 14, 'min' => 11, 'max' => 22, 'step' => 1, 'unit' => 'px', 'advanced' => true, 'keywords' => array( 'text' ) ),
			array( 'key' => 'choice.color', 'section' => 'choice', 'group' => '', 'label' => __( 'Option text color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-choice-color', 'default' => '#575757', 'advanced' => true, 'keywords' => array( 'text' ) ),
			self::fstyle( 'choice.fstyle', 'choice', 'choice', null, '400' ),
			self::talign( 'choice.align', '--evf-choice-align', 'choice', null ),
			self::box( 'choice.margin', __( 'Margin', 'everest-forms' ), '--evf-choice-margin', array( 0, 0, 5, 0 ), 'choice', null ),
		);

		$button = array(
			// btn.bg is palette-driven (hidden picker); the default only shows on a NO-palette form,
			// where it must match v1's default-palette fallback button_background #0073aa
			// (scss.php:107), not a v2-invented blue — otherwise every no-palette form's button
			// recolours on migration.
			array( 'key' => 'btn.bg', 'section' => 'button', 'group' => '', 'label' => __( 'Button color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-btn-bg', 'default' => '#0073aa', 'hidden' => true, 'keywords' => array( 'submit', 'background' ) ),
			array( 'key' => 'btn.color', 'section' => 'button', 'group' => '', 'label' => __( 'Text color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-btn-color', 'default' => '#ffffff', 'hidden' => true, 'keywords' => array( 'submit' ) ),
			// default 3: legacy config's button border_radius default (everest_forms_general_buttons).
			self::radius( 'btn.radius', '--evf-btn-radius', 3, 'button', null, '', false ),
			// default 14: legacy config's button_font_size default.
			array( 'key' => 'btn.size', 'section' => 'button', 'group' => '', 'label' => __( 'Font size', 'everest-forms' ), 'type' => 'slider', 'var' => '--evf-btn-size', 'default' => 14, 'min' => 11, 'max' => 24, 'step' => 1, 'unit' => 'px', 'advanced' => true, 'keywords' => array( 'submit' ) ),
			// nw=400: legacy emits no font-weight override at all when button_font_style is
			// unset (the common case), so the button falls through to the browser/theme base
			// weight — empirically confirmed 400 via getComputedStyle. Legacy-parity baseline.
			self::fstyle( 'btn.fstyle', 'btn', 'button', null, '400' ),
			self::talign( 'btn.align', '--evf-btn-align', 'button', null, 'center' ),
			self::line( 'btn.line', '--evf-btn-lh', 1.5, 'button', null ),
			self::box( 'btn.margin', __( 'Margin', 'everest-forms' ), '--evf-btn-margin', array( 0, 0, 0, 0 ), 'button', null ),
			// default 10/15/10/15: legacy config's button_padding default.
			array( 'key' => 'btn.pad', 'section' => 'button', 'group' => '', 'label' => __( 'Padding', 'everest-forms' ), 'type' => 'box4', 'var' => '--evf-btn-pad', 'default' => array( 10, 15, 10, 15 ), 'responsive' => true, 'advanced' => true, 'keywords' => array( 'size' ) ),
			array( 'key' => 'btn.borderStyle', 'section' => 'button', 'group' => '', 'label' => __( 'Border type', 'everest-forms' ), 'type' => 'select', 'var' => '--evf-btn-border-style', 'default' => 'solid', 'options' => $border, 'deps' => array( 'btn.bw', 'btn.borderC' ), 'advanced' => true, 'keywords' => array( 'outline' ) ),
			// default 1: legacy config's button border_width default (everest_forms_general_buttons).
			self::bwidth( 'btn.bw', '--evf-btn-bw', 1, 'button', null, '', true ),
			// default '#cccccc': matches both the core (non-customizer) button stylesheet and
			// the legacy compiled CSS's own button_border_color fallback, confirmed via
			// getComputedStyle on a real unstyled form. Legacy-parity baseline.
			array( 'key' => 'btn.borderC', 'section' => 'button', 'group' => '', 'label' => __( 'Border color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-btn-border-c', 'default' => '#cccccc', 'advanced' => true ),
			// defaults '#eeeeee'/'#23282d': the legacy config's button_hover_background_color /
			// button_hover_font_color setting defaults, confirmed via getComputedStyle on a real
			// unstyled/no-palette form — a palette still derives btn.bgHover per §12.
			array( 'key' => 'btn.bgHover', 'section' => 'button', 'group' => '', 'label' => __( 'Button color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-btn-bg-hover', 'default' => '#eeeeee', 'state' => 'hover', 'keywords' => array( 'mouse over' ) ),
			array( 'key' => 'btn.colorHover', 'section' => 'button', 'group' => '', 'label' => __( 'Text color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-btn-color-hover', 'default' => '#23282d', 'state' => 'hover', 'keywords' => array( 'mouse over', 'hover font' ) ),
			// default '#cccccc': legacy config's button_border_hover_color default.
			array( 'key' => 'btn.borderCHover', 'section' => 'button', 'group' => '', 'label' => __( 'Border color', 'everest-forms' ), 'type' => 'color', 'var' => '--evf-btn-border-c-hover', 'default' => '#cccccc', 'state' => 'hover', 'keywords' => array( 'mouse over', 'hover border' ) ),
		);

		// NOTE: the Messages section tokens (tier=pro) are NOT defined here. They live in the Pro
		// plugin and are injected via the `evf_style_schema` filter only when Pro is active, so a
		// free-only site's schema physically has no pro tokens for the sanitizer/compiler to
		// process — the free/pro split is secure by construction, not by a bypassable flag.
		return array_merge(
			$form,
			self::text_role_tokens(),
			$fields,
			$choice,
			$button
		);
	}

	/**
	 * The four Text roles (Label / Sublabel / Description / Section Title), 7 controls each.
	 * Label & sublabel colour are palette-driven (hidden). Margin & padding are responsive.
	 *
	 * @return array
	 */
	protected static function text_role_tokens() {
		$roles = array(
			// state key => [ var-prefix, size, min, max, color, neutral-weight, margin, line, pad, color-hidden ].
			// nw=600: the legacy SCSS *intends* `font-weight: normal` when bold is off
			// (scss.php's `.evf-label` block), but that block is nested inside
			// `label.evf-field-label { .evf-label { ... } }` WITHOUT `&`, so it compiles to the
			// descendant selector `label.evf-field-label .evf-label` — which matches no real
			// element (the label itself carries the `evf-field-label` class, not a child with
			// class `evf-label`). That override is dead code: it never overrides anything, so
			// every legacy form's labels actually render at the core stylesheet's unconditional
			// `.everest-forms label.evf-field-label{font-weight:600}`, confirmed via
			// getComputedStyle on a real production form. This is legacy-PARITY (matching what
			// v1 truly renders, bug included), not agreement with the SCSS author's intent.
			// margin bottom=10, line=1.7: legacy config's field_labels_margin / field_labels_line_height
			// defaults, confirmed via getComputedStyle on a real unstyled form.
			// sublabel/desc/title defaults confirmed against the legacy config's declared defaults
			// (evf-style-customizer-form-wrapper-configs.php) + getComputedStyle on real unstyled
			// forms, so an UNcustomized text role migrates pixel-identically instead of shifting a
			// couple of px / a slightly different grey. desc/title colours are palette-driven (§12
			// spreads field_sublabel→desc.color, field_label→title.color), so the default only shows
			// on a form with no palette — where it must still match legacy's #575757.
			// label/sublabel colour is palette-driven (hidden picker); the default only shows on a
			// NO-palette form, where it must match v1's default-palette fallback — scss.php:104-105
			// uses field_label #333333 / field_sublabel #666666 when no palette is set. (Earlier
			// #1f2433/#6b7280 were measured on a form that HAD a palette, so the true no-palette
			// default was never exercised until the 10-template migration audit surfaced it.)
			'label'    => array( 'sub' => 'label', 'size' => 14, 'min' => 10, 'max' => 24, 'color' => '#333333', 'nw' => '600', 'margin' => array( 0, 0, 10, 0 ), 'line' => 1.7, 'pad' => array( 0, 0, 0, 0 ), 'color_hidden' => true ),
			'sublabel' => array( 'sub' => 'sub', 'size' => 12, 'min' => 9, 'max' => 20, 'color' => '#666666', 'nw' => '400', 'margin' => array( 0, 0, 10, 0 ), 'line' => 1.5, 'pad' => array( 0, 0, 0, 0 ), 'color_hidden' => true ),
			'desc'     => array( 'sub' => 'desc', 'size' => 14, 'min' => 9, 'max' => 20, 'color' => '#575757', 'nw' => '400', 'margin' => array( 0, 0, 10, 0 ), 'line' => 1.7, 'pad' => array( 0, 0, 0, 0 ), 'color_hidden' => false ),
			'title'    => array( 'sub' => 'title', 'size' => 16, 'min' => 12, 'max' => 34, 'color' => '#575757', 'nw' => '700', 'margin' => array( 25, 0, 25, 0 ), 'line' => 1.5, 'pad' => array( 0, 0, 0, 0 ), 'color_hidden' => false ),
		);

		$out = array();
		foreach ( $roles as $state => $r ) {
			$vp    = $r['sub'];
			$out[] = array( 'key' => "{$vp}.size", 'section' => 'text', 'state' => $state, 'group' => '', 'label' => __( 'Font size', 'everest-forms' ), 'type' => 'slider', 'var' => "--evf-{$vp}-size", 'default' => $r['size'], 'min' => $r['min'], 'max' => $r['max'], 'step' => 1, 'unit' => 'px', 'keywords' => array( 'text', $state ) );
			$out[] = array( 'key' => "{$vp}.color", 'section' => 'text', 'state' => $state, 'group' => '', 'label' => __( 'Color', 'everest-forms' ), 'type' => 'color', 'var' => "--evf-{$vp}-color", 'default' => $r['color'], 'hidden' => $r['color_hidden'], 'keywords' => array( 'text', $state ) );
			$out[] = self::fstyle( "{$vp}.fstyle", $vp, 'text', $state, $r['nw'] );
			$out[] = self::talign( "{$vp}.align", "--evf-{$vp}-align", 'text', $state );
			$out[] = self::line( "{$vp}.line", "--evf-{$vp}-lh", $r['line'], 'text', $state );
			$out[] = self::box( "{$vp}.margin", __( 'Margin', 'everest-forms' ), "--evf-{$vp}-margin", $r['margin'], 'text', $state );
			$out[] = self::box( "{$vp}.pad", __( 'Padding', 'everest-forms' ), "--evf-{$vp}-pad", $r['pad'], 'text', $state );
		}
		return $out;
	}

	/* --------------------------------------------------------------------- *
	 * Token builders (mixins) — repeated clusters generated, not hand-copied.
	 *
	 * The builders below (fstyle/talign/bwidth/radius/border_options) are PUBLIC: they are the
	 * shared token-shape factory the Pro plugin (and any add-on) uses to contribute tier=pro
	 * tokens through the `evf_style_schema` filter, so an injected token is guaranteed the same
	 * shape as a core one. The filtered result is `normalize()`d by {@see tokens()}, so callers
	 * return raw token arrays (box4 defaults as [t,r,b,l], `tier` defaulting to 'free').
	 * --------------------------------------------------------------------- */

	/**
	 * Font-style control (Bold / Italic / Underline / Uppercase). Stored with the OLD
	 * customizer's keys (`bold/italic/underline/uppercase`) so migration is a straight copy;
	 * `vars` maps each flag to its CSS property and `neutral_weight` is the un-bold weight.
	 *
	 * @param string $key     Token key.
	 * @param string $base    CSS var base (e.g. `label`, `input`, `msg-success`).
	 * @param string $section Section key.
	 * @param string $state   Variant/state (nullable).
	 * @param string $nw      Neutral (non-bold) font-weight.
	 * @return array
	 */
	public static function fstyle( $key, $base, $section, $state, $nw ) {
		return array(
			'key'            => $key,
			'section'        => $section,
			'state'          => $state,
			'group'          => '',
			'label'          => __( 'Font style', 'everest-forms' ),
			'type'           => 'fontstyle',
			'vars'           => array(
				'weight'     => "--evf-{$base}-weight",
				'style'      => "--evf-{$base}-fs",
				'decoration' => "--evf-{$base}-td",
				'transform'  => "--evf-{$base}-tt",
			),
			'neutral_weight' => $nw,
			'default'        => array(
				'bold'      => false,
				'italic'    => false,
				'underline' => false,
				'uppercase' => false,
			),
			'advanced'       => true,
			'keywords'       => array( 'bold', 'italic', 'underline', 'uppercase', 'font style' ),
		);
	}

	/**
	 * Line-height slider (1–3, single value).
	 *
	 * @param string $key     Token key.
	 * @param string $var     CSS var.
	 * @param float  $default Default line-height.
	 * @param string $section Section key.
	 * @param string $state   Variant/state (nullable).
	 * @return array
	 */
	protected static function line( $key, $var, $default, $section, $state ) {
		return array(
			'key'      => $key,
			'section'  => $section,
			'state'    => $state,
			'group'    => '',
			'label'    => __( 'Line height', 'everest-forms' ),
			'type'     => 'slider',
			'var'      => $var,
			'default'  => $default,
			'min'      => 1,
			'max'      => 3,
			'step'     => 0.1,
			'unit'     => '',
			'advanced' => true,
			'keywords' => array( 'leading', 'line height' ),
		);
	}

	/**
	 * Alignment control (left/center/right). Single value — the old customizer's
	 * text alignment was NOT responsive.
	 *
	 * @param string $key     Token key.
	 * @param string $var     CSS var.
	 * @param string $section Section key.
	 * @param string $state   Variant/state (nullable).
	 * @param string $default Default alignment.
	 * @return array
	 */
	public static function talign( $key, $var, $section, $state, $default = 'left' ) {
		return array(
			'key'      => $key,
			'section'  => $section,
			'state'    => $state,
			'group'    => '',
			'label'    => __( 'Alignment', 'everest-forms' ),
			'type'     => 'align',
			'var'      => $var,
			'default'  => $default,
			'advanced' => true,
			'keywords' => array( 'left', 'center', 'right', 'alignment' ),
		);
	}

	/**
	 * Margin / padding box (4-side). THE responsive control type — Desktop/Tablet/Mobile.
	 *
	 * @param string $key     Token key.
	 * @param string $label   Control label.
	 * @param string $var     CSS var.
	 * @param array  $default [top,right,bottom,left].
	 * @param string $section Section key.
	 * @param string $state   Variant/state (nullable).
	 * @param int    $min     Optional per-side lower bound (null = type default).
	 * @param int    $max     Optional per-side upper bound (null = type default).
	 * @return array
	 */
	protected static function box( $key, $label, $var, $default, $section, $state, $min = null, $max = null ) {
		$out = array(
			'key'        => $key,
			'section'    => $section,
			'state'      => $state,
			'group'      => '',
			'label'      => $label,
			'type'       => 'box4',
			'var'        => $var,
			'default'    => $default,
			'responsive' => true,
			'advanced'   => true,
			'keywords'   => array( 'spacing' ),
		);
		if ( null !== $min ) {
			$out['min'] = $min;
		}
		if ( null !== $max ) {
			$out['max'] = $max;
		}
		return $out;
	}

	/**
	 * Border-width box (4-side, single value — border width was never responsive).
	 *
	 * @param string $key      Token key.
	 * @param string $var      CSS var.
	 * @param int    $d        Default (applied to all four sides).
	 * @param string $section  Section key.
	 * @param string $state    Variant/state (nullable).
	 * @param string $group    Group label.
	 * @param bool   $advanced Whether behind the "advanced" reveal.
	 * @return array
	 */
	public static function bwidth( $key, $var, $d, $section, $state, $group, $advanced = true ) {
		return array(
			'key'      => $key,
			'section'  => $section,
			'state'    => $state,
			'group'    => $group,
			'label'    => __( 'Border width', 'everest-forms' ),
			'type'     => 'box4',
			'var'      => $var,
			'default'  => array( $d, $d, $d, $d ),
			'max'      => 12,
			'advanced' => $advanced,
			'keywords' => array( 'thickness', 'border' ),
		);
	}

	/**
	 * Border-radius box (4-corner + px/% unit, single value). Matches the old
	 * `EVF_Customize_Dimension_Control` with `unit_choices`.
	 *
	 * @param string $key      Token key.
	 * @param string $var      CSS var.
	 * @param int    $d        Default (applied to all four corners).
	 * @param string $section  Section key.
	 * @param string $state    Variant/state (nullable).
	 * @param string $group    Group label.
	 * @param bool   $advanced Whether behind the "advanced" reveal.
	 * @return array
	 */
	public static function radius( $key, $var, $d, $section, $state, $group, $advanced ) {
		return array(
			'key'      => $key,
			'section'  => $section,
			'state'    => $state,
			'group'    => $group,
			'label'    => __( 'Border radius', 'everest-forms' ),
			'type'     => 'box4',
			'var'      => $var,
			'default'  => array( $d, $d, $d, $d ),
			'max'      => 60,
			'units'    => array( 'px', '%' ),
			'corners'  => true,
			'advanced' => $advanced,
			'keywords' => array( 'corner', 'rounded' ),
		);
	}

	/* --------------------------------------------------------------------- *
	 * Helpers
	 * --------------------------------------------------------------------- */

	/**
	 * Shared border-type options — the full set the legacy customizer offered, so v2 keeps feature
	 * parity and every legacy border value survives migration through the sanitizer (EVF-2665).
	 *
	 * @return array
	 */
	public static function border_options() {
		return self::opts(
			array(
				'none'    => __( 'None', 'everest-forms' ),
				'hidden'  => __( 'Hidden', 'everest-forms' ),
				'dotted'  => __( 'Dotted', 'everest-forms' ),
				'dashed'  => __( 'Dashed', 'everest-forms' ),
				'solid'   => __( 'Solid', 'everest-forms' ),
				'double'  => __( 'Double', 'everest-forms' ),
				'groove'  => __( 'Groove', 'everest-forms' ),
				'ridge'   => __( 'Ridge', 'everest-forms' ),
				'inset'   => __( 'Inset', 'everest-forms' ),
				'outset'  => __( 'Outset', 'everest-forms' ),
				'initial' => __( 'Initial', 'everest-forms' ),
				'inherit' => __( 'Inherit', 'everest-forms' ),
			)
		);
	}

	/**
	 * Font-family options. Only the "Theme default" (empty value = use the theme font) is baked
	 * into the schema; the full Google Fonts list is supplied to the panel at runtime via the
	 * REST payload (`google_fonts`, from {@see evfsc_get_google_font_families()}) so it matches
	 * the legacy customizer exactly and is fetched once. The token is marked `source =>
	 * google_fonts`, which is how the panel (and the sanitizer) recognise it.
	 *
	 * @return array
	 */
	protected static function font_options() {
		return self::opts(
			array(
				'' => __( 'Theme default', 'everest-forms' ),
			)
		);
	}

	/**
	 * Convert a value=>label map into the panel's [{value,label}] option shape.
	 *
	 * @param array $map value => label.
	 * @return array
	 */
	protected static function opts( $map ) {
		$out = array();
		foreach ( $map as $value => $label ) {
			$out[] = array(
				'value' => $value,
				'label' => $label,
			);
		}
		return $out;
	}

	/**
	 * Fill every token with its default keys so consumers can rely on the full shape.
	 *
	 * @param array $token Raw token.
	 * @return array
	 */
	protected static function normalize( $token ) {
		$token = array_merge(
			array(
				'key'        => '',
				'section'    => '',
				'group'      => '',
				'label'      => '',
				'type'       => '',
				'var'        => null,
				'default'    => null,
				'state'      => null,
				'responsive' => false,
				'advanced'   => false,
				'hidden'     => false,
				'tier'       => 'free',
				'keywords'   => array(),
			),
			$token
		);

		// Box defaults are authored compactly as [t,r,b,l]; store/emit them as the SAME
		// associative shape the legacy engine uses ({top,right,bottom,left}[,unit]) so
		// migration is an identity copy, not a reshape.
		if ( 'box4' === $token['type'] && is_array( $token['default'] ) && isset( $token['default'][0] ) ) {
			$token['default'] = self::to_dim( $token['default'], ! empty( $token['units'] ) ? $token['units'][0] : null );
		}

		return $token;
	}

	/**
	 * [t,r,b,l] → { top, right, bottom, left [, unit ] }.
	 *
	 * @param array       $box  Indexed sides.
	 * @param string|null $unit Optional unit (radius).
	 * @return array
	 */
	protected static function to_dim( $box, $unit = null ) {
		$dim = array(
			'top'    => isset( $box[0] ) ? $box[0] : 0,
			'right'  => isset( $box[1] ) ? $box[1] : 0,
			'bottom' => isset( $box[2] ) ? $box[2] : 0,
			'left'   => isset( $box[3] ) ? $box[3] : 0,
		);
		if ( null !== $unit ) {
			$dim['unit'] = $unit;
		}
		return $dim;
	}
}
