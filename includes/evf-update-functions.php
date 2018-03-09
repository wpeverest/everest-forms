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
	EVF_Install::update_db_version( '1.0.0' );
}

/**
 * Update DB Version.
 */
function evf_update_101_db_version() {
	EVF_Install::update_db_version( '1.0.1' );
}

/**
 * Update DB Version.
 */
function evf_update_102_db_version() {
	EVF_Install::update_db_version( '1.0.2' );
}

/**
 * Update DB Version.
 */
function evf_update_103_db_version() {
	EVF_Install::update_db_version( '1.0.3' );
}

/**
 * Update DB Version.
 */
function evf_update_110_db_version() {
	EVF_Install::update_db_version( '1.1.0' );
}
