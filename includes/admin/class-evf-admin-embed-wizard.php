<?php
/**
 * Embed Wizard.
 *
 * @package EverestForms/Admin
 * @version 2.0.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Embed Wizard Class.
 *
 * @since 2.0.8
 */
class EVF_Admin_Embed_Wizard {

	/**
	 * Initialize class.
	 *
	 * @since 2.0.8
	 */
	public static function init() {
		add_filter( 'default_title', array( __CLASS__, 'set_embed_page_title' ), 10, 2 );
		add_filter( 'default_content', array( __CLASS__, 'set_embed_page_content' ), 10, 2 );
	}

	/**
	 * Set default title for newly created pages.
	 *
	 * @since 2.0.8
	 *
	 * @param string   $post_title Default post title.
	 * @param \WP_Post $post       Post object.
	 */
	public static function set_embed_page_title( $post_title, $post ) {

		$meta = self::get_meta();

		self::delete_meta();

		return empty( $meta['embed_page_title'] ) ? $post_title : $meta['embed_page_title'];
	}

	/**
	 * Insert the form into the new page.
	 *
	 * @since 2.0.8
	 * @param string   $post_content Default post content.
	 * @param \WP_Post $post         Post object.
	 */
	public static function set_embed_page_content( $post_content, $post ) {
		$meta = self::get_meta();

		$form_id = ! empty( $meta['form_id'] ) ? $meta['form_id'] : 0;
		$page_id = ! empty( $meta['embed_page'] ) ? $meta['embed_page'] : 0;

		if ( ! empty( $page_id ) || empty( $form_id ) ) {
			return $post_content;
		}
		$pattern = '[everest_form id="%d"]';

		return sprintf( $pattern, absint( $form_id ) );
	}


	/**
	 * Set user's embed meta data for Everest Forms.
	 *
	 * @since 2.0.8
	 * @param array $data Data array to set as embed meta data.
	 */
	public static function set_meta( $data ) {
		update_user_meta( get_current_user_id(), 'everest_forms_form_embed', $data );
	}


	/**
	 * Get user's embed meta data for Everest Forms.
	 *
	 * @since 2.0.8
	 * @return array User's embed meta data for Everest Forms.
	 */
	public static function get_meta() {

		return get_user_meta( get_current_user_id(), 'everest_forms_form_embed', true );
	}

	/**
	 * Delete user's embed meta data for Everest Forms.
	 *
	 * @since 2.0.8
	 */
	public static function delete_meta() {

		delete_user_meta( get_current_user_id(), 'everest_forms_form_embed' );
	}
}
EVF_Admin_Embed_Wizard::init();
