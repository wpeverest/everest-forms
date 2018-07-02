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
 * Abstract EVF_Admin_Form_Panel Class
 */
abstract class EVF_Form_Panel extends EVF_Admin_Form_Panel implements EVF_Form_Panel_Interface {

	/**
	 * Panel ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Icon for the panel.
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * Panel title.
	 *
	 * @var string
	 */
	public $title;
}
