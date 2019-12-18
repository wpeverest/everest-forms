<?php
/**
 * Admin View: Page - Export
 *
 * @package EverestForms/Admin/Export
 */

defined( 'ABSPATH' ) || exit;

?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=export' ) ); ?>">
	<?php
	$forms   = evf_get_all_forms( true );
	if ( ! empty( $forms ) ) {
			echo '<select id="everest-forms-form-export" style="min-width: 350px;" name="form_id" data-placeholder="' . esc_attr__( 'Select form', 'everest-forms' ) . '">';
			foreach ( $forms as $id => $form ) :
				echo '<option value="'.esc_attr( $id ).'">'.esc_html( $form ).'</option>';
			endforeach;
			echo '</select>';
		echo '</span>';
	} else {
		echo '<p>' . esc_html__( 'You need to create a form before you can use form export.', 'everest-forms' ) . '</p>';
	}
	?>
	<br>
	<?php wp_nonce_field( 'everest_forms_export_nonce', 'everest-forms-export-nonce' ); ?>
	<button type="submit" name="everest-forms-export-form"><?php esc_html_e( 'Export', 'everest-forms' ); ?></button>
</form>
