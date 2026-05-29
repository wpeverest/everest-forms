<?php
/**
 * Divi builder integration.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\DiviBuilder\DiviBuilder
 */
namespace EverestForms\Addons\DiviBuilder;

use EverestForms\Traits\Singleton;
use EverestForms\Addons\DiviBuilder\Helper;
use EverestForms\Addons\DiviBuilder\EverestFormsModule;

/**
 * DiviBuilder.
 *
 * @since 3.0.5
 */
class DiviBuilder {

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

		if ( ! Helper::is_divi_active() ) {

			Helper::print_admin_notice();

			return;
		}

		add_action( 'et_builder_ready', array( $this, 'everest_form_register_divi_builder' ) );
	}

	/**
	 * Function to check whether the divi module is loaded or not.
	 *
	 * @since 3.0.5
	 */
	public function everest_form_register_divi_builder() {
		if ( ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		new EverestFormsModule();
	}
}
