<?php
/**
 * Oxygen builder integration.
 *
 * @since xx.xx.xx
 * @package EverestForms\Addons\OxygenBuilder\OxygenBuilder
 */
namespace EverestForms\Addons\OxygenBuilder;

use EverestForms\Traits\Singleton;
use EverestForms\Addons\OxygenBuilder\Helper;

/**
 * OxygenBuilder.
 *
 * @since xx.xx.xx
 */
class OxygenBuilder {

	use Singleton;
	/**
	 * Constructor.
	 *
	 * @since xx.xx.xx
	 */
	public function __construct() {
		$this->init();
	}
	/**
	 * Init.
	 *
	 * @since xx.xx.xx
	 */
	public function init() {

		if ( ! Helper::is_oxygen_active() ) {

			Helper::print_admin_notice();

			return;
		}

	}

}
