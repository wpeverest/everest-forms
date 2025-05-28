<?php
/**
 * CleanTalk.
 *
 * @since 3.2.2
 * @package EverestForms\Addons\CleanTalk\Builder
 */

namespace EverestForms\Addons\CleanTalk\Builder;

/**
 * CleanTalk.
 *
 * @since 3.2.2
 */
class Builder {

	/**
	 * Constructor.
	 *
	 * @since 3.2.2
	 */
	public function __construct() {
		add_action( 'everest_forms_inline_cleantalk_settings', array( $this, 'add_inline_clean_talk_settings' ) );
	}

	/**
	 * Inline settings.
	 *
	 * @param [type] $obj
	 */
	public function add_inline_clean_talk_settings( $obj ) {
		$settings = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();

		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'CleanTalk', 'everest-forms' ) . '</h4>';
		everest_forms_panel_field(
			'toggle',
			'settings',
			'cleantalk',
			$obj->form_data,
			esc_html__( 'Enable CleanTalk anti-spam protection', 'everest-forms' ),
			array(
				'default' => '0',
			)
		);

		$clean_talk_method = get_option( 'everest_forms_clean_talk_methods', 'rest_api' );
		$access_key        = get_option( 'everest_forms_recaptcha_cleantalk_access_key', '' );

		error_log( print_r( $access_key, true ) );
		echo '<div class="everest-forms-border-container everest-forms-cleantalk-protection-type">';
		everest_forms_panel_field(
			'text',
			'settings',
			'clean_talk_access_key',
			$access_key,
			esc_html__( 'CleanTalk Access Key', 'everest-forms' ),
			array(
				'default' => $access_key,
				'tooltip' => esc_html__( 'CleanTalk Access Key', 'everest-forms' ),
			)
		);
		echo '</div>';
		echo '<div class="everest-forms-border-container everest-forms-cleantalk-protection-type">';
		everest_forms_panel_field(
			'select',
			'settings',
			'cleantalk_protection_type',
			$obj->form_data,
			esc_html__( 'Protection type', 'everest-forms' ),
			array(
				'default' => 'validation_failed',
				'tooltip' => esc_html__( "Please select the protection type. Choosing 'Mark as Spam' allows the submission but marks the entry as spam, while selecting 'Make the form submission as failed' will prevent the form submission.", 'everest-forms' ),
				'options' => array(
					'validation_failed' => esc_html__( 'Make the form submission as failed', 'everest-forms' ),
					'mark_as_spam'      => esc_html__( 'Mark as Spam', 'everest-forms' ),
				),
			)
		);

		echo '</div>';
		echo '</div>';

	}
}
