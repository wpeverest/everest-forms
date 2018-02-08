<?php
/**
 * Admin View: Notice - Update
 *
 * @package EverestForms\Admin\Notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated everest-forms-message evf-connect">
	<p><strong><?php esc_html_e( 'Everest Forms data update', 'everest-forms' ); ?></strong> &#8211; <?php esc_html_e( 'We need to update your store database to the latest version.', 'everest-forms' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( add_query_arg( 'do_update_everest_forms', 'true', admin_url( 'admin.php?page=evf-settings' ) ) ); ?>" class="evf-update-now button-primary"><?php esc_html_e( 'Run the updater', 'everest-forms' ); ?></a></p>
</div>
<script type="text/javascript">
	jQuery( '.evf-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'everest-forms' ) ); ?>' );
	});
</script>
