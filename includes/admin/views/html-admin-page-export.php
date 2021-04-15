<?php
/**
 * Admin View: Page - Export
 *
 * @package EverestForms/Admin/Export
 */

defined( 'ABSPATH' ) || exit;

?>
<style>
	.everest-forms-export-form {
		background-color: inherit;
		border: none;
		box-shadow: none;	
	}
	.everest-forms-export-form .publishing-action {
		border: none;
		text-align: left;
	}
	div.divider {
		height: 1px;
		width: 100%;
		background-color: #cccccc;
	}

</style>

<div class="everest-forms-export-form">
	<h3><?php esc_html_e( 'Export individual form settings', 'everest-forms' ); ?></h3>
	<p><?php esc_html_e( 'Export your forms along with their settings as JSON file.', 'everest-forms' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=export' ) ); ?>">
		<?php
		$forms = evf_get_all_forms( true );
		if ( ! empty( $forms ) ) {
			echo '<select id="everest-forms-form-export" style="min-width: 350px;" name="form_id" data-placeholder="' . esc_attr__( 'Select form', 'everest-forms' ) . '"><option value="">' . esc_html__( 'Select a form', 'everest-forms' ) . '</option>';
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
			<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest-forms-export-form-action" name="everest-forms-export-form"><?php esc_html_e( 'Export', 'everest-forms' ); ?></button>
		</div>
	</form>
</div>

<div class="divider"></div>

<div class="everest-forms-export-form everest-forms-export-settings">
	<h3><?php esc_html_e( 'Export global settings', 'everest-forms' ); ?></h3>
	<p><?php esc_html_e( 'Export your global settings as JSON file.', 'everest-forms' ); ?></p>
	<?php
	if ( defined( 'EFP_VERSION' ) ) {
		?>
		<p><?php esc_html_e( 'Integration settings will not be exported as they require authentication.', 'everest-forms' ); ?></p>
	<?php } ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=export' ) ); ?>">
		<div class="publishing-action">
			<?php wp_nonce_field( 'everest_forms_global_export_nonce', 'everest-forms-global-export-nonce' ); ?>
			<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest-forms-global-setting-export" name="everest-forms-global-setting-export"><?php esc_html_e( 'Export', 'everest-forms' ); ?></button>
		</div>
	</form>
</div>
