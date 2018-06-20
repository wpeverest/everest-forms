<?php
/**
 * Form panel Interface
 *
 * @package EverestForms/Interface
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF Form Panel Interface
 *
 * Functions that must be defined by form panel classes.
 *
 * @version 1.2.0
 */
interface EVF_Form_Panel_Interface {

	/**
	 * Outputs the Field panel sidebar.
	 */
	public function panel_sidebar();

	/**
	 * Outputs the Field panel content.
	 */
	public function panel_content();
}
