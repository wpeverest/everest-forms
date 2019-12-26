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

?>
	<!-- <?php if ( apply_filters( 'everest_forms_refresh_templates', true ) ) : ?>
		<a href="<?php echo esc_url( $refresh_url ); ?>" class="page-title-action"><?php esc_html_e( 'Refresh Templates', 'everest-forms' ); ?></a>
	<?php endif; ?> -->

<!-- <div class ="wrap everest-forms">
	<form id="everest-forms" class="everest-forms-setup" name="everest-forms-setup" method="post">
		<div class="everest-forms-setup-form-name">
			<span class="title"><?php _e( 'Form Name', 'everest-forms' ); ?></span>
			<input type="text" id="everest-forms-setup-name" class="widefat everest-forms-setup-name" placeholder="<?php _e( 'Enter your form name here&hellip;', 'everest-forms' ); ?>">
		</div>
		<div class="evf-setup-title">
			<?php esc_html_e( 'Select A Template', 'everest-forms' ); ?>
			<p class="desc">
				<?php esc_html_e( 'To speed up the process, you can select from one of our pre-made templates listed below:', 'everest-forms' ); ?>
			</p>
		</div>
		<div class="evf-setup-templates">
			<?php foreach ( $core_templates as $template ) : ?>
				<div class="evf-template" id="everest-forms-template-<?php echo esc_attr( $template['slug'] ); ?>">
					<img src="<?php echo esc_url( EVF()->plugin_url() . "/assets/images/templates/{$template['slug']}-form.jpg" ); ?>" />
					<div class="evf-template-overlay">
						<a href="#" class="evf-button evf-button-rounded evf-template-select" data-template-name-raw="<?php echo esc_attr( $template['name'] ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template['name'] ) ); ?>" data-template="<?php echo esc_attr( $template['slug'] ); ?>"><?php printf( _x( 'Create a %s', 'Template name', 'everest-forms' ), esc_html( $template['name'] ) ); ?></a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="clear"></div>
	</form>
</div> -->

<div class ="wrap everest-forms">
	<div class="everest-forms-setup">
		<div class="everest-forms-setup-header">
			<div class="everest-forms-logo">
				<svg xmlns="http://www.w3.org/2000/svg" height="32" width="32" viewBox="0 0 24 24"><path fill="#7e3bd0" d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"/></svg>
			</div>
			<h4>Add New Form</h4>
			<nav class="everest-forms-tab">
				<ul>
					<li class="everest-forms-tab-nav active">
						<a href="#" class="everest-forms-tab-nav-link">Free Templates</a>
					</li>
					<li class="everest-forms-tab-nav">
						<a href="#" class="everest-forms-tab-nav-link">Premium Templates</a>
					</li>
				</ul>
			</nav>
		</div>
		<div class="everest-forms-form-template">

		</div>
	</div>
</div>
