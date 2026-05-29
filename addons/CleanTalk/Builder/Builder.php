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
		$clean_talk_method = get_option( 'everest_forms_clean_talk_methods', 'rest_api' );
		$access_key        = get_option( 'everest_forms_recaptcha_cleantalk_access_key', '' );

		echo '<div class="everest-forms-border-container">';
		echo '<div class="everest-forms-clean-talk-setting-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'CleanTalk', 'everest-forms' ) . '</h4>';
		echo '<div>';
		echo '<button class="everest-forms-update-clean-talk-key-button ' . ( empty( $access_key ) ? 'everest-forms-hidden' : '' ) . '" data-access-key="' . esc_attr( $access_key ) . '">';
		echo esc_html__( 'Update Key', 'everest-forms' );
		echo '</button>';
		echo '<a href="https://docs.everestforms.net/docs/cleantalk/" target="_blank" class="everest-forms-learn-more-link-cleantalk">' . esc_html__( 'View Docs', 'everest-forms' ) . '</a>';
		echo '</div></div>';
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

		if (  empty( $access_key ) ) {
			echo '<div class="everest-forms-warning-container">';
			echo '<img src="' . esc_url( plugins_url( 'addons/CleanTalk/assets/images/warning.png', EVF_PLUGIN_FILE ) ) . '" alt="' . esc_attr__( 'CleanTalk', 'everest-forms' ) . '" class="everest-forms-warning-icon" />';
			echo '<p class="everest-forms-warning-text">';
			echo esc_html__( 'No CleanTalk access key found  ', 'everest-forms' );
			echo '<span class="everest-forms-warning-text-link">Add Key</span>';
			echo '</p>';
			echo '</div>';
		}
		echo '<div class="everest-forms-border-container everest-forms-cleantalk-protection-type">';
		everest_forms_panel_field(
			'select',
			'settings',
			'cleantalk_protection_type',
			$obj->form_data,
			esc_html__( 'Protection type', 'everest-forms' ),
			array(
				'default' => 'mark_as_spam',
				'tooltip' => esc_html__( "Please select the protection type. Choosing 'Mark as Spam' allows the submission but marks the entry as spam, while selecting 'Reject Submission' will prevent the form submission.", 'everest-forms' ),
				'options' => array(
					'mark_as_spam'      => esc_html__( 'Mark as Spam', 'everest-forms' ),
					'validation_failed' => esc_html__( 'Reject Submission', 'everest-forms' ),
				),
			)
		);

		echo '</div>';
		echo '</div>';

	}
}
