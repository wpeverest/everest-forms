<?php
/**
 * Admin View: Page - Status
 *
 * @package EverestForms/Admin/Logs
 */

defined( 'ABSPATH' ) || exit;

$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'import';
$tabs        = array(
	'import' => __( 'Import', 'everest-forms' ),
	'export' => __( 'Export', 'everest-forms' ),
	'logs' => __( 'Logs', 'everest-forms' ),
);
$tabs        = apply_filters( 'everest_forms_admin_status_tabs', $tabs );
?>
<div class="wrap everest-forms">
	<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
		<?php
			foreach ( $tabs as $name => $label ) {
				echo '<a href="' . admin_url( 'admin.php?page=evf-tools&tab=' . $name ) . '" class="nav-tab ';
				if ( $current_tab == $name ) {
					echo 'nav-tab-active';
				}
				echo '">' . $label . '</a>';
			}
		?>
	</nav>
	<h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>
	<?php
		switch ( $current_tab ) {
			case "logs" :
				EVF_Admin_Tools::status_logs();
			break;
			case "import" :
				EVF_Admin_Tools::import();
			break;
			case "export" :
				EVF_Admin_Tools::export();
			break;
			default :
					if ( array_key_exists( $current_tab, $tabs ) && has_action( 'everest_forms_admin_status_content_' . $current_tab ) ) {
						do_action( 'everest_forms_admin_status_content_' . $current_tab );
					} else {
						EVF_Admin_Tools::import();
					}
			break;
		}
	?>
</div>
