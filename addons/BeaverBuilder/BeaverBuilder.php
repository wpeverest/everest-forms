<?php
/**
 * Beaver builder integration.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\BeaverBuilder\BeaverBuilder
 */
namespace EverestForms\Addons\BeaverBuilder;

use EverestForms\Traits\Singleton;
use EverestForms\Addons\BeaverBuilder\Helper;
use EverestForms\Addons\BeaverBuilder\EverestFormModule;

/**
 * BeaverBuilder.
 *
 * @since 3.0.5
 */
class BeaverBuilder {

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
		if ( ! Helper::is_beaver_active() ) {

			Helper::print_admin_notice();

			return;
		}

		$this->init_elements();
	}

	/**
	 * Initialized modules.
	 *
	 * @since 1.10.0 [Free]
	 */
	public function init_elements() {
		if ( class_exists( 'FLBuilder' ) ) {
			\FLBuilder::register_module(
				EverestFormModule::class,
				Helper::get_everest_forms_setting()
			);
		}
	}
}
