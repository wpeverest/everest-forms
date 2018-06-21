<?php
/**
 * Admin View: Builder
 *
 * @package EverestForms/Admin/Builder
 */

defined( 'ABSPATH' ) || exit;

$form_data['form_field_id'] = isset( $form_data['form_field_id'] ) ? $form_data['form_field_id'] : 0;

// Get tabs for the builder panel.
$tabs = apply_filters( 'everest_forms_builder_tabs_array', array() );

?>
<div id="everest-forms-builder" class="everest-forms">
	<form name="everest-forms-builder" id="everest-forms-builder-form" method="post" data-id="<?php echo $form_id; ?>">
		<input type="hidden" name="id" value="<?php echo $form_id; ?>">
		<input type="hidden" value="<?php echo( $form_data['form_field_id'] ); ?>" name="form_field_id" id="everest-forms-field-id">

		<div class="everest-forms-nav-wrapper clearfix">
			<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
				<?php
				foreach ( $tabs as $slug => $tab ) {
					echo '<a href="#" class="evf-panel-' . esc_attr( $slug ) . '-button nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '" data-panel="' . esc_attr( $slug ) . '"><span class="' . esc_attr( $tab['icon'] ) . '"></span>' . esc_html( $tab['label'] ) . '</a>';
				}

				do_action( 'everest_forms_builder_tabs' );
				?>
			</nav>
			<div class="evf-forms-nav-right">
				<div class="evf-shortcode-field">
					<input type="text" class="large-text code" onfocus="this.select();" value="<?php printf( esc_html( '[everest_form id="%s"]' ), $_GET['form_id'] ) ?>" id="evf-form-shortcode" readonly="readonly" />
					<button id="copy-shortcode" class="everest-forms-btn help_tip dashicons dashicons-admin-page" href="#" data-tip="<?php esc_attr_e( 'Copy Shortcode!', 'everest-forms' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'everest-forms' ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'everest-forms' ); ?></span>
					</button>
				</div>
				<button name="save_form" class="everest-forms-btn everest-forms-save-button button-primary" type="button" value="<?php esc_attr_e( 'Save', 'everest-forms' ); ?>"><?php esc_html_e( 'Save', 'everest-forms' ); ?></button>
			</div>
		</div>
		<div class="evf-tab-content">
			<?php do_action( 'everest_forms_builder_output', $form, $current_tab ); ?>
		</div>
	</form>
</div>
