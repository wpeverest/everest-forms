<?php
/**
 * Admin View: Notice - Update
 *
 * @package EverestForms\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

$update_url = wp_nonce_url(
	add_query_arg( 'do_update_everest_forms', 'true', admin_url( 'admin.php?page=evf-settings' ) ),
	'evf_db_update',
	'evf_db_update_nonce'
);
?>
<div id="message" class="updated everest-forms-message evf-connect">
	<p>
		<strong><?php esc_html_e( 'Everest Forms database update required', 'everest-forms' ); ?></strong>
	</p>
	<p>
		<?php esc_html_e( 'Everest Forms has been updated! To keep things running smoothly, we have to update your database to the newest version. The database update process runs in the background and may take a little while, so please be patient.', 'everest-forms' ); ?>
	</p>
	<p class="submit">
		<a href="<?php echo esc_url( $update_url ); ?>" class="evf-update-now button-primary">
			<?php esc_html_e( 'Update Everest Forms Database', 'everest-forms' ); ?>
		</a>
	</p>
</div>
<script type="text/javascript">
	jQuery( '.evf-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'everest-forms' ) ); ?>' );
	});
</script>
