<?php
/**
 * Oxygen builder integration.
 *
 * @since xx.xx.xx
 * @package EverestForms\Addons\WPBakeryBuilder\WPBakeryBuilder
 */
namespace EverestForms\Addons\WPBakeryBuilder;

use EverestForms\Traits\Singleton;

/**
 * WPBakeryBuilder.
 *
 * @since xx.xx.xx
 */
class WPBakeryBuilder {

	use Singleton;

	public function __construct() {
		$this->init_hooks();
	}

	public static function init() {
		return new WPBakeryBuilder();
	}

	public function init_hooks() {
		/**
		 * Create WPBakery Widget for User Registration.
		 */
		add_action( 'vc_before_init', array( $this, 'create_wpbakery_widget_category' ) );
	}

	/**
	 * Create WPBakery Widgets for User Registration.
	 *
	 * @since 3.3.2
	 */
	function create_wpbakery_widget_category() {
		$evf_all_forms = evf_get_all_forms();
		if ( empty( $evf_all_forms ) ) {
			$evf_all_forms = array( '0' => esc_html__( 'Please create a form to use.', 'user-registration' ) );
		}

		vc_map(
			array(
				'name'        => esc_html__( 'Everest Forms', 'everest-forms' ),
				'base'        => 'output',
				'icon'        => 'icon-wpb-vc_everest_forms',
				'category'    => esc_html__( 'Everest Forms', 'everest-forms' ),
				'description' => esc_html__( 'Everest Forms widget for WPBakery.', 'user-registration' ),
				'params'      => array(
					array(
						'type'        => 'dropdown',
						'heading'     => esc_html__( 'Form', 'user-registration' ),
						'param_name'  => 'id',
						'value'       => $evf_all_forms, // Should be associative array
						'description' => esc_html__( 'Select Form.', 'user-registration' ),
					),
				),
			)
		);

		do_action( 'user_registration_add_wpbakery_widget' );
	}

}
