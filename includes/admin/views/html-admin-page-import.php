<?php
/**
 * Admin View: Page - Import
 *
 * @package EverestForms/Admin/Import
 */

defined( 'ABSPATH' ) || exit;

?>

<style>
	.everest-forms-import-form {
		background-color: inherit;
		border: none;
		box-shadow: none;	
	}
	.everest-forms-import-form .publishing-action {
		border: none;
		text-align: left;
	}
	div.divider {
		height: 1px;
		width: 100%;
		background-color: #cccccc;

	}

</style>
<div class="everest-forms-import-form everest-forms-import-form-settings">
	<h3><?php esc_html_e( 'Import Everest Forms', 'everest-forms' ); ?></h3>
	<p><?php esc_html_e( 'Select JSON file to import the form.', 'everest-forms' ); ?></p>
	<div class="everest-forms-file-upload">
		<input type="file" name="file" id="everest-forms-import" <?php esc_attr_e( 'files selected', 'everest-forms' ); ?>" accept=".json" />
		<label for="everest-forms-import"><span class="everest-forms-btn dashicons dashicons-upload">Choose File</span><span id="import-file-name"><?php esc_html_e( 'No file selected', 'everest-forms' ); ?></span></label>
	</div>
	<p class="description">
		<i class="dashicons dashicons-info"></i>
		<?php
		/* translators: %s: File format */
		printf( esc_html__( 'Only %s file is allowed.', 'everest-forms' ), '<strong>JSON</strong>' );
		?>
	</p>
	<div class="publishing-action">
		<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_import_action" name="everest-forms-import-form"><?php esc_html_e( 'Import Form', 'everest-forms' ); ?></button>
		<?php wp_nonce_field( 'everest_forms_import_nonce', 'everest-forms-import-nonce' ); ?>
	</div>
</div>
<div class="divider"></div>
<div class="everest-forms-import-form everest-forms-import-global-settings">
	<h3><?php esc_html_e( 'Import Everest Forms Global Settings', 'everest-forms' ); ?></h3>
	<p><?php esc_html_e( 'Select JSON file to import the settngs.', 'everest-forms' ); ?></p>
	<div class="everest-forms-file-upload">
		<input type="file" name="file" id="everest-forms-global-settings-import" <?php esc_attr_e( 'files selected', 'everest-forms' ); ?>" accept=".json" />
		<label for="everest-forms-global-settings-import"><span class="everest-forms-btn dashicons dashicons-upload">Choose File</span><span id="import-settings-file-name"><?php esc_html_e( 'No file selected', 'everest-forms' ); ?></span></label>
	</div>
	<p class="description">
		<i class="dashicons dashicons-info"></i>
		<?php
		/* translators: %s: File format */
		printf( esc_html__( 'Only %s file is allowed.', 'everest-forms' ), '<strong>JSON</strong>' );
		?>
	</p>
	<div class="publishing-action">
		<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_global_settings_import_action" name="everest-forms-global-settings-import"><?php esc_html_e( 'Import Settings', 'everest-forms' ); ?></button>
		<?php wp_nonce_field( 'everest_forms_global_settings_import_nonce', 'everest-forms-global-settings-import-nonce' ); ?>
	</div>
</div>
