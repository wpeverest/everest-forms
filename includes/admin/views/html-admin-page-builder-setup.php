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
	delete_transient( 'evf_template_section' );
	delete_transient( 'evf_template_sections' );
	echo '<pre>' . print_r( $license_plan, true ) . '</pre>';
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
						<a href="#" class="everest-forms-tab-nav-link"><?php _e( 'Free', 'everest-forms' ); ?></a>
					</li>
					<li class="everest-forms-tab-nav">
						<a href="#" class="everest-forms-tab-nav-link"><?php _e( 'Premium', 'everest-forms' ); ?></a>
					</li>
				</ul>
			</nav>
		</div>
		<div class="everest-forms-form-template evf-setup-templates">
			<?php
			foreach ( $templates as $template ) :
				$badge = '';
				$click_class = '';
				if ( ! in_array( 'free', $template->plan ) ) {
					$badge = '<span class="everest-forms-badge everest-forms-badge-success">' . __( 'Pro', 'everest-forms' ) . '</span>';
				}

				if ( 'blank' === $template->slug ) {
					$click_class = "evf-template-select";
				}
				?>
				<div class="everest-forms-template-wrap evf-template"  id="everest-forms-template-<?php echo esc_attr( $template->slug ); ?>">
					<figure class="everest-forms-screenshot <?php echo $click_class; ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template->title ) ); ?>">
						<img src="<?php echo esc_url( $template->image ); ?>"/>
						<?php echo $badge; ?>
						<?php if ( 'blank' !== $template->slug ) : ?>
							<div class="form-action">
								<a href="#" class="everest-forms-btn everest-forms-btn-primary evf-template-select" data-licence-plan="<?php echo esc_attr( $license_plan ); ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template->title ) ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>"><?php printf( _x( '%s', 'Template name', 'everest-forms' ), __( 'Get Started', 'everest-forms' ) ); ?></a>
								<a href="#" class="everest-forms-btn everest-forms-btn-secondary"><?php _e( 'Preview', 'everest-forms' ); ?></a>
							</div>
						<?php endif; ?>
					</figure>
					<div class="everest-forms-form-id-container">
						<a class="everest-forms-template-name evf-template-select" href="#" data-licence-plan="<?php echo esc_attr( $license_plan ); ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template->title ) ); ?>"><?php echo esc_attr( $template->title ); ?></a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
