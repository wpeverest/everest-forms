<?php
/**
 * Admin View: Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'everest_forms_sections_' . $current_tab ) || has_action( 'everest_forms_settings_' . $current_tab ) || has_action( 'everest_forms_settings_tabs_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=evf-settings' ) );
	exit;
}
?>
<div class="everest-forms">
	<form method="<?php echo esc_attr( apply_filters( 'everest_forms_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
		<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
			<?php

			foreach ( $tabs as $slug => $tab ) {
				echo '<a href="' . esc_html( admin_url( 'admin.php?page=evf-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '" data-key="' . esc_attr( $slug ) . '"><span class="' . esc_attr( $tab['icon'] ) . '"></span>' . esc_html( $tab['label'] ) . '</a>';
			}

			do_action( 'everest_forms_settings_tabs' );

			?>
		</nav>
		<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label['label'] ); ?></h1>
		<?php
		do_action( 'everest_forms_sections_' . $current_tab );

		self::show_messages();
		foreach ( $tabs as $tab_key => $tab_data ) {

			?>
			<div data-conent-key="<?php echo $tab_key; ?>"
			     class="evf-setting-tab-content <?php echo( $current_tab == $tab_key ? 'active' : '' ); ?>"> <?php
				do_action( 'everest_forms_settings_' . $tab_key );
				?>
			</div>
		<?php } ?>
		<?php
		do_action( 'everest_forms_settings_tabs_' . $current_tab );
		?>

		<div style="clear:both"></div>
		<p class="submit">
			<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
				<input name="save" class="button-primary everest-forms-save-button" type="submit"
				       value="<?php esc_attr_e( 'Save changes', 'everest-forms' ); ?>"/>
			<?php endif; ?>
			<?php wp_nonce_field( 'everest-forms-settings' ); ?>
		</p>
	</form>
</div>
