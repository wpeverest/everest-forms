<?php
/**
 * Style Customizer v2 — builder "Style" tab.
 *
 * Registers a "Style" tab in the form builder, mounts the React panel into it, and enqueues
 * the panel bundle. Wired only when `Engine::enabled()`.
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
	 * Builder tab slug (drives the `&tab=style` URL and the `everest-forms-panel-style` wrapper).
	 */
	const TAB = 'style';

	/**
	 * Builder screen id.
	 */
	const SCREEN = 'everest-forms_page_evf-builder';

	/**
	 * Wire the builder hooks. Called from {@see Engine::boot()}.
	 */
	public static function register() {
		add_filter( 'everest_forms_builder_tabs_array', array( __CLASS__, 'add_tab' ), 30 );
		add_action( 'everest_forms_builder_sidebar_' . self::TAB, array( __CLASS__, 'render_sidebar' ) );
		add_action( 'everest_forms_builder_content_' . self::TAB, array( __CLASS__, 'render_content' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_head', array( __CLASS__, 'tab_icon_styles' ) );
	}

	/**
	 * Add the "Style" tab to the builder nav, positioned right after "Fields".
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
	 * Render the controls mount point inside the builder's native sidebar.
	 */
	public static function render_sidebar() {
		echo '<div id="evf-scv2-controls" class="evf-scv2-controls"></div>';
	}

	/**
	 * Render the live-preview mount point inside the tab's content panel.
	 */
	public static function render_content() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0;
		printf( '<div id="evf-scv2-preview" class="evf-scv2-preview" data-form-id="%d"></div>', (int) $form_id );
	}

	/**
	 * Paint the "Style" builder tab's icon with a masked SVG (no icon-font glyph exists for it).
	 */
	public static function tab_icon_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::SCREEN !== $screen->id ) {
			return;
		}
		// Hardcoded, fully URL-encoded SVG constant with no external input.
		$svg = "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%23000'%20stroke-width='2'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M3%2021l3-1%2011-11a2.8%202.8%200%200%200-4-4L2%2016l-1%205'/%3E%3Cpath%20d='m15%205%204%204'/%3E%3C/svg%3E";
		$mask = 'url("' . $svg . '") no-repeat center / contain';

		echo '<style id="evf-scv2-tab-icon">'
			. '.everest-forms nav.evf-nav-tab-wrapper a.evf-panel-style-button .evf-nav-icon.style::before{'
			. 'content:"";display:inline-block;width:16px;height:16px;background-color:currentColor;'
			. '-webkit-mask:' . $mask . ';mask:' . $mask . ';}'
			. '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static developer-controlled CSS constant.
	}

	/**
	 * Mtime of a bundled V2 asset (cache-bust component), or '0' if unreadable.
	 *
	 * @param string $relative Path relative to the V2 directory.
	 * @return string
	 */
	protected static function asset_mtime( $relative ) {
		$path = plugin_dir_path( __FILE__ ) . ltrim( $relative, '/' );
		return file_exists( $path ) ? (string) filemtime( $path ) : '0';
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

		wp_enqueue_media();

		// Cache-bust by the built file's mtime; fall back to the plugin version.
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

		// Per-page-load token scoping the live-preview draft to this builder session.
		$preview_session = wp_generate_password( 12, false );

		wp_localize_script(
			'evf-style-v2-panel',
			'evfStyleV2',
			array(
				'restBase'       => '/everest-forms/v1/styles',
				'formId'         => $form_id,
				'formTitle'      => get_the_title( $form_id ),
				// Scheme forced to match the admin request to avoid a mixed-content iframe block.
				'previewUrl'     => set_url_scheme(
					add_query_arg(
						array(
							'form_id'                  => $form_id,
							'evf_preview'              => 'true',
							PreviewDraft::PREVIEW_FLAG => '1',
							PreviewDraft::SESSION_ARG  => $preview_session,
						),
						home_url( '/' )
					),
					is_ssl() ? 'https' : 'http'
				),
				// Rule template URL, version-stamped to avoid a stale browser cache.
				'frontendCssUrl' => add_query_arg( 'ver', (string) Schema::version() . '.' . self::asset_mtime( 'assets/css/frontend.css' ), evf()->plugin_url() . '/addons/StyleCustomizer/V2/assets/css/frontend.css' ),
				'wrapperId'      => 'evf-' . $form_id,
				'markerClass'    => FrontendEnqueue::MARKER_CLASS,
				'previewSession' => $preview_session,
				// Same AI-availability check the Create-with-AI feature uses.
				'aiEnabled'      => class_exists( 'EVF_AI_Registration' ) && ! \EVF_AI_Registration::is_local_site(),
				// Full initial REST payload, computed here so the panel initializes without a follow-up fetch.
				'payload'        => RestController::build_payload( $form_id ),
			)
		);
	}
}
