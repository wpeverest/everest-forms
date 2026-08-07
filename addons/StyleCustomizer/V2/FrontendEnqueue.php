<?php
/**
 * Style Customizer v2 — engine-aware frontend enqueue.
 *
 * Decides, per form, whether the front end renders through the v2 engine or the legacy one
 * (exactly one stylesheet per form). A v2 form gets the `evf-style-v2` marker class, the rule
 * template ID-scoped to `#evf-{id}` (out-specifying Everest Forms' deeply-nested base CSS),
 * the per-form CSS-variable block from {@see Compiler}, and its selected Google font.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend enqueue for v2 forms.
 */
final class FrontendEnqueue {

	/**
	 * Shared stylesheet handle.
	 */
	const HANDLE = 'evf-style-v2';

	/**
	 * Marker class added to a v2 form's wrapper. Shared with the builder panel so its live
	 * preview bridge can scope the same rule template inside the `?evf_preview` iframe.
	 */
	const MARKER_CLASS = 'evf-style-v2';

	/**
	 * Memoized rule-template text (read once per request from the shared CSS file).
	 *
	 * @var string|null
	 */
	protected static $template = null;

	/**
	 * Wire the frontend hooks. Called from {@see Engine::boot()} (only when enabled). Runs at
	 * priority 20, after the legacy Style Customizer's enqueue (10), so it can dequeue it.
	 */
	public static function register() {
		add_action( 'everest_forms_shortcode_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_filter( 'everest_forms_frontend_container_class', array( __CLASS__, 'container_class' ), 10, 2 );
	}

	/**
	 * Enqueue the rule template + per-form variables for a v2 form. Mirrors the v1 enqueue
	 * hook signature (`$atts['id']`).
	 *
	 * @param array $atts Shortcode atts (`id`).
	 */
	public static function enqueue( $atts ) {
		$form_id = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;
		if ( ! $form_id ) {
			return;
		}

		$record = self::record( $form_id );
		if ( ! Engine::is_v2_record( $record ) ) {
			return; // Legacy form — the v1 enqueue handles it.
		}

		// A v2 form must not also load the legacy per-form compiled sheet.
		self::suppress_legacy_styles( $form_id );

		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			wp_register_style( self::HANDLE, false, array(), (string) Schema::version() );
		}
		wp_enqueue_style( self::HANDLE );

		// 1) The rule template (selectors reading the vars), ID-scoped to this form.
		$css = self::scoped_rule_template( $form_id );

		// 2) Per-form variable block.
		$css .= Compiler::compile( $record, $form_id );

		// 3) User custom CSS, scoped to the wrapper, emitted last so it can override token output.
		if ( ! empty( $record['custom_css'] ) ) {
			$css .= Compiler::scope_custom_css( $record['custom_css'], $form_id );
		}

		if ( '' !== $css ) {
			wp_add_inline_style( self::HANDLE, $css );
		}

		self::maybe_enqueue_font( $record, $form_id );
	}

	/**
	 * The shared rule template, ID-scoped to a form (`.evf-style-v2#evf-{id}`) — mirrors the
	 * live-preview bridge's transform so frontend and preview stay in lockstep.
	 *
	 * @param int $form_id Form id.
	 * @return string Rescoped CSS (empty if the template file is unreadable).
	 */
	protected static function scoped_rule_template( $form_id ) {
		$template = self::rule_template();
		if ( '' === $template ) {
			return '';
		}
		$scope = self::MARKER_CLASS . '#evf-' . (int) $form_id;
		// Only replace the marker class when not followed by a name char (mirrors PreviewBridge's regex).
		return (string) preg_replace(
			'/\.' . preg_quote( self::MARKER_CLASS, '/' ) . '(?![\w-])/',
			'.' . $scope,
			$template
		);
	}

	/**
	 * Read (and memoize) the shared rule-template file.
	 *
	 * @return string
	 */
	protected static function rule_template() {
		if ( null === self::$template ) {
			$path           = plugin_dir_path( __FILE__ ) . 'assets/css/frontend.css';
			self::$template = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a bundled plugin asset, not a remote/user file.
		}
		return self::$template;
	}

	/**
	 * Enqueue the form's selected Google font, unless it's using the theme font.
	 *
	 * @param array      $record  V2 style record.
	 * @param int|string $form_id Form id.
	 */
	protected static function maybe_enqueue_font( $record, $form_id ) {
		$tokens = isset( $record['tokens'] ) && is_array( $record['tokens'] ) ? $record['tokens'] : array();
		// The global "Apply Theme Style" toggle forces theme fonts too — see Compiler::compile().
		$apply_theme_style = 'default' !== get_post_meta( $form_id, 'everest_forms_enable_theme_style', true );
		$theme_font        = $apply_theme_style || ! empty( $tokens['fonts.theme']['desktop'] );
		$family            = isset( $tokens['fonts.family']['desktop'] ) ? trim( (string) $tokens['fonts.family']['desktop'] ) : '';

		if ( $theme_font || '' === $family ) {
			return;
		}
		if ( function_exists( 'evfsc_enqueue_fonts' ) ) {
			evfsc_enqueue_fonts( $family );
		}
	}

	/**
	 * Add the `evf-style-v2` marker class to a v2 form's wrapper. Also adds
	 * `evf-choice-{variation}` when the Choices "Style variation" is `outline`/`filled`, and
	 * `evf-choice-align-{center|right}` when the Choices "Alignment" isn't the default `left`.
	 *
	 * @param array $classes   Container classes.
	 * @param array $form_data Form data (`id`).
	 * @return array
	 */
	public static function container_class( $classes, $form_data ) {
		$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
		$record  = $form_id ? self::record( $form_id ) : array();
		if ( ! Engine::is_v2_record( $record ) ) {
			return $classes;
		}
		$classes[]  = 'evf-style-v2';
		$variation  = isset( $record['tokens']['choice.variation']['desktop'] ) ? (string) $record['tokens']['choice.variation']['desktop'] : '';
		if ( in_array( $variation, array( 'outline', 'filled' ), true ) ) {
			$classes[] = 'evf-choice-' . $variation;
		}
		// The Subscription Plan card's name/price row is a fixed `space-between` flex row (see
		// evf-payment-subscription-plan-frontend.scss) that plain `text-align` can never reach —
		// bridge the align token to a class too, the same way choice.variation already does, so
		// only that field's row responds to it (every other choice type already works via
		// `--evf-choice-align`'s inherited `text-align`).
		$align = isset( $record['tokens']['choice.align']['desktop'] ) ? (string) $record['tokens']['choice.align']['desktop'] : '';
		if ( in_array( $align, array( 'center', 'right' ), true ) ) {
			$classes[] = 'evf-choice-align-' . $align;
		}
		// btn.widthMode is another "meta" token (no CSS var) — 'fill' adds a class, same pattern
		// as choice.variation/choice.align above; 'fit' (the default) needs no class at all.
		$width_mode = isset( $record['tokens']['btn.widthMode']['desktop'] ) ? (string) $record['tokens']['btn.widthMode']['desktop'] : '';
		if ( 'fill' === $width_mode ) {
			$classes[] = 'evf-btn-width-fill';
		}
		return $classes;
	}

	/**
	 * Dequeue + deregister the legacy per-form stylesheet handle for a v2 form.
	 *
	 * @param int $form_id Form id.
	 */
	protected static function suppress_legacy_styles( $form_id ) {
		$handle = 'everest-forms-style-' . $form_id;
		if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	/**
	 * The stored style record for a form.
	 *
	 * @param int $form_id Form id.
	 * @return array
	 */
	protected static function record( $form_id ) {
		$all = get_option( 'everest_forms_styles', array() );
		return isset( $all[ $form_id ] ) && is_array( $all[ $form_id ] ) ? $all[ $form_id ] : array();
	}
}
