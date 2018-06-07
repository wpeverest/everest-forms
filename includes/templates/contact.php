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
		'name' => array(
			'id'          => 'name',
			'type'        => 'text',
			'label'       => 'Name',
			'meta-key'    => 'name',
			'description' => '',
			'required'    => '1',
			'placeholder' => '',
			'css'         => '',
		),
		'email' => array(
			'id'            => 'email',
			'type'          => 'email',
			'label'         => 'Email',
			'meta-key'      => 'email',
			'description'   => '',
			'required'      => '1',
			'placeholder'   => '',
			'default_value' => '',
			'css'           => '',
		),
		'subject' => array(
			'id'          => 'subject',
			'type'        => 'text',
			'label'       => 'Subject',
			'meta-key'    => 'subject',
			'description' => '',
			'required'    => '1',
			'placeholder' => '',
			'css'         => '',
		),
		'message' => array(
			'id'          => 'message',
			'type'        => 'textarea',
			'label'       => 'Message',
			'meta-key'    => 'message',
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
			'evf_to_email'      => get_option( 'admin_email' ),
			'evf_from_name'     => get_bloginfo( 'name', 'display' ),
			'evf_from_email'    => get_option( 'admin_email' ),
			'evf_email_subject' => __( 'New Form Entry', 'everest-forms' ),
			'evf_email_message' => '{all_fields}',
		),
	),
	'structure' => array(
		'row_1' => array(
			'grid_1' => array(
				'name',
				'email',
				'subject',
				'message',
			),
		),
	),
);
