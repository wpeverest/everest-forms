<?php
/**
 * Abstract form panel
 *
 * @package EverestForms\Abstracts
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

// @deprecated since 1.2.0
if ( ! class_exists( 'EVF_Admin_Form_Panel', false ) ) {
	include_once dirname( EVF_PLUGIN_FILE ) . '/includes/abstracts/legacy/abstract-evf-admin-form-panel.php';
}

/**
 * Abstract EVF_Admin_Form_Panel Class
 *
 * @version 1.0.0
 * @author  WPEverest
 */
abstract class EVF_Form_Panel extends EVF_Admin_Form_Panel {}
