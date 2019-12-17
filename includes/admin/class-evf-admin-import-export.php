<?php
/**
 * Import/Export page
 *
 * @package EverestForms/Admin/Import Export
 * @since 1.5.11
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Import_Export Class.
 */
class EVF_Admin_Import_Export {

	/**
	 * Handles output of the import export page in admin.
	 */
	public static function output() {
		include_once( dirname( __FILE__ ) . '/views/html-admin-page-import-export.php' );
	}
}
