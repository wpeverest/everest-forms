<?php
/**
 * Admin View: Page - Forms List
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php _e( 'All Forms', 'everest-forms' ); ?> <a
			href="<?php echo 'admin.php?&page=edit-evf-form';?>"
			class="add-new-h2"><?php _e( 'Add New', 'everest-forms' ); ?></a></h1>
	<form id="evf-list-management" method="post">
		<input type="hidden" name="page" value="everest-forms"/>
		<?php
		$evf_form_list->views();
		$evf_form_list->search_box( __( 'Search Form', 'everest-forms' ), 'everest-forms' );
		$evf_form_list->display();

		wp_nonce_field( 'save', 'everest-forms_nonce' );
		?>
	</form>
</div>
