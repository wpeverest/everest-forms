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
		 * Action to create WPBakery Widget for Everest Forms.
		 *
		 * @since xx.xx.xx
		 */
		add_action( 'vc_before_init', array( $this, 'evf_create_wpbakery_widget_category' ) );
	}

	/**
	 * Create WPBakery Widgets for Everest Forms.
	 *
	 * @since xx.xx.xx
	 */
	function evf_create_wpbakery_widget_category() {
		$evf_get_all_forms = evf_get_all_forms();
		$evf_all_forms     = array_flip( $evf_get_all_forms );

		if ( empty( $evf_all_forms ) ) {
			$evf_all_forms = array( '0' => esc_html__( 'Please create a form to use.', 'everest-forms' ) );
		} else {
			$evf_all_forms = array_merge( array( 0 => esc_html__( 'Select Form', 'everest-forms' ) ), $evf_all_forms );
		}

		vc_map(
			array(
				'name'        => esc_html__( 'Everest Forms', 'everest-forms' ),
				'base'        => 'everest_form',
				'icon'        => 'icon-wpb-vc_everest_forms',
				'category'    => esc_html__( 'Everest Forms', 'everest-forms' ),
				'description' => esc_html__( 'Everest Forms widget for WPBakery.', 'everest-forms' ),
				'params'      => array(
					array(
						'type'        => 'dropdown',
						'heading'     => esc_html__( 'Form', 'everest-forms' ),
						'param_name'  => 'id',
						'value'       => $evf_all_forms,
						'description' => esc_html__( 'Select Form.', 'everest-forms' ),
					),
				),
			)
		);

		do_action( 'everest_forms_add_wpbakery_widget' );
	}

}
