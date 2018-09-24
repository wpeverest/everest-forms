<?php
/**
 * Form Preview
 *
 * @package EverestForms\Classes
 * @since   1.3.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EverestForms Preview class.
 */
class EVF_Preview {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'form_preview' ) );
	}

	/**
	 * Handles the form preview.
	 */
	public function form_preview() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_everest_forms' ) ) {
			return;
		}

		// Form preview.
		if ( isset( $_GET['form_id'], $_GET['everest_forms'] ) && 'preview' === $_GET['everest_forms'] ) {
			add_filter( 'the_posts', array( $this, 'form_preview_query' ), 10, 2 );
		}
	}

	/**
	 * Tweak the page content for form preview page requests.
	 *
	 * @since 1.3.1
	 *
	 * @param array $posts
	 * @param WP_Query $query
	 *
	 * @return array
	 */
	public function form_preview_query( $posts, $query ) {
		// One last cap check, just for fun.
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_everest_forms' ) ) {
			return $posts;
		}

		// Only target main query.
		if ( ! $query->is_main_query() ) {
			return $posts;
		}

		// If our queried object ID does not match the preview page ID, return early.
		$preview_id = absint( get_option( 'evf_preview_page' ) );
		$queried    = $query->get_queried_object_id();
		if (
			$queried &&
			$queried !== $preview_id &&
			isset( $query->query_vars['page_id'] ) &&
			$preview_id != $query->query_vars['page_id']
		) {
			return $posts;
		}

		// Get the form details.
		$form = evf()->form->get(
			absint( $_GET['form_id'] ),
			array(
				'content_only' => true,
			)
		);

		if ( ! $form || empty( $form ) ) {
			return $posts;
		}

		// Customize the page content.
		$title     = ! empty( $form['settings']['form_title'] ) ? sanitize_text_field( $form['settings']['form_title'] ) : esc_html__( 'Form', 'wpforms' );
		$shortcode = ! empty( $form['id'] ) ? '[everest_form id="' . absint( $form['id'] ) . '"]' : '';
		$content   = esc_html__( 'This is a preview of your form. This page is not publicly accessible.', 'everest-forms' );
		if ( ! empty( $_GET['new_window'] ) ) {
			$content .= ' <a href="javascript:window.close();">' . esc_html__( 'Close this window', 'wpforms' ) . '.</a>';
		}
		/* translators: %s - Form name. */
		$posts[0]->post_title   = sprintf( esc_html__( '%s Preview', 'wpforms' ), $title );
		$posts[0]->post_content = $content . $shortcode;
		$posts[0]->post_status  = 'public';

		return $posts;
	}
}

new EVF_Preview();
