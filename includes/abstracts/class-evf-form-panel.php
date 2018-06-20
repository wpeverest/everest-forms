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
	 * Form object.
	 *
	 * @var object
	 */
	protected $form;

	/**
	 * Builder page id.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Builder page icon.
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Builder page label.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Array of form data.
	 *
	 * @var array
	 */
	protected $form_data = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$form_id         = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;
		$this->form      = evf()->form->get( $form_id );
		$this->form_data = $this->form ? evf_decode( $this->form->post_content ) : false;

		// Hooks.
		add_filter( 'everest_forms_builder_tabs_array', array( $this, 'add_builder_page' ), 20 );
	}

	/**
	 * Add this page to builder.
	 *
	 * @param  array $pages Builder pages.
	 * @return mixed
	 */
	public function add_builder_page( $pages ) {
		$pages[ $this->id ] = array(
			'icon'  => $this->icon,
			'label' => $this->label,
		);

		return $pages;
	}




}
