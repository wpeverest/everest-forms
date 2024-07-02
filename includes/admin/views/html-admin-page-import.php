<?php
/**
 * Admin View: Page - Import
 *
 * @package EverestForms/Admin/Import
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="everest-forms-import-form">
	<h3><?php esc_html_e( 'Import Everest Forms', 'everest-forms' ); ?></h3>
	<p><?php esc_html_e( 'Select JSON file to import the form.', 'everest-forms' ); ?></p>
	<div class="everest-forms-file-upload">
	<input type="file" name="file" id="everest-forms-import" <?php esc_attr_e( 'files selected', 'everest-forms' ); ?>" accept=".json" />
		<label for="everest-forms-import"><span class="everest-forms-btn dashicons dashicons-upload"><?php esc_html_e( 'Choose File', 'everest-forms' ); ?></span><span id="import-file-name"><?php esc_html_e( 'No file selected', 'everest-forms' ); ?></span></label>
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
<div class="everest-forms-import-entries-wrapper">
	<h3><?php esc_html_e( 'Import Entries', 'everest-forms' ); ?></h3>
	<div class="evf-form-and-csv-upload">
		<div class="everest-forms-upload-csv-container">
		<?php
			$forms = evf_get_all_forms( false );
		if ( empty( $forms ) ) {
			echo '<div id="message" class="error inline everest-froms-import_notice"><p><strong>You must have form to import entries.</strong></p></div>';
		}
		?>
	<p><?php esc_html_e( 'Select form to import the entries.', 'everest-forms' ); ?></p>
	<?php
	if ( empty( $forms ) ) {
		echo "<select class='evf-enhanced-select' style='min-width: 350px;' name='form_id' id='everest-forms-import-entries'>";
		echo '<option value="">' . esc_html__( 'No form found', 'everest-forms' ) . '</option>';
		echo '</select>';
		return;
	} else {
		echo "<select class='evf-enhanced-select' style='min-width: 350px;' name='form_id' id='everest-forms-import-entries'>";
		foreach ( $forms as $form_id => $form_name ) {
			echo "<option value='" . esc_attr( $form_id ) . "'>" . esc_html( $form_name ) . '</option>';
		}
		echo '</select>';
	}
	?>
	<p><?php esc_html_e( 'Select csv file to import the entries.', 'everest-forms' ); ?></p>

	<div class="everest-forms-file-upload">
	<input type="file" name="file" id="everest-forms-import-csv" <?php esc_attr_e( 'files selected', 'everest-forms' ); ?>" accept=".csv" />
		<label for="everest-forms-import"><span class="everest-forms-btn dashicons dashicons-upload"><?php esc_html_e( 'Choose File', 'everest-forms' ); ?></span><span id="import-file-name-entry"><?php esc_html_e( 'No file selected', 'everest-forms' ); ?></span></label>
	</div>
	<p class="description">
		<i class="dashicons dashicons-info"></i>
		<?php
		/* translators: %s: File format */
		printf( esc_html__( 'Only %s file is allowed.', 'everest-forms' ), '<strong>CSV</strong>' );
		?>
	</p>
	<div class="publishing-action">
		<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_import_entries" name="everest-forms-import-entries"><?php esc_html_e( 'Map CSV', 'everest-forms' ); ?></button>
		<?php wp_nonce_field( 'everest_forms_import_nonce', 'everest-forms-import-nonce' ); ?>
	</div>
	</div>
</div>
</div>
