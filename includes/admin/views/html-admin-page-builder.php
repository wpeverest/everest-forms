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
	<div class="everest-forms-overlay">
		<div class="everest-forms-overlay-content">
			<svg xmlns="http://www.w3.org/2000/svg" id="Bk5ao7MMX" viewBox="0 0 301 154"><style>@-webkit-keyframes r1U56i7MzQ_Animation{0%,to{opacity:.5}50%{opacity:1}}@keyframes r1U56i7MzQ_Animation{0%,to{opacity:.5}50%{opacity:1}}@-webkit-keyframes HkVqTomfGX_Animation{0%,83.33%,to{opacity:.5}33.33%{opacity:1}}@keyframes HkVqTomfGX_Animation{0%,83.33%,to{opacity:.5}33.33%{opacity:1}}@-webkit-keyframes H1G56i7GGm_Animation{0%,66.67%,to{opacity:.5}16.67%{opacity:1}}@keyframes H1G56i7GGm_Animation{0%,66.67%,to{opacity:.5}16.67%{opacity:1}}#Bk5ao7MMX *{-webkit-animation-duration:.6s;animation-duration:.6s;-webkit-animation-iteration-count:infinite;animation-iteration-count:infinite;-webkit-animation-timing-function:cubic-bezier(0,0,1,1);animation-timing-function:cubic-bezier(0,0,1,1)}#H1G56i7GGm_HyOMAQfzm{-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box}</style><g id="H1e9TjXff7" data-name="Layer 2"><g id="S1Wc6j7zMQ" data-name="Layer 1"><path fill="#5891ff" style="-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box;-webkit-animation-name:H1G56i7GGm_Animation;animation-name:H1G56i7GGm_Animation" d="M160.12 154H12.66A12.65 12.65 0 0 1 2.4 134l74-101.9a12.64 12.64 0 0 1 20.54 0l14.79 19.82L170.4 134a12.64 12.64 0 0 1-10.28 20z"/><path d="M158.79 153.56H50.46A13.1 13.1 0 0 1 43.59 133l65.12-85.06 60.72 84.95a13.08 13.08 0 0 1-10.64 20.67z" opacity=".1"/><path fill="#50abe8" style="-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box;-webkit-animation-name:HkVqTomfGX_Animation;animation-name:HkVqTomfGX_Animation" d="M261.06 154H63.29a12.65 12.65 0 0 1-10-20.33L152.49 5a12.64 12.64 0 0 1 20.1 0l45.63 59.13 52.9 69.6A12.64 12.64 0 0 1 261.06 154z"/><path d="M258.38 154.45h-81.64c-8-2.25-12.17-12.22-8.05-19.92L215.47 61l53 72.35c6.32 8.65.38 21.1-10.09 21.1z" opacity=".1"/><path fill="#65eaff" style="-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box;-webkit-animation-name:r1U56i7MzQ_Animation;animation-name:r1U56i7MzQ_Animation" d="M186.49 154h102.74a12.65 12.65 0 0 0 10.57-19.58l-51.19-77.13a12.64 12.64 0 0 0-21.11 0L176 134.4a12.64 12.64 0 0 0 10.49 19.6z"/></g></g></svg>
			<span class="loading"><?php esc_html_e( 'Loading&hellip;', 'everest-forms' ); ?></span>
		</div>
	</div>
	<form name="everest-forms-builder" id="everest-forms-builder-form" method="post" data-id="<?php echo absint( $form_id ); ?>">
		<input type="hidden" name="id" value="<?php echo absint( $form_id ); ?>">
		<input type="hidden" value="<?php echo absint( $form_data['form_field_id'] ); ?>" name="form_field_id" id="everest-forms-field-id">

		<div class="everest-forms-nav-wrapper clearfix">
			<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
				<?php
				foreach ( $tabs as $slug => $tab ) {
					echo '<a href="#" class="evf-panel-' . esc_attr( $slug ) . '-button nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '" data-panel="' . esc_attr( $slug ) . '"><span class="evf-nav-icon ' . esc_attr( $slug ) . '"></span>' . esc_html( $tab['label'] ) . '</a>';
				}

				do_action( 'everest_forms_builder_tabs' );
				?>
			</nav>
			<div class="evf-forms-nav-right">
				<div class="evf-shortcode-field">
					<input type="text" class="large-text code" onfocus="this.select();" value="<?php printf( esc_html( '[everest_form id="%s"]' ), absint( wp_unslash( $_GET['form_id'] ) ) ); ?>" id="evf-form-shortcode" readonly="readonly" />
					<button id="copy-shortcode" class="everest-forms-btn help_tip dashicons copy-shortcode" href="#" data-tip="<?php esc_attr_e( 'Copy Shortcode!', 'everest-forms' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'everest-forms' ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'everest-forms' ); ?></span>
					</button>
				</div>
				<button name="save_form" class="everest-forms-btn everest-forms-save-button button-primary" type="button" value="<?php esc_attr_e( 'Save', 'everest-forms' ); ?>"><?php esc_html_e( 'Save', 'everest-forms' ); ?></button>
			</div>
		</div>
		<div class="evf-tab-content">
			<?php foreach ( $tabs as $slug => $tab ) : ?>
				<div id="everest-forms-panel-<?php echo esc_attr( $slug ); ?>" class="everest-forms-panel<?php echo $current_tab === $slug ? ' active' : ''; ?>">
					<div class="everest-forms-panel-<?php echo $tab['sidebar'] ? 'sidebar-content' : 'full-content'; ?>">
						<?php if ( $tab['sidebar'] ) : ?>
							<div class="everest-forms-panel-sidebar">
								<?php do_action( 'everest_forms_builder_sidebar_' . $slug ); ?>
							</div>
						<?php endif; ?>
						<div class="panel-wrap everest-forms-panel-content-wrap">
							<div class="everest-forms-panel-content">
								<?php do_action( 'everest_forms_builder_content_' . $slug ); ?>
							</div>
							<?php do_action( 'everest_forms_builder_after_content_' . $slug ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<?php do_action( 'everest_forms_builder_output' ); ?>
		</div>
	</form>
</div>
