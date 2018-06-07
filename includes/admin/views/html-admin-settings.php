<?php
/**
 * Admin View: Settings
 *
 * @package EverestForms
 */

defined( 'ABSPATH' ) || exit;

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'everest_forms_sections_' . $current_tab ) || has_action( 'everest_forms_settings_' . $current_tab ) || has_action( 'everest_forms_settings_tabs_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=evf-settings' ) );
	exit;
}
?>
<div class="wrap everest-forms">
	<form method="<?php echo esc_attr( apply_filters( 'everest_forms_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
		<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
			<?php
			foreach ( $tabs as $slug => $tab ) {
				echo '<a href="' . esc_html( admin_url( 'admin.php?page=evf-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '"><span class="' . esc_attr( $tab['icon'] ) . '"></span>' . esc_html( $tab['label'] ) . '</a>';
			}

			do_action( 'everest_forms_settings_tabs' );
			?>
		</nav>
		<div class="everest-forms-settings">
			<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label['label'] ); ?></h1>
			<?php
				do_action( 'everest_forms_sections_' . $current_tab );

				self::show_messages();

				do_action( 'everest_forms_settings_' . $current_tab );
			?>
			<p class="submit">
				<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
					<button name="save" class="button-primary everest-forms-save-button" type="submit" value="<?php esc_attr_e( 'Save Changes', 'everest-forms' ); ?>"><?php esc_html_e( 'Save Changes', 'everest-forms' ); ?></button>
				<?php endif; ?>
				<?php wp_nonce_field( 'everest-forms-settings' ); ?>
			</p>
		</div>
	</form>
</div>
