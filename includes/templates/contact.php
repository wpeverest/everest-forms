<?php
/**
 * Contact form Template.
 *
 * @package EverestForms\Templates
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

$form_title               = isset( $title ) ? sanitize_text_field( $title ) : esc_html__( 'Contact Form', 'everest-forms' );
$form_template['contact'] = array(
	'form_field_id' => '1',
	'form_fields' => array(
		'lVizlNhYus-1' => array(
			'id'          => 'lVizlNhYus-1',
			'type'        => 'text',
			'label'       => 'Name',
			'description' => '',
			'required'    => '1',
			'placeholder' => '',
			'css'         => '',
		),
		'xJivsqAS2c-2' => array(
			'id'          => 'xJivsqAS2c-2',
			'type'        => 'text',
			'label'       => 'Subject',
			'description' => '',
			'required'    => '1',
			'placeholder' => '',
			'css'         => '',
		),
		'XYnMdkQDKM-3' => array(
			'id'            => 'XYnMdkQDKM-3',
			'type'          => 'email',
			'label'         => 'Email',
			'description'   => '',
			'required'      => '1',
			'placeholder'   => '',
			'default_value' => '',
			'css'           => '',
		),
		'YalaPcQ0DO-4' => array(
			'id'          => 'YalaPcQ0DO-4',
			'type'        => 'textarea',
			'label'       => 'Message',
			'description' => '',
			'placeholder' => '',
			'css'         => '',
		),
	),
	'settings' => array(
		'form_title'  => $form_title,
		'form_desc'   => '',
		'successful_form_submission_message' => get_option( 'everest_forms_successful_form_submission_message', __( 'Thanks for contacting us! We will be in touch with you shortly.', 'everest-forms' ) ),
		'redirect_to'                        => '0',
		'custom_page'                        => '2',
		'external_url'                       => '',
		'layout_class'                       => 'default',
		'form_class'                         => '',
		'submit_button_text'                 => get_option( 'everest_forms_form_submit_button_label', __( 'Submit', 'everest-forms' ) ),
		'email' => array(
			'evf_to_email'      => get_option( 'evf_to_email', get_option( 'admin_email' ) ),
			'evf_from_name'     => get_option( 'evf_from_name', evf_sender_name() ),
			'evf_from_email'    => get_option( 'evf_from_name', evf_sender_address() ),
			'evf_email_subject' => get_option( 'evf_email_subject', __( 'New Form Entry', 'everest-forms' ) ),
			'evf_email_message' => get_option( 'evf_email_message', '{all_fields}' ),
		),
	),
	'structure' => array(
		'row_1' => array(
			'grid_1' => array(
				'lVizlNhYus-1',
				'XYnMdkQDKM-3',
				'xJivsqAS2c-2',
				'YalaPcQ0DO-4',
			),
		),
	),
);
