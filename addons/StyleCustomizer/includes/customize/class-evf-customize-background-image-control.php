<?php
/**
 * Customize API: EVF_Customize_Background_Image_Control class
 *
 * @package EverestForms_Style_Customizer\Customize
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customize Background Image Control class.
 *
 * @see WP_Customize_Image_Control
 */
class EVF_Customize_Background_Image_Control extends WP_Customize_Image_Control {

	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'evf-background';

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		parent::enqueue();

		$custom_background = get_theme_support( 'custom-background' );
		wp_localize_script(
			'everest-forms-customize-controls',
			'_wpCustomizeBackground',
			array(
				'defaults' => ! empty( $custom_background[0] ) ? $custom_background[0] : array(),
				'nonces'   => array(
					'add' => wp_create_nonce( 'background-add' ),
				),
			)
		);
	}
}
