<?php
/**
 * File responsible for sdk files loading.
 *
 * @package     ThemeGrillSDK
 * @copyright   Copyright (c) 2025, ThemeGrill
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */

namespace ThemeGrillSDK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$products = apply_filters( 'themegrill_sdk_products', [] );

$themegrill_library_path = __DIR__;
$files_to_load           = [
	$themegrill_library_path . '/src/Loader.php',
	$themegrill_library_path . '/src/Product.php',

	$themegrill_library_path . '/src/Common/AbstractModule.php',
	$themegrill_library_path . '/src/Common/ModuleFactory.php',

	$themegrill_library_path . '/src/Modules/ScriptLoader.php',
	$themegrill_library_path . '/src/Modules/Rollback.php',
	$themegrill_library_path . '/src/Modules/UninstallFeedback.php',
	$themegrill_library_path . '/src/Modules/Notification.php',
	$themegrill_library_path . '/src/Modules/Logger.php',
	$themegrill_library_path . '/src/Modules/Announcements.php',
];

$files_to_load = array_merge( $files_to_load, apply_filters( 'themegrill_sdk_required_files', [] ) );

foreach ( $files_to_load as $file ) {
	if ( is_file( $file ) ) {
		require_once $file;
	}
}
Loader::init();

foreach ( $products as $product ) {
	Loader::add_product( $product );
}
