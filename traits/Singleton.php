<?php
/**
 * Singleton class trait.
 *
 * @package EverestForms\Traits
 */

namespace EverestForms\Traits;

/**
 * Singleton trait.
 */
trait Singleton {

	/**
	 * Holds single instance of the class.
	 *
	 * @var null|static
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return static
	 */
	final public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Disable un-serializing of the class.
	 *
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Disable cloning of the class.
	 *
	 * @return void
	 */
	public function __clone() {}
}
