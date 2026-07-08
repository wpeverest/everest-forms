<?php
/**
 * Style Customizer v2 — builder "Style" tab.
 *
 * Registers a "Style" tab in the form builder and mounts the React island into it, plus
 * enqueues the panel bundle on the builder screen. Wired only when `Engine::enabled()`.
 *
 * The mount div's id (`evf-style-customizer-v2`) and the localized `evfStyleV2` object are
 * the contract with `src/style-customizer-v2/index.tsx`.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Builder tab + panel enqueue.
 */
final class BuilderPanel {

	/**
	 * Builder tab slug.
	 */
	const TAB = 'style-v2';

	/**
	 * Builder screen id.
	 */
	const SCREEN = 'everest-forms_page_evf-builder';

	/**
	 * Wire the builder hooks. Called from {@see Engine::boot()}.
	 */
	public static function register() {
		add_filter( 'everest_forms_builder_tabs_array', array( __CLASS__, 'add_tab' ), 30 );
		// The tab uses the builder's native sidebar layout: controls in the 400px sidebar, the
		// live preview in the content area — so the panel matches the builder pixel for pixel.
		add_action( 'everest_forms_builder_sidebar_' . self::TAB, array( __CLASS__, 'render_sidebar' ) );
		add_action( 'everest_forms_builder_content_' . self::TAB, array( __CLASS__, 'render_content' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		// The builder renders each tab icon from an icon font keyed by slug; there is no glyph
		// for our `style-v2` slug, so paint one with a masked SVG (design's pencil icon).
		add_action( 'admin_head', array( __CLASS__, 'tab_icon_styles' ) );
	}

	/**
	 * Add the "Style" tab to the builder nav, positioned right after "Fields".
	 *
	 * Builder pages register at priority 20; this filter runs at 30, so `$tabs` already holds
	 * them in order. We splice ourselves in after `fields` rather than appending to the end.
	 *
	 * @param array $tabs Tabs (slug => [label, sidebar]).
	 * @return array
	 */
	public static function add_tab( $tabs ) {
		$entry = array(
			self::TAB => array(
				'label'   => __( 'Style', 'everest-forms' ),
				'sidebar' => true,
			),
		);

		if ( ! isset( $tabs['fields'] ) ) {
			return array_merge( $tabs, $entry );
		}

		$out = array();
		foreach ( $tabs as $slug => $tab ) {
			$out[ $slug ] = $tab;
			if ( 'fields' === $slug ) {
				$out = array_merge( $out, $entry );
			}
		}
		return $out;
	}

	/**
	 * Render the controls mount point inside the builder's native sidebar. The React app mounts
	 * here and portals its live preview into the content mount below.
	 */
	public static function render_sidebar() {
		echo '<div id="evf-scv2-controls" class="evf-scv2-controls"></div>';
	}

	/**
	 * Render the live-preview mount point inside the tab's content panel. The React app targets
	 * this via a portal (see src/style-customizer-v2/App.tsx).
	 */
	public static function render_content() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0;
		printf( '<div id="evf-scv2-preview" class="evf-scv2-preview" data-form-id="%d"></div>', (int) $form_id );
	}

	/**
	 * Paint the "Style" builder tab's icon. The core builder markup is
	 * `<a class="evf-panel-style-v2-button nav-tab"><span class="evf-nav-icon style-v2"></span>…`
	 * where the icon comes from an icon font by slug — but there is no `style-v2` glyph. We
	 * override the `::before` with a masked SVG so the icon inherits the tab's text colour
	 * (grey → purple when active), matching the other tabs. Builder screen only.
	 */
	public static function tab_icon_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::SCREEN !== $screen->id ) {
			return;
		}
		// url-encoded stroke SVG (design pencil). A hardcoded, fully URL-encoded constant with no
		// external input; not run through esc_url() because that strips the `data:` scheme.
		$svg = "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%23000'%20stroke-width='2'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M3%2021l3-1%2011-11a2.8%202.8%200%200%200-4-4L2%2016l-1%205'/%3E%3Cpath%20d='m15%205%204%204'/%3E%3C/svg%3E";
		$mask = 'url("' . $svg . '") no-repeat center / contain';

		echo '<style id="evf-scv2-tab-icon">'
			. '.everest-forms nav.evf-nav-tab-wrapper a.evf-panel-style-v2-button .evf-nav-icon.style-v2::before{'
			. 'content:"";display:inline-block;width:16px;height:16px;background-color:currentColor;'
			. '-webkit-mask:' . $mask . ';mask:' . $mask . ';}'
			. '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static developer-controlled CSS constant.
	}

	/**
	 * Enqueue the panel bundle on the builder screen.
	 *
	 * @param string $hook Current admin page hook (unused; we check the screen id).
	 */
	public static function enqueue( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $screen || self::SCREEN !== $screen->id || ! isset( $_GET['form_id'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = absint( wp_unslash( $_GET['form_id'] ) );

		// The panel offers a native media picker for the background image control.
		wp_enqueue_media();

		// Version by the built file's mtime so a rebuilt bundle is never served stale (even
		// within the same plugin version); falls back to the plugin version.
		$bundle_path = evf()->plugin_path() . '/dist/styleCustomizerV2.min.js';
		$bundle_ver  = file_exists( $bundle_path ) ? (string) filemtime( $bundle_path ) : ( defined( 'EVF_VERSION' ) ? EVF_VERSION : false );

		wp_enqueue_script(
			'evf-style-v2-panel',
			evf()->plugin_url() . '/dist/styleCustomizerV2.min.js',
			array( 'wp-element', 'wp-api-fetch', 'wp-i18n', 'react', 'react-dom' ),
			$bundle_ver,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'evf-style-v2-panel', 'everest-forms' );
		}

		wp_localize_script(
			'evf-style-v2-panel',
			'evfStyleV2',
			array(
				'restBase'       => '/everest-forms/v1/styles',
				'formId'         => $form_id,
				'formTitle'      => get_the_title( $form_id ),
				// Same-origin front-end preview route; the bridge live-edits its CSS variables.
				// Force the scheme to match the current admin request so an https builder never
				// embeds an http iframe (mixed-content → Chrome blocks it), and same-origin
				// framing (X-Frame-Options: SAMEORIGIN) keeps working.
				'previewUrl'     => set_url_scheme(
					add_query_arg(
						array(
							'form_id'     => $form_id,
							'evf_preview' => 'true',
						),
						home_url( '/' )
					),
					is_ssl() ? 'https' : 'http'
				),
				// The shared rule template the bridge injects into the preview iframe.
				'frontendCssUrl' => evf()->plugin_url() . '/addons/StyleCustomizer/V2/assets/css/frontend.css',
				// The wrapper the compiler scopes variables to (see Compiler::wrapper_selector()).
				'wrapperId'      => 'evf-' . $form_id,
				'markerClass'    => FrontendEnqueue::MARKER_CLASS,
			)
		);
	}
}
