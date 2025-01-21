<?php
/**
 * Admin View: Page - Export
 *
 * @package EverestForms/Admin/Export
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="everest-forms-tabs-wrapper">
	<ul class="everest-forms-tabs">
		<li class="active" data-tab="export-forms-tab"><?php esc_html_e( 'Export Forms', 'everest-forms' ); ?></li>
		<?php if ( defined( 'EFP_PLUGIN_FILE' ) ) { ?>
		<li data-tab="export-entries-tab"><?php esc_html_e( 'Export Entries', 'everest-forms' ); ?></li>
		<?php } ?>
	</ul>

	<div class="everest-forms-tab-content active" id="export-forms-tab">
		<div class="everest-forms-export-form">
			<h3><?php esc_html_e( 'Export Everest Forms with Settings', 'everest-forms' ); ?></h3>
			<p><?php esc_html_e( 'Export your forms along with their settings as JSON file.', 'everest-forms' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=export' ) ); ?>">
				<?php
				$forms = evf_get_all_forms( true, false );
				if ( ! empty( $forms ) ) {
					echo '<select id="everest-forms-form-export" class="evf-enhanced-select" style="min-width: 350px;" name="form_ids[]" data-placeholder="' . esc_attr__( 'Select Form(s)', 'everest-forms' ) . '" multiple>';
					foreach ( $forms as $id => $form ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $form ) . '</option>';
					}
					echo '</select>';
				} else {
					echo '<p>' . esc_html__( 'You need to create a form before you can use form export.', 'everest-forms' ) . '</p>';
				}
				?>
				<div class="publishing-action">
					<?php wp_nonce_field( 'everest_forms_export_nonce', 'everest-forms-export-nonce' ); ?>
					<button type="submit"
						class="everest-forms-btn everest-forms-btn-primary everest-forms-export-form-action"
						name="everest-forms-export-form"><?php esc_html_e( 'Export', 'everest-forms' ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<div class="everest-forms-tab-content" id="export-entries-tab">
		<?php do_action( 'html_admin_page_export_entries' ); ?>
	</div>
</div>
