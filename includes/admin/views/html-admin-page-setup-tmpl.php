<?php
/**
 * Admin View: Builder setup
 *
 * @package EverestForms/Admin/Builder
 */

defined( 'ABSPATH' ) || exit;

$core_templates = apply_filters( 'everest_forms_templates_core', array(
	'blank-form'   => array(
		'slug' => 'blank',
		'name' => __( 'Blank Form', 'everest-forms' ),
	),
	'contact-form' => array(
		'slug' => 'contact',
		'name' => __( 'Contact Form', 'everest-forms' ),
	),
) );

?>
<div class ="wrap everest-forms">
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
					<img src="<?php echo esc_url( EVF()->plugin_url(). "/assets/images/templates/{$template['slug']}-form.jpg" ); ?>" />
					<div class="evf-template-overlay">
						<a href="#" class="evf-button evf-button-rounded evf-template-select" data-template-name-raw="<?php echo esc_attr( $template['name'] ); ?>" data-template-name="<?php printf( _x( '%s template', 'Template name', 'everest-forms' ), esc_attr( $template['name'] ) ); ?>" data-template="<?php echo esc_attr( $template['slug'] ); ?>"><?php printf( _x( 'Create a %s', 'Template name', 'everest-forms' ), esc_html( $template['name'] ) ); ?></a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="clear"></div>
	</form>
</div>
