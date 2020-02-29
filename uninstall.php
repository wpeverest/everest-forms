<?php
/**
 * EverestForms Uninstall
 *
 * Uninstalls the plugin deletes user roles, tables, and options.
 *
 * @package EverestForms\Uninstaller
 * @version 1.0.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

wp_clear_scheduled_hook( 'everest_forms_cleanup_logs' );
wp_clear_scheduled_hook( 'everest_forms_cleanup_sessions' );

/*
 * Only remove ALL  data if EVF_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'EVF_REMOVE_ALL_DATA' ) && true === EVF_REMOVE_ALL_DATA ) {
	include_once dirname( __FILE__ ) . '/includes/class-evf-install.php';

	// Roles + caps.
	EVF_Install::remove_roles();

	// Tables.
	EVF_Install::drop_tables();

	// Pages.
	wp_trash_post( get_option( 'everest_forms_default_form_page_id' ) );

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'evf\_%';" );

	// Delete usermeta.
	$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'evf\_%';" );

	// Delete posts + data.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'everest_form' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
