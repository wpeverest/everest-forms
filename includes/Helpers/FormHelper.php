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
	public static function get_all_form_category( $key = 'slug' ) {
		$form_category = get_terms(
			array(
				'taxonomy'   => \EVF_Post_Types::CATEGORY_TAXONOMY,
				'hide_empty' => false,
			)
		);

		$form_category = is_wp_error( $form_category ) ? array() : (array) $form_category;
		$tags_options  = wp_list_pluck( $form_category, 'name', $key );

		return $tags_options;
	}
}
