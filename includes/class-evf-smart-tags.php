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
	 * Other smart tags.
	 *
	 * @param string $return
	 *
	 * @return string|array
	 */
	public function other_smart_tags( $return = 'array' ) {

		$smart_tags = array(
			'admin_email'      => esc_html__( 'Site Admin Email', 'everest-forms' ),
			'site_name'        => esc_html__( 'Site Name', 'everest-forms' ),
			'site_url'         => esc_html__( 'Site URL', 'everest-forms' ),
			'page_title'       => esc_html__( 'Page Title', 'everest-forms' ),
			'page_url'         => esc_html__( 'Page URL', 'everest-forms' ),
			'page_id'          => esc_html__( 'Page ID', 'everest-forms' ),
			'page_id'          => esc_html__( 'Page ID', 'everest-forms' ),
			'form_name'        => esc_html__( 'Form Name', 'everest-forms' ),
			'user_ip_address'  => esc_html__( 'User IP Address', 'everest-forms' ),
			'user_id'          => esc_html__( 'User ID', 'everest-forms' ),
			'user_name'        => esc_html__( 'User Name', 'everest-forms' ),
			'user_email'       => esc_html__( 'User Email', 'everest-forms' ),
			'referrer_url'     => esc_html__( 'Referrer URL', 'everest-forms' ),

		);

		$smart_tags = apply_filters( 'everest_forms_smart_tags', $smart_tags );

		return $smart_tags;
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
		preg_match_all( "/\{field_id=\"(.+?)\"\}/", $content, $ids );

		// We can only process field smart tags if we have $fields
		if ( ! empty( $ids[1] ) && ! empty( $fields ) ) {

			foreach ( $ids[1] as $key => $field_id ) {
				if( $field_id !== 'fullname' && $field_id !== 'email' && $field_id !== 'subject' && $field_id !== 'message' ) {
					$mixed_field_id = explode( '_', $field_id );
					$value = ! empty( $fields[ $mixed_field_id[1] ]['value'] ) ? evf_sanitize_textarea_field( $fields[ $mixed_field_id[1] ]['value'] ) : '';
				} else {
					$value = ! empty( $fields[ $field_id ]['value'] ) ? evf_sanitize_textarea_field( $fields[ $field_id ]['value'] ) : '';
				}

				if( ! is_array($value) ){
					$content = str_replace( '{field_id="' . $field_id . '"}', $value, $content );
				} else {
					$value = implode(" ",$value);
					$content = str_replace( '{field_id="' . $field_id . '"}', $value, $content );
				}
			}
		}

		return $content;
	}
}
