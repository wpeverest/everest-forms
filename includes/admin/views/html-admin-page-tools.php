<?php
/**
 * Admin View: Page - Status
 *
 * @package EverestForms/Admin/Logs
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.WP.GlobalVariablesOverride
$tabs        = apply_filters(
	'everest_forms_admin_status_tabs',
	array(
		'import'               => __( 'Import', 'everest-forms' ),
		'export'               => __( 'Export', 'everest-forms' ),
		'form_migrator'        => __( 'Form Migrator', 'everest-forms' ),
		'system_info'          => __( 'System Info', 'everest-forms' ),
		'roles_and_permission' => __( 'Roles and Permission', 'everest-forms' ),
	)
);
$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( wp_unslash( $_REQUEST['tab'] ) ) : 'import'; // phpcs:ignore WordPress.Security.NonceVerification

if ( 'yes' === get_option( 'everest_forms_enable_log', 'no' ) ) {
	$tabs['logs'] = __( 'Logs', 'everest-forms' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}


?>
<div class="everest-forms">
	<div class="everest-forms-settings">
		<div class="everest-forms-settings-wrapper">
			<header class="everest-forms-header">
				<div class="everest-forms-header--top">
					<div class="everest-forms-header--top-logo">
						<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/images/icons/Everest-forms-Logo.png' ); ?>"
							alt="">
					</div>
				</div>
				<div class="everest-forms-header--nav">
					<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
						<?php
						foreach ( $tabs as $slug => $label ) {
							?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=' . $slug ) ); ?>"
							class="nav-tab evf-nav__link <?php echo ( $current_tab === $slug ? 'nav-tab-active is-active' : '' ); ?>">
							<span class="evf-nav__link-icon">
								<?php echo evf_file_get_contents( '/assets/images/tools-icons/' . $slug . '.svg' ); //phpcs:ignore ?>
							</span>
							<span class="evf-nav__link-label">
								<p>
									<?php echo esc_html( $label ); ?>
								</p>
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<path stroke="#383838" stroke-linecap="round" stroke-linejoin="round"
										stroke-width="2" d="m9 18 6-6-6-6" />
								</svg>
							</span>
						</a>
							<?php
						}
						?>
						<button id="evf-settings-collapse" class="nav-tab evf-nav__link">
							<span class="evf-nav-icon">
								<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/images/icons/collapse-line.svg' ); ?>"
									alt="">
							</span>
							<span class="evf-nav__link-label">
								<?php esc_html_e( 'Collapse Menu', 'everest-forms' ); ?>
							</span>
						</button>
					</nav>
				</div>
			</header>
			<div class="everest-forms-settings-container">
				<div class="everest-forms-settings-main">
					<div class="everest-forms-options-header">
						<div class="everest-forms-options-header--top">
							<?php
							foreach ( $tabs as $slug => $label ) {
								if ( $current_tab === $slug ) {
									echo  '<span class="evf-forms-options-header-header--top-icon">' . evf_file_get_contents( '/assets/images/tools-icons/' . $slug . '.svg' ) . '</span>'; //phpcs:ignore
								}
							}
							?>
							<h3><?php echo esc_html( $tabs[ $current_tab ] ); ?></h3>

						</div>
					</div>
					<?php
					switch ( $current_tab ) {
						case 'logs':
							EVF_Admin_Tools::status_logs();
							break;
						case 'import':
							EVF_Admin_Tools::import();
							break;
						case 'export':
							EVF_Admin_Tools::export();
							break;
						case 'system_info':
							EVF_Admin_Tools::setting();
							break;
						case 'form_migrator':
							EVF_Admin_Tools::form_migrator();
							break;
						case 'payment_log':
							if ( ! class_exists( 'EVF_Pro_Admin_Tools' ) ) {
								return;
							}
							\EVF_Pro_Admin_Tools::payment_log();
							break;
						case 'api_logs':
							if ( ! class_exists( 'EVF_Pro_Admin_Tools' ) ) {
								return;
							}
							\EVF_Pro_Admin_Tools::api_logs();
							break;
						case 'roles_and_permission':
							EVF_Admin_Tools::roles_and_permission();
							break;
						default:
							if ( array_key_exists( $current_tab, $tabs ) && has_action( 'everest_forms_admin_status_content_' . $current_tab ) ) {
								do_action( 'everest_forms_admin_status_content_' . $current_tab );
							} else {
								EVF_Admin_Tools::import();
							}
							break;
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
