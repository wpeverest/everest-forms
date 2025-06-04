<?php
/**
 * CleanTalk.
 *
 * @since 3.2.2
 * @package EverestForms\Addons\CleanTalk\Settings
 */

namespace EverestForms\Addons\CleanTalk\Settings;

/**
 * CleanTalk.
 *
 * @since 3.2.2
 */
class Settings extends \EVF_Integration {

	/**
	 * Account status.
	 *
	 * @var [type] The account status.
	 */

	public $account_status;
	/**
	 * Constructor.
	 *
	 * @since 3.2.2
	 */
	public function __construct() {
		$this->id                 = 'clean-talk';
		$this->icon               = plugins_url( 'addons/CleanTalk/assets/images/CleanTalk.png', EVF_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'CleanTalk', 'everest-forms-pro' );
		$this->method_description = esc_html__( 'CleanTalk Integration with Everest Forms', 'everest-forms-pro' );
		$connected_lists          = get_option( 'everest_forms_integrations', array() );
		if ( ! empty( get_option( 'everest_forms_recaptcha_cleantalk_access_key' ) ) ) {
			$this->account_status = 'connected';
		} else {
			$this->account_status = '';
		}
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
				<div class="evf-connection-list">
				<!-- Toggle Row -->
				 <form method="post" action="" id="everest-forms-clean-talk-settings-form">
						<!-- Access Key -->
						<div class="evf-clean-talk-access-key">
							<div>
								<label class="evf-clean-talk-label-1" for="everest_forms_recaptcha_cleantalk_access_key"><?php echo __( 'CleanTalk Access Key', 'everest-forms' ); ?></label>
							</div>
							<input style="margin: 12px 0; width: 100%" class="evf-access-key" type="password" id="everest_forms_recaptcha_cleantalk_access_key" name="everest_forms_recaptcha_cleantalk_access_key" value="<?php echo esc_attr( get_option( 'everest_forms_recaptcha_cleantalk_access_key' ) ); ?>">
							<p style="margin-bottom:0; margin-top:0"><?php echo __( 'Enter your CleanTalk REST API key from your ', 'everest-forms' ); ?><a href="https://cleantalk.org/my/" target="_blank" rel="noopener noreferrer"><?php echo __( 'account dashboard here', 'everest-forms' ); ?></a>.</p>
						</div>
						<div class="evf-clean-talk-message" style="display: none;"></div>
					</div>
					<button style="margin-top: 12px;" type="submit" id="everest-forms-clean-talk-save-settings" class="everest-forms-btn everest-forms-btn-primary" ><?php echo __('Save Settings', 'everest-forms') ?></button>
				 </form>

				</div>
			</div>
		<?php
	}
}
