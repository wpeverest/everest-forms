<?php
/**
 * Smart tag functionality.
 *
 * @package EverestForms\Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * EVF_Smart_Tags Class.
 */
class EVF_Smart_Tags {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'everest_forms_process_smart_tags', array( $this, 'process' ), 10, 4 );
	}

	/**
	 * Process and parse smart tags.
	 *
	 * @param string       $content The string to preprocess.
	 * @param array        $form_data Array of the form data.
	 * @param string|array $fields Form fields.
	 * @param int|string   $entry_id Entry ID.
	 *
	 * @return string
	 */
	public function process( $content, $form_data, $fields = '', $entry_id = '' ) {

		// Field smart tags (settings, etc).
		preg_match_all( "/\{field_id=\"(.+?)\"\}/", $content, $meta_keys );

		// We can only process field smart tags if we have $fields
		if ( ! empty( $meta_keys[1] ) && ! empty( $fields ) ) {

			foreach ( $meta_keys[1] as $key => $field_id ) {
				$value = ! empty( $fields[ $field_id ]['value'] ) ? evf_sanitize_textarea_field( $fields[ $field_id ]['value'] ) : '';

				$content = str_replace( '{field_id="' . $field_id . '"}', $value, $content );
			}
		}

		return $content;
	}
}
