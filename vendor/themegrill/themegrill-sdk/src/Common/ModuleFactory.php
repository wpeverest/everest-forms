<?php
/**
 * The module factory class.
 *
 * @package     ThemeGrillSDK
 * @subpackage  Loader
 * @copyright   Copyright (c) 2025, ThemeGrill
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */

namespace ThemeGrillSDK\Common;

use ThemeGrillSDK\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Job_Factory
 *
 * @package ThemeGrillSDK\Common
 */
class ModuleFactory {
	/**
	 * Partners slugs.
	 *
	 * @var array $SLUGS Partners product slugs.
	 */
	public static $slugs = [];
	/**
	 * Partners domains.
	 *
	 * @var array $DOMAINS Partners domains.
	 */
	public static $domains = [];
	/**
	 * Map which contains all the modules loaded for each product.
	 *
	 * @var array Mapping array.
	 */
	private static $modules_attached = [];

	/**
	 * Load available modules for the selected product.
	 *
	 * @param Product $product Loaded product.
	 * @param array   $modules List of modules.
	 */
	public static function attach( $product, $modules ) {

		if ( ! isset( self::$modules_attached[ $product->get_slug() ] ) ) {
			self::$modules_attached[ $product->get_slug() ] = [];
		}

		foreach ( $modules as $module ) {
			$class = 'ThemeGrillSDK\\Modules\\' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $module ) ) );
			try {
				/** @var AbstractModule $module_object */
				$module_object = new $class( $product );

				if ( ! $module_object->can_load( $product ) ) {
					continue;
				}
				self::$modules_attached[ $product->get_slug() ][ $module ] = $module_object->load( $product );
			} catch ( \Throwable $e ) {
				continue;
			}
		}
	}

	/**
	 * Products/Modules loaded map.
	 *
	 * @return array Modules map.
	 */
	public static function get_modules_map() {
		return self::$modules_attached;
	}

	/**
	 * Get a specific loaded module for a product.
	 *
	 * @param string $product_slug Product slug.
	 * @param string $module_name  Module name (e.g. 'licenser').
	 *
	 * @return AbstractModule|null
	 */
	public static function get_module( $product_slug, $module_name ) {
		return self::$modules_attached[ $product_slug ][ $module_name ] ?? null;
	}
}
