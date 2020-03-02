<?php
/**
 * EverestForms Integration Settings
 *
 * @package EverestForms\Admin
 * @version 1.3.0
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
		$this->label = esc_html__( 'Integration', 'everest-forms' );

		if ( isset( evf()->integrations ) && evf()->integrations->get_integrations() ) {
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

		$integrations = evf()->integrations->get_integrations();

		if ( '' === $current_section ) {
			$this->output_integrations( $integrations );
		} else {
			if ( isset( $integrations[ $current_section ] ) ) {
				$integrations[ $current_section ]->output_integration();
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
		<h2><?php esc_html_e( 'Integrations', 'everest-forms' ); ?></h2>
		<div class="everest-forms-integrations-connection">
			<?php foreach ( $integrations as $integration ) : ?>
				<div class="everest-forms-integrations">
					<div class="integration-header-info">
						<div class="integration-status">
							<span class="toggle-switch-outer <?php echo esc_attr( $integration->account_status ); ?>"></span>
						</div>
						<div class="integration-detail">
							<figure class="logo">
								<img src="<?php echo esc_url( $integration->icon ); ?>" alt="<?php echo esc_attr( $integration->method_title ); ?>" />
							</figure>
							<div class="integration-info">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration&section=' . $integration->id ) ); ?>">
									<h3><?php echo esc_html( $integration->method_title ); ?></h3>
								</a>
								<p><?php echo esc_html( $integration->method_description ); ?></p>
							</div>
						</div>
					</div>
					<div class="integartion-action">
						<a class="integration-setup" href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=integration&section=' . $integration->id ) ); ?>">
							<span class="evf-icon evf-icon-setting-cog"></span>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

return new EVF_Settings_Integrations();
