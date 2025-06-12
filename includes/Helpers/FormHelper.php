<?php
/**
 * Core function for the plugin.
 *
 * @package EverestForms\Helpers
 * @since 3.2.0
 */

namespace EverestForms\Helpers;

/**
 * FormHelper.
 *
 * @since 3.2.0
 */
class FormHelper {
	/**
	 * Get all the form category list.
	 *
	 * @since 3.2.0
	 * @param string $key The key.
	 */
	public static function get_all_form_tags( $key = 'slug' ) {
		$form_tags = get_terms(
			array(
				'taxonomy'   => \EVF_Post_Types::TAGS_TAXONOMY,
				'hide_empty' => false,
			)
		);

		$form_tags    = is_wp_error( $form_tags ) ? array() : (array) $form_tags;
		$tags_options = wp_list_pluck( $form_tags, 'name', $key );

		return $tags_options;
	}

	/**
	 * Particular form tags.
	 *
	 * @since 3.2.0
	 * @param [type] $form_id The form id.
	 * @param string $key The option key type.
	 */
	public static function get_form_tags( $form_id, $key = 'term_id' ) {
		$form_tags = wp_get_post_terms(
			$form_id,
			\EVF_Post_Types::TAGS_TAXONOMY,
			true
		);

		$form_tags    = is_wp_error( $form_tags ) ? array() : (array) $form_tags;
		$tags_options = wp_list_pluck( $form_tags, 'name', $key );

		return $tags_options;
	}

	/**
	 * Get all the form tags based on the forms.
	 *
	 * @param [type] $form_ids The form list.
	 */
	public static function get_selected_forms_tags( $form_ids ) {
		$all_tags = array();
		foreach ( $form_ids as $form_id ) {

			$tags = self::get_form_tags( $form_id );
			$all_tags = $all_tags + $tags;
		}

		return $all_tags;
	}
}
