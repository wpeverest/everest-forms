<?php
/**
 * EverestForms features
 *
 * @author   WPEverest
 * @category Classes
 * @package  EverestForms
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main EverestForms Class.
 */
class EVF_Forms_Features {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'everest_forms_form_settings_notifications', array( $this, 'form_settings_notifications' ), 8, 1 );

		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			add_filter( 'everest_forms_fields', array( $this, 'form_fields' ) );
		}
	}

	/**
	 * Load additional fields available in the Pro version.
	 *
	 * @param  array $fields Registered form fields.
	 * @return array
	 */
	public function form_fields( $fields ) {
		$pro_fields = array(
			'EVF_Field_File_Upload',
			'EVF_Field_Hidden',
			'EVF_Field_Phone',
			'EVF_Field_Address',
			'EVF_Field_Country',
			'EVF_Field_City',
			'EVF_Field_Zip',
			'EVF_Field_Single_Item',
			'EVF_Field_Multiple_Item',
			'EVF_Field_Total',
			'EVF_Field_Password',
			'EVF_Field_HTML',
			'EVF_Field_Title'
		);

		return array_merge( $fields, $pro_fields );
	}

	/**
	 * Form notification settings, supports multiple notifications.
	 *
	 * @since      1.0.0
	 *
	 * @param object $settings
	 */
	public function form_settings_notifications( $settings ) {
		// Fetch next ID and handle backwards compatibility.
		if ( empty( $settings->form_data['settings']['notifications'] ) ) {
			$settings->form_data['settings']['notifications'][1]['email']          = ! empty( $settings->form_data['settings']['notification_email'] ) ? $settings->form_data['settings']['notification_email'] : '{
				admin_email}';
			$settings->form_data['settings']['notifications'][1]['subject']        = ! empty( $settings->form_data['settings']['notification_subject'] ) ? $settings->form_data['settings']['notification_subject'] : sprintf( __( 'New %s Entry', 'everest-forms' ), $settings->form->post_title );
			$settings->form_data['settings']['notifications'][1]['sender_name']    = ! empty( $settings->form_data['settings']['notification_fromname'] ) ? $settings->form_data['settings']['notification_fromname'] : get_bloginfo( 'name' );
			$settings->form_data['settings']['notifications'][1]['sender_address'] = ! empty( $settings->form_data['settings']['notification_fromaddress'] ) ? $settings->form_data['settings']['notification_fromaddress'] : '{
				admin_email}';
			$settings->form_data['settings']['notifications'][1]['replyto']        = ! empty( $settings->form_data['settings']['notification_replyto'] ) ? $settings->form_data['settings']['notification_replyto'] : '';
		}
		$id = 1;

		echo ' < div class="everest-forms-panel-content-section-title" > ';
		_e( 'Notifications', 'everest-forms' );
		echo ' </div > ';

		everest_forms_panel_field(
			'select',
			'settings',
			'notification_enable',
			$settings->form_data,
			__( 'Notifications', 'everest-forms' ),
			array(
				'default' => '1',
				'options' => array(
					'1' => __( 'On', 'everest-forms' ),
					'0' => __( 'Off', 'everest-forms' ),
				),
			)
		);

		echo ' < div class="everest-forms-notification" > ';

		echo '<div class="everest-forms-notification-header" > ';
		echo '<span > ' . __( 'Default Notification', 'everest-forms' ) . ' </span > ';
		echo '</div > ';


		everest_forms_panel_field(
			'text',
			'notifications',
			'subject',
			$settings->form_data,
			__( 'Email Subject', 'everest-forms' ),
			array(
				'default'    => sprintf( _x( 'New Entry: %s', 'Form name', 'everest-forms' ), $settings->form->post_title ),
				'smarttags'  => array(
					'type' => 'all',
				),
				'parent'     => 'settings',
				'subsection' => $id,
			)
		);
		echo ' </div > ';
	}
}

new EVF_Forms_Features();
