<?php
/**
 * EverestForms Admin
 *
 * @package EverestForms/Admin
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Admin_Form_Builder Class.
 */
class EVF_Admin_Form_Builder {

	/**
	 * Current view (panel)
	 *
	 * @since      1.0.0
	 * @var string
	 */
	public $tab_view;

	/**
	 * Current form.
	 *
	 * @since      1.0.0
	 * @var object
	 */
	public $form;

	/**
	 * Current template information.
	 *
	 * @since      1.0.0
	 * @var array
	 */
	public $template;

	private $sec_post_id;

	/**
	 * Primary class constructor.
	 *
	 * @since      1.0.0
	 */
	public function __construct() {

		// Maybe load form builder

		$this->init();
	}

	/**
	 * Determing if the user is viewing the builder, if so, party on.
	 *
	 * @since      1.0.0
	 */
	public function init() {

		// Check what page we are on
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		if ( ! 'edit-evf-form' === $page ) {
			return;
		}

		// Load conditionally.
		if ( ! isset( $_GET['tab'], $_GET['form_id'] ) ) {
			add_action( 'everest_form_admin_form_template_page', array( $this, 'output_template' ) );
		} elseif ( isset( $_GET['tab'], $_GET['form_id'] ) ) {
			// Load form if found
			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

			$this->form = EVF()->form->get( $form_id );

			$this->tab_view = isset( $_GET['tab'] ) ? $_GET['tab'] : 'Fields';

			// Default  for new field is the setup panel
			$this->load_admin_panels();

			add_action( 'everest_form_admin_form_builder_page', array( $this, 'output' ) );

			// Provide hook for addons
			do_action( 'everest_forms_builder_init', $this->tab_view );

			do_action( 'everest_forms_builder_scripts' );
		}
	}

	public function load_admin_panels() {
		$form_panels = apply_filters( 'everest_forms_builder_panels', array(
			'EVF_Panel_Fields',
			'EVF_Panel_Settings',
		) );

		// Load form panels.
		foreach ( $form_panels as $panel ) {
			$load_panel = is_string( $panel ) ? new $panel() : $panel;
		}
	}

	/**
	 * Load the appropriate files to build the page.
	 *
	 * @since 1.0.0
	 */
	public function output() {
		$form_id                    = $this->form ? absint( $this->form->ID ) : '';
		$form_data                  = $this->form ? evf_decode( $this->form->post_content ) : false;
		$form_data['form_field_id'] = isset( $form_data['form_field_id'] ) ? $form_data['form_field_id'] : 0;

		?>
		<div id="everest-forms-builder" class="everest-forms">
			<form name="everest-forms-builder" id="everest-forms-builder-form" method="post" data-id="<?php echo $form_id; ?>">
				<input type="hidden" name="id" value="<?php echo $form_id; ?>">
				<input type="hidden" value="<?php echo( $form_data['form_field_id'] ); ?>" name="form_field_id" id="everest-forms-field-id">

				<div class="everest-forms-nav-wrapper clearfix">
					<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
						<?php do_action( 'everest_forms_builder_panel_buttons', $this->form, $this->tab_view ); ?>
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
					<?php do_action( 'everest_forms_builder_panels', $this->form, $this->tab_view ); ?>
					<div style="clear:both"></div>
				</div>
			</form>
		</div>
		<?php
	}

	public function output_template() {
		wp_enqueue_script( 'everest_forms_builder' );
		wp_enqueue_script( 'everest_forms_admin' );

		include_once( dirname( __FILE__ ) . '/views/html-admin-form-modal.php' );
		wp_enqueue_style( 'evf-form-modal-style', EVF()->plugin_url() . '/assets/css/evf-form-modal.css', array(), EVF_VERSION );

		wp_enqueue_script( 'evf-admin-form-modal', EVF()->plugin_url() . '/assets/js/admin/evf-form-modal.js', array( 'underscore', 'backbone', 'wp-util' ), EVF_VERSION );

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
		<?php
	}
}

new EVF_Admin_Form_Builder();
