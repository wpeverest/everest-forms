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
			foreach ( $form_data['form_fields'] as &$field ) {
				if ( ! isset( $field['meta-key'] ) ) {
					$field['meta-key'] = evf_get_meta_key_field_option( $field );
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

/**
 * Update settings option to use new renamed option for 1.2.0.
 */
function evf_update_120_db_rename_options() {
	$rename_options = array(
		'evf_email_template'        => 'everest_forms_email_template',
		'evf_recaptcha_site_key'    => 'everest_forms_recaptcha_site_key',
		'evf_recaptcha_site_secret' => 'everest_forms_recaptcha_site_secret',
		'evf_required_validation'   => 'everest_forms_required_validation',
		'evf_url_validation'        => 'everest_forms_url_validation',
		'evf_email_validation'      => 'everest_forms_email_validation',
		'evf_number_validation'     => 'everest_forms_number_validation',
		'evf_recaptcha_validation'  => 'everest_forms_recaptcha_validation',
		'evf_default_form_page_id'  => 'everest_forms_default_form_page_id',
	);

	foreach ( $rename_options as $old_option => $new_option ) {
		$raw_old_option = get_option( $old_option );

		if ( ! empty( $raw_old_option ) ) {
			update_option( $new_option, $raw_old_option );
			delete_option( $old_option );
		}
	}
}

/**
 * Update DB Version.
 */
function evf_update_120_db_version() {
	EVF_Install::update_db_version( '1.2.0' );
}

/**
 * Update DB Version.
 */
function evf_update_130_db_version() {
	EVF_Install::update_db_version( '1.3.0' );
}
