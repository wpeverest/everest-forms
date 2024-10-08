<?php
/**
 * Addons main files.
 *
 * @since xx.xx.xx
 * @package EverestForms\Addons\Addons
 */

namespace EverestForms\Addons;

use EverestForms\Addons\OxygenBuilder\OxygenBuilder;
use EverestForms\Addons\DiviBuilder\DiviBuilder;
use EverestForms\Traits\Singleton;

/**
 * Addon class.
 *
 * @since xx.xx.xx
 */
class Addons {

	use Singleton;

	/**
	 * Class constructor.
	 *
	 * @since xx.xx.xx
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'addons_init' ) );
	}

	/**
	 * Get addon list.
	 *
	 * @since xx.xx.xx
	 */
	public function get_addon_list() {
		/**
		 * Everest forms addon list.
		 *
		 * @since xx.xx.xx
		 * @return array List of addon class.
		 */
		return apply_filters(
			'everest_forms_addon_list',
			array(
				'oxygen-builder' => OxygenBuilder::class,
				'divi-builder'   => DiviBuilder::class,
			)
		);
	}

	/**
	 * Initializes the Everest Forms addons.
	 *
	 * @since xx.xx.xx
	 */
	public function addons_init() {

		$classes = $this->get_addon_list();

		if ( empty( $classes ) ) {
			return;
		}

		$enabled_features = get_option( 'everest_forms_enabled_features', array() );

		if ( empty( $enabled_features ) ) {
			return;
		}

		foreach ( $classes as $key => $class_name ) {
			$key = 'everest-forms-' . $key;
			if ( in_array( $key, $enabled_features, true ) ) {

				if ( class_exists( $class_name ) ) {
					$class_name::init();
				}
			}
		}
	}
}
