<?php
/**
 * Admin View: Page - Forms List
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'All Forms', 'everest-forms' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?&page=edit-evf-form' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'everest-forms' ); ?></a>
	<hr class="wp-header-end">
	<form id="evf-list-management" method="post">
		<input type="hidden" name="page" value="everest-forms"/>
		<?php
			$forms_table_list->views();
			$forms_table_list->search_box( __( 'Search Form', 'everest-forms' ), 'everest-forms' );
			$forms_table_list->display();

			wp_nonce_field( 'save', 'everest-forms_nonce' );
		?>
	</form>
</div>
