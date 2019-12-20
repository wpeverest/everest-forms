<?php
/**
 * Admin View: Page - Export
 *
 * @package EverestForms/Admin/Export
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="everest-forms-import-form everest-forms-sm-card">
	<h3><?php echo __( 'Export Everest Forms with Settings', 'everest-forms' ); ?></h3>
	<p><?php echo __( 'Export your forms along with their settings as JSON file.', 'everest-forms' ); ?></p>
	<div class="everest-forms-file-upload">
	<?php
	$forms   = evf_get_all_forms( true );
	if ( ! empty( $forms ) ) {
			echo '<select id="everest-forms-form-export" style="min-width: 350px;" name="form_id" data-placeholder="' . esc_attr__( 'Select form', 'everest-forms' ) . '"><option value="">'.__( 'Select a form', 'everest-forms').'</option>';
			foreach ( $forms as $id => $form ) :
				echo '<option value="'.esc_attr( $id ).'">'.esc_html( $form ).'</option>';
			endforeach;
			echo '</select>';
	} else {
		echo '<p>' . esc_html__( 'You need to create a form before you can use form export.', 'everest-forms' ) . '</p>';
	}
	?>
	</div>
	<div class="publishing-action">
		<?php wp_nonce_field( 'everest_forms_export_nonce', 'everest-forms-export-nonce' ); ?>
		<button type="submit" class="everest-forms-btn everest-forms-btn-primary" name="everest-forms-export-form"><?php esc_html_e( 'Export', 'everest-forms' ); ?></button>
	</div>
</div>
