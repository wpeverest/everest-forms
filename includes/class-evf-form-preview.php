<?php
/**
 * Form Preview
 *
 * This is a clever technique to preview a form without having to set any placeholder page by
 * setting the page templates to singular pages (page.php, single.php and index.php). Setting
 * the posts per page to 1 and changing the page title and contents dynamiclly allows us to
 * preview the form without any placeholder page.
 *
 * This technique requires the theme to have at least the above mentioned templates in the theme
 * and requires to have the WordPress Loop, otherwise we wouldn't be able to set the title and
 * the page content dynamically.
 *
 * The technique is borrowed from Ninja Forms (thanks guys!)
 *
 * @package EverestForms\Classes
 * @since   1.3.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form Preview class.
 */
class EVF_Form_Preview {

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
		self::$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

		if ( isset( $_GET['everest_forms'] ) && 'preview' === $_GET['everest_forms'] ) {
			add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
			add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
			add_action( 'template_redirect', array( __CLASS__, 'form_preview_init' ) );
		}
	}

	/**
	 * Hook into pre_get_posts to limit posts.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public static function pre_get_posts( $q ) {
		// We only want to affect the main query.
		if ( ! is_admin() && $q->is_main_query() ) {
			$q->set( 'posts_per_page', 1 );
		}
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
	 * Hook in methods to enhance the form preview.
	 */
	public static function form_preview_init() {
		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}

		if ( 0 < self::$form_id ) {
			add_filter( 'the_title', array( __CLASS__, 'the_title' ) );
			add_filter( 'the_content', array( __CLASS__, 'the_content' ) );
			add_filter( 'get_the_excerpt', array( __CLASS__, 'the_content' ) );
			add_filter( 'post_thumbnail_html', '__return_empty_string' );
		}
	}

	/**
	 * Filter the title and insert form preview title.
	 *
	 * @param  string $title Existing title.
	 * @return string
	 */
	public static function the_title( $title ) {
		$form = EVF()->form->get( self::$form_id, array(
			'content_only' => true,
		) );

		if ( ! empty( $form['settings']['form_title'] ) && in_the_loop() ) {
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
	public static function the_content( $content ) {
		if ( ! is_user_logged_in() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		self::$in_content_filter = true;

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( __CLASS__, 'the_content' ) );

		if ( current_user_can( 'manage_everest_forms' ) ) {
			$content = do_shortcode( '[everest_form id="' . absint( self::$form_id ) . '"]' );
		}

		self::$in_content_filter = false;

		return $content;
	}
}

add_action( 'init', array( 'EVF_Form_Preview', 'init' ) );
