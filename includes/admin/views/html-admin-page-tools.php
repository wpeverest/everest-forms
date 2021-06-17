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
		'import' => __( 'Import', 'everest-forms' ),
		'export' => __( 'Export', 'everest-forms' ),
	)
);
$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( wp_unslash( $_REQUEST['tab'] ) ) : 'import'; // phpcs:ignore WordPress.Security.NonceVerification

if ( 'yes' === get_option( 'everest_forms_enable_log', 'no' ) ) {
	$tabs['logs'] = __( 'Logs', 'everest-forms' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

?>
<div class="wrap everest-forms">
	<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
		<?php
		foreach ( $tabs as $slug => $label ) {
			echo '<a href="' . esc_html( admin_url( 'admin.php?page=evf-tools&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '"><span class="evf-nav-icon ' . esc_attr( $slug ) . '"></span>' . esc_html( $label ) . '</a>';
		}
		?>
	</nav>
	<div class="everest-forms-tools">
		<h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>
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
