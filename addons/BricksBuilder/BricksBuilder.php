<?php
/**
 * Bricks builder integration.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\BricksBuilder\BricksBuilder
 */
namespace EverestForms\Addons\BricksBuilder;

use EverestForms\Traits\Singleton;

/**
 * BricksBuilder.
 *
 * @since 3.0.5
 */
class BricksBuilder {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * @since 3.0.5
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Init.
	 *
	 * @since 3.0.5
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
	 * @since 3.0.5
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
