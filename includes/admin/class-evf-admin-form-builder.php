<?php
/**
 * EverestForms Admin Builder Class
 *
 * @package EverestForms\Form Builder
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form builder class.
 */
class EVF_Admin_Form_Builder {

	/**
	 * Load the appropriate files to build the page.
	 */
	public static function output() {
		global $current_tab;

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

		if ( $form_id && 'fields' === $current_tab ) {
			self::output_builder( $form_id, $current_tab );
		} else {
			self::output_template();
		}
	}

	/**
	 * Output builder page.
	 *
	 * @param id $form_id Form ID.
	 */
	public static function output_builder( $form_id, $current_tab ) {
		$form                       = EVF()->form->get( $form_id );
		$form_id                    = $form ? absint( $form->ID ) : $form_id;
		$form_data                  = $form ? evf_decode( $form->post_content ) : false;
		$form_data['form_field_id'] = isset( $form_data['form_field_id'] ) ? $form_data['form_field_id'] : 0;

		?>
		<div id="everest-forms-builder" class="everest-forms">
			<form name="everest-forms-builder" id="everest-forms-builder-form" method="post" data-id="<?php echo $form_id; ?>">
				<input type="hidden" name="id" value="<?php echo $form_id; ?>">
				<input type="hidden" value="<?php echo( $form_data['form_field_id'] ); ?>" name="form_field_id" id="everest-forms-field-id">

				<div class="everest-forms-nav-wrapper clearfix">
					<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
						<?php do_action( 'everest_forms_builder_panel_buttons', $form, $current_tab ); ?>
					</nav>
					<div class="evf-forms-nav-right">
						<div class="evf-shortcode-field">
							<input type="text" class="large-text code" onfocus="this.select();" value="<?php printf( esc_html( '[everest_form id="%s"]' ), $_GET['form_id'] ) ?>" id="evf-form-shortcode" readonly="readonly" />
							<button id="copy-shortcode" class="evf-btn dashicons dashicons-admin-page" href="#" data-tip="<?php esc_attr_e( 'Copied!', 'everest-forms' ); ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'everest-forms' ); ?></span>
							</button>
						</div>
						<button name="save_form" class="button-primary everest-forms-save-button" type="button" value="<?php esc_attr_e( 'Save', 'everest-forms' ); ?>"><?php esc_html_e( 'Save', 'everest-forms' ); ?></button>
					</div>
				</div>
				<div class="evf-tab-content">
					<?php do_action( 'everest_forms_builder_panels', $form, $current_tab ); ?>
					<div style="clear:both"></div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Output new form template.
	 */
	public static function output_template() {
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

		include_once( dirname( __FILE__ ) . '/views/html-admin-form-modal.php' );

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
		<?php
	}
}
