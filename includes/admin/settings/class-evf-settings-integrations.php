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
		$this->icon  = 'evf-icon evf-icon-integration';
		$this->label = __( 'Integration', 'everest-forms' );

		if ( isset( EVF()->integrations ) && EVF()->integrations->get_integrations() ) {
			parent::__construct();
		}
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sectionss() {
		global $current_section;

		$sections = array();

		if ( ! defined( 'EVF_INSTALLING' ) ) {
			$integrations = EVF()->integrations->get_integrations();

			if ( ! $current_section && ! empty( $integrations ) ) {
				$current_section = current( $integrations )->id;
			}

			if ( sizeof( $integrations ) > 1 ) {
				foreach ( $integrations as $integration ) {
					$title                                      = empty( $integration->method_title ) ? ucfirst( $integration->id ) : $integration->method_title;
					$sections[ strtolower( $integration->id ) ] = esc_html( $title );
				}
			}
		}

		return apply_filters( 'everest_forms_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;
		$integrations = EVF()->integrations->get_integrations();
		// echo '<pre>' . print_r( $integrations, true ) . '</pre>';
	?>
		<h2>Integrations</h2>
		<p>I am testing paragraph.</p>
		<div class="everest-forms-integrations-connection">
		<?php
			foreach ( $integrations as $integration ) { ?>
				<div class="everest-forms-integrations">
					<div class="integration-header-info">
						<div class="integration-status">
						</div>
						<div class="integration-desc">
							<figure class="logo">
								<img src="<?php echo $integration->icon;  ?>" alt="<?php echo $integration->method_title;  ?>" />
							</figure>
							<a class="integration-info" href="#">
								<h3><?php echo $integration->method_title;  ?></h3>
								<p><?php echo $integration->method_description;  ?></p>
							</a>
						</div>
					</div>
					<div class="integartion-action">
						<div class="toggle-button">
							<input type="checkbox">
							<span class="slide round"></span>
						</div>
						<a class="integration-setup" href="#">
							<span class="evf-icon evf-icon-setting-cog"></span>
						</a>
					</div>
				</div>
		<?php } ?>
</div>
<?php
		// $integrations = EVF()->integrations->get_integrations();

		// if ( isset( $integrations[ $current_section ] ) ) {
			// $integrations[ $current_section ]->admin_options();
		// }
	}
}

return new EVF_Settings_Integrations();
