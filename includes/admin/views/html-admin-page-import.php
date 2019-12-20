<?php
/**
 * Admin View: Page - Import
 *
 * @package EverestForms/Admin/Import
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="everest-forms-import-form everest-forms-sm-card">
	<h3><?php echo __( 'Import Everest Forms', 'everest-forms' ); ?></h3>
	<p><?php echo __( 'Select JSON file to import the form.', 'everest-forms' ); ?></p>
	<div class="everest-forms-file-upload">
		<input type="file" name="file" id="everest-forms-import" <?php esc_attr_e( 'files selected', 'everest-forms' ); ?>" accept=".json" />
		<label for="everest-forms-import"><span class="everest-forms-btn dashicons dashicons-upload">Choose File</span><span id="import-file-name"><?php echo __('No file selected', 'everest-forms'); ?></span></label>
	</div>
	<p class="description"><i class="dashicons dashicons-info"></i><?php echo __( 'Only ' . '<strong>' . 'JSON' . '</strong>' . ' file is allowed.', 'everest-forms' ); ?></p>
	<div class="publishing-action">
		<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_import_action" name="everest-forms-import-form"><?php esc_html_e( 'Import Form', 'everest-forms' ); ?></button>
		<?php wp_nonce_field( 'everest_forms_import_nonce', 'everest-forms-import-nonce' ); ?>
	</div>
</div>
