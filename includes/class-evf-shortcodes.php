<?php
/**
 * Shortcodes
 *
 * @package EverestForms\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EverestForms Shortcodes class.
 */
class EVF_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		self::init_shortcode_hooks();

		$shortcodes = array(
			'everest_form' => __CLASS__ . '::form',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'everest-forms',
			'before' => null,
			'after'  => null,
		)
	) {
		$form_id        = isset( $atts['id'] ) ? $atts['id'] : '';
		$is_theme_style = get_post_meta( $form_id, 'everest_forms_enable_theme_style', true );
		if ( 'default' === $is_theme_style ) {
			$wrapper['class'] .= ' evf-frontend-form-default';
			wp_register_style( 'evf-frontend-default-css', EVF()->plugin_url() . '/assets/css/everest-forms-default-frontend.css', array(), EVF_VERSION );
			wp_enqueue_style( 'evf-frontend-default-css' );
		}
		ob_start();

		$wrap_before = empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		echo wp_kses_post( $wrap_before );
		call_user_func( $function, $atts );
		$wrap_after = empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		echo wp_kses_post( $wrap_after );

		return ob_get_clean();
	}

	/**
	 * Form shortcode.
	 *
	 * @param  array $atts Attributes.
	 * @return string
	 */
	public static function form( $atts ) {
		return self::shortcode_wrapper( array( 'EVF_Shortcode_Form', 'output' ), $atts );
	}

	/**
	 * Initialize shortcode.
	 */
	public static function init_shortcode_hooks() {
		self::shortcode_wrapper( array( 'EVF_Shortcode_Form', 'hooks' ) );
	}
}
