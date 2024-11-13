<?php
/**
 * Bricks builder integration.
 *
 * @since xx.xx.xx
 * @package EverestForms\Addons\BricksBuilder\BricksBuilder
 */
namespace EverestForms\Addons\BricksBuilder;

use EverestForms\Traits\Singleton;

/**
 * BricksBuilder.
 *
 * @since xx.xx.xx
 */
class BricksBuilder {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * @since xx.xx.xx
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Init.
	 *
	 * @since xx.xx.xx
	 */
	public function setup() {

		if ( ! Helper::is_bricks_active() ) {

			Helper::print_admin_notice();

			return;
		}

		if ( ! class_exists( '\Bricks\Elements' ) ) {
			return;
		}

		add_action(
			'init',
			array( $this, 'register_bricks_elements' ),
			11
		);
	}

	/**
	 * Register bricks elements.
	 *
	 * @since xx.xx.xx
	 */
	public function register_bricks_elements() {
		$element_files = array(
			__DIR__ . '/BricksFormWidget.php',
		);

		foreach ( $element_files as $file ) {
			\Bricks\Elements::register_element( $file );
		}

	}
}
