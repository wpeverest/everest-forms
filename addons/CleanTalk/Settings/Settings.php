<?php
/**
 * CleanTalk.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\CleanTalk\Settings
 */

namespace EverestForms\Addons\CleanTalk\Settings;

/**
 * CleanTalk.
 *
 * @since 3.0.5
 */
class Settings {

	/**
	 * Constructor.
	 *
	 * @since 3.0.5
	 */
	public function __construct() {
		add_filter( 'everest_forms_misc_settings', array( $this, 'misc_settings' ), 10, 1 );
	}

	/**
	 * CleanTalk Anti-Spam Protection.
	 * 
	 ** @param $settings Misc settings.
	 */
	public function misc_settings( $settings ) {
		$count         = count( $settings ) - 2;
		$misc_settings = array(
			array(
				'title'    => esc_html__( 'Enable CleanTalk Spam Protection', 'everest-forms' ),
				'desc'     => __( 'CleanTalk spam protection desc', 'everest-forms' ),
				'id'       => 'everest_forms_enable_cleantalk_spam_protection',
				'type'     => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			array(
				'title'    => esc_html__( 'CleanTalk Methods', 'everest-forms' ),
				'desc'     => esc_html__( 'Choose the CleanTalk methods.', 'everest-forms' ),
				'id'       => 'everest_forms_clean_talk_methods',
				'default'  => 'rest_api',
				'type'     => 'radio',
				'options'  => array(
					'rest_api' => 'RestApi',
					'clean_talk_plugin' => 'CleanTalk Plugin',
				),
				'desc_tip' => true,
				'class'    => 'evf-clean-talk-method',
			),
			array(
				'title'    => esc_html__( 'Access Key', 'everest-forms' ),
				'desc'     => esc_html__( 'Enter the access key', 'everest-forms' ),
				'id'       => 'everest_forms_recaptcha_cleantalk_access_key',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true,
				'class'    => 'evf-clean-talk-access-key',
			),
		);
		array_splice( $settings, $count, 0, $misc_settings );
		return $settings;
	}
}
