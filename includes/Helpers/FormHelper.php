<?php
/**
 * Core function for the plugin.
 *
 * @package EverestForms\Helpers
 * @since xx.xx.xx
 */

namespace EverestForms\Helpers;

/**
 * FormHelper.
 *
 * @since xx.xx.xx
 */
class FormHelper {
	/**
	 * Get all the form category list.
	 *
	 * @since xx.xx.xx
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
}
