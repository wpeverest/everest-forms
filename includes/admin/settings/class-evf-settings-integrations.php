<?php
/**
 * EverestForms Integration Settings
 *
 * @package EverestForms\Admin
 * @version 1.2.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Integrations', false ) ) {
	return new EVF_Settings_Integrations();
}

/**
 * EVF_Settings_Integrations.
 */
class EVF_Settings_Integrations extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'integration';
		$this->label = __( 'Integration', 'everest-forms' );

		if ( isset( EVF()->integrations ) && EVF()->integrations->get_integrations() ) {
			parent::__construct();
		}
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section, $hide_save_button;

		// Hide the save button.
		$GLOBALS['hide_save_button'] = true;

		$integrations = EVF()->integrations->get_integrations();

		if ( '' === $current_section ) {
			$this->output_integrations( $integrations );
		} else {
			if ( isset( $integrations[ $current_section ] ) ) {
				$integrations[ $current_section ]->admin_options();
			}
		}
	}

	/**
	 * Handles output of the integrations page in admin.
	 *
	 * @param array $integrations Array of integrations.
	 */
	protected function output_integrations( $integrations ) {
		?>
		<h2>Integrations</h2>
		<p>I am testing paragraph.</p>
		<div class="everest-forms-integrations-connection">
			<?php
			foreach ( $integrations as $integration ) { ?>
				<div class="everest-forms-integrations">
					<div class="integration-header-info">
						<div class="integration-status">
							<span class="toggle-switch"></span>
						</div>
						<div class="integration-desc">
							<figure class="logo">
								<img src="<?php echo $integration->icon;  ?>" alt="<?php echo $integration->method_title;  ?>" />
							</figure>
							<div class="integration-info">
								<a href="<?php echo admin_url( 'admin.php?page=evf-settings&tab=integration&section=' . $integration->id ); ?>">
									<h3><?php echo $integration->method_title;  ?></h3>
								</a>
								<p><?php echo $integration->method_description; ?></p>
							</div>
						</div>
					</div>
					<div class="integartion-action">
						<a class="integration-setup" href="<?php echo admin_url( 'admin.php?page=evf-settings&tab=integration&section=' . $integration->id ); ?>">
							<span class="evf-icon evf-icon-setting-cog"></span>
						</a>
					</div>
				</div>
			<?php } ?>
		</div>
		<?php
	}
}

return new EVF_Settings_Integrations();
