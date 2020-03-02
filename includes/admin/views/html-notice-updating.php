<?php
/**
 * Admin View: Notice - Updating
 *
 * @package EverestForms\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated everest-forms-message evf-connect">
	<p><strong><?php esc_html_e( 'Everest Forms data update', 'everest-forms' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background.', 'everest-forms' ); ?> <a href="<?php echo esc_url( add_query_arg( 'force_update_everest_forms', 'true', admin_url( 'admin.php?page=evf-settings' ) ) ); ?>"><?php esc_html_e( 'Taking a while? Click here to run it now.', 'everest-forms' ); ?></a></p>
</div>
