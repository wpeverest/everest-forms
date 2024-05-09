<?php
/**
 * Everest Forms User Login block.
 *
 * @since 3.0.0
 * @package everest-forms
 */

defined( 'ABSPATH' ) || exit;
/**
 * Block form selector class.
 */
class EVF_Blocks_User_Login extends EVF_Blocks_Abstract {
	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'user-login';

	/**
	 * Build html.
	 *
	 * @param string $content Build html content.
	 * @return string
	 */
	protected function build_html( $content ) {
		$attr         = $this->attributes;
		$redirect_url = isset( $attr['redirect_url'] ) ? sanitize_text_field( $attr['redirect_url'] ) : '';
		$recaptcha    = isset( $attr['recaptcha'] ) ? sanitize_text_field( $attr['recaptcha'] ) : false;
		$params       = array();
		if ( ! empty( $redirect_url ) ) {
			$params['redirect_url'] = $redirect_url;
		}
		if ( ! empty( $recaptcha ) ) {
			$params['recaptcha'] = $recaptcha;
		}
		if ( ! class_exists( 'Everest_Forms_Login_Shortcode' ) ) {
			return $content;
		}
		$user_login_instance = new Everest_Forms_Login_Shortcode();
		return $user_login_instance->user_login( $params );
	}
}
