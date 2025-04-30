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
		$this->icon               = '';
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
					<p><?php echo $this->method_description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></p>
				</div>
			</div>

			<div class="integration-connection-detail">
				<div class="evf-account-connect">
					<h3>Set Up CleanTalk Integration</h3>
				</div>
				<div class="evf-connection-list">
					<style>
					.evf-settings-row {
						display: flex;
						align-items: center;
						gap: 12px;
						margin-bottom: 20px;
					}

					.evf-switch {
						position: relative;
						display: inline-block;
						width: 46px;
						height: 24px;
					}

					.evf-switch input {
						opacity: 0;
						width: 0;
						height: 0;
					}

					.evf-slider {
						position: absolute;
						cursor: pointer;
						top: 0;
						left: 0;
						right: 0;
						bottom: 0;
						background-color: #ccc;
						transition: .4s;
						border-radius: 24px;
					}

					.evf-slider:before {
						position: absolute;
						content: "";
						height: 18px;
						width: 18px;
						left: 3px;
						bottom: 3px;
						background-color: white;
						transition: .4s;
						border-radius: 50%;
					}

					.evf-switch input:checked + .evf-slider {
						background-color: #7c3aed;
					}

					.evf-switch input:checked + .evf-slider:before {
						transform: translateX(22px);
					}

					.evf-label {
						font-weight: 500;
						margin: 0;
					}

					.evf-section {
						margin-bottom: 25px;
					}

					.evf-radio-flex {
						display: flex;
						gap: 20px;
						align-items: center;
					}

					.evf-radio-flex label {
						display: flex;
						align-items: center;
						font-weight: 500;
						gap: 6px;
					}

					.evf-access-key {
						width: 300px;
						max-width: 100%;
						padding: 8px;
						border: 1px solid #ccc;
						border-radius: 5px;
					}
				</style>

				<!-- Toggle Row -->
				 <form method="post" action="" id="everest-forms-clean-talk-settings-form">
					<div class="evf-settings-row">
						<div class="evf-toggle-section">
						<span class="everest-forms-toggle-form">
						<input type="checkbox" class="widefat" id="everest_forms_enable_cleantalk_spam_protection" value="yes" <?php checked( 'yes', get_option('everest_forms_enable_cleantalk_spam_protection') ) ?>>
						<span class="slider round"></span>
						</span>
					</div>
						<label class="evf-label" for="everest_forms_enable_cleantalk_spam_protection">Enable CleanTalk Spam Protection</label>

					</div>

					<!-- Radio Buttons -->
					<div id="evf-clean-talk-section-container" class="<?php echo "yes" !== get_option('everest_forms_enable_cleantalk_spam_protection') ? 'everest-forms-hidden' : ''  ?>">
						<div class="evf-section">
							<p class="evf-label">CleanTalk Methods</p>
							<div class="evf-radio-flex">
								<label>
									<input type="radio" name="everest_forms_clean_talk_methods" value="clean_talk_plugin" <?php checked( 'clean_talk_plugin', get_option( 'everest_forms_clean_talk_methods' ) ); ?>>
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
							<label class="evf-label" for="everest_forms_recaptcha_cleantalk_access_key">Access Key</label><br>
							<input class="evf-access-key" type="text" id="everest_forms_recaptcha_cleantalk_access_key" name="everest_forms_recaptcha_cleantalk_access_key" value="<?php echo esc_attr( get_option( 'everest_forms_recaptcha_cleantalk_access_key' ) ); ?>">
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
