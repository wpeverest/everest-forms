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
class Settings extends \EVF_Integration {

	/**
	 * Constructor.
	 *
	 * @since 3.0.5
	 */
	public function __construct() {
		$this->id                 = 'clean-talk';
		$this->icon               = plugins_url( 'addons/CleanTalk/assets/images/CleanTalk.png', EVF_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'CleanTalk', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'CleanTalk Integration with Everest Forms', 'everest-forms-pro' );
	}

	/**
	 * Output Integration.
	 */
	public function output_integration() {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration' ) ); ?>" class="everest-forms-integration-back-button">
			<span><?php echo esc_html__( 'Back', 'everest-forms-pro' ); ?></span>
		</a>
		<div class="everest-forms-integration-content">
			<div class="integration-addon-detail">
			<div class="evf-integration-info-header">
					<figure class="evf-integration-logo">
						<img src="<?php echo esc_attr( $this->icon ); ?>" alt="<?php echo esc_attr( 'CleanTalk' ); ?>">
					</figure>
					<div class="integration-info">
						<h3><?php echo $this->method_title; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></h3>
					</div>
				</div>
				<p><?php echo $this->method_description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></p>
			</div>

			<div class="integration-connection-detail">
				<div class="evf-account-connect">
					<p style="font-size: 16px; font-weight: 500; margin: 0">Set Up CleanTalk Integration</p>
				</div>
				<div class="evf-connection-list">
				<!-- Toggle Row -->
				 <form method="post" action="" id="everest-forms-clean-talk-settings-form">
					<div class="evf-settings-row" style="display: flex; gap: 12px">
						<div class="evf-toggle-section">
						<span class="everest-forms-toggle-form">
						<input type="checkbox" class="widefat" id="everest_forms_enable_cleantalk_spam_protection" value="yes" <?php checked( 'yes', get_option('everest_forms_enable_cleantalk_spam_protection') ) ?>>
						<span class="slider round"></span>
						</span>
					</div>
						<label class="" style="font-size: 13px; font-weight: 400" for="everest_forms_enable_cleantalk_spam_protection">Enable CleanTalk Spam Protection</label>

					</div>

					<!-- Radio Buttons -->
					<div id="evf-clean-talk-section-container evf-settings-row" class="<?php echo "yes" !== get_option('everest_forms_enable_cleantalk_spam_protection') ? 'everest-forms-hidden' : ''  ?>">
						<div class="evf-section">
							<p style="font-size: 16px; font-weight: 500; margin: 12px 0">CleanTalk Methods</p>
							<div class="evf-radio-flex" style="display:flex; margin: 12px 0; gap:24px; align-items: center">
								<label>
									<input style="font-size: 13px; font-weight: 400" type="radio" name="everest_forms_clean_talk_methods" value="clean_talk_plugin" <?php checked( 'clean_talk_plugin', get_option( 'everest_forms_clean_talk_methods' ) ); ?>>
									CleanTalk Plugin
								</label>
								<label>
									<input type="radio" name="everest_forms_clean_talk_methods" value="rest_api" <?php checked( 'rest_api', get_option( 'everest_forms_clean_talk_methods' ) ); ?>>
									CleanTalk RestApi
								</label>
							</div>
						</div>

						<!-- Access Key -->
						<div class="evf-clean-talk-access-key <?php echo "clean_talk_plugin" === get_option('everest_forms_clean_talk_methods') ? 'everest-forms-hidden' : ''  ?>">
							<label style="font-size: 16px; font-weight: 500" class="evf-label" for="everest_forms_recaptcha_cleantalk_access_key">Access Key</label><br>
							<input style="margin: 12px 0; width: 100%" class="evf-access-key" type="password" id="everest_forms_recaptcha_cleantalk_access_key" name="everest_forms_recaptcha_cleantalk_access_key" value="<?php echo esc_attr( get_option( 'everest_forms_recaptcha_cleantalk_access_key' ) ); ?>">
						</div>
					</div>
					<input type="submit" id="everest-forms-clean-talk-save-settings" class="everest-forms-btn everest-forms-btn-primary" value="<?php esc_attr_e( 'Save Settings', 'everest-forms' ); ?>">
				 </form>

				</div>
			</div>
		</div>
		<?php
	}
}
