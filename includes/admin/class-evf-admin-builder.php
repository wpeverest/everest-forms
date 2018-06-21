<?php
/**
 * EverestForms Admin Builder Class
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EVF_Admin_Builder', false ) ) :

	/**
	 * EVF_Admin_Builder Class.
	 */
	class EVF_Admin_Builder {

		/**
		 * Builder pages.
		 *
		 * @var array
		 */
		private static $builder = array();

		/**
		 * Include the builder page classes.
		 */
		public static function get_builder_pages() {
			if ( empty( self::$builder ) ) {
				$builder = array();

				include_once dirname( __FILE__ ) . '/builder/class-evf-builder-page.php';

				$builder[] = include 'builder/class-evf-builder-fields.php';
				$builder[] = include 'builder/class-evf-builder-settings.php';

				self::$builder = apply_filters( 'everest_forms_get_builder_pages', $builder );
			}

			return self::$builder;
		}
	}

endif;
