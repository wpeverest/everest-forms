<?php
/**
 * EverestForm Elementor
 *
 * @package EverstForms\Class
 * @version 1.8.5
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Plugin as ElementorPlugin;

/**
 * Elementor class.
 */
class EVF_Elementor {

	/**
	 * Initialize.
	 */
	public function __construct() {

		$this->init();

	}

	/**
	 * Initialize elementor hooks.
	 *
	 * @since 1.6.0
	 */
	public function init() {

		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'evf_elementor_widget_categories' ) );
	}

	/**
	 * Register Everest forms Widget.
	 *
	 * @since 1.8.5
	 */
	public function register_widget() {
			// Include Widget files.
			require_once EVF_ABSPATH . 'includes/elementor/class-evf-widget.php';

			ElementorPlugin::instance()->widgets_manager->register( new EVF_Widget() );
	}

	/**
	 * Custom Widgets Category.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 *
	 * @since 1.8.5
	 */
	public function evf_elementor_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'everest-forms',
			array(
				'title' => esc_html__( 'Everest Forms', 'everest-forms' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}
}

new EVF_Elementor();
