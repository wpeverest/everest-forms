<?php
/**
 * EverestForms Updates
 *
 * Functions for updating data, used by the background updater.
 *
 * @package EverestForms\Functions
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Update DB Version.
 */
function evf_update_100_db_version() {
	EVF_Install::update_db_version( '1.0.0' );
}

/**
 * Update DB Version.
 */
function evf_update_101_db_version() {
	EVF_Install::update_db_version( '1.0.1' );
}

/**
 * Update DB Version.
 */
function evf_update_102_db_version() {
	EVF_Install::update_db_version( '1.0.2' );
}

/**
 * Update DB Version.
 */
function evf_update_103_db_version() {
	EVF_Install::update_db_version( '1.0.3' );
}

/**
 * Update all forms for meta-key.
 */
function evf_update_110_update_forms() {
	$forms = evf_get_all_forms();

	foreach ( $forms as $form_id => $form ) {
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as &$form_fields ) {
				if ( ! isset( $form_fields['meta-key'] ) ) {
					$form_fields['meta-key'] = evf_get_meta_key_field_option( $form_fields );
				}
			}
		}

		// Update form data.
		EVF()->form->update( $form_id, $form_data );
	}
}

/**
 * Update DB Version.
 */
function evf_update_110_db_version() {
	EVF_Install::update_db_version( '1.1.0' );
}
