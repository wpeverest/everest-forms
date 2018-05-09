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
		add_filter( 'everest_forms_builder_fields_buttons', array( $this, 'form_fields' ), 20 );
		//add_filter( 'everest_forms_builder_preview', array( $this, 'everest_forms_builder_preview' ), 20, 1 );
		//add_action( 'everest_forms_builder_panel_buttons', array( $this, 'form_panels' ), 20 );
		add_action( 'everest_forms_builder_enqueues_before', array( $this, 'builder_enqueues' ) );
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

	/**
	 * Display/register additional fields available in the Pro version.
	 *
	 * @param  array $fields
	 * @return array
	 */
	public function form_fields( $fields ) {
		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			$fields['advanced']['fields'][] = array(
				'icon'  => 'evf-icon evf-icon-file-upload',
				'name'  => 'File Upload',
				'type'  => 'file-upload',
				'order' => 12,
				'class' => 'upgrade-modal',
			);
		}

		return $fields;
	}

	/**
	 * Display/register additional panels available in the Pro version.
	 *
	 * @since 1.0.0
	 */
	public function form_panels() {
		?>
		<button class="everest-forms-panel-tet-button upgrade-modal" data-panel="payments">
			<i class="fa fa-usd"></i><span><?php _e( 'Payments', 'everest-forms' ); ?></span>
		</button>
		<?php
	}

	/**
	 * Load assets for free version with the upgrade message.
	 *
	 * @since 1.2.0
	 */
	public function builder_enqueues() {
		wp_enqueue_script( 'everest-builder-upgrade', EVF()->plugin_url() . '/assets/js/admin/builder-upgrade.js', array( 'jquery', 'jquery-confirm' ), EVF_VERSION, false );

		wp_localize_script(
			'everest-builder-upgrade',
			'everest_builder_upgrade',
			array(
				'upgrade_title'   => esc_html__( 'is a PRO Feature', 'everest-forms' ),
				'upgrade_message' => esc_html__( 'We\'re sorry, %name% is not available on your plan.<br>Please upgrade to the PRO plan to unlock all these awesome features.', 'everest-forms' ),
				'upgrade_button'  => esc_html__( 'Upgrade to PRO', 'everest-forms' ),
				'upgrade_url'     => apply_filters( 'everest_forms_upgrade_url', 'https://wpeverest.com/wordpress-plugins/everest-forms/' ),
			)
		);
	}
}

new EVF_Forms_Features;
