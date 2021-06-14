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
		$form_obj  = evf()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as &$field ) {
				if ( ! isset( $field['meta-key'] ) ) {
					$field['meta-key'] = evf_get_meta_key_field_option( $field );
				}
			}
		}

		// Update form data.
		evf()->form->update( $form_id, $form_data );
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
 * Update email settings adding connection data.
 */
function evf_update_140_db_multiple_email() {
	$forms = evf()->form->get_multiple( array( 'order' => 'DESC' ) );

	// Loop through each forms.
	foreach ( $forms as $form ) {
		$form_id   = isset( $form->ID ) ? $form->ID : '0';
		$form_data = ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : '';

		if ( ! empty( $form_data['settings'] ) ) {
			$email = (array) $form_data['settings']['email'];

			// New email conn.
			$new_email                    = array();
			$new_email['connection_name'] = esc_html__( 'Admin Notification', 'everest-forms' );
			$new_email                    = array_merge( $new_email, $email );

			// Unset previous email data structure.
			$email_settings = array( 'evf_send_confirmation_email', 'evf_user_to_email', 'evf_user_email_subject', 'evf_user_email_message', 'attach_pdf_to_user_email' );
			foreach ( $email_settings as $email_setting ) {
				unset( $email_setting );
			}

			// Maintain the multiple-email connections data structure.
			if ( ! isset( $form_data['settings']['email']['connection_1'] ) ) {
				$unique_connection_id           = sprintf( 'connection_%s', uniqid() );
				$form_data['settings']['email'] = array( 'connection_1' => $new_email );

				if ( isset( $email['evf_send_confirmation_email'] ) && '1' === $email['evf_send_confirmation_email'] ) {
					$form_data['settings']['email'][ $unique_connection_id ] = array(
						'connection_name'   => esc_html__( 'User Notification', 'everest-forms' ),
						'evf_to_email'      => '{field_id="' . $email['evf_user_to_email'] . '"}',
						'evf_from_name'     => $email['evf_from_name'],
						'evf_from_email'    => $email['evf_from_email'],
						'evf_reply_to'      => $email['evf_reply_to'],
						'evf_email_subject' => $email['evf_user_email_subject'],
						'evf_email_message' => $email['evf_user_email_message'],
					);
				}

				if ( isset( $email['attach_pdf_to_user_email'] ) && '1' === $email['attach_pdf_to_user_email'] ) {
					$form_data['settings']['email'][ $unique_connection_id ]['attach_pdf_to_admin_email'] = '1';
				}

				if ( isset( $email['conditional_logic_status'] ) ) {
					$form_data['settings']['email'][ $unique_connection_id ]['conditional_logic_status'] = $email['conditional_logic_status'];
					$form_data['settings']['email'][ $unique_connection_id ]['conditional_option']       = $email['conditional_option'];
					$form_data['settings']['email'][ $unique_connection_id ]['conditionals']             = array();
				}
			}

			// Update form data.
			evf()->form->update( $form_id, $form_data );
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

/**
 * Update DB Version.
 */
function evf_update_140_db_version() {
	EVF_Install::update_db_version( '1.4.0' );
}

/**
 * Delete global reCAPTCHA related options.
 */
function evf_update_144_delete_options() {
	delete_option( 'everest_forms_recaptcha_validation' );
}

/**
 * Update DB Version.
 */
function evf_update_144_db_version() {
	EVF_Install::update_db_version( '1.4.4' );
}

/**
 * Update settings option to use new renamed option for 1.4.9.
 */
function evf_update_149_db_rename_options() {
	$rename_options = array(
		'everest_forms_recaptcha_site_key'    => 'everest_forms_recaptcha_v2_site_key',
		'everest_forms_recaptcha_site_secret' => 'everest_forms_recaptcha_v2_secret_key',
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
 * Remove payment option field from all forms.
 */
function evf_update_149_no_payment_options() {
	$forms = evf_get_all_forms();

	// Loop through each forms.
	foreach ( $forms as $form_id => $form ) {
		$form_obj  = evf()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $field_id => &$field ) {
				if ( isset( $field['type'] ) && 'payment-charge-options' === $field['type'] ) {
					unset( $form_data['form_fields'][ $field_id ] );
				}
			}
		}

		// Update form data.
		evf()->form->update( $form_id, $form_data );
	}
}

/**
 * Update DB Version.
 */
function evf_update_149_db_version() {
	EVF_Install::update_db_version( '1.4.9' );
}

/**
 * Update date field type for all forms.
 */
function evf_update_150_field_datetime_type() {
	$forms = evf()->form->get_multiple( array( 'order' => 'DESC' ) );

	// Loop through each forms.
	foreach ( $forms as $form ) {
		$form_id   = isset( $form->ID ) ? $form->ID : '0';
		$form_data = ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as &$field ) {
				if ( isset( $field['type'] ) && 'date' === $field['type'] ) {
					$field['type'] = 'date-time';
				}
			}
		}

		// Update form data.
		evf()->form->update( $form_id, $form_data );
	}
}

/**
 * Update DB Version.
 */
function evf_update_150_db_version() {
	EVF_Install::update_db_version( '1.5.0' );
}

/**
 * Update DB Version.
 */
function evf_update_160_db_version() {
	EVF_Install::update_db_version( '1.6.0' );
}

/**
 * Update core capabilities.
 */
function evf_update_175_remove_capabilities() {
	global $wp_roles;

	if ( ! class_exists( 'WP_Roles' ) ) {
		return;
	}

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
	}

	$capability_types = array( 'everest_form' );

	foreach ( $capability_types as $capability_type ) {
		$capabilities[ $capability_type ] = array(
			// Post type.
			"edit_{$capability_type}",
			"read_{$capability_type}",
			"delete_{$capability_type}",
			"edit_{$capability_type}s",
			"edit_others_{$capability_type}s",
			"publish_{$capability_type}s",
			"read_private_{$capability_type}s",
			"delete_{$capability_type}s",
			"delete_private_{$capability_type}s",
			"delete_published_{$capability_type}s",
			"delete_others_{$capability_type}s",
			"edit_private_{$capability_type}s",
			"edit_published_{$capability_type}s",

			// Terms.
			"manage_{$capability_type}_terms",
			"edit_{$capability_type}_terms",
			"delete_{$capability_type}_terms",
			"assign_{$capability_type}_terms",
		);
	}

	// Remove unused core capabilities.
	foreach ( $capabilities as $cap_group ) {
		foreach ( $cap_group as $cap ) {
			$wp_roles->remove_cap( 'administrator', $cap );
		}
	}
}

/**
 * Restore draft forms to publish.
 */
function evf_update_175_restore_draft_forms() {
	$form_ids = get_posts(
		array(
			'post_type'   => 'everest_form',
			'post_status' => 'draft',
			'fields'      => 'ids',
			'numberposts' => - 1,
		)
	);

	foreach ( $form_ids as $form_id ) {
		wp_update_post(
			array(
				'ID'          => $form_id,
				'post_status' => 'publish',
			)
		);
	}
}

/**
 * Update DB Version.
 */
function evf_update_175_db_version() {
	EVF_Install::update_db_version( '1.7.5' );
}
