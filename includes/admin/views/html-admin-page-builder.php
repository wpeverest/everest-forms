<?php
/**
 * Admin View: Builder
 *
 * @package EverestForms/Admin/Builder
 */

defined( 'ABSPATH' ) || exit;

$form_data['form_field_id'] = isset( $form_data['form_field_id'] ) ? $form_data['form_field_id'] : 0;
$form_data['form_enabled']  = isset( $form_data['form_enabled'] ) ? $form_data['form_enabled'] : 1;

// Get tabs for the builder panel.
$tabs = apply_filters( 'everest_forms_builder_tabs_array', array() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride

// Get preview link.
$preview_link = add_query_arg(
	array(
		'form_id'     => absint( $form_data['id'] ),
		'evf_preview' => 'true',
	),
	home_url()
);

?>
<div id="everest-forms-builder" class="everest-forms">
	<div class="everest-forms-overlay">
		<div class="everest-forms-overlay-content">
		<svg id="eUYBWjcnFJs1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 500 500" shape-rendering="geometricPrecision" text-rendering="geometricPrecision">
			<style>
			<![CDATA[
			#eUYBWjcnFJs5_ts {
			animation: eUYBWjcnFJs5_ts__ts 2000ms linear infinite normal forwards
			}

			@keyframes eUYBWjcnFJs5_ts__ts {
			0% {
				transform: translate(250.000015px, 185.300003px) scale(1, 1)
			}

			50% {
				transform: translate(250.000015px, 185.300003px) scale(0.8, 0.8)
			}

			100% {
				transform: translate(250.000015px, 185.300003px) scale(1, 1)
			}
			}
			]]>
			</style>
			<g transform="translate(1.439678 0)">
			<text dx="0" dy="0" font-family="&quot;eUYBWjcnFJs1:::Montserrat&quot;" font-size="72" font-weight="600" transform="translate(97.506642 430.341)">
			<tspan y="0" font-weight="600">
				<![CDATA[

			Loading

			]]>
			</tspan>
			</text>
			</g>
			<g id="eUYBWjcnFJs5_ts" transform="translate(250.000015,185.300003) scale(1,1)">
			<g transform="translate(-250.000015,-185.300003)">
			<path d="M249.9,324c76.7.1,138.8-62.1,138.8-138.8-.1-75.4-63.2-138.5-138.6-138.6-76.7-.1-138.8,62.1-138.8,138.8.1,75.5,63.2,138.6,138.6,138.6Z" fill="#fff" />
			<g>
				<path d="M294.3,127.2h-28.2l8.6,14.5h28.2l-8.6-14.5Z" fill="#5317aa" />
				<path d="M312,156.3h-28.2l9.1,14.5h28.2L312,156.3Z" fill="#5317aa" />
				<path d="M311.5,229h-4.1-12.7-90.4l45-73.6l18.6,30h-4.1-14.5l-8.6,14.5h8.6h5.9h38.6l-44.5-71.8-70.9,115.4h12.7h116.3h13.2L311.5,229Z" fill="#5317aa" />
			</g>
			</g>
			</g>
			<style>
			<![CDATA[
			@font-face {
			font-family: 'eUYBWjcnFJs1:::Montserrat';
			font-style: normal;
			font-weight: 600;
			src: url(data:font/ttf;charset=utf-8;base64,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) format('truetype');
			}
			]]>
			</style>
		</svg>
		</div>
	</div>
	<form id="everest-forms-builder-form" name="everest-forms-builder" method="post" data-id="<?php echo absint( $form_id ); ?>">
		<input type="hidden" name="id" value="<?php echo absint( $form_id ); ?>">
		<input type="hidden" name="form_enabled" value="<?php echo absint( $form_data['form_enabled'] ); ?>">
		<input type="hidden" value="<?php echo absint( $form_data['form_field_id'] ); ?>" name="form_field_id" id="everest-forms-field-id">

		<div class="everest-forms-nav-wrapper clearfix">
			<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
				<div class="everest-forms-logo">
							<img src="<?php echo esc_url( plugin_dir_url( EVF_PLUGIN_FILE ) . 'assets/images/everest-forms-logo.png' ); ?>" alt="<?php esc_attr_e( 'Everest Forms logo', 'everest-forms' ); ?>">
				</div>
				<?php
				foreach ( $tabs as $slug => $tab ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					echo '<a href="#" class="evf-panel-' . esc_attr( $slug ) . '-button nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '" data-panel="' . esc_attr( $slug ) . '"><span class="evf-nav-icon ' . esc_attr( $slug ) . '"></span>' . esc_html( $tab['label'] ) . '</a>';
				}

				do_action( 'everest_forms_builder_tabs' );
				?>
			</nav>
			<div class="evf-forms-nav-right">
				<div class="evf-shortcode-field">
					<input type="text" class="large-text code" onfocus="this.select();" value="<?php printf( esc_html( '[everest_form id="%s"]' ), isset( $_GET['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification ?>" id="evf-form-shortcode" readonly="readonly" />
					<button id="copy-shortcode" class="everest-forms-btn help_tip dashicons copy-shortcode" href="#" data-tip="<?php esc_attr_e( 'Copy Shortcode!', 'everest-forms' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'everest-forms' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 25">
						<path fill-rule="evenodd" d="M3.033 3.533c.257-.257.605-.4.968-.4h9A1.368 1.368 0 0 1 14.369 4.5v1a.632.632 0 0 0 1.263 0v-1a2.632 2.632 0 0 0-2.631-2.632H4A2.632 2.632 0 0 0 1.368 4.5v9A2.631 2.631 0 0 0 4 16.131h1a.632.632 0 0 0 0-1.263H4A1.368 1.368 0 0 1 2.631 13.5v-9c0-.363.144-.711.401-.968Zm6.598 7.968A1.37 1.37 0 0 1 11 10.132h9c.756 0 1.368.613 1.368 1.369v9c0 .755-.612 1.368-1.368 1.368h-9A1.368 1.368 0 0 1 9.63 20.5v-9ZM11 8.869A2.632 2.632 0 0 0 8.368 11.5v9A2.632 2.632 0 0 0 11 23.131h9a2.632 2.632 0 0 0 2.63-2.631v-9A2.632 2.632 0 0 0 20 8.87h-9Z" clip-rule="evenodd"/>
					</svg>
						<span class="screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'everest-forms' ); ?></span>
					</button>
				</div>
				<a class="everest-forms-btn everest-forms-preview-button" href="<?php echo esc_url( $preview_link ); ?>" rel="bookmark" target="_blank"><?php esc_html_e( 'Preview', 'everest-forms' ); ?></a>
				<button name="embed_form" data-form_id="<?php echo esc_html( isset( $_GET['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) ) : 0 ); ?>" class="everest-forms-btn everest-forms-embed-button" type="button" value="<?php esc_attr_e( 'Embed', 'everest-forms' ); ?>"><?php esc_html_e( 'Embed', 'everest-forms' ); ?></button>
				<button name="save_form" class="everest-forms-btn everest-forms-save-button" type="button" value="<?php esc_attr_e( 'Save', 'everest-forms' ); ?>"><?php esc_html_e( 'Save', 'everest-forms' ); ?></button>
			</div>
		</div>
		<div class="evf-tab-content">
			<?php foreach ( $tabs as $slug => $tab ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride ?>
				<div id="everest-forms-panel-<?php echo esc_attr( $slug ); ?>" class="everest-forms-panel<?php echo $current_tab === $slug ? ' active' : ''; ?>">
					<div class="everest-forms-panel-<?php echo $tab['sidebar'] ? 'sidebar-content' : 'full-content'; ?>">
						<?php if ( $tab['sidebar'] ) : ?>
							<div class="everest-forms-panel-sidebar">
								<?php do_action( 'everest_forms_builder_sidebar_' . $slug ); ?>
								<button id="evf-collapse" class="close">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										<path fill="#6B6B6B" d="M16.5 22a1.003 1.003 0 0 1-.71-.29l-9-9a1 1 0 0 1 0-1.42l9-9a1.004 1.004 0 1 1 1.42 1.42L8.91 12l8.3 8.29A.999.999 0 0 1 16.5 22Z"/>
									</svg>
								</button>
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
<script type="text/html" id="tmpl-everest-forms-field-preview-choices">
	<# if ( data.settings.choices_images ) { #>
		<ul class="widefat primary-input everest-forms-image-choices">
			<# _.each( data.order, function( choiceID, key ) {  #>
				<li class="everest-forms-image-choices-item<# if ( 1 === data.settings.choices[choiceID].default ) { print( ' everest-forms-selected' ); } #>">
					<label>
						<span class="everest-forms-image-choices-image">
							<# if ( ! _.isEmpty( data.settings.choices[choiceID].image ) ) { #>
								<img src="{{ data.settings.choices[choiceID].image }}" alt="{{ data.settings.choices[choiceID].label }}"<# if ( data.settings.choices[choiceID].label ) { #> title="{{ data.settings.choices[choiceID].label }}"<# } #>>
							<# } else { #>
								<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/images/everest-forms-placeholder.png' ); ?>" alt="{{ data.settings.choices[choiceID].label }}"<# if ( data.settings.choices[choiceID].label ) { #> title="{{ data.settings.choices[choiceID].label }}"<# } #>>
							<# } #>
						</span>
						<input type="{{ data.type }}" disabled<# if ( 1 === data.settings.choices[choiceID].default ) { print( ' checked' ); } #>>
						<span class="everest-forms-image-choices-label">{{{ data.settings.choices[choiceID].label }}} <# if(( 'payment-checkbox' === data.settings.type ) || ( 'payment-multiple' === data.settings.type )) { print ( ' - ' + data.amountFilter( evf_data, data.settings.choices[choiceID].value )) }#></span>
					</label>
				</li>
			<# }) #>
		</ul>
	<# } else { #>
		<ul class="widefat primary-input">
			<# _.each( data.order, function( choiceID, key ) {  #>
				<li>
					<input type="{{ data.type }}" disabled<# if ( 1 === data.settings.choices[choiceID].default ) { print( ' checked' ); } #>>{{{ data.settings.choices[choiceID].label }}} <# if(( 'payment-checkbox' === data.settings.type ) || ( 'payment-multiple' === data.settings.type )) { print ( ' - ' + data.amountFilter( evf_data, data.settings.choices[choiceID].value )) }#>
				</li>
			<# }) #>
		</ul>
	<# } #>
</script>
