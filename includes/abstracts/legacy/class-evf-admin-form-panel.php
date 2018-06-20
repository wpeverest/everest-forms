<?php
/**
 * Abstract form panel
 *
 * @package    EverestForms\Abstracts
 * @deprecated 1.2.0
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract EVF_Admin_Form_Panel Class
 *
 * @deprecated 1.2.0
 */
abstract class EVF_Admin_Form_Panel {

	/**
	 * Name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Slug.
	 *
	 * @var string
	 */
	public $slug; // $id

	/**
	 * Priority order the field button should show inside the "Add Fields" tab.
	 *
	 * @var integer
	 */
	public $order = 50; // $priority

	/**
	 * If panel contains a sidebar element or is full width.
	 *
	 * @var boolean
	 */
	public $sidebar = false; // $has_sidebar

	public $form_settings;

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		$this->form_setting = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();

		// Init.
		$this->init();

		// Hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ), 15 );
		add_action( 'everest_forms_builder_panel_buttons', array( $this, 'button' ), $this->order, 2 );
		add_action( 'everest_forms_builder_panels', array( $this, 'panel_output' ), $this->order, 2 );
	}

	/**
	 * Enqueue scripts.
	 */
	public function init() {}

	/**
	 * Enqueue scripts.
	 */
	public function enqueues() {}

	/**
	 * Primary panel tab navigation.
	 *
	 * @param mixed  $form
	 * @param string $current_tab
	 */
	public function button( $form, $current_tab ) {
		$active = $current_tab == $this->slug ? 'nav-tab-active' : '';

		printf( '<a href="#" class="evf-panel-%1$s-button nav-tab %2$s" data-panel="%1$s">', $this->slug, $active );
		printf( '<span class="%s"></span>', $this->icon );
		printf( '%s</a>', $this->name ? $name : $label );
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

		echo '<a href="#" class="evf-panel-tab evf-setting-panel everest-forms-panel-sidebar-section everest-forms-panel-sidebar-section-' . esc_attr( $slug ) . $class . '" data-section="' . esc_attr( $slug ) . '">';

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
	public function panel_content() {}
}
