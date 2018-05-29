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

/**
 * Delete global email related options.
 */
function evf_update_116_delete_options() {
	$delete_options = array(
		'evf_to_email',
		'evf_from_name',
		'evf_from_address',
		'evf_email_subject',
		'evf_email_message',
		'everest_forms_disable_form_entries',
		'everest_forms_form_submit_button_label',
		'everest_forms_successful_form_submission_message',
	);

	foreach ( $delete_options as $delete_option ) {
		delete_option( $delete_option );
	}
}

/**
 * Update DB Version.
 */
function evf_update_116_db_version() {
	EVF_Install::update_db_version( '1.1.6' );
}
