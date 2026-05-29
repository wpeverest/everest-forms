<?php
/**
 * Admin View: Page - Form Migrator
 *
 * @since 2.0.6
 *
 * @package EverestForms/Admin/Form_migrator
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="everest-forms-form-migrator">
	<h3><?php esc_html_e( 'Form Migrator', 'everest-forms' ); ?></h3>
	<div class="evf-form-migrator-info">
		<p><?php esc_html_e('', 'everest-forms') ?></p>
		<p class="description">
		<i class="dashicons dashicons-info"></i>
		<?php
		/* translators: %s: Form migrator notice */
		printf( esc_html__( 'Migrate forms from other plugins into Everest Forms seamlessly with just one click.', 'everest-forms' ) );
		?>
		</p>
	</div>
	<div class="evf-fm-select-popular-form">
	<?php
		if ( ! empty( $forms_status ) ) {
			echo '<select id="everest-forms-form-migrator" class="evf-enhanced-select" style="min-width: 350px;" name="form_ids[]" data-placeholder="' . esc_attr__( 'Select Form To Migrate', 'everest-forms' ) . '">';
			echo '<option value=""> '.esc_html('-- Select Form To Migrate --', 'everest-forms').'</option>';
			foreach ( $forms_status as $id => $form_status ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				$disabled ='';
				$extra_text = '';
				if( ! $form_status['installed']) {
					$disabled ='disabled';
					$extra_text = esc_html__("(Not Installed)", 'everest-forms');
				}elseif( ! $form_status['active']) {
					$disabled ='disabled';
					$extra_text = esc_html__("(Not Active)", 'everest-forms' );
				}

				echo '<option value="' . esc_attr( $form_status['slug'] ) . '" '.esc_attr( $disabled ).'>' . esc_html( $form_status['name'] ) .' '.esc_html( $extra_text ). '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>' . esc_html__( 'There are no any form migrator.', 'everest-forms' ) . '</p>';
		}
		?>
	</div>
	<div class="evf-fm-wrapper">
		<div id="evf-fm-forms-list-container" class="evf-fm-form-list-container">
		</div>
	</div>
</div>
