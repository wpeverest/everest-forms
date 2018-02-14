<?php
/**
 * EverestForms Updates
 *
 * Functions for updating data, used by the background updater.
 *
 * @package EverestForms\Functions
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Update DB Version.
 */
function evf_update_100_db_version() {
	UM_Install::update_db_version( '1.0.0' );
}

/**
 * Update DB Version.
 */
function evf_update_101_db_version() {
	UM_Install::update_db_version( '1.0.1' );
}
