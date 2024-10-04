<?php
/**
 * Divi builder integration.
 *
 * @since xx.xx.xx
 * @package EverestForms\Addons\DiviBuilder\DiviBuilder
 */
namespace EverestForms\Addons\DiviBuilder;

use EverestForms\Traits\Singleton;
use EverestForms\Addons\DiviBuilder\Helper;
use EverestForms\Addons\DiviBuilder\EverestFormsModule;

/**
 * DiviBuilder.
 *
 * @since xx.xx.xx
 */
class DiviBuilder {

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

		if ( ! Helper::is_divi_active() ) {

			Helper::print_admin_notice();

			return;
		}

		add_action( 'et_builder_ready', array( $this, 'everest_form_register_divi_builder' ) );
	}

	/**
	 * Function to check whether the divi module is loaded or not.
	 *
	 * @since xx.xx.xx
	 */
	public function everest_form_register_divi_builder() {
		if ( ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		new EverestFormsModule();
	}
}
