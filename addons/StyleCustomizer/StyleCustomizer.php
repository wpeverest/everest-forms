<?php
/**
 * Style Customizer.
 *
 * @since 3.0.5
 * @package EverestForms\Addons\StyleCustomizer
 */

namespace EverestForms\Addons\StyleCustomizer;

use EverestForms\Traits\Singleton;

/**
 * Style Customizer.
 *
 * @since 3.0.5
 */
class StyleCustomizer {

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
	 * Setup the customizer.
	 *
	 * @since 3.0.5
	 */
	public function setup() {

		if ( ! class_exists( 'EverestForms_Style_Customizer' ) ) {
			include_once dirname( __FILE__ ) . '/includes/class-everest-forms-style-customizer.php';
		}


		if ( class_exists( 'EverestForms_Style_Customizer' ) ) {
			\EverestForms_Style_Customizer::get_instance();
		}
	}
}
