<?php
/**
 * Template Loader
 *
 * @package EverestForms\Classes
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Template loader class.
 */
class EVF_Template_Loader {

	/**
	 * Store the form ID.
	 *
	 * @var integer
	 */
	private static $form_id = 0;

	/**
	 * Store whether we're processing a form preview inside the_content filter.
	 *
	 * @var boolean
	 */
	private static $in_content_filter = false;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		self::$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // WPCS: CSRF ok.

		if ( ! is_admin() && isset( $_GET['evf_preview'] ) ) { // WPCS: CSRF ok.
			add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
			add_filter( 'edit_post_link', array( __CLASS__, 'edit_form_link' ) );
			add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
			add_action( 'template_redirect', array( __CLASS__, 'form_preview_init' ) );
		} else {
			add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
		}
	}

	/**
	 * Hook into pre_get_posts to limit posts.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public static function pre_get_posts( $q ) {
		// Limit one post to query.
		if ( $q->is_main_query() ) {
			$q->set( 'posts_per_page', 1 );
		}
	}

	/**
	 * Change edit link of preview page.
	 *
	 * @param string $link Edit post link.
	 */
	public static function edit_form_link( $link ) {
		if ( 0 < self::$form_id ) {
			return '<a href="' . esc_url( admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . self::$form_id ) ) . '" class="post-edit-link">' . esc_html( 'Edit Form', 'everest-forms' ) . '</a>';
		}

		return $link;
	}

	/**
	 * Limit page templates to singular pages only.
	 *
	 * @return string
	 */
	public static function template_include() {
		return locate_template( array( 'page.php', 'single.php', 'index.php' ) );
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. everest-forms looks for theme.
	 * overrides in /theme/everest-forms/ by default.
	 *
	 * For beginners, it also looks for a everest-forms.php template first. If the user adds.
	 * this to the theme (containing a everest-forms() inside) this will be used for all.
	 * everest-forms templates.
	 *
	 * @param string $template Template to load.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			/**
			 * Filter hook to choose which files to find before EverestForms does it's own logic.
			 *
			 * @since 1.0.0
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template || EVF_TEMPLATE_DEBUG_MODE ) {
				$template = EVF()->plugin_path() . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		return '';
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @since  1.0.0
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$search_files   = apply_filters( 'everest_forms_template_loader_files', array(), $default_file );
		$search_files[] = 'everest-forms.php';

		if ( is_page_template() ) {
			$search_files[] = get_page_template_slug();
		}

		$search_files[] = $default_file;
		$search_files[] = EVF()->template_path() . $default_file;

		return array_unique( $search_files );
	}

	/*
	|--------------------------------------------------------------------------
	| Form Preview Handling
	|--------------------------------------------------------------------------
	*/

	/**
	 * Hook in methods to enhance the form preview.
	 */
	public static function form_preview_init() {
		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}

		if ( 0 < self::$form_id ) {
			add_filter( 'the_title', array( __CLASS__, 'form_preview_title_filter' ) );
			add_filter( 'the_content', array( __CLASS__, 'form_preview_content_filter' ) );
			add_filter( 'get_the_excerpt', array( __CLASS__, 'form_preview_content_filter' ) );
			add_filter( 'post_thumbnail_html', '__return_empty_string' );
		}
	}

	/**
	 * Filter the title and insert form preview title.
	 *
	 * @param  string $title Existing title.
	 * @return string
	 */
	public static function form_preview_title_filter( $title ) {
		$form = EVF()->form->get(
			self::$form_id,
			array(
				'content_only' => true,
			)
		);

		if ( ! empty( $form['settings']['form_title'] ) && in_the_loop() ) {
			if ( is_customize_preview() ) {
				return esc_html( sanitize_text_field( $form['settings']['form_title'] ) );
			}

			/* translators: %s - Form name. */
			return sprintf( esc_html__( '%s &ndash; Preview', 'everest-forms' ), sanitize_text_field( $form['settings']['form_title'] ) );
		}

		return $title;
	}

	/**
	 * Filter the content and insert form preview content.
	 *
	 * @param  string $content Existing post content.
	 * @return string
	 */
	public static function form_preview_content_filter( $content ) {
		if ( ! is_user_logged_in() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		self::$in_content_filter = true;

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( __CLASS__, 'form_preview_content_filter' ) );

		if ( current_user_can( 'manage_everest_forms' ) ) {
			$content = do_shortcode( '[everest_form id="' . absint( self::$form_id ) . '"]' );
		}

		self::$in_content_filter = false;

		return $content;
	}
}

add_action( 'init', array( 'EVF_Template_Loader', 'init' ) );
