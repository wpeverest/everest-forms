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
		$this->setup();
	}
	/**
	 * Init.
	 *
	 * @since xx.xx.xx
	 */
	public function setup() {

		if ( ! Helper::is_oxygen_active() ) {

			Helper::print_admin_notice();

			return;
		}

		if ( ! class_exists( 'OxyEl' ) ) {
			return;
		}

		add_action( 'oxygen_add_plus_sections', array( $this, 'add_accordion_section' ) );
		add_action( 'oxygen_add_plus_everest-forms_section_content', array( $this, 'register_add_plus_subsections' ) );

		new OxygenFormWidget();

	}

	/**
	 * Add accordin section in the elements.
	 *
	 * @since xx.xx.xx
	 */
	public function add_accordion_section() {
		$brand_name = __( 'Everest Forms', 'everest-forms' );
		\CT_Toolbar::oxygen_add_plus_accordion_section( 'everest-forms', $brand_name );
	}

	/**
	 * Add subsection.
	 *
	 * @since xx.xx.xx
	 */
	public function register_add_plus_subsections() {
		do_action( 'oxygen_add_plus_everest-forms_form' );
	}

}
