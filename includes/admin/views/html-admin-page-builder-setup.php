<?php
/**
 * Admin View: Builder setup
 *
 * @package EverestForms/Admin/Builder
 *
 * @var string $view
 * @var object $templates
 */

defined( 'ABSPATH' ) || exit;
$core_templates = apply_filters(
	'everest_forms_templates_core',
	array(
		'blank-form'   => array(
			'slug' => 'blank',
			'name' => __( 'Blank Form', 'everest-forms' ),
		),
		'contact-form' => array(
			'slug' => 'contact',
			'name' => __( 'Contact Form', 'everest-forms' ),
		),
		)
	);
	wp_enqueue_script( 'evf-template-controller' );
	delete_transient( 'evf_template_section' );
	delete_transient( 'evf_template_sections' );
?>
<div class ="wrap everest-forms">
	<div class="evf-loading"></div>
	<div class="everest-forms-setup">
		<div class="everest-forms-setup-header">
			<div class="everest-forms-logo">
				<svg xmlns="http://www.w3.org/2000/svg" height="32" width="32" viewBox="0 0 24 24"><path fill="#7e3bd0" d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"/></svg>
			</div>
			<h4><?php _e( 'Add New Form', 'everest-forms' ); ?></h4>
			<?php if ( apply_filters( 'everest_forms_refresh_templates', true ) ) : ?>
				<a href="<?php echo esc_url( $refresh_url ); ?>" class="page-title-action"><?php esc_html_e( 'Refresh Templates', 'everest-forms' ); ?></a>
			<?php endif; ?>
			<nav class="everest-forms-tab">
				<ul>
					<li class="everest-forms-tab-nav active">
						<a href="#" id="evf-form-all" class="everest-forms-tab-nav-link"><?php _e( 'All', 'everest-forms' ); ?></a>
					</li>
					<li class="everest-forms-tab-nav">
						<a href="#" id="evf-form-basic" class="everest-forms-tab-nav-link"><?php _e( 'Free', 'everest-forms' ); ?></a>
					</li>
					<li class="everest-forms-tab-nav">
						<a href="#" id="evf-form-pro" class="everest-forms-tab-nav-link"><?php _e( 'Premium', 'everest-forms' ); ?></a>
					</li>
				</ul>
			</nav>
		</div>
		<div class="everest-forms-form-template evf-setup-templates" data-license-type="<?php echo esc_attr( $license_plan )?>">
			<?php
			if ( empty( $templates ) ) {
				$error_string = esc_html("Please check your internet connection.", 'everest-forms' );
				echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
			} else {
				foreach ( $templates as $template ) :
					$badge = '';
					$upgrade_class = 'evf-template-select';
					$preview_link = isset( $template->preview_link ) ? $template->preview_link : '';
					$click_class = '';
					if ( ! in_array( 'free', $template->plan ) ) {
						$badge = '<span class="everest-forms-badge everest-forms-badge-success">' . __( 'Pro', 'everest-forms' ) . '</span>';
					}

					if ( 'blank' === $template->slug ) {
						$click_class = "evf-template-select";
					}

					if ( empty( $license_plan) && ! in_array( 'free', $template->plan ) ) {
						$upgrade_class = "upgrade-modal";
					}
					?>
					<div class="everest-forms-template-wrap evf-template"  id="everest-forms-template-<?php echo esc_attr( $template->slug ); ?>">
						<figure class="everest-forms-screenshot <?php echo $click_class; ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template->title ) ); ?>">
							<img src="<?php echo esc_url( $template->image ); ?>"/>
							<?php echo $badge; ?>
							<?php if ( 'blank' !== $template->slug ) : ?>
								<div class="form-action">
									<a href="#" class="everest-forms-btn everest-forms-btn-primary <?php echo esc_attr( $upgrade_class ); ?>" data-licence-plan="<?php echo esc_attr( $license_plan ); ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template->title ) ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>"><?php printf( _x( '%s', 'Template name', 'everest-forms' ), __( 'Get Started', 'everest-forms' ) ); ?></a>
									<a href="#" target="blank" class="everest-forms-btn everest-forms-btn-secondary evf-template-preview" data-preview-link="<?php echo $preview_link; ?>"><?php _e( 'Preview', 'everest-forms' ); ?></a>
								</div>
							<?php endif; ?>
						</figure>
						<div class="everest-forms-form-id-container">
							<a class="everest-forms-template-name <?php echo esc_attr( $upgrade_class ); ?>" href="#" data-licence-plan="<?php echo esc_attr( $license_plan ); ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template->title ) ); ?>"><?php echo esc_attr( $template->title ); ?></a>
						</div>
					</div>
				<?php endforeach;
		 	} ?>
		</div>
	</div>
</div>
<?php
/**
 * Prints the JavaScript templates for install admin notices.
 *
 * Template takes one argument with four values:
 *
 *     param {object} data {
 *         Arguments for admin notice.
 *
 *         @type string id        ID of the notice.
 *         @type string className Class names for the notice.
 *         @type string message   The notice's message.
 *         @type string type      The type of update the notice is for. Either 'plugin' or 'theme'.
 *     }
 *
 * @since 1.4.0
 */
function everest_forms_print_admin_notice_templates() {
	?>
	<script id="tmpl-wp-installs-admin-notice" type="text/html">
		<div <# if ( data.id ) { #>id="{{ data.id }}"<# } #> class="notice {{ data.className }}"><p>{{{ data.message }}}</p></div>
	</script>
	<script id="tmpl-wp-bulk-installs-admin-notice" type="text/html">
		<div id="{{ data.id }}" class="{{ data.className }} notice <# if ( data.errors ) { #>notice-error<# } else { #>notice-success<# } #>">
			<p>
				<# if ( data.successes ) { #>
					<# if ( 1 === data.successes ) { #>
						<# if ( 'plugin' === data.type ) { #>
							<?php
							/* translators: %s: Number of plugins */
							printf( __( '%s plugin successfully installed.', 'themegrill-demo-importer' ), '{{ data.successes }}' );
							?>
						<# } #>
					<# } else { #>
						<# if ( 'plugin' === data.type ) { #>
							<?php
							/* translators: %s: Number of plugins */
							printf( __( '%s plugins successfully installed.', 'themegrill-demo-importer' ), '{{ data.successes }}' );
							?>
						<# } #>
					<# } #>
				<# } #>
				<# if ( data.errors ) { #>
					<button class="button-link bulk-action-errors-collapsed" aria-expanded="false">
						<# if ( 1 === data.errors ) { #>
							<?php
							/* translators: %s: Number of failed installs */
							printf( __( '%s install failed.', 'themegrill-demo-importer' ), '{{ data.errors }}' );
							?>
						<# } else { #>
							<?php
							/* translators: %s: Number of failed installs */
							printf( __( '%s installs failed.', 'themegrill-demo-importer' ), '{{ data.errors }}' );
							?>
						<# } #>
						<span class="screen-reader-text"><?php _e( 'Show more details', 'themegrill-demo-importer' ); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
				<# } #>
			</p>
			<# if ( data.errors ) { #>
				<ul class="bulk-action-errors hidden">
					<# _.each( data.errorMessages, function( errorMessage ) { #>
						<li>{{ errorMessage }}</li>
					<# } ); #>
				</ul>
			<# } #>
		</div>
	</script>
	<?php
}
everest_forms_print_admin_notice_templates();
