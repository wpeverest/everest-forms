<?php
/**
 * Admin View: Page - Import
 *
 * @package EverestForms/Admin/Import
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="everest-forms-import-form">
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=import' ) ); ?>">
		<h3><?php echo __( 'Import Everest Forms', 'everest-forms' ); ?></h3>
		<p><?php echo __( 'Import CSV file.', 'everest-forms' ); ?></p>
		<input type="file" name="file" id="everest-forms-import" <?php esc_attr_e( 'files selected', 'everest-forms' ); ?>" accept=".json" />
		<div class="publishing-action"></div>
			<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_import_action" name="everest-forms-import-form"><?php esc_html_e( 'Import', 'everest-forms' ); ?></button>
			<?php wp_nonce_field( 'everest_forms_import_nonce', 'everest-forms-import-nonce' ); ?>
		</div>
	</form>
</div>
