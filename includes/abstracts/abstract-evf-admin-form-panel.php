<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract EVF_Admin_Form_Panel Class
 *
 * @version 1.0.0
 * @author  WPEverest
 */
abstract class EVF_Admin_Form_Panel {

	/**
	 * Full name of the panel.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Font Awesome Icon used for the editor button.
	 *
	 * @var mixed
	 */
	public $icon = false;

	/**
	 * Priority order the field button should show inside the "Add Fields" tab.
	 *
	 * @var integer
	 */
	public $order = 50;

	/**
	 * If panel contains a sidebar element or is full width.
	 *
	 * @var boolean
	 */
	public $sidebar = false;

	/**
	 * Contains form object if we have one.
	 *
	 * @var object
	 */
	public $form;

	/**
	 * Contains array of the form data (post_content).
	 *
	 * @var array
	 */
	public $form_data;

	/**
	 * Primary class constructor.
	 */
	public $form_setting;

	public function __construct() {
		// Load form if found.
		$form_id            = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;
		$this->form         = EVF()->form->get( $form_id );
		$this->form_data    = $this->form ? evf_decode( $this->form->post_content ) : false;
		$this->form_setting = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();
		$this->init();

		// Hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ), 15 );
		add_action( 'everest_forms_builder_panel_buttons', array( $this, 'button' ), $this->order, 2 );
		add_action( 'everest_forms_builder_panels', array( $this, 'panel_output' ), $this->order, 2 );
	}

	/**
	 * Hook in tabs.
	 */
	public function init() {}

	/**
	 * Enqueue assets
	 */
	public function enqueues() {}

	/**
	 * Primary panel button in the left panel navigation.
	 *
	 * @param mixed  $form
	 * @param string $view
	 */
	public function button( $form, $view ) {
		$active = $view == $this->slug ? 'active' : '';

		printf( '<li class="evf-panel-%s-button" data-panel="%s">', $this->slug, $this->slug );
		printf( '<a href="#" class="%s">', $active );
		printf( '<span class="%s"></span>', $this->icon );
		printf( '%s</a>', $this->name );
		echo '</li>';
	}

	/**
	 * Outputs the contents of the panel.
	 *
	 * @param object $form
	 * @param string $view
	 */
	public function panel_output( $form, $view ) {
		$active = $view == $this->slug ? 'active' : '';

		$wrap = $this->sidebar ? 'everest-forms-panel-sidebar-content' : 'everest-forms-panel-full-content';

		printf( '<div class="everest-forms-panel %s" id="everest-forms-panel-%s">', $active, $this->slug );

		printf( '<div class="%s">', $wrap );

		if ( true == $this->sidebar ) {

			echo '<div class="everest-forms-panel-sidebar">';

			do_action( 'everest_forms_builder_before_panel_sidebar', $this->form, $this->slug );

			$this->panel_sidebar();

			do_action( 'everest_forms_builder_after_panel_sidebar', $this->form, $this->slug );

			echo '</div>';
		}

		echo '<div class="everest-forms-panel-content-wrap">';
		echo '<div class="everest-forms-panel-content">';

		do_action( 'everest_forms_builder_before_panel_content', $this->form, $this->slug );

		$this->panel_content();

		do_action( 'everest_forms_builder_after_panel_content', $this->form, $this->slug );

		echo '</div></div></div></div>';
	}

	/**
	 * Outputs the panel's sidebar if we have one.
	 */
	public function panel_sidebar() {}

	/**
	 * Outputs panel sidebar sections.
	 */
	public function panel_sidebar_section( $name, $slug, $icon = '' ) {

		$class = '';
		$class .= $slug == 'default' ? ' default' : '';
		$class .= ! empty( $icon ) ? ' icon' : '';

		echo '<a href="#" class="evf-setting-panel everest-forms-panel-sidebar-section everest-forms-panel-sidebar-section-' . esc_attr( $slug ) . $class . '" data-section="' . esc_attr( $slug ) . '">';

		if ( ! empty( $icon ) ) {
			echo '<img src="' . esc_url( $icon ) . '">';
		}

		echo esc_html( $name );

		echo '<i class="dashicons dashicons-arrow-right-alt2 everest-forms-toggle-arrow"></i>';

		echo '</a>';
	}

	/**
	 * Outputs the panel's primary content.
	 *
	 * @since      1.0.0
	 */
	public function panel_content() {
	}
}
