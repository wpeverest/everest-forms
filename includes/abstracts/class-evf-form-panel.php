<?php
/**
 * Abstract form panel
 *
 * @package EverestForms\Abstracts
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EVF_Admin_Form_Panel', false ) ) {
	include_once dirname( EVF_PLUGIN_FILE ) . '/includes/abstracts/legacy/class-evf-admin-form-panel.php';
}

/**
 * Abstract EVF_Admin_Form_Panel Class.
 */
abstract class EVF_Form_Panel extends EVF_Admin_Form_Panel implements EVF_Form_Panel_Interface {

	/**
	 * Panel ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Panel name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Panel icon.
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * Form object.
	 *
	 * @var object
	 */
	public $form;

	/**
	 * Array of form data.
	 *
	 * @var array
	 */
	public $form_data;

	/**
	 * Priority for hooks.
	 *
	 * @var int
	 */
	public $priority = 50;

	/**
	 * Is sidebar available?
	 *
	 * @var bool
	 */
	public $has_sidebar = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$form_id         = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;
		$this->form      = evf()->form->get( $form_id );
		$this->form_data = $this->form ? evf_decode( $this->form->post_content ) : false;

		// Init.
		$this->init();

		// Hooks.
		add_action( 'everest_forms_builder_panels', array( $this, 'panel_output' ), $this->priority, 2 );
	}

	/**
	 * Hook in tabs.
	 */
	public function init() {}

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
		printf( '%s</a>', $this->name );
	}

}
